<?php
/**
 * راه‌اندازی قالب لامرد خودرو.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

define( 'LK_THEME_VERSION', '1.0.3' );
define( 'LK_THEME_DIR', get_template_directory() );
define( 'LK_THEME_URI', get_template_directory_uri() );

/*
 * بارگذاری زودهنگام دامنه ترجمه.
 * functions.php قالب پیش از `plugins_loaded` اجرا می‌شود؛ اگر تا `widgets_init`
 * بارگذاری نشود، ترجمه‌جویی تنبل وردپرس تلاش شبکه‌ای می‌کند و صفحه را کند می‌کند.
 */
load_theme_textdomain( 'lamerd-khodro', LK_THEME_DIR . '/languages' );

require_once LK_THEME_DIR . '/inc/template-tags.php';
require_once LK_THEME_DIR . '/inc/chart.php';

/**
 * پشتیبانی‌های قالب.
 */
function lk_theme_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 48,
			'width'       => 180,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_image_size( 'lk-card', 640, 400, true );
	add_image_size( 'lk-hero', 1200, 700, true );

	register_nav_menus(
		array(
			'primary' => __( 'منوی اصلی', 'lamerd-khodro' ),
			'footer'  => __( 'منوی فوتر', 'lamerd-khodro' ),
			'quick'   => __( 'دسترسی سریع', 'lamerd-khodro' ),
		)
	);

	// RTL واقعی مستقل از نصب بسته زبان.
	$GLOBALS['text_direction'] = 'rtl';
}
add_action( 'after_setup_theme', 'lk_theme_setup' );

/**
 * اطمینان از lang="fa-IR" و dir="rtl" روی تگ html.
 *
 * @param string $output خروجی language_attributes.
 * @return string
 */
function lk_language_attributes( string $output ): string {
	$output = preg_replace( '/lang="[^"]*"/', 'lang="fa-IR"', $output );
	if ( false === strpos( $output, 'dir=' ) ) {
		$output .= ' dir="rtl"';
	} else {
		$output = preg_replace( '/dir="[^"]*"/', 'dir="rtl"', $output );
	}
	return trim( $output );
}
add_filter( 'language_attributes', 'lk_language_attributes' );

/**
 * بارگذاری دارایی‌ها.
 */
function lk_enqueue_assets(): void {
	$style_path = LK_THEME_DIR . '/assets/css/main.css';
	$script_path = LK_THEME_DIR . '/assets/js/main.js';

	wp_enqueue_style(
		'lk-main',
		LK_THEME_URI . '/assets/css/main.css',
		array(),
		file_exists( $style_path ) ? (string) filemtime( $style_path ) : LK_THEME_VERSION
	);

	wp_enqueue_script(
		'lk-main',
		LK_THEME_URI . '/assets/js/main.js',
		array(),
		file_exists( $script_path ) ? (string) filemtime( $script_path ) : LK_THEME_VERSION,
		true
	);

	wp_localize_script(
		'lk-main',
		'lkData',
		array(
			'homeUrl' => esc_url_raw( home_url( '/' ) ),
			'vehiclesUrl' => esc_url_raw( get_post_type_archive_link( 'vehicles' ) ?: home_url( '/vehicles/' ) ),
			'countdown' => array(
				'expired' => __( 'پایان یافته', 'lamerd-khodro' ),
				'day'     => __( 'روز', 'lamerd-khodro' ),
			),
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'lk_enqueue_assets' );

/**
 * کلاس‌های بدنه.
 *
 * @param string[] $classes کلاس‌ها.
 * @return string[]
 */
function lk_body_classes( array $classes ): array {
	$classes[] = 'lk-body';

	if ( ! is_active_sidebar( 'sidebar-1' ) ) {
		$classes[] = 'lk-no-sidebar';
	}
	if ( is_singular( 'vehicles' ) ) {
		$classes[] = 'lk-single-vehicle';
	}

	return $classes;
}
add_filter( 'body_class', 'lk_body_classes' );

/**
 * طول خلاصه.
 *
 * @return int
 */
function lk_excerpt_length(): int {
	return 26;
}
add_filter( 'excerpt_length', 'lk_excerpt_length', 20 );

/**
 * ادامه خلاصه.
 *
 * @return string
 */
function lk_excerpt_more(): string {
	return '…';
}
add_filter( 'excerpt_more', 'lk_excerpt_more' );

/**
 * افزودن فهرست خودروها به آرشیو جستجو (پیش‌فرض همه انواع).
 *
 * @param WP_Query $query کوئری.
 */
function lk_search_post_types( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}
	$query->set( 'post_type', array( 'post', 'page', 'vehicles' ) );
}
add_action( 'pre_get_posts', 'lk_search_post_types' );

/**
 * حذف پیشوند «دسته:» و «برچسب:» از عنوان آرشیو.
 *
 * @param string $title عنوان.
 * @return string
 */
function lk_archive_title( string $title ): string {
	if ( is_category() || is_tag() || is_tax() ) {
		$title = single_term_title( '', false );
	} elseif ( is_post_type_archive() ) {
		$title = post_type_archive_title( '', false );
	} elseif ( is_author() ) {
		$title = get_the_author();
	}
	return $title;
}
add_filter( 'get_the_archive_title', 'lk_archive_title' );

/**
 * منوی پیش‌فرض وقتی منویی تخصیص داده نشده است.
 */
function lk_primary_menu_fallback(): void {
	$items = array(
		home_url( '/' )                        => __( 'صفحه اصلی', 'lamerd-khodro' ),
		get_post_type_archive_link( 'vehicles' ) => __( 'قیمت خودرو', 'lamerd-khodro' ),
	);

	echo '<ul class="lk-nav__list">';
	foreach ( $items as $url => $label ) {
		if ( ! $url ) {
			continue;
		}
		printf(
			'<li class="lk-nav__item"><a href="%s">%s</a></li>',
			esc_url( $url ),
			esc_html( $label )
		);
	}
	echo '<li class="lk-nav__item"><a href="' . esc_url( home_url( '/اخبار/' ) ) . '">' . esc_html__( 'اخبار', 'lamerd-khodro' ) . '</a></li>';
	echo '<li class="lk-nav__item"><a href="' . esc_url( home_url( '/مقایسه/' ) ) . '">' . esc_html__( 'مقایسه', 'lamerd-khodro' ) . '</a></li>';
	echo '<li class="lk-nav__item"><a href="' . esc_url( (string) ( get_post_type_archive_link( 'listing' ) ?: home_url( '/listings/' ) ) ) . '">' . esc_html__( 'آگهی‌های فروش', 'lamerd-khodro' ) . '</a></li>';
	echo '</ul>';
}

/**
 * ثبت ناحیه ابزارک (در صورت نیاز).
 */
function lk_widgets_init(): void {
	register_sidebar(
		array(
			'name'          => __( 'ستون کنار', 'lamerd-khodro' ),
			'id'            => 'sidebar-1',
			'description'   => __( 'ابزارک‌های ستون کنار صفحات', 'lamerd-khodro' ),
			'before_widget' => '<section class="lk-widget">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="lk-widget__title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'lk_widgets_init' );
