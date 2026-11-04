<?php
/**
 * کلاس اصلی افزونه هسته لامرد خودرو.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

/**
 * نقطه ورود ماژولار افزونه.
 */
final class LKC_Plugin {

	/**
	 * نمونه تنها.
	 */
	private static ?LKC_Plugin $instance = null;

	/**
	 * ماژول‌های فعال.
	 *
	 * @var array<string, object>
	 */
	private array $modules = array();

	/**
	 * دریافت نمونه.
	 */
	public static function instance(): LKC_Plugin {
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
	 * راه‌اندازی ماژول‌ها.
	 */
	public function init(): void {
		$this->modules['post_types']    = new LKC_Post_Types();
		$this->modules['taxonomies']    = new LKC_Taxonomies();
		$this->modules['listings']      = new LKC_Listings();
		$this->modules['meta_boxes']    = new LKC_Meta_Boxes();
		$this->modules['homepage']      = new LKC_Homepage_Settings();
		$this->modules['search']        = new LKC_Search();
		$this->modules['rest']          = new LKC_Rest();

		foreach ( $this->modules as $module ) {
			if ( method_exists( $module, 'register' ) ) {
				$module->register();
			}
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
		add_action( 'template_redirect', array( $this, 'track_vehicle_view' ) );
	}

	/**
	 * دسترسی به ماژول.
	 *
	 * @param string $key شناسه ماژول.
	 * @return object|null
	 */
	public function module( string $key ): ?object {
		return $this->modules[ $key ] ?? null;
	}

	/**
	 * سرویس خودروها (قرارداد عمومی بین افزونه‌ها و قالب).
	 */
	public function vehicles(): LKC_Vehicle_Service {
		static $service = null;
		if ( null === $service ) {
			$service = new LKC_Vehicle_Service();
		}
		return $service;
	}

	/**
	 * سرویس برندها (قرارداد عمومی بین افزونه‌ها و قالب).
	 */
	public function brands(): LKC_Brand_Service {
		static $service = null;
		if ( null === $service ) {
			$service = new LKC_Brand_Service();
		}
		return $service;
	}

	/**
	 * ثبت بازدید صفحه خودرو، یک‌بار در هر نشست مرورگر.
	 */
	public function track_vehicle_view(): void {
		if ( is_admin() || ! is_singular( 'vehicles' ) ) {
			return;
		}

		$post_id = (int) get_queried_object_id();
		if ( $post_id <= 0 ) {
			return;
		}

		$cookie = 'lkc_viewed_' . $post_id;
		if ( isset( $_COOKIE[ $cookie ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فقط خواندن پرچم بازدید.
			return;
		}

		$this->vehicles()->increment_views( $post_id );

		if ( headers_sent() ) {
			return;
		}

		setcookie(
			$cookie,
			'1',
			array(
				'expires'  => time() + DAY_IN_SECONDS,
				'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => defined( 'COOKIE_DOMAIN' ) ? (string) COOKIE_DOMAIN : '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
	}

	/**
	 * بارگذاری CSS/JS صفحات عمومی (مشترک بین همه صفحات).
	 */
	public function enqueue_frontend(): void {
		wp_enqueue_style(
			'lkc-frontend',
			LKC_URL . 'assets/frontend.css',
			array(),
			LKC_VERSION
		);
	}
}
