<?php
/**
 * Front-end delivery: assets and the popup payload.
 *
 * @package PopcornPopups
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Popcorn_Frontend
 */
class Popcorn_Frontend {

	/**
	 * Popups chosen for this request.
	 *
	 * @var WP_Post[]|null
	 */
	protected static $popups = null;

	/**
	 * Extra popup IDs forced onto the page by a shortcode.
	 *
	 * @var int[]
	 */
	protected static $forced = array();

	/**
	 * Hook it up.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 20 );
		// Priority 5 so the payload is printed before the engine script runs.
		add_action( 'wp_footer', array( __CLASS__, 'payload' ), 5 );
	}

	/**
	 * Force a popup onto this page (used by the shortcode).
	 *
	 * @param int $popup_id Popup ID.
	 */
	public static function force( $popup_id ) {
		$popup_id = absint( $popup_id );
		if ( $popup_id && ! in_array( $popup_id, self::$forced, true ) ) {
			self::$forced[] = $popup_id;
		}
	}

	/**
	 * Popups for this request, resolved once.
	 *
	 * @return WP_Post[]
	 */
	public static function popups() {
		if ( null === self::$popups ) {
			self::$popups = Popcorn_Targeting::matching();
		}

		$popups = self::$popups;
		$known  = wp_list_pluck( $popups, 'ID' );

		foreach ( self::$forced as $forced_id ) {
			if ( in_array( $forced_id, $known, true ) ) {
				continue;
			}
			$extra = get_post( $forced_id );
			if ( $extra && POPCORN_CPT === $extra->post_type && 'publish' === $extra->post_status ) {
				$popups[] = $extra;
			}
		}

		return $popups;
	}

	/**
	 * Register front-end assets. They only actually print when a popup qualifies.
	 */
	public static function assets() {
		wp_register_style( 'popcorn', POPCORN_URL . 'assets/css/popcorn.css', array(), POPCORN_VERSION );
		wp_register_script( 'popcorn', POPCORN_URL . 'assets/js/popcorn.js', array(), POPCORN_VERSION, true );

		if ( ! empty( self::popups() ) ) {
			wp_enqueue_style( 'popcorn' );
			wp_enqueue_script( 'popcorn' );
		}
	}

	/**
	 * Print the popup payload in the footer.
	 */
	public static function payload() {
		$popups = self::popups();
		if ( empty( $popups ) ) {
			return;
		}

		// The shortcode may have added a popup after wp_enqueue_scripts ran.
		if ( ! wp_script_is( 'popcorn', 'enqueued' ) ) {
			wp_enqueue_style( 'popcorn' );
			wp_enqueue_script( 'popcorn' );
		}

		$data = array(
			'endpoint' => esc_url_raw( rest_url( 'popcorn/v1/track' ) ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'track'    => ! current_user_can( 'edit_posts' ),
			'popups'   => array(),
		);

		foreach ( $popups as $popup ) {
			$data['popups'][] = self::config( $popup );
		}

		printf(
			'<script id="popcorn-data">window.PopcornData = %s;</script>',
			wp_json_encode( $data )
		);
	}

	/**
	 * Build the JS config for one popup.
	 *
	 * @param WP_Post $popup Popup post.
	 * @return array
	 */
	public static function config( $popup ) {
		$id = $popup->ID;

		$config = array(
			'id'          => $id,
			'name'        => get_the_title( $popup ),
			'content'     => self::content( $popup ),
			'trigger'     => popcorn_get( $id, 'trigger' ),
			'delay'       => (int) popcorn_get( $id, 'trigger_delay' ),
			'scroll'      => (int) popcorn_get( $id, 'trigger_scroll' ),
			'selector'    => popcorn_get( $id, 'trigger_selector' ),
			'idle'        => (int) popcorn_get( $id, 'trigger_idle' ),
			'frequency'   => popcorn_get( $id, 'frequency' ),
			'freqDays'    => (int) popcorn_get( $id, 'frequency_days' ),
			'maxShows'    => (int) popcorn_get( $id, 'max_shows' ),
			'cookieDays'  => (int) popcorn_get( $id, 'cookie_days' ),
			'rev'         => self::cookie_rev( $id ),
			'closeDelay'  => (int) popcorn_get( $id, 'close_delay' ),
			'position'    => popcorn_get( $id, 'position' ),
			'chrome'      => popcorn_get( $id, 'chrome' ),
			'offset'      => (int) popcorn_get( $id, 'corner_offset' ),
			'animation'   => popcorn_get( $id, 'animation' ),
			'width'       => (int) popcorn_get( $id, 'width' ),
			'radius'      => (int) popcorn_get( $id, 'radius' ),
			'bg'          => popcorn_get( $id, 'bg_color' ),
			'text'        => popcorn_get( $id, 'text_color' ),
			'accent'      => popcorn_get( $id, 'accent_color' ),
			'overlay'     => (int) popcorn_get( $id, 'overlay' ) ? 1 : 0,
			'overlayBg'   => popcorn_get( $id, 'overlay_color' ),
			'blur'        => (int) popcorn_get( $id, 'overlay_blur' ) ? 1 : 0,
			'confetti'    => popcorn_get( $id, 'confetti_when' ),
			'confettiFx'  => popcorn_get( $id, 'confetti_style' ),
			'confettiHue' => self::confetti_colors( $id ),
			'sound'       => (int) popcorn_get( $id, 'sound' ) ? 1 : 0,
			'emojiRain'   => popcorn_get( $id, 'emoji_rain' ),
			'ctaText'     => popcorn_get( $id, 'cta_text' ),
			'ctaUrl'      => popcorn_get( $id, 'cta_url' ),
			'ctaNewTab'   => (int) popcorn_get( $id, 'cta_new_tab' ) ? 1 : 0,
			'dismissText' => popcorn_get( $id, 'dismiss_text' ),
			'devices'     => popcorn_get( $id, 'devices' ),
		);

		/**
		 * Filter a popup's front-end config.
		 *
		 * @param array   $config Config array.
		 * @param WP_Post $popup  Popup post.
		 */
		return apply_filters( 'popcorn_popup_config', $config, $popup );
	}

	/**
	 * A short stamp of everything that decides how often a popup may show.
	 *
	 * It is baked into the visitor's cookie names, so changing any of these
	 * settings retires the old cookies instead of letting a stale "seen it" or
	 * "no thanks" quietly outlive the rule that created it. Editing the popup's
	 * copy or colours deliberately does not reset anyone's count.
	 *
	 * @param int $id Popup ID.
	 * @return string
	 */
	public static function cookie_rev( $id ) {
		$seed = implode(
			'|',
			array(
				popcorn_get( $id, 'frequency' ),
				(int) popcorn_get( $id, 'frequency_days' ),
				(int) popcorn_get( $id, 'max_shows' ),
				(int) popcorn_get( $id, 'cookie_days' ),
				'' === trim( (string) popcorn_get( $id, 'dismiss_text' ) ) ? '' : 'dismissable',
			)
		);

		return substr( md5( $seed ), 0, 6 );
	}

	/**
	 * Resolve the confetti palette into a list of hex colours.
	 *
	 * @param int $id Popup ID.
	 * @return string[]
	 */
	public static function confetti_colors( $id ) {
		$accent = popcorn_get( $id, 'accent_color' );

		$palettes = array(
			'popcorn' => array( $accent, '#ffd166', '#fff3c4', '#ff9f1c', '#ffffff' ),
			'party'   => array( '#ef476f', '#ffd166', '#06d6a0', '#118ab2', '#f78c6b' ),
			'neon'    => array( '#39ff14', '#ff073a', '#00e5ff', '#ff00e6', '#faff00' ),
			'gold'    => array( '#d4af37', '#f6e7b4', '#b8860b', '#fffdf3', '#e8c14f' ),
			'mono'    => array( '#111111', '#555555', '#999999', '#dddddd', '#ffffff' ),
			'accent'  => array( $accent ),
		);

		$choice = popcorn_get( $id, 'confetti_palette' );

		if ( 'custom' === $choice ) {
			$custom = array(
				popcorn_get( $id, 'confetti_c1' ),
				popcorn_get( $id, 'confetti_c2' ),
				popcorn_get( $id, 'confetti_c3' ),
				popcorn_get( $id, 'confetti_c4' ),
			);
			$custom = array_values( array_filter( array_map( 'sanitize_hex_color', $custom ) ) );

			if ( ! empty( $custom ) ) {
				return $custom;
			}
		}

		$colors = isset( $palettes[ $choice ] ) ? $palettes[ $choice ] : $palettes['popcorn'];

		/**
		 * Filter the confetti colours for a popup.
		 *
		 * @param string[] $colors Hex colours.
		 * @param int      $id     Popup ID.
		 */
		return apply_filters( 'popcorn_confetti_colors', $colors, $id );
	}

	/**
	 * Prepare popup body HTML.
	 *
	 * Deliberately does not run the full `the_content` filter chain, so other
	 * plugins do not staple share buttons and related-post lists inside a popup.
	 *
	 * @param WP_Post $popup Popup post.
	 * @return string
	 */
	protected static function content( $popup ) {
		$html = $popup->post_content;
		$html = do_blocks( $html );
		$html = wptexturize( $html );
		$html = convert_smilies( $html );
		$html = wpautop( $html );
		$html = shortcode_unautop( $html );
		$html = do_shortcode( $html );

		/**
		 * Filter the rendered popup body.
		 *
		 * @param string  $html  Rendered HTML.
		 * @param WP_Post $popup Popup post.
		 */
		return apply_filters( 'popcorn_popup_content', $html, $popup );
	}
}
