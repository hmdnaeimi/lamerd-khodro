<?php
/**
 * کارت آگهی فروش خودرو.
 *
 * @package LamerdKhodro
 *
 * @var array<string, mixed> $args داده آگهی (خروجی lk_listing_data).
 */

defined( 'ABSPATH' ) || exit;

$lk_ad = isset( $args ) && is_array( $args ) ? $args : array();

if ( empty( $lk_ad['id'] ) ) {
	return;
}

$lk_ad_featured  = ! empty( $lk_ad['featured'] );
$lk_ad_remaining = (int) ( $lk_ad['remaining'] ?? 0 );
$lk_ad_until     = $lk_ad_featured && $lk_ad_remaining > 0
	? (int) current_time( 'timestamp' ) + $lk_ad_remaining
	: 0;
$lk_ad_specs     = array();

if ( ! empty( $lk_ad['year'] ) ) {
	$lk_ad_specs[] = array(
		'label' => __( 'سال', 'lamerd-khodro' ),
		'value' => lk_fa_digits( (string) $lk_ad['year'] ),
	);
}

if ( ! empty( $lk_ad['mileage'] ) ) {
	$lk_ad_specs[] = array(
		'label' => __( 'کارکرد', 'lamerd-khodro' ),
		'value' => lk_fa_digits( number_format_i18n( (int) $lk_ad['mileage'] ) ) . ' ' . __( 'کیلومتر', 'lamerd-khodro' ),
	);
}

if ( ! empty( $lk_ad['city'] ) ) {
	$lk_ad_specs[] = array(
		'label' => __( 'شهر', 'lamerd-khodro' ),
		'value' => (string) $lk_ad['city'],
	);
}
?>
<article class="lk-ad<?php echo $lk_ad_featured ? ' lk-ad--featured' : ''; ?>">
	<a class="lk-ad__media" href="<?php echo esc_url( (string) $lk_ad['url'] ); ?>" aria-hidden="true" tabindex="-1">
		<?php if ( ! empty( $lk_ad['thumbnail'] ) ) : ?>
			<img src="<?php echo esc_url( (string) $lk_ad['thumbnail'] ); ?>" alt="<?php echo esc_attr( (string) $lk_ad['title'] ); ?>" loading="lazy" width="640" height="400" />
		<?php else : ?>
			<span class="lk-ad__placeholder" aria-hidden="true">
				<svg width="46" height="46" viewBox="0 0 24 24" fill="none">
					<path d="M3 13.5 5.2 8a3 3 0 0 1 2.8-2h8a3 3 0 0 1 2.8 2L21 13.5V18a1 1 0 0 1-1 1h-1.2a1 1 0 0 1-1-1v-.6H6.2V18a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-4.5Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
					<circle cx="7.4" cy="14.6" r="1.2" fill="currentColor"/>
					<circle cx="16.6" cy="14.6" r="1.2" fill="currentColor"/>
				</svg>
			</span>
		<?php endif; ?>

		<?php if ( $lk_ad_featured ) : ?>
			<span class="lk-ad__badge"><?php esc_html_e( 'فروش ویژه', 'lamerd-khodro' ); ?></span>
		<?php endif; ?>
	</a>

	<div class="lk-ad__body">
		<div class="lk-ad__meta">
			<?php if ( ! empty( $lk_ad['brand'] ) ) : ?>
				<span class="lk-chip"><?php echo esc_html( (string) $lk_ad['brand'] ); ?></span>
			<?php endif; ?>
			<span class="lk-ad__date"><?php echo esc_html( lk_posted_on( (int) $lk_ad['id'] ) ); ?></span>
		</div>

		<h3 class="lk-ad__title">
			<a href="<?php echo esc_url( (string) $lk_ad['url'] ); ?>"><?php echo esc_html( (string) $lk_ad['title'] ); ?></a>
		</h3>

		<?php if ( $lk_ad_specs ) : ?>
			<ul class="lk-ad__specs">
				<?php foreach ( $lk_ad_specs as $lk_ad_spec ) : ?>
					<li>
						<span class="lk-ad__spec-label"><?php echo esc_html( (string) $lk_ad_spec['label'] ); ?></span>
						<span class="lk-ad__spec-value"><?php echo esc_html( (string) $lk_ad_spec['value'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<div class="lk-ad__footer">
			<span class="lk-ad__price<?php echo null === $lk_ad['price'] ? ' lk-ad__price--na' : ''; ?>">
				<?php echo esc_html( lk_money( null === $lk_ad['price'] ? null : (int) $lk_ad['price'], __( 'قیمت توافقی', 'lamerd-khodro' ) ) ); ?>
			</span>
			<a class="lk-ad__link" href="<?php echo esc_url( (string) $lk_ad['url'] ); ?>">
				<?php esc_html_e( 'مشاهده آگهی', 'lamerd-khodro' ); ?> ←
			</a>
		</div>

		<?php if ( $lk_ad_until ) : ?>
			<div class="lk-ad__timer" data-lk-countdown="<?php echo esc_attr( (string) $lk_ad_until ); ?>">
				<span class="lk-ad__timer-icon" aria-hidden="true">⏱</span>
				<span class="lk-ad__timer-label"><?php esc_html_e( 'پایان فروش ویژه', 'lamerd-khodro' ); ?></span>
				<span class="lk-ad__timer-value" data-lk-countdown-value><?php echo esc_html( lk_countdown_text( $lk_ad_remaining ) ); ?></span>
			</div>
		<?php endif; ?>
	</div>
</article>
