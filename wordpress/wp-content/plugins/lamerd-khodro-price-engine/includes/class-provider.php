<?php
/**
 * قرارداد Provider و ساختار خروجی نرمال‌شده.
 *
 * @package LamerdKhodroPriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * خطای لایه Provider (شامل نوع خطا برای تصمیم Retry).
 */
class LKP_Provider_Exception extends Exception {

	/** @var string یکی از: http, rate_limited, timeout, invalid_json, invalid_payload, config */
	protected string $type;

	/** @var int کد HTTP در صورت وجود. */
	protected int $status;

	/**
	 * سازنده.
	 *
	 * @param string $message پیام.
	 * @param string $type    نوع خطا.
	 * @param int    $status  کد HTTP.
	 */
	public function __construct( string $message, string $type = 'http', int $status = 0 ) {
		parent::__construct( $message );
		$this->type   = $type;
		$this->status = $status;
	}

	/**
	 * نوع خطا.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * کد HTTP.
	 *
	 * @return int
	 */
	public function get_status(): int {
		return $this->status;
	}

	/**
	 * آیا تلاش مجدد مجاز است؟
	 *
	 * @return bool
	 */
	public function is_retryable(): bool {
		if ( in_array( $this->type, array( 'rate_limited', 'timeout', 'server' ), true ) ) {
			return true;
		}
		return $this->type === 'http' && ( 0 === $this->status || $this->status >= 500 );
	}
}

/**
 * یک خودرو در پاسخ منبع، پس از نرمال‌سازی اولیه.
 */
class LKP_Car_Row {

	/**
	 * سازنده.
	 *
	 * @param int         $external_id شناسه در منبع.
	 * @param string      $name        نام.
	 * @param string      $group       گروه/برند اعلام‌شده منبع.
	 * @param int|null    $factory     قیمت کارخانه.
	 * @param int|null    $market      قیمت بازار.
	 * @param int|null    $change      تغییر مبلغ.
	 * @param float|null  $change_pct  تغییر درصد.
	 * @param bool        $stopped     توقف تولید.
	 */
	public function __construct(
		public int $external_id,
		public string $name,
		public string $group,
		public ?int $factory,
		public ?int $market,
		public ?int $change,
		public ?float $change_pct,
		public bool $stopped
	) {}
}

/**
 * خروجی نرمال‌شده یک جدول قیمت.
 */
class LKP_TablePayload {

	/**
	 * سازنده.
	 *
	 * @param int            $id               شناسه جدول در منبع.
	 * @param string         $title            عنوان.
	 * @param string|null    $source_updated_at زمان اعلام منبع.
	 * @param string         $unit             واحد پول.
	 * @param int            $count            تعداد اعلام‌شده.
	 * @param LKP_Car_Row[]  $cars             ردیف‌ها.
	 * @param string[]       $warnings         هشدارهای اعتبارسنجی.
	 */
	public function __construct(
		public int $id,
		public string $title,
		public ?string $source_updated_at,
		public string $unit,
		public int $count,
		public array $cars,
		public array $warnings = array()
	) {}
}

/**
 * قرارداد منبع قیمت.
 */
interface LKP_Provider {

	/**
	 * متادیتای منبع.
	 *
	 * @return array{name:string,url:string,attribution:string,attribution_url:string}
	 */
	public function get_source_meta(): array;

	/**
	 * فهرست جدول‌های موجود در منبع.
	 *
	 * @return array<int, array{id:int,title:string,updated_at:?string}>
	 *
	 * @throws LKP_Provider_Exception در خطای شبکه/پاسخ.
	 */
	public function list_tables(): array;

	/**
	 * دریافت یک جدول.
	 *
	 * @param int $table_id شناسه جدول.
	 * @return LKP_TablePayload
	 *
	 * @throws LKP_Provider_Exception در خطای شبکه/پاسخ.
	 */
	public function fetch_table( int $table_id ): LKP_TablePayload;
}
