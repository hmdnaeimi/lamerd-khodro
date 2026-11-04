<?php
/**
 * رندر صفحات پیشخوان موتور قیمت.
 *
 * @package LamerdKhodroPriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * صفحات مدیریت.
 */
class LKP_Admin_Pages {

	/**
	 * بررسی دسترسی صفحه.
	 */
	protected static function guard(): void {
		if ( ! current_user_can( LKP_Installer::CAPABILITY ) ) {
			wp_die( esc_html__( 'دسترسی مجاز نیست.', 'lamerd-khodro' ), 403 );
		}
	}

	/**
	 * نمایش پیام نتیجه عملیات.
	 */
	protected static function notice(): void {
		$done = isset( $_GET['lkp_done'] ) ? sanitize_key( wp_unslash( $_GET['lkp_done'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $done ) {
			return;
		}

		$map = array(
			'success'     => array( 'ok', __( 'همگام‌سازی با موفقیت انجام شد.', 'lamerd-khodro' ) ),
			'partial'     => array( 'warn', __( 'همگام‌سازی انجام شد، اما برخی ردیف‌ها هشدار داشتند.', 'lamerd-khodro' ) ),
			'failed'      => array( 'bad', __( 'همگام‌سازی ناموفق بود؛ داده قبلی حفظ شد. جزئیات در صفحه همگام‌سازی.', 'lamerd-khodro' ) ),
			'locked'      => array( 'warn', __( 'همگام‌سازی دیگری در حال اجراست.', 'lamerd-khodro' ) ),
			'saved'       => array( 'ok', __( 'تنظیمات ذخیره شد.', 'lamerd-khodro' ) ),
			'linked'      => array( 'ok', __( 'اتصال خودرو انجام شد.', 'lamerd-khodro' ) ),
			'unlinked'    => array( 'ok', __( 'اتصال خودرو قطع شد.', 'lamerd-khodro' ) ),
			'dropped'     => array( 'bad', __( 'همه جداول و داده‌های موتور قیمت حذف شد.', 'lamerd-khodro' ) ),
			'drop_aborted' => array( 'warn', __( 'حذف داده‌ها انجام نشد؛ عبارت تأیید مطابقت نداشت.', 'lamerd-khodro' ) ),
		);

		$item = $map[ $done ] ?? array( 'ok', __( 'عملیات انجام شد.', 'lamerd-khodro' ) );
		$class = 'lkp-notice-inline' . ( 'ok' === $item[0] ? ' lkp-notice-inline--ok' : ( 'bad' === $item[0] ? ' lkp-notice-inline--bad' : '' ) );

		echo '<div class="' . esc_attr( $class ) . '">' . esc_html( $item[1] ) . '</div>';
	}

	/**
	 * دکمه اجرای همگام‌سازی.
	 */
	protected static function sync_button( string $label = '' ): void {
		$label = $label ?: __( 'دریافت دستی قیمت‌ها', 'lamerd-khodro' );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
			<input type="hidden" name="action" value="lkp_run_sync" />
			<?php wp_nonce_field( 'lkp_run_sync' ); ?>
			<button type="submit" class="button button-primary"><?php echo esc_html( $label ); ?></button>
		</form>
		<?php
	}

	/**
	 * داشبورد.
	 */
	public static function dashboard(): void {
		self::guard();

		$engine = lamerd_khodro_price_engine();
		$prices = $engine->prices();
		$stats  = $prices->stats();
		$health = $engine->sync()->health();
		$cron   = LKP_Scheduler::info();
		$last   = $prices->last_fetch();
		$logs   = ( new LKP_Log_Repository() )->recent( 8 );
		$total_days = 0;
		?>
		<div class="wrap lkp-wrap">
			<h1><?php esc_html_e( 'داشبورد موتور قیمت', 'lamerd-khodro' ); ?></h1>
			<?php self::notice(); ?>

			<div class="lkp-cards">
				<div class="lkp-card">
					<div class="lkp-card__label"><?php esc_html_e( 'خودروهای منبع', 'lamerd-khodro' ); ?></div>
					<div class="lkp-card__value"><?php echo esc_html( lkp_fa_digits( number_format_i18n( $stats['total'] ) ) ); ?></div>
				</div>
				<div class="lkp-card lkp-card--ok">
					<div class="lkp-card__label"><?php esc_html_e( 'قیمت معتبر', 'lamerd-khodro' ); ?></div>
					<div class="lkp-card__value"><?php echo esc_html( lkp_fa_digits( number_format_i18n( $stats['priced'] ) ) ); ?></div>
				</div>
				<div class="lkp-card<?php echo $stats['missing'] > 0 ? ' lkp-card--alert' : ''; ?>">
					<div class="lkp-card__label"><?php esc_html_e( 'قیمت ناموجود', 'lamerd-khodro' ); ?></div>
					<div class="lkp-card__value"><?php echo esc_html( lkp_fa_digits( number_format_i18n( $stats['missing'] ) ) ); ?></div>
				</div>
				<div class="lkp-card">
					<div class="lkp-card__label"><?php esc_html_e( 'متصل به بانک اطلاعات', 'lamerd-khodro' ); ?></div>
					<div class="lkp-card__value"><?php echo esc_html( lkp_fa_digits( number_format_i18n( $stats['linked'] ) ) ); ?></div>
				</div>
				<div class="lkp-card">
					<div class="lkp-card__label"><?php esc_html_e( 'افزایش امروز', 'lamerd-khodro' ); ?></div>
					<div class="lkp-card__value lkp-up"><?php echo esc_html( lkp_fa_digits( number_format_i18n( $stats['today_up'] ) ) ); ?></div>
				</div>
				<div class="lkp-card">
					<div class="lkp-card__label"><?php esc_html_e( 'کاهش امروز', 'lamerd-khodro' ); ?></div>
					<div class="lkp-card__value lkp-down"><?php echo esc_html( lkp_fa_digits( number_format_i18n( $stats['today_dn'] ) ) ); ?></div>
				</div>
			</div>

			<h2><?php esc_html_e( 'وضعیت منبع و زمان‌بندی', 'lamerd-khodro' ); ?></h2>
			<table class="widefat striped" style="max-width:820px">
				<tbody>
					<tr>
						<td style="width:220px"><?php esc_html_e( 'وضعیت API', 'lamerd-khodro' ); ?></td>
						<td><?php echo wp_kses_post( lkp_badge( $health['state'], $health['label'] ) ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'آخرین دریافت موفق', 'lamerd-khodro' ); ?></td>
						<td><?php echo esc_html( lkp_jalali( $health['last_success'] ) ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'آخرین داده ثبت‌شده', 'lamerd-khodro' ); ?></td>
						<td><?php echo esc_html( lkp_jalali( $last ) ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'اجرای بعدی', 'lamerd-khodro' ); ?></td>
						<td><?php echo esc_html( $cron['next'] ? lkp_jalali( $cron['next'] ) : __( 'زمان‌بندی نشده', 'lamerd-khodro' ) ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'فاصله همگام‌سازی', 'lamerd-khodro' ); ?></td>
						<td><?php echo esc_html( lkp_fa_digits( (string) $cron['interval'] ) . ' ' . __( 'دقیقه', 'lamerd-khodro' ) ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'قفل اجرا', 'lamerd-khodro' ); ?></td>
						<td>
							<?php
							echo wp_kses_post(
								$engine->sync()->is_locked()
									? lkp_badge( 'warn', __( 'قفل است (اجرا در حال انجام)', 'lamerd-khodro' ) )
									: lkp_badge( 'ok', __( 'آزاد', 'lamerd-khodro' ) )
							);
							?>
						</td>
					</tr>
					<?php if ( $health['last_error'] ) : ?>
						<tr>
							<td><?php esc_html_e( 'آخرین خطا', 'lamerd-khodro' ); ?></td>
							<td><code style="direction:ltr;display:inline-block"><?php echo esc_html( $health['last_error'] ); ?></code></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<p style="margin-top:16px"><?php self::sync_button(); ?></p>

			<h2><?php esc_html_e( 'آخرین اجراها', 'lamerd-khodro' ); ?></h2>
			<div class="lkp-table-wrap">
				<table class="lkp-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'زمان', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'محرک', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'وضعیت', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'دریافتی', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'تغییر قیمت', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'خطا', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'مدت', 'lamerd-khodro' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! $logs ) : ?>
							<tr><td colspan="7" class="lkp-muted"><?php esc_html_e( 'هنوز اجرایی ثبت نشده است.', 'lamerd-khodro' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $logs as $log ) : ?>
								<tr>
									<td><?php echo esc_html( lkp_jalali( (string) $log['started_at'] ) ); ?></td>
									<td><?php echo esc_html( lkp_trigger_label( (string) $log['trigger_type'] ) ); ?></td>
									<td><?php echo wp_kses_post( lkp_badge( (string) $log['status'], lkp_status_label( (string) $log['status'] ) ) ); ?></td>
									<td class="num"><?php echo esc_html( lkp_fa_digits( (string) (int) $log['received'] ) ); ?></td>
									<td class="num"><?php echo esc_html( lkp_fa_digits( (string) (int) $log['price_changed'] ) ); ?></td>
									<td class="num"><?php echo esc_html( lkp_fa_digits( (string) (int) $log['errors'] ) ); ?></td>
									<td class="num"><?php echo esc_html( lkp_fa_digits( (string) (int) $log['duration_ms'] ) . ' ms' ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<p class="lkp-hint">
				<?php esc_html_e( 'همه اعداد این داشبورد از دیتابیس داخلی خوانده می‌شوند؛ قطع API نمایش سایت را متوقف نمی‌کند.', 'lamerd-khodro' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * فهرست خودروهای منبع و اتصال به CPT.
	 */
	public static function vehicles(): void {
		self::guard();

		$repo = new LKP_Vehicle_Repository();

		$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$group    = isset( $_GET['group'] ) ? sanitize_text_field( wp_unslash( $_GET['group'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$filter   = isset( $_GET['link'] ) ? sanitize_key( wp_unslash( $_GET['link'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page     = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page = 25;

		$result = $repo->paginate(
			array(
				'search'        => $search,
				'group'         => $group,
				'page'          => $page,
				'per_page'      => $per_page,
				'only_unlinked' => 'unlinked' === $filter,
				'only_linked'   => 'linked' === $filter,
			)
		);

		$pages    = max( 1, (int) ceil( $result['total'] / $per_page ) );
		$core     = function_exists( 'lamerd_khodro_core' );
		$groups   = $repo->groups();
		?>
		<div class="wrap lkp-wrap">
			<h1><?php esc_html_e( 'خودروهای منبع و اتصال به بانک اطلاعات', 'lamerd-khodro' ); ?></h1>
			<?php self::notice(); ?>

			<form method="get" class="lkp-filters">
				<input type="hidden" name="page" value="lkp-vehicles" />
				<label>
					<?php esc_html_e( 'جستجو', 'lamerd-khodro' ); ?>
					<input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'نام خودرو یا گروه', 'lamerd-khodro' ); ?>" />
				</label>
				<label>
					<?php esc_html_e( 'گروه', 'lamerd-khodro' ); ?>
					<select name="group">
						<option value=""><?php esc_html_e( 'همه', 'lamerd-khodro' ); ?></option>
						<?php foreach ( $groups as $item ) : ?>
							<option value="<?php echo esc_attr( $item['name'] ); ?>" <?php selected( $group, $item['name'] ); ?>>
								<?php echo esc_html( $item['name'] . ' (' . lkp_fa_digits( (string) $item['count'] ) . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<?php esc_html_e( 'وضعیت اتصال', 'lamerd-khodro' ); ?>
					<select name="link">
						<option value=""><?php esc_html_e( 'همه', 'lamerd-khodro' ); ?></option>
						<option value="linked" <?php selected( $filter, 'linked' ); ?>><?php esc_html_e( 'متصل', 'lamerd-khodro' ); ?></option>
						<option value="unlinked" <?php selected( $filter, 'unlinked' ); ?>><?php esc_html_e( 'بدون اتصال', 'lamerd-khodro' ); ?></option>
					</select>
				</label>
				<button type="submit" class="button"><?php esc_html_e( 'فیلتر', 'lamerd-khodro' ); ?></button>
			</form>

			<?php if ( ! $core ) : ?>
				<div class="lkp-notice-inline">
					<?php esc_html_e( 'افزونه «Lamerd Khodro Core» فعال نیست؛ اتصال به بانک اطلاعات خودرو ممکن نیست.', 'lamerd-khodro' ); ?>
				</div>
			<?php endif; ?>

			<div class="lkp-table-wrap">
				<table class="lkp-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'شناسه', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'نام خودرو', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'گروه', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'قیمت بازار', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'اتصال', 'lamerd-khodro' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! $result['items'] ) : ?>
							<tr><td colspan="5" class="lkp-muted"><?php esc_html_e( 'خودرویی یافت نشد. ابتدا همگام‌سازی را اجرا کنید.', 'lamerd-khodro' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $result['items'] as $item ) : ?>
								<?php
								$post_id = (int) ( $item['post_id'] ?? 0 );
								$suggest = 0;
								if ( $core && ! $post_id ) {
									$suggest = lamerd_khodro_core()->vehicles()->find_by_name( (string) $item['name'] );
								}
								?>
								<tr>
									<td class="num"><?php echo esc_html( lkp_fa_digits( (string) (int) $item['id'] ) ); ?></td>
									<td><?php echo esc_html( (string) $item['name'] ); ?></td>
									<td><?php echo esc_html( (string) $item['brand_group'] ); ?></td>
									<td class="num"><?php echo esc_html( lkp_format_amount( null === $item['market_price'] ? null : (int) $item['market_price'] ) ); ?></td>
									<td>
										<?php if ( $post_id ) : ?>
											<a href="<?php echo esc_url( (string) get_edit_post_link( $post_id ) ); ?>"><?php echo esc_html( (string) get_the_title( $post_id ) ); ?></a>
											<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
												<input type="hidden" name="action" value="lkp_link_vehicle" />
												<input type="hidden" name="lkp_vehicle_id" value="<?php echo esc_attr( (string) $item['id'] ); ?>" />
												<input type="hidden" name="lkp_action" value="unlink" />
												<?php wp_nonce_field( 'lkp_link_vehicle' ); ?>
												<button type="submit" class="button button-small"><?php esc_html_e( 'قطع اتصال', 'lamerd-khodro' ); ?></button>
											</form>
										<?php elseif ( $suggest ) : ?>
											<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
												<input type="hidden" name="action" value="lkp_link_vehicle" />
												<input type="hidden" name="lkp_vehicle_id" value="<?php echo esc_attr( (string) $item['id'] ); ?>" />
												<input type="hidden" name="lkp_post_id" value="<?php echo esc_attr( (string) $suggest ); ?>" />
												<?php wp_nonce_field( 'lkp_link_vehicle' ); ?>
												<button type="submit" class="button button-small button-primary">
													<?php echo esc_html( sprintf( __( 'اتصال به «%s»', 'lamerd-khodro' ), get_the_title( $suggest ) ) ); ?>
												</button>
											</form>
										<?php else : ?>
											<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:4px;align-items:center">
												<input type="hidden" name="action" value="lkp_link_vehicle" />
												<input type="hidden" name="lkp_vehicle_id" value="<?php echo esc_attr( (string) $item['id'] ); ?>" />
												<input type="number" name="lkp_post_id" min="1" placeholder="<?php esc_attr_e( 'شناسه نوشته', 'lamerd-khodro' ); ?>" style="width:110px" />
												<?php wp_nonce_field( 'lkp_link_vehicle' ); ?>
												<button type="submit" class="button button-small"><?php esc_html_e( 'اتصال', 'lamerd-khodro' ); ?></button>
											</form>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<div class="lkp-pagination">
				<span class="lkp-muted">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: total, 2: page, 3: pages */
							__( 'مجموع: %1$s — صفحه %2$s از %3$s', 'lamerd-khodro' ),
							number_format_i18n( $result['total'] ),
							number_format_i18n( $page ),
							number_format_i18n( $pages )
						)
					);
					?>
				</span>
				<?php if ( $page > 1 ) : ?>
					<a class="button button-small" href="<?php echo esc_url( add_query_arg( 'paged', $page - 1 ) ); ?>"><?php esc_html_e( 'قبلی', 'lamerd-khodro' ); ?></a>
				<?php endif; ?>
				<?php if ( $page < $pages ) : ?>
					<a class="button button-small" href="<?php echo esc_url( add_query_arg( 'paged', $page + 1 ) ); ?>"><?php esc_html_e( 'بعدی', 'lamerd-khodro' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * فهرست آخرین قیمت‌ها.
	 */
	public static function prices(): void {
		self::guard();

		$repo = new LKP_Vehicle_Repository();

		$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby  = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'market'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order    = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'desc'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page     = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page = 50;

		$result = $repo->paginate(
			array(
				'search'   => $search,
				'orderby'  => in_array( $orderby, array( 'name', 'market', 'change', 'group', 'id' ), true ) ? $orderby : 'market',
				'order'    => $order,
				'page'     => $page,
				'per_page' => $per_page,
			)
		);

		$pages = max( 1, (int) ceil( $result['total'] / $per_page ) );
		?>
		<div class="wrap lkp-wrap">
			<h1><?php esc_html_e( 'آخرین قیمت‌ها', 'lamerd-khodro' ); ?></h1>
			<?php self::notice(); ?>

			<form method="get" class="lkp-filters">
				<input type="hidden" name="page" value="lkp-prices" />
				<label>
					<?php esc_html_e( 'جستجو', 'lamerd-khodro' ); ?>
					<input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" />
				</label>
				<label>
					<?php esc_html_e( 'ترتیب', 'lamerd-khodro' ); ?>
					<select name="orderby">
						<option value="market" <?php selected( $orderby, 'market' ); ?>><?php esc_html_e( 'قیمت بازار', 'lamerd-khodro' ); ?></option>
						<option value="change" <?php selected( $orderby, 'change' ); ?>><?php esc_html_e( 'درصد تغییر', 'lamerd-khodro' ); ?></option>
						<option value="name" <?php selected( $orderby, 'name' ); ?>><?php esc_html_e( 'نام', 'lamerd-khodro' ); ?></option>
						<option value="group" <?php selected( $orderby, 'group' ); ?>><?php esc_html_e( 'گروه', 'lamerd-khodro' ); ?></option>
					</select>
				</label>
				<label>
					<?php esc_html_e( 'جهت', 'lamerd-khodro' ); ?>
					<select name="order">
						<option value="desc" <?php selected( $order, 'desc' ); ?>><?php esc_html_e( 'نزولی', 'lamerd-khodro' ); ?></option>
						<option value="asc" <?php selected( $order, 'asc' ); ?>><?php esc_html_e( 'صعودی', 'lamerd-khodro' ); ?></option>
					</select>
				</label>
				<button type="submit" class="button"><?php esc_html_e( 'اعمال', 'lamerd-khodro' ); ?></button>
			</form>

			<div class="lkp-table-wrap">
				<table class="lkp-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'خودرو', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'گروه', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'قیمت کارخانه', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'قیمت بازار', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'تغییر', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'درصد', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'آخرین دریافت', 'lamerd-khodro' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! $result['items'] ) : ?>
							<tr><td colspan="7" class="lkp-muted"><?php esc_html_e( 'داده‌ای موجود نیست.', 'lamerd-khodro' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $result['items'] as $item ) : ?>
								<?php
								$change  = isset( $item['change_percent'] ) && null !== $item['change_percent'] ? (float) $item['change_percent'] : null;
								$amount  = isset( $item['change_amount'] ) && null !== $item['change_amount'] ? (int) $item['change_amount'] : null;
								$class   = null === $item['market_price'] ? 'lkp-muted' : '';
								?>
								<tr>
									<td><?php echo esc_html( (string) $item['name'] ); ?></td>
									<td><?php echo esc_html( (string) $item['brand_group'] ); ?></td>
									<td class="num"><?php echo esc_html( lkp_format_amount( null === $item['factory_price'] ? null : (int) $item['factory_price'] ) ); ?></td>
									<td class="num <?php echo esc_attr( $class ); ?>"><?php echo esc_html( lkp_format_amount( null === $item['market_price'] ? null : (int) $item['market_price'] ) ); ?></td>
									<td class="num <?php echo null !== $amount && $amount > 0 ? 'lkp-up' : ( null !== $amount && $amount < 0 ? 'lkp-down' : 'lkp-muted' ); ?>">
										<?php echo esc_html( lkp_format_change( $amount ) ); ?>
									</td>
									<td class="num">
										<?php if ( null === $change ) : ?>
											<span class="lkp-muted">—</span>
										<?php else : ?>
											<span class="<?php echo $change > 0 ? 'lkp-up' : ( $change < 0 ? 'lkp-down' : 'lkp-muted' ); ?>">
												<?php echo esc_html( ( $change > 0 ? '+' : '' ) . lkp_fa_digits( number_format( $change, 2 ) ) . '٪' ); ?>
											</span>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( lkp_jalali( (string) $item['fetched_at'] ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<div class="lkp-pagination">
				<span class="lkp-muted">
					<?php
					echo esc_html(
						sprintf(
							__( 'مجموع: %1$s — صفحه %2$s از %3$s', 'lamerd-khodro' ),
							number_format_i18n( $result['total'] ),
							number_format_i18n( $page ),
							number_format_i18n( $pages )
						)
					);
					?>
				</span>
				<?php if ( $page < $pages ) : ?>
					<a class="button button-small" href="<?php echo esc_url( add_query_arg( 'paged', $page + 1 ) ); ?>"><?php esc_html_e( 'بعدی', 'lamerd-khodro' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * تاریخچه قیمت یک خودرو.
	 */
	public static function history(): void {
		self::guard();

		$vehicles = new LKP_Vehicle_Repository();
		$prices   = new LKP_Price_Repository();

		$vehicle_id = isset( $_GET['vehicle_id'] ) ? absint( wp_unslash( $_GET['vehicle_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$from       = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$to         = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$list = $vehicles->paginate( array( 'per_page' => 300, 'orderby' => 'name', 'order' => 'ASC' ) );
		$rows = $vehicle_id ? $prices->history( $vehicle_id, $from, $to ) : array();
		$info = $vehicle_id ? $vehicles->find( $vehicle_id ) : null;
		$current = $vehicle_id ? $prices->get_current( $vehicle_id ) : null;
		?>
		<div class="wrap lkp-wrap">
			<h1><?php esc_html_e( 'تاریخچه قیمت', 'lamerd-khodro' ); ?></h1>
			<?php self::notice(); ?>

			<form method="get" class="lkp-filters">
				<input type="hidden" name="page" value="lkp-history" />
				<label>
					<?php esc_html_e( 'خودرو', 'lamerd-khodro' ); ?>
					<select name="vehicle_id">
						<option value=""><?php esc_html_e( '— انتخاب —', 'lamerd-khodro' ); ?></option>
						<?php foreach ( $list['items'] as $item ) : ?>
							<option value="<?php echo esc_attr( (string) $item['id'] ); ?>" <?php selected( $vehicle_id, (int) $item['id'] ); ?>>
								<?php echo esc_html( (string) $item['name'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<?php esc_html_e( 'از تاریخ', 'lamerd-khodro' ); ?>
					<input type="date" name="from" value="<?php echo esc_attr( $from ); ?>" />
				</label>
				<label>
					<?php esc_html_e( 'تا تاریخ', 'lamerd-khodro' ); ?>
					<input type="date" name="to" value="<?php echo esc_attr( $to ); ?>" />
				</label>
				<button type="submit" class="button"><?php esc_html_e( 'نمایش', 'lamerd-khodro' ); ?></button>
			</form>

			<?php if ( $info ) : ?>
				<h2>
					<?php echo esc_html( (string) $info['name'] ); ?>
					<span class="lkp-muted" style="font-size:13px">— <?php echo esc_html( (string) $info['brand_group'] ); ?></span>
				</h2>

				<?php if ( $current ) : ?>
					<div class="lkp-summary">
						<span><strong><?php esc_html_e( 'قیمت کارخانه:', 'lamerd-khodro' ); ?></strong> <?php echo esc_html( lkp_format_amount( $current['factory_price'] ) ); ?></span>
						<span><strong><?php esc_html_e( 'قیمت بازار:', 'lamerd-khodro' ); ?></strong> <?php echo esc_html( lkp_format_amount( $current['market_price'] ) ); ?></span>
						<span><strong><?php esc_html_e( 'آخرین دریافت:', 'lamerd-khodro' ); ?></strong> <?php echo esc_html( lkp_jalali( $current['fetched_at'] ) ); ?></span>
					</div>
				<?php endif; ?>

				<div class="lkp-table-wrap">
					<table class="lkp-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'زمان ثبت', 'lamerd-khodro' ); ?></th>
								<th><?php esc_html_e( 'قیمت کارخانه', 'lamerd-khodro' ); ?></th>
								<th><?php esc_html_e( 'قیمت بازار', 'lamerd-khodro' ); ?></th>
								<th><?php esc_html_e( 'تغییر کارخانه', 'lamerd-khodro' ); ?></th>
								<th><?php esc_html_e( 'تغییر بازار', 'lamerd-khodro' ); ?></th>
								<th><?php esc_html_e( 'علت ثبت', 'lamerd-khodro' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( ! $rows ) : ?>
								<tr><td colspan="6" class="lkp-muted"><?php esc_html_e( 'در این بازه تغییری ثبت نشده است.', 'lamerd-khodro' ); ?></td></tr>
							<?php else : ?>
								<?php foreach ( $rows as $row ) : ?>
									<tr>
										<td><?php echo esc_html( lkp_jalali( (string) $row['recorded_at'] ) ); ?></td>
										<td class="num"><?php echo esc_html( lkp_format_amount( $row['factory_price'] ) ); ?></td>
										<td class="num"><?php echo esc_html( lkp_format_amount( $row['market_price'] ) ); ?></td>
										<td class="num <?php echo null !== $row['factory_change'] && $row['factory_change'] < 0 ? 'lkp-down' : ( null !== $row['factory_change'] && $row['factory_change'] > 0 ? 'lkp-up' : '' ); ?>">
											<?php echo esc_html( lkp_format_change( $row['factory_change'] ) ); ?>
										</td>
										<td class="num <?php echo null !== $row['market_change'] && $row['market_change'] < 0 ? 'lkp-down' : ( null !== $row['market_change'] && $row['market_change'] > 0 ? 'lkp-up' : '' ); ?>">
											<?php echo esc_html( lkp_format_change( $row['market_change'] ) ); ?>
										</td>
										<td>
											<?php
											$notes = array(
												'initial'         => __( 'اولین ثبت', 'lamerd-khodro' ),
												'price_change'    => __( 'تغییر قیمت', 'lamerd-khodro' ),
												'price_available' => __( 'ثبت قیمت جدید', 'lamerd-khodro' ),
												'price_removed'   => __( 'حذف قیمت', 'lamerd-khodro' ),
											);
											echo esc_html( $notes[ (string) $row['note'] ] ?? (string) $row['note'] );
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<p class="lkp-hint">
					<?php esc_html_e( 'تاریخچه فقط از زمان راه‌اندازی موتور قیمت ثبت می‌شود؛ منبع داده تاریخچه گذشته ندارد.', 'lamerd-khodro' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * گزارش‌ها + خروجی.
	 */
	public static function reports(): void {
		self::guard();

		$repo    = lamerd_khodro_price_engine()->reports();
		$keys    = $repo->keys();
		$ranges  = $repo->ranges();
		$groups  = ( new LKP_Vehicle_Repository() )->groups();

		$key   = isset( $_GET['report'] ) ? sanitize_key( wp_unslash( $_GET['report'] ) ) : 'daily'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$key   = isset( $keys[ $key ] ) ? $key : 'daily';
		$range = isset( $_GET['range'] ) ? sanitize_key( wp_unslash( $_GET['range'] ) ) : '30d'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$range = isset( $ranges[ $range ] ) ? $range : '30d';

		$args = array(
			'range'      => $range,
			'from'       => isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'to'         => isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'brand'      => isset( $_GET['brand'] ) ? sanitize_text_field( wp_unslash( $_GET['brand'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'vehicle_id' => isset( $_GET['vehicle_id'] ) ? absint( wp_unslash( $_GET['vehicle_id'] ) ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'price_type' => ( isset( $_GET['price_type'] ) && 'factory' === $_GET['price_type'] ) ? 'factory' : 'market', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		);

		$data = $repo->get( $key, $args );

		$export_base = array(
			'report'     => $key,
			'range'      => $range,
			'from'       => $args['from'],
			'to'         => $args['to'],
			'brand'      => $args['brand'],
			'vehicle_id' => $args['vehicle_id'],
			'price_type' => $args['price_type'],
		);
		?>
		<div class="wrap lkp-wrap">
			<h1><?php esc_html_e( 'گزارش‌های قیمت', 'lamerd-khodro' ); ?></h1>
			<?php self::notice(); ?>

			<form method="get" class="lkp-filters">
				<input type="hidden" name="page" value="lkp-reports" />
				<label>
					<?php esc_html_e( 'گزارش', 'lamerd-khodro' ); ?>
					<select name="report">
						<?php foreach ( $keys as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $key, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<?php esc_html_e( 'بازه', 'lamerd-khodro' ); ?>
					<select name="range">
						<?php foreach ( $ranges as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $range, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<?php esc_html_e( 'از', 'lamerd-khodro' ); ?>
					<input type="date" name="from" value="<?php echo esc_attr( $args['from'] ); ?>" />
				</label>
				<label>
					<?php esc_html_e( 'تا', 'lamerd-khodro' ); ?>
					<input type="date" name="to" value="<?php echo esc_attr( $args['to'] ); ?>" />
				</label>
				<label>
					<?php esc_html_e( 'گروه/برند', 'lamerd-khodro' ); ?>
					<select name="brand">
						<option value=""><?php esc_html_e( 'همه', 'lamerd-khodro' ); ?></option>
						<?php foreach ( $groups as $group ) : ?>
							<option value="<?php echo esc_attr( $group['name'] ); ?>" <?php selected( $args['brand'], $group['name'] ); ?>><?php echo esc_html( $group['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<?php esc_html_e( 'نوع قیمت', 'lamerd-khodro' ); ?>
					<select name="price_type">
						<option value="market" <?php selected( $args['price_type'], 'market' ); ?>><?php esc_html_e( 'بازار', 'lamerd-khodro' ); ?></option>
						<option value="factory" <?php selected( $args['price_type'], 'factory' ); ?>><?php esc_html_e( 'کارخانه', 'lamerd-khodro' ); ?></option>
					</select>
				</label>
				<label>
					<?php esc_html_e( 'شناسه خودرو', 'lamerd-khodro' ); ?>
					<input type="number" name="vehicle_id" min="0" value="<?php echo esc_attr( (string) $args['vehicle_id'] ); ?>" style="width:110px" />
				</label>
				<button type="submit" class="button"><?php esc_html_e( 'نمایش گزارش', 'lamerd-khodro' ); ?></button>
			</form>

			<div class="lkp-actions" style="margin-bottom:14px">
				<?php
				$formats = array(
					'csv'  => __( 'خروجی CSV', 'lamerd-khodro' ),
					'xlsx' => __( 'خروجی Excel', 'lamerd-khodro' ),
					'pdf'  => __( 'چاپ / PDF', 'lamerd-khodro' ),
				);
				foreach ( $formats as $format => $label ) :
					$url = add_query_arg(
						array_merge(
							$export_base,
							array(
								'action' => 'lkp_export',
								'format' => $format,
							)
						),
						admin_url( 'admin-post.php' )
					);
					?>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( $url, 'lkp_export' ) ); ?>" target="_blank" rel="noopener">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</div>

			<h2>
				<?php echo esc_html( $data['title'] ); ?>
				<span class="lkp-muted" style="font-size:13px">— <?php echo esc_html( $data['bounds']['label'] ); ?></span>
			</h2>

			<?php if ( ! empty( $data['summary'] ) ) : ?>
				<div class="lkp-summary">
					<?php foreach ( $data['summary'] as $label => $value ) : ?>
						<span><strong><?php echo esc_html( (string) $label ); ?>:</strong> <?php echo esc_html( (string) $value ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="lkp-table-wrap">
				<table class="lkp-table">
					<thead>
						<tr>
							<?php foreach ( $data['columns'] as $label ) : ?>
								<th><?php echo esc_html( (string) $label ); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! $data['rows'] ) : ?>
							<tr>
								<td colspan="<?php echo esc_attr( (string) max( 1, count( $data['columns'] ) ) ); ?>" class="lkp-muted">
									<?php esc_html_e( 'داده‌ای برای این بازه ثبت نشده است. تاریخچه از زمان راه‌اندازی موتور قیمت ساخته می‌شود.', 'lamerd-khodro' ); ?>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $data['rows'] as $row ) : ?>
								<tr>
									<?php foreach ( $row as $cell ) : ?>
										<td class="num">
											<?php
											if ( null === $cell ) {
												echo '<span class="lkp-muted">—</span>';
											} elseif ( is_int( $cell ) ) {
												echo esc_html( lkp_fa_digits( number_format_i18n( $cell ) ) );
											} else {
												echo esc_html( (string) $cell );
											}
											?>
										</td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * منابع قیمت.
	 */
	public static function sources(): void {
		self::guard();

		$sync   = lamerd_khodro_price_engine()->sync();
		$source = $sync->source();
		$health = $sync->health();
		?>
		<div class="wrap lkp-wrap">
			<h1><?php esc_html_e( 'منابع قیمت', 'lamerd-khodro' ); ?></h1>
			<?php self::notice(); ?>

			<?php if ( ! $source ) : ?>
				<div class="lkp-notice-inline lkp-notice-inline--bad">
					<?php esc_html_e( 'منبعی ثبت نشده است. افزونه را یک‌بار غیرفعال و فعال کنید یا دیتابیس را بازسازی کنید.', 'lamerd-khodro' ); ?>
				</div>
			<?php else : ?>
				<table class="widefat striped" style="max-width:900px">
					<tbody>
						<tr><td style="width:200px"><?php esc_html_e( 'نام منبع', 'lamerd-khodro' ); ?></td><td><?php echo esc_html( (string) $source['name'] ); ?></td></tr>
						<tr><td><?php esc_html_e( 'آدرس پایه', 'lamerd-khodro' ); ?></td><td><span class="lkp-code"><?php echo esc_html( (string) $source['api_base'] ); ?></span></td></tr>
						<tr><td><?php esc_html_e( 'متن ذکر منبع', 'lamerd-khodro' ); ?></td><td><?php echo esc_html( (string) $source['attribution_text'] ); ?></td></tr>
						<tr><td><?php esc_html_e( 'لینک منبع', 'lamerd-khodro' ); ?></td><td><a href="<?php echo esc_url( (string) $source['attribution_url'] ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( (string) $source['attribution_url'] ); ?></a></td></tr>
						<tr><td><?php esc_html_e( 'وضعیت', 'lamerd-khodro' ); ?></td><td><?php echo wp_kses_post( lkp_badge( $health['state'], $health['label'] ) ); ?></td></tr>
						<tr><td><?php esc_html_e( 'آخرین دریافت موفق', 'lamerd-khodro' ); ?></td><td><?php echo esc_html( lkp_jalali( $health['last_success'] ) ); ?></td></tr>
						<tr><td><?php esc_html_e( 'آخرین تلاش', 'lamerd-khodro' ); ?></td><td><?php echo esc_html( lkp_jalali( $source['last_attempt_at'] ?? null ) ); ?></td></tr>
						<tr><td><?php esc_html_e( 'آخرین خطا', 'lamerd-khodro' ); ?></td><td><code style="direction:ltr;display:inline-block"><?php echo esc_html( (string) ( $source['last_error'] ?: '—' ) ); ?></code></td></tr>
					</tbody>
				</table>

				<p style="margin-top:16px"><?php self::sync_button(); ?></p>

				<p class="lkp-hint">
					<?php esc_html_e( 'این افزونه فقط قیمت می‌خواند؛ سقف درخواست منبع رعایت می‌شود (حداکثر ۲ درخواست در هر اجرا) و همه داده‌ها در دیتابیس داخلی ذخیره می‌شوند.', 'lamerd-khodro' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * صفحه همگام‌سازی.
	 */
	public static function sync(): void {
		self::guard();

		$engine = lamerd_khodro_price_engine();
		$sync   = $engine->sync();
		$logs   = ( new LKP_Log_Repository() )->recent( 40 );
		$cron   = LKP_Scheduler::info();
		?>
		<div class="wrap lkp-wrap">
			<h1><?php esc_html_e( 'همگام‌سازی قیمت‌ها', 'lamerd-khodro' ); ?></h1>
			<?php self::notice(); ?>

			<div class="lkp-summary">
				<span><strong><?php esc_html_e( 'قفل:', 'lamerd-khodro' ); ?></strong>
					<?php echo wp_kses_post( $sync->is_locked() ? lkp_badge( 'warn', __( 'قفل است', 'lamerd-khodro' ) ) : lkp_badge( 'ok', __( 'آزاد', 'lamerd-khodro' ) ) ); ?>
				</span>
				<span><strong><?php esc_html_e( 'اجرای بعدی:', 'lamerd-khodro' ); ?></strong> <?php echo esc_html( $cron['next'] ? lkp_jalali( $cron['next'] ) : '—' ); ?></span>
				<span><strong><?php esc_html_e( 'فاصله:', 'lamerd-khodro' ); ?></strong> <?php echo esc_html( lkp_fa_digits( (string) $cron['interval'] ) . ' ' . __( 'دقیقه', 'lamerd-khodro' ) ); ?></span>
			</div>

			<p><?php self::sync_button(); ?></p>

			<h2><?php esc_html_e( 'اجرای مطمئن cron روی هاست', 'lamerd-khodro' ); ?></h2>
			<p class="lkp-hint">
				<?php esc_html_e( 'WP-Cron به بازدید کاربر وابسته است. برای اجرای مطمئن، این دستور را در crontab هاست قرار دهید:', 'lamerd-khodro' ); ?>
			</p>
			<p>
				<span class="lkp-code">*/30 * * * * cd <?php echo esc_html( ABSPATH ); ?> && wp cron event run --due-now >/dev/null 2>&1</span>
			</p>
			<p class="lkp-hint">
				<?php esc_html_e( 'اگر WP-CLI روی هاست نیست، از این معادل استفاده کنید:', 'lamerd-khodro' ); ?>
				<span class="lkp-code">*/30 * * * * php <?php echo esc_html( ABSPATH . 'wp-cron.php' ); ?> >/dev/null 2>&1</span>
			</p>

			<h2><?php esc_html_e( 'لاگ اجراها', 'lamerd-khodro' ); ?></h2>
			<div class="lkp-table-wrap">
				<table class="lkp-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'شروع', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'پایان', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'محرک', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'وضعیت', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'دریافتی', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'جدید', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'تغییر', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'خطا', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'مدت', 'lamerd-khodro' ); ?></th>
							<th><?php esc_html_e( 'جزئیات', 'lamerd-khodro' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! $logs ) : ?>
							<tr><td colspan="10" class="lkp-muted"><?php esc_html_e( 'لاگی موجود نیست.', 'lamerd-khodro' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $logs as $log ) : ?>
								<tr>
									<td><?php echo esc_html( lkp_jalali( (string) $log['started_at'] ) ); ?></td>
									<td><?php echo esc_html( lkp_jalali( $log['finished_at'] ? (string) $log['finished_at'] : null ) ); ?></td>
									<td><?php echo esc_html( lkp_trigger_label( (string) $log['trigger_type'] ) ); ?></td>
									<td><?php echo wp_kses_post( lkp_badge( (string) $log['status'], lkp_status_label( (string) $log['status'] ) ) ); ?></td>
									<td class="num"><?php echo esc_html( lkp_fa_digits( (string) (int) $log['received'] ) ); ?></td>
									<td class="num"><?php echo esc_html( lkp_fa_digits( (string) (int) $log['created'] ) ); ?></td>
									<td class="num"><?php echo esc_html( lkp_fa_digits( (string) (int) $log['price_changed'] ) ); ?></td>
									<td class="num"><?php echo esc_html( lkp_fa_digits( (string) (int) $log['errors'] ) ); ?></td>
									<td class="num"><?php echo esc_html( lkp_fa_digits( (string) (int) $log['duration_ms'] ) . ' ms' ); ?></td>
									<td>
										<?php if ( $log['error_details'] ) : ?>
											<details>
												<summary><?php esc_html_e( 'نمایش', 'lamerd-khodro' ); ?></summary>
												<pre style="white-space:pre-wrap;direction:rtl;max-width:420px;font-size:11px"><?php echo esc_html( (string) $log['error_details'] ); ?></pre>
											</details>
										<?php else : ?>
											<span class="lkp-muted">—</span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * تنظیمات.
	 */
	public static function settings(): void {
		self::guard();

		$settings = LKP_Sync_Service::settings();
		$cron     = LKP_Scheduler::info();
		?>
		<div class="wrap lkp-wrap">
			<h1><?php esc_html_e( 'تنظیمات موتور قیمت', 'lamerd-khodro' ); ?></h1>
			<?php self::notice(); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lkp_save_settings" />
				<?php wp_nonce_field( 'lkp_save_settings' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="sync_interval"><?php esc_html_e( 'فاصله همگام‌سازی', 'lamerd-khodro' ); ?></label></th>
						<td>
							<select name="sync_interval" id="sync_interval">
								<?php foreach ( LKP_Scheduler::allowed_intervals() as $minutes ) : ?>
									<option value="<?php echo esc_attr( (string) $minutes ); ?>" <?php selected( (int) $settings['sync_interval'], $minutes ); ?>>
										<?php echo esc_html( sprintf( __( 'هر %s دقیقه', 'lamerd-khodro' ), number_format_i18n( $minutes ) ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'پیش‌فرض ۶۰ دقیقه؛ با هر اجرا حداکثر ۲ درخواست به منبع ارسال می‌شود (زیر سقف ۱۲۰ درخواست در دقیقه).', 'lamerd-khodro' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="table_ids"><?php esc_html_e( 'شناسه جدول‌های منبع', 'lamerd-khodro' ); ?></label></th>
						<td>
							<input type="text" name="table_ids" id="table_ids" class="regular-text" value="<?php echo esc_attr( implode( ',', array_map( 'intval', (array) $settings['table_ids'] ) ) ); ?>" />
							<p class="description"><?php esc_html_e( 'با کاما جدا کنید. جدول ۱ = قیمت خودروهای داخلی.', 'lamerd-khodro' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Snapshot روزانه', 'lamerd-khodro' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="enable_snapshots" value="1" <?php checked( ! empty( $settings['enable_snapshots'] ) ); ?> />
								<?php esc_html_e( 'ثبت وضعیت روزانه برای تحلیل‌های بلندمدت', 'lamerd-khodro' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="retention_days"><?php esc_html_e( 'نگهداری snapshot و لاگ (روز)', 'lamerd-khodro' ); ?></label></th>
						<td>
							<input type="number" min="30" max="3650" name="retention_days" id="retention_days" value="<?php echo esc_attr( (string) $settings['retention_days'] ); ?>" />
							<p class="description"><?php esc_html_e( 'تاریخچه قیمت هرگز حذف نمی‌شود؛ این مقدار فقط برای snapshot و لاگ‌هاست.', 'lamerd-khodro' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'زمان‌بندی فعلی', 'lamerd-khodro' ); ?></th>
						<td>
							<?php
							echo esc_html(
								sprintf(
									__( 'اجرای بعدی: %1$s — بازه: %2$s', 'lamerd-khodro' ),
									$cron['next'] ? lkp_jalali( $cron['next'] ) : __( 'ثبت نشده', 'lamerd-khodro' ),
									$cron['recurrence']
								)
							);
							?>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'ذخیره تنظیمات', 'lamerd-khodro' ) ); ?>
			</form>

			<div class="lkp-danger">
				<h2><?php esc_html_e( 'حذف کامل داده‌های موتور قیمت', 'lamerd-khodro' ); ?></h2>
				<p>
					<?php esc_html_e( 'این عملیات همه جداول lkp_* (خودروها، قیمت‌ها، تاریخچه و لاگ‌ها) را حذف می‌کند و قابل بازگشت نیست. حذف افزونه به‌تنهایی داده‌ها را پاک نمی‌کند.', 'lamerd-khodro' ); ?>
				</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="lkp_drop_data" />
					<?php wp_nonce_field( 'lkp_drop_data' ); ?>
					<p>
						<label>
							<?php esc_html_e( 'برای تأیید عبارت DELETE را وارد کنید:', 'lamerd-khodro' ); ?>
							<input type="text" name="lkp_confirm" value="" style="direction:ltr" />
						</label>
					</p>
					<button type="submit" class="button button-link-delete"><?php esc_html_e( 'حذف کامل داده‌ها', 'lamerd-khodro' ); ?></button>
				</form>
			</div>
		</div>
		<?php
	}
}
