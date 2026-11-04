<?php
/**
 * کارت خودرو.
 *
 * @package LamerdKhodro
 *
 * @var array<string, mixed> $args داده خودرو (خروجی LKC_Vehicle_Service).
 */

defined( 'ABSPATH' ) || exit;

$lk_item = isset( $args ) && is_array( $args ) ? $args : array();

if ( empty( $lk_item['id'] ) ) {
	return;
}

$lk_price   = $lk_item['price'] ?? null;
$lk_market  = is_array( $lk_price ) ? ( $lk_price['market_price'] ?? null ) : null;
$lk_year    = $lk_item['specs']['general']['fields']['year'] ?? '';
$lk_brand   = $lk_item['brand']['name'] ?? '';
$lk_updated = is_array( $lk_price ) ? ( $lk_price['fetched_at'] ?? '' ) : '';
?>
<article class="lk-card">
	<a class="lk-card__media" href="<?php echo esc_url( (string) $lk_item['url'] ); ?>" aria-hidden="true" tabindex="-1">
		<?php if ( ! empty( $lk_item['thumbnail'] ) ) : ?>
			<img
				src="<?php echo esc_url( (string) $lk_item['thumbnail'] ); ?>"
				alt="<?php echo esc_attr( (string) $lk_item['title'] ); ?>"
				loading="lazy"
				width="640"
				height="400"
			/>
		<?php else : ?>
			<span class="lk-card__media-placeholder" aria-hidden="true">
				<svg width="44" height="44" viewBox="0 0 24 24" fill="none">
					<path d="M3 13.5 5.2 8a3 3 0 0 1 2.8-2h8a3 3 0 0 1 2.8 2L21 13.5V18a1 1 0 0 1-1 1h-1.2a1 1 0 0 1-1-1v-.6H6.2V18a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-4.5Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
					<circle cx="7.4" cy="14.6" r="1.2" fill="currentColor"/>
					<circle cx="16.6" cy="14.6" r="1.2" fill="currentColor"/>
				</svg>
			</span>
		<?php endif; ?>
	</a>

	<div class="lk-card__body">
		<div class="lk-card__meta">
			<?php if ( $lk_brand ) : ?>
				<span class="lk-chip"><?php echo esc_html( $lk_brand ); ?></span>
			<?php else : ?>
				<span></span>
			<?php endif; ?>
			<?php if ( $lk_year ) : ?>
				<span><?php echo esc_html( lk_fa_digits( (string) $lk_year ) ); ?></span>
			<?php endif; ?>
		</div>

		<h3 class="lk-card__title">
			<a href="<?php echo esc_url( (string) $lk_item['url'] ); ?>"><?php echo esc_html( (string) $lk_item['title'] ); ?></a>
		</h3>

		<div class="lk-card__price">
			<span class="lk-card__price-value<?php echo null === $lk_market ? ' lk-card__price-value--na' : ''; ?>">
				<?php echo esc_html( lk_money( null === $lk_market ? null : (int) $lk_market ) ); ?>
			</span>
			<?php echo wp_kses_post( lk_change_badge( is_array( $lk_price ) ? $lk_price : null ) ); ?>
		</div>

		<?php if ( $lk_updated ) : ?>
			<p class="lk-card__note">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: date */
						__( 'به‌روزرسانی: %s', 'lamerd-khodro' ),
						lk_posted_on_fallback( (string) $lk_updated )
					)
				);
				?>
			</p>
		<?php endif; ?>
	</div>
</article>
