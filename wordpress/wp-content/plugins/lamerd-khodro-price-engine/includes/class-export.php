<?php
/**
 * خروجی گزارش‌ها: CSV، XLSX و نسخه چاپ‌پذیر (PDF).
 *
 * @package LamerdKhodroPriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * تولید فایل‌های خروجی از گزارش‌ها.
 */
class LKP_Export {

	/**
	 * اتصال هوک‌ها.
	 */
	public function register(): void {
		add_action( 'admin_post_lkp_export', array( $this, 'handle' ) );
	}

	/**
	 * پردازش درخواست خروجی.
	 */
	public function handle(): void {
		if ( ! current_user_can( LKP_Installer::CAPABILITY ) ) {
			wp_die( esc_html__( 'دسترسی مجاز نیست.', 'lamerd-khodro' ), 403 );
		}

		check_admin_referer( 'lkp_export' );

		$report = isset( $_GET['report'] ) ? sanitize_key( wp_unslash( $_GET['report'] ) ) : 'daily';
		$format = isset( $_GET['format'] ) ? sanitize_key( wp_unslash( $_GET['format'] ) ) : 'csv';

		$args = array(
			'range'      => isset( $_GET['range'] ) ? sanitize_key( wp_unslash( $_GET['range'] ) ) : '30d',
			'from'       => isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '',
			'to'         => isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '',
			'brand'      => isset( $_GET['brand'] ) ? sanitize_text_field( wp_unslash( $_GET['brand'] ) ) : '',
			'vehicle_id' => isset( $_GET['vehicle_id'] ) ? absint( wp_unslash( $_GET['vehicle_id'] ) ) : 0,
			'price_type' => ( isset( $_GET['price_type'] ) && 'factory' === $_GET['price_type'] ) ? 'factory' : 'market',
		);

		$data = lamerd_khodro_price_engine()->reports()->get( $report, $args );

		if ( 'pdf' === $format ) {
			$this->render_printable( $data, $args );
			exit;
		}

		$filename = 'lamerd-khodro-' . $report . '-' . gmdate( 'Ymd-His' );

		if ( 'xlsx' === $format ) {
			$this->send_xlsx( $data, $filename . '.xlsx' );
			exit;
		}

		$this->send_csv( $data, $filename . '.csv' );
		exit;
	}

	/**
	 * ارسال CSV با BOM برای اکسل فارسی.
	 *
	 * @param array<string, mixed> $data داده گزارش.
	 * @param string               $filename نام فایل.
	 */
	protected function send_csv( array $data, string $filename ): void {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );

		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			return;
		}

		fwrite( $output, "\xEF\xBB\xBF" );

		// عنوان گزارش و بازه.
		fputcsv( $output, array( $data['title'], $data['bounds']['label'] ) );
		fputcsv( $output, array() );
		fputcsv( $output, array_values( $data['columns'] ) );

		foreach ( $data['rows'] as $row ) {
			fputcsv( $output, array_map( array( $this, 'plain' ), $row ) );
		}

		fclose( $output );
	}

	/**
	 * تولید XLSX بدون کتابخانه خارجی (OOXML با inlineStr).
	 *
	 * @param array<string, mixed> $data     داده گزارش.
	 * @param string               $filename نام فایل.
	 */
	protected function send_xlsx( array $data, string $filename ): void {
		if ( ! class_exists( 'ZipArchive' ) ) {
			wp_die( esc_html__( 'افزونه ZipArchive روی سرور فعال نیست؛ از خروجی CSV استفاده کنید.', 'lamerd-khodro' ) );
		}

		$tmp = wp_tempnam( 'lkp-export' );
		if ( ! $tmp ) {
			wp_die( esc_html__( 'ساخت فایل موقت ناموفق بود.', 'lamerd-khodro' ) );
		}

		$zip = new ZipArchive();
		if ( true !== $zip->open( $tmp, ZipArchive::OVERWRITE ) ) {
			wp_die( esc_html__( 'ساخت فایل XLSX ناموفق بود.', 'lamerd-khodro' ) );
		}

		$sheet_rows = array();

		$sheet_rows[] = $this->xlsx_row( array( $data['title'] ), 1 );
		$sheet_rows[] = $this->xlsx_row( array( $data['bounds']['label'] ), 2 );
		$sheet_rows[] = $this->xlsx_row( array_values( $data['columns'] ), 3, true );

		$index = 4;
		foreach ( $data['rows'] as $row ) {
			$sheet_rows[] = $this->xlsx_row( array_values( $row ), $index );
			$index++;
		}

		$last_col = $this->column_letter( max( 1, count( $data['columns'] ) ) );
		$sheet    = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
			. '<sheetViews><sheetView rightToLeft="1" workbookViewId="0"/></sheetViews>'
			. '<cols><col min="1" max="' . esc_attr( (string) count( $data['columns'] ) ) . '" width="22" customWidth="1"/></cols>'
			. '<sheetData>' . implode( '', $sheet_rows ) . '</sheetData>'
			. '<autoFilter ref="A3:' . $last_col . '3"/></worksheet>';

		$zip->addFromString(
			'[Content_Types].xml',
			'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
			. '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
			. '<Default Extension="xml" ContentType="application/xml"/>'
			. '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
			. '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
			. '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
			. '</Types>'
		);

		$zip->addFromString(
			'_rels/.rels',
			'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
			. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
			. '</Relationships>'
		);

		$zip->addFromString(
			'xl/workbook.xml',
			'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
			. '<sheets><sheet name="' . esc_attr( mb_substr( $data['title'], 0, 28, 'UTF-8' ) ) . '" sheetId="1" r:id="rId1"/></sheets>'
			. '</workbook>'
		);

		$zip->addFromString(
			'xl/_rels/workbook.xml.rels',
			'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
			. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
			. '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
			. '</Relationships>'
		);

		$zip->addFromString(
			'xl/styles.xml',
			'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
			. '<fonts count="2"><font><sz val="11"/><name val="Tahoma"/></font><font><b/><sz val="11"/><name val="Tahoma"/></font></fonts>'
			. '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
			. '<borders count="1"><border/></borders>'
			. '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
			. '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
			. '</styleSheet>'
		);

		$zip->addFromString( 'xl/worksheets/sheet1.xml', $sheet );
		$zip->close();

		nocache_headers();
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
		header( 'Content-Length: ' . (string) filesize( $tmp ) );

		readfile( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		wp_delete_file( $tmp );
	}

	/**
	 * ساخت ردیف XML.
	 *
	 * @param array<int, mixed> $values مقادیر.
	 * @param int               $index  شماره ردیف.
	 * @param bool              $header آیا ردیف سرصفحه است.
	 * @return string
	 */
	protected function xlsx_row( array $values, int $index, bool $header = false ): string {
		$cells = '';

		foreach ( array_values( $values ) as $position => $value ) {
			$ref = $this->column_letter( $position + 1 ) . $index;

			if ( is_null( $value ) || '' === $value ) {
				$cells .= '<c r="' . $ref . '"' . ( $header ? ' s="1"' : '' ) . '/>';
				continue;
			}

			$style = $header ? ' s="1"' : '';

			if ( is_int( $value ) || is_float( $value ) ) {
				$cells .= '<c r="' . $ref . '"' . $style . '><v>' . esc_html( (string) $value ) . '</v></c>';
				continue;
			}

			$cells .= '<c r="' . $ref . '"' . $style . ' t="inlineStr"><is><t xml:space="preserve">'
				. esc_html( $this->plain( $value ) ) . '</t></is></c>';
		}

		return '<row r="' . $index . '">' . $cells . '</row>';
	}

	/**
	 * حرف ستون اکسل.
	 *
	 * @param int $number شماره ستون (۱ مبنا).
	 * @return string
	 */
	protected function column_letter( int $number ): string {
		$letters = '';
		while ( $number > 0 ) {
			$mod     = ( $number - 1 ) % 26;
			$letters = chr( 65 + $mod ) . $letters;
			$number  = (int) floor( ( $number - $mod ) / 26 );
		}
		return $letters;
	}

	/**
	 * تبدیل مقدار به متن ساده.
	 *
	 * @param mixed $value مقدار.
	 * @return string
	 */
	protected function plain( $value ): string {
		if ( is_null( $value ) ) {
			return '';
		}
		if ( is_bool( $value ) ) {
			return $value ? '1' : '0';
		}
		return (string) $value;
	}

	/**
	 * نسخه چاپ‌پذیر (تبدیل به PDF از دیالوگ چاپ مرورگر).
	 *
	 * @param array<string, mixed> $data داده گزارش.
	 * @param array<string, mixed> $args پارامترهای فیلتر.
	 */
	protected function render_printable( array $data, array $args ): void {
		$site = get_bloginfo( 'name' );
		?>
<!DOCTYPE html>
<html lang="fa-IR" dir="rtl">
<head>
	<meta charset="utf-8" />
	<title><?php echo esc_html( $data['title'] . ' — ' . $site ); ?></title>
	<style>
		@page { size: A4 landscape; margin: 12mm; }
		body { font-family: Tahoma, "IRANSans", sans-serif; font-size: 11px; color: #111; }
		h1 { font-size: 16px; margin: 0 0 4px; }
		.meta { color: #555; margin-bottom: 10px; font-size: 11px; }
		table { width: 100%; border-collapse: collapse; }
		th, td { border: 1px solid #bbb; padding: 4px 6px; text-align: right; }
		th { background: #f0f0f0; }
		tbody tr:nth-child(even) td { background: #fafafa; }
		.summary { margin-top: 10px; font-size: 11px; }
		.no-print { margin-bottom: 10px; }
		@media print { .no-print { display: none; } }
	</style>
</head>
<body>
	<div class="no-print">
		<button onclick="window.print()"><?php esc_html_e( 'چاپ / ذخیره به‌عنوان PDF', 'lamerd-khodro' ); ?></button>
	</div>
	<h1><?php echo esc_html( $data['title'] ); ?></h1>
	<div class="meta">
		<?php echo esc_html( $site ); ?> —
		<?php echo esc_html( $data['bounds']['label'] ); ?>
		—
		<?php echo esc_html( sprintf( __( 'تاریخ تهیه: %s', 'lamerd-khodro' ), date_i18n( 'Y/m/d H:i' ) ) ); ?>
	</div>
	<table>
		<thead>
			<tr>
				<?php foreach ( $data['columns'] as $label ) : ?>
					<th><?php echo esc_html( (string) $label ); ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $data['rows'] ) : ?>
				<tr><td colspan="<?php echo esc_attr( (string) max( 1, count( $data['columns'] ) ) ); ?>">
					<?php esc_html_e( 'داده‌ای در این بازه ثبت نشده است.', 'lamerd-khodro' ); ?>
				</td></tr>
			<?php else : ?>
				<?php foreach ( $data['rows'] as $row ) : ?>
					<tr>
						<?php foreach ( $row as $cell ) : ?>
							<td><?php echo is_null( $cell ) ? '—' : esc_html( number_format_i18n( (int) $cell ) === (string) $cell || is_int( $cell ) ? number_format_i18n( (int) $cell ) : (string) $cell ); ?></td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
	<?php if ( ! empty( $data['summary'] ) ) : ?>
		<div class="summary">
			<?php foreach ( $data['summary'] as $label => $value ) : ?>
				<strong><?php echo esc_html( (string) $label ); ?>:</strong> <?php echo esc_html( (string) $value ); ?> &nbsp;
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</body>
</html>
		<?php
	}
}
