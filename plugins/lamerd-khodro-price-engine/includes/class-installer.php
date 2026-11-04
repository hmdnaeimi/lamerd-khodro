<?php
/**
 * نصب، ارتقای اسکیما و پاک‌سازی جداول اختصاصی.
 *
 * @package LamerdKhodroPriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * مدیریت ساختار دیتابیس موتور قیمت.
 */
class LKP_Installer {

	const DB_VERSION        = '1.0.0';
	const DB_VERSION_OPTION = 'lkp_db_version';
	const CAPABILITY        = 'manage_lkp_options';

	/**
	 * نام کامل جدول.
	 *
	 * @param string $name بخش بدون پیشوند.
	 * @return string
	 */
	public static function table( string $name ): string {
		global $wpdb;
		return $wpdb->prefix . 'lkp_' . $name;
	}

	/**
	 * فهرست جداول افزونه.
	 *
	 * @return string[]
	 */
	public static function tables(): array {
		return array( 'vehicles', 'current_prices', 'price_history', 'price_sources', 'sync_logs', 'price_snapshots' );
	}

	/**
	 * فعال‌سازی افزونه.
	 */
	public static function activate(): void {
		self::create_tables();
		self::install_capability();
		self::seed_source();
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );

		LKP_Scheduler::schedule();
	}

	/**
	 * غیرفعال‌سازی: فقط زمان‌بندی حذف می‌شود؛ داده‌ها حفظ می‌شوند.
	 */
	public static function deactivate(): void {
		LKP_Scheduler::clear();
		delete_option( 'lkp_sync_lock' );
	}

	/**
	 * ارتقای اسکیما هنگام تغییر نسخه.
	 */
	public static function maybe_upgrade(): void {
		if ( get_option( self::DB_VERSION_OPTION ) === self::DB_VERSION ) {
			return;
		}
		self::create_tables();
		self::install_capability();
		self::seed_source();
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );
	}

	/**
	 * افزودن دسترسی مدیریت قیمت به مدیر کل.
	 */
	public static function install_capability(): void {
		$role = get_role( 'administrator' );
		if ( $role && ! $role->has_cap( self::CAPABILITY ) ) {
			$role->add_cap( self::CAPABILITY );
		}
		if ( function_exists( 'lkc_has_price_engine' ) ) {
			$price_role = get_role( 'lkc_price_manager' );
			if ( $price_role ) {
				$price_role->add_cap( self::CAPABILITY );
			}
		}
	}

	/**
	 * ثبت منبع پیش‌فرض (کافه خودرو) در صورت نبود.
	 */
	public static function seed_source(): void {
		global $wpdb;

		$table = self::table( 'price_sources' );
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE api_base = %s LIMIT 1", 'https://cofekhodro.ir' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $exists ) {
			return;
		}

		$wpdb->insert(
			$table,
			array(
				'name'             => 'کافه خودرو',
				'api_base'         => 'https://cofekhodro.ir',
				'attribution_text' => 'منبع: کافه خودرو',
				'attribution_url'  => 'https://cofekhodro.ir',
				'is_active'        => 1,
				'settings'         => wp_json_encode(
					array(
						'table_ids' => array( 1 ),
						'timeout'   => 15,
						'retries'   => 3,
					)
				),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s' )
		);
	}

	/**
	 * ایجاد/به‌روزرسانی جداول با dbDelta.
	 */
	public static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();

		$vehicles = self::table( 'vehicles' );
		$current  = self::table( 'current_prices' );
		$history  = self::table( 'price_history' );
		$sources  = self::table( 'price_sources' );
		$logs     = self::table( 'sync_logs' );
		$snapshot = self::table( 'price_snapshots' );

		$sql = array();

		$sql[] = "CREATE TABLE {$sources} (
			id tinyint unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			api_base varchar(200) NOT NULL,
			attribution_text varchar(255) NOT NULL DEFAULT '',
			attribution_url varchar(200) NOT NULL DEFAULT '',
			is_active tinyint(1) NOT NULL DEFAULT 1,
			settings longtext NULL,
			last_success_at datetime NULL DEFAULT NULL,
			last_attempt_at datetime NULL DEFAULT NULL,
			last_error text NULL,
			request_count smallint unsigned NOT NULL DEFAULT 0,
			window_started_at datetime NULL DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY idx_active (is_active),
			KEY idx_api_base (api_base(100))
		) {$charset};";

		$sql[] = "CREATE TABLE {$vehicles} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			source_id tinyint unsigned NOT NULL DEFAULT 1,
			external_id bigint unsigned NOT NULL,
			name varchar(191) NOT NULL,
			name_normalized varchar(191) NOT NULL,
			brand_group varchar(100) NOT NULL DEFAULT '',
			post_id bigint unsigned NULL DEFAULT NULL,
			production_stopped tinyint(1) NOT NULL DEFAULT 0,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY uq_source_external (source_id,external_id),
			KEY idx_post_id (post_id),
			KEY idx_name_norm (name_normalized(100)),
			KEY idx_group (brand_group),
			KEY idx_active (is_active)
		) {$charset};";

		$sql[] = "CREATE TABLE {$current} (
			vehicle_id bigint unsigned NOT NULL,
			factory_price bigint unsigned NULL DEFAULT NULL,
			market_price bigint unsigned NULL DEFAULT NULL,
			change_amount bigint NULL DEFAULT NULL,
			change_percent decimal(7,2) NULL DEFAULT NULL,
			source_id tinyint unsigned NOT NULL DEFAULT 1,
			fetched_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			source_updated_at datetime NULL DEFAULT NULL,
			is_valid tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY  (vehicle_id),
			KEY idx_market (market_price),
			KEY idx_factory (factory_price),
			KEY idx_valid (is_valid),
			KEY idx_fetched (fetched_at)
		) {$charset};";

		$sql[] = "CREATE TABLE {$history} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			vehicle_id bigint unsigned NOT NULL,
			source_id tinyint unsigned NOT NULL DEFAULT 1,
			factory_price bigint unsigned NULL DEFAULT NULL,
			market_price bigint unsigned NULL DEFAULT NULL,
			factory_change bigint NULL DEFAULT NULL,
			market_change bigint NULL DEFAULT NULL,
			recorded_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			source_updated_at datetime NULL DEFAULT NULL,
			note varchar(40) NOT NULL DEFAULT 'price_change',
			PRIMARY KEY  (id),
			KEY idx_vehicle_time (vehicle_id,recorded_at),
			KEY idx_recorded (recorded_at)
		) {$charset};";

		$sql[] = "CREATE TABLE {$logs} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			started_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			finished_at datetime NULL DEFAULT NULL,
			trigger_type varchar(20) NOT NULL DEFAULT 'cron',
			status varchar(20) NOT NULL DEFAULT 'running',
			received int unsigned NOT NULL DEFAULT 0,
			created int unsigned NOT NULL DEFAULT 0,
			updated int unsigned NOT NULL DEFAULT 0,
			price_changed int unsigned NOT NULL DEFAULT 0,
			errors int unsigned NOT NULL DEFAULT 0,
			duration_ms int unsigned NOT NULL DEFAULT 0,
			error_details text NULL,
			PRIMARY KEY  (id),
			KEY idx_started (started_at),
			KEY idx_status (status)
		) {$charset};";

		$sql[] = "CREATE TABLE {$snapshot} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			snapshot_date date NOT NULL,
			vehicle_id bigint unsigned NOT NULL,
			factory_price bigint unsigned NULL DEFAULT NULL,
			market_price bigint unsigned NULL DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uq_day_vehicle (snapshot_date,vehicle_id),
			KEY idx_vehicle (vehicle_id)
		) {$charset};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}

	/**
	 * حذف کامل داده‌ها (فقط با تأیید صریح مدیر).
	 *
	 * @return bool
	 */
	public static function drop_all(): bool {
		global $wpdb;

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		foreach ( self::tables() as $table ) {
			$name = self::table( $table );
			$wpdb->query( "DROP TABLE IF EXISTS {$name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
		}

		delete_option( self::DB_VERSION_OPTION );
		delete_option( 'lkp_settings' );
		delete_option( 'lkp_sync_lock' );

		return true;
	}
}
