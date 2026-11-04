<?php
/**
 * نتایج جستجو.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lk_core    = function_exists( 'lamerd_khodro_core' );
$lk_service = $lk_core ? lamerd_khodro_core()->vehicles() : null;
?>

<div class="lk-container">
	<?php lk_breadcrumbs(); ?>

	<header class="lk-section__head">
		<h1 class="lk-section__title">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: query */
					__( 'نتایج جستجو برای «%s»', 'lamerd-khodro' ),
					get_search_query()
				)
			);
			?>
		</h1>
		<span class="lk-muted" style="font-size:.8125rem">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: count */
					__( '%s نتیجه', 'lamerd-khodro' ),
					lk_fa_digits( number_format_i18n( (int) $GLOBALS['wp_query']->found_posts ) )
				)
			);
			?>
		</span>
	</header>

	<div style="margin-block-end:20px;max-inline-size:560px">
		<?php get_search_form(); ?>
	</div>

	<?php if ( have_posts() ) : ?>
		<?php if ( $lk_service ) : ?>
			<div class="lk-cards" style="margin-block-end:24px">
				<?php
				$lk_vehicle_posts = array();
				while ( have_posts() ) :
					the_post();
					if ( 'vehicles' === get_post_type() ) {
						lk_load_template_part( 'template-parts/card-vehicle', $lk_service->format( get_post() ) );
						$lk_vehicle_posts[] = get_the_ID();
					}
				endwhile;

				// بازگرداندن کوئری برای فهرست مطالب.
				rewind_posts();
				?>
			</div>
		<?php endif; ?>

		<div class="lk-news">
			<?php
			$lk_shown = 0;
			while ( have_posts() ) :
				the_post();
				if ( 'vehicles' === get_post_type() ) {
					continue;
				}
				$lk_shown++;
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
						<h2 class="lk-news-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p class="lk-news-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
						<span class="lk-news-card__date"><?php echo esc_html( lk_posted_on() ); ?></span>
					</div>
				</article>
				<?php
			endwhile;
			?>

			<?php if ( 0 === $lk_shown ) : ?>
				<p class="lk-muted"><?php esc_html_e( 'مطلب دیگری جز خودروها با این عبارت پیدا نشد.', 'lamerd-khodro' ); ?></p>
			<?php endif; ?>
		</div>

		<?php lk_pagination(); ?>
	<?php else : ?>
		<div class="lk-empty">
			<p><?php esc_html_e( 'نتیجه‌ای برای این عبارت یافت نشد.', 'lamerd-khodro' ); ?></p>
			<p class="lk-muted"><?php esc_html_e( 'می‌توانید نام خودرو، برند یا بخشی از آن را جستجو کنید.', 'lamerd-khodro' ); ?></p>
			<p>
				<a class="lk-btn lk-btn--primary" href="<?php echo esc_url( get_post_type_archive_link( 'vehicles' ) ?: home_url( '/vehicles/' ) ); ?>">
					<?php esc_html_e( 'مشاهده فهرست قیمت خودروها', 'lamerd-khodro' ); ?>
				</a>
			</p>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
