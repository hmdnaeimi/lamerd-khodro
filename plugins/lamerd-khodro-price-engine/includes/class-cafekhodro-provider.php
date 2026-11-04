<?php
/**
 * Provider کافه خودرو.
 *
 * مستندات منبع: https://cofekhodro.ir/wp-json/cafekhodro/v1/tables
 *
 * @package LamerdKhodroPriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * پیاده‌سازی ارتباط با API کافه خودرو.
 *
 * این کلاس هیچ خروجی چاپ نمی‌کند و به `$_GET`/`$_POST` دسترسی ندارد؛
 * فقط HTTP و تبدیل ساختار.
 */
class LKP_CafeKhodro_Provider implements LKP_Provider {

	const BASE = 'https://cofekhodro.ir';
	const NS   = '/wp-json/cafekhodro/v1';

	/**
	 * تاخیرهای تلاش مجدد (ثانیه) طبق سند فنی: ۵ → ۱۵ → ۴۵.
	 *
	 * @return int[]
	 */
	protected function retry_delays(): array {
		$delays = apply_filters( 'lkp_retry_delays', array( 5, 15, 45 ) );
		return array_values( array_map( 'absint', (array) $delays ) );
	}

	/**
	 * متادیتای منبع.
	 *
	 * @return array{name:string,url:string,attribution:string,attribution_url:string}
	 */
	public function get_source_meta(): array {
		return array(
			'name'            => 'کافه خودرو',
			'url'             => self::BASE,
			'attribution'     => 'منبع: کافه خودرو',
			'attribution_url' => self::BASE,
		);
	}

	/**
	 * فهرست جدول‌ها.
	 *
	 * @return array<int, array{id:int,title:string,updated_at:?string}>
	 *
	 * @throws LKP_Provider_Exception در خطا.
	 */
	public function list_tables(): array {
		$data = $this->request( self::NS . '/tables' );

		if ( ! isset( $data['tables'] ) || ! is_array( $data['tables'] ) ) {
			throw new LKP_Provider_Exception( 'پاسخ فهرست جدول‌ها ساختار مورد انتظار را ندارد.', 'invalid_payload' );
		}

		$out = array();
		foreach ( $data['tables'] as $table ) {
			if ( ! is_array( $table ) || ! isset( $table['id'] ) ) {
				continue;
			}
			$out[] = array(
				'id'         => (int) $table['id'],
				'title'      => isset( $table['title'] ) ? (string) $table['title'] : '',
				'updated_at' => isset( $table['updated_at'] ) ? (string) $table['updated_at'] : null,
			);
		}

		return $out;
	}

	/**
	 * دریافت جدول قیمت.
	 *
	 * @param int $table_id شناسه جدول.
	 * @return LKP_TablePayload
	 *
	 * @throws LKP_Provider_Exception در خطا.
	 */
	public function fetch_table( int $table_id ): LKP_TablePayload {
		$data = $this->request( self::NS . '/tables/' . $table_id );

		if ( ! isset( $data['cars'] ) || ! is_array( $data['cars'] ) ) {
			throw new LKP_Provider_Exception( 'پاسخ جدول، فهرست خودروها را ندارد.', 'invalid_payload' );
		}

		$cars = array();
		foreach ( $data['cars'] as $car ) {
			$row = $this->normalize_car( $car );
			if ( $row ) {
				$cars[] = $row;
			}
		}

		return new LKP_TablePayload(
			(int) ( $data['id'] ?? $table_id ),
			(string) ( $data['title'] ?? '' ),
			isset( $data['updated_at'] ) ? (string) $data['updated_at'] : null,
			(string) ( $data['unit'] ?? '' ),
			(int) ( $data['count'] ?? count( $cars ) ),
			$cars
		);
	}

	/**
	 * تبدیل یک ردیف خام به شیء نرمال.
	 *
	 * @param mixed $car ردیف خام.
	 * @return LKP_Car_Row|null
	 */
	protected function normalize_car( $car ): ?LKP_Car_Row {
		if ( ! is_array( $car ) || ! isset( $car['id'], $car['name'] ) ) {
			return null;
		}

		$id = (int) $car['id'];
		if ( $id <= 0 ) {
			return null;
		}

		$change     = ( isset( $car['change'] ) && is_array( $car['change'] ) ) ? $car['change'] : array();
		$amount     = null;
		$percent    = null;

		if ( isset( $change['amount'] ) && is_numeric( $change['amount'] ) ) {
			$amount = (int) round( (float) $change['amount'] );
		}
		if ( isset( $change['percent'] ) && is_numeric( $change['percent'] ) ) {
			$percent = (float) $change['percent'];
		}

		return new LKP_Car_Row(
			$id,
			trim( wp_strip_all_tags( (string) $car['name'] ) ),
			isset( $car['group'] ) ? trim( wp_strip_all_tags( (string) $car['group'] ) ) : '',
			$this->to_amount( $car['factory_price'] ?? null ),
			$this->to_amount( $car['market_price'] ?? null ),
			$amount,
			$percent,
			! empty( $car['production_stopped'] )
		);
	}

	/**
	 * تبدیل ورودی به مبلغ تومان یا null (null هرگز صفر نمی‌شود).
	 *
	 * @param mixed $value مقدار.
	 * @return int|null
	 */
	protected function to_amount( $value ): ?int {
		if ( null === $value || '' === $value || false === $value ) {
			return null;
		}
		if ( ! is_numeric( $value ) ) {
			return null;
		}
		return (int) round( (float) $value );
	}

	/**
	 * درخواست HTTP با تلاش مجدد.
	 *
	 * @param string $path مسیر نسبی.
	 * @return array<string, mixed>
	 *
	 * @throws LKP_Provider_Exception در خطا.
	 */
	protected function request( string $path ): array {
		$url     = self::BASE . $path;
		$delays  = $this->retry_delays();
		$attempt = 0;
		$last    = null;

		do {
			try {
				return $this->single_request( $url );
			} catch ( LKP_Provider_Exception $e ) {
				$last = $e;
				if ( ! $e->is_retryable() || $attempt >= count( $delays ) ) {
					break;
				}
				$wait = $delays[ $attempt ];
				if ( $wait > 0 ) {
					sleep( $wait );
				}
				$attempt++;
			}
		} while ( $attempt <= count( $delays ) );

		throw $last ?? new LKP_Provider_Exception( 'درخواست به منبع ناموفق بود.', 'http' );
	}

	/**
	 * یک درخواست HTTP.
	 *
	 * @param string $url آدرس کامل.
	 * @return array<string, mixed>
	 *
	 * @throws LKP_Provider_Exception در خطا.
	 */
	protected function single_request( string $url ): array {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 15,
				'decompress'  => true,
				'redirection' => 3,
				'headers'     => array(
					'Accept'       => 'application/json',
					'User-Agent'   => 'LamerdKhodroPriceEngine/1.0 (+https://lamerdkhodro.ir)',
					'Cache-Control' => 'no-cache',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$code = $response->get_error_code();
			$type = ( 'http_request_timeout' === $code ) ? 'timeout' : 'http';
			throw new LKP_Provider_Exception( 'خطای شبکه: ' . $response->get_error_message(), $type );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = (string) wp_remote_retrieve_body( $response );

		if ( 429 === $status ) {
			throw new LKP_Provider_Exception( 'محدودیت نرخ درخواست منبع (429).', 'rate_limited', 429 );
		}

		if ( $status >= 500 ) {
			throw new LKP_Provider_Exception( 'خطای سرور منبع (' . $status . ').', 'server', $status );
		}

		if ( $status < 200 || $status >= 300 ) {
			throw new LKP_Provider_Exception( 'پاسخ نامعتبر منبع (' . $status . ').', 'http', $status );
		}

		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			throw new LKP_Provider_Exception( 'پاسخ منبع JSON معتبر نیست.', 'invalid_json', $status );
		}

		return $data;
	}
}
