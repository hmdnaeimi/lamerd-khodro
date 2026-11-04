<?php
/**
 * فرم جستجو.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

$lk_id = 'lk-search-' . wp_unique_id();
?>
<form role="search" method="get" class="lk-hero-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" style="border:1px solid var(--lk-line)">
	<label class="screen-reader-text" for="<?php echo esc_attr( $lk_id ); ?>"><?php esc_html_e( 'جستجو', 'lamerd-khodro' ); ?></label>
	<input
		type="search"
		id="<?php echo esc_attr( $lk_id ); ?>"
		name="s"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="<?php esc_attr_e( 'نام خودرو یا برند…', 'lamerd-khodro' ); ?>"
	/>
	<button type="submit" class="lk-btn lk-btn--primary"><?php esc_html_e( 'جستجو', 'lamerd-khodro' ); ?></button>
</form>
