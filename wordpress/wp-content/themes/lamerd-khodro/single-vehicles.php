<?php
/**
 * صفحه اختصاصی خودرو.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$lk_post_id  = get_the_ID();
	$lk_core     = function_exists( 'lamerd_khodro_core' );
	$lk_brand    = lk_vehicle_brand( $lk_post_id );
	$lk_price    = lk_get_price( $lk_post_id );
	$lk_sections = lk_spec_sections( $lk_post_id );

	$lk_year  = get_post_meta( $lk_post_id, '_lkp_year', true );
	$lk_model = get_post_meta( $lk_post_id, '_lkp_model', true );

	$lk_status_map = array(
		'in'      => __( 'در تولید', 'lamerd-khodro' ),
		'stopped' => __( 'توقف تولید', 'lamerd-khodro' ),
		'future'  => __( 'تولید آینده', 'lamerd-khodro' ),
	);
	$lk_status = (string) get_post_meta( $lk_post_id, '_lkp_production_status', true );
	?>

	<div class="lk-container">
		<?php lk_breadcrumbs(); ?>

		<div class="lk-vehicle">
			<div>
				<div class="lk-vehicle__gallery">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php the_post_thumbnail( 'lk-hero', array( 'fetchpriority' => 'high' ) ); ?>
					<?php else : ?>
						<div class="lk-card__media-placeholder" style="aspect-ratio:16/10;display:grid;place-items:center;background:linear-gradient(135deg,#eef2f7,#e3e9f1);color:#b6c2d2">
							<svg width="70" height="70" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M3 13.5 5.2 8a3 3 0 0 1 2.8-2h8a3 3 0 0 1 2.8 2L21 13.5V18a1 1 0 0 1-1 1h-1.2a1 1 0 0 1-1-1v-.6H6.2V18a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-4.5Z" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/>
								<circle cx="7.4" cy="14.6" r="1.2" fill="currentColor"/>
								<circle cx="16.6" cy="14.6" r="1.2" fill="currentColor"/>
							</svg>
						</div>
					<?php endif; ?>
				</div>

				<div class="lk-vehicle__headline">
					<h1 class="lk-vehicle__title"><?php the_title(); ?></h1>
					<?php if ( $lk_brand ) : ?>
						<a class="lk-chip" href="<?php echo esc_url( $lk_brand['url'] ); ?>"><?php echo esc_html( $lk_brand['name'] ); ?></a>
					<?php endif; ?>
					<?php if ( $lk_status && isset( $lk_status_map[ $lk_status ] ) ) : ?>
						<span class="lk-chip <?php echo 'stopped' === $lk_status ? 'lk-chip--accent' : 'lk-chip--down'; ?>">
							<?php echo esc_html( $lk_status_map[ $lk_status ] ); ?>
						</span>
					<?php endif; ?>
				</div>

				<div class="lk-vehicle__sub">
					<?php if ( $lk_model ) : ?>
						<span><?php echo esc_html( (string) $lk_model ); ?></span>
					<?php endif; ?>
					<?php if ( $lk_year ) : ?>
						<span><?php echo esc_html( sprintf( __( 'مدل %s', 'lamerd-khodro' ), lk_fa_digits( (string) $lk_year ) ) ); ?></span>
					<?php endif; ?>
					<span><?php echo esc_html( sprintf( __( 'بازدید: %s', 'lamerd-khodro' ), lk_fa_digits( number_format_i18n( (int) get_post_meta( $lk_post_id, '_lkp_views', true ) ) ) ) ); ?></span>
				</div>

				<?php if ( get_the_content() ) : ?>
					<div class="lk-prose">
						<?php the_content(); ?>
					</div>
				<?php endif; ?>
			</div>

			<aside class="lk-vehicle__aside">
				<?php lk_price_block( $lk_post_id ); ?>
				<?php lk_attribution( $lk_price ); ?>

				<div style="display:grid;gap:8px;margin-block-start:12px">
					<button
						type="button"
						class="lk-btn lk-btn--primary lk-btn--block"
						data-lk-compare-add="<?php echo esc_attr( (string) $lk_post_id ); ?>"
						data-lk-compare-label-add="<?php esc_attr_e( 'افزودن به مقایسه', 'lamerd-khodro' ); ?>"
						data-lk-compare-label-remove="<?php esc_attr_e( 'حذف از مقایسه', 'lamerd-khodro' ); ?>"
						aria-pressed="false"
					>
						<?php esc_html_e( 'افزودن به مقایسه', 'lamerd-khodro' ); ?>
					</button>
					<a class="lk-btn lk-btn--ghost lk-btn--block" href="<?php echo esc_url( lk_compare_url() ); ?>" data-lk-compare-link>
						<?php esc_html_e( 'رفتن به صفحه مقایسه', 'lamerd-khodro' ); ?>
					</a>
				</div>

				<?php
				$lk_quick = array(
					'engine_type' => __( 'موتور', 'lamerd-khodro' ),
					'engine_cc'   => __( 'حجم موتور', 'lamerd-khodro' ),
					'transmission' => __( 'گیربکس', 'lamerd-khodro' ),
					'fuel'        => __( 'سوخت', 'lamerd-khodro' ),
					'body_type'   => __( 'بدنه', 'lamerd-khodro' ),
					'seats'       => __( 'ظرفیت', 'lamerd-khodro' ),
				);

				$lk_quick_rows = array();
				foreach ( $lk_sections as $lk_group_key => $lk_group ) {
					foreach ( $lk_quick as $lk_key => $lk_label ) {
						if ( isset( $lk_group['rows'][ $lk_key ] ) ) {
							$lk_quick_rows[ $lk_label ] = $lk_group['rows'][ $lk_key ]['value'];
						}
					}
				}

				if ( $lk_quick_rows ) :
					?>
					<div class="lk-specs">
						<div class="lk-specs__head"><?php esc_html_e( 'نگاه سریع', 'lamerd-khodro' ); ?></div>
						<table class="lk-specs__table">
							<tbody>
								<?php foreach ( $lk_quick_rows as $lk_label => $lk_value ) : ?>
									<tr>
										<th scope="row"><?php echo esc_html( (string) $lk_label ); ?></th>
										<td><?php echo esc_html( (string) $lk_value ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</aside>
		</div>

		<?php
		$lk_chart    = null;
		$lk_price_id = (int) get_post_meta( $lk_post_id, '_lkp_price_vehicle_id', true );

		if ( $lk_price_id > 0 && function_exists( 'lamerd_khodro_price_engine' ) ) {
			$lk_price_row = lamerd_khodro_price_engine()->vehicles()->find( $lk_price_id );

			if ( $lk_price_row ) {
				$lk_chart = lamerd_khodro_price_engine()->prices()->chart_series( $lk_price_id, 180 );
			}
		}

		if ( $lk_chart ) :
			?>
			<section class="lk-section lk-section--tight">
				<div class="lk-section__head">
					<h2 class="lk-section__title"><?php esc_html_e( 'روند تغییرات قیمت', 'lamerd-khodro' ); ?></h2>
				</div>
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo lk_render_price_chart( (array) $lk_chart['points'] );
				?>
			</section>
		<?php endif; ?>

		<?php if ( $lk_sections ) : ?>
			<section class="lk-section lk-section--tight">
				<div class="lk-section__head">
					<h2 class="lk-section__title"><?php esc_html_e( 'مشخصات فنی', 'lamerd-khodro' ); ?></h2>
				</div>

				<?php foreach ( $lk_sections as $lk_group ) : ?>
					<div class="lk-specs">
						<div class="lk-specs__head"><?php echo esc_html( (string) $lk_group['title'] ); ?></div>
						<table class="lk-specs__table">
							<tbody>
								<?php foreach ( $lk_group['rows'] as $lk_row ) : ?>
									<tr>
										<th scope="row"><?php echo esc_html( (string) $lk_row['label'] ); ?></th>
										<td><?php echo esc_html( (string) $lk_row['value'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endforeach; ?>
			</section>
		<?php endif; ?>

		<?php
		$lk_brand_terms = get_the_terms( $lk_post_id, 'brand' );
		$lk_brand_term  = ( is_array( $lk_brand_terms ) && $lk_brand_terms ) ? (int) $lk_brand_terms[0]->term_id : 0;

		$lk_related = new WP_Query(
			array(
				'post_type'      => 'vehicles',
				'posts_per_page' => 4,
				'post__not_in'   => array( $lk_post_id ),
				'no_found_rows'  => true,
				'tax_query'      => $lk_brand_term ? array(
					array(
						'taxonomy' => 'brand',
						'field'    => 'term_id',
						'terms'    => $lk_brand_term,
					),
				) : array(),
			)
		);

		if ( $lk_related->have_posts() && $lk_core ) :
			$lk_service = lamerd_khodro_core()->vehicles();
			?>
			<section class="lk-section">
				<div class="lk-section__head">
					<h2 class="lk-section__title"><?php esc_html_e( 'خودروهای مشابه', 'lamerd-khodro' ); ?></h2>
				</div>
				<div class="lk-cards">
					<?php
					while ( $lk_related->have_posts() ) :
						$lk_related->the_post();
						lk_load_template_part( 'template-parts/card-vehicle', $lk_service->format( get_post() ) );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</section>
			<?php
		endif;
		?>

		<?php
		if ( comments_open() || get_comments_number() ) {
			echo '<div class="lk-panel lk-section">';
			comments_template();
			echo '</div>';
		}
		?>
	</div>

	<?php
endwhile;

get_footer();
