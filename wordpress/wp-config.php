<?php
/**
 * تنظیمات نصب آزمایشی محلی لامرد خودرو.
 * این پوشه فقط برای تست محلی است و بخشی از محصول نیست.
 */

define( 'DB_NAME', 'lamerd_khodro_test' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', '127.0.0.1' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

define( 'AUTH_KEY',         'lk-test-auth-key-local-only' );
define( 'SECURE_AUTH_KEY',  'lk-test-secure-auth-key-local-only' );
define( 'LOGGED_IN_KEY',    'lk-test-logged-in-key-local-only' );
define( 'NONCE_KEY',        'lk-test-nonce-key-local-only' );
define( 'AUTH_SALT',        'lk-test-auth-salt-local-only' );
define( 'SECURE_AUTH_SALT', 'lk-test-secure-auth-salt-local-only' );
define( 'LOGGED_IN_SALT',   'lk-test-logged-in-salt-local-only' );
define( 'NONCE_SALT',       'lk-test-nonce-salt-local-only' );

$table_prefix = 'wp_';

define( 'WP_HOME', 'http://127.0.0.1:8095' );
define( 'WP_SITEURL', 'http://127.0.0.1:8095' );
define( 'WP_ENVIRONMENT_TYPE', 'local' );

define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', false );

define( 'DISALLOW_FILE_EDIT', true );
define( 'AUTOMATIC_UPDATER_DISABLED', true );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
