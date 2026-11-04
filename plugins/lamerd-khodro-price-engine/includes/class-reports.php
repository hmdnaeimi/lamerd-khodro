<?php
/**
 * محاسبه ۱۲ گزارش از تاریخچه قیمت و لاگ همگام‌سازی.
 *
 * @package LamerdKhodroPriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * منبع واحد گزارش‌ها (برای نمایش و خروجی).
 */
class LKP_Reports {

	/**
	 * کلیدها و عنوان گزارش‌ها.
	 *
	 * @return array<string, string>
	 */
	public function keys(): array {
		return array(
			'daily'       => __( 'تغییرات روزانه', 'lamerd-khodro' ),
			'weekly'      => __( 'تغییرات هفتگی', 'lamerd-khodro' ),
			'monthly'     => __( 'تغییرات ماهانه', 'lamerd-khodro' ),
			'yearly'      => __( 'تغییرات سالانه', 'lamerd-khodro' ),
			'top_up'      => __( 'بیشترین افزایش قیمت', 'lamerd-khodro' ),
			'top_down'    => __( 'بیشترین کاهش قیمت', 'lamerd-khodro' ),
			'average'     => __( 'میانگین تغییرات', 'lamerd-khodro' ),
			'gap'         => __( 'اختلاف بازار و کارخانه', 'lamerd-khodro' ),
			'brands'      => __( 'مقایسه برندها', 'lamerd-khodro' ),
			'unchanged'   => __( 'خودروهای بدون تغییر', 'lamerd-khodro' ),
			'sources'     => __( 'عملکرد منابع', 'lamerd-khodro' ),
			'sync_log'    => __( 'نتیجه دریافت‌ها', 'lamerd-khodro' ),
		);
	}

	/**
	 * بازه‌های آماده.
	 *
	 * @return array<string, string>
	 */
	public function ranges(): array {
		return array(
			'today'     => __( 'امروز', 'lamerd-khodro' ),
			'yesterday' => __( 'دیروز', 'lamerd-khodro' ),
			'7d'        => __( '۷ روز اخیر', 'lamerd-khodro' ),
			'30d'       => __( '۳۰ روز اخیر', 'lamerd-khodro' ),
			'90d'       => __( '۹۰ روز اخیر', 'lamerd-khodro' ),
			'365d'      => __( 'یک سال اخیر', 'lamerd-khodro' ),
			'custom'    => __( 'بازه دلخواه', 'lamerd-khodro' ),
		);
	}

	/**
	 * تبدیل بازه به تاریخ شروع/پایان.
	 *
	 * @param string $range بازه.
	 * @param string $from  شروع دلخواه.
	 * @param string $to    پایان دلخواه.
	 * @return array{from:string,to:string,label:string}
	 */
	public function bounds( string $range, string $from = '', string $to = '' ): array {
		// تاریخ‌ها با منطقه زمانی سایت محاسبه می‌شوند تا با recorded_at هم‌زبان باشند.
		$timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
		$now      = new DateTimeImmutable( 'now', $timezone );
		$today    = $now->format( 'Y-m-d' );

		$days_ago = static function ( int $days ) use ( $now ): string {
			return $now->modify( '-' . max( 0, $days ) . ' days' )->format( 'Y-m-d' );
		};

		switch ( $range ) {
			case 'today':
				return array( 'from' => $today . ' 00:00:00', 'to' => $today . ' 23:59:59', 'label' => __( 'امروز', 'lamerd-khodro' ) );
			case 'yesterday':
				$day = $days_ago( 1 );
				return array( 'from' => $day . ' 00:00:00', 'to' => $day . ' 23:59:59', 'label' => __( 'دیروز', 'lamerd-khodro' ) );
			case '7d':
				return array( 'from' => $days_ago( 7 ) . ' 00:00:00', 'to' => $today . ' 23:59:59', 'label' => __( '۷ روز اخیر', 'lamerd-khodro' ) );
			case '90d':
				return array( 'from' => $days_ago( 90 ) . ' 00:00:00', 'to' => $today . ' 23:59:59', 'label' => __( '۹۰ روز اخیر', 'lamerd-khodro' ) );
			case '365d':
				return array( 'from' => $days_ago( 365 ) . ' 00:00:00', 'to' => $today . ' 23:59:59', 'label' => __( 'یک سال اخیر', 'lamerd-khodro' ) );
			case 'custom':
				$start = $from ? sanitize_text_field( $from ) : $days_ago( 30 );
				$end   = $to ? sanitize_text_field( $to ) : $today;
				return array(
					'from'  => $start . ' 00:00:00',
					'to'    => $end . ' 23:59:59',
					'label' => sprintf( '%s تا %s', $start, $end ),
				);
			case '30d':
			default:
				return array( 'from' => $days_ago( 30 ) . ' 00:00:00', 'to' => $today . ' 23:59:59', 'label' => __( '۳۰ روز اخیر', 'lamerd-khodro' ) );
		}
	}

	/**
	 * اجرای گزارش.
	 *
	 * @param string               $key  کلید گزارش.
	 * @param array<string, mixed> $args پارامترها: range, from, to, brand, vehicle_id, price_type.
	 * @return array{title:string,columns:array<string,string>,rows:array<int,array<int,mixed>>,summary:array<string,string>,bounds:array<string,string>}
	 */
	public function get( string $key, array $args = array() ): array {
		$args = wp_parse_args(
			$args,
			array(
				'range'      => '30d',
				'from'       => '',
				'to'         => '',
				'brand'      => '',
				'vehicle_id' => 0,
				'price_type' => 'market',
			)
		);

		$bounds = $this->bounds( (string) $args['range'], (string) $args['from'], (string) $args['to'] );
		$keys   = $this->keys();
		$title  = $keys[ $key ] ?? __( 'گزارش', 'lamerd-khodro' );

		$data = match ( $key ) {
			'daily'     => $this->grouped( 'DATE(h.recorded_at)', $bounds, $args ),
			'weekly'    => $this->grouped( "DATE_FORMAT(h.recorded_at, '%x-W%v')", $bounds, $args ),
			'monthly'   => $this->grouped( "DATE_FORMAT(h.recorded_at, '%Y-%m')", $bounds, $args ),
			'yearly'    => $this->grouped( 'YEAR(h.recorded_at)', $bounds, $args ),
			'top_up'    => $this->top_movers( 'up', $bounds, $args ),
			'top_down'  => $this->top_movers( 'down', $bounds, $args ),
			'average'   => $this->average( $bounds, $args ),
			'gap'       => $this->gap( $args ),
			'brands'    => $this->brands( $args ),
			'unchanged' => $this->unchanged( $bounds, $args ),
			'sources'   => $this->sources( $bounds ),
			'sync_log'  => $this->sync_logs( $bounds ),
			default     => array( 'columns' => array(), 'rows' => array(), 'summary' => array() ),
		};

		return array(
			'title'   => $title,
			'columns' => $data['columns'],
			'rows'    => $data['rows'],
			'summary' => $data['summary'] ?? array(),
			'bounds'  => $bounds,
		);
	}

	/**
	 * ستون قیمت بر اساس نوع انتخاب‌شده.
	 *
	 * @param array<string, mixed> $args پارامترها.
	 * @return string
	 */
	protected function price_column( array $args ): string {
		return 'factory' === ( $args['price_type'] ?? 'market' ) ? 'factory_change' : 'market_change';
	}

	/**
	 * شرط‌های فیلتر مشترک.
	 *
	 * @param array<string, mixed> $args پارامترها.
	 * @return array{0:string,1:array<int,mixed>}
	 */
	protected function filters( array $args ): array {
		$sql    = '';
		$params = array();

		if ( '' !== (string) ( $args['brand'] ?? '' ) ) {
			$sql     .= ' AND v.brand_group = %s';
			$params[] = (string) $args['brand'];
		}
		if ( (int) ( $args['vehicle_id'] ?? 0 ) > 0 ) {
			$sql     .= ' AND h.vehicle_id = %d';
			$params[] = (int) $args['vehicle_id'];
		}

		return array( $sql, $params );
	}

	/**
	 * گزارش‌های گروه‌بندی‌شده زمانی.
	 *
	 * @param string               $group_expr عبارت گروه‌بندی.
	 * @param array<string, mixed> $bounds     بازه.
	 * @param array<string, mixed> $args       پارامترها.
	 * @return array{columns:array<string,string>,rows:array<int,array<int,mixed>>,summary:array<string,string>}
	 */
	protected function grouped( string $group_expr, array $bounds, array $args ): array {
		global $wpdb;

		$history = LKP_Installer::table( 'price_history' );
		$veh     = LKP_Installer::table( 'vehicles' );
		$col     = $this->price_column( $args );
		list( $filter_sql, $filter_params ) = $this->filters( $args );

		$sql = "SELECT {$group_expr} AS bucket,
				COUNT(*) AS events,
				SUM(CASE WHEN h.{$col} > 0 THEN 1 ELSE 0 END) AS up_count,
				SUM(CASE WHEN h.{$col} < 0 THEN 1 ELSE 0 END) AS down_count,
				SUM(CASE WHEN h.{$col} > 0 THEN h.{$col} ELSE 0 END) AS up_sum,
				SUM(CASE WHEN h.{$col} < 0 THEN h.{$col} ELSE 0 END) AS down_sum,
				AVG(CASE WHEN h.{$col} IS NOT NULL AND (h.market_price - h.{$col}) > 0
					THEN h.{$col} / (h.market_price - h.{$col}) * 100 END) AS avg_pct
			FROM {$history} h
			INNER JOIN {$veh} v ON v.id = h.vehicle_id
			WHERE h.recorded_at BETWEEN %s AND %s AND h.{$col} IS NOT NULL {$filter_sql}
			GROUP BY bucket
			ORDER BY bucket DESC
			LIMIT 366";

		$params = array_merge( array( $bounds['from'], $bounds['to'] ), $filter_params );
		$rows   = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$out       = array();
		$tot_event = 0;
		$tot_up    = 0;
		$tot_down  = 0;

		foreach ( (array) $rows as $row ) {
			$tot_event += (int) $row['events'];
			$tot_up    += (int) $row['up_count'];
			$tot_down  += (int) $row['down_count'];

			$out[] = array(
				(string) $row['bucket'],
				(int) $row['events'],
				(int) $row['up_count'],
				(int) $row['down_count'],
				(int) round( (float) $row['up_sum'] ),
				(int) round( (float) $row['down_sum'] ),
				null === $row['avg_pct'] ? null : round( (float) $row['avg_pct'], 2 ),
			);
		}

		return array(
			'columns' => array(
				'd'     => __( 'بازه', 'lamerd-khodro' ),
				'e'     => __( 'رویداد تغییر', 'lamerd-khodro' ),
				'up'    => __( 'افزایش', 'lamerd-khodro' ),
				'down'  => __( 'کاهش', 'lamerd-khodro' ),
				'up_s'  => __( 'مجموع افزایش (تومان)', 'lamerd-khodro' ),
				'dn_s'  => __( 'مجموع کاهش (تومان)', 'lamerd-khodro' ),
				'avg'   => __( 'میانگین درصد', 'lamerd-khodro' ),
			),
			'rows'    => $out,
			'summary' => array(
				__( 'مجموع رویدادها', 'lamerd-khodro' ) => (string) $tot_event,
				__( 'افزایش‌ها', 'lamerd-khodro' )      => (string) $tot_up,
				__( 'کاهش‌ها', 'lamerd-khodro' )        => (string) $tot_down,
			),
		);
	}

	/**
	 * بیشترین افزایش/کاهش.
	 *
	 * @param string               $direction up|down.
	 * @param array<string, mixed> $bounds    بازه.
	 * @param array<string, mixed> $args      پارامترها.
	 * @return array{columns:array<string,string>,rows:array<int,array<int,mixed>>,summary:array<string,string>}
	 */
	protected function top_movers( string $direction, array $bounds, array $args ): array {
		global $wpdb;

		$history = LKP_Installer::table( 'price_history' );
		$veh     = LKP_Installer::table( 'vehicles' );
		$col     = $this->price_column( $args );
		$sign    = 'up' === $direction ? '>' : '<';
		$order   = 'up' === $direction ? 'DESC' : 'ASC';

		list( $filter_sql, $filter_params ) = $this->filters( $args );

		$sql = "SELECT v.id, v.name, v.brand_group, v.post_id,
				SUM(h.{$col}) AS total_change,
				MAX(h.{$col}) AS max_change,
				MIN(h.{$col}) AS min_change,
				COUNT(*) AS events
			FROM {$history} h
			INNER JOIN {$veh} v ON v.id = h.vehicle_id
			WHERE h.recorded_at BETWEEN %s AND %s AND h.{$col} {$sign} 0 {$filter_sql}
			GROUP BY v.id, v.name, v.brand_group, v.post_id
			ORDER BY total_change {$order}
			LIMIT 50";

		$params = array_merge( array( $bounds['from'], $bounds['to'] ), $filter_params );
		$rows   = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$out   = array();
		$total = 0;
		foreach ( (array) $rows as $row ) {
			$total += (int) $row['total_change'];
			$out[]  = array(
				(string) $row['name'],
				(string) $row['brand_group'],
				(int) $row['total_change'],
				(int) $row['max_change'],
				(int) $row['min_change'],
				(int) $row['events'],
			);
		}

		return array(
			'columns' => array(
				'n'    => __( 'خودرو', 'lamerd-khodro' ),
				'g'    => __( 'گروه', 'lamerd-khodro' ),
				't'    => __( 'مجموع تغییر (تومان)', 'lamerd-khodro' ),
				'max'  => __( 'بیشترین تغییر', 'lamerd-khodro' ),
				'min'  => __( 'کمترین تغییر', 'lamerd-khodro' ),
				'e'    => __( 'تعداد رویداد', 'lamerd-khodro' ),
			),
			'rows'    => $out,
			'summary' => array(
				__( 'تعداد خودرو', 'lamerd-khodro' ) => (string) count( $out ),
				__( 'مجموع تغییرات', 'lamerd-khodro' ) => number_format_i18n( $total ) . ' ' . __( 'تومان', 'lamerd-khodro' ),
			),
		);
	}

	/**
	 * میانگین تغییرات.
	 *
	 * @param array<string, mixed> $bounds بازه.
	 * @param array<string, mixed> $args   پارامترها.
	 * @return array{columns:array<string,string>,rows:array<int,array<int,mixed>>,summary:array<string,string>}
	 */
	protected function average( array $bounds, array $args ): array {
		global $wpdb;

		$history = LKP_Installer::table( 'price_history' );
		$veh     = LKP_Installer::table( 'vehicles' );
		$col     = $this->price_column( $args );

		list( $filter_sql, $filter_params ) = $this->filters( $args );

		$sql = "SELECT v.id, v.name, v.brand_group,
				COUNT(*) AS events,
				AVG(h.{$col}) AS avg_change,
				AVG(CASE WHEN (h.{$col}) <> 0 AND (h.market_price - h.{$col}) > 0
					THEN h.{$col} / (h.market_price - h.{$col}) * 100 END) AS avg_pct
			FROM {$history} h
			INNER JOIN {$veh} v ON v.id = h.vehicle_id
			WHERE h.recorded_at BETWEEN %s AND %s AND h.{$col} IS NOT NULL {$filter_sql}
			GROUP BY v.id, v.name, v.brand_group
			ORDER BY avg_change DESC
			LIMIT 100";

		$params = array_merge( array( $bounds['from'], $bounds['to'] ), $filter_params );
		$rows   = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$out = array();
		$sum = 0.0;
		foreach ( (array) $rows as $row ) {
			$sum  += (float) $row['avg_change'];
			$out[] = array(
				(string) $row['name'],
				(string) $row['brand_group'],
				(int) $row['events'],
				(int) round( (float) $row['avg_change'] ),
				null === $row['avg_pct'] ? null : round( (float) $row['avg_pct'], 2 ),
			);
		}

		return array(
			'columns' => array(
				'n'   => __( 'خودرو', 'lamerd-khodro' ),
				'g'   => __( 'گروه', 'lamerd-khodro' ),
				'e'   => __( 'تعداد تغییر', 'lamerd-khodro' ),
				'avg' => __( 'میانگین تغییر (تومان)', 'lamerd-khodro' ),
				'pct' => __( 'میانگین درصد', 'lamerd-khodro' ),
			),
			'rows'    => $out,
			'summary' => array(
				__( 'میانگین کل تغییرات', 'lamerd-khodro' ) => $out ? number_format_i18n( (int) round( $sum / count( $out ) ) ) . ' ' . __( 'تومان', 'lamerd-khodro' ) : '—',
			),
		);
	}

	/**
	 * اختلاف بازار و کارخانه.
	 *
	 * @param array<string, mixed> $args پارامترها.
	 * @return array{columns:array<string,string>,rows:array<int,array<int,mixed>>,summary:array<string,string>}
	 */
	protected function gap( array $args ): array {
		global $wpdb;

		$current = LKP_Installer::table( 'current_prices' );
		$veh     = LKP_Installer::table( 'vehicles' );

		$where  = 'c.is_valid = 1 AND c.factory_price IS NOT NULL AND c.market_price IS NOT NULL AND c.factory_price > 0';
		$params = array();

		if ( '' !== (string) ( $args['brand'] ?? '' ) ) {
			$where   .= ' AND v.brand_group = %s';
			$params[] = (string) $args['brand'];
		}
		if ( (int) ( $args['vehicle_id'] ?? 0 ) > 0 ) {
			$where   .= ' AND v.id = %d';
			$params[] = (int) $args['vehicle_id'];
		}

		$sql = "SELECT v.name, v.brand_group, c.factory_price, c.market_price,
				(c.market_price - c.factory_price) AS gap,
				((c.market_price - c.factory_price) / c.factory_price * 100) AS gap_pct
			FROM {$current} c
			INNER JOIN {$veh} v ON v.id = c.vehicle_id
			WHERE {$where}
			ORDER BY gap DESC
			LIMIT 200";

		$rows = $params
			? $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			: $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[] = array(
				(string) $row['name'],
				(string) $row['brand_group'],
				(int) $row['factory_price'],
				(int) $row['market_price'],
				(int) $row['gap'],
				round( (float) $row['gap_pct'], 2 ),
			);
		}

		return array(
			'columns' => array(
				'n'   => __( 'خودرو', 'lamerd-khodro' ),
				'g'   => __( 'گروه', 'lamerd-khodro' ),
				'f'   => __( 'قیمت کارخانه', 'lamerd-khodro' ),
				'm'   => __( 'قیمت بازار', 'lamerd-khodro' ),
				'gap' => __( 'اختلاف (تومان)', 'lamerd-khodro' ),
				'pct' => __( 'درصد اختلاف', 'lamerd-khodro' ),
			),
			'rows'    => $out,
			'summary' => array(
				__( 'تعداد ردیف', 'lamerd-khodro' ) => (string) count( $out ),
			),
		);
	}

	/**
	 * مقایسه برندها (گروه منبع).
	 *
	 * @param array<string, mixed> $args پارامترها.
	 * @return array{columns:array<string,string>,rows:array<int,array<int,mixed>>,summary:array<string,string>}
	 */
	protected function brands( array $args ): array {
		global $wpdb;

		$current = LKP_Installer::table( 'current_prices' );
		$veh     = LKP_Installer::table( 'vehicles' );
		$col     = $this->price_column( $args );

		$sql = "SELECT v.brand_group,
				COUNT(*) AS total,
				AVG(c.market_price) AS avg_market,
				MIN(c.market_price) AS min_market,
				MAX(c.market_price) AS max_market,
				AVG(c.change_percent) AS avg_pct,
				SUM(CASE WHEN c.{$col} > 0 THEN 1 ELSE 0 END) AS up_count,
				SUM(CASE WHEN c.{$col} < 0 THEN 1 ELSE 0 END) AS down_count
			FROM {$current} c
			INNER JOIN {$veh} v ON v.id = c.vehicle_id
			WHERE c.is_valid = 1 AND c.market_price IS NOT NULL AND v.brand_group <> ''
			GROUP BY v.brand_group
			ORDER BY total DESC";

		$rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[] = array(
				(string) $row['brand_group'],
				(int) $row['total'],
				(int) round( (float) $row['avg_market'] ),
				(int) $row['min_market'],
				(int) $row['max_market'],
				null === $row['avg_pct'] ? null : round( (float) $row['avg_pct'], 2 ),
				(int) $row['up_count'],
				(int) $row['down_count'],
			);
		}

		return array(
			'columns' => array(
				'g'    => __( 'گروه/برند', 'lamerd-khodro' ),
				't'    => __( 'تعداد خودرو', 'lamerd-khodro' ),
				'avg'  => __( 'میانگین قیمت بازار', 'lamerd-khodro' ),
				'min'  => __( 'کمترین قیمت', 'lamerd-khodro' ),
				'max'  => __( 'بیشترین قیمت', 'lamerd-khodro' ),
				'pct'  => __( 'میانگین درصد تغییر', 'lamerd-khodro' ),
				'up'   => __( 'افزایشی', 'lamerd-khodro' ),
				'down' => __( 'کاهشی', 'lamerd-khodro' ),
			),
			'rows'    => $out,
			'summary' => array(
				__( 'تعداد گروه', 'lamerd-khodro' ) => (string) count( $out ),
			),
		);
	}

	/**
	 * خودروهای بدون تغییر در بازه.
	 *
	 * @param array<string, mixed> $bounds بازه.
	 * @param array<string, mixed> $args   پارامترها.
	 * @return array{columns:array<string,string>,rows:array<int,array<int,mixed>>,summary:array<string,string>}
	 */
	protected function unchanged( array $bounds, array $args ): array {
		global $wpdb;

		$current = LKP_Installer::table( 'current_prices' );
		$veh     = LKP_Installer::table( 'vehicles' );
		$history = LKP_Installer::table( 'price_history' );

		$where  = 'c.is_valid = 1';
		$params = array( $bounds['from'], $bounds['to'] );

		if ( '' !== (string) ( $args['brand'] ?? '' ) ) {
			$where   .= ' AND v.brand_group = %s';
			$params[] = (string) $args['brand'];
		}

		$sql = "SELECT v.name, v.brand_group, c.market_price, c.factory_price, c.fetched_at
			FROM {$current} c
			INNER JOIN {$veh} v ON v.id = c.vehicle_id
			WHERE {$where}
			  AND NOT EXISTS (
				SELECT 1 FROM {$history} h
				WHERE h.vehicle_id = c.vehicle_id AND h.recorded_at BETWEEN %s AND %s
			  )
			ORDER BY v.name ASC
			LIMIT 500";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[] = array(
				(string) $row['name'],
				(string) $row['brand_group'],
				null === $row['factory_price'] ? null : (int) $row['factory_price'],
				null === $row['market_price'] ? null : (int) $row['market_price'],
				(string) $row['fetched_at'],
			);
		}

		return array(
			'columns' => array(
				'n' => __( 'خودرو', 'lamerd-khodro' ),
				'g' => __( 'گروه', 'lamerd-khodro' ),
				'f' => __( 'قیمت کارخانه', 'lamerd-khodro' ),
				'm' => __( 'قیمت بازار', 'lamerd-khodro' ),
				't' => __( 'آخرین دریافت', 'lamerd-khodro' ),
			),
			'rows'    => $out,
			'summary' => array(
				__( 'بدون تغییر', 'lamerd-khodro' ) => (string) count( $out ),
			),
		);
	}

	/**
	 * عملکرد منابع.
	 *
	 * @param array<string, mixed> $bounds بازه.
	 * @return array{columns:array<string,string>,rows:array<int,array<int,mixed>>,summary:array<string,string>}
	 */
	protected function sources( array $bounds ): array {
		global $wpdb;

		$sources = LKP_Installer::table( 'price_sources' );
		$logs    = LKP_Installer::table( 'sync_logs' );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.name, s.last_success_at, s.last_error,
					COUNT(l.id) AS runs,
					SUM(CASE WHEN l.status = 'success' THEN 1 ELSE 0 END) AS ok,
					SUM(CASE WHEN l.status IN ('failed','partial','locked') THEN 1 ELSE 0 END) AS bad,
					AVG(l.duration_ms) AS avg_ms,
					SUM(l.received) AS received_total
				FROM {$sources} s
				LEFT JOIN {$logs} l ON l.started_at BETWEEN %s AND %s
				GROUP BY s.id, s.name, s.last_success_at, s.last_error
				ORDER BY s.id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$bounds['from'],
				$bounds['to']
			),
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
			$runs = (int) $row['runs'];
			$ok   = (int) $row['ok'];
			$out[] = array(
				(string) $row['name'],
				$runs,
				$ok,
				(int) $row['bad'],
				$runs > 0 ? round( $ok / $runs * 100, 1 ) : null,
				(int) round( (float) $row['avg_ms'] ),
				(int) $row['received_total'],
				(string) ( $row['last_success_at'] ?? '—' ),
			);
		}

		return array(
			'columns' => array(
				's'    => __( 'منبع', 'lamerd-khodro' ),
				'r'    => __( 'اجراها', 'lamerd-khodro' ),
				'ok'   => __( 'موفق', 'lamerd-khodro' ),
				'bad'  => __( 'ناموفق/جزئی', 'lamerd-khodro' ),
				'rate' => __( 'نرخ موفقیت (٪)', 'lamerd-khodro' ),
				'ms'   => __( 'میانگین زمان (ms)', 'lamerd-khodro' ),
				'rec'  => __( 'خودروی دریافتی', 'lamerd-khodro' ),
				'last' => __( 'آخرین موفقیت', 'lamerd-khodro' ),
			),
			'rows'    => $out,
			'summary' => array(),
		);
	}

	/**
	 * لاگ دریافت‌ها.
	 *
	 * @param array<string, mixed> $bounds بازه.
	 * @return array{columns:array<string,string>,rows:array<int,array<int,mixed>>,summary:array<string,string>}
	 */
	protected function sync_logs( array $bounds ): array {
		global $wpdb;

		$logs = LKP_Installer::table( 'sync_logs' );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$logs} WHERE started_at BETWEEN %s AND %s ORDER BY id DESC LIMIT 300", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$bounds['from'],
				$bounds['to']
			),
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[] = array(
				(string) $row['started_at'],
				(string) $row['trigger_type'],
				(string) $row['status'],
				(int) $row['received'],
				(int) $row['created'],
				(int) $row['price_changed'],
				(int) $row['errors'],
				(int) $row['duration_ms'],
			);
		}

		return array(
			'columns' => array(
				't'    => __( 'زمان شروع', 'lamerd-khodro' ),
				'tr'   => __( 'محرک', 'lamerd-khodro' ),
				'st'   => __( 'وضعیت', 'lamerd-khodro' ),
				'rec'  => __( 'دریافتی', 'lamerd-khodro' ),
				'new'  => __( 'جدید', 'lamerd-khodro' ),
				'chg'  => __( 'تغییر قیمت', 'lamerd-khodro' ),
				'err'  => __( 'خطا', 'lamerd-khodro' ),
				'ms'   => __( 'مدت (ms)', 'lamerd-khodro' ),
			),
			'rows'    => $out,
			'summary' => array(
				__( 'تعداد اجرا', 'lamerd-khodro' ) => (string) count( $out ),
			),
		);
	}
}
