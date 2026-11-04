<?php
/**
 * توابع کمکی مشترک افزونه هسته.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

/**
 * قالب‌بندی عدد به فارسی با جداکننده هزارگان.
 *
 * @param int|string $number عدد.
 * @return string
 */
function lkc_format_number( $number ): string {
	$number = (string) $number;
	if ( '' === $number ) {
		return '';
	}
	return number_format_i18n( (float) $number, 0 );
}

/**
 * قالب‌بندی قیمت تومان.
 *
 * @param int|string $amount مبلغ به تومان.
 * @return string
 */
function lkc_format_price( $amount ): string {
	if ( '' === $amount || null === $amount ) {
		return __( 'ناموجود', 'lamerd-khodro' );
	}
	return lkc_format_number( $amount ) . ' ' . __( 'تومان', 'lamerd-khodro' );
}

/**
 * تبدیل عدد انگلیسی به فارسی (برای نمایش یکپارچه).
 *
 * @param string $text متن.
 * @return string
 */
function lkc_to_fa_digits( string $text ): string {
	$en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	$fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
	return str_replace( $en, $fa, $text );
}

/**
 * نرمال‌سازی متن برای جستجو/تطبیق فارسی.
 *
 * یکسان‌سازی ی/ک عربی، حذف نیم‌فاصله و فاضله، اعداد فارسی→انگلیسی، حروف کوچک.
 *
 * @param string $text متن ورودی.
 * @return string
 */
function lkc_normalize_text( string $text ): string {
	$text = mb_strtolower( trim( $text ), 'UTF-8' );

	// اعداد فارسی و عربی به انگلیسی.
	$fa_digits   = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' );
	$en_digits   = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	$text        = str_replace( $fa_digits, $en_digits, $text );

	// عربی به فارسی: ي→ی ، ك→ک ، أ/إ/آ→ا ، ة→ه ، ئ→ی
	$text = str_replace(
		array( 'ي', 'ك', 'ة', 'ى', 'ؤ', 'إ', 'أ', 'ٱ', 'ٲ', 'ٳ', 'ٔ', 'ء' ),
		array( 'ی', 'ک', 'ه', 'ی', 'و', 'ا', 'ا', 'ا', 'ا', 'ا', '', 'ی' ),
		$text
	);

	// نیم‌فاصله عربی و فاصله‌های اضافه حذف.
	$text = str_replace( array( "\u{200C}", "\u{200D}", '‌', '‍' ), '', $text );
	$text = preg_replace( '/\s+/u', ' ', $text );

	return trim( (string) $text );
}

/**
 * آیا افزونه Price Engine فعال است؟
 *
 * @return bool
 */
function lkc_has_price_engine(): bool {
	return function_exists( 'lamerd_khodro_price_engine' );
}

/**
 * بررسی عمومی بودن درخواست جاری.
 *
 * @return bool
 */
function lkc_is_public_request(): bool {
	return ! is_admin() && ! wp_doing_ajax();
}
