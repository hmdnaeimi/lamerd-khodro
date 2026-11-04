<?php
/**
 * لایه دسترسی به داده (Repositories).
 *
 * @package LamerdKhodroPriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * مخزن خودروهای منبع.
 */
class LKP_Vehicle_Repository {

	/**
	 * ایجاد/به‌روزرسانی خودروی منبع.
	 *
	 * @param LKP_Car_Row $car       ردیف معتبر.
	 * @param int         $source_id منبع.
	 * @return array{id:int,created:bool}
	 */
	public function upsert( LKP_Car_Row $car, int $source_id ): array {
		global $wpdb;

		$table = LKP_Installer::table( 'vehicles' );
		$now   = current_time( 'mysql' );

		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE source_id = %d AND external_id = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$source_id,
				$car->external_id
			)
		);

		$norm = function_exists( 'lkc_normalize_text' )
			? lkc_normalize_text( $car->name )
			: mb_strtolower( $car->name, 'UTF-8' );

		if ( $existing ) {
			$wpdb->update(
				$table,
				array(
					'name'               => $car->name,
					'name_normalized'    => $norm,
					'brand_group'        => $car->group,
					'production_stopped' => $car->stopped ? 1 : 0,
					'is_active'          => 1,
					'updated_at'         => $now,
				),
				array( 'id' => (int) $existing->id ),
				array( '%s', '%s', '%s', '%d', '%d', '%s' ),
				array( '%d' )
			);

			return array(
				'id'      => (int) $existing->id,
				'created' => false,
			);
		}

		$wpdb->insert(
			$table,
			array(
				'source_id'          => $source_id,
				'external_id'        => $car->external_id,
				'name'               => $car->name,
				'name_normalized'    => $norm,
				'brand_group'        => $car->group,
				'post_id'            => null,
				'production_stopped' => $car->stopped ? 1 : 0,
				'is_active'          => 1,
				'created_at'         => $now,
				'updated_at'         => $now,
			),
			array( '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' )
		);

		return array(
			'id'      => (int) $wpdb->insert_id,
			'created' => true,
		);
	}

	/**
	 * یافتن ردیف با شناسه داخلی.
	 *
	 * @param int $id شناسه.
	 * @return array<string, mixed>|null
	 */
	public function find( int $id ): ?array {
		global $wpdb;

		$table = LKP_Installer::table( 'vehicles' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ?: null;
	}

	/**
	 * یافتن با شناسه منبع.
	 *
	 * @param int $external_id شناسه در منبع.
	 * @param int $source_id   منبع.
	 * @return array<string, mixed>|null
	 */
	public function find_by_external( int $external_id, int $source_id = 1 ): ?array {
		global $wpdb;

		$table = LKP_Installer::table( 'vehicles' );
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE source_id = %d AND external_id = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$source_id,
				$external_id
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	/**
	 * فهرست خودروها با جستجو و صفحه‌بندی.
	 *
	 * @param array<string, mixed> $args فیلترها.
	 * @return array{items:array<int,array<string,mixed>>,total:int}
	 */
	public function paginate( array $args = array() ): array {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'search'        => '',
				'group'         => '',
				'page'          => 1,
				'per_page'      => 25,
				'only_unlinked' => false,
				'only_linked'   => false,
				'orderby'       => 'name',
				'order'         => 'ASC',
			)
		);

		$table    = LKP_Installer::table( 'vehicles' );
		$current  = LKP_Installer::table( 'current_prices' );
		$where    = array( 'v.is_active = 1' );
		$params   = array();

		if ( '' !== trim( (string) $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( trim( (string) $args['search'] ) ) . '%';
			$where[]  = '(v.name LIKE %s OR v.name_normalized LIKE %s OR v.brand_group LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ( '' !== (string) $args['group'] ) {
			$where[]  = 'v.brand_group = %s';
			$params[] = (string) $args['group'];
		}

		if ( ! empty( $args['only_unlinked'] ) ) {
			$where[] = '(v.post_id IS NULL OR v.post_id = 0)';
		}
		if ( ! empty( $args['only_linked'] ) ) {
			$where[] = 'v.post_id IS NOT NULL AND v.post_id > 0';
		}

		$where_sql = implode( ' AND ', $where );

		$order_map = array(
			'name'   => 'v.name',
			'id'     => 'v.id',
			'market' => 'c.market_price',
			'change' => 'c.change_percent',
			'group'  => 'v.brand_group',
		);
		$order_by  = $order_map[ (string) $args['orderby'] ] ?? 'v.name';
		$order     = 'DESC' === strtoupper( (string) $args['order'] ) ? 'DESC' : 'ASC';

		$per_page = max( 1, min( 200, (int) $args['per_page'] ) );
		$offset   = ( max( 1, (int) $args['page'] ) - 1 ) * $per_page;

		$count_sql = "SELECT COUNT(*) FROM {$table} v WHERE {$where_sql}";
		$total     = (int) ( $params
			? $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			: $wpdb->get_var( $count_sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$sql = "SELECT v.*, c.market_price, c.factory_price, c.change_amount, c.change_percent, c.fetched_at
			FROM {$table} v
			LEFT JOIN {$current} c ON c.vehicle_id = v.id
			WHERE {$where_sql}
			ORDER BY {$order_by} {$order}, v.id ASC
			LIMIT %d OFFSET %d";

		$query_params   = $params;
		$query_params[] = $per_page;
		$query_params[] = $offset;

		$items = $wpdb->get_results( $wpdb->prepare( $sql, $query_params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return array(
			'items' => (array) $items,
			'total' => $total,
		);
	}

	/**
	 * تعداد کل خودروهای فعال.
	 *
	 * @return int
	 */
	public function count(): int {
		global $wpdb;
		$table = LKP_Installer::table( 'vehicles' );
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_active = 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * گروه‌های برند اعلام‌شده منبع.
	 *
	 * @return array<int, array{name:string,count:int}>
	 */
	public function groups(): array {
		global $wpdb;
		$table = LKP_Installer::table( 'vehicles' );
		$rows  = $wpdb->get_results(
			"SELECT brand_group, COUNT(*) AS total FROM {$table} WHERE is_active = 1 AND brand_group <> '' GROUP BY brand_group ORDER BY total DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return array_map(
			static function ( $row ) {
				return array(
					'name'  => (string) $row['brand_group'],
					'count' => (int) $row['total'],
				);
			},
			(array) $rows
		);
	}

	/**
	 * اتصال ردیف منبع به نوشته CPT.
	 *
	 * @param int      $vehicle_id شناسه ردیف.
	 * @param int|null $post_id    شناسه نوشته (null = قطع اتصال).
	 * @return bool
	 */
	public function set_post( int $vehicle_id, ?int $post_id ): bool {
		global $wpdb;

		$table = LKP_Installer::table( 'vehicles' );

		$result = $wpdb->update(
			$table,
			array( 'post_id' => ( $post_id && $post_id > 0 ) ? $post_id : null ),
			array( 'id' => $vehicle_id ),
			array( '%d' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * فهرست خودروهای بدون اتصال.
	 *
	 * @param int $limit تعداد.
	 * @return array<int, array<string, mixed>>
	 */
	public function unlinked( int $limit = 100 ): array {
		global $wpdb;
		$table = LKP_Installer::table( 'vehicles' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE is_active = 1 AND (post_id IS NULL OR post_id = 0) ORDER BY name ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				max( 1, $limit )
			),
			ARRAY_A
		);
		return (array) $rows;
	}

	/**
	 * نگاشت شناسه ردیف به شناسه نوشته.
	 *
	 * @return array<int, int>
	 */
	public function post_map(): array {
		global $wpdb;
		$table = LKP_Installer::table( 'vehicles' );
		$rows  = $wpdb->get_results(
			"SELECT id, post_id FROM {$table} WHERE post_id IS NOT NULL AND post_id > 0", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		$map = array();
		foreach ( (array) $rows as $row ) {
			$map[ (int) $row['id'] ] = (int) $row['post_id'];
		}
		return $map;
	}
}

/**
 * مخزن قیمت‌ها و تاریخچه.
 */
class LKP_Price_Repository {

	/**
	 * قیمت جاری یک خودرو.
	 *
	 * @param int $vehicle_id شناسه ردیف منبع.
	 * @return array<string, mixed>|null
	 */
	public function get_current( int $vehicle_id ): ?array {
		global $wpdb;

		if ( $vehicle_id <= 0 ) {
			return null;
		}

		$current = LKP_Installer::table( 'current_prices' );
		$veh     = LKP_Installer::table( 'vehicles' );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT c.*, v.name, v.external_id, v.brand_group, v.production_stopped, v.post_id, s.attribution_text, s.attribution_url
				 FROM {$current} c
				 INNER JOIN {$veh} v ON v.id = c.vehicle_id
				 LEFT JOIN " . LKP_Installer::table( 'price_sources' ) . " s ON s.id = c.source_id
				 WHERE c.vehicle_id = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
				$vehicle_id
			),
			ARRAY_A
		);

		// سازگاری: اگر شناسه منبع خارجی داده شد، ردیف متناظر پیدا می‌شود.
		if ( ! $row ) {
			$internal = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$veh} WHERE external_id = %d ORDER BY id ASC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$vehicle_id
				)
			);
			if ( $internal ) {
				return $this->get_current( (int) $internal );
			}
			return null;
		}

		return $this->format_current( $row );
	}

	/**
	 * نرمال‌سازی ردیف قیمت جاری برای مصرف قالب.
	 *
	 * @param array<string, mixed> $row ردیف دیتابیس.
	 * @return array<string, mixed>
	 */
	protected function format_current( array $row ): array {
		$fetched = (string) ( $row['fetched_at'] ?? '' );
		$stale   = false;

		if ( '' !== $fetched ) {
			$timestamp = strtotime( get_gmt_from_date( $fetched ) . ' UTC' );
			if ( $timestamp ) {
				$stale = ( time() - $timestamp ) > DAY_IN_SECONDS;
			}
		}

		return array(
			'vehicle_id'     => (int) $row['vehicle_id'],
			'external_id'    => (int) ( $row['external_id'] ?? 0 ),
			'name'           => (string) ( $row['name'] ?? '' ),
			'group'          => (string) ( $row['brand_group'] ?? '' ),
			'post_id'        => (int) ( $row['post_id'] ?? 0 ),
			'factory_price'  => null === $row['factory_price'] ? null : (int) $row['factory_price'],
			'market_price'   => null === $row['market_price'] ? null : (int) $row['market_price'],
			'change_amount'  => null === $row['change_amount'] ? null : (int) $row['change_amount'],
			'change_percent' => null === $row['change_percent'] ? null : (float) $row['change_percent'],
			'fetched_at'     => $fetched,
			'updated_at'     => $fetched,
			'source_updated_at' => $row['source_updated_at'] ?? null,
			'is_stale'       => $stale,
			'is_valid'       => ! empty( $row['is_valid'] ),
			'attribution'    => array(
				'text' => (string) ( $row['attribution_text'] ?? '' ),
				'url'  => (string) ( $row['attribution_url'] ?? '' ),
			),
		);
	}

	/**
	 * قیمت جاری چند خودرو.
	 *
	 * @param int[] $vehicle_ids شناسه‌ها.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_current_many( array $vehicle_ids ): array {
		global $wpdb;

		$ids = array_values( array_filter( array_map( 'absint', $vehicle_ids ) ) );
		if ( ! $ids ) {
			return array();
		}

		$current = LKP_Installer::table( 'current_prices' );
		$veh     = LKP_Installer::table( 'vehicles' );
		$in      = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*, v.name, v.external_id, v.brand_group, v.production_stopped, v.post_id
				 FROM {$current} c
				 INNER JOIN {$veh} v ON v.id = c.vehicle_id
				 WHERE c.vehicle_id IN ({$in})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
				$ids
			),
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[ (int) $row['vehicle_id'] ] = $this->format_current( $row );
		}
		return $out;
	}

	/**
	 * ردیف قیمت خام (برای مقایسه در همگام‌سازی).
	 *
	 * @param int $vehicle_id شناسه.
	 * @return array<string, mixed>|null
	 */
	public function get_raw_current( int $vehicle_id ): ?array {
		global $wpdb;
		$current = LKP_Installer::table( 'current_prices' );
		$row     = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$current} WHERE vehicle_id = %d LIMIT 1", $vehicle_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		return $row ?: null;
	}

	/**
	 * ذخیره قیمت جاری.
	 *
	 * @param int              $vehicle_id شناسه ردیف.
	 * @param int              $source_id  منبع.
	 * @param LKP_Car_Row      $car        داده معتبر.
	 * @param string|null      $source_updated_at زمان اعلام منبع.
	 * @return void
	 */
	public function save_current( int $vehicle_id, int $source_id, LKP_Car_Row $car, ?string $source_updated_at ): void {
		global $wpdb;

		$current = LKP_Installer::table( 'current_prices' );
		$now     = current_time( 'mysql' );
		$source  = $this->normalize_datetime( $source_updated_at );

		$data = array(
			'factory_price'     => $car->factory,
			'market_price'      => $car->market,
			'change_amount'     => $car->change,
			'change_percent'    => $car->change_pct,
			'source_id'         => $source_id,
			'fetched_at'        => $now,
			'source_updated_at' => $source,
			'is_valid'          => 1,
		);

		$formats = array( '%d', '%d', '%d', '%f', '%d', '%s', '%s', '%d' );

		$exists = $wpdb->get_var(
			$wpdb->prepare( "SELECT vehicle_id FROM {$current} WHERE vehicle_id = %d", $vehicle_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		if ( $exists ) {
			$wpdb->update( $current, $data, array( 'vehicle_id' => $vehicle_id ), $formats, array( '%d' ) );
			return;
		}

		$data['vehicle_id'] = $vehicle_id;
		$formats[]          = '%d';
		$wpdb->insert( $current, $data, $formats );
	}

	/**
	 * ثبت رکورد تاریخچه.
	 *
	 * @param int         $vehicle_id     شناسه ردیف.
	 * @param int         $source_id      منبع.
	 * @param LKP_Car_Row $car            داده جدید.
	 * @param int|null    $factory_change تغییر کارخانه.
	 * @param int|null    $market_change  تغییر بازار.
	 * @param string      $note           علت.
	 * @param string|null $source_updated_at زمان اعلام منبع.
	 * @return int شناسه رکورد.
	 */
	public function append_history( int $vehicle_id, int $source_id, LKP_Car_Row $car, ?int $factory_change, ?int $market_change, string $note, ?string $source_updated_at ): int {
		global $wpdb;

		$history = LKP_Installer::table( 'price_history' );

		$wpdb->insert(
			$history,
			array(
				'vehicle_id'        => $vehicle_id,
				'source_id'         => $source_id,
				'factory_price'     => $car->factory,
				'market_price'      => $car->market,
				'factory_change'    => $factory_change,
				'market_change'     => $market_change,
				'recorded_at'       => current_time( 'mysql' ),
				'source_updated_at' => $this->normalize_datetime( $source_updated_at ),
				'note'              => $note,
			),
			array( '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * تاریخچه یک خودرو.
	 *
	 * @param int    $vehicle_id شناسه.
	 * @param string $from       از تاریخ (Y-m-d).
	 * @param string $to         تا تاریخ (Y-m-d).
	 * @param int    $limit      سقف.
	 * @return array<int, array<string, mixed>>
	 */
	public function history( int $vehicle_id, string $from = '', string $to = '', int $limit = 500 ): array {
		global $wpdb;

		$history = LKP_Installer::table( 'price_history' );
		$where   = array( 'vehicle_id = %d' );
		$params  = array( $vehicle_id );

		if ( '' !== $from ) {
			$where[]  = 'recorded_at >= %s';
			$params[] = $from . ' 00:00:00';
		}
		if ( '' !== $to ) {
			$where[]  = 'recorded_at <= %s';
			$params[] = $to . ' 23:59:59';
		}

		$params[] = max( 1, min( 2000, $limit ) );
		$sql      = "SELECT * FROM {$history} WHERE " . implode( ' AND ', $where ) . ' ORDER BY recorded_at DESC, id DESC LIMIT %d';

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return array_map(
			static function ( $row ) {
				return array(
					'id'             => (int) $row['id'],
					'factory_price'  => null === $row['factory_price'] ? null : (int) $row['factory_price'],
					'market_price'   => null === $row['market_price'] ? null : (int) $row['market_price'],
					'factory_change' => null === $row['factory_change'] ? null : (int) $row['factory_change'],
					'market_change'  => null === $row['market_change'] ? null : (int) $row['market_change'],
					'recorded_at'    => (string) $row['recorded_at'],
					'note'           => (string) $row['note'],
				);
			},
			(array) $rows
		);
	}

	/**
	 * سری زمانی نمودار (قدیمی → جدید).
	 *
	 * @param int $vehicle_id شناسه.
	 * @param int $days       تعداد روز.
	 * @return array{points:array<int,array<string,mixed>>,first_at:?string}
	 */
	public function chart_series( int $vehicle_id, int $days = 90 ): array {
		global $wpdb;

		$history  = LKP_Installer::table( 'price_history' );
		$timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
		$from     = ( new DateTimeImmutable( 'now', $timezone ) )
			->modify( '-' . max( 1, $days ) . ' days' )
			->format( 'Y-m-d 00:00:00' );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT recorded_at, factory_price, market_price FROM {$history}
				 WHERE vehicle_id = %d AND recorded_at >= %s
				 ORDER BY recorded_at ASC, id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$vehicle_id,
				$from
			),
			ARRAY_A
		);

		$points = array();
		foreach ( (array) $rows as $row ) {
			$points[] = array(
				'date'    => (string) $row['recorded_at'],
				'factory' => null === $row['factory_price'] ? null : (int) $row['factory_price'],
				'market'  => null === $row['market_price'] ? null : (int) $row['market_price'],
			);
		}

		$first = $wpdb->get_var(
			$wpdb->prepare( "SELECT MIN(recorded_at) FROM {$history} WHERE vehicle_id = %d", $vehicle_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return array(
			'points'   => $points,
			'first_at' => $first ? (string) $first : null,
		);
	}

	/**
	 * زمان آخرین دریافت موفق.
	 *
	 * @return string|null
	 */
	public function last_fetch(): ?string {
		global $wpdb;
		$current = LKP_Installer::table( 'current_prices' );
		$value   = $wpdb->get_var( "SELECT MAX(fetched_at) FROM {$current}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $value ? (string) $value : null;
	}

	/**
	 * آمار کلی برای داشبورد.
	 *
	 * @return array<string, int>
	 */
	public function stats(): array {
		global $wpdb;

		$current = LKP_Installer::table( 'current_prices' );
		$veh     = LKP_Installer::table( 'vehicles' );

		$total    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$veh} WHERE is_active = 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$priced   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$current} WHERE is_valid = 1 AND market_price IS NOT NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$missing  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$current} WHERE is_valid = 1 AND market_price IS NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$linked   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$veh} WHERE post_id IS NOT NULL AND post_id > 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$today_up = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$current} WHERE change_amount > 0 AND DATE(fetched_at) = CURDATE()" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$today_dn = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$current} WHERE change_amount < 0 AND DATE(fetched_at) = CURDATE()" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array(
			'total'    => $total,
			'priced'   => $priced,
			'missing'  => $missing,
			'linked'   => $linked,
			'today_up' => $today_up,
			'today_dn' => $today_dn,
		);
	}

	/**
	 * ثبت snapshot روزانه.
	 *
	 * @return int تعداد ثبت‌شده.
	 */
	public function snapshot_today(): int {
		global $wpdb;

		$current  = LKP_Installer::table( 'current_prices' );
		$snapshot = LKP_Installer::table( 'price_snapshots' );
		$today    = current_time( 'Y-m-d' );

		$done = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$snapshot} WHERE snapshot_date = %s", $today ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		if ( $done > 0 ) {
			return 0;
		}

		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$snapshot} (snapshot_date, vehicle_id, factory_price, market_price)
				 SELECT %s, vehicle_id, factory_price, market_price FROM {$current} WHERE is_valid = 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$today
			)
		);

		return (int) $result;
	}

	/**
	 * تبدیل ISO8601 منبع به datetime محلی وردپرس.
	 *
	 * @param string|null $value مقدار.
	 * @return string|null
	 */
	protected function normalize_datetime( ?string $value ): ?string {
		if ( ! $value ) {
			return null;
		}
		$timestamp = strtotime( $value );
		if ( ! $timestamp ) {
			return null;
		}
		return get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $timestamp ) );
	}
}

/**
 * مخزن لاگ همگام‌سازی.
 */
class LKP_Log_Repository {

	/**
	 * آغاز یک اجرا.
	 *
	 * @param string $trigger محرک.
	 * @return int شناسه لاگ.
	 */
	public function start( string $trigger ): int {
		global $wpdb;
		$table = LKP_Installer::table( 'sync_logs' );
		$wpdb->insert(
			$table,
			array(
				'started_at'   => current_time( 'mysql' ),
				'trigger_type' => $trigger,
				'status'       => 'running',
			),
			array( '%s', '%s', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * پایان یک اجرا.
	 *
	 * @param int                  $log_id شناسه.
	 * @param array<string, mixed> $data   داده‌ها.
	 * @return void
	 */
	public function finish( int $log_id, array $data ): void {
		global $wpdb;

		$table = LKP_Installer::table( 'sync_logs' );
		$wpdb->update(
			$table,
			array(
				'finished_at'   => current_time( 'mysql' ),
				'status'        => (string) ( $data['status'] ?? 'failed' ),
				'received'      => (int) ( $data['received'] ?? 0 ),
				'created'       => (int) ( $data['created'] ?? 0 ),
				'updated'       => (int) ( $data['updated'] ?? 0 ),
				'price_changed' => (int) ( $data['price_changed'] ?? 0 ),
				'errors'        => (int) ( $data['errors'] ?? 0 ),
				'duration_ms'   => (int) ( $data['duration_ms'] ?? 0 ),
				'error_details' => isset( $data['error_details'] ) ? mb_substr( (string) $data['error_details'], 0, 4000, 'UTF-8' ) : null,
			),
			array( 'id' => $log_id ),
			array( '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * لاگ‌های اخیر.
	 *
	 * @param int $limit تعداد.
	 * @return array<int, array<string, mixed>>
	 */
	public function recent( int $limit = 20 ): array {
		global $wpdb;
		$table = LKP_Installer::table( 'sync_logs' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", max( 1, $limit ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		return (array) $rows;
	}

	/**
	 * آخرین اجرای موفق.
	 *
	 * @return array<string, mixed>|null
	 */
	public function last_success(): ?array {
		global $wpdb;
		$table = LKP_Installer::table( 'sync_logs' );
		$row   = $wpdb->get_row(
			"SELECT * FROM {$table} WHERE status IN ('success','partial') ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		return $row ?: null;
	}
}
