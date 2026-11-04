<?php
/**
 * تنظیمات بخش‌های صفحه اصلی (مدیریت از پیشخوان).
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

/**
 * صفحه تنظیمات صفحه اصلی در پیشخوان.
 */
class LKC_Homepage_Settings {

	const OPTION = 'lkc_homepage_settings';

	/**
	 * مقادیر پیش‌فرض.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'hero_title'       => 'لامرد خودرو؛ مرجع قیمت و اطلاعات خودرو',
			'hero_text'        => 'قیمت روز خودروهای داخلی و وارداتی، مشخصات فنی، مقایسه و آخرین اخبار بازار — به‌روزرسانی روزانه از منابع معتبر.',
			'hero_image'       => 0,
			'hero_search'      => 1,
			'show_prices'      => 1,
			'prices_count'     => 6,
			'show_brands'      => 1,
			'brands_count'     => 12,
			'show_new_cars'    => 1,
			'new_cars_count'   => 6,
			'show_popular'     => 1,
			'popular_count'    => 6,
			'show_news'        => 1,
			'news_count'       => 4,
			'show_reviews'     => 1,
			'reviews_count'    => 4,
			'show_compare_cta' => 1,
			'show_sales'       => 1,
			'sales_count'      => 4,
			'show_listings'    => 1,
			'listings_count'   => 6,
			'footer_about'     => 'لامرد خودرو پلتفرم تخصصی اطلاع‌رسانی خودرو است؛ قیمت روز، بانک اطلاعات خودرو، مشخصات فنی، مقایسه و اخبار بازار در یک مرجع واحد.',
			'social_telegram'  => '',
			'social_instagram' => '',
			'social_eita'      => '',
			'contact_phone'    => '',
			'contact_email'    => '',
			'contact_address'  => '',
		);
	}

	/**
	 * خواندن تنظیم با پیش‌فرض.
	 *
	 * @param string $key کلید.
	 * @return mixed
	 */
	public static function get( string $key ) {
		$settings = get_option( self::OPTION, array() );
		$defaults = self::defaults();
		return $settings[ $key ] ?? $defaults[ $key ] ?? '';
	}

	/**
	 * اتصال هوک‌ها.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * ثبت منوی تنظیمات.
	 */
	public function add_menu(): void {
		add_menu_page(
			__( 'تنظیمات سایت لامرد خودرو', 'lamerd-khodro' ),
			__( 'لامرد خودرو', 'lamerd-khodro' ),
			'manage_options',
			'lkc-settings',
			array( $this, 'render_page' ),
			'dashicons-admin-customizer',
			3
		);

		add_submenu_page(
			'lkc-settings',
			__( 'صفحه اصلی', 'lamerd-khodro' ),
			__( 'صفحه اصلی', 'lamerd-khodro' ),
			'manage_options',
			'lkc-settings',
			array( $this, 'render_page' )
		);

		add_submenu_page(
			'lkc-settings',
			__( 'سئو و متا', 'lamerd-khodro' ),
			__( 'سئو و متا', 'lamerd-khodro' ),
			'manage_options',
			'lkc-seo',
			array( $this, 'render_seo_page' )
		);
	}

	/**
	 * ثبت تنظیمات.
	 */
	public function register_settings(): void {
		register_setting(
			'lkc_homepage_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);

		register_setting(
			'lkc_seo_group',
			'lkc_seo_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_seo' ),
			)
		);
	}

	/**
	 * پاک‌سازی تنظیمات صفحه اصلی.
	 *
	 * @param array<string, mixed> $input ورودی.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ): array {
		$defaults = self::defaults();
		$clean    = array();

		$clean['hero_title'] = isset( $input['hero_title'] ) ? sanitize_text_field( $input['hero_title'] ) : $defaults['hero_title'];
		$clean['hero_text']  = isset( $input['hero_text'] ) ? sanitize_textarea_field( $input['hero_text'] ) : $defaults['hero_text'];
		$clean['hero_image'] = isset( $input['hero_image'] ) ? absint( $input['hero_image'] ) : 0;

		$int_fields = array(
			'hero_search',
			'show_prices',
			'prices_count',
			'show_brands',
			'brands_count',
			'show_new_cars',
			'new_cars_count',
			'show_popular',
			'popular_count',
			'show_news',
			'news_count',
			'show_reviews',
			'reviews_count',
			'show_compare_cta',
			'show_sales',
			'sales_count',
			'show_listings',
			'listings_count',
		);
		foreach ( $int_fields as $key ) {
			$clean[ $key ] = isset( $input[ $key ] ) ? absint( $input[ $key ] ) : $defaults[ $key ];
		}

		$clean['footer_about'] = isset( $input['footer_about'] ) ? sanitize_textarea_field( $input['footer_about'] ) : $defaults['footer_about'];

		foreach ( array( 'social_telegram', 'social_instagram', 'social_eita', 'contact_phone', 'contact_email', 'contact_address' ) as $key ) {
			$raw      = isset( $input[ $key ] ) ? (string) $input[ $key ] : '';
			$clean[ $key ] = in_array( $key, array( 'social_telegram', 'social_instagram', 'social_eita' ), true )
				? esc_url_raw( $raw )
				: sanitize_text_field( $raw );
		}

		$clean['contact_email'] = sanitize_email( $clean['contact_email'] );

		return $clean;
	}

	/**
	 * پاک‌سازی تنظیمات سئو.
	 *
	 * @param array<string, mixed> $input ورودی.
	 * @return array<string, mixed>
	 */
	public function sanitize_seo( $input ): array {
		return array(
			'title_template' => isset( $input['title_template'] ) ? sanitize_text_field( $input['title_template'] ) : '%s | لامرد خودرو',
			'description'    => isset( $input['description'] ) ? sanitize_textarea_field( $input['description'] ) : '',
			'og_image'       => isset( $input['og_image'] ) ? absint( $input['og_image'] ) : 0,
		);
	}

	/**
	 * رندر صفحه تنظیمات.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = array_merge( self::defaults(), (array) get_option( self::OPTION, array() ) );
		?>
		<div class="wrap lkc-settings-wrap" dir="rtl">
			<h1><?php esc_html_e( 'تنظیمات صفحه اصلی لامرد خودرو', 'lamerd-khodro' ); ?></h1>
			<p class="description"><?php esc_html_e( 'تمام بخش‌های صفحه اصلی از همین‌جا مدیریت می‌شوند؛ تغییرات بلافاصله در سایت اعمال می‌شود.', 'lamerd-khodro' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'lkc_homepage_group' ); ?>

				<h2><?php esc_html_e( 'بخش Hero', 'lamerd-khodro' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="hero_title"><?php esc_html_e( 'عنوان اصلی', 'lamerd-khodro' ); ?></label></th>
						<td><input type="text" class="large-text" id="hero_title" name="<?php echo esc_attr( self::OPTION ); ?>[hero_title]" value="<?php echo esc_attr( $settings['hero_title'] ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="hero_text"><?php esc_html_e( 'توضیح کوتاه', 'lamerd-khodro' ); ?></label></th>
						<td><textarea class="large-text" rows="3" id="hero_text" name="<?php echo esc_attr( self::OPTION ); ?>[hero_text]"><?php echo esc_textarea( $settings['hero_text'] ); ?></textarea></td>
					</tr>
					<tr>
						<th><label for="hero_image"><?php esc_html_e( 'شناسه تصویر Hero (اختیاری)', 'lamerd-khodro' ); ?></label></th>
						<td>
							<input type="number" min="0" id="hero_image" name="<?php echo esc_attr( self::OPTION ); ?>[hero_image]" value="<?php echo esc_attr( (string) $settings['hero_image'] ); ?>" />
							<p class="description"><?php esc_html_e( 'در صورت خالی بودن، تصویر پیش‌فرض قالب استفاده می‌شود. برای آپلود از رسانه‌ها شناسه را وارد کنید.', 'lamerd-khodro' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'جستجوی خودرو در Hero', 'lamerd-khodro' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[hero_search]" value="1" <?php checked( ! empty( $settings['hero_search'] ) ); ?> /> <?php esc_html_e( 'نمایش باکس جستجو', 'lamerd-khodro' ); ?></label></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'بخش‌های صفحه اصلی', 'lamerd-khodro' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><?php esc_html_e( 'قیمت روز', 'lamerd-khodro' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_prices]" value="1" <?php checked( ! empty( $settings['show_prices'] ) ); ?> /> <?php esc_html_e( 'فعال', 'lamerd-khodro' ); ?></label>
							&nbsp; <label><?php esc_html_e( 'تعداد:', 'lamerd-khododro' ); ?>
							<input type="number" min="1" max="20" name="<?php echo esc_attr( self::OPTION ); ?>[prices_count]" value="<?php echo esc_attr( (string) $settings['prices_count'] ); ?>" style="width:70px" /></label>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'برندها', 'lamerd-khodro' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_brands]" value="1" <?php checked( ! empty( $settings['show_brands'] ) ); ?> /> <?php esc_html_e( 'فعال', 'lamerd-khodro' ); ?></label>
							&nbsp; <label><?php esc_html_e( 'تعداد:', 'lamerd-khodro' ); ?>
							<input type="number" min="1" max="40" name="<?php echo esc_attr( self::OPTION ); ?>[brands_count]" value="<?php echo esc_attr( (string) $settings['brands_count'] ); ?>" style="width:70px" /></label>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'خودروهای جدید', 'lamerd-khodro' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_new_cars]" value="1" <?php checked( ! empty( $settings['show_new_cars'] ) ); ?> /> <?php esc_html_e( 'فعال', 'lamerd-khodro' ); ?></label>
							&nbsp; <label><?php esc_html_e( 'تعداد:', 'lamerd-khodro' ); ?>
							<input type="number" min="1" max="20" name="<?php echo esc_attr( self::OPTION ); ?>[new_cars_count]" value="<?php echo esc_attr( (string) $settings['new_cars_count'] ); ?>" style="width:70px" /></label>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'خودروهای محبوب', 'lamerd-khodro' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_popular]" value="1" <?php checked( ! empty( $settings['show_popular'] ) ); ?> /> <?php esc_html_e( 'فعال', 'lamerd-khodro' ); ?></label>
							&nbsp; <label><?php esc_html_e( 'تعداد:', 'lamerd-khodro' ); ?>
							<input type="number" min="1" max="20" name="<?php echo esc_attr( self::OPTION ); ?>[popular_count]" value="<?php echo esc_attr( (string) $settings['popular_count'] ); ?>" style="width:70px" /></label>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'اخبار خودرو', 'lamerd-khodro' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_news]" value="1" <?php checked( ! empty( $settings['show_news'] ) ); ?> /> <?php esc_html_e( 'فعال', 'lamerd-khodro' ); ?></label>
							&nbsp; <label><?php esc_html_e( 'تعداد:', 'lamerd-khodro' ); ?>
							<input type="number" min="1" max="12" name="<?php echo esc_attr( self::OPTION ); ?>[news_count]" value="<?php echo esc_attr( (string) $settings['news_count'] ); ?>" style="width:70px" /></label>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'بررسی خودرو', 'lamerd-khodro' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_reviews]" value="1" <?php checked( ! empty( $settings['show_reviews'] ) ); ?> /> <?php esc_html_e( 'فعال', 'lamerd-khodro' ); ?></label>
							&nbsp; <label><?php esc_html_e( 'تعداد:', 'lamerd-khodro' ); ?>
							<input type="number" min="1" max="12" name="<?php echo esc_attr( self::OPTION ); ?>[reviews_count]" value="<?php echo esc_attr( (string) $settings['reviews_count'] ); ?>" style="width:70px" /></label>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'CTA مقایسه خودرو', 'lamerd-khodro' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_compare_cta]" value="1" <?php checked( ! empty( $settings['show_compare_cta'] ) ); ?> /> <?php esc_html_e( 'فعال', 'lamerd-khodro' ); ?></label></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'آگهی‌های فروش خودرو', 'lamerd-khodro' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_listings]" value="1" <?php checked( ! empty( $settings['show_listings'] ) ); ?> /> <?php esc_html_e( 'فعال', 'lamerd-khodro' ); ?></label>
							&nbsp; <label><?php esc_html_e( 'تعداد:', 'lamerd-khodro' ); ?>
							<input type="number" min="1" max="12" name="<?php echo esc_attr( self::OPTION ); ?>[listings_count]" value="<?php echo esc_attr( (string) $settings['listings_count'] ); ?>" style="width:70px" /></label>
							<p class="description"><?php esc_html_e( 'علاوه بر این تعداد، حداکثر ۲ آگهی فروش ویژه فعال هم نمایش داده می‌شود.', 'lamerd-khodro' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'شرایط فروش', 'lamerd-khodro' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_sales]" value="1" <?php checked( ! empty( $settings['show_sales'] ) ); ?> /> <?php esc_html_e( 'فعال', 'lamerd-khodro' ); ?></label>
							&nbsp; <label><?php esc_html_e( 'تعداد:', 'lamerd-khodro' ); ?>
							<input type="number" min="1" max="12" name="<?php echo esc_attr( self::OPTION ); ?>[sales_count]" value="<?php echo esc_attr( (string) $settings['sales_count'] ); ?>" style="width:70px" /></label>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'فوتر و ارتباط', 'lamerd-khodro' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="footer_about"><?php esc_html_e( 'معرفی در فوتر', 'lamerd-khodro' ); ?></label></th>
						<td><textarea class="large-text" rows="3" id="footer_about" name="<?php echo esc_attr( self::OPTION ); ?>[footer_about]"><?php echo esc_textarea( $settings['footer_about'] ); ?></textarea></td>
					</tr>
					<tr><th><?php esc_html_e( 'شبکه‌های اجتماعی', 'lamerd-khodro' ); ?></th>
						<td>
							<p><label><?php esc_html_e( 'تلگرام:', 'lamerd-khodro' ); ?> <input type="url" class="large-text" name="<?php echo esc_attr( self::OPTION ); ?>[social_telegram]" value="<?php echo esc_attr( $settings['social_telegram'] ); ?>" placeholder="https://t.me/..." /></label></p>
							<p><label><?php esc_html_e( 'اینستاگرام:', 'lamerd-khodro' ); ?> <input type="url" class="large-text" name="<?php echo esc_attr( self::OPTION ); ?>[social_instagram]" value="<?php echo esc_attr( $settings['social_instagram'] ); ?>" placeholder="https://instagram.com/..." /></label></p>
							<p><label><?php esc_html_e( 'آپارات:', 'lamerd-khodro' ); ?> <input type="url" class="large-text" name="<?php echo esc_attr( self::OPTION ); ?>[social_eita]" value="<?php echo esc_attr( $settings['social_eita'] ); ?>" placeholder="https://..." /></label></p>
						</td>
					</tr>
					<tr><th><?php esc_html_e( 'اطلاعات تماس', 'lamerd-khodro' ); ?></th>
						<td>
							<p><label><?php esc_html_e( 'تلفن:', 'lamerd-khodro' ); ?> <input type="text" name="<?php echo esc_attr( self::OPTION ); ?>[contact_phone]" value="<?php echo esc_attr( $settings['contact_phone'] ); ?>" /></label></p>
							<p><label><?php esc_html_e( 'ایمیل:', 'lamerd-khodro' ); ?> <input type="email" name="<?php echo esc_attr( self::OPTION ); ?>[contact_email]" value="<?php echo esc_attr( $settings['contact_email'] ); ?>" /></label></p>
							<p><label><?php esc_html_e( 'آدرس:', 'lamerd-khodro' ); ?> <input type="text" class="large-text" name="<?php echo esc_attr( self::OPTION ); ?>[contact_address]" value="<?php echo esc_attr( $settings['contact_address'] ); ?>" /></label></p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * رندر صفحه سئو.
	 */
	public function render_seo_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = array_merge(
			array(
				'title_template' => '%s | لامرد خودرو',
				'description'    => '',
				'og_image'       => 0,
			),
			(array) get_option( 'lkc_seo_settings', array() )
		);
		?>
		<div class="wrap lkc-settings-wrap" dir="rtl">
			<h1><?php esc_html_e( 'سئو و متا', 'lamerd-khodro' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'lkc_seo_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="lkc_title_template"><?php esc_html_e( 'الگوی عنوان', 'lamerd-khodro' ); ?></label></th>
						<td>
							<input type="text" class="large-text" id="lkc_title_template" name="lkc_seo_settings[title_template]" value="<?php echo esc_attr( $settings['title_template'] ); ?>" />
							<p class="description"><?php esc_html_e( '%s جای نام صفحه می‌گیرد.', 'lamerd-khodro' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="lkc_seo_desc"><?php esc_html_e( 'توضیح پیش‌فرض', 'lamerd-khodro' ); ?></label></th>
						<td><textarea class="large-text" rows="3" id="lkc_seo_desc" name="lkc_seo_settings[description]"><?php echo esc_textarea( $settings['description'] ); ?></textarea></td>
					</tr>
					<tr>
						<th><label for="lkc_seo_og"><?php esc_html_e( 'شناسه تصویر OG پیش‌فرض', 'lamerd-khodro' ); ?></label></th>
						<td><input type="number" min="0" id="lkc_seo_og" name="lkc_seo_settings[og_image]" value="<?php echo esc_attr( (string) $settings['og_image'] ); ?>" /></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
