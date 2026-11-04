<?php
/**
 * پنل مدیریت موتور قیمت.
 *
 * @package LamerdKhodroPriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * منوها، دارایی‌ها و پردازش فرم‌ها.
 */
class LKP_Admin {

	const SLUG = 'lkp-dashboard';

	/**
	 * اتصال هوک‌ها.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );

		add_action( 'admin_post_lkp_run_sync', array( $this, 'handle_run_sync' ) );
		add_action( 'admin_post_lkp_link_vehicle', array( $this, 'handle_link_vehicle' ) );
		add_action( 'admin_post_lkp_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'admin_post_lkp_drop_data', array( $this, 'handle_drop_data' ) );

		( new LKP_Export() )->register();
	}

	/**
	 * ثبت منو.
	 */
	public function menu(): void {
		add_menu_page(
			__( 'مدیریت قیمت خودرو', 'lamerd-khodro' ),
			__( 'قیمت خودرو', 'lamerd-khodro' ),
			LKP_Installer::CAPABILITY,
			self::SLUG,
			array( 'LKP_Admin_Pages', 'dashboard' ),
			'dashicons-chart-line',
			4
		);

		$pages = array(
			'dashboard' => array( __( 'داشبورد', 'lamerd-khodro' ), 'dashboard' ),
			'vehicles'  => array( __( 'خودروها و اتصال', 'lamerd-khodro' ), 'vehicles' ),
			'prices'    => array( __( 'آخرین قیمت‌ها', 'lamerd-khodro' ), 'prices' ),
			'history'   => array( __( 'تاریخچه قیمت', 'lamerd-khodro' ), 'history' ),
			'reports'   => array( __( 'گزارش‌ها', 'lamerd-khodro' ), 'reports' ),
			'sources'   => array( __( 'منابع', 'lamerd-khodro' ), 'sources' ),
			'sync'      => array( __( 'همگام‌سازی', 'lamerd-khodro' ), 'sync' ),
			'settings'  => array( __( 'تنظیمات', 'lamerd-khodro' ), 'settings' ),
		);

		foreach ( $pages as $slug => $page ) {
			add_submenu_page(
				self::SLUG,
				$page[0],
				$page[0],
				LKP_Installer::CAPABILITY,
				'dashboard' === $slug ? self::SLUG : 'lkp-' . $slug,
				'LKP_Admin_Pages::' . $page[1]
			);
		}
	}

	/**
	 * بارگذاری استایل پیشخوان در صفحات افزونه.
	 *
	 * @param string $hook صفحه جاری.
	 */
	public function assets( string $hook ): void {
		if ( false === strpos( $hook, 'lkp-' ) ) {
			return;
		}

		wp_enqueue_style( 'lkp-admin', LKP_URL . 'assets/admin.css', array(), LKP_VERSION );
	}

	/**
	 * اجرای دستی همگام‌سازی.
	 */
	public function handle_run_sync(): void {
		$this->guard( 'lkp_run_sync' );

		$result = lamerd_khodro_price_engine()->sync()->run( 'manual' );

		$status = (string) ( $result['status'] ?? 'failed' );
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => 'lkp-sync',
					'lkp_done' => $status,
					'count'    => (int) ( $result['stats']['price_changed'] ?? 0 ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * اتصال یا قطع اتصال خودروی منبع به نوشته CPT.
	 */
	public function handle_link_vehicle(): void {
		$this->guard( 'lkp_link_vehicle' );

		$vehicle_id = isset( $_POST['lkp_vehicle_id'] ) ? absint( wp_unslash( $_POST['lkp_vehicle_id'] ) ) : 0;
		$post_id    = isset( $_POST['lkp_post_id'] ) ? absint( wp_unslash( $_POST['lkp_post_id'] ) ) : 0;
		$action     = isset( $_POST['lkp_action'] ) ? sanitize_key( wp_unslash( $_POST['lkp_action'] ) ) : 'link';

		if ( ! $vehicle_id ) {
			wp_die( esc_html__( 'شناسه خودرو نامعتبر است.', 'lamerd-khodro' ) );
		}

		$vehicles = lamerd_khodro_price_engine()->vehicles();
		$vehicle  = $vehicles->find( $vehicle_id );
		if ( ! $vehicle ) {
			wp_die( esc_html__( 'خودروی منبع یافت نشد.', 'lamerd-khodro' ) );
		}

		if ( 'unlink' === $action ) {
			if ( ! empty( $vehicle['post_id'] ) ) {
				delete_post_meta( (int) $vehicle['post_id'], '_lkp_price_vehicle_id' );
			}
			$vehicles->set_post( $vehicle_id, null );
			$msg = 'unlinked';
		} else {
			$post = $post_id ? get_post( $post_id ) : null;
			if ( ! $post || 'vehicles' !== $post->post_type ) {
				wp_die( esc_html__( 'نوشته انتخاب‌شده از نوع «خودرو» نیست.', 'lamerd-khodro' ) );
			}

			$vehicles->set_post( $vehicle_id, $post_id );
			update_post_meta( $post_id, '_lkp_price_vehicle_id', $vehicle_id );
			$msg = 'linked';
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'lkp-vehicles',
					'lkp_done' => $msg,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * ذخیره تنظیمات.
	 */
	public function handle_save_settings(): void {
		$this->guard( 'lkp_save_settings' );

		$interval  = isset( $_POST['sync_interval'] ) ? absint( wp_unslash( $_POST['sync_interval'] ) ) : 60;
		$tables    = isset( $_POST['table_ids'] ) ? (string) wp_unslash( $_POST['table_ids'] ) : '1';
		$snapshots = isset( $_POST['enable_snapshots'] ) ? 1 : 0;
		$retention = isset( $_POST['retention_days'] ) ? absint( wp_unslash( $_POST['retention_days'] ) ) : 730;

		if ( ! in_array( $interval, LKP_Scheduler::allowed_intervals(), true ) ) {
			$interval = 60;
		}

		$table_ids = array_values( array_filter( array_map( 'absint', explode( ',', $tables ) ) ) );
		if ( ! $table_ids ) {
			$table_ids = array( 1 );
		}

		update_option(
			'lkp_settings',
			array(
				'sync_interval'    => $interval,
				'table_ids'        => $table_ids,
				'enable_snapshots' => $snapshots,
				'retention_days'   => max( 30, $retention ),
			),
			false
		);

		LKP_Scheduler::schedule();

		wp_safe_redirect( add_query_arg( array( 'page' => 'lkp-settings', 'lkp_done' => 'saved' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * حذف کامل داده‌ها (با تأیید متنی).
	 */
	public function handle_drop_data(): void {
		$this->guard( 'lkp_drop_data' );

		$confirm = isset( $_POST['lkp_confirm'] ) ? sanitize_text_field( wp_unslash( $_POST['lkp_confirm'] ) ) : '';
		if ( 'DELETE' !== $confirm ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'lkp-settings', 'lkp_done' => 'drop_aborted' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'این عملیات فقط برای مدیر کل مجاز است.', 'lamerd-khodro' ), 403 );
		}

		LKP_Installer::drop_all();
		LKP_Scheduler::clear();

		wp_safe_redirect( add_query_arg( array( 'page' => 'lkp-settings', 'lkp_done' => 'dropped' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * بررسی دسترسی و nonce.
	 *
	 * @param string $action نام nonce.
	 */
	protected function guard( string $action ): void {
		if ( ! current_user_can( LKP_Installer::CAPABILITY ) ) {
			wp_die( esc_html__( 'دسترسی مجاز نیست.', 'lamerd-khodro' ), 403 );
		}
		check_admin_referer( $action );
	}
}
