<?php
/**
 * Uninstall routine.
 *
 * Deliberately conservative: the popups themselves are content, so they stay in
 * the database. Only the plugin's own bookkeeping option is removed.
 *
 * @package PopcornPopups
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'popcorn_installed' );
delete_transient( 'popcorn_just_popped' );
