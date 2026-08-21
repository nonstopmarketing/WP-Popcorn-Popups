<?php
/**
 * Plugin Name:       Popcorn Popups
 * Plugin URI:        https://example.com/popcorn-popups
 * Description:       🍿 A ridiculously fun popup builder. Pop things up on pages and posts with wild triggers, silly animations, and actual confetti.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            You
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       popcorn-popups
 *
 * @package PopcornPopups
 */

defined( 'ABSPATH' ) || exit;

define( 'POPCORN_VERSION', '1.0.0' );
define( 'POPCORN_FILE', __FILE__ );
define( 'POPCORN_DIR', plugin_dir_path( __FILE__ ) );
define( 'POPCORN_URL', plugin_dir_url( __FILE__ ) );
define( 'POPCORN_CPT', 'popcorn_popup' );
define( 'POPCORN_PREFIX', '_pcp_' );

require_once POPCORN_DIR . 'includes/class-popcorn-cpt.php';
require_once POPCORN_DIR . 'includes/class-popcorn-fields.php';
require_once POPCORN_DIR . 'includes/class-popcorn-meta.php';
require_once POPCORN_DIR . 'includes/class-popcorn-admin.php';
require_once POPCORN_DIR . 'includes/class-popcorn-targeting.php';
require_once POPCORN_DIR . 'includes/class-popcorn-frontend.php';
require_once POPCORN_DIR . 'includes/class-popcorn-rest.php';
require_once POPCORN_DIR . 'includes/class-popcorn-shortcode.php';

/**
 * Boot the whole snack bar.
 */
function popcorn_boot() {
	Popcorn_CPT::init();
	Popcorn_Meta::init();
	Popcorn_Admin::init();
	Popcorn_Frontend::init();
	Popcorn_REST::init();
	Popcorn_Shortcode::init();
}
add_action( 'plugins_loaded', 'popcorn_boot' );

/**
 * Register the CPT on activation so rewrite rules are fresh.
 */
function popcorn_activate() {
	Popcorn_CPT::register();
	flush_rewrite_rules();
	if ( ! get_option( 'popcorn_installed' ) ) {
		add_option( 'popcorn_installed', time() );
		set_transient( 'popcorn_just_popped', 1, 60 );
	}
}
register_activation_hook( __FILE__, 'popcorn_activate' );

/**
 * Tidy up on deactivation. Popups stay put — only rewrites are flushed.
 */
function popcorn_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'popcorn_deactivate' );

/**
 * Handy accessor for a popup setting with a sane fallback.
 *
 * @param int    $post_id Popup ID.
 * @param string $key     Field key without prefix.
 * @param mixed  $default Fallback value.
 * @return mixed
 */
function popcorn_get( $post_id, $key, $default = '' ) {
	$value = get_post_meta( $post_id, POPCORN_PREFIX . $key, true );
	if ( '' === $value || null === $value ) {
		$defaults = Popcorn_Fields::defaults();
		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : $default;
	}
	return $value;
}
