<?php
/**
 * Plugin Name:       Lamerd Khodro Core
 * Plugin URI:        https://lamerdkhodro.ir
 * Description:       هسته اطلاعات خودرو لامرد خودرو — بانک اطلاعات خودروها، برندها، مشخصات فنی، جستجو و صفحات اختصاصی.
 * Version:           1.0.3
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Lamerd Khodro
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lamerd-khodro
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'LKC_VERSION', '1.0.3' );
define( 'LKC_FILE', __FILE__ );
define( 'LKC_PATH', plugin_dir_path( __FILE__ ) );
define( 'LKC_URL', plugin_dir_url( __FILE__ ) );

require_once LKC_PATH . 'includes/helpers.php';
require_once LKC_PATH . 'includes/class-services.php';
require_once LKC_PATH . 'includes/class-plugin.php';
require_once LKC_PATH . 'includes/class-post-types.php';
require_once LKC_PATH . 'includes/class-listings.php';
require_once LKC_PATH . 'includes/class-taxonomies.php';
require_once LKC_PATH . 'includes/class-meta-boxes.php';
require_once LKC_PATH . 'includes/class-homepage-settings.php';
require_once LKC_PATH . 'includes/class-search.php';
require_once LKC_PATH . 'includes/class-rest.php';
require_once LKC_PATH . 'includes/class-activation.php';

/**
 * نمونه اصلی افزونه هسته.
 *
 * قرارداد سرویس بین افزونه‌ها: قالب و Price Engine فقط از این تابع استفاده می‌کنند.
 */
function lamerd_khodro_core(): LKC_Plugin {
	return LKC_Plugin::instance();
}

register_activation_hook( __FILE__, array( 'LKC_Activation', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LKC_Activation', 'deactivate' ) );

add_action( 'plugins_loaded', 'lamerd_khodro_core_bootstrap', 5 );
/**
 * بارگذاری افزونه پس از آماده شدن هسته وردپرس.
 */
function lamerd_khodro_core_bootstrap(): void {
	load_plugin_textdomain( 'lamerd-khodro', false, dirname( plugin_basename( LKC_FILE ) ) . '/languages' );
	lamerd_khodro_core()->init();
}
