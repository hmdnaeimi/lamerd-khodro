<?php
/**
 * اعتبارسنجی داده‌های دریافتی پیش از ذخیره.
 *
 * @package LamerdKhodroPriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * قواعد اعتبارسنجی؛ داده نامعتبر هرگز داده معتبر قبلی را بازنویسی نمی‌کند.
 */
class LKP_Validator {

	/**
	 * واحد پول پذیرفته‌شده.
	 */
	const EXPECTED_UNIT = 'toman';

	/**
	 * حداکثر مبلغ قابل قبول (تومان) — ۱۰۰ هزار میلیارد.
	 */
	const MAX_AMOUNT = 100000000000000;

	/**
	 * اعتبارسنجی کل پاسخ جدول.
	 *
	 * @param LKP_TablePayload $payload پاسخ.
	 * @return LKP_TablePayload
	 *
	 * @throws LKP_Provider_Exception اگر پاسخ در سطح کلی نامعتبر باشد.
	 */
	public function validate_payload( LKP_TablePayload $payload ): LKP_TablePayload {
		if ( self::EXPECTED_UNIT !== strtolower( trim( $payload->unit ) ) ) {
			throw new LKP_Provider_Exception(
				sprintf( 'واحد پول پاسخ «%s» است و پذیرفته نمی‌شود.', $payload->unit ),
				'invalid_payload'
			);
		}

		if ( $payload->count > 0 && count( $payload->cars ) !== $payload->count ) {
			throw new LKP_Provider_Exception(
				sprintf( 'تعداد خودروها (%d) با مقدار اعلام‌شده منبع (%d) نمی‌خواند.', count( $payload->cars ), $payload->count ),
				'invalid_payload'
			);
		}

		if ( ! $payload->cars ) {
			throw new LKP_Provider_Exception( 'پاسخ منبع هیچ خودرویی ندارد.', 'invalid_payload' );
		}

		$valid    = array();
		$warnings = array();
		$seen     = array();

		foreach ( $payload->cars as $car ) {
			$checked = $this->validate_car( $car, $warnings );
			if ( null === $checked ) {
				continue;
			}
			if ( isset( $seen[ $checked->external_id ] ) ) {
				$warnings[] = sprintf( 'خودروی تکراری با شناسه %d نادیده گرفته شد.', $checked->external_id );
				continue;
			}
			$seen[ $checked->external_id ] = true;
			$valid[]                       = $checked;
		}

		if ( ! $valid ) {
			throw new LKP_Provider_Exception( 'هیچ ردیف معتبری در پاسخ منبع باقی نماند.', 'invalid_payload' );
		}

		return new LKP_TablePayload(
			$payload->id,
			$payload->title,
			$payload->source_updated_at,
			self::EXPECTED_UNIT,
			$payload->count,
			$valid,
			$warnings
		);
	}

	/**
	 * اعتبارسنجی یک خودرو.
	 *
	 * @param LKP_Car_Row $car      ردیف.
	 * @param string[]    $warnings هشدارها (ارجاعی).
	 * @return LKP_Car_Row|null null یعنی ردیف غیرقابل استفاده است.
	 */
	protected function validate_car( LKP_Car_Row $car, array &$warnings ): ?LKP_Car_Row {
		if ( $car->external_id <= 0 ) {
			$warnings[] = 'ردیفی با شناسه نامعتبر نادیده گرفته شد.';
			return null;
		}

		$name = trim( $car->name );
		if ( '' === $name || mb_strlen( $name, 'UTF-8' ) > 190 ) {
			$warnings[] = sprintf( 'نام خودروی #%d نامعتبر است.', $car->external_id );
			return null;
		}

		$factory = $this->validate_amount( $car->factory, 'قیمت کارخانه', $car->external_id, $warnings );
		$market  = $this->validate_amount( $car->market, 'قیمت بازار', $car->external_id, $warnings );

		// منطق: قیمت بازار نباید کمتر از نصف قیمت کارخانه باشد (خطای آشکار داده).
		if ( null !== $factory && null !== $market && $factory > 0 && $market > 0 && $market < ( $factory * 0.5 ) ) {
			$warnings[] = sprintf(
				'قیمت بازار خودروی #%d (%s) کمتر از نصف قیمت کارخانه است؛ قیمت بازار نامعتبر تلقی شد.',
				$car->external_id,
				$name
			);
			$market = null;
		}

		$amount  = $this->validate_change_amount( $car->change, $car->external_id, $warnings );
		$percent = $this->validate_change_percent( $car->change_pct, $car->external_id, $warnings );

		return new LKP_Car_Row(
			$car->external_id,
			$name,
			mb_substr( $car->group, 0, 100, 'UTF-8' ),
			$factory,
			$market,
			$amount,
			$percent,
			$car->stopped
		);
	}

	/**
	 * اعتبارسنجی مبلغ قیمت.
	 *
	 * @param int|null $amount     مبلغ.
	 * @param string   $label      برچسب.
	 * @param int      $car_id     شناسه خودرو.
	 * @param string[] $warnings   هشدارها.
	 * @return int|null
	 */
	protected function validate_amount( ?int $amount, string $label, int $car_id, array &$warnings ): ?int {
		if ( null === $amount ) {
			return null;
		}

		if ( $amount < 0 ) {
			$warnings[] = sprintf( '%s خودروی #%d منفی بود و ناموجود ثبت شد.', $label, $car_id );
			return null;
		}

		if ( $amount > self::MAX_AMOUNT ) {
			$warnings[] = sprintf( '%s خودروی #%d از سقف مجاز بیشتر بود و ناموجود ثبت شد.', $label, $car_id );
			return null;
		}

		// صفر در منبع معمولاً یعنی «ثبت نشده»؛ به null تبدیل می‌شود تا null≠0 حفظ شود.
		if ( 0 === $amount ) {
			return null;
		}

		return $amount;
	}

	/**
	 * اعتبارسنجی مبلغ تغییر اعلام‌شده منبع.
	 *
	 * @param int|null $amount   مبلغ تغییر.
	 * @param int      $car_id   شناسه خودرو.
	 * @param string[] $warnings هشدارها.
	 * @return int|null
	 */
	protected function validate_change_amount( ?int $amount, int $car_id, array &$warnings ): ?int {
		if ( null === $amount ) {
			return null;
		}
		if ( abs( $amount ) > self::MAX_AMOUNT ) {
			$warnings[] = sprintf( 'مبلغ تغییر خودروی #%d نامعقول بود و ثبت نشد.', $car_id );
			return null;
		}
		return $amount;
	}

	/**
	 * اعتبارسنجی درصد تغییر.
	 *
	 * @param float|null $percent  درصد.
	 * @param int        $car_id   شناسه خودرو.
	 * @param string[]   $warnings هشدارها.
	 * @return float|null
	 */
	protected function validate_change_percent( ?float $percent, int $car_id, array &$warnings ): ?float {
		if ( null === $percent ) {
			return null;
		}
		if ( $percent < -100 || $percent > 300 ) {
			$warnings[] = sprintf( 'درصد تغییر خودروی #%d (%.2f) خارج از محدوده معقول بود و ثبت نشد.', $car_id, $percent );
			return null;
		}
		return round( $percent, 2 );
	}
}
