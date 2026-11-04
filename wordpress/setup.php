<?php
/**
 * نصب و پیکربندی نصب آزمایشی محلی (فقط برای تست).
 * اجرا: php -d extension=mysqli setup.php
 */

define( 'WP_INSTALLING', true );
define( 'WP_USE_THEMES', false );

$_SERVER['HTTP_HOST']      = '127.0.0.1:8080';
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';

require_once __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/theme.php';

if ( ! is_blog_installed() ) {
	$result = wp_install(
		'لامرد خودرو (تست)',
		'admin',
		'admin@example.test',
		true,
		'',
		'Admin12345'
	);
	echo 'installed user_id=' . (int) $result['user_id'] . "\n";
} else {
	echo "already installed\n";
}

update_option( 'blog_public', 0 );
update_option( 'timezone_string', 'Asia/Tehran' );
update_option( 'date_format', 'Y/m/d' );
update_option( 'blogdescription', 'مرجع قیمت و اطلاعات خودرو' );
update_option( 'permalink_structure', '' );
update_option( 'posts_per_page', 9 );

// قالب.
switch_theme( 'lamerd-khodro' );
echo 'theme=' . get_option( 'stylesheet' ) . "\n";

// افزونه‌ها.
foreach ( array( 'lamerd-khodro-core/lamerd-khodro-core.php', 'lamerd-khodro-price-engine/lamerd-khodro-price-engine.php' ) as $plugin ) {
	$error = activate_plugin( $plugin );
	echo 'plugin ' . $plugin . ': ' . ( is_wp_error( $error ) ? 'ERROR ' . $error->get_error_message() : 'active' ) . "\n";
}

wp_clean_themes_cache();
echo 'active plugins: ' . implode( ', ', (array) get_option( 'active_plugins' ) ) . "\n";

// صفحه مقایسه.
$compare = get_page_by_path( 'مقایسه' );
if ( ! $compare ) {
	$page_id = wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_title'  => 'مقایسه خودرو',
			'post_name'   => 'مقایسه',
			'post_status' => 'publish',
			'post_content' => '',
		)
	);
	update_post_meta( $page_id, '_wp_page_template', 'page-compare.php' );
	echo 'compare page id=' . (int) $page_id . ' link=' . get_permalink( $page_id ) . "\n";
} else {
	echo 'compare page exists id=' . (int) $compare->ID . "\n";
}

// صفحه نمونه برای «قیمت روز خودرو» (آرشیو CPT به‌صورت خودکار کار می‌کند).
echo 'home=' . home_url( '/' ) . "\n";
echo 'vehicles archive=' . get_post_type_archive_link( 'vehicles' ) . "\n";
echo 'brand taxonomy exists=' . ( taxonomy_exists( 'brand' ) ? 'yes' : 'no' ) . "\n";
echo 'CPT exists=' . ( post_type_exists( 'vehicles' ) ? 'yes' : 'no' ) . "\n";
echo 'price engine active=' . ( function_exists( 'lamerd_khodro_price_engine' ) ? 'yes' : 'no' ) . "\n";
echo 'core active=' . ( function_exists( 'lamerd_khodro_core' ) ? 'yes' : 'no' ) . "\n";
