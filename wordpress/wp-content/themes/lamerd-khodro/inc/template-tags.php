<?php
/**
 * توابع کمکی قالب: قیمت، مشخصات، مسیر راهنما و داده ساختاریافته.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

/**
 * بارگذاری یک بخش قالب با پارامتر.
 *
 * @param string               $slug نام فایل بدون پسوند.
 * @param array<string, mixed> $args پارامترها (در فایل قالب با نام $args).
 */
function lk_load_template_part( string $slug, array $args = array() ): void {
	get_template_part( $slug, null, $args );
}

/**
 * دریافت خروجی یک بخش قالب به‌صورت رشته.
 *
 * @param string               $slug نام فایل بدون پسوند.
 * @param array<string, mixed> $args پارامترها.
 * @return string
 */
function lk_render_template_part( string $slug, array $args = array() ): string {
	ob_start();
	get_template_part( $slug, null, $args );
	return (string) ob_get_clean();
}

/**
 * داده قیمت یک خودرو از موتور قیمت.
 *
 * @param int $post_id شناسه خودرو.
 * @return array<string, mixed>|null
 */
function lk_get_price( int $post_id ): ?array {
	if ( ! function_exists( 'lamerd_khodro_core' ) ) {
		return null;
	}
	$price = lamerd_khodro_core()->vehicles()->price( $post_id );
	return is_array( $price ) ? $price : null;
}

/**
 * قالب‌بندی مبلغ؛ null هرگز «۰» نمایش داده نمی‌شود.
 *
 * @param int|null $amount مبلغ.
 * @param string   $empty  متن جایگزین.
 * @return string
 */
function lk_money( ?int $amount, string $empty = '' ): string {
	if ( null === $amount ) {
		return $empty ?: __( 'ناموجود', 'lamerd-khodro' );
	}
	return lk_fa_digits( number_format_i18n( $amount ) ) . ' ' . __( 'تومان', 'lamerd-khodro' );
}

/**
 * داده یک آگهی فروش برای استفاده در قالب.
 *
 * @param int $post_id شناسه آگهی.
 * @return array<string, mixed>
 */
function lk_listing_data( int $post_id ): array {
	if ( class_exists( 'LKC_Listings' ) ) {
		return LKC_Listings::data( $post_id );
	}

	return array(
		'id'        => $post_id,
		'title'     => get_the_title( $post_id ),
		'url'       => (string) get_permalink( $post_id ),
		'thumbnail' => (string) get_the_post_thumbnail_url( $post_id, 'lk-card' ),
		'excerpt'   => '',
		'price'     => null,
		'year'      => '',
		'mileage'   => null,
		'city'      => '',
		'color'     => '',
		'seller'    => '',
		'phone'     => '',
		'brand'     => '',
		'featured'  => false,
		'remaining' => 0,
		'published' => (string) get_the_date( 'U', $post_id ),
	);
}

/**
 * زمان باقی‌مانده به شکل «۳ روز و ۰۴:۱۵:۲۰».
 *
 * @param int $seconds ثانیه.
 * @return string
 */
function lk_countdown_text( int $seconds ): string {
	if ( $seconds <= 0 ) {
		return __( 'پایان یافته', 'lamerd-khodro' );
	}

	$days    = (int) floor( $seconds / DAY_IN_SECONDS );
	$hours   = (int) floor( ( $seconds % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );
	$minutes = (int) floor( ( $seconds % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );
	$rest    = (int) ( $seconds % MINUTE_IN_SECONDS );
	$clock   = lk_fa_digits( sprintf( '%02d:%02d:%02d', $hours, $minutes, $rest ) );

	if ( $days > 0 ) {
		return sprintf(
			/* translators: ۱: روز ۲: ساعت */
			__( '%1$s روز و %2$s', 'lamerd-khodro' ),
			lk_fa_digits( (string) $days ),
			$clock
		);
	}

	return $clock;
}

/**
 * تبدیل ارقام لاتین به فارسی.
 *
 * @param string $text متن.
 * @return string
 */
function lk_fa_digits( string $text ): string {
	if ( function_exists( 'lkc_to_fa_digits' ) ) {
		return lkc_to_fa_digits( $text );
	}
	return str_replace( array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ), array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ), $text );
}

/**
 * نمایش تاریخ شمسی نوشته.
 *
 * @param int|null $post_id شناسه.
 * @return string
 */
function lk_posted_on( ?int $post_id = null ): string {
	$post_id = $post_id ?: get_the_ID();
	$time    = get_the_date( 'U', $post_id );

	if ( ! $time ) {
		return '';
	}

	if ( function_exists( 'lkp_gregorian_to_jalali' ) ) {
		list( $jy, $jm, $jd ) = lkp_gregorian_to_jalali(
			(int) gmdate( 'Y', (int) $time ),
			(int) gmdate( 'n', (int) $time ),
			(int) gmdate( 'j', (int) $time )
		);
		return lk_fa_digits( sprintf( '%d/%02d/%02d', $jy, $jm, $jd ) );
	}

	return lk_fa_digits( date_i18n( 'Y/m/d', (int) $time ) );
}

/**
 * نشان تغییر قیمت.
 *
 * @param array<string, mixed>|null $price داده قیمت.
 * @return string HTML
 */
function lk_change_badge( ?array $price ): string {
	if ( ! $price ) {
		return '';
	}

	$percent = $price['change_percent'] ?? null;
	$amount  = $price['change_amount'] ?? null;

	if ( null === $percent && null === $amount ) {
		return '';
	}

	$class = 'lk-change lk-change--flat';
	$text  = __( 'بدون تغییر', 'lamerd-khodro' );

	if ( null !== $percent ) {
		if ( $percent > 0 ) {
			$class = 'lk-change lk-change--up';
			$text  = lk_fa_digits( number_format( (float) $percent, 2 ) ) . '٪ ↑';
		} elseif ( $percent < 0 ) {
			$class = 'lk-change lk-change--down';
			$text  = lk_fa_digits( number_format( abs( (float) $percent ), 2 ) ) . '٪ ↓';
		}
	} elseif ( null !== $amount ) {
		if ( $amount > 0 ) {
			$class = 'lk-change lk-change--up';
			$text  = lk_fa_digits( number_format_i18n( (int) $amount ) ) . ' +';
		} elseif ( $amount < 0 ) {
			$class = 'lk-change lk-change--down';
			$text  = lk_fa_digits( number_format_i18n( abs( (int) $amount ) ) ) . ' −';
		}
	}

	return sprintf(
		'<span class="%s" title="%s">%s</span>',
		esc_attr( $class ),
		esc_attr__( 'تغییر نسبت به آخرین قیمت ثبت‌شده', 'lamerd-khodro' ),
		esc_html( $text )
	);
}

/**
 * بلوک قیمت خودرو (بازار + کارخانه + تغییر).
 *
 * @param int                  $post_id شناسه خودرو.
 * @param array<string, mixed> $args    گزینه‌ها: compact.
 */
function lk_price_block( int $post_id, array $args = array() ): void {
	$args      = wp_parse_args( $args, array( 'compact' => false ) );
	$price     = lk_get_price( $post_id );
	$market    = $price['market_price'] ?? null;
	$factory   = $price['factory_price'] ?? null;
	$available = null !== $market;

	$class = 'lk-pricebox' . ( $available ? '' : ' lk-pricebox--na' ) . ( $args['compact'] ? ' lk-pricebox--compact' : '' );
	?>
	<div class="<?php echo esc_attr( $class ); ?>">
		<div class="lk-pricebox__row">
			<span class="lk-pricebox__label"><?php esc_html_e( 'قیمت بازار', 'lamerd-khodro' ); ?></span>
			<span class="lk-pricebox__value"><?php echo esc_html( lk_money( null === $market ? null : (int) $market ) ); ?></span>
		</div>
		<?php if ( ! $args['compact'] ) : ?>
			<div class="lk-pricebox__row lk-pricebox__row--muted">
				<span class="lk-pricebox__label"><?php esc_html_e( 'قیمت کارخانه', 'lamerd-khodro' ); ?></span>
				<span class="lk-pricebox__value"><?php echo esc_html( lk_money( null === $factory ? null : (int) $factory ) ); ?></span>
			</div>
		<?php endif; ?>
		<div class="lk-pricebox__meta">
			<?php echo wp_kses_post( lk_change_badge( $price ) ); ?>
			<?php if ( $price && ! empty( $price['fetched_at'] ) ) : ?>
				<span class="lk-pricebox__date">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: date */
							__( 'آخرین به‌روزرسانی: %s', 'lamerd-khodro' ),
							lk_posted_on_fallback( (string) $price['fetched_at'] )
						)
					);
					?>
				</span>
			<?php endif; ?>
		</div>
		<?php if ( $price && ! empty( $price['is_stale'] ) ) : ?>
			<p class="lk-pricebox__stale">
				<?php esc_html_e( 'این قیمت بیش از یک روز به‌روزرسانی نشده است.', 'lamerd-khodro' ); ?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * تبدیل datetime دیتابیس به تاریخ شمسی برای نمایش.
 *
 * @param string $datetime تاریخ.
 * @return string
 */
function lk_posted_on_fallback( string $datetime ): string {
	$timestamp = strtotime( $datetime );
	if ( ! $timestamp ) {
		return '—';
	}
	if ( function_exists( 'lkp_jalali' ) ) {
		return lkp_jalali( $datetime );
	}
	return lk_fa_digits( date_i18n( 'Y/m/d', $timestamp ) );
}

/**
 * ذکر اجباری منبع قیمت.
 *
 * @param array<string, mixed>|null $price داده قیمت.
 */
function lk_attribution( ?array $price = null ): void {
	$url  = 'https://cofekhodro.ir';
	$name = __( 'کافه خودرو', 'lamerd-khodro' );

	if ( $price && ! empty( $price['attribution']['url'] ) ) {
		$url = (string) $price['attribution']['url'];
	}
	if ( $price && ! empty( $price['attribution']['text'] ) ) {
		$name = (string) $price['attribution']['text'];
		$name = str_replace( array( 'منبع:', 'منبع :' ), '', $name );
		$name = trim( $name );
	}
	?>
	<p class="lk-attribution">
		<?php esc_html_e( 'منبع قیمت:', 'lamerd-khodro' ); ?>
		<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( $name ); ?></a>
	</p>
	<?php
}

/**
 * برند خودرو.
 *
 * @param int $post_id شناسه.
 * @return array{name:string,url:string}|null
 */
function lk_vehicle_brand( int $post_id ): ?array {
	$terms = get_the_terms( $post_id, 'brand' );
	if ( ! is_array( $terms ) || ! $terms ) {
		return null;
	}
	return array(
		'name' => (string) $terms[0]->name,
		'url'  => (string) get_term_link( $terms[0] ),
	);
}

/**
 * برچسب فارسی یک مشخصه فنی.
 *
 * @param string $group گروه.
 * @param string $key   کلید.
 * @return string
 */
function lk_spec_label( string $group, string $key ): string {
	if ( ! class_exists( 'LKC_Meta_Boxes' ) ) {
		return $key;
	}
	$fields = LKC_Meta_Boxes::fields();
	return (string) ( $fields[ $group ]['fields'][ $key ]['label'] ?? $key );
}

/**
 * مقدار نمایشی یک مشخصه (ترجمه گزینه‌های select).
 *
 * @param string $group گروه.
 * @param string $key   کلید.
 * @param mixed  $value مقدار خام.
 * @return string
 */
function lk_spec_value( string $group, string $key, $value ): string {
	if ( ! class_exists( 'LKC_Meta_Boxes' ) ) {
		return is_scalar( $value ) ? (string) $value : '';
	}

	$field = LKC_Meta_Boxes::fields()[ $group ]['fields'][ $key ] ?? array();

	if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
		return (string) ( $field['options'][ (string) $value ] ?? (string) $value );
	}

	if ( is_array( $value ) ) {
		return implode( '، ', array_map( 'strval', $value ) );
	}

	$suffix = '';
	if ( 'engine' === $group && in_array( $key, array( 'engine_cc' ), true ) ) {
		$suffix = ' ' . __( 'سی‌سی', 'lamerd-khodro' );
	} elseif ( 'engine' === $group && 'max_power' === $key ) {
		$suffix = ' ' . __( 'اسب‌بخار', 'lamerd-khodro' );
	} elseif ( 'engine' === $group && 'max_torque' === $key ) {
		$suffix = ' ' . __( 'نیوتن‌متر', 'lamerd-khodro' );
	} elseif ( 'performance' === $group && 'acceleration' === $key ) {
		$suffix = ' ' . __( 'ثانیه', 'lamerd-khodro' );
	} elseif ( 'performance' === $group && 'top_speed' === $key ) {
		$suffix = ' ' . __( 'کیلومتر/ساعت', 'lamerd-khodro' );
	} elseif ( 'dimensions' === $group && in_array( $key, array( 'length', 'width', 'height', 'wheelbase' ), true ) ) {
		$suffix = ' ' . __( 'میلی‌متر', 'lamerd-khodro' );
	} elseif ( 'dimensions' === $group && 'weight' === $key ) {
		$suffix = ' ' . __( 'کیلوگرم', 'lamerd-khodro' );
	} elseif ( 'dimensions' === $group && in_array( $key, array( 'trunk', 'tank' ), true ) ) {
		$suffix = ' ' . __( 'لیتر', 'lamerd-khodro' );
	} elseif ( 'dimensions' === $group && 'seats' === $key ) {
		$suffix = ' ' . __( 'نفر', 'lamerd-khodro' );
	}

	return lk_fa_digits( (string) $value ) . $suffix;
}

/**
 * بخش‌های مشخصات برای نمایش.
 *
 * @param int $post_id شناسه خودرو.
 * @return array<string, array<string, array{label:string,value:string}>>
 */
function lk_spec_sections( int $post_id ): array {
	if ( ! function_exists( 'lamerd_khodro_core' ) ) {
		return array();
	}

	$specs = lamerd_khodro_core()->vehicles()->get_specs( $post_id );
	$out   = array();

	foreach ( $specs as $group_key => $group ) {
		$rows = array();
		foreach ( (array) $group['fields'] as $key => $value ) {
			$rows[ $key ] = array(
				'label' => lk_spec_label( (string) $group_key, (string) $key ),
				'value' => lk_spec_value( (string) $group_key, (string) $key, $value ),
			);
		}
		if ( $rows ) {
			$out[ (string) $group_key ] = array(
				'title' => (string) $group['title'],
				'rows'  => $rows,
			);
		}
	}

	return $out;
}

/**
 * مسیر راهنما (Breadcrumb).
 */
function lk_breadcrumbs(): void {
	if ( is_front_page() ) {
		return;
	}

	$items = array(
		array(
			'label' => __( 'خانه', 'lamerd-khodro' ),
			'url'   => home_url( '/' ),
		),
	);

	if ( is_singular( 'vehicles' ) ) {
		$items[] = array(
			'label' => __( 'قیمت خودرو', 'lamerd-khodro' ),
			'url'   => (string) get_post_type_archive_link( 'vehicles' ),
		);
		$brand   = lk_vehicle_brand( get_the_ID() );
		if ( $brand ) {
			$items[] = array(
				'label' => $brand['name'],
				'url'   => $brand['url'],
			);
		}
		$items[] = array(
			'label' => get_the_title(),
			'url'   => '',
		);
	} elseif ( is_singular( 'listing' ) ) {
		$items[] = array(
			'label' => __( 'آگهی‌های فروش', 'lamerd-khodro' ),
			'url'   => (string) ( get_post_type_archive_link( 'listing' ) ?: home_url( '/listings/' ) ),
		);
		$items[] = array(
			'label' => get_the_title(),
			'url'   => '',
		);
	} elseif ( is_singular() ) {
		$categories = get_the_category();
		if ( $categories ) {
			$items[] = array(
				'label' => $categories[0]->name,
				'url'   => (string) get_category_link( $categories[0] ),
			);
		}
		$items[] = array(
			'label' => get_the_title(),
			'url'   => '',
		);
	} elseif ( is_post_type_archive() ) {
		$items[] = array(
			'label' => post_type_archive_title( '', false ),
			'url'   => '',
		);
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$items[] = array(
			'label' => single_term_title( '', false ),
			'url'   => '',
		);
	} elseif ( is_search() ) {
		$items[] = array(
			'label' => sprintf( __( 'نتیجه جستجو برای: %s', 'lamerd-khodro' ), get_search_query() ),
			'url'   => '',
		);
	} elseif ( is_404() ) {
		$items[] = array(
			'label' => __( 'صفحه یافت نشد', 'lamerd-khodro' ),
			'url'   => '',
		);
	} elseif ( is_archive() ) {
		$items[] = array(
			'label' => get_the_archive_title(),
			'url'   => '',
		);
	}

	echo '<nav class="lk-breadcrumb" aria-label="' . esc_attr__( 'مسیر صفحه', 'lamerd-khodro' ) . '"><ol>';
	foreach ( $items as $index => $item ) {
		$is_last = ( count( $items ) - 1 ) === $index;
		echo '<li>';
		if ( $is_last || '' === $item['url'] ) {
			echo '<span aria-current="page">' . esc_html( $item['label'] ) . '</span>';
		} else {
			echo '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
		}
		echo '</li>';
	}
	echo '</ol></nav>';
}

/**
 * صفحه‌بندی ساده.
 */
function lk_pagination(): void {
	$links = paginate_links(
		array(
			'type'      => 'array',
			'prev_text' => __( 'قبلی', 'lamerd-khodro' ),
			'next_text' => __( 'بعدی', 'lamerd-khodro' ),
		)
	);

	if ( ! is_array( $links ) || ! $links ) {
		return;
	}

	echo '<nav class="lk-pagination" aria-label="' . esc_attr__( 'صفحه‌بندی', 'lamerd-khodro' ) . '">';
	foreach ( $links as $link ) {
		echo wp_kses_post( $link );
	}
	echo '</nav>';
}

/**
 * داده ساختاریافته خودرو (JSON-LD).
 */
function lk_schema_vehicle(): void {
	if ( ! is_singular( 'vehicles' ) ) {
		return;
	}

	$post_id = get_the_ID();
	$price   = lk_get_price( $post_id );
	$brand   = lk_vehicle_brand( $post_id );

	$data = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Car',
		'name'        => get_the_title( $post_id ),
		'url'         => get_permalink( $post_id ),
		'description' => wp_strip_all_tags( get_the_excerpt( $post_id ) ),
	);

	if ( has_post_thumbnail( $post_id ) ) {
		$data['image'] = get_the_post_thumbnail_url( $post_id, 'large' );
	}
	if ( $brand ) {
		$data['brand'] = array(
			'@type' => 'Brand',
			'name'  => $brand['name'],
		);
	}

	$year = get_post_meta( $post_id, '_lkp_year', true );
	if ( $year ) {
		$data['vehicleModelDate'] = (string) $year;
	}

	$specs = array(
		'vehicleEngineDisplacement' => '_lkp_engine_cc',
		'fuelType'                  => '_lkp_fuel',
		'vehicleTransmission'       => '_lkp_transmission',
		'bodyType'                  => '_lkp_body_type',
	);
	foreach ( $specs as $schema_key => $meta_key ) {
		$value = get_post_meta( $post_id, $meta_key, true );
		if ( '' !== $value && null !== $value ) {
			$data[ $schema_key ] = (string) $value;
		}
	}

	if ( $price && null !== ( $price['market_price'] ?? null ) ) {
		$data['offers'] = array(
			'@type'         => 'Offer',
			'price'         => (int) $price['market_price'],
			'priceCurrency' => 'IRT',
			'availability'  => 'https://schema.org/InStock',
			'url'           => get_permalink( $post_id ),
		);
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
}
add_action( 'wp_head', 'lk_schema_vehicle', 20 );

/**
 * سازمان در صفحه اصلی (JSON-LD).
 */
function lk_schema_organization(): void {
	if ( ! is_front_page() ) {
		return;
	}

	$data = array(
		'@context' => 'https://schema.org',
		'@type'    => 'WebSite',
		'name'     => get_bloginfo( 'name' ),
		'url'      => home_url( '/' ),
		'inLanguage' => 'fa-IR',
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/?s={search_term_string}&post_type=vehicles' ),
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
}
add_action( 'wp_head', 'lk_schema_organization', 21 );

/**
 * آدرس صفحه مقایسه.
 *
 * @param int[] $ids شناسه خودروها.
 * @return string
 */
function lk_compare_url( array $ids = array() ): string {
	$page = get_page_by_path( 'مقایسه' );
	$base = $page ? get_permalink( $page ) : home_url( '/مقایسه/' );

	if ( ! $ids ) {
		return $base;
	}

	return add_query_arg( 'ids', implode( ',', array_map( 'absint', $ids ) ), $base );
}
