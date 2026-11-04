<?php
/**
 * رفتار فعال/غیرفعال‌سازی افزونه هسته.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

/**
 * ایجاد ساختارهای اولیه هنگام فعال‌سازی.
 */
class LKC_Activation {

	/**
	 * دسته‌بندی‌های اخبار سند نیازمندی‌ها.
	 *
	 * @return string[]
	 */
	public static function news_categories(): array {
		return array(
			'اخبار خودرو',
			'اخبار داخلی',
			'اخبار خارجی',
			'ایران خودرو',
			'سایپا',
			'خودروهای وارداتی',
			'خودروهای چینی',
			'شرایط فروش',
			'قیمت خودرو',
			'بررسی خودرو',
			'فناوری خودرو',
			'صنعت خودرو',
		);
	}

	/**
	 * فعال‌سازی.
	 */
	public static function activate(): void {
		// ایجاد دسته‌بندی‌ها (فقط ساختار؛ بدون محتوای ساختگی).
		foreach ( self::news_categories() as $cat_name ) {
			if ( ! category_exists( $cat_name ) ) {
				wp_insert_term( $cat_name, 'category', array( 'slug' => sanitize_title( $cat_name ) ) );
			}
		}

		// تنظیمات پیش‌فرض.
		if ( false === get_option( 'lkc_homepage_settings' ) ) {
			update_option( 'lkc_homepage_settings', LKC_Homepage_Settings::defaults(), false );
		}
		if ( false === get_option( 'lkc_seo_settings' ) ) {
			update_option(
				'lkc_seo_settings',
				array(
					'title_template' => '%s | لامرد خودرو',
					'description'    => 'لامرد خودرو؛ مرجع قیمت روز خودرو، بانک اطلاعات خودروهای داخلی و خارجی، مشخصات فنی، مقایسه و اخبار بازار خودرو.',
					'og_image'       => 0,
				),
				false
			);
		}

		// رول‌های سفارشی برای دسترسی‌های آینده (بدون تغییر رول‌های هسته).
		add_role( 'lkc_price_manager', 'مدیر قیمت خودرو', array( 'read' => true ) );

		self::register_cpt_flush();
	}

	/**
	 * غیرفعال‌سازی (داده‌ها حفظ می‌شوند).
	 */
	public static function deactivate(): void {
		// زمان‌بندی همگام‌سازی متعلق به افزونه Price Engine است و اینجا دستکاری نمی‌شود.
		self::register_cpt_flush();
	}

	/**
	 * ثبت مجدد rewrite rules پس از تغییر ساختار.
	 */
	private static function register_cpt_flush(): void {
		// CPT هنگام `init` ثبت می‌شود؛ فراخوانی مجدد برای flush امن است.
		( new LKC_Post_Types() )->register_post_type();
		( new LKC_Taxonomies() )->register_taxonomy();
		flush_rewrite_rules();
	}
}
