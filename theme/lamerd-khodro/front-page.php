<?php
/**
 * صفحه اصلی.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lk_core     = function_exists( 'lamerd_khodro_core' );
$lk_settings = class_exists( 'LKC_Homepage_Settings' ) ? 'LKC_Homepage_Settings' : null;

/**
 * خواندن تنظیم با پیش‌فرض.
 *
 * @param string $key کلید.
 * @param mixed  $default پیش‌فرض.
 * @return mixed
 */
$lk_get = static function ( string $key, $default = '' ) use ( $lk_settings ) {
	if ( ! $lk_settings ) {
		return $default;
	}
	$value = $lk_settings::get( $key );
	return ( '' === $value || null === $value ) ? $default : $value;
};

$lk_vehicles = $lk_core ? lamerd_khodro_core()->vehicles() : null;
$lk_brands   = $lk_core ? lamerd_khodro_core()->brands() : null;

$lk_prices_items = $lk_vehicles ? $lk_vehicles->get_all( array( 'limit' => (int) $lk_get( 'prices_count', 6 ), 'orderby' => 'modified' ) ) : array();
$lk_new_items    = $lk_vehicles ? $lk_vehicles->get_all( array( 'limit' => (int) $lk_get( 'new_cars_count', 4 ), 'orderby' => 'date' ) ) : array();
$lk_popular      = $lk_vehicles ? $lk_vehicles->get_all( array( 'limit' => (int) $lk_get( 'popular_count', 4 ), 'orderby' => 'views' ) ) : array();
$lk_brand_items  = $lk_brands ? $lk_brands->get_all( array( 'limit' => (int) $lk_get( 'brands_count', 12 ) ) ) : array();
?>

<section class="lk-hero">
	<div class="lk-container lk-hero__inner">
		<div class="lk-hero__content">
			<span class="lk-hero__eyebrow">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
					<path d="m12 3 2.6 5.6 6.1.8-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6-4.5-4.2 6.1-.8L12 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
				</svg>
				<?php esc_html_e( 'قیمت روز بازار خودرو ایران', 'lamerd-khodro' ); ?>
			</span>

			<h1 class="lk-hero__title"><?php echo esc_html( (string) $lk_get( 'hero_title', get_bloginfo( 'name' ) ) ); ?></h1>
			<p class="lk-hero__text"><?php echo esc_html( (string) $lk_get( 'hero_text' ) ); ?></p>

			<?php if ( $lk_get( 'hero_search', 1 ) ) : ?>
				<div class="lk-hero__search">
					<?php if ( shortcode_exists( 'lkc_search' ) ) : ?>
						<?php echo do_shortcode( '[lkc_search variant="hero"]' ); ?>
					<?php else : ?>
						<form class="lk-hero-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
							<label class="screen-reader-text" for="lk-hero-s"><?php esc_html_e( 'جستجوی خودرو', 'lamerd-khodro' ); ?></label>
							<input type="search" id="lk-hero-s" name="s" placeholder="<?php esc_attr_e( 'نام خودرو یا برند را بنویسید…', 'lamerd-khodro' ); ?>" />
							<input type="hidden" name="post_type" value="vehicles" />
							<button type="submit" class="lk-btn lk-btn--primary"><?php esc_html_e( 'جستجو', 'lamerd-khodro' ); ?></button>
						</form>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<ul class="lk-hero__stats">
				<?php
				$lk_vehicle_count = 0;
				if ( post_type_exists( 'vehicles' ) ) {
					$lk_counts        = wp_count_posts( 'vehicles' );
					$lk_vehicle_count = isset( $lk_counts->publish ) ? (int) $lk_counts->publish : 0;
				}

				$lk_brand_ids = get_terms(
					array(
						'taxonomy'   => 'brand',
						'hide_empty' => true,
						'fields'     => 'ids',
					)
				);
				$lk_brand_count = is_wp_error( $lk_brand_ids ) ? 0 : count( (array) $lk_brand_ids );
				?>
				<li class="lk-hero__stat">
					<strong><?php echo esc_html( lk_fa_digits( number_format_i18n( $lk_vehicle_count ) ) ); ?></strong>
					<span><?php esc_html_e( 'خودرو در بانک اطلاعات', 'lamerd-khodro' ); ?></span>
				</li>
				<li class="lk-hero__stat">
					<strong><?php echo esc_html( lk_fa_digits( number_format_i18n( $lk_brand_count ) ) ); ?></strong>
					<span><?php esc_html_e( 'برند فعال', 'lamerd-khodro' ); ?></span>
				</li>
				<li class="lk-hero__stat">
					<strong>
						<?php
						$lk_last_sync = '';
						if ( function_exists( 'lamerd_khodro_price_engine' ) ) {
							$lk_last_sync = (string) ( lamerd_khodro_price_engine()->prices()->last_fetch() ?: '' );
						}
						echo esc_html( $lk_last_sync ? lk_posted_on_fallback( $lk_last_sync ) : __( '—', 'lamerd-khodro' ) );
						?>
					</strong>
					<span><?php esc_html_e( 'آخرین به‌روزرسانی قیمت', 'lamerd-khodro' ); ?></span>
				</li>
			</ul>
		</div>

		<aside class="lk-hero__card" aria-label="<?php esc_attr_e( 'آخرین تغییرات قیمت', 'lamerd-khodro' ); ?>">
			<h2 class="lk-hero__card-title"><?php esc_html_e( 'پرنوسان‌ترین قیمت‌های امروز', 'lamerd-khodro' ); ?></h2>

			<?php if ( $lk_prices_items ) : ?>
				<ul class="lk-hero__list">
					<?php foreach ( array_slice( $lk_prices_items, 0, 4 ) as $lk_item ) : ?>
						<?php
						$lk_price  = $lk_item['price'] ?? null;
						$lk_market = is_array( $lk_price ) ? ( $lk_price['market_price'] ?? null ) : null;
						?>
						<li>
							<a href="<?php echo esc_url( (string) $lk_item['url'] ); ?>"><?php echo esc_html( (string) $lk_item['title'] ); ?></a>
							<span class="lk-hero__price">
								<?php echo esc_html( null === $lk_market ? __( 'ناموجود', 'lamerd-khodro' ) : lk_short_money( (int) $lk_market ) ); ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="lk-hero__price">
					<?php esc_html_e( 'پس از افزودن خودروها و اجرای همگام‌سازی، آخرین تغییرات اینجا نمایش داده می‌شود.', 'lamerd-khodro' ); ?>
				</p>
			<?php endif; ?>
		</aside>
	</div>
</section>

<?php if ( $lk_get( 'show_prices', 1 ) ) : ?>
	<section class="lk-section">
		<div class="lk-container">
			<div class="lk-section__head">
				<h2 class="lk-section__title"><?php esc_html_e( 'قیمت روز خودرو', 'lamerd-khodro' ); ?></h2>
				<a class="lk-section__more" href="<?php echo esc_url( get_post_type_archive_link( 'vehicles' ) ?: home_url( '/vehicles/' ) ); ?>">
					<?php esc_html_e( 'مشاهده همه قیمت‌ها', 'lamerd-khodro' ); ?> ←
				</a>
			</div>

			<?php
			if ( $lk_prices_items ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo lk_render_template_part( 'template-parts/price-table', $lk_prices_items );
			} else {
				echo '<div class="lk-empty">' . esc_html__( 'هنوز خودرویی در بانک اطلاعات ثبت نشده است. از پیشخوان، خودروها را اضافه کنید.', 'lamerd-khodro' ) . '</div>';
			}
			?>
		</div>
	</section>
<?php endif; ?>

<?php if ( $lk_get( 'show_brands', 1 ) && $lk_brand_items ) : ?>
	<section class="lk-section lk-section--alt">
		<div class="lk-container">
			<div class="lk-section__head">
				<h2 class="lk-section__title"><?php esc_html_e( 'برندهای خودرو', 'lamerd-khodro' ); ?></h2>
				<a class="lk-section__more" href="<?php echo esc_url( home_url( '/brand/' ) ); ?>">
					<?php esc_html_e( 'همه برندها', 'lamerd-khodro' ); ?> ←
				</a>
			</div>

			<div class="lk-brands">
				<?php foreach ( $lk_brand_items as $lk_brand ) : ?>
					<a class="lk-brand" href="<?php echo esc_url( (string) $lk_brand['url'] ); ?>">
						<span class="lk-brand__logo">
							<?php if ( ! empty( $lk_brand['logo'] ) ) : ?>
								<img src="<?php echo esc_url( (string) $lk_brand['logo'] ); ?>" alt="<?php echo esc_attr( (string) $lk_brand['name'] ); ?>" loading="lazy" />
							<?php else : ?>
								<?php echo esc_html( mb_substr( (string) $lk_brand['name'], 0, 1, 'UTF-8' ) ); ?>
							<?php endif; ?>
						</span>
						<span class="lk-brand__name"><?php echo esc_html( (string) $lk_brand['name'] ); ?></span>
						<span class="lk-brand__count"><?php echo esc_html( lk_fa_digits( number_format_i18n( (int) $lk_brand['count'] ) ) . ' ' . __( 'خودرو', 'lamerd-khodro' ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if ( $lk_get( 'show_new_cars', 1 ) && $lk_new_items ) : ?>
	<section class="lk-section">
		<div class="lk-container">
			<div class="lk-section__head">
				<h2 class="lk-section__title"><?php esc_html_e( 'خودروهای تازه‌افزوده', 'lamerd-khodro' ); ?></h2>
				<a class="lk-section__more" href="<?php echo esc_url( get_post_type_archive_link( 'vehicles' ) ?: home_url( '/vehicles/' ) ); ?>">
					<?php esc_html_e( 'مشاهده همه', 'lamerd-khodro' ); ?> ←
				</a>
			</div>

			<div class="lk-cards">
				<?php foreach ( $lk_new_items as $lk_item ) : ?>
					<?php lk_load_template_part( 'template-parts/card-vehicle', $lk_item ); ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if ( $lk_get( 'show_compare_cta', 1 ) ) : ?>
	<section class="lk-section lk-section--tight">
		<div class="lk-container">
			<div class="lk-compare-band">
				<div>
					<h2><?php esc_html_e( 'کدام خودرو برای شما مناسب‌تر است؟', 'lamerd-khodro' ); ?></h2>
					<p><?php esc_html_e( 'دو تا چهار خودرو را انتخاب کنید و مشخصات فنی، قیمت و امکانات را کنار هم ببینید.', 'lamerd-khodro' ); ?></p>
				</div>
				<div class="lk-compare-band__form">
					<a class="lk-btn lk-btn--accent" href="<?php echo esc_url( lk_compare_url() ); ?>" data-lk-compare-link>
						<?php esc_html_e( 'شروع مقایسه خودرو', 'lamerd-khodro' ); ?>
					</a>
					<a class="lk-btn lk-btn--ghost" style="background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.3);color:#fff" href="<?php echo esc_url( get_post_type_archive_link( 'vehicles' ) ?: home_url( '/vehicles/' ) ); ?>">
						<?php esc_html_e( 'انتخاب خودرو', 'lamerd-khodro' ); ?>
					</a>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if ( $lk_get( 'show_popular', 1 ) && $lk_popular ) : ?>
	<section class="lk-section lk-section--alt">
		<div class="lk-container">
			<div class="lk-section__head">
				<h2 class="lk-section__title"><?php esc_html_e( 'محبوب‌ترین خودروها', 'lamerd-khodro' ); ?></h2>
			</div>
			<div class="lk-cards">
				<?php foreach ( $lk_popular as $lk_item ) : ?>
					<?php lk_load_template_part( 'template-parts/card-vehicle', $lk_item ); ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php
$lk_featured_listings = array();
$lk_listing_ids       = array();

if ( class_exists( 'LKC_Listings' ) ) {
	$lk_featured_listings = LKC_Listings::featured_ids( 2 );
	$lk_listing_ids       = array_merge(
		$lk_featured_listings,
		LKC_Listings::latest_ids( (int) $lk_get( 'listings_count', 6 ), $lk_featured_listings )
	);
}

if ( $lk_get( 'show_listings', 1 ) && $lk_listing_ids ) :
	$lk_listings_url = get_post_type_archive_link( 'listing' ) ?: home_url( '/listings/' );
	?>
	<section class="lk-section lk-listings">
		<div class="lk-container">
			<div class="lk-section__head">
				<h2 class="lk-section__title"><?php esc_html_e( 'آگهی‌های فروش خودرو', 'lamerd-khodro' ); ?></h2>
				<a class="lk-section__more" href="<?php echo esc_url( $lk_listings_url ); ?>">
					<?php esc_html_e( 'همه آگهی‌ها', 'lamerd-khodro' ); ?> ←
				</a>
			</div>

			<p class="lk-listings__intro">
				<?php esc_html_e( 'خودروهای آماده فروش با قیمت و مشخصات؛ آگهی‌های ویژه تا پایان مهلت با تایمر نمایش داده می‌شوند.', 'lamerd-khodro' ); ?>
			</p>

			<div class="lk-ads__grid">
				<?php foreach ( $lk_listing_ids as $lk_listing_id ) : ?>
					<?php lk_load_template_part( 'template-parts/card-listing', lk_listing_data( (int) $lk_listing_id ) ); ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php
$lk_news_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'posts_per_page'      => (int) $lk_get( 'news_count', 4 ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

if ( $lk_get( 'show_news', 1 ) && $lk_news_query->have_posts() ) :
	?>
	<section class="lk-section">
		<div class="lk-container">
			<div class="lk-section__head">
				<h2 class="lk-section__title"><?php esc_html_e( 'آخرین اخبار خودرو', 'lamerd-khodro' ); ?></h2>
				<a class="lk-section__more" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/' ) ); ?>">
					<?php esc_html_e( 'همه اخبار', 'lamerd-khodro' ); ?> ←
				</a>
			</div>

			<div class="lk-news">
				<?php
				while ( $lk_news_query->have_posts() ) :
					$lk_news_query->the_post();
					$lk_cats = get_the_category();
					?>
					<article class="lk-news-card">
						<a class="lk-news-card__media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'lk-card', array( 'loading' => 'lazy' ) ); ?>
							<?php endif; ?>
						</a>
						<div class="lk-news-card__body">
							<?php if ( $lk_cats ) : ?>
								<span class="lk-news-card__cat"><?php echo esc_html( $lk_cats[0]->name ); ?></span>
							<?php endif; ?>
							<h3 class="lk-news-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<p class="lk-news-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 16 ) ); ?></p>
							<span class="lk-news-card__date"><?php echo esc_html( lk_posted_on() ); ?></span>
						</div>
					</article>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</div>
	</section>
	<?php
endif;
?>

<?php
$lk_sales_cat  = get_category_by_slug( sanitize_title( 'شرایط فروش' ) );
$lk_sales_query = $lk_sales_cat
	? new WP_Query(
		array(
			'post_type'      => 'post',
			'posts_per_page' => (int) $lk_get( 'sales_count', 4 ),
			'cat'            => (int) $lk_sales_cat->term_id,
			'no_found_rows'  => true,
		)
	)
	: null;

if ( $lk_get( 'show_sales', 1 ) && $lk_sales_query && $lk_sales_query->have_posts() ) :
	?>
	<section class="lk-section lk-section--alt">
		<div class="lk-container">
			<div class="lk-section__head">
				<h2 class="lk-section__title"><?php esc_html_e( 'شرایط فروش خودرو', 'lamerd-khodro' ); ?></h2>
				<?php if ( $lk_sales_cat ) : ?>
					<a class="lk-section__more" href="<?php echo esc_url( (string) get_category_link( $lk_sales_cat ) ); ?>">
						<?php esc_html_e( 'همه شرایط فروش', 'lamerd-khodro' ); ?> ←
					</a>
				<?php endif; ?>
			</div>

			<div class="lk-news">
				<?php
				while ( $lk_sales_query->have_posts() ) :
					$lk_sales_query->the_post();
					?>
					<article class="lk-news-card">
						<div class="lk-news-card__body">
							<span class="lk-news-card__cat"><?php esc_html_e( 'شرایط فروش', 'lamerd-khodro' ); ?></span>
							<h3 class="lk-news-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<p class="lk-news-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
							<span class="lk-news-card__date"><?php echo esc_html( lk_posted_on() ); ?></span>
						</div>
					</article>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</div>
	</section>
	<?php
endif;
?>

<?php if ( is_active_sidebar( 'sidebar-1' ) ) : ?>
	<section class="lk-section lk-section--tight">
		<div class="lk-container">
			<?php dynamic_sidebar( 'sidebar-1' ); ?>
		</div>
	</section>
<?php endif; ?>

<?php
get_footer();
