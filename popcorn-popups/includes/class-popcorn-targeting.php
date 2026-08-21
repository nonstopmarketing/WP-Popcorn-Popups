<?php
/**
 * Works out which popups belong on the current request.
 *
 * @package PopcornPopups
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Popcorn_Targeting
 */
class Popcorn_Targeting {

	/**
	 * Every published popup that qualifies for this request.
	 *
	 * Ordered by priority (lowest number first). Only the winning auto-triggered
	 * popup survives, but every click-triggered popup is kept — those only appear
	 * when the visitor asks for them, so they cannot pile up.
	 *
	 * @return WP_Post[]
	 */
	public static function matching() {
		if ( is_admin() || is_feed() || is_embed() || is_preview() ) {
			return array();
		}

		$popups = get_posts(
			array(
				'post_type'              => POPCORN_CPT,
				'post_status'            => 'publish',
				'posts_per_page'         => 100,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			)
		);

		$qualified = array();
		foreach ( $popups as $popup ) {
			if ( self::qualifies( $popup ) ) {
				$qualified[] = $popup;
			}
		}

		usort(
			$qualified,
			static function ( $a, $b ) {
				return (int) popcorn_get( $a->ID, 'priority' ) <=> (int) popcorn_get( $b->ID, 'priority' );
			}
		);

		$final    = array();
		$auto_set = false;
		foreach ( $qualified as $popup ) {
			if ( 'click' === popcorn_get( $popup->ID, 'trigger' ) ) {
				$final[] = $popup;
				continue;
			}
			if ( ! $auto_set ) {
				$final[]  = $popup;
				$auto_set = true;
			}
		}

		/**
		 * Filter the popups chosen for this request.
		 *
		 * @param WP_Post[] $final     Chosen popups.
		 * @param WP_Post[] $qualified Everything that passed targeting.
		 */
		return apply_filters( 'popcorn_matching_popups', $final, $qualified );
	}

	/**
	 * Does this popup belong here?
	 *
	 * @param WP_Post $popup Popup post.
	 * @return bool
	 */
	public static function qualifies( $popup ) {
		$id = $popup->ID;

		if ( ! self::in_schedule( $id ) ) {
			return false;
		}

		if ( ! self::right_visitor( $id ) ) {
			return false;
		}

		$object_id = self::current_object_id();

		// Exclusions always win.
		$excluded = self::id_list( popcorn_get( $id, 'exclude_ids' ) );
		if ( $object_id && in_array( $object_id, $excluded, true ) ) {
			return false;
		}

		$show_on = popcorn_get( $id, 'show_on' );

		switch ( $show_on ) {
			case 'everywhere':
				$match = true;
				break;

			case 'front':
				$match = is_front_page();
				break;

			case 'pages':
				$match = is_page();
				break;

			case 'posts':
				$match = is_singular( 'post' );
				break;

			case 'archives':
				$match = is_home() || is_archive() || is_search();
				break;

			case 'selected':
				$match = false;
				if ( $object_id && in_array( $object_id, self::id_list( popcorn_get( $id, 'include_ids' ) ), true ) ) {
					$match = true;
				}
				if ( ! $match && is_singular() ) {
					$match = self::in_terms( $object_id, popcorn_get( $id, 'terms' ) );
				}
				break;

			default:
				$match = false;
				break;
		}

		/**
		 * Filter whether a single popup qualifies for this request.
		 *
		 * @param bool    $match Qualifies.
		 * @param WP_Post $popup Popup post.
		 */
		return (bool) apply_filters( 'popcorn_popup_qualifies', $match, $popup );
	}

	/**
	 * Post/page ID for the current view, if there is one.
	 *
	 * @return int
	 */
	protected static function current_object_id() {
		if ( is_singular() ) {
			return (int) get_queried_object_id();
		}
		if ( is_front_page() && ! is_home() ) {
			return (int) get_queried_object_id();
		}
		if ( is_home() && ! is_front_page() ) {
			return (int) get_option( 'page_for_posts' );
		}
		return 0;
	}

	/**
	 * Parse a comma separated ID list.
	 *
	 * @param string $value Raw meta value.
	 * @return int[]
	 */
	protected static function id_list( $value ) {
		return array_values( array_filter( array_map( 'absint', explode( ',', (string) $value ) ) ) );
	}

	/**
	 * Does the current post sit in one of the given category/tag slugs?
	 *
	 * @param int    $post_id Post ID.
	 * @param string $slugs   Comma separated slugs.
	 * @return bool
	 */
	protected static function in_terms( $post_id, $slugs ) {
		$slugs = array_filter( array_map( 'trim', explode( ',', (string) $slugs ) ) );
		if ( empty( $slugs ) || ! $post_id ) {
			return false;
		}

		$taxonomies = get_object_taxonomies( get_post_type( $post_id ) );
		foreach ( $taxonomies as $taxonomy ) {
			if ( has_term( $slugs, $taxonomy, $post_id ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Is today inside the popup's date window?
	 *
	 * @param int $id Popup ID.
	 * @return bool
	 */
	protected static function in_schedule( $id ) {
		$start = popcorn_get( $id, 'start_date' );
		$end   = popcorn_get( $id, 'end_date' );
		if ( ! $start && ! $end ) {
			return true;
		}

		$today = current_time( 'Y-m-d' );

		if ( $start && $today < $start ) {
			return false;
		}
		if ( $end && $today > $end ) {
			return false;
		}
		return true;
	}

	/**
	 * Logged-in / logged-out rule.
	 *
	 * @param int $id Popup ID.
	 * @return bool
	 */
	protected static function right_visitor( $id ) {
		$rule = popcorn_get( $id, 'visitors' );
		if ( 'logged_in' === $rule ) {
			return is_user_logged_in();
		}
		if ( 'logged_out' === $rule ) {
			return ! is_user_logged_in();
		}
		return true;
	}
}
