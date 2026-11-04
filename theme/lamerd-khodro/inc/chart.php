<?php
/**
 * نمودار قیمت به‌صورت SVG در سمت سرور (بدون کتابخانه جاوااسکریپت).
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

/**
 * رندر نمودار خطی دو سری (بازار و کارخانه).
 *
 * @param array<int, array<string, mixed>> $points نقاط: date/factory/market.
 * @param array<string, mixed>             $args   گزینه‌ها: height, show_legend.
 * @return string HTML/SVG ایمن.
 */
function lk_render_price_chart( array $points, array $args = array() ): string {
	$args = wp_parse_args(
		$args,
		array(
			'height'      => 220,
			'show_legend' => true,
		)
	);

	if ( count( $points ) < 2 ) {
		$first = $points[0]['date'] ?? null;

		return '<div class="lk-chart lk-chart--empty">'
			. '<p>' . esc_html__( 'نمودار تغییرات پس از ثبت حداقل دو قیمت نمایش داده می‌شود.', 'lamerd-khodro' ) . '</p>'
			. ( $first ? '<p class="lk-chart__note">' . esc_html( sprintf( __( 'داده‌های این خودرو از %s ثبت شده است.', 'lamerd-khodro' ), lk_posted_on_fallback( (string) $first ) ) ) . '</p>' : '' )
			. '</div>';
	}

	$width  = 720;
	$height = max( 140, (int) $args['height'] );
	$pad_l  = 8;
	$pad_r  = 58;
	$pad_t  = 14;
	$pad_b  = 28;

	$values = array();
	foreach ( $points as $point ) {
		if ( null !== ( $point['market'] ?? null ) ) {
			$values[] = (float) $point['market'];
		}
		if ( null !== ( $point['factory'] ?? null ) ) {
			$values[] = (float) $point['factory'];
		}
	}

	if ( ! $values ) {
		return '<div class="lk-chart lk-chart--empty"><p>' . esc_html__( 'قیمتی برای رسم نمودار ثبت نشده است.', 'lamerd-khodro' ) . '</p></div>';
	}

	$min = min( $values );
	$max = max( $values );

	if ( $max <= $min ) {
		$max = $min + max( 1.0, $min * 0.02 );
	}

	$range = $max - $min;
	$min   = max( 0.0, $min - ( $range * 0.12 ) );
	$max   = $max + ( $range * 0.12 );

	$count = count( $points );
	$inner_w = $width - $pad_l - $pad_r;
	$inner_h = $height - $pad_t - $pad_b;

	$x = static function ( int $index ) use ( $count, $pad_l, $inner_w ): float {
		if ( $count <= 1 ) {
			return (float) $pad_l;
		}
		return $pad_l + ( $index / ( $count - 1 ) ) * $inner_w;
	};

	$y = static function ( float $value ) use ( $min, $max, $pad_t, $inner_h ): float {
		$ratio = ( $value - $min ) / ( $max - $min );
		return $pad_t + ( 1 - $ratio ) * $inner_h;
	};

	$build = static function ( array $points, string $key ) use ( $x, $y ): array {
		$segments = array();
		$current  = array();

		foreach ( $points as $index => $point ) {
			$value = $point[ $key ] ?? null;
			if ( null === $value ) {
				if ( $current ) {
					$segments[] = $current;
					$current    = array();
				}
				continue;
			}
			$current[] = array(
				'x' => $x( $index ),
				'y' => $y( (float) $value ),
			);
		}

		if ( $current ) {
			$segments[] = $current;
		}

		return $segments;
	};

	$market_segments  = $build( $points, 'market' );
	$factory_segments = $build( $points, 'factory' );

	$polyline = static function ( array $segment ): string {
		$coords = array_map(
			static function ( array $point ): string {
				return round( $point['x'], 1 ) . ',' . round( $point['y'], 1 );
			},
			$segment
		);
		return implode( ' ', $coords );
	};

	// برچسب محور عمودی: حداقل، میانه و حداکثر.
	$labels = array( $max, ( $min + $max ) / 2, $min );

	$svg  = '<svg class="lk-chart__svg" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="' . esc_attr__( 'نمودار تغییرات قیمت بازار و کارخانه', 'lamerd-khodro' ) . '" preserveAspectRatio="none">';

	// خطوط شبکه.
	foreach ( $labels as $label ) {
		$yy = round( $y( (float) $label ), 1 );
		$svg .= '<line x1="' . $pad_l . '" y1="' . $yy . '" x2="' . ( $width - $pad_r ) . '" y2="' . $yy . '" class="lk-chart__grid" />';
		$svg .= '<text x="' . ( $width - 4 ) . '" y="' . ( $yy + 4 ) . '" class="lk-chart__axis" text-anchor="start">'
			. esc_html( lk_short_money( (int) $label ) ) . '</text>';
	}

	// سری کارخانه (خط‌چین).
	foreach ( $factory_segments as $segment ) {
		if ( count( $segment ) < 2 ) {
			continue;
		}
		$svg .= '<polyline points="' . esc_attr( $polyline( $segment ) ) . '" class="lk-chart__line lk-chart__line--factory" />';
	}

	// سری بازار.
	foreach ( $market_segments as $segment ) {
		if ( count( $segment ) < 2 ) {
			continue;
		}
		$svg .= '<polyline points="' . esc_attr( $polyline( $segment ) ) . '" class="lk-chart__line lk-chart__line--market" />';
	}

	// نقطه پایانی.
	foreach ( $market_segments as $segment ) {
		$last = end( $segment );
		if ( $last ) {
			$svg .= '<circle cx="' . esc_attr( (string) round( $last['x'], 1 ) ) . '" cy="' . esc_attr( (string) round( $last['y'], 1 ) ) . '" r="3.5" class="lk-chart__dot" />';
		}
	}

	// برچسب اولین و آخرین تاریخ.
	$first_date = (string) ( $points[0]['date'] ?? '' );
	$last_point = $points[ $count - 1 ];
	$last_date  = (string) ( $last_point['date'] ?? '' );

	$svg .= '<text x="' . $pad_l . '" y="' . ( $height - 8 ) . '" class="lk-chart__axis" text-anchor="start">'
		. esc_html( lk_posted_on_fallback( $first_date ) ) . '</text>';
	$svg .= '<text x="' . ( $width - $pad_r ) . '" y="' . ( $height - 8 ) . '" class="lk-chart__axis" text-anchor="end">'
		. esc_html( lk_posted_on_fallback( $last_date ) ) . '</text>';

	$svg .= '</svg>';

	$legend = '';
	if ( $args['show_legend'] ) {
		$legend = '<div class="lk-chart__legend">'
			. '<span class="lk-chart__legend-item"><i class="lk-chart__swatch lk-chart__swatch--market"></i>' . esc_html__( 'قیمت بازار', 'lamerd-khodro' ) . '</span>'
			. '<span class="lk-chart__legend-item"><i class="lk-chart__swatch lk-chart__swatch--factory"></i>' . esc_html__( 'قیمت کارخانه', 'lamerd-khodro' ) . '</span>'
			. '</div>';
	}

	$note = '<p class="lk-chart__note">'
		. esc_html(
			sprintf(
				/* translators: %s: date */
				__( 'داده‌های نمودار از %s به بعد ثبت شده است؛ تاریخچه پیش از آن در منبع وجود ندارد.', 'lamerd-khodro' ),
				lk_posted_on_fallback( $first_date )
			)
		)
		. '</p>';

	return '<div class="lk-chart">' . $legend . $svg . $note . '</div>';
}

/**
 * خلاصه مبلغ (میلیون/میلیارد تومان) برای محور نمودار.
 *
 * @param int $amount مبلغ.
 * @return string
 */
function lk_short_money( int $amount ): string {
	if ( $amount >= 1000000000 ) {
		return lk_fa_digits( number_format( $amount / 1000000000, 1 ) ) . ' ' . __( 'میلیارد', 'lamerd-khodro' );
	}
	if ( $amount >= 1000000 ) {
		return lk_fa_digits( number_format( $amount / 1000000, 0 ) ) . ' ' . __( 'میلیون', 'lamerd-khodro' );
	}
	return lk_fa_digits( number_format_i18n( $amount ) );
}
