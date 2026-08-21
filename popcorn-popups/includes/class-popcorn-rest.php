<?php
/**
 * The tiny tracking endpoint.
 *
 * @package PopcornPopups
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Popcorn_REST
 */
class Popcorn_REST {

	/**
	 * Events we count, mapped to their meta key suffix.
	 *
	 * @var array
	 */
	protected static $events = array(
		'view'  => 'stat_views',
		'click' => 'stat_clicks',
		'close' => 'stat_closes',
	);

	/**
	 * Hook it up.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	/**
	 * Register the route.
	 */
	public static function routes() {
		register_rest_route(
			'popcorn/v1',
			'/track',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'track' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id'    => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'event' => array(
						'required' => true,
						'type'     => 'string',
						'enum'     => array( 'view', 'click', 'close' ),
					),
				),
			)
		);
	}

	/**
	 * Bump a counter.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function track( $request ) {
		$id    = absint( $request->get_param( 'id' ) );
		$event = (string) $request->get_param( 'event' );

		if ( ! isset( self::$events[ $event ] ) ) {
			return new WP_Error( 'popcorn_bad_event', __( 'Unknown event.', 'popcorn-popups' ), array( 'status' => 400 ) );
		}

		$popup = get_post( $id );
		if ( ! $popup || POPCORN_CPT !== $popup->post_type || 'publish' !== $popup->post_status ) {
			return new WP_Error( 'popcorn_bad_popup', __( 'That popup is not on the menu.', 'popcorn-popups' ), array( 'status' => 404 ) );
		}

		$key   = POPCORN_PREFIX . self::$events[ $event ];
		$count = (int) get_post_meta( $id, $key, true ) + 1;
		update_post_meta( $id, $key, $count );

		/**
		 * Fires after a popup event is counted.
		 *
		 * @param string $event Event name.
		 * @param int    $id    Popup ID.
		 * @param int    $count New total.
		 */
		do_action( 'popcorn_tracked', $event, $id, $count );

		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}
}
