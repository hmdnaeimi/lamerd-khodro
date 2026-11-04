<?php
/**
 * آرشیو خودروها (فهرست قیمت).
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lk_core    = function_exists( 'lamerd_khodro_core' );
$lk_service = $lk_core ? lamerd_khodro_core()->vehicles() : null;
$lk_brands  = $lk_core ? lamerd_khodro_core()->brands()->get_all( array( 'limit' => 40, 'orderby' => 'name', 'order' => 'ASC' ) ) : array();

$lk_current_brand = isset( $_GET['lkc_brand'] ) ? sanitize_title( wp_unslash( $_GET['lkc_brand'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$lk_current_year  = isset( $_GET['lkc_year'] ) ? absint( wp_unslash( $_GET['lkc_year'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$lk_current_body  = isset( $_GET['lkc_body'] ) ? sanitize_key( wp_unslash( $_GET['lkc_body'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$lk_current_order = isset( $_GET['lkc_order'] ) ? sanitize_key( wp_unslash( $_GET['lkc_order'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$lk_bodies = array(
	'sedan'     => __( 'سدان', 'lamerd-khodro' ),
	'hatch'     => __( 'هاچ‌بک', 'lamerd-khodro' ),
	'crossover' => __( 'کراس‌اور', 'lamerd-khodro' ),
	'suv'       => __( 'شاسی‌بلند', 'lamerd-khodro' ),
	'pickup'    => __( 'وانت', 'lamerd-khodro' ),
	'minion'    => __( 'مینی‌ون', 'lamerd-khodro' ),
);
?>

<div class="lk-container">
	<?php lk_breadcrumbs(); ?>

	<div class="lk-section__head">
		<h1 class="lk-section__title"><?php esc_html_e( 'قیمت روز خودرو', 'lamerd-khodro' ); ?></h1>
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
	</div>

	<form method="get" class="lk-filters" action="<?php echo esc_url( get_post_type_archive_link( 'vehicles' ) ?: home_url( '/vehicles/' ) ); ?>" style="display:flex;flex-wrap:wrap;gap:8px;margin-block-end:18px">
		<label class="screen-reader-text" for="lk-filter-brand"><?php esc_html_e( 'برند', 'lamerd-khodro' ); ?></label>
		<select id="lk-filter-brand" name="lkc_brand" style="padding:9px 12px;border-radius:10px;border:1px solid var(--lk-line);background:#fff">
			<option value=""><?php esc_html_e( 'همه برندها', 'lamerd-khodro' ); ?></option>
			<?php foreach ( $lk_brands as $lk_brand ) : ?>
				<option value="<?php echo esc_attr( (string) $lk_brand['slug'] ); ?>" <?php selected( $lk_current_brand, (string) $lk_brand['slug'] ); ?>>
					<?php echo esc_html( (string) $lk_brand['name'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label class="screen-reader-text" for="lk-filter-body"><?php esc_html_e( 'نوع بدنه', 'lamerd-khodro' ); ?></label>
		<select id="lk-filter-body" name="lkc_body" style="padding:9px 12px;border-radius:10px;border:1px solid var(--lk-line);background:#fff">
			<option value=""><?php esc_html_e( 'همه بدنه‌ها', 'lamerd-khodro' ); ?></option>
			<?php foreach ( $lk_bodies as $lk_value => $lk_label ) : ?>
				<option value="<?php echo esc_attr( $lk_value ); ?>" <?php selected( $lk_current_body, $lk_value ); ?>><?php echo esc_html( $lk_label ); ?></option>
			<?php endforeach; ?>
		</select>

		<label class="screen-reader-text" for="lk-filter-year"><?php esc_html_e( 'سال تولید', 'lamerd-khodro' ); ?></label>
		<input
			id="lk-filter-year"
			type="number"
			name="lkc_year"
			min="1380"
			max="1420"
			placeholder="<?php esc_attr_e( 'سال (مثلاً ۱۴۰۳)', 'lamerd-khodro' ); ?>"
			value="<?php echo esc_attr( $lk_current_year ? (string) $lk_current_year : '' ); ?>"
			style="padding:9px 12px;border-radius:10px;border:1px solid var(--lk-line);background:#fff;inline-size:150px"
		/>

		<label class="screen-reader-text" for="lk-filter-order"><?php esc_html_e( 'ترتیب', 'lamerd-khodro' ); ?></label>
		<select id="lk-filter-order" name="lkc_order" style="padding:9px 12px;border-radius:10px;border:1px solid var(--lk-line);background:#fff">
			<option value=""><?php esc_html_e( 'جدیدترین', 'lamerd-khodro' ); ?></option>
			<option value="popular" <?php selected( $lk_current_order, 'popular' ); ?>><?php esc_html_e( 'محبوب‌ترین', 'lamerd-khodro' ); ?></option>
			<option value="title" <?php selected( $lk_current_order, 'title' ); ?>><?php esc_html_e( 'الفبایی', 'lamerd-khodro' ); ?></option>
		</select>

		<button type="submit" class="lk-btn lk-btn--primary"><?php esc_html_e( 'اعمال فیلتر', 'lamerd-khodro' ); ?></button>
	</form>

	<?php if ( have_posts() ) : ?>
		<div class="lk-cards">
			<?php
			while ( have_posts() ) :
				the_post();

				if ( $lk_service ) {
					lk_load_template_part( 'template-parts/card-vehicle', $lk_service->format( get_post() ) );
				}
			endwhile;
			?>
		</div>

		<?php lk_pagination(); ?>

		<?php lk_attribution(); ?>
	<?php else : ?>
		<div class="lk-empty">
			<p><?php esc_html_e( 'خودرویی با این فیلترها یافت نشد.', 'lamerd-khodro' ); ?></p>
			<p><a class="lk-btn lk-btn--ghost" href="<?php echo esc_url( get_post_type_archive_link( 'vehicles' ) ?: home_url( '/vehicles/' ) ); ?>"><?php esc_html_e( 'حذف فیلترها', 'lamerd-khodro' ); ?></a></p>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
