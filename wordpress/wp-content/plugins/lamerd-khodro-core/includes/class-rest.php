<?php
/**
 * مسیرهای REST برای مصرف قالب و درخواست‌های سمت کلاینت.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

/**
 * ثبت namespace اختصاصی `lkc/v1`.
 */
class LKC_Rest {

	const NAMESPACE = 'lkc/v1';

	/**
	 * اتصال هوک‌ها.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * ثبت مسیرها.
	 */
	public function routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/search',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q'     => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'limit' => array(
						'type'    => 'integer',
						'default' => 8,
						'minimum' => 1,
						'maximum' => 20,
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/vehicles',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'vehicles' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'limit'   => array( 'type' => 'integer', 'default' => 12, 'minimum' => 1, 'maximum' => 100 ),
					'page'    => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1 ),
					'brand'   => array( 'type' => 'string', 'default' => '' ),
					'orderby' => array( 'type' => 'string', 'default' => 'date', 'enum' => array( 'date', 'title', 'views', 'modified', 'rand' ) ),
					'year'    => array( 'type' => 'integer', 'default' => 0 ),
					'body'    => array( 'type' => 'string', 'default' => '' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/vehicles/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'vehicle' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array( 'type' => 'integer', 'required' => true, 'minimum' => 1 ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/brands',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'brands' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'limit'      => array( 'type' => 'integer', 'default' => 24, 'minimum' => 1, 'maximum' => 100 ),
					'hide_empty' => array( 'type' => 'boolean', 'default' => true ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/compare',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'compare' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'ids' => array(
						'required' => true,
						'type'     => 'string',
						'validate_callback' => static function ( $value ): bool {
							$ids = array_filter( array_map( 'absint', explode( ',', (string) $value ) ) );
							return count( $ids ) >= 2 && count( $ids ) <= 4;
						},
					),
				),
			)
		);
	}

	/**
	 * جستجو.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response
	 */
	public function search( WP_REST_Request $request ): WP_REST_Response {
		$results = lamerd_khodro_core()->module( 'search' );
		$items   = $results instanceof LKC_Search
			? $results->query( (string) $request->get_param( 'q' ), (int) $request->get_param( 'limit' ) )
			: array();

		return new WP_REST_Response( array( 'count' => count( $items ), 'results' => $items ), 200 );
	}

	/**
	 * فهرست خودروها.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response
	 */
	public function vehicles( WP_REST_Request $request ): WP_REST_Response {
		$items = lamerd_khodro_core()->vehicles()->get_all(
			array(
				'limit'   => (int) $request->get_param( 'limit' ),
				'page'    => (int) $request->get_param( 'page' ),
				'brand'   => (string) $request->get_param( 'brand' ),
				'orderby' => (string) $request->get_param( 'orderby' ),
				'year'    => (int) $request->get_param( 'year' ),
				'body'    => (string) $request->get_param( 'body' ),
			)
		);

		return new WP_REST_Response( array( 'count' => count( $items ), 'items' => $items ), 200 );
	}

	/**
	 * یک خودرو.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response
	 */
	public function vehicle( WP_REST_Request $request ): WP_REST_Response {
		$item = lamerd_khodro_core()->vehicles()->get( (int) $request->get_param( 'id' ) );
		if ( null === $item ) {
			return new WP_REST_Response( array( 'message' => __( 'خودرو یافت نشد.', 'lamerd-khodro' ) ), 404 );
		}
		return new WP_REST_Response( $item, 200 );
	}

	/**
	 * فهرست برندها.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response
	 */
	public function brands( WP_REST_Request $request ): WP_REST_Response {
		$items = lamerd_khodro_core()->brands()->get_all(
			array(
				'limit'      => (int) $request->get_param( 'limit' ),
				'hide_empty' => (bool) $request->get_param( 'hide_empty' ),
			)
		);

		return new WP_REST_Response( array( 'count' => count( $items ), 'items' => $items ), 200 );
	}

	/**
	 * مقایسه ۲ تا ۴ خودرو.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response
	 */
	public function compare( WP_REST_Request $request ): WP_REST_Response {
		$ids     = array_slice( array_values( array_unique( array_filter( array_map( 'absint', explode( ',', (string) $request->get_param( 'ids' ) ) ) ) ) ), 0, 4 );
		$service = lamerd_khodro_core()->vehicles();

		$items = array();
		foreach ( $ids as $id ) {
			$item = $service->get( $id );
			if ( null !== $item ) {
				$items[] = $item;
			}
		}

		if ( count( $items ) < 2 ) {
			return new WP_REST_Response( array( 'message' => __( 'برای مقایسه حداقل دو خودرو لازم است.', 'lamerd-khodro' ) ), 400 );
		}

		return new WP_REST_Response( array( 'count' => count( $items ), 'items' => $items ), 200 );
	}
}
