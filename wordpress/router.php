<?php
/**
 * روتر سرور داخلی PHP برای نصب آزمایشی وردپرس (فقط تست محلی).
 */

$path = parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH );
$path = $path ? rawurldecode( $path ) : '/';

$root = __DIR__;
$file = $root . $path;

// فایل‌های واقعی (استاتیک) مستقیم سرو می‌شوند.
if ( '/' !== $path && is_file( $file ) ) {
	return false;
}

// مسیرهای دایرکتوری با index.php.
if ( is_dir( $file ) && is_file( rtrim( $file, '/\\' ) . '/index.php' ) ) {
	require rtrim( $file, '/\\' ) . '/index.php';
	return true;
}

require $root . '/index.php';
