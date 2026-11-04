<?php
/**
 * متا‌بکس‌های مشخصات فنی خودرو.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

/**
 * ثبت و ذخیره متا‌فیلدهای ساختاریافته خودرو.
 */
class LKC_Meta_Boxes {

	const PREFIX = '_lkp_';

	/**
	 * تعریف گروه‌ها و فیلدها (ساختار داده سند نیازمندی‌ها).
	 *
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	public static function fields(): array {
		return array(
			'general'      => array(
				'title'  => __( 'اطلاعات عمومی', 'lamerd-khodro' ),
				'fields' => array(
					'name_en'          => array( 'label' => __( 'نام انگلیسی', 'lamerd-khodro' ), 'type' => 'text' ),
					'model'            => array( 'label' => __( 'مدل', 'lamerd-khodro' ), 'type' => 'text' ),
					'year'             => array( 'label' => __( 'سال تولید', 'lamerd-khodro' ), 'type' => 'number', 'min' => 1380, 'max' => 1420 ),
					'country'          => array( 'label' => __( 'کشور سازنده', 'lamerd-khodro' ), 'type' => 'text' ),
					'distributor'      => array( 'label' => __( 'شرکت عرضه‌کننده', 'lamerd-khodro' ), 'type' => 'text' ),
					'body_type'        => array(
						'label'   => __( 'نوع بدنه', 'lamerd-khodro' ),
						'type'    => 'select',
						'options' => array(
							''       => __( '— انتخاب —', 'lamerd-khodro' ),
							'sedan'  => __( 'سدان', 'lamerd-khodro' ),
							'hatch'  => __( 'هاچ‌بک', 'lamerd-khodro' ),
							'crossover' => __( 'کراس‌اور', 'lamerd-khodro' ),
							'suv'    => __( 'شاسی‌بلند', 'lamerd-khodro' ),
							'pickup' => __( 'وانت', 'lamerd-khodro' ),
							'minion' => __( 'مینی‌ون', 'lamerd-khodro' ),
							'coupe'  => __( 'کوپه', 'lamerd-khodro' ),
							'station'=> __( 'استیشن', 'lamerd-khodro' ),
						),
					),
					'car_class'        => array(
						'label'   => __( 'کلاس خودرو', 'lamerd-khodro' ),
						'type'    => 'select',
						'options' => array(
							''         => __( '— انتخاب —', 'lamerd-khodro' ),
							'economy'  => __( 'اقتصادی', 'lamerd-khodro' ),
							'mid'      => __( 'میان‌رده', 'lamerd-khodro' ),
							'luxury'   => __( 'لوکس', 'lamerd-khodro' ),
							'sport'    => __( 'اسپرت', 'lamerd-khodro' ),
							'commercial' => __( 'تجاری', 'lamerd-khodro' ),
						),
					),
					'production_status'=> array(
						'label'   => __( 'وضعیت تولید', 'lamerd-khodro' ),
						'type'    => 'select',
						'options' => array(
							'in'     => __( 'در تولید', 'lamerd-khodro' ),
							'stopped'=> __( 'توقف تولید', 'lamerd-khodro' ),
							'future' => __( 'تولید آینده', 'lamerd-khodro' ),
						),
					),
					'market_status'    => array(
						'label'   => __( 'وضعیت عرضه', 'lamerd-khodro' ),
						'type'    => 'select',
						'options' => array(
							'active'    => __( 'عرضه فعال', 'lamerd-khodro' ),
							'presale'   => __( 'پیش‌فروش', 'lamerd-khodro' ),
							'paused'    => __( 'متوقف', 'lamerd-khodro' ),
							'discontinued' => __( 'خارج از عرضه', 'lamerd-khodro' ),
						),
					),
				),
			),
			'engine'       => array(
				'title'  => __( 'مشخصات موتور', 'lamerd-khodro' ),
				'fields' => array(
					'engine_type'   => array( 'label' => __( 'نوع موتور', 'lamerd-khodro' ), 'type' => 'text' ),
					'engine_cc'     => array( 'label' => __( 'حجم موتور (سی‌سی)', 'lamerd-khodro' ), 'type' => 'number' ),
					'cylinders'     => array( 'label' => __( 'تعداد سیلندر', 'lamerd-khodro' ), 'type' => 'number' ),
					'valves'        => array( 'label' => __( 'تعداد سوپاپ', 'lamerd-khodro' ), 'type' => 'number' ),
					'max_power'     => array( 'label' => __( 'حداکثر قدرت (اسب‌بخار)', 'lamerd-khodro' ), 'type' => 'number' ),
					'max_torque'    => array( 'label' => __( 'حداکثر گشتاور (نیوتن‌متر)', 'lamerd-khodro' ), 'type' => 'number' ),
					'fuel'          => array(
						'label'   => __( 'نوع سوخت', 'lamerd-khodro' ),
						'type'    => 'select',
						'options' => array(
							'petrol' => __( 'بنزین', 'lamerd-khodro' ),
							'cng'    => __( 'دوگانه‌سوز', 'lamerd-khodro' ),
							'diesel' => __( 'دیزل', 'lamerd-khodro' ),
							'hybrid' => __( 'هیبرید', 'lamerd-khodro' ),
							'ev'     => __( 'برقی', 'lamerd-khodro' ),
						),
					),
					'emission'      => array(
						'label'   => __( 'استاندارد آلایندگی', 'lamerd-khodro' ),
						'type'    => 'select',
						'options' => array(
							'euro4' => __( 'یورو ۴', 'lamerd-khodro' ),
							'euro5' => __( 'یورو ۵', 'lamerd-khodro' ),
							'euro6' => __( 'یورو ۶', 'lamerd-khodro' ),
						),
					),
				),
			),
			'transmission' => array(
				'title'  => __( 'مشخصات گیربکس', 'lamerd-khodro' ),
				'fields' => array(
					'transmission' => array(
						'label'   => __( 'نوع گیربکس', 'lamerd-khodro' ),
						'type'    => 'select',
						'options' => array(
							'manual' => __( 'دستی', 'lamerd-khodro' ),
							'auto'   => __( 'اتوماتیک', 'lamerd-khodro' ),
							'cvt'    => __( 'CVT', 'lamerd-khodro' ),
							'dct'    => __( 'دوکلاچه', 'lamerd-khodro' ),
						),
					),
					'gears'        => array( 'label' => __( 'تعداد دنده', 'lamerd-khodro' ), 'type' => 'number' ),
					'drivetrain'   => array(
						'label'   => __( 'نوع انتقال قدرت', 'lamerd-khodro' ),
						'type'    => 'select',
						'options' => array(
							'fwd' => __( 'دیفرانسیل جلو', 'lamerd-khodro' ),
							'rwd' => __( 'دیفرانسیل عقب', 'lamerd-khodro' ),
							'awd' => __( 'چهار چرخ متحرک', 'lamerd-khodro' ),
						),
					),
				),
			),
			'performance'  => array(
				'title'  => __( 'عملکرد', 'lamerd-khodro' ),
				'fields' => array(
					'acceleration'  => array( 'label' => __( 'شتاب ۰ تا ۱۰۰ (ثانیه)', 'lamerd-khodro' ), 'type' => 'decimal' ),
					'top_speed'     => array( 'label' => __( 'حداکثر سرعت (کیلومتر بر ساعت)', 'lamerd-khodro' ), 'type' => 'number' ),
					'fuel_city'     => array( 'label' => __( 'مصرف سوخت شهری (لیتر/۱۰۰ کیلومتر)', 'lamerd-khodro' ), 'type' => 'decimal' ),
					'fuel_highway'  => array( 'label' => __( 'مصرف سوخت جاده‌ای', 'lamerd-khodro' ), 'type' => 'decimal' ),
					'fuel_combined' => array( 'label' => __( 'مصرف سوخت ترکیبی', 'lamerd-khodro' ), 'type' => 'decimal' ),
					'tank'          => array( 'label' => __( 'ظرفیت باک (لیتر)', 'lamerd-khodro' ), 'type' => 'number' ),
				),
			),
			'dimensions'   => array(
				'title'  => __( 'ابعاد', 'lamerd-khodro' ),
				'fields' => array(
					'length'    => array( 'label' => __( 'طول (میلی‌متر)', 'lamerd-khodro' ), 'type' => 'number' ),
					'width'     => array( 'label' => __( 'عرض (میلی‌متر)', 'lamerd-khodro' ), 'type' => 'number' ),
					'height'    => array( 'label' => __( 'ارتفاع (میلی‌متر)', 'lamerd-khodro' ), 'type' => 'number' ),
					'wheelbase' => array( 'label' => __( 'فاصله محوری (میلی‌متر)', 'lamerd-khodro' ), 'type' => 'number' ),
					'weight'    => array( 'label' => __( 'وزن (کیلوگرم)', 'lamerd-khodro' ), 'type' => 'number' ),
					'trunk'     => array( 'label' => __( 'حجم صندوق عقب (لیتر)', 'lamerd-khodro' ), 'type' => 'number' ),
					'seats'     => array( 'label' => __( 'ظرفیت سرنشین', 'lamerd-khodro' ), 'type' => 'number' ),
				),
			),
			'features'     => array(
				'title'  => __( 'امکانات', 'lamerd-khodro' ),
				'fields' => array(
					'safety'    => array( 'label' => __( 'امکانات ایمنی', 'lamerd-khodro' ), 'type' => 'textarea' ),
					'comfort'   => array( 'label' => __( 'امکانات رفاهی', 'lamerd-khodro' ), 'type' => 'textarea' ),
					'brakes'    => array( 'label' => __( 'سیستم ترمز', 'lamerd-khodro' ), 'type' => 'textarea' ),
					'airbags'   => array( 'label' => __( 'کیسه هوا', 'lamerd-khodro' ), 'type' => 'textarea' ),
					'adas'      => array( 'label' => __( 'سیستم‌های کمک راننده', 'lamerd-khodro' ), 'type' => 'textarea' ),
					'display'   => array( 'label' => __( 'نمایشگر', 'lamerd-khodro' ), 'type' => 'textarea' ),
					'audio'     => array( 'label' => __( 'سیستم صوتی', 'lamerd-khodro' ), 'type' => 'textarea' ),
					'camera'    => array( 'label' => __( 'دوربین', 'lamerd-khodro' ), 'type' => 'textarea' ),
					'sensors'   => array( 'label' => __( 'سنسورها', 'lamerd-khodro' ), 'type' => 'textarea' ),
					'interior'  => array( 'label' => __( 'امکانات داخلی', 'lamerd-khodro' ), 'type' => 'textarea' ),
				),
			),
			'link'         => array(
				'title'      => __( 'اتصال به موتور قیمت', 'lamerd-khodro' ),
				'admin_only' => true,
				'fields' => array(
					'price_vehicle_id' => array(
						'label' => __( 'شناسه خودرو در Price Engine', 'lamerd-khodro' ),
						'type'  => 'number',
						'desc'  => __( 'شناسه `lkp_vehicles.id` برای نمایش قیمت. در پیشخوان «مدیریت قیمت ← خودروها» به‌صورت خودکار پیشنهاد می‌شود.', 'lamerd-khodro' ),
					),
				),
			),
		);
	}

	/**
	 * گروه‌های قابل نمایش عمومی (بدون گروه‌های فنی پیشخوان).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function public_fields(): array {
		$out = array();

		foreach ( self::fields() as $group_key => $group ) {
			if ( empty( $group['admin_only'] ) ) {
				$out[ $group_key ] = $group;
			}
		}

		return $out;
	}

	/**
	 * اتصال هوک‌ها.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . LKC_Post_Types::POST_TYPE, array( $this, 'save' ), 10, 2 );
	}

	/**
	 * ثبت متا‌بکس‌ها.
	 */
	public function add_meta_boxes(): void {
		foreach ( self::fields() as $id => $group ) {
			add_meta_box(
				'lkc-' . $id,
				$group['title'],
				array( $this, 'render' ),
				LKC_Post_Types::POST_TYPE,
				'normal',
				$id === 'general' ? 'high' : 'default',
				array( 'group' => $id )
			);
		}
	}

	/**
	 * رندر متا‌بکس.
	 *
	 * @param WP_Post $post نوشته.
	 * @param array   $args  آرگومان‌ها.
	 */
	public function render( WP_Post $post, array $args ): void {
		$group_id = $args['group'] ?? 'general';
		$group    = self::fields()[ $group_id ] ?? null;
		if ( ! $group ) {
			return;
		}

		wp_nonce_field( 'lkc_vehicle_meta', 'lkc_vehicle_meta_nonce' );

		echo '<div class="lkc-meta-grid">';
		foreach ( $group['fields'] as $key => $field ) {
			$name = self::PREFIX . $key;
			$value = get_post_meta( $post->ID, $name, true );
			$desc  = ! empty( $field['desc'] ) ? '<p class="description">' . esc_html( $field['desc'] ) . '</p>' : '';

			echo '<div class="lkc-meta-field lkc-meta-field--' . esc_attr( $field['type'] ) . '">';
			echo '<label for="' . esc_attr( $name ) . '">' . esc_html( $field['label'] ) . '</label>';

			switch ( $field['type'] ) {
				case 'textarea':
					echo '<textarea id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" rows="3">' . esc_textarea( (string) $value ) . '</textarea>';
					echo '<p class="description">' . esc_html__( 'هر مورد در یک خط یا با جداکننده «|»', 'lamerd-khodro' ) . '</p>';
					break;

				case 'select':
					echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '">';
					foreach ( $field['options'] as $opt_value => $opt_label ) {
						echo '<option value="' . esc_attr( $opt_value ) . '"' . selected( (string) $value, (string) $opt_value, false ) . '>' . esc_html( $opt_label ) . '</option>';
					}
					echo '</select>';
					break;

				case 'number':
					echo '<input type="number" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '"';
					if ( isset( $field['min'] ) ) {
						echo ' min="' . esc_attr( (string) $field['min'] ) . '"';
					}
					if ( isset( $field['max'] ) ) {
						echo ' max="' . esc_attr( (string) $field['max'] ) . '"';
					}
					echo ' step="1" />';
					break;

				case 'decimal':
					echo '<input type="number" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" step="0.1" />';
					break;

				default:
					echo '<input type="text" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" />';
			}

			echo $desc; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- از قبل escape شده.
			echo '</div>';
		}
		echo '</div>';
	}

	/**
	 * ذخیره امن متا‌ها.
	 *
	 * @param int     $post_id شناسه.
	 * @param WP_Post $post    نوشته.
	 */
	public function save( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST['lkc_vehicle_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['lkc_vehicle_meta_nonce'] ) ), 'lkc_vehicle_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( LKC_Post_Types::POST_TYPE !== $post->post_type ) {
			return;
		}

		foreach ( self::fields() as $group ) {
			foreach ( $group['fields'] as $key => $field ) {
				$name = self::PREFIX . $key;
				if ( ! isset( $_POST[ $name ] ) ) {
					continue;
				}
				$raw = wp_unslash( $_POST[ $name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

				switch ( $field['type'] ) {
					case 'number':
						$clean = '' === trim( (string) $raw ) ? '' : (string) absint( $raw );
						break;
					case 'decimal':
						$clean = '' === trim( (string) $raw ) ? '' : (string) floatval( $raw );
						break;
					case 'textarea':
						$lines = array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $raw ) ?: array() );
						$clean = implode( '|', array_filter( array_map( 'sanitize_textarea_field', $lines ) ) );
						break;
					case 'select':
						$allowed = array_keys( $field['options'] );
						$clean  = in_array( (string) $raw, $allowed, true ) ? sanitize_key( (string) $raw ) : '';
						break;
					default:
						$clean = sanitize_text_field( (string) $raw );
				}

				if ( '' === $clean || null === $clean ) {
					delete_post_meta( $post_id, $name );
				} else {
					update_post_meta( $post_id, $name, $clean );
				}
			}
		}
	}
}
