<?php
/**
 * قالب نوشته (خبر/مقاله).
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$lk_cats = get_the_category();
	?>

	<div class="lk-container">
		<?php lk_breadcrumbs(); ?>

		<article <?php post_class( 'lk-panel' ); ?>>
			<header class="lk-article__header">
				<?php if ( $lk_cats ) : ?>
					<a class="lk-chip" href="<?php echo esc_url( (string) get_category_link( $lk_cats[0] ) ); ?>"><?php echo esc_html( $lk_cats[0]->name ); ?></a>
				<?php endif; ?>

				<h1 style="margin-top:10px"><?php the_title(); ?></h1>

				<div class="lk-article__meta">
					<span><?php echo esc_html( lk_posted_on() ); ?></span>
					<span aria-hidden="true">·</span>
					<span><?php echo esc_html( get_the_author() ); ?></span>
					<?php if ( get_comments_number() ) : ?>
						<span aria-hidden="true">·</span>
						<span><?php echo esc_html( lk_fa_digits( number_format_i18n( (int) get_comments_number() ) ) . ' ' . __( 'دیدگاه', 'lamerd-khodro' ) ); ?></span>
					<?php endif; ?>
				</div>
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

			<?php
			$lk_tags = get_the_tags();
			if ( $lk_tags ) :
				?>
				<div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:18px">
					<?php foreach ( $lk_tags as $lk_tag ) : ?>
						<a class="lk-chip lk-chip--accent" href="<?php echo esc_url( (string) get_tag_link( $lk_tag ) ); ?>"><?php echo esc_html( $lk_tag->name ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</article>

		<?php
		$lk_related = new WP_Query(
			array(
				'post_type'      => 'post',
				'posts_per_page' => 3,
				'post__not_in'   => array( get_the_ID() ),
				'category__in'   => $lk_cats ? wp_list_pluck( $lk_cats, 'term_id' ) : array(),
				'no_found_rows'  => true,
			)
		);

		if ( $lk_related->have_posts() ) :
			?>
			<section class="lk-section">
				<div class="lk-section__head">
					<h2 class="lk-section__title"><?php esc_html_e( 'مطالب مرتبط', 'lamerd-khodro' ); ?></h2>
				</div>
				<div class="lk-news">
					<?php
					while ( $lk_related->have_posts() ) :
						$lk_related->the_post();
						?>
						<article class="lk-news-card">
							<a class="lk-news-card__media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
								<?php if ( has_post_thumbnail() ) : ?>
									<?php the_post_thumbnail( 'lk-card', array( 'loading' => 'lazy' ) ); ?>
								<?php endif; ?>
							</a>
							<div class="lk-news-card__body">
								<h3 class="lk-news-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
								<span class="lk-news-card__date"><?php echo esc_html( lk_posted_on() ); ?></span>
							</div>
						</article>
						<?php
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</section>
			<?php
		endif;

		if ( comments_open() || get_comments_number() ) {
			echo '<div class="lk-panel">';
			comments_template();
			echo '</div>';
		}
endwhile;

get_footer();
