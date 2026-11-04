<?php
/**
 * صفحه یک آگهی فروش خودرو.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$lk_ad       = lk_listing_data( get_the_ID() );
	$lk_ad_until = ! empty( $lk_ad['featured'] ) && (int) $lk_ad['remaining'] > 0
		? (int) current_time( 'timestamp' ) + (int) $lk_ad['remaining']
		: 0;

	$lk_ad_rows = array(
		__( 'برند', 'lamerd-khodro' )     => (string) ( $lk_ad['brand'] ?? '' ),
		__( 'سال ساخت', 'lamerd-khodro' ) => '' !== (string) ( $lk_ad['year'] ?? '' ) ? lk_fa_digits( (string) $lk_ad['year'] ) : '',
		__( 'کارکرد', 'lamerd-khodro' )   => null !== ( $lk_ad['mileage'] ?? null ) ? lk_fa_digits( number_format_i18n( (int) $lk_ad['mileage'] ) ) . ' ' . __( 'کیلومتر', 'lamerd-khodro' ) : '',
		__( 'شهر', 'lamerd-khodro' )      => (string) ( $lk_ad['city'] ?? '' ),
		__( 'رنگ', 'lamerd-khodro' )      => (string) ( $lk_ad['color'] ?? '' ),
	);

	$lk_ad_rows = array_filter( $lk_ad_rows, static fn( $value ) => '' !== trim( (string) $value ) );

	$lk_ad_phone = preg_replace( '/[^0-9+]/', '', (string) ( $lk_ad['phone'] ?? '' ) );
	?>
	<div class="lk-container">
		<?php lk_breadcrumbs(); ?>

		<article class="lk-ad-single">
			<div class="lk-ad-single__main">
				<div class="lk-ad-single__media<?php echo ! empty( $lk_ad['featured'] ) ? ' is-featured' : ''; ?>">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php the_post_thumbnail( 'lk-hero', array( 'loading' => 'eager' ) ); ?>
					<?php else : ?>
						<span class="lk-ad-single__placeholder" aria-hidden="true">
							<svg width="76" height="76" viewBox="0 0 24 24" fill="none">
								<path d="M3 13.5 5.2 8a3 3 0 0 1 2.8-2h8a3 3 0 0 1 2.8 2L21 13.5V18a1 1 0 0 1-1 1h-1.2a1 1 0 0 1-1-1v-.6H6.2V18a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-4.5Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
								<circle cx="7.4" cy="14.6" r="1.2" fill="currentColor"/>
								<circle cx="16.6" cy="14.6" r="1.2" fill="currentColor"/>
							</svg>
						</span>
					<?php endif; ?>

					<?php if ( ! empty( $lk_ad['featured'] ) ) : ?>
						<span class="lk-ad__badge"><?php esc_html_e( 'فروش ویژه', 'lamerd-khodro' ); ?></span>
					<?php endif; ?>
				</div>

				<h1 class="lk-ad-single__title"><?php the_title(); ?></h1>

				<div class="lk-ad-single__meta">
					<span><?php echo esc_html( lk_posted_on() ); ?></span>
					<?php if ( ! empty( $lk_ad['city'] ) ) : ?>
						<span><?php echo esc_html( (string) $lk_ad['city'] ); ?></span>
					<?php endif; ?>
				</div>

				<?php if ( $lk_ad_until ) : ?>
					<div class="lk-ad__timer lk-ad__timer--wide" data-lk-countdown="<?php echo esc_attr( (string) $lk_ad_until ); ?>">
						<span class="lk-ad__timer-icon" aria-hidden="true">⏱</span>
						<span class="lk-ad__timer-label"><?php esc_html_e( 'پایان فروش ویژه', 'lamerd-khodro' ); ?></span>
						<span class="lk-ad__timer-value" data-lk-countdown-value><?php echo esc_html( lk_countdown_text( (int) $lk_ad['remaining'] ) ); ?></span>
					</div>
				<?php endif; ?>

				<?php if ( $lk_ad_rows ) : ?>
					<div class="lk-ad-single__specs">
						<h2 class="lk-ad-single__subtitle"><?php esc_html_e( 'مشخصات خودرو', 'lamerd-khodro' ); ?></h2>
						<ul>
							<?php foreach ( $lk_ad_rows as $lk_ad_label => $lk_ad_value ) : ?>
								<li>
									<span class="lk-ad-single__spec-label"><?php echo esc_html( (string) $lk_ad_label ); ?></span>
									<span class="lk-ad-single__spec-value"><?php echo esc_html( (string) $lk_ad_value ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( get_the_content() ) : ?>
					<div class="lk-prose">
						<h2 class="lk-ad-single__subtitle"><?php esc_html_e( 'توضیحات آگهی', 'lamerd-khodro' ); ?></h2>
						<?php the_content(); ?>
					</div>
				<?php endif; ?>
			</div>

			<aside class="lk-ad-single__aside">
				<div class="lk-ad-price-card">
					<span class="lk-ad-price-card__label"><?php esc_html_e( 'قیمت فروش', 'lamerd-khodro' ); ?></span>
					<strong class="lk-ad-price-card__value<?php echo null === $lk_ad['price'] ? ' is-na' : ''; ?>">
						<?php echo esc_html( lk_money( null === $lk_ad['price'] ? null : (int) $lk_ad['price'], __( 'قیمت توافقی', 'lamerd-khodro' ) ) ); ?>
					</strong>

					<?php if ( $lk_ad_phone ) : ?>
						<a class="lk-btn lk-btn--primary lk-btn--block" href="tel:<?php echo esc_attr( $lk_ad_phone ); ?>">
							<?php esc_html_e( 'تماس با فروشنده', 'lamerd-khodro' ); ?>
						</a>
						<p class="lk-ad-price-card__phone"><?php echo esc_html( lk_fa_digits( (string) ( $lk_ad['phone'] ?? '' ) ) ); ?></p>
					<?php else : ?>
						<p class="lk-ad-price-card__na"><?php esc_html_e( 'شماره تماس برای این آگهی ثبت نشده است.', 'lamerd-khodro' ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $lk_ad['seller'] ) ) : ?>
						<p class="lk-ad-price-card__seller">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: seller name */
									__( 'آگهی‌دهنده: %s', 'lamerd-khodro' ),
									(string) $lk_ad['seller']
								)
							);
							?>
						</p>
					<?php endif; ?>
				</div>

				<p class="lk-ad-single__note">
					<?php esc_html_e( 'لامرد خودرو فقط نمایش‌دهنده این آگهی است و در معامله، قیمت‌گذاری یا پرداخت دخالتی ندارد. پیش از هر پرداخت، خودرو و مدارک را حضوری بررسی کنید.', 'lamerd-khodro' ); ?>
				</p>

				<a class="lk-btn lk-btn--ghost lk-btn--block" href="<?php echo esc_url( (string) ( get_post_type_archive_link( 'listing' ) ?: home_url( '/listings/' ) ) ); ?>">
					<?php esc_html_e( 'همه آگهی‌های فروش', 'lamerd-khodro' ); ?>
				</a>
			</aside>
		</article>
	</div>
	<?php
endwhile;

get_footer();
