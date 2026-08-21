<?php
/**
 * A shortcode for opening a popup from anywhere in a page or post.
 *
 * @package PopcornPopups
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Popcorn_Shortcode
 */
class Popcorn_Shortcode {

	/**
	 * Hook it up.
	 */
	public static function init() {
		add_shortcode( 'popcorn', array( __CLASS__, 'render' ) );
		add_shortcode( 'popcorn_button', array( __CLASS__, 'render' ) );
	}

	/**
	 * Render a button that pops a given popup.
	 *
	 * Usage: [popcorn id="12" text="Show me the deal"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'text'  => __( 'Open the popup 🍿', 'popcorn-popups' ),
				'class' => '',
			),
			$atts,
			'popcorn'
		);

		$id    = absint( $atts['id'] );
		$popup = $id ? get_post( $id ) : null;

		if ( ! $popup || POPCORN_CPT !== $popup->post_type ) {
			return current_user_can( 'edit_posts' )
				? '<span class="popcorn-shortcode-error">' . esc_html__( '[popcorn] needs a valid popup id, e.g. [popcorn id="12"]', 'popcorn-popups' ) . '</span>'
				: '';
		}

		if ( 'publish' !== $popup->post_status ) {
			return current_user_can( 'edit_posts' )
				? '<span class="popcorn-shortcode-error">' . esc_html__( 'That popup is not published yet, so this button does nothing for visitors.', 'popcorn-popups' ) . '</span>'
				: '';
		}

		Popcorn_Frontend::force( $id );

		return sprintf(
			'<button type="button" class="popcorn-open-btn %1$s" data-popcorn-open="%2$d">%3$s</button>',
			esc_attr( $atts['class'] ),
			$id,
			esc_html( $atts['text'] )
		);
	}
}
