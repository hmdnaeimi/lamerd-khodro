<?php
/**
 * Template Name: مقایسه خودرو
 * Description: مقایسه مشخصات و قیمت دو تا چهار خودرو.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lk_core = function_exists( 'lamerd_khodro_core' );

// ورودی شناسه‌ها: هم به‌صورت رشته «۱,۲» و هم آرایه ids[] پذیرفته می‌شود.
$lk_raw = isset( $_GET['ids'] ) ? wp_unslash( $_GET['ids'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( is_array( $lk_raw ) ) {
	$lk_ids = array_map( 'absint', $lk_raw );
} else {
	$lk_ids = array_map( 'absint', explode( ',', (string) $lk_raw ) );
}

$lk_ids   = array_slice( array_values( array_unique( array_filter( $lk_ids ) ) ), 0, 4 );
$lk_items = array();

if ( $lk_core ) {
	$lk_service = lamerd_khodro_core()->vehicles();
	foreach ( $lk_ids as $lk_id ) {
		$lk_item = $lk_service->get( (int) $lk_id );
		if ( $lk_item ) {
			$lk_items[ (int) $lk_id ] = $lk_item;
		}
	}

	$lk_options = $lk_service->get_all( array( 'limit' => 100, 'orderby' => 'title', 'order' => 'ASC' ) );
} else {
	$lk_options = array();
}

// ساخت ردیف‌های مقایسه از مشخصات همه خودروهای انتخاب‌شده.
$lk_rows = array();

if ( $lk_items && class_exists( 'LKC_Meta_Boxes' ) ) {
	$lk_groups = LKC_Meta_Boxes::public_fields();

	foreach ( $lk_groups as $lk_group_key => $lk_group ) {
		foreach ( array_keys( $lk_group['fields'] ) as $lk_field_key ) {
			$lk_label = (string) ( $lk_group['fields'][ $lk_field_key ]['label'] ?? $lk_field_key );
			$lk_label = lk_spec_label( (string) $lk_group_key, (string) $lk_field_key );

			$lk_values = array();
			foreach ( $lk_items as $lk_item_id => $lk_item ) {
				$lk_raw_value = $lk_item['specs'][ $lk_group_key ]['fields'][ $lk_field_key ] ?? '';
				$lk_values[ $lk_item_id ] = '' === $lk_raw_value
					? '—'
					: lk_spec_value( (string) $lk_group_key, (string) $lk_field_key, $lk_raw_value );
			}

			// فقط ردیف‌هایی که حداقل یک مقدار دارند.
			if ( array_filter( $lk_values, static fn( $value ) => '—' !== $value ) ) {
				$lk_rows[ (string) $lk_group['title'] ][ $lk_label ] = $lk_values;
			}
		}
	}
}
?>

<div class="lk-container">
	<?php lk_breadcrumbs(); ?>

	<header class="lk-section__head">
		<h1 class="lk-section__title"><?php esc_html_e( 'مقایسه خودرو', 'lamerd-khodro' ); ?></h1>
		<span class="lk-muted" style="font-size:.8125rem"><?php esc_html_e( 'حداقل دو و حداکثر چهار خودرو', 'lamerd-khodro' ); ?></span>
	</header>

	<form method="get" class="lk-panel" style="margin-block-end:20px">
		<div style="display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
			<?php for ( $lk_index = 0; $lk_index < 4; $lk_index++ ) : ?>
				<?php $lk_selected = $lk_ids[ $lk_index ] ?? 0; ?>
				<label style="display:flex;flex-direction:column;gap:6px;font-size:.8125rem">
					<span><?php echo esc_html( sprintf( __( 'خودرو %s', 'lamerd-khodro' ), lk_fa_digits( (string) ( $lk_index + 1 ) ) ) ); ?></span>
					<select name="ids[]" style="padding:10px 12px;border-radius:10px;border:1px solid var(--lk-line);background:#fff">
						<option value=""><?php esc_html_e( '— انتخاب نشده —', 'lamerd-khodro' ); ?></option>
						<?php foreach ( $lk_options as $lk_option ) : ?>
							<option value="<?php echo esc_attr( (string) $lk_option['id'] ); ?>" <?php selected( $lk_selected, (int) $lk_option['id'] ); ?>>
								<?php echo esc_html( (string) $lk_option['title'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endfor; ?>
		</div>

		<p style="margin:14px 0 0">
			<button type="submit" class="lk-btn lk-btn--primary"><?php esc_html_e( 'مقایسه کن', 'lamerd-khodro' ); ?></button>
		</p>
	</form>

	<?php if ( count( $lk_items ) < 2 ) : ?>
		<div class="lk-empty">
			<p><?php esc_html_e( 'برای مقایسه، نام دو خودرو را از فهرست بالا انتخاب کنید.', 'lamerd-khodro' ); ?></p>
			<p class="lk-muted"><?php esc_html_e( 'مقایسه شامل قیمت، مشخصات فنی، ابعاد و امکانات است.', 'lamerd-khodro' ); ?></p>
		</div>
	<?php else : ?>
		<div class="lk-compare">
			<table>
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'ویژگی', 'lamerd-khodro' ); ?></th>
						<?php foreach ( $lk_items as $lk_item ) : ?>
							<th scope="col">
								<?php if ( ! empty( $lk_item['thumbnail'] ) ) : ?>
									<img class="lk-compare__img" src="<?php echo esc_url( (string) $lk_item['thumbnail'] ); ?>" alt="<?php echo esc_attr( (string) $lk_item['title'] ); ?>" loading="lazy" />
								<?php endif; ?>
								<a href="<?php echo esc_url( (string) $lk_item['url'] ); ?>"><?php echo esc_html( (string) $lk_item['title'] ); ?></a>
							</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'قیمت بازار', 'lamerd-khodro' ); ?></th>
						<?php foreach ( $lk_items as $lk_item ) : ?>
							<?php $lk_market = $lk_item['price']['market_price'] ?? null; ?>
							<td style="font-weight:800">
								<?php echo esc_html( lk_money( null === $lk_market ? null : (int) $lk_market ) ); ?>
							</td>
						<?php endforeach; ?>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'قیمت کارخانه', 'lamerd-khodro' ); ?></th>
						<?php foreach ( $lk_items as $lk_item ) : ?>
							<?php $lk_factory = $lk_item['price']['factory_price'] ?? null; ?>
							<td><?php echo esc_html( lk_money( null === $lk_factory ? null : (int) $lk_factory ) ); ?></td>
						<?php endforeach; ?>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'تغییر قیمت', 'lamerd-khodro' ); ?></th>
						<?php foreach ( $lk_items as $lk_item ) : ?>
							<td><?php echo wp_kses_post( lk_change_badge( is_array( $lk_item['price'] ) ? $lk_item['price'] : null ) ?: '<span class="lk-muted">—</span>' ); ?></td>
						<?php endforeach; ?>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'برند', 'lamerd-khodro' ); ?></th>
						<?php foreach ( $lk_items as $lk_item ) : ?>
							<td><?php echo esc_html( (string) ( $lk_item['brand']['name'] ?? '—' ) ); ?></td>
						<?php endforeach; ?>
					</tr>

					<?php foreach ( $lk_rows as $lk_group_title => $lk_group_rows ) : ?>
						<tr>
							<th scope="row" colspan="<?php echo esc_attr( (string) ( count( $lk_items ) + 1 ) ); ?>" style="background:var(--lk-surface-2);font-weight:800;color:var(--lk-ink)">
								<?php echo esc_html( (string) $lk_group_title ); ?>
							</th>
						</tr>
						<?php foreach ( $lk_group_rows as $lk_label => $lk_values ) : ?>
							<tr>
								<th scope="row"><?php echo esc_html( (string) $lk_label ); ?></th>
								<?php foreach ( $lk_items as $lk_item_id => $lk_item ) : ?>
									<td><?php echo esc_html( (string) ( $lk_values[ $lk_item_id ] ?? '—' ) ); ?></td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php lk_attribution( $lk_items[ array_key_first( $lk_items ) ]['price'] ?? null ); ?>
	<?php endif; ?>
</div>

<?php
get_footer();
