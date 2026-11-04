<?php
/**
 * قالب برگه.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="lk-container">
	<?php lk_breadcrumbs(); ?>

	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'lk-panel' ); ?>>
			<header class="lk-article__header">
				<h1><?php the_title(); ?></h1>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="lk-article__thumb">
					<?php the_post_thumbnail( 'lk-hero' ); ?>
				</div>
			<?php endif; ?>

			<div class="lk-prose">
				<?php the_content(); ?>
			</div>

			<?php
			wp_link_pages(
				array(
					'before' => '<nav class="lk-pagination">',
					'after'  => '</nav>',
				)
			);
			?>
		</article>

		<?php
		if ( comments_open() || get_comments_number() ) {
			echo '<div class="lk-panel" style="margin-top:16px">';
			comments_template();
			echo '</div>';
		}
	endwhile;
	?>
</div>

<?php
get_footer();
