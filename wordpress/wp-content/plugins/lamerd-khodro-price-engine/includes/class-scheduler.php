<?php
/**
 * زمان‌بندی همگام‌سازی با WP-Cron.
 *
 * @package LamerdKhodroPriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * مدیریت رویداد cron.
 */
class LKP_Scheduler {

	const HOOK = 'lkp_price_sync';

	/**
	 * فواصل مجاز (دقیقه).
	 *
	 * @return int[]
	 */
	public static function allowed_intervals(): array {
		return array( 15, 30, 60, 180, 1440 );
	}

	/**
	 * اتصال هوک‌ها.
	 */
	public function register(): void {
		add_action( self::HOOK, array( $this, 'run' ) );
		add_filter( 'cron_schedules', array( $this, 'add_schedules' ) ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
	}

	/**
	 * افزودن بازه‌های اختصاصی.
	 *
	 * @param array<string, array<string, mixed>> $schedules بازه‌ها.
	 * @return array<string, array<string, mixed>>
	 */
	public function add_schedules( array $schedules ): array {
		foreach ( self::allowed_intervals() as $minutes ) {
			$schedules[ 'lkp_' . $minutes . 'min' ] = array(
				'interval' => $minutes * MINUTE_IN_SECONDS,
				'display'  => sprintf(
					/* translators: %d: minutes */
					__( 'هر %d دقیقه (لامرد خودرو)', 'lamerd-khodro' ),
					$minutes
				),
			);
		}

		return $schedules;
	}

	/**
	 * نام بازه برای مقدار دقیقه.
	 *
	 * @param int $minutes دقیقه.
	 * @return string
	 */
	public static function schedule_name( int $minutes ): string {
		if ( ! in_array( $minutes, self::allowed_intervals(), true ) ) {
			$minutes = 60;
		}
		return 'lkp_' . $minutes . 'min';
	}

	/**
	 * برنامه‌ریزی رویداد.
	 */
	public static function schedule(): void {
		$settings = LKP_Sync_Service::settings();
		$minutes  = (int) ( $settings['sync_interval'] ?? 60 );
		$recur    = self::schedule_name( $minutes );

		$existing = wp_get_scheduled_event( self::HOOK );
		if ( $existing && $existing->schedule === $recur ) {
			return;
		}

		wp_clear_scheduled_hook( self::HOOK );
		wp_schedule_event( time() + ( 5 * MINUTE_IN_SECONDS ), $recur, self::HOOK );
	}

	/**
	 * حذف رویداد.
	 */
	public static function clear(): void {
		wp_clear_scheduled_hook( self::HOOK );
	}

	/**
	 * اجرای همگام‌سازی از cron.
	 */
	public function run(): void {
		lamerd_khodro_price_engine()->sync()->run( 'cron' );
	}

	/**
	 * اطلاعات زمان‌بندی برای نمایش در پیشخوان.
	 *
	 * @return array{next:?string,recurrence:string,interval:int}
	 */
	public static function info(): array {
		$event = wp_get_scheduled_event( self::HOOK );
		$settings = LKP_Sync_Service::settings();

		return array(
			'next'       => $event ? get_date_from_gmt( gmdate( 'Y-m-d H:i:s', (int) $event->timestamp ) ) : null,
			'recurrence' => $event ? (string) $event->schedule : '—',
			'interval'   => (int) ( $settings['sync_interval'] ?? 60 ),
		);
	}
}
