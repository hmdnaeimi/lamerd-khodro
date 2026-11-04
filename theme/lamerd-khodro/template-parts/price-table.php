<?php
/**
 * جدول قیمت خودروها.
 *
 * @package LamerdKhodro
 *
 * @var array<int, array<string, mixed>> $args فهرست خودروها.
 */

defined( 'ABSPATH' ) || exit;

$lk_items = isset( $args ) && is_array( $args ) ? $args : array();

if ( ! $lk_items ) {
	echo '<div class="lk-empty">' . esc_html__( 'قیمتی برای نمایش ثبت نشده است.', 'lamerd-khodro' ) . '</div>';
	return;
}
?>
<div class="lk-table-card">
	<table class="lk-price-table" data-lk-sortable>
		<thead>
			<tr>
				<th scope="col">
					<button type="button" class="lk-sort" data-lk-sort="name" data-direction="asc" style="background:none;border:0;padding:0;font:inherit;color:inherit;cursor:pointer">
						<?php esc_html_e( 'خودرو', 'lamerd-khodro' ); ?>
					</button>
				</th>
				<th scope="col"><?php esc_html_e( 'برند', 'lamerd-khodro' ); ?></th>
				<th scope="col">
					<button type="button" data-lk-sort="price" data-direction="desc" style="background:none;border:0;padding:0;font:inherit;color:inherit;cursor:pointer">
						<?php esc_html_e( 'قیمت بازار', 'lamerd-khodro' ); ?>
					</button>
				</th>
				<th scope="col"><?php esc_html_e( 'تغییر', 'lamerd-khodro' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $lk_items as $lk_item ) : ?>
				<?php
				$lk_price  = $lk_item['price'] ?? null;
				$lk_market = is_array( $lk_price ) ? ( $lk_price['market_price'] ?? null ) : null;
				?>
				<tr data-name="<?php echo esc_attr( (string) $lk_item['title'] ); ?>" data-price="<?php echo esc_attr( null === $lk_market ? '0' : (string) $lk_market ); ?>">
					<td class="lk-col-name">
						<a href="<?php echo esc_url( (string) $lk_item['url'] ); ?>"><?php echo esc_html( (string) $lk_item['title'] ); ?></a>
					</td>
					<td class="lk-col-brand"><?php echo esc_html( (string) ( $lk_item['brand']['name'] ?? '—' ) ); ?></td>
					<td class="lk-col-price">
						<?php echo esc_html( lk_money( null === $lk_market ? null : (int) $lk_market ) ); ?>
					</td>
					<td class="lk-col-change">
						<?php echo wp_kses_post( lk_change_badge( is_array( $lk_price ) ? $lk_price : null ) ?: '<span class="lk-muted">—</span>' ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<div class="lk-table-foot">
		<span><?php esc_html_e( 'قیمت‌ها به تومان و بر اساس آخرین دریافت از منبع است.', 'lamerd-khodro' ); ?></span>
		<?php lk_attribution( $lk_items[0]['price'] ?? null ); ?>
	</div>
</div>
