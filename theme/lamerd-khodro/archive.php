<?php
/**
 * قالب آرشیو عمومی (دسته، برچسب، نویسنده، تاریخ).
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="lk-container">
	<?php lk_breadcrumbs(); ?>

	<header class="lk-section__head">
		<h1 class="lk-section__title"><?php echo esc_html( get_the_archive_title() ); ?></h1>
		<span class="lk-muted" style="font-size:.8125rem">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: count */
					__( '%s مطلب', 'lamerd-khodro' ),
					lk_fa_digits( number_format_i18n( (int) $GLOBALS['wp_query']->found_posts ) )
				)
			);
			?>
		</span>
	</header>

	<?php
	if ( get_the_archive_description() ) {
		echo '<div class="lk-prose" style="margin-block-end:18px">' . wp_kses_post( get_the_archive_description() ) . '</div>';
	}
	?>

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
			<p><?php esc_html_e( 'مطلبی در این بخش ثبت نشده است.', 'lamerd-khodro' ); ?></p>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
