<?php
/**
 * سربرگ قالب.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="theme-color" content="#0f3d6b" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="lk-skip-link" href="#lk-main"><?php esc_html_e( 'رفتن به محتوای اصلی', 'lamerd-khodro' ); ?></a>

<header class="lk-header" data-lk-header>
	<div class="lk-container lk-header__inner">
		<div class="lk-header__brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="lk-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<span class="lk-logo__mark" aria-hidden="true">
						<svg width="26" height="26" viewBox="0 0 24 24" fill="none">
							<path d="M3 13.5 5.2 8a3 3 0 0 1 2.8-2h8a3 3 0 0 1 2.8 2L21 13.5V18a1 1 0 0 1-1 1h-1.2a1 1 0 0 1-1-1v-.6H6.2V18a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-4.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
							<circle cx="7.4" cy="14.6" r="1.2" fill="currentColor"/>
							<circle cx="16.6" cy="14.6" r="1.2" fill="currentColor"/>
						</svg>
					</span>
					<span class="lk-logo__text">
						<span class="lk-logo__title"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
						<span class="lk-logo__tagline"><?php echo esc_html( get_bloginfo( 'description' ) ?: __( 'مرجع قیمت و اطلاعات خودرو', 'lamerd-khodro' ) ); ?></span>
					</span>
				</a>
			<?php endif; ?>
		</div>

		<button class="lk-nav-toggle" type="button" aria-expanded="false" aria-controls="lk-primary-nav" data-lk-nav-toggle>
			<span class="lk-nav-toggle__bars" aria-hidden="true"><i></i><i></i><i></i></span>
			<span class="screen-reader-text"><?php esc_html_e( 'منو', 'lamerd-khodro' ); ?></span>
		</button>

		<nav class="lk-nav" id="lk-primary-nav" aria-label="<?php esc_attr_e( 'منوی اصلی', 'lamerd-khodro' ); ?>" data-lk-nav>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'lk-nav__list',
					'depth'          => 2,
					'fallback_cb'    => 'lk_primary_menu_fallback',
				)
			);
			?>
		</nav>

		<div class="lk-header__actions">
			<?php if ( function_exists( 'lamerd_khodro_core' ) ) : ?>
				<a class="lk-btn lk-btn--ghost lk-header__search-link" href="<?php echo esc_url( home_url( '/?s=' ) ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
						<circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
						<path d="m16.5 16.5 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
					</svg>
					<span><?php esc_html_e( 'جستجو', 'lamerd-khodro' ); ?></span>
				</a>
			<?php endif; ?>
			<a class="lk-btn lk-btn--primary" href="<?php echo esc_url( get_post_type_archive_link( 'vehicles' ) ?: home_url( '/vehicles/' ) ); ?>">
				<?php esc_html_e( 'قیمت روز خودرو', 'lamerd-khodro' ); ?>
			</a>
		</div>
	</div>
</header>

<main id="lk-main" class="lk-main">
