<?php
/**
 * سرویس‌های داده خودرو و برند (قرارداد عمومی بین افزونه‌ها و قالب).
 *
 * قالب و افزونه Price Engine فقط از طریق این سرویس‌ها به داده دسترسی دارند تا
 * ساختار داخلی CPT و متا‌ها قابل تغییر بماند.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

/**
 * سرویس خواندن/نوشتن خودروها.
 */
class LKC_Vehicle_Service {

	/**
	 * حداکثر تعداد در هر درخواست (جلوگیری از کوئری سنگین).
	 */
	const MAX_LIMIT = 100;

	/**
	 * دریافت یک خودرو به شکل آرایه نرمال‌شده.
	 *
	 * @param int $post_id شناسه نوشته.
	 * @return array<string, mixed>|null
	 */
	public function get( int $post_id ): ?array {
		if ( $post_id <= 0 ) {
			return null;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || LKC_Post_Types::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}

		return $this->format( $post );
	}

	/**
	 * فهرست خودروها.
	 *
	 * @param array<string, mixed> $args فیلترها.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_all( array $args = array() ): array {
		$args = wp_parse_args(
			$args,
			array(
				'limit'    => 12,
				'page'     => 1,
				'brand'    => '',
				'search'   => '',
				'orderby'  => 'date',
				'order'    => 'DESC',
				'year'     => 0,
				'body'     => '',
				'exclude'  => array(),
				'include'  => array(),
				'production_status' => '',
			)
		);

		$limit = min( self::MAX_LIMIT, max( 1, (int) $args['limit'] ) );
		$page  = max( 1, (int) $args['page'] );

		$query_args = array(
			'post_type'           => LKC_Post_Types::POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'paged'               => $page,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		if ( ! empty( $args['exclude'] ) ) {
			$query_args['post__not_in'] = array_map( 'absint', (array) $args['exclude'] );
		}
		if ( ! empty( $args['include'] ) ) {
			$query_args['post__in'] = array_map( 'absint', (array) $args['include'] );
		}

		if ( '' !== (string) $args['brand'] ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => LKC_Taxonomies::BRAND,
					'field'    => is_numeric( $args['brand'] ) ? 'term_id' : 'slug',
					'terms'    => is_numeric( $args['brand'] ) ? absint( $args['brand'] ) : sanitize_title( (string) $args['brand'] ),
				),
			);
		}

		if ( '' !== (string) $args['search'] ) {
			$query_args['s'] = sanitize_text_field( (string) $args['search'] );
		}

		$meta_query = array();
		if ( (int) $args['year'] > 0 ) {
			$meta_query[] = array(
				'key'     => LKC_Meta_Boxes::PREFIX . 'year',
				'value'   => (int) $args['year'],
				'compare' => '=',
				'type'    => 'NUMERIC',
			);
		}
		if ( '' !== (string) $args['body'] ) {
			$meta_query[] = array(
				'key'     => LKC_Meta_Boxes::PREFIX . 'body_type',
				'value'   => sanitize_key( (string) $args['body'] ),
				'compare' => '=',
			);
		}
		if ( '' !== (string) $args['production_status'] ) {
			$meta_query[] = array(
				'key'     => LKC_Meta_Boxes::PREFIX . 'production_status',
				'value'   => sanitize_key( (string) $args['production_status'] ),
				'compare' => '=',
			);
		}
		if ( $meta_query ) {
			$query_args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		switch ( (string) $args['orderby'] ) {
			case 'views':
				$query_args['meta_key'] = LKC_Meta_Boxes::PREFIX . 'views'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby']  = 'meta_value_num';
				break;
			case 'title':
				$query_args['orderby'] = 'title';
				break;
			case 'rand':
				$query_args['orderby'] = 'rand';
				break;
			case 'modified':
				$query_args['orderby'] = 'modified';
				break;
			default:
				$query_args['orderby'] = 'date';
		}
		$query_args['order'] = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';

		$posts = get_posts( $query_args );

		return array_map( array( $this, 'format' ), $posts );
	}

	/**
	 * تبدیل نوشته به ساختار خروجی.
	 *
	 * @param WP_Post $post نوشته.
	 * @return array<string, mixed>
	 */
	public function format( WP_Post $post ): array {
		$brands = get_the_terms( $post->ID, LKC_Taxonomies::BRAND );

		return array(
			'id'        => $post->ID,
			'title'     => get_the_title( $post ),
			'url'       => get_permalink( $post ),
			'excerpt'   => get_the_excerpt( $post ),
			'thumbnail' => get_the_post_thumbnail_url( $post, 'medium_large' ) ?: '',
			'date'      => get_the_date( 'c', $post ),
			'brand'     => ( is_array( $brands ) && $brands ) ? array(
				'id'   => (int) $brands[0]->term_id,
				'name' => $brands[0]->name,
				'slug' => $brands[0]->slug,
			) : null,
			'specs'     => $this->get_specs( $post->ID ),
			'price'     => $this->price( $post->ID ),
			'views'     => (int) get_post_meta( $post->ID, LKC_Meta_Boxes::PREFIX . 'views', true ),
		);
	}

	/**
	 * دریافت قیمت جاری خودرو از Price Engine (در صورت نصب بودن).
	 *
	 * @param int $post_id شناسه خودرو.
	 * @return array<string, mixed>|null
	 */
	public function price( int $post_id ): ?array {
		if ( ! lkc_has_price_engine() ) {
			return null;
		}

		$api_id = (int) get_post_meta( $post_id, LKC_Meta_Boxes::PREFIX . 'price_vehicle_id', true );
		if ( $api_id <= 0 ) {
			return null;
		}

		$engine = lamerd_khodro_price_engine();
		if ( ! $engine || ! method_exists( $engine, 'prices' ) ) {
			return null;
		}

		$prices = $engine->prices();
		if ( ! is_object( $prices ) || ! method_exists( $prices, 'get_current' ) ) {
			return null;
		}

		$current = $prices->get_current( $api_id );
		return is_array( $current ) ? $current : null;
	}

	/**
	 * مشخصات فنی گروه‌بندی‌شده.
	 *
	 * @param int $post_id شناسه خودرو.
	 * @return array<string, array<string, mixed>>
	 */
	public function get_specs( int $post_id ): array {
		$out = array();
		foreach ( LKC_Meta_Boxes::public_fields() as $group_key => $group ) {
			$values = array();
			foreach ( array_keys( $group['fields'] ) as $field_key ) {
				$value = get_post_meta( $post_id, LKC_Meta_Boxes::PREFIX . $field_key, true );
				if ( '' === $value || null === $value || array() === $value ) {
					continue;
				}
				$values[ $field_key ] = $value;
			}
			if ( $values ) {
				$out[ $group_key ] = array(
					'title'  => $group['title'],
					'fields' => $values,
				);
			}
		}
		return $out;
	}

	/**
	 * خواندن یک مشخصه.
	 *
	 * @param int    $post_id شناسه خودرو.
	 * @param string $key     کلید بدون پیشوند.
	 * @param mixed  $default مقدار پیش‌فرض.
	 * @return mixed
	 */
	public function get_spec( int $post_id, string $key, $default = '' ) {
		$value = get_post_meta( $post_id, LKC_Meta_Boxes::PREFIX . $key, true );
		return ( '' === $value || null === $value ) ? $default : $value;
	}

	/**
	 * نرمال‌سازی نام خودرو برای تطبیق (فارسی/عربی، نیم‌فاصله، اعداد).
	 *
	 * @param string $name نام.
	 * @return string
	 */
	public function normalize_name( string $name ): string {
		return lkc_normalize_text( $name );
	}

	/**
	 * یافتن نوشته خودرو با شناسه ردیف قیمت (`lkp_vehicles.id`).
	 *
	 * @param int $api_id شناسه داخلی ردیف در جدول قیمت.
	 * @return int شناسه نوشته یا ۰.
	 */
	public function find_by_api_id( int $api_id ): int {
		if ( $api_id <= 0 ) {
			return 0;
		}

		$found = get_posts(
			array(
				'post_type'        => LKC_Post_Types::POST_TYPE,
				'post_status'      => 'any',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
				'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => LKC_Meta_Boxes::PREFIX . 'price_vehicle_id',
						'value'   => $api_id,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		return $found ? (int) $found[0] : 0;
	}

	/**
	 * یافتن خودرو بر اساس نام (تطبیق نرمال‌شده، سپس جستجوی متنی).
	 *
	 * @param string $name نام خودرو.
	 * @return int شناسه نوشته یا ۰.
	 */
	public function find_by_name( string $name ): int {
		$name = trim( $name );
		if ( '' === $name ) {
			return 0;
		}

		$target = $this->normalize_name( $name );

		$all = get_posts(
			array(
				'post_type'      => LKC_Post_Types::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 300,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		foreach ( $all as $id ) {
			if ( $this->normalize_name( get_the_title( (int) $id ) ) === $target ) {
				return (int) $id;
			}
		}

		$fallback = get_posts(
			array(
				'post_type'      => LKC_Post_Types::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				's'              => $name,
			)
		);

		return $fallback ? (int) $fallback[0] : 0;
	}

	/**
	 * اتصال خودرو به شناسه منبع قیمت.
	 *
	 * @param int $post_id شناسه خودرو.
	 * @param int $api_id  شناسه منبع.
	 * @return bool
	 */
	public function link_price_vehicle( int $post_id, int $api_id ): bool {
		if ( $post_id <= 0 || $api_id <= 0 ) {
			return false;
		}
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || LKC_Post_Types::POST_TYPE !== $post->post_type ) {
			return false;
		}
		update_post_meta( $post_id, LKC_Meta_Boxes::PREFIX . 'price_vehicle_id', $api_id );
		return true;
	}

	/**
	 * فهرست خودروهای متصل به منبع قیمت.
	 *
	 * @return array<int, int> نگاشت api_id => post_id.
	 */
	public function linked_map(): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value FROM {$wpdb->postmeta}
				 WHERE meta_key = %s AND meta_value <> '' AND meta_value <> '0'",
				LKC_Meta_Boxes::PREFIX . 'price_vehicle_id'
			)
		);

		$map = array();
		foreach ( (array) $rows as $row ) {
			$map[ (int) $row->meta_value ] = (int) $row->post_id;
		}
		return $map;
	}

	/**
	 * افزایش شمارنده بازدید (یک‌بار در هر نشست).
	 *
	 * @param int $post_id شناسه خودرو.
	 * @return int
	 */
	public function increment_views( int $post_id ): int {
		if ( $post_id <= 0 || is_admin() ) {
			return 0;
		}

		$key = LKC_Meta_Boxes::PREFIX . 'views';
		$current = (int) get_post_meta( $post_id, $key, true );
		$current++;
		update_post_meta( $post_id, $key, $current );

		return $current;
	}
}

/**
 * سرویس برندها.
 */
class LKC_Brand_Service {

	/**
	 * فهرست برندها.
	 *
	 * @param array<string, mixed> $args فیلترها.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_all( array $args = array() ): array {
		$args = wp_parse_args(
			$args,
			array(
				'limit'    => 20,
				'orderby'  => 'count',
				'order'    => 'DESC',
				'hide_empty' => true,
			)
		);

		$terms = get_terms(
			array(
				'taxonomy'   => LKC_Taxonomies::BRAND,
				'hide_empty' => (bool) $args['hide_empty'],
				'number'     => max( 1, (int) $args['limit'] ),
				'orderby'    => in_array( $args['orderby'], array( 'count', 'name', 'slug', 'term_id' ), true ) ? $args['orderby'] : 'count',
				'order'      => 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC',
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		return array_map( array( $this, 'format' ), $terms );
	}

	/**
	 * تبدیل ترم به خروجی.
	 *
	 * @param WP_Term $term ترم.
	 * @return array<string, mixed>
	 */
	public function format( WP_Term $term ): array {
		$logo_id = (int) get_term_meta( $term->term_id, '_lkc_brand_logo', true );

		return array(
			'id'      => (int) $term->term_id,
			'name'    => $term->name,
			'name_en' => (string) get_term_meta( $term->term_id, '_lkc_brand_name_en', true ),
			'slug'    => $term->slug,
			'country' => (string) get_term_meta( $term->term_id, '_lkc_brand_country', true ),
			'website' => (string) get_term_meta( $term->term_id, '_lkc_brand_website', true ),
			'logo'    => $logo_id ? (string) wp_get_attachment_image_url( $logo_id, 'medium' ) : '',
			'count'   => (int) $term->count,
			'url'     => (string) get_term_link( $term ),
		);
	}

	/**
	 * دریافت برند با شناسه یا اسلاگ.
	 *
	 * @param int|string $identifier شناسه یا اسلاگ.
	 * @return array<string, mixed>|null
	 */
	public function get( $identifier ): ?array {
		$term = get_term_by( is_numeric( $identifier ) ? 'id' : 'slug', $identifier, LKC_Taxonomies::BRAND );
		return ( $term instanceof WP_Term ) ? $this->format( $term ) : null;
	}

	/**
	 * برندهای یک خودرو.
	 *
	 * @param int $post_id شناسه خودرو.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_for_vehicle( int $post_id ): array {
		$terms = get_the_terms( $post_id, LKC_Taxonomies::BRAND );
		if ( ! is_array( $terms ) ) {
			return array();
		}
		return array_map( array( $this, 'format' ), $terms );
	}

	/**
	 * یافتن برند با نام فارسی یا انگلیسی (بدون ایجاد ترم).
	 *
	 * @param string $name نام.
	 * @return int شناسه ترم یا ۰.
	 */
	public function find_by_name( string $name ): int {
		$name    = trim( $name );
		$target  = lkc_normalize_text( $name );
		$terms   = get_terms(
			array(
				'taxonomy'   => LKC_Taxonomies::BRAND,
				'hide_empty' => false,
				'number'     => 300,
			)
		);
		if ( is_wp_error( $terms ) ) {
			return 0;
		}

		foreach ( $terms as $term ) {
			if ( lkc_normalize_text( $term->name ) === $target ) {
				return (int) $term->term_id;
			}
			$en = (string) get_term_meta( $term->term_id, '_lkc_brand_name_en', true );
			if ( '' !== $en && lkc_normalize_text( $en ) === $target ) {
				return (int) $term->term_id;
			}
		}

		return 0;
	}
}
