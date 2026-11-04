<?php
/**
 * توابع کمکی موتور قیمت (مستقل از افزونه هسته).
 *
 * @package LamerdKhodroPriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * قالب‌بندی مبلغ تومان؛ null هرگز صفر نمایش داده نمی‌شود.
 *
 * @param int|null $amount مبلغ.
 * @return string
 */
function lkp_format_amount( ?int $amount ): string {
	if ( null === $amount ) {
		return __( 'ناموجود', 'lamerd-khodro' );
	}
	return number_format_i18n( $amount ) . ' ' . __( 'تومان', 'lamerd-khodro' );
}

/**
 * قالب‌بندی تغییر مبلغ با علامت.
 *
 * @param int|null $amount تغییر.
 * @return string
 */
function lkp_format_change( ?int $amount ): string {
	if ( null === $amount ) {
		return '—';
	}
	if ( 0 === $amount ) {
		return __( 'بدون تغییر', 'lamerd-khodro' );
	}
	$sign = $amount > 0 ? '+' : '−';
	return $sign . number_format_i18n( abs( $amount ) );
}

/**
 * تبدیل ارقام به فارسی برای نمایش.
 *
 * @param string $text متن.
 * @return string
 */
function lkp_fa_digits( string $text ): string {
	if ( function_exists( 'lkc_to_fa_digits' ) ) {
		return lkc_to_fa_digits( $text );
	}
	return str_replace( range( '0', '9' ), array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ), $text );
}

/**
 * تبدیل تاریخ میلادی به شمسی.
 *
 * @param int $gy سال.
 * @param int $gm ماه.
 * @param int $gd روز.
 * @return array{0:int,1:int,2:int}
 */
function lkp_gregorian_to_jalali( int $gy, int $gm, int $gd ): array {
	$g_days_in_month = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
	$j_days_in_month = array( 31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29 );

	$gy2 = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;

	$days = 355666 + ( 365 * $gy ) + intdiv( $gy2 + 3, 4 ) - intdiv( $gy2 + 99, 100 ) + intdiv( $gy2 + 399, 400 ) + $gd;

	for ( $i = 0; $i < $gm - 1; $i++ ) {
		$days += $g_days_in_month[ $i ];
	}

	$jy   = -1595 + ( 33 * intdiv( $days, 12053 ) );
	$days %= 12053;

	$jy   += 4 * intdiv( $days, 1461 );
	$days %= 1461;

	if ( $days > 365 ) {
		$jy  += intdiv( $days - 1, 365 );
		$days = ( $days - 1 ) % 365;
	}

	if ( $days < 186 ) {
		$jm = 1 + intdiv( $days, 31 );
		$jd = 1 + ( $days % 31 );
	} else {
		$jm = 7 + intdiv( $days - 186, 30 );
		$jd = 1 + ( ( $days - 186 ) % 30 );
	}

	return array( $jy, $jm, $jd );
}

/**
 * نمایش تاریخ/ساعت دیتابیس به شمسی.
 *
 * @param string|null $datetime تاریخ به قالب Y-m-d H:i:s.
 * @param bool        $with_time نمایش ساعت.
 * @return string
 */
function lkp_jalali( ?string $datetime, bool $with_time = true ): string {
	if ( ! $datetime || '0000-00-00 00:00:00' === $datetime ) {
		return '—';
	}

	$timestamp = strtotime( $datetime );
	if ( ! $timestamp ) {
		return '—';
	}

	list( $jy, $jm, $jd ) = lkp_gregorian_to_jalali(
		(int) gmdate( 'Y', $timestamp ),
		(int) gmdate( 'n', $timestamp ),
		(int) gmdate( 'j', $timestamp )
	);

	$out = sprintf( '%04d/%02d/%02d', $jy, $jm, $jd );

	if ( $with_time ) {
		$out .= ' — ' . gmdate( 'H:i', $timestamp );
	}

	return lkp_fa_digits( $out );
}

/**
 * نشان وضعیت.
 *
 * @param string $state ok|warn|bad|stale|error|never.
 * @param string $label متن.
 * @return string HTML ایمن.
 */
function lkp_badge( string $state, string $label ): string {
	$class = 'lkp-badge';
	if ( in_array( $state, array( 'ok', 'success', 'linked' ), true ) ) {
		$class .= ' lkp-badge--ok';
	} elseif ( in_array( $state, array( 'warn', 'partial', 'stale', 'locked', 'unlinked' ), true ) ) {
		$class .= ' lkp-badge--warn';
	} elseif ( in_array( $state, array( 'bad', 'failed', 'error', 'never', 'running' ), true ) ) {
		$class .= ' lkp-badge--bad';
	}
	return '<span class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
}

/**
 * برچسب فارسی وضعیت همگام‌سازی.
 *
 * @param string $status وضعیت.
 * @return string
 */
function lkp_status_label( string $status ): string {
	$map = array(
		'success' => __( 'موفق', 'lamerd-khodro' ),
		'partial' => __( 'جزئی', 'lamerd-khodro' ),
		'failed'  => __( 'ناموفق', 'lamerd-khodro' ),
		'locked'  => __( 'قفل', 'lamerd-khodro' ),
		'running' => __( 'در حال اجرا', 'lamerd-khodro' ),
	);
	return $map[ $status ] ?? $status;
}

/**
 * برچسب محرک همگام‌سازی.
 *
 * @param string $trigger محرک.
 * @return string
 */
function lkp_trigger_label( string $trigger ): string {
	$map = array(
		'cron'   => __( 'زمان‌بندی', 'lamerd-khodro' ),
		'manual' => __( 'دستی', 'lamerd-khodro' ),
		'retry'  => __( 'تلاش مجدد', 'lamerd-khodro' ),
	);
	return $map[ $trigger ] ?? $trigger;
}
