<?php
/**
 * ثبت Post Type خودرو.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

/**
 * CPT اختصاصی بانک اطلاعات خودرو.
 */
class LKC_Post_Types {

	const POST_TYPE = 'vehicles';

	/**
	 * اتصال هوک‌ها.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_filter( 'manage_vehicles_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_vehicles_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-vehicles_sortable_columns', array( $this, 'sortable_columns' ) );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
	}

	/**
	 * ثبت CPT خودرو.
	 */
	public function register_post_type(): void {
		$labels = array(
			'name'               => __( 'خودروها', 'lamerd-khodro' ),
			'singular_name'      => __( 'خودرو', 'lamerd-khodro' ),
			'menu_name'          => __( 'بانک اطلاعات خودرو', 'lamerd-khodro' ),
			'add_new'            => __( 'افزودن خودرو', 'lamerd-khodro' ),
			'add_new_item'       => __( 'افزودن خودرو جدید', 'lamerd-khodro' ),
			'edit_item'          => __( 'ویرایش خودرو', 'lamerd-khodro' ),
			'new_item'           => __( 'خودروی جدید', 'lamerd-khodro' ),
			'view_item'          => __( 'مشاهده خودرو', 'lamerd-khodro' ),
			'search_items'       => __( 'جستجوی خودرو', 'lamerd-khodro' ),
			'not_found'          => __( 'خودرویی یافت نشد', 'lamerd-khodro' ),
			'not_found_in_trash' => __( 'خودرویی در زباله‌دان نیست', 'lamerd-khodro' ),
			'all_items'          => __( 'همه خودروها', 'lamerd-khodro' ),
			'featured_image'     => __( 'تصویر اصلی خودرو', 'lamerd-khodro' ),
			'set_featured_image' => __( 'تنظیم تصویر اصلی', 'lamerd-khodro' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => $labels,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'query_var'          => true,
				'rewrite'            => array(
					'slug'       => 'vehicle',
					'with_front' => false,
				),
				'capability_type'    => 'post',
				'has_archive'        => 'vehicles',
				'hierarchical'       => false,
				'menu_position'      => 5,
				'menu_icon'          => 'dashicons-car',
				'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments', 'revisions' ),
				'taxonomies'         => array( LKC_Taxonomies::BRAND ),
			)
		);
	}

	/**
	 * ستون‌های فهرست مدیریت.
	 *
	 * @param array<string,string> $columns ستون‌ها.
	 * @return array<string,string>
	 */
	public function columns( array $columns ): array {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['lkc_brand']    = __( 'برند', 'lamerd-khodro' );
				$new['lkc_price']    = __( 'قیمت بازار', 'lamerd-khodro' );
				$new['lkc_views']    = __( 'بازدید', 'lamerd-khodro' );
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
			case 'lkc_brand':
				$terms = get_the_terms( $post_id, LKC_Taxonomies::BRAND );
				if ( is_array( $terms ) && $terms ) {
					echo esc_html( $terms[0]->name );
				} else {
					echo '&mdash;';
				}
				break;

			case 'lkc_price':
				if ( function_exists( 'lamerd_khodro_price_engine' ) ) {
					$vehicle_id = (int) get_post_meta( $post_id, '_lkp_price_vehicle_id', true );
					if ( $vehicle_id ) {
						$price = lamerd_khodro_price_engine()->prices()->get_current( $vehicle_id );
						if ( $price && null !== $price['market_price'] ) {
							echo esc_html( lkc_format_price( (int) $price['market_price'] ) );
							break;
						}
					}
				}
				echo '&mdash;';
				break;

			case 'lkc_views':
				echo esc_html( (string) (int) get_post_meta( $post_id, '_lkp_views', true ) );
				break;
		}
	}

	/**
	 * ستون قابل مرتب‌سازی.
	 *
	 * @param array<string,string> $columns ستون‌ها.
	 * @return array<string,string>
	 */
	public function sortable_columns( array $columns ): array {
		$columns['lkc_views'] = 'lkc_views';
		return $columns;
	}

	/**
	 * کلاس بدنه برای RTL در پیشخوان.
	 *
	 * @param string $classes کلاس‌ها.
	 * @return string
	 */
	public function admin_body_class( string $classes ): string {
		if ( in_array( 'post-type-vehicles', explode( ' ', $classes ), true ) ) {
			$classes .= ' lkc-admin-rtl ';
		}
		return $classes;
	}
}
