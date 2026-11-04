<?php
/**
 * صفحه ۴۰۴.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="lk-container">
	<?php lk_breadcrumbs(); ?>

	<div class="lk-empty" style="padding-block:clamp(32px,8vw,72px)">
		<p style="font-size:3rem;font-weight:800;color:var(--lk-brand);margin:0 0 8px">۴۰۴</p>
		<h1 style="margin-block-end:8px"><?php esc_html_e( 'این صفحه پیدا نشد', 'lamerd-khodro' ); ?></h1>
		<p class="lk-muted" style="max-inline-size:52ch;margin-inline:auto">
			<?php esc_html_e( 'ممکن است نشانی تغییر کرده یا صفحه حذف شده باشد. از جستجو یا فهرست قیمت خودروها استفاده کنید.', 'lamerd-khodro' ); ?>
		</p>

		<div style="max-inline-size:520px;margin:18px auto">
			<?php get_search_form(); ?>
		</div>

		<p style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
			<a class="lk-btn lk-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'صفحه اصلی', 'lamerd-khodro' ); ?></a>
			<a class="lk-btn lk-btn--ghost" href="<?php echo esc_url( get_post_type_archive_link( 'vehicles' ) ?: home_url( '/vehicles/' ) ); ?>"><?php esc_html_e( 'قیمت خودروها', 'lamerd-khodro' ); ?></a>
		</p>
	</div>
</div>

<?php
get_footer();
