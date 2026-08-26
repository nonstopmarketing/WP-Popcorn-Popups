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
	 * Has this address had its fill of counting for now?
	 *
	 * The endpoint has to stay open to logged-out visitors, so anyone can post
	 * to it. This keeps a scripted flood from inflating the numbers and, more
	 * importantly, from hammering the database with writes. It uses a transient,
	 * so it lands in the object cache on sites that have one.
	 *
	 * @return bool True when the request may be counted.
	 */
	protected static function within_rate_limit() {
		/**
		 * Filter how many events one address may record per minute.
		 *
		 * Sites behind a proxy or CDN that does not restore the visitor's real
		 * IP will see all traffic share one address, so raise this or return 0
		 * to switch the limit off entirely.
		 *
		 * @param int $limit Events per minute. 0 disables the limit.
		 */
		$limit = (int) apply_filters( 'popcorn_track_rate_limit', 60 );

		if ( $limit <= 0 ) {
			return true;
		}

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ip = filter_var( $ip, FILTER_VALIDATE_IP );

		// No usable address means no way to bucket the request; let it through.
		if ( ! $ip ) {
			return true;
		}

		// The address is hashed so no raw IP is ever written to the database.
		$key   = 'pcp_rl_' . md5( $ip . '|' . wp_salt() );
		$count = (int) get_transient( $key );

		if ( $count >= $limit ) {
			return false;
		}

		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );

		return true;
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

		if ( ! self::within_rate_limit() ) {
			return new WP_Error(
				'popcorn_too_fast',
				__( 'Slow down. Counting can wait.', 'popcorn-popups' ),
				array( 'status' => 429 )
			);
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
