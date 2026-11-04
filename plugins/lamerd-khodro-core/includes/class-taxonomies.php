<?php
/**
 * ثبت تکسونومی برندها.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

/**
 * تکسونومی اختصاصی برند خودرو با متا‌های اختصاصی.
 */
class LKC_Taxonomies {

	const BRAND = 'brand';

	/**
	 * اتصال هوک‌ها.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_action( 'brand_add_form_fields', array( $this, 'add_term_fields' ) );
		add_action( 'brand_edit_form_fields', array( $this, 'edit_term_fields' ) );
		add_action( 'created_brand', array( $this, 'save_term_fields' ) );
		add_action( 'edited_brand', array( $this, 'save_term_fields' ) );
		add_filter( 'manage_brand_custom_column', array( $this, 'render_column' ), 10, 3 );
	}

	/**
	 * ثبت تکسونومی.
	 */
	public function register_taxonomy(): void {
		$labels = array(
			'name'              => __( 'برندها', 'lamerd-khodro' ),
			'singular_name'     => __( 'برند', 'lamerd-khodro' ),
			'menu_name'         => __( 'برندها', 'lamerd-khodro' ),
			'all_items'         => __( 'همه برندها', 'lamerd-khodro' ),
			'edit_item'         => __( 'ویرایش برند', 'lamerd-khodro' ),
			'view_item'         => __( 'مشاهده برند', 'lamerd-khodro' ),
			'update_item'       => __( 'به‌روزرسانی برند', 'lamerd-khodro' ),
			'add_new_item'      => __( 'افزودن برند جدید', 'lamerd-khodro' ),
			'new_item_name'     => __( 'نام برند جدید', 'lamerd-khodro' ),
			'search_items'      => __( 'جستجوی برندها', 'lamerd-khodro' ),
			'not_found'         => __( 'برندی یافت نشد', 'lamerd-khodro' ),
			'popular_items'     => __( 'پرکاربردترین برندها', 'lamerd-khodro' ),
			'featured_image'    => __( 'لوگوی برند', 'lamerd-khodro' ),
			'set_featured_image'=> __( 'تنظیم لوگو', 'lamerd-khodro' ),
		);

		register_taxonomy(
			self::BRAND,
			array( LKC_Post_Types::POST_TYPE, LKC_Listings::POST_TYPE ),
			array(
				'labels'            => $labels,
				'hierarchical'      => false,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'show_ui'           => true,
				'query_var'         => true,
				'rewrite'           => array(
					'slug'         => 'brand',
					'with_front'   => false,
					'hierarchical' => false,
				),
			)
		);
	}

	/**
	 * فیلدهای افزودن برند.
	 */
	public function add_term_fields(): void {
		wp_nonce_field( 'lkc_brand_term', 'lkc_brand_term_nonce' );
		?>
		<div class="form-field">
			<label for="lkc_brand_name_en"><?php esc_html_e( 'نام انگلیسی', 'lamerd-khodro' ); ?></label>
			<input type="text" name="lkc_brand_name_en" id="lkc_brand_name_en" />
			<p><?php esc_html_e( 'نام برند به انگلیسی مانند Toyota.', 'lamerd-khodro' ); ?></p>
		</div>
		<div class="form-field">
			<label for="lkc_brand_country"><?php esc_html_e( 'کشور سازنده', 'lamerd-khodro' ); ?></label>
			<input type="text" name="lkc_brand_country" id="lkc_brand_country" />
		</div>
		<div class="form-field">
			<label for="lkc_brand_website"><?php esc_html_e( 'وب‌سایت رسمی', 'lamerd-khodro' ); ?></label>
			<input type="url" name="lkc_brand_website" id="lkc_brand_website" placeholder="https://" />
		</div>
		<div class="form-field">
			<label for="lkc_brand_logo"><?php esc_html_e( 'شناسه پیوست لوگو (اختیاری)', 'lamerd-khodro' ); ?></label>
			<input type="number" min="0" name="lkc_brand_logo" id="lkc_brand_logo" />
			<p><?php esc_html_e( 'می‌توانید بعداً از رسانه‌ها تصویر شاخص برند را تنظیم کنید.', 'lamerd-khodro' ); ?></p>
		</div>
		<?php
	}

	/**
	 * فیلدهای ویرایش برند.
	 *
	 * @param WP_Term $term برند.
	 */
	public function edit_term_fields( WP_Term $term ): void {
		wp_nonce_field( 'lkc_brand_term', 'lkc_brand_term_nonce' );
		$name_en  = get_term_meta( $term->term_id, '_lkc_brand_name_en', true );
		$country  = get_term_meta( $term->term_id, '_lkc_brand_country', true );
		$website  = get_term_meta( $term->term_id, '_lkc_brand_website', true );
		$logo_id  = (int) get_term_meta( $term->term_id, '_lkc_brand_logo', true );
		?>
		<tr class="form-field">
			<th scope="row"><label for="lkc_brand_name_en"><?php esc_html_e( 'نام انگلیسی', 'lamerd-khodro' ); ?></label></th>
			<td><input type="text" name="lkc_brand_name_en" id="lkc_brand_name_en" value="<?php echo esc_attr( $name_en ); ?>" /></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="lkc_brand_country"><?php esc_html_e( 'کشور سازنده', 'lamerd-khodro' ); ?></label></th>
			<td><input type="text" name="lkc_brand_country" id="lkc_brand_country" value="<?php echo esc_attr( $country ); ?>" /></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="lkc_brand_website"><?php esc_html_e( 'وب‌سایت رسمی', 'lamerd-khodro' ); ?></label></th>
			<td><input type="url" name="lkc_brand_website" id="lkc_brand_website" value="<?php echo esc_attr( $website ); ?>" placeholder="https://" /></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="lkc_brand_logo"><?php esc_html_e( 'شناسه پیوست لوگو', 'lamerd-khodro' ); ?></label></th>
			<td><input type="number" min="0" name="lkc_brand_logo" id="lkc_brand_logo" value="<?php echo $logo_id ? esc_attr( (string) $logo_id ) : ''; ?>" /></td>
		</tr>
		<?php
	}

	/**
	 * ذخیره متا‌های برند.
	 *
	 * @param int $term_id شناسه ترم.
	 */
	public function save_term_fields( int $term_id ): void {
		if ( ! isset( $_POST['lkc_brand_term_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['lkc_brand_term_nonce'] ) ), 'lkc_brand_term' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		$name_en = isset( $_POST['lkc_brand_name_en'] ) ? sanitize_text_field( wp_unslash( $_POST['lkc_brand_name_en'] ) ) : '';
		$country = isset( $_POST['lkc_brand_country'] ) ? sanitize_text_field( wp_unslash( $_POST['lkc_brand_country'] ) ) : '';
		$website = isset( $_POST['lkc_brand_website'] ) ? esc_url_raw( wp_unslash( $_POST['lkc_brand_website'] ) ) : '';
		$logo    = isset( $_POST['lkc_brand_logo'] ) ? absint( $_POST['lkc_brand_logo'] ) : 0;

		update_term_meta( $term_id, '_lkc_brand_name_en', $name_en );
		update_term_meta( $term_id, '_lkc_brand_country', $country );
		update_term_meta( $term_id, '_lkc_brand_website', $website );
		update_term_meta( $term_id, '_lkc_brand_logo', $logo );
	}

	/**
	 * ستون سفارشی در فهرست برندها.
	 *
	 * @param string $output خروجی.
	 * @param string $column  ستون.
	 * @param int    $term_id شناسه ترم.
	 * @return string
	 */
	public function render_column( string $output, string $column, int $term_id ): string {
		if ( 'lkc_brand_meta' === $column ) {
			$country = get_term_meta( $term_id, '_lkc_brand_country', true );
			$name_en = get_term_meta( $term_id, '_lkc_brand_name_en', true );
			$parts   = array_filter( array( $name_en, $country ) );
			return $parts ? esc_html( implode( ' — ', $parts ) ) : '&mdash;';
		}
		return $output;
	}
}
