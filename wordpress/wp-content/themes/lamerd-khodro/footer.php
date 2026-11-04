<?php
/**
 * پابرگ قالب.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

$lk_about     = '';
$lk_phone     = '';
$lk_email     = '';
$lk_address   = '';
$lk_telegram  = '';
$lk_instagram = '';
$lk_aparat    = '';

if ( class_exists( 'LKC_Homepage_Settings' ) ) {
	$lk_about     = (string) LKC_Homepage_Settings::get( 'footer_about' );
	$lk_phone     = (string) LKC_Homepage_Settings::get( 'contact_phone' );
	$lk_email     = (string) LKC_Homepage_Settings::get( 'contact_email' );
	$lk_address   = (string) LKC_Homepage_Settings::get( 'contact_address' );
	$lk_telegram  = (string) LKC_Homepage_Settings::get( 'social_telegram' );
	$lk_instagram = (string) LKC_Homepage_Settings::get( 'social_instagram' );
	$lk_aparat    = (string) LKC_Homepage_Settings::get( 'social_eita' );
}

$lk_categories = get_categories(
	array(
		'orderby'    => 'count',
		'order'      => 'DESC',
		'number'     => 6,
		'hide_empty' => true,
	)
);
?>
</main>

<footer class="lk-footer">
	<div class="lk-container lk-footer__grid">
		<div class="lk-footer__col lk-footer__col--about">
			<a class="lk-logo lk-logo--footer" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<span class="lk-logo__mark" aria-hidden="true">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none">
						<path d="M3 13.5 5.2 8a3 3 0 0 1 2.8-2h8a3 3 0 0 1 2.8 2L21 13.5V18a1 1 0 0 1-1 1h-1.2a1 1 0 0 1-1-1v-.6H6.2V18a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-4.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
					</svg>
				</span>
				<span class="lk-logo__title"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			</a>

			<p class="lk-footer__text">
				<?php
				echo esc_html(
					$lk_about
						?: __( 'لامرد خودرو؛ مرجع تخصصی قیمت روز، مشخصات فنی، مقایسه و اخبار بازار خودرو در ایران.', 'lamerd-khodro' )
				);
				?>
			</p>

			<?php if ( $lk_telegram || $lk_instagram || $lk_aparat ) : ?>
				<div class="lk-social">
					<?php if ( $lk_telegram ) : ?>
						<a href="<?php echo esc_url( $lk_telegram ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'تلگرام', 'lamerd-khodro' ); ?>">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.5 4.2 18.7 19c-.2 1-.8 1.2-1.6.8l-4.4-3.2-2.1 2c-.2.2-.4.4-.9.4l.3-4.4 8-7.2c.3-.3 0-.5-.5-.2L7.6 13.1 3.3 11.7c-.9-.3-.9-.9.2-1.3L20 3.5c.8-.3 1.5.2 1.5.7Z"/></svg>
						</a>
					<?php endif; ?>
					<?php if ( $lk_instagram ) : ?>
						<a href="<?php echo esc_url( $lk_instagram ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'اینستاگرام', 'lamerd-khodro' ); ?>">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.7"/><circle cx="17" cy="7" r="1.2" fill="currentColor"/></svg>
						</a>
					<?php endif; ?>
					<?php if ( $lk_aparat ) : ?>
						<a href="<?php echo esc_url( $lk_aparat ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'آپارات', 'lamerd-khodro' ); ?>">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.7"/><path d="M10.4 8.9v6.2l5.1-3.1-5.1-3.1Z" fill="currentColor"/></svg>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="lk-footer__col">
			<h2 class="lk-footer__title"><?php esc_html_e( 'دسترسی سریع', 'lamerd-khodro' ); ?></h2>
			<?php
			if ( has_nav_menu( 'quick' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'quick',
						'container'      => false,
						'menu_class'     => 'lk-footer__list',
						'depth'          => 1,
					)
				);
			} else {
				?>
				<ul class="lk-footer__list">
					<li><a href="<?php echo esc_url( get_post_type_archive_link( 'vehicles' ) ?: home_url( '/vehicles/' ) ); ?>"><?php esc_html_e( 'قیمت روز خودرو', 'lamerd-khodro' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/brand/' ) ); ?>"><?php esc_html_e( 'برندهای خودرو', 'lamerd-khodro' ); ?></a></li>
					<li><a href="<?php echo esc_url( lk_compare_url() ); ?>"><?php esc_html_e( 'مقایسه خودرو', 'lamerd-khodro' ); ?></a></li>
					<li><a href="<?php echo esc_url( (string) ( get_post_type_archive_link( 'listing' ) ?: home_url( '/listings/' ) ) ); ?>"><?php esc_html_e( 'آگهی‌های فروش', 'lamerd-khodro' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/?s=' ) ); ?>"><?php esc_html_e( 'جستجوی خودرو', 'lamerd-khodro' ); ?></a></li>
				</ul>
				<?php
			}
			?>
		</div>

		<div class="lk-footer__col">
			<h2 class="lk-footer__title"><?php esc_html_e( 'دسته‌بندی اخبار', 'lamerd-khodro' ); ?></h2>
			<ul class="lk-footer__list">
				<?php if ( $lk_categories ) : ?>
					<?php foreach ( $lk_categories as $lk_category ) : ?>
						<li>
							<a href="<?php echo esc_url( (string) get_category_link( $lk_category ) ); ?>">
								<?php echo esc_html( $lk_category->name ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				<?php else : ?>
					<li><span class="lk-muted"><?php esc_html_e( 'به‌زودی', 'lamerd-khodro' ); ?></span></li>
				<?php endif; ?>
			</ul>
		</div>

		<div class="lk-footer__col">
			<h2 class="lk-footer__title"><?php esc_html_e( 'ارتباط با ما', 'lamerd-khodro' ); ?></h2>
			<ul class="lk-footer__list lk-footer__list--contact">
				<?php if ( $lk_phone ) : ?>
					<li>
						<span class="lk-footer__label"><?php esc_html_e( 'تلفن:', 'lamerd-khodro' ); ?></span>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $lk_phone ) ); ?>" dir="ltr"><?php echo esc_html( $lk_phone ); ?></a>
					</li>
				<?php endif; ?>
				<?php if ( $lk_email ) : ?>
					<li>
						<span class="lk-footer__label"><?php esc_html_e( 'ایمیل:', 'lamerd-khodro' ); ?></span>
						<a href="mailto:<?php echo esc_attr( $lk_email ); ?>" dir="ltr"><?php echo esc_html( $lk_email ); ?></a>
					</li>
				<?php endif; ?>
				<?php if ( $lk_address ) : ?>
					<li>
						<span class="lk-footer__label"><?php esc_html_e( 'نشانی:', 'lamerd-khodro' ); ?></span>
						<span><?php echo esc_html( $lk_address ); ?></span>
					</li>
				<?php endif; ?>
				<?php if ( ! $lk_phone && ! $lk_email && ! $lk_address ) : ?>
					<li class="lk-muted"><?php esc_html_e( 'اطلاعات تماس را از تنظیمات «لامرد خودرو» وارد کنید.', 'lamerd-khodro' ); ?></li>
				<?php endif; ?>
			</ul>
		</div>
	</div>

	<div class="lk-container lk-footer__bottom">
		<p class="lk-footer__copy">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: site name */
					__( '© %1$s — تمامی حقوق برای %2$s محفوظ است.', 'lamerd-khodro' ),
					lk_fa_digits( (string) (int) gmdate( 'Y' ) ),
					get_bloginfo( 'name' )
				)
			);
			?>
		</p>
		<p class="lk-footer__source">
			<?php esc_html_e( 'قیمت‌ها با ذکر منبع از کافه خودرو دریافت می‌شوند.', 'lamerd-khodro' ); ?>
			<a href="https://cofekhodro.ir" target="_blank" rel="noopener nofollow">cofekhodro.ir</a>
		</p>
	</div>
</footer>

<button class="lk-to-top" type="button" data-lk-to-top aria-label="<?php esc_attr_e( 'بازگشت به بالا', 'lamerd-khodro' ); ?>">
	<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
		<path d="M12 19V6M6 12l6-6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
	</svg>
</button>

<?php wp_footer(); ?>
</body>
</html>
