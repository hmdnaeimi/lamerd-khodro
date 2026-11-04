<?php
/**
 * سرویس همگام‌سازی قیمت‌ها.
 *
 * @package LamerdKhodroPriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * اجرای دریافت، اعتبارسنجی و ذخیره داده قیمت.
 */
class LKP_Sync_Service {

	const LOCK_OPTION = 'lkp_sync_lock';
	const LOCK_TTL    = 600;

	/**
	 * @var LKP_Log_Repository
	 */
	protected LKP_Log_Repository $logs;

	/**
	 * سازنده.
	 */
	public function __construct() {
		$this->logs = new LKP_Log_Repository();
	}

	/**
	 * تنظیمات افزونه.
	 *
	 * @return array<string, mixed>
	 */
	public static function settings(): array {
		return wp_parse_args(
			(array) get_option( 'lkp_settings', array() ),
			array(
				'sync_interval'    => 60,
				'table_ids'        => array( 1 ),
				'enable_snapshots' => 1,
				'retention_days'   => 730,
			)
		);
	}

	/**
	 * آیا قفل فعال است؟
	 *
	 * @return bool
	 */
	public function is_locked(): bool {
		$lock = get_option( self::LOCK_OPTION );
		if ( ! $lock || ! is_array( $lock ) ) {
			return false;
		}
		return (int) ( $lock['expires_at'] ?? 0 ) > time();
	}

	/**
	 * اخذ قفل.
	 *
	 * @return bool
	 */
	protected function acquire_lock(): bool {
		if ( $this->is_locked() ) {
			return false;
		}

		update_option(
			self::LOCK_OPTION,
			array(
				'expires_at' => time() + self::LOCK_TTL,
				'owner'      => wp_generate_password( 12, false ),
			),
			false
		);

		return true;
	}

	/**
	 * آزادسازی قفل.
	 */
	protected function release_lock(): void {
		delete_option( self::LOCK_OPTION );
	}

	/**
	 * اجرای کامل همگام‌سازی.
	 *
	 * @param string $trigger محرک: cron | manual | retry.
	 * @return array<string, mixed> نتیجه.
	 */
	public function run( string $trigger = 'cron' ): array {
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 180 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		if ( ! $this->acquire_lock() ) {
			$log_id = $this->logs->start( $trigger );
			$this->logs->finish(
				$log_id,
				array(
					'status'        => 'locked',
					'errors'        => 1,
					'error_details' => 'اجرای دیگری در حال انجام است؛ این اجرا انجام نشد.',
				)
			);

			return array(
				'status'  => 'locked',
				'message' => __( 'همگام‌سازی دیگری در حال اجراست.', 'lamerd-khodro' ),
			);
		}

		$start   = microtime( true );
		$log_id  = $this->logs->start( $trigger );
		$stats   = array(
			'received'      => 0,
			'created'       => 0,
			'updated'       => 0,
			'price_changed' => 0,
			'errors'        => 0,
		);
		$errors  = array();
		$status  = 'failed';

		try {
			$settings   = self::settings();
			$table_ids  = array_values( array_filter( array_map( 'absint', (array) $settings['table_ids'] ) ) );
			if ( ! $table_ids ) {
				$table_ids = array( 1 );
			}

			$provider  = new LKP_CafeKhodro_Provider();
			$validator = new LKP_Validator();
			$vehicles  = new LKP_Vehicle_Repository();
			$prices    = new LKP_Price_Repository();
			$source_id = 1;

			foreach ( $table_ids as $table_id ) {
				try {
					$payload = $provider->fetch_table( $table_id );
				} catch ( LKP_Provider_Exception $e ) {
					$errors[] = sprintf( 'جدول %d: %s', $table_id, $e->getMessage() );
					$stats['errors']++;
					continue;
				}

				try {
					$payload = $validator->validate_payload( $payload );
				} catch ( LKP_Provider_Exception $e ) {
					$errors[] = sprintf( 'جدول %d نامعتبر: %s', $table_id, $e->getMessage() );
					$stats['errors']++;
					continue;
				}

				foreach ( $payload->warnings as $warning ) {
					$errors[] = $warning;
					$stats['errors']++;
				}

				$stats['received'] += count( $payload->cars );

				foreach ( $payload->cars as $car ) {
					$upsert = $vehicles->upsert( $car, $source_id );
					if ( $upsert['created'] ) {
						$stats['created']++;
					}

					$previous = $prices->get_raw_current( $upsert['id'] );
					$changes  = $this->compare( $previous, $car );

					if ( $changes['changed'] ) {
						$prices->append_history(
							$upsert['id'],
							$source_id,
							$car,
							$changes['factory_change'],
							$changes['market_change'],
							$changes['note'],
							$payload->source_updated_at
						);
						$stats['price_changed']++;
						if ( ! $upsert['created'] ) {
							$stats['updated']++;
						}
					}

					$prices->save_current( $upsert['id'], $source_id, $car, $payload->source_updated_at );
				}

				$this->touch_source_success( $source_id );
			}

			if ( ! empty( $settings['enable_snapshots'] ) ) {
				$prices->snapshot_today();
			}

			$this->prune( (int) $settings['retention_days'] );

			$status = $stats['errors'] > 0 ? 'partial' : 'success';
			if ( 0 === $stats['received'] ) {
				$status = 'failed';
			}
		} catch ( Throwable $e ) {
			$errors[] = 'خطای غیرمنتظره: ' . $e->getMessage();
			$stats['errors']++;
			$status = 'failed';
		} finally {
			$duration = (int) round( ( microtime( true ) - $start ) * 1000 );

			$this->logs->finish(
				$log_id,
				array(
					'status'        => $status,
					'received'      => $stats['received'],
					'created'       => $stats['created'],
					'updated'       => $stats['updated'],
					'price_changed' => $stats['price_changed'],
					'errors'        => $stats['errors'],
					'duration_ms'   => $duration,
					'error_details' => $errors ? implode( "\n", array_slice( $errors, 0, 50 ) ) : null,
				)
			);

			$this->release_lock();
			delete_transient( 'lkp_dashboard_snapshot' );

			if ( 'failed' === $status && $errors ) {
				$this->touch_source_error( implode( ' | ', array_slice( $errors, 0, 5 ) ) );
			}
		}

		return array(
			'status' => $status,
			'stats'  => $stats,
			'errors' => $errors,
		);
	}

	/**
	 * مقایسه داده جدید با آخرین قیمت ثبت‌شده.
	 *
	 * @param array<string, mixed>|null $previous ردیف قبلی.
	 * @param LKP_Car_Row               $car      داده جدید.
	 * @return array{changed:bool,factory_change:?int,market_change:?int,note:string}
	 */
	protected function compare( ?array $previous, LKP_Car_Row $car ): array {
		if ( ! $previous ) {
			return array(
				'changed'        => true,
				'factory_change' => null,
				'market_change'  => null,
				'note'           => 'initial',
			);
		}

		$prev_factory = null === $previous['factory_price'] ? null : (int) $previous['factory_price'];
		$prev_market  = null === $previous['market_price'] ? null : (int) $previous['market_price'];

		$factory_changed = $prev_factory !== $car->factory;
		$market_changed  = $prev_market !== $car->market;

		if ( ! $factory_changed && ! $market_changed ) {
			return array(
				'changed'        => false,
				'factory_change' => null,
				'market_change'  => null,
				'note'           => 'unchanged',
			);
		}

		$factory_change = ( null !== $prev_factory && null !== $car->factory ) ? ( $car->factory - $prev_factory ) : null;
		$market_change  = ( null !== $prev_market && null !== $car->market ) ? ( $car->market - $prev_market ) : null;

		$note = 'price_change';
		if ( ( null === $prev_factory && null !== $car->factory ) || ( null === $prev_market && null !== $car->market ) ) {
			$note = 'price_available';
		} elseif ( ( null !== $prev_factory && null === $car->factory ) || ( null !== $prev_market && null === $car->market ) ) {
			$note = 'price_removed';
		}

		return array(
			'changed'        => true,
			'factory_change' => $factory_change,
			'market_change'  => $market_change,
			'note'           => $note,
		);
	}

	/**
	 * ثبت موفقیت منبع.
	 *
	 * @param int $source_id منبع.
	 */
	protected function touch_source_success( int $source_id ): void {
		global $wpdb;
		$table = LKP_Installer::table( 'price_sources' );
		$wpdb->update(
			$table,
			array(
				'last_success_at' => current_time( 'mysql' ),
				'last_attempt_at' => current_time( 'mysql' ),
				'last_error'      => null,
			),
			array( 'id' => $source_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * ثبت خطای منبع (بدون اطلاعات حساس).
	 *
	 * @param string $message پیام.
	 */
	protected function touch_source_error( string $message ): void {
		global $wpdb;
		$table = LKP_Installer::table( 'price_sources' );
		$wpdb->update(
			$table,
			array(
				'last_attempt_at' => current_time( 'mysql' ),
				'last_error'      => mb_substr( $message, 0, 1000, 'UTF-8' ),
			),
			array( 'id' => 1 ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * حذف snapshotها و لاگ‌های قدیمی (تاریخچه قیمت هرگز حذف نمی‌شود).
	 *
	 * @param int $retention_days تعداد روز نگهداری.
	 */
	protected function prune( int $retention_days ): void {
		global $wpdb;

		$days     = max( 30, $retention_days );
		$timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
		$cut      = ( new DateTimeImmutable( 'now', $timezone ) )
			->modify( '-' . $days . ' days' )
			->format( 'Y-m-d' );

		$snapshot = LKP_Installer::table( 'price_snapshots' );
		$logs     = LKP_Installer::table( 'sync_logs' );

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$snapshot} WHERE snapshot_date < %s", $cut ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$logs} WHERE started_at < %s", $cut . ' 00:00:00' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * اطلاعات منبع برای نمایش.
	 *
	 * @return array<string, mixed>|null
	 */
	public function source(): ?array {
		global $wpdb;
		$table = LKP_Installer::table( 'price_sources' );
		$row   = $wpdb->get_row( "SELECT * FROM {$table} ORDER BY id ASC LIMIT 1", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ?: null;
	}

	/**
	 * وضعیت سلامت منبع.
	 *
	 * @return array{state:string,label:string,last_success:?string,last_error:?string}
	 */
	public function health(): array {
		$source = $this->source();

		if ( ! $source ) {
			return array(
				'state'        => 'never',
				'label'        => __( 'بدون منبع', 'lamerd-khodro' ),
				'last_success' => null,
				'last_error'   => null,
			);
		}

		$last_success = $source['last_success_at'] ?: null;
		$state        = 'never';
		$label        = __( 'هرگز دریافت نشده', 'lamerd-khodro' );

		if ( $last_success ) {
			$timestamp = strtotime( $last_success );
			$age       = $timestamp ? ( time() - $timestamp ) : PHP_INT_MAX;
			$interval  = (int) ( self::settings()['sync_interval'] ?? 60 ) * MINUTE_IN_SECONDS;

			if ( $age <= ( 2 * max( $interval, MINUTE_IN_SECONDS * 15 ) ) ) {
				$state = 'ok';
				$label = __( 'سالم', 'lamerd-khodro' );
			} else {
				$state = 'stale';
				$label = __( 'داده قدیمی', 'lamerd-khodro' );
			}
		} elseif ( $source['last_error'] ) {
			$state = 'error';
			$label = __( 'خطا در دریافت', 'lamerd-khodro' );
		}

		return array(
			'state'        => $state,
			'label'        => $label,
			'last_success' => $last_success,
			'last_error'   => $source['last_error'] ?: null,
		);
	}
}
