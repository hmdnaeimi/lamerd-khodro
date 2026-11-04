<?php
/**
 * کلاس اصلی افزونه Price Engine.
 *
 * @package LamerdKhodroPriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * نمونه مرکزی و دسترسی به سرویس‌ها.
 */
final class LKP_Plugin {

	/**
	 * نمونه تنها.
	 */
	private static ?LKP_Plugin $instance = null;

	/**
	 * @var LKP_Price_Repository|null
	 */
	private ?LKP_Price_Repository $prices = null;

	/**
	 * @var LKP_Vehicle_Repository|null
	 */
	private ?LKP_Vehicle_Repository $vehicles = null;

	/**
	 * @var LKP_Sync_Service|null
	 */
	private ?LKP_Sync_Service $sync = null;

	/**
	 * @var LKP_Reports|null
	 */
	private ?LKP_Reports $reports = null;

	/**
	 * دریافت نمونه.
	 */
	public static function instance(): LKP_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * سازنده خصوصی.
	 */
	private function __construct() {}

	/**
	 * راه‌اندازی.
	 */
	public function init(): void {
		( new LKP_Scheduler() )->register();

		if ( is_admin() ) {
			( new LKP_Admin() )->register();
		}

		add_action( 'lkp_daily_maintenance', array( $this, 'daily_maintenance' ) );
		if ( ! wp_next_scheduled( 'lkp_daily_maintenance' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'lkp_daily_maintenance' );
		}
	}

	/**
	 * نگهداری روزانه: snapshot و پاک‌سازی.
	 */
	public function daily_maintenance(): void {
		$settings = LKP_Sync_Service::settings();
		if ( ! empty( $settings['enable_snapshots'] ) ) {
			$this->prices()->snapshot_today();
		}
	}

	/**
	 * مخزن قیمت.
	 */
	public function prices(): LKP_Price_Repository {
		if ( null === $this->prices ) {
			$this->prices = new LKP_Price_Repository();
		}
		return $this->prices;
	}

	/**
	 * مخزن خودروهای منبع.
	 */
	public function vehicles(): LKP_Vehicle_Repository {
		if ( null === $this->vehicles ) {
			$this->vehicles = new LKP_Vehicle_Repository();
		}
		return $this->vehicles;
	}

	/**
	 * سرویس همگام‌سازی.
	 */
	public function sync(): LKP_Sync_Service {
		if ( null === $this->sync ) {
			$this->sync = new LKP_Sync_Service();
		}
		return $this->sync;
	}

	/**
	 * گزارش‌ها.
	 */
	public function reports(): LKP_Reports {
		if ( null === $this->reports ) {
			$this->reports = new LKP_Reports();
		}
		return $this->reports;
	}
}
