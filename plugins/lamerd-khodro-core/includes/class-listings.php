<?php
/**
 * آگهی‌های فروش خودرو (بدون سبد خرید و پرداخت).
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

/**
 * ثبت و مدیریت آگهی فروش خودرو.
 *
 * هر آگهی یک نوشته از نوع `listing` است؛ قیمت و مشخصات در متا ذخیره می‌شوند و
 * «فروش ویژه» با تاریخ پایان اختیاری، تایمر شمارش معکوس در سایت می‌گیرد.
 */
class LKC_Listings {

	const POST_TYPE    = 'listing';
	const META         = '_lkc_listing_';
	const NONCE_ACTION = 'lkc_listing_save';
	const NONCE_NAME   = 'lkc_listing_nonce';

	/**
	 * فیلدهای مشخصات آگهی.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function fields(): array {
		return array(
			'price'   => array(
				'label' => __( 'قیمت (تومان)', 'lamerd-khodro' ),
				'type'  => 'number',
				'desc'  => __( 'قیمت فروش به تومان؛ مثلاً 850000000.', 'lamerd-khodro' ),
			),
			'year'    => array(
				'label' => __( 'سال ساخت (شمسی)', 'lamerd-khodro' ),
				'type'  => 'number',
				'min'   => 1360,
				'max'   => 1420,
			),
			'mileage' => array(
				'label' => __( 'کارکرد (کیلومتر)', 'lamerd-khodro' ),
				'type'  => 'number',
			),
			'city'    => array(
				'label' => __( 'شهر', 'lamerd-khodro' ),
				'type'  => 'text',
				'desc'  => __( 'مثلاً لامرد، شیراز، لار.', 'lamerd-khodro' ),
			),
			'color'   => array(
				'label' => __( 'رنگ', 'lamerd-khodro' ),
				'type'  => 'text',
			),
			'seller'  => array(
				'label' => __( 'نام آگهی‌دهنده', 'lamerd-khodro' ),
				'type'  => 'text',
			),
			'phone'   => array(
				'label' => __( 'شماره تماس', 'lamerd-khodro' ),
				'type'  => 'text',
				'desc'  => __( 'در صفحه آگهی به‌صورت دکمه تماس نمایش داده می‌شود. خالی بماند، دکمه تماس نمایش داده نمی‌شود.', 'lamerd-khodro' ),
			),
		);
	}

	/**
	 * اتصال هوک‌ها.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save' ), 10, 2 );
		add_filter( 'manage_listing_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_listing_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'enter_title_here', array( $this, 'title_placeholder' ) );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
	}

	/**
	 * ثبت CPT آگهی.
	 */
	public function register_post_type(): void {
		$labels = array(
			'name'               => __( 'آگهی‌های فروش', 'lamerd-khodro' ),
			'singular_name'      => __( 'آگهی فروش', 'lamerd-khodro' ),
			'menu_name'          => __( 'آگهی‌های فروش', 'lamerd-khodro' ),
			'add_new'            => __( 'افزودن آگهی', 'lamerd-khodro' ),
			'add_new_item'       => __( 'افزودن آگهی فروش خودرو', 'lamerd-khodro' ),
			'edit_item'          => __( 'ویرایش آگهی فروش', 'lamerd-khodro' ),
			'new_item'           => __( 'آگهی جدید', 'lamerd-khodro' ),
			'view_item'          => __( 'مشاهده آگهی', 'lamerd-khodro' ),
			'search_items'       => __( 'جستجوی آگهی', 'lamerd-khodro' ),
			'not_found'          => __( 'آگهی‌ای یافت نشد', 'lamerd-khodro' ),
			'not_found_in_trash' => __( 'آگهی‌ای در زباله‌دان نیست', 'lamerd-khodro' ),
			'all_items'          => __( 'همه آگهی‌ها', 'lamerd-khodro' ),
			'featured_image'     => __( 'تصویر خودرو', 'lamerd-khodro' ),
			'set_featured_image' => __( 'تنظیم تصویر آگهی', 'lamerd-khodro' ),
			'archives'           => __( 'آرشیو آگهی‌ها', 'lamerd-khodro' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => $labels,
				'description'        => __( 'آگهی فروش خودرو توسط مدیر سایت؛ بدون سبد خرید و پرداخت.', 'lamerd-khodro' ),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'query_var'          => true,
				'rewrite'            => array(
					'slug'       => 'listing',
					'with_front' => false,
				),
				'has_archive'        => 'listings',
				'capability_type'    => 'post',
				'hierarchical'       => false,
				'menu_position'      => 6,
				'menu_icon'          => 'dashicons-megaphone',
				'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
				'taxonomies'         => array( LKC_Taxonomies::BRAND ),
			)
		);
	}

	/**
	 * متن راهنمای عنوان.
	 *
	 * @param string $text متن پیش‌فرض.
	 * @return string
	 */
	public function title_placeholder( string $text ): string {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && self::POST_TYPE === $screen->post_type ) {
			return __( 'عنوان آگهی (مثلاً پژو ۲۰۶ تیپ ۵ مدل ۱۳۹۹)', 'lamerd-khodro' );
		}

		return $text;
	}

	/**
	 * کلاس بدنه پیشخوان.
	 *
	 * @param string $classes کلاس‌ها.
	 * @return string
	 */
	public function admin_body_class( string $classes ): string {
		if ( in_array( 'post-type-' . self::POST_TYPE, explode( ' ', $classes ), true ) ) {
			$classes .= ' lkc-admin-rtl ';
		}
		return $classes;
	}

	/**
	 * ثبت متاباکس‌ها.
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'lkc-listing-details',
			__( 'مشخصات و قیمت آگهی', 'lamerd-khodro' ),
			array( $this, 'render_details' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'lkc-listing-featured',
			__( 'فروش ویژه', 'lamerd-khodro' ),
			array( $this, 'render_featured' ),
			self::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * رندر متاباکس مشخصات.
	 *
	 * @param WP_Post $post نوشته.
	 */
	public function render_details( $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<div class="lkc-listing-fields" dir="rtl">
			<table class="form-table" role="presentation">
				<tbody>
					<?php foreach ( self::fields() as $key => $field ) : ?>
						<?php
						$value = (string) get_post_meta( (int) $post->ID, self::META . $key, true );
						$id    = 'lkc_listing_' . $key;
						?>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( (string) $field['label'] ); ?></label></th>
							<td>
								<input
									type="<?php echo esc_attr( 'number' === $field['type'] ? 'number' : 'text' ); ?>"
									class="regular-text"
									id="<?php echo esc_attr( $id ); ?>"
									name="<?php echo esc_attr( $id ); ?>"
									value="<?php echo esc_attr( $value ); ?>"
									<?php if ( isset( $field['min'] ) ) : ?>min="<?php echo esc_attr( (string) $field['min'] ); ?>"<?php endif; ?>
									<?php if ( isset( $field['max'] ) ) : ?>max="<?php echo esc_attr( (string) $field['max'] ); ?>"<?php endif; ?>
								/>
								<?php if ( ! empty( $field['desc'] ) ) : ?>
									<p class="description"><?php echo esc_html( (string) $field['desc'] ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description">
				<?php esc_html_e( 'برند خودرو را از کادر «برندها» در ستون کنار انتخاب کنید. توضیحات کامل آگهی را در ویرایشگر بنویسید و تصویر اصلی را تنظیم کنید.', 'lamerd-khodro' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * رندر متاباکس فروش ویژه.
	 *
	 * @param WP_Post $post نوشته.
	 */
	public function render_featured( $post ): void {
		$featured = '1' === (string) get_post_meta( (int) $post->ID, self::META . 'featured', true );
		$until    = (string) get_post_meta( (int) $post->ID, self::META . 'featured_until', true );
		$until_in = $until ? str_replace( ' ', 'T', substr( $until, 0, 16 ) ) : '';
		?>
		<div dir="rtl">
			<p>
				<label>
					<input type="checkbox" name="lkc_listing_featured" value="1" <?php checked( $featured ); ?> />
					<?php esc_html_e( 'این آگهی فروش ویژه باشد', 'lamerd-khodro' ); ?>
				</label>
			</p>
			<p>
				<label for="lkc_listing_featured_until"><?php esc_html_e( 'پایان فروش ویژه', 'lamerd-khodro' ); ?></label><br />
				<input
					type="datetime-local"
					id="lkc_listing_featured_until"
					name="lkc_listing_featured_until"
					value="<?php echo esc_attr( $until_in ); ?>"
					style="width:100%"
				/>
			</p>
			<p class="description">
				<?php esc_html_e( 'تا این تاریخ، آگهی با نشان «فروش ویژه» و تایمر شمارش معکوس در صفحه اصلی نمایش داده می‌شود. خالی بماند، ویژه بدون تایمر است. پس از پایان، آگهی خودکار به فهرست عادی برمی‌گردد.', 'lamerd-khodro' ); ?>
			</p>
			<?php if ( $until ) : ?>
				<p class="description">
					<?php
					printf(
						/* translators: %s: date */
						esc_html__( 'زمان ثبت‌شده: %s', 'lamerd-khodro' ),
						esc_html( lkc_to_fa_digits( $until ) )
					);
					?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * ذخیره متاها.
	 *
	 * @param int     $post_id شناسه نوشته.
	 * @param WP_Post $post    نوشته.
	 */
	public function save( int $post_id, $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( self::fields() as $key => $field ) {
			$name  = 'lkc_listing_' . $key;
			$raw   = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';
			$value = 'number' === $field['type']
				? ( '' === trim( (string) $raw ) ? '' : (string) absint( $raw ) )
				: sanitize_text_field( (string) $raw );

			if ( '' === $value ) {
				delete_post_meta( $post_id, self::META . $key );
				continue;
			}

			update_post_meta( $post_id, self::META . $key, $value );
		}

		$featured = isset( $_POST['lkc_listing_featured'] ) ? '1' : '0';
		update_post_meta( $post_id, self::META . 'featured', $featured );

		$until_raw = isset( $_POST['lkc_listing_featured_until'] ) ? sanitize_text_field( wp_unslash( $_POST['lkc_listing_featured_until'] ) ) : '';
		$until     = self::normalize_datetime( $until_raw );

		if ( '' === $until ) {
			delete_post_meta( $post_id, self::META . 'featured_until' );
		} else {
			update_post_meta( $post_id, self::META . 'featured_until', $until );
		}
	}

	/**
	 * تبدیل مقدار datetime-local به قالب MySQL محلی.
	 *
	 * @param string $value مقدار ورودی (Y-m-d\TH:i).
	 * @return string
	 */
	public static function normalize_datetime( string $value ): string {
		$value = trim( str_replace( 'T', ' ', $value ) );

		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})[ ](\d{2}):(\d{2})(:(\d{2}))?$/', $value, $m ) ) {
			return '';
		}

		$timestamp = mktime( (int) $m[4], (int) $m[5], (int) ( $m[7] ?? 0 ), (int) $m[2], (int) $m[3], (int) $m[1] );

		if ( false === $timestamp ) {
			return '';
		}

		return (string) gmdate( 'Y-m-d H:i:s', $timestamp + (int) ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
	}

	/**
	 * ستون‌های فهرست مدیریت.
	 *
	 * @param array<string, string> $columns ستون‌ها.
	 * @return array<string, string>
	 */
	public function columns( array $columns ): array {
		$new = array();

		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['lkc_listing_thumb'] = __( 'تصویر', 'lamerd-khodro' );
			}

			$new[ $key ] = $label;

			if ( 'title' === $key ) {
				$new['lkc_listing_price']    = __( 'قیمت', 'lamerd-khodro' );
				$new['lkc_listing_specs']    = __( 'سال / کارکرد', 'lamerd-khodro' );
				$new['lkc_listing_city']     = __( 'شهر', 'lamerd-khodro' );
				$new['lkc_listing_featured'] = __( 'فروش ویژه', 'lamerd-khodro' );
			}
		}

		return $new;
	}

	/**
	 * رندر ستون‌ها.
	 *
	 * @param string $column  نام ستون.
	 * @param int    $post_id شناسه.
	 */
	public function render_column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'lkc_listing_thumb':
				echo get_the_post_thumbnail( $post_id, array( 60, 60 ), array( 'style' => 'border-radius:6px;object-fit:cover' ) );
				break;

			case 'lkc_listing_price':
				$price = self::meta( $post_id, 'price' );
				echo $price ? esc_html( lkc_format_price( (int) $price ) ) : '&mdash;';
				break;

			case 'lkc_listing_specs':
				$year    = self::meta( $post_id, 'year' );
				$mileage = self::meta( $post_id, 'mileage' );
				$parts   = array();

				if ( $year ) {
					$parts[] = esc_html( lkc_to_fa_digits( (string) $year ) );
				}
				if ( $mileage ) {
					$parts[] = esc_html( lkc_to_fa_digits( number_format_i18n( (int) $mileage ) ) . ' ' . __( 'کیلومتر', 'lamerd-khodro' ) );
				}

				echo $parts ? implode( ' / ', $parts ) : '&mdash;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- خروجی بالا escape شده است.
				break;

			case 'lkc_listing_city':
				$city = self::meta( $post_id, 'city' );
				echo $city ? esc_html( (string) $city ) : '&mdash;';
				break;

			case 'lkc_listing_featured':
				if ( '1' !== self::meta( $post_id, 'featured' ) ) {
					echo '&mdash;';
					break;
				}

				if ( ! self::is_featured( $post_id ) ) {
					echo '<span style="color:#a00">' . esc_html__( 'پایان یافته', 'lamerd-khodro' ) . '</span>';
					break;
				}

				echo '<span style="color:#046a38;font-weight:600">' . esc_html__( 'فعال', 'lamerd-khodro' ) . '</span>';

				$remaining = self::featured_remaining( $post_id );
				if ( $remaining > 0 ) {
					echo '<br /><small>' . esc_html( self::format_remaining( $remaining ) ) . '</small>';
				}
				break;
		}
	}

	/**
	 * خواندن یک متای آگهی.
	 *
	 * @param int    $post_id شناسه.
	 * @param string $key     کلید.
	 * @return string
	 */
	public static function meta( int $post_id, string $key ): string {
		$value = get_post_meta( $post_id, self::META . $key, true );
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * آیا آگهی همین حالا فروش ویژه است؟
	 *
	 * @param int $post_id شناسه.
	 * @return bool
	 */
	public static function is_featured( int $post_id ): bool {
		if ( '1' !== self::meta( $post_id, 'featured' ) ) {
			return false;
		}

		$until = self::meta( $post_id, 'featured_until' );

		if ( '' === $until ) {
			return true;
		}

		return $until >= current_time( 'mysql' );
	}

	/**
	 * ثانیه‌های باقی‌مانده تا پایان فروش ویژه (۰ = بدون تایمر یا پایان یافته).
	 *
	 * @param int $post_id شناسه.
	 * @return int
	 */
	public static function featured_remaining( int $post_id ): int {
		$until = self::meta( $post_id, 'featured_until' );

		if ( '' === $until ) {
			return 0;
		}

		$until_ts = (int) mysql2date( 'U', $until, false );
		$left     = $until_ts - (int) current_time( 'timestamp' );

		return max( 0, $left );
	}

	/**
	 * قالب‌بندی زمان باقی‌مانده برای پیشخوان.
	 *
	 * @param int $seconds ثانیه.
	 * @return string
	 */
	public static function format_remaining( int $seconds ): string {
		$days  = (int) floor( $seconds / DAY_IN_SECONDS );
		$hours = (int) floor( ( $seconds % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );

		if ( $days > 0 ) {
			return sprintf(
				/* translators: ۱: روز ۲: ساعت */
				__( '%1$s روز و %2$s ساعت مانده', 'lamerd-khodro' ),
				lkc_to_fa_digits( (string) $days ),
				lkc_to_fa_digits( (string) $hours )
			);
		}

		return sprintf(
			/* translators: ۱: ساعت ۲: دقیقه */
			__( '%1$s ساعت و %2$s دقیقه مانده', 'lamerd-khodro' ),
			lkc_to_fa_digits( (string) $hours ),
			lkc_to_fa_digits( (string) (int) floor( ( $seconds % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS ) )
		);
	}

	/**
	 * داده کامل یک آگهی برای قالب.
	 *
	 * @param int $post_id شناسه.
	 * @return array<string, mixed>
	 */
	public static function data( int $post_id ): array {
		$price   = self::meta( $post_id, 'price' );
		$mileage = self::meta( $post_id, 'mileage' );
		$brands  = get_the_terms( $post_id, LKC_Taxonomies::BRAND );
		$brand   = is_array( $brands ) && $brands ? (string) $brands[0]->name : '';

		return array(
			'id'          => $post_id,
			'title'       => get_the_title( $post_id ),
			'url'         => (string) get_permalink( $post_id ),
			'thumbnail'   => (string) get_the_post_thumbnail_url( $post_id, 'lk-card' ),
			'excerpt'     => (string) get_the_excerpt( $post_id ),
			'price'       => '' === $price ? null : (int) $price,
			'year'        => self::meta( $post_id, 'year' ),
			'mileage'     => '' === $mileage ? null : (int) $mileage,
			'city'        => self::meta( $post_id, 'city' ),
			'color'       => self::meta( $post_id, 'color' ),
			'seller'      => self::meta( $post_id, 'seller' ),
			'phone'       => self::meta( $post_id, 'phone' ),
			'brand'       => $brand,
			'featured'    => self::is_featured( $post_id ),
			'remaining'   => self::is_featured( $post_id ) ? self::featured_remaining( $post_id ) : 0,
			'published'   => (string) get_the_date( 'U', $post_id ),
		);
	}

	/**
	 * آگهی‌های فروش ویژه فعال.
	 *
	 * @param int   $limit   تعداد.
	 * @param int[] $exclude شناسه‌های کنارگذاشته.
	 * @return int[]
	 */
	public static function featured_ids( int $limit, array $exclude = array() ): array {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'no_found_rows'  => true,
				'meta_key'       => self::META . 'featured', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		$ids = array();

		foreach ( $posts as $post ) {
			if ( in_array( (int) $post->ID, $exclude, true ) ) {
				continue;
			}
			if ( self::is_featured( (int) $post->ID ) ) {
				$ids[] = (int) $post->ID;
			}
			if ( count( $ids ) >= $limit ) {
				break;
			}
		}

		return $ids;
	}

	/**
	 * جدیدترین آگهی‌ها.
	 *
	 * @param int   $limit   تعداد.
	 * @param int[] $exclude شناسه‌های کنارگذاشته.
	 * @return int[]
	 */
	public static function latest_ids( int $limit, array $exclude = array() ): array {
		if ( $limit < 1 ) {
			return array();
		}

		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'post__not_in'   => $exclude,
				'no_found_rows'  => true,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		return array_map( static fn( $post ) => (int) $post->ID, $posts );
	}
}
