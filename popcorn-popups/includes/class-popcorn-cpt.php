<?php
/**
 * Registers the popup post type.
 *
 * @package PopcornPopups
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Popcorn_CPT
 */
class Popcorn_CPT {

	/**
	 * Hook it up.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'enter_title_here', array( __CLASS__, 'title_placeholder' ), 10, 2 );
		add_filter( 'post_updated_messages', array( __CLASS__, 'messages' ) );
	}

	/**
	 * Register the CPT. Classic editor on purpose: the builder lives in meta boxes.
	 */
	public static function register() {
		$labels = array(
			'name'                  => __( 'Popups', 'popcorn-popups' ),
			'singular_name'         => __( 'Popup', 'popcorn-popups' ),
			'menu_name'             => __( 'Popups', 'popcorn-popups' ),
			'add_new'               => __( 'Pop a new one', 'popcorn-popups' ),
			'add_new_item'          => __( 'Add New Popup', 'popcorn-popups' ),
			'edit_item'             => __( 'Edit Popup', 'popcorn-popups' ),
			'new_item'              => __( 'New Popup', 'popcorn-popups' ),
			'view_item'             => __( 'View Popup', 'popcorn-popups' ),
			'search_items'          => __( 'Search Popups', 'popcorn-popups' ),
			'not_found'             => __( 'No popups yet. The kernel bag is empty. 🍿', 'popcorn-popups' ),
			'not_found_in_trash'    => __( 'Nothing burnt in the trash.', 'popcorn-popups' ),
			'all_items'             => __( 'All Popups', 'popcorn-popups' ),
			'items_list'            => __( 'Popups list', 'popcorn-popups' ),
		);

		register_post_type(
			POPCORN_CPT,
			array(
				'labels'              => $labels,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'menu_icon'           => 'dashicons-megaphone',
				'menu_position'       => 26,
				'supports'            => array( 'title', 'editor', 'revisions' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'exclude_from_search' => true,
			)
		);
	}

	/**
	 * Friendlier title placeholder.
	 *
	 * @param string  $text Placeholder.
	 * @param WP_Post $post Current post.
	 * @return string
	 */
	public static function title_placeholder( $text, $post ) {
		if ( $post && POPCORN_CPT === $post->post_type ) {
			return __( 'Name this popup (only you see this)', 'popcorn-popups' );
		}
		return $text;
	}

	/**
	 * Replace the boring "Post updated" notices.
	 *
	 * @param array $messages Messages.
	 * @return array
	 */
	public static function messages( $messages ) {
		$messages[ POPCORN_CPT ] = array(
			0  => '',
			1  => __( 'Popup updated. 🍿 Fresh batch ready.', 'popcorn-popups' ),
			4  => __( 'Popup updated.', 'popcorn-popups' ),
			6  => __( 'Popup published. It is out there popping. 🎉', 'popcorn-popups' ),
			7  => __( 'Popup saved.', 'popcorn-popups' ),
			8  => __( 'Popup submitted.', 'popcorn-popups' ),
			10 => __( 'Popup draft updated.', 'popcorn-popups' ),
		);
		return $messages;
	}
}
