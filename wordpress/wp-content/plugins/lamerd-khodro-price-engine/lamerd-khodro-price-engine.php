<?php
/**
 * Plugin Name:       Lamerd Khodro Price Engine
 * Plugin URI:        https://lamerdkhodro.ir
 * Description:       موتور قیمت خودرو لامرد خودرو — دریافت قیمت از منبع، اعتبارسنجی، ذخیره دائمی، تاریخچه، نمودار و گزارش.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Lamerd Khodro
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lamerd-khodro
 * Domain Path:       /languages
 *
 * @package LamerdKhodroPriceEngine
 */

defined( 'ABSPATH' ) || exit;

define( 'LKP_VERSION', '1.0.0' );
define( 'LKP_FILE', __FILE__ );
define( 'LKP_PATH', plugin_dir_path( __FILE__ ) );
define( 'LKP_URL', plugin_dir_url( __FILE__ ) );

require_once LKP_PATH . 'includes/helpers.php';
require_once LKP_PATH . 'includes/class-installer.php';
require_once LKP_PATH . 'includes/class-provider.php';
require_once LKP_PATH . 'includes/class-cafekhodro-provider.php';
require_once LKP_PATH . 'includes/class-validator.php';
require_once LKP_PATH . 'includes/class-repositories.php';
require_once LKP_PATH . 'includes/class-sync-service.php';
require_once LKP_PATH . 'includes/class-scheduler.php';
require_once LKP_PATH . 'includes/class-reports.php';
require_once LKP_PATH . 'includes/class-export.php';
require_once LKP_PATH . 'includes/class-admin-pages.php';
require_once LKP_PATH . 'includes/class-admin.php';
require_once LKP_PATH . 'includes/class-plugin.php';

/**
 * دسترسی به نمونه موتور قیمت.
 *
 * قرارداد سرویس: قالب و افزونه هسته فقط از این تابع و متدهای `prices()` /
 * `sync()` / `vehicles()` استفاده می‌کنند.
 */
function lamerd_khodro_price_engine(): LKP_Plugin {
	return LKP_Plugin::instance();
}

register_activation_hook( __FILE__, array( 'LKP_Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LKP_Installer', 'deactivate' ) );

add_action( 'plugins_loaded', 'lkp_bootstrap', 6 );

/**
 * راه‌اندازی افزونه.
 */
function lkp_bootstrap(): void {
	load_plugin_textdomain( 'lamerd-khodro', false, dirname( plugin_basename( LKP_FILE ) ) . '/languages' );

	// نصب/به‌روزرسانی اسکیما در صورت تغییر نسخه.
	LKP_Installer::maybe_upgrade();

	lamerd_khodro_price_engine()->init();
}
