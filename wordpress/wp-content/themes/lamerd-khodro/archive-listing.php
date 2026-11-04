<?php
/**
 * آرشیو آگهی‌های فروش خودرو.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="lk-container">
	<?php lk_breadcrumbs(); ?>

	<div class="lk-section__head">
		<h1 class="lk-section__title"><?php esc_html_e( 'آگهی‌های فروش خودرو', 'lamerd-khodro' ); ?></h1>
		<span class="lk-muted" style="font-size:.8125rem">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: count */
					__( '%s آگهی', 'lamerd-khodro' ),
					lk_fa_digits( number_format_i18n( (int) $GLOBALS['wp_query']->found_posts ) )
				)
			);
			?>
		</span>
	</div>

	<?php if ( have_posts() ) : ?>
		<div class="lk-ads__grid">
			<?php
			while ( have_posts() ) :
				the_post();
				lk_load_template_part( 'template-parts/card-listing', lk_listing_data( get_the_ID() ) );
			endwhile;
			?>
		</div>

		<?php lk_pagination(); ?>
	<?php else : ?>
		<div class="lk-empty">
			<p><?php esc_html_e( 'هنوز آگهی فروشی ثبت نشده است.', 'lamerd-khodro' ); ?></p>
			<p><a class="lk-btn lk-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'بازگشت به صفحه اصلی', 'lamerd-khodro' ); ?></a></p>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
