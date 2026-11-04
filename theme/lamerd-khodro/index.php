<?php
/**
 * قالب پیش‌فرض فهرست نوشته‌ها.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="lk-container">
	<?php lk_breadcrumbs(); ?>

	<header class="lk-article__header">
		<h1>
			<?php
			if ( is_home() && ! is_front_page() ) {
				echo esc_html( single_post_title( '', false ) );
			} else {
				esc_html_e( 'آخرین مطالب', 'lamerd-khodro' );
			}
			?>
		</h1>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="lk-news">
			<?php
			while ( have_posts() ) :
				the_post();
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
		</div>

		<?php lk_pagination(); ?>
	<?php else : ?>
		<div class="lk-empty">
			<p><?php esc_html_e( 'مطلبی برای نمایش وجود ندارد.', 'lamerd-khodro' ); ?></p>
			<p><a class="lk-btn lk-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'بازگشت به صفحه اصلی', 'lamerd-khodro' ); ?></a></p>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
