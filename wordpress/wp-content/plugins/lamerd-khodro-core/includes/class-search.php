<?php
/**
 * جستجوی خودرو (فرم، AJAX و فیلتر آرشیو).
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

/**
 * جستجوی زنده خودرو و برند.
 */
class LKC_Search {

	/**
	 * اتصال هوک‌ها.
	 */
	public function register(): void {
		add_action( 'wp_ajax_lkc_search', array( $this, 'handle_ajax' ) );
		add_action( 'wp_ajax_nopriv_lkc_search', array( $this, 'handle_ajax' ) );
		add_shortcode( 'lkc_search', array( $this, 'shortcode' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_archive' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * بارگذاری اسکریپت جستجوی زنده.
	 */
	public function enqueue(): void {
		wp_enqueue_script(
			'lkc-search',
			LKC_URL . 'assets/frontend.js',
			array(),
			LKC_VERSION,
			true
		);

		wp_localize_script(
			'lkc-search',
			'lkcSearch',
			array(
				'endpoint' => esc_url_raw( admin_url( 'admin-ajax.php' ) ) . '?action=lkc_search',
				'nonce'    => wp_create_nonce( 'lkc_search' ),
				'empty'    => __( 'نتیجه‌ای یافت نشد.', 'lamerd-khodro' ),
				'loading'  => __( 'در حال جستجو…', 'lamerd-khodro' ),
			)
		);
	}

	/**
	 * جستجوی داخلی مشترک بین AJAX و REST.
	 *
	 * @param string $query عبارت جستجو.
	 * @param int    $limit تعداد نتایج.
	 * @return array<int, array<string, mixed>>
	 */
	public function query( string $query, int $limit = 8 ): array {
		$query = trim( wp_strip_all_tags( $query ) );
		if ( mb_strlen( $query, 'UTF-8' ) < 2 ) {
			return array();
		}

		$vehicles = get_posts(
			array(
				'post_type'      => LKC_Post_Types::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => min( 20, max( 1, $limit ) ),
				'no_found_rows'  => true,
				's'              => $query,
			)
		);

		$service = lamerd_khodro_core()->vehicles();
		$out     = array();

		foreach ( $vehicles as $post ) {
			$item = $service->format( $post );
			$out[] = array(
				'id'        => $item['id'],
				'title'     => $item['title'],
				'url'       => $item['url'],
				'brand'     => $item['brand']['name'] ?? '',
				'thumbnail' => $item['thumbnail'],
				'price'     => $item['price'],
			);
		}

		return $out;
	}

	/**
	 * پاسخ AJAX جستجوی زنده.
	 */
	public function handle_ajax(): void {
		check_ajax_referer( 'lkc_search', 'nonce' );

		$query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

		$results = $this->query( $query );

		wp_send_json_success(
			array(
				'query'   => $query,
				'results' => $results,
				'count'   => count( $results ),
			)
		);
	}

	/**
	 * شورتکد باکس جستجو.
	 *
	 * @param array<string, mixed> $atts پارامترها.
	 * @return string
	 */
	public function shortcode( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'placeholder' => __( 'نام خودرو یا برند را جستجو کنید…', 'lamerd-khodro' ),
				'variant'     => 'default',
			),
			$atts,
			'lkc_search'
		);

		$action = esc_url( home_url( '/' ) );

		ob_start();
		?>
		<div class="lkc-search lkc-search--<?php echo esc_attr( sanitize_html_class( (string) $atts['variant'] ) ); ?>" data-lkc-search>
			<form class="lkc-search__form" role="search" method="get" action="<?php echo $action; ?>">
				<label class="screen-reader-text" for="lkc-search-input"><?php esc_html_e( 'جستجوی خودرو', 'lamerd-khodro' ); ?></label>
				<input
					type="search"
					id="lkc-search-input"
					class="lkc-search__input"
					name="s"
					value="<?php echo esc_attr( get_search_query() ); ?>"
					placeholder="<?php echo esc_attr( (string) $atts['placeholder'] ); ?>"
					autocomplete="off"
					data-lkc-search-input
				/>
				<input type="hidden" name="post_type" value="<?php echo esc_attr( LKC_Post_Types::POST_TYPE ); ?>" />
				<button type="submit" class="lkc-search__submit">
					<span class="screen-reader-text"><?php esc_html_e( 'جستجو', 'lamerd-khodro' ); ?></span>
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
						<circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
						<path d="m16.5 16.5 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
					</svg>
				</button>
			</form>
			<div class="lkc-search__results" data-lkc-search-results hidden></div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * فیلتر آرشیو خودروها بر اساس پارامترهای URL.
	 *
	 * @param WP_Query $query کوئری.
	 */
	public function filter_archive( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( ! $query->is_post_type_archive( LKC_Post_Types::POST_TYPE ) && ! $query->is_tax( LKC_Taxonomies::BRAND ) ) {
			return;
		}

		$meta_query = (array) $query->get( 'meta_query' );

		$brand = isset( $_GET['lkc_brand'] ) ? sanitize_title( wp_unslash( $_GET['lkc_brand'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' !== $brand && ! $query->is_tax( LKC_Taxonomies::BRAND ) ) {
			$query->set(
				'tax_query',
				array(
					array(
						'taxonomy' => LKC_Taxonomies::BRAND,
						'field'    => 'slug',
						'terms'    => $brand,
					),
				)
			);
		}

		$year = isset( $_GET['lkc_year'] ) ? absint( wp_unslash( $_GET['lkc_year'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $year > 0 ) {
			$meta_query[] = array(
				'key'     => LKC_Meta_Boxes::PREFIX . 'year',
				'value'   => $year,
				'compare' => '=',
				'type'    => 'NUMERIC',
			);
		}

		$body = isset( $_GET['lkc_body'] ) ? sanitize_key( wp_unslash( $_GET['lkc_body'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' !== $body ) {
			$meta_query[] = array(
				'key'     => LKC_Meta_Boxes::PREFIX . 'body_type',
				'value'   => $body,
				'compare' => '=',
			);
		}

		if ( $meta_query ) {
			$query->set( 'meta_query', $meta_query );
		}

		$orderby = isset( $_GET['lkc_order'] ) ? sanitize_key( wp_unslash( $_GET['lkc_order'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		switch ( $orderby ) {
			case 'newest':
				$query->set( 'orderby', 'date' );
				$query->set( 'order', 'DESC' );
				break;
			case 'title':
				$query->set( 'orderby', 'title' );
				$query->set( 'order', 'ASC' );
				break;
			case 'popular':
				$query->set( 'meta_key', LKC_Meta_Boxes::PREFIX . 'views' ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'order', 'DESC' );
				break;
		}

		$query->set( 'posts_per_page', 12 );
	}
}
