<?php
/**
 * آرشیو برند.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lk_term = get_queried_object();
$lk_core = function_exists( 'lamerd_khodro_core' );

$lk_logo   = ( $lk_term instanceof WP_Term ) ? (int) get_term_meta( $lk_term->term_id, '_lkc_brand_logo', true ) : 0;
$lk_name_en = ( $lk_term instanceof WP_Term ) ? (string) get_term_meta( $lk_term->term_id, '_lkc_brand_name_en', true ) : '';
$lk_country = ( $lk_term instanceof WP_Term ) ? (string) get_term_meta( $lk_term->term_id, '_lkc_brand_country', true ) : '';
$lk_website = ( $lk_term instanceof WP_Term ) ? (string) get_term_meta( $lk_term->term_id, '_lkc_brand_website', true ) : '';
?>

<div class="lk-container">
	<?php lk_breadcrumbs(); ?>

	<header class="lk-section__head" style="align-items:flex-start">
		<div style="display:flex;align-items:center;gap:14px">
			<?php if ( $lk_logo ) : ?>
				<span class="lk-brand__logo" style="inline-size:64px;block-size:64px">
					<?php echo wp_get_attachment_image( $lk_logo, 'thumbnail', false, array( 'alt' => (string) ( $lk_term->name ?? '' ) ) ); ?>
				</span>
			<?php endif; ?>

			<div>
				<h1 class="lk-section__title" style="margin-block-end:4px">
					<?php echo esc_html( $lk_term instanceof WP_Term ? $lk_term->name : __( 'برند', 'lamerd-khodro' ) ); ?>
				</h1>
				<div class="lk-muted" style="font-size:.8125rem;display:flex;flex-wrap:wrap;gap:10px">
					<?php if ( $lk_name_en ) : ?>
						<span dir="ltr"><?php echo esc_html( $lk_name_en ); ?></span>
					<?php endif; ?>
					<?php if ( $lk_country ) : ?>
						<span><?php echo esc_html( sprintf( __( 'کشور: %s', 'lamerd-khodro' ), $lk_country ) ); ?></span>
					<?php endif; ?>
					<?php if ( $lk_website ) : ?>
						<a href="<?php echo esc_url( $lk_website ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'وب‌سایت رسمی', 'lamerd-khodro' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<span class="lk-muted" style="font-size:.8125rem">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: count */
					__( '%s خودرو', 'lamerd-khodro' ),
					lk_fa_digits( number_format_i18n( (int) $GLOBALS['wp_query']->found_posts ) )
				)
			);
			?>
		</span>
	</header>

	<?php if ( $lk_term instanceof WP_Term && $lk_term->description ) : ?>
		<div class="lk-prose" style="margin-block-end:18px">
			<?php echo wp_kses_post( wpautop( $lk_term->description ) ); ?>
		</div>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<div class="lk-cards">
			<?php
			while ( have_posts() ) :
				the_post();
				if ( $lk_core ) {
					lk_load_template_part( 'template-parts/card-vehicle', lamerd_khodro_core()->vehicles()->format( get_post() ) );
				}
			endwhile;
			?>
		</div>

		<?php lk_pagination(); ?>

		<?php lk_attribution(); ?>
	<?php else : ?>
		<div class="lk-empty">
			<p><?php esc_html_e( 'خودرویی برای این برند ثبت نشده است.', 'lamerd-khodro' ); ?></p>
			<p>
				<a class="lk-btn lk-btn--primary" href="<?php echo esc_url( get_post_type_archive_link( 'vehicles' ) ?: home_url( '/vehicles/' ) ); ?>">
					<?php esc_html_e( 'مشاهده همه خودروها', 'lamerd-khodro' ); ?>
				</a>
			</p>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
