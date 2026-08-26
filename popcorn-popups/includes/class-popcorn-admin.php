<?php
/**
 * Admin screens, assets, columns and the scoreboard.
 *
 * @package PopcornPopups
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Popcorn_Admin
 */
class Popcorn_Admin {

	/**
	 * Hook it up.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_notices', array( __CLASS__, 'welcome_notice' ) );
		add_action( 'wp_ajax_popcorn_search_posts', array( __CLASS__, 'ajax_search_posts' ) );

		add_filter( 'manage_' . POPCORN_CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . POPCORN_CPT . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
	}

	/**
	 * Load builder assets on the popup edit screens only.
	 *
	 * @param string $hook Current admin page.
	 */
	public static function assets( $hook ) {
		$screen    = get_current_screen();
		$is_editor = $screen && POPCORN_CPT === $screen->post_type && in_array( $hook, array( 'post.php', 'post-new.php' ), true );
		$is_stats  = $screen && false !== strpos( (string) $screen->id, 'popcorn-scoreboard' );

		if ( ! $is_editor && ! $is_stats ) {
			return;
		}

		wp_enqueue_style(
			'popcorn-admin',
			POPCORN_URL . 'assets/css/popcorn-admin.css',
			array(),
			POPCORN_VERSION
		);

		if ( ! $is_editor ) {
			return;
		}

		// The front-end look & engine power the live preview.
		wp_enqueue_style( 'popcorn', POPCORN_URL . 'assets/css/popcorn.css', array(), POPCORN_VERSION );
		wp_enqueue_script( 'popcorn', POPCORN_URL . 'assets/js/popcorn.js', array(), POPCORN_VERSION, true );

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'popcorn-admin',
			POPCORN_URL . 'assets/js/popcorn-admin.js',
			array( 'jquery', 'wp-color-picker', 'popcorn' ),
			POPCORN_VERSION,
			true
		);

		wp_localize_script(
			'popcorn-admin',
			'PopcornAdmin',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'popcorn_admin' ),
				'noResult' => __( 'Nothing found. Try fewer letters.', 'popcorn-popups' ),
				'empty'    => __( 'Your popup has no content yet — write something in the editor above!', 'popcorn-popups' ),
			)
		);
	}

	/**
	 * Add the scoreboard page under the Popups menu.
	 */
	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=' . POPCORN_CPT,
			__( 'Popups Scoreboard', 'popcorn-popups' ),
			__( '📊 Scoreboard', 'popcorn-popups' ),
			'edit_posts',
			'popcorn-scoreboard',
			array( __CLASS__, 'render_scoreboard' )
		);
	}

	/**
	 * One-off hello after activation.
	 */
	public static function welcome_notice() {
		if ( ! get_transient( 'popcorn_just_popped' ) || ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		delete_transient( 'popcorn_just_popped' );
		$url = admin_url( 'post-new.php?post_type=' . POPCORN_CPT );
		?>
		<div class="notice notice-success is-dismissible pcp-welcome">
			<p>
				<strong><?php esc_html_e( '🍿 Popcorn Popups is warmed up!', 'popcorn-popups' ); ?></strong>
				<?php esc_html_e( 'Build your first popup and point it at any page or post on the site.', 'popcorn-popups' ); ?>
				<a href="<?php echo esc_url( $url ); ?>" class="button button-primary" style="margin-left:8px;"><?php esc_html_e( 'Pop a new one', 'popcorn-popups' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * List table columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['pcp_trigger'] = __( 'Trigger', 'popcorn-popups' );
				$new['pcp_where']   = __( 'Where', 'popcorn-popups' );
				$new['pcp_score']   = __( 'Score', 'popcorn-popups' );
			}
		}
		return $new;
	}

	/**
	 * Render custom column cells.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Popup ID.
	 */
	public static function column_content( $column, $post_id ) {
		$fields = Popcorn_Fields::flat();

		switch ( $column ) {
			case 'pcp_trigger':
				$trigger = popcorn_get( $post_id, 'trigger' );
				$choice  = isset( $fields['trigger']['choices'][ $trigger ] ) ? $fields['trigger']['choices'][ $trigger ] : array( '❓', $trigger, '' );
				$extra   = '';
				if ( 'time' === $trigger ) {
					$extra = sprintf( /* translators: %d: seconds */ __( 'after %ds', 'popcorn-popups' ), (int) popcorn_get( $post_id, 'trigger_delay' ) );
				} elseif ( 'scroll' === $trigger ) {
					$extra = sprintf( /* translators: %d: percentage */ __( 'at %d%%', 'popcorn-popups' ), (int) popcorn_get( $post_id, 'trigger_scroll' ) );
				} elseif ( 'idle' === $trigger ) {
					$extra = sprintf( /* translators: %d: seconds */ __( 'after %ds idle', 'popcorn-popups' ), (int) popcorn_get( $post_id, 'trigger_idle' ) );
				}
				echo esc_html( $choice[0] . ' ' . $choice[1] );
				if ( $extra ) {
					echo '<br><span class="pcp-muted">' . esc_html( $extra ) . '</span>';
				}
				break;

			case 'pcp_where':
				$show_on = popcorn_get( $post_id, 'show_on' );
				$choice  = isset( $fields['show_on']['choices'][ $show_on ] ) ? $fields['show_on']['choices'][ $show_on ] : array( '❓', $show_on, '' );
				echo esc_html( $choice[0] . ' ' . $choice[1] );
				if ( 'selected' === $show_on ) {
					$ids = array_filter( array_map( 'absint', explode( ',', (string) popcorn_get( $post_id, 'include_ids' ) ) ) );
					echo '<br><span class="pcp-muted">' . esc_html(
						sprintf(
							/* translators: %d: number of items */
							_n( '%d item', '%d items', count( $ids ), 'popcorn-popups' ),
							count( $ids )
						)
					) . '</span>';
				}
				break;

			case 'pcp_score':
				$views  = (int) get_post_meta( $post_id, POPCORN_PREFIX . 'stat_views', true );
				$clicks = (int) get_post_meta( $post_id, POPCORN_PREFIX . 'stat_clicks', true );
				$rate   = $views ? round( ( $clicks / $views ) * 100, 1 ) : 0;
				printf(
					'<strong>%s</strong> %s<br><span class="pcp-muted">%s</span>',
					esc_html( number_format_i18n( $views ) ),
					esc_html__( 'pops', 'popcorn-popups' ),
					esc_html( sprintf( /* translators: %s: percentage */ __( '%s%% click rate', 'popcorn-popups' ), $rate ) )
				);
				break;
		}
	}

	/**
	 * The scoreboard page.
	 */
	public static function render_scoreboard() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You are not allowed to peek at the scoreboard.', 'popcorn-popups' ) );
		}

		$popups = get_posts(
			array(
				'post_type'      => POPCORN_CPT,
				'post_status'    => array( 'publish', 'draft', 'pending' ),
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$rows = array();
		$tv   = 0;
		$tc   = 0;
		foreach ( $popups as $popup ) {
			$views  = (int) get_post_meta( $popup->ID, POPCORN_PREFIX . 'stat_views', true );
			$clicks = (int) get_post_meta( $popup->ID, POPCORN_PREFIX . 'stat_clicks', true );
			$closes = (int) get_post_meta( $popup->ID, POPCORN_PREFIX . 'stat_closes', true );
			$tv    += $views;
			$tc    += $clicks;
			$rows[] = array(
				'post'   => $popup,
				'views'  => $views,
				'clicks' => $clicks,
				'closes' => $closes,
				'rate'   => $views ? round( ( $clicks / $views ) * 100, 1 ) : 0,
			);
		}

		usort(
			$rows,
			static function ( $a, $b ) {
				return $b['views'] <=> $a['views'];
			}
		);

		$total_rate = $tv ? round( ( $tc / $tv ) * 100, 1 ) : 0;
		?>
		<div class="wrap pcp-scoreboard">
			<h1><?php esc_html_e( '📊 Popups Scoreboard', 'popcorn-popups' ); ?></h1>
			<p class="pcp-muted"><?php echo esc_html( self::pep_talk( $tv, $total_rate ) ); ?></p>

			<div class="pcp-bigstats">
				<div class="pcp-bigstat">
					<span class="pcp-bigstat__n"><?php echo esc_html( number_format_i18n( $tv ) ); ?></span>
					<span class="pcp-bigstat__l"><?php esc_html_e( 'total pops', 'popcorn-popups' ); ?></span>
				</div>
				<div class="pcp-bigstat">
					<span class="pcp-bigstat__n"><?php echo esc_html( number_format_i18n( $tc ) ); ?></span>
					<span class="pcp-bigstat__l"><?php esc_html_e( 'total clicks', 'popcorn-popups' ); ?></span>
				</div>
				<div class="pcp-bigstat pcp-bigstat--hero">
					<span class="pcp-bigstat__n"><?php echo esc_html( $total_rate ); ?>%</span>
					<span class="pcp-bigstat__l"><?php esc_html_e( 'click rate', 'popcorn-popups' ); ?></span>
				</div>
				<div class="pcp-bigstat">
					<span class="pcp-bigstat__n"><?php echo esc_html( number_format_i18n( count( $rows ) ) ); ?></span>
					<span class="pcp-bigstat__l"><?php esc_html_e( 'popups built', 'popcorn-popups' ); ?></span>
				</div>
			</div>

			<?php if ( empty( $rows ) ) : ?>
				<div class="pcp-empty">
					<p style="font-size:48px;margin:0;">🍿</p>
					<p><?php esc_html_e( 'No popups yet. The scoreboard is patiently waiting.', 'popcorn-popups' ); ?></p>
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . POPCORN_CPT ) ); ?>">
						<?php esc_html_e( 'Pop a new one', 'popcorn-popups' ); ?>
					</a>
				</div>
			<?php else : ?>
				<table class="widefat striped pcp-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Popup', 'popcorn-popups' ); ?></th>
							<th><?php esc_html_e( 'Status', 'popcorn-popups' ); ?></th>
							<th><?php esc_html_e( 'Pops', 'popcorn-popups' ); ?></th>
							<th><?php esc_html_e( 'Clicks', 'popcorn-popups' ); ?></th>
							<th><?php esc_html_e( 'Dismissals', 'popcorn-popups' ); ?></th>
							<th><?php esc_html_e( 'Click rate', 'popcorn-popups' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $row['post']->ID ) ); ?>">
									<strong><?php echo esc_html( get_the_title( $row['post'] ) ? get_the_title( $row['post'] ) : __( '(no title)', 'popcorn-popups' ) ); ?></strong>
								</a>
							</td>
							<td>
								<?php
								echo 'publish' === $row['post']->post_status
									? '<span class="pcp-pill pcp-pill--live">' . esc_html__( 'Live', 'popcorn-popups' ) . '</span>'
									: '<span class="pcp-pill">' . esc_html__( 'Draft', 'popcorn-popups' ) . '</span>';
								?>
							</td>
							<td><?php echo esc_html( number_format_i18n( $row['views'] ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $row['clicks'] ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $row['closes'] ) ); ?></td>
							<td>
								<div class="pcp-bar" style="--pcp-w:<?php echo esc_attr( min( 100, $row['rate'] ) ); ?>%">
									<span><?php echo esc_html( $row['rate'] ); ?>%</span>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * A mildly encouraging line about the numbers.
	 *
	 * @param int   $views Total views.
	 * @param float $rate  Click rate percentage.
	 * @return string
	 */
	public static function pep_talk( $views, $rate ) {
		if ( $views < 1 ) {
			return __( 'No pops yet. Publish it, visit the site, and watch this space.', 'popcorn-popups' );
		}
		if ( $views < 25 ) {
			return __( 'Early days — the numbers below are basically a rumour so far.', 'popcorn-popups' );
		}
		if ( $rate >= 20 ) {
			return __( 'Over 20% click rate. Frame this popup and hang it on the wall. 🏆', 'popcorn-popups' );
		}
		if ( $rate >= 8 ) {
			return __( 'Solid numbers. Your popup is pulling its weight. 💪', 'popcorn-popups' );
		}
		if ( $rate >= 2 ) {
			return __( 'Respectable. A punchier button label might squeeze out more. ✍️', 'popcorn-popups' );
		}
		return __( 'People are seeing it and shrugging. Try a different offer, or a gentler trigger. 🤔', 'popcorn-popups' );
	}

	/**
	 * AJAX: search pages and posts for the picker.
	 */
	public static function ajax_search_posts() {
		check_ajax_referer( 'popcorn_admin', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array(), 403 );
		}

		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( array() );
		}

		$types = get_post_types(
			array(
				'public' => true,
			)
		);
		unset( $types['attachment'], $types[ POPCORN_CPT ] );

		$found = get_posts(
			array(
				'post_type'        => array_values( $types ),
				'post_status'      => array( 'publish', 'draft', 'private' ),
				's'                => $term,
				'posts_per_page'   => 15,
				'suppress_filters' => false,
			)
		);

		$results = array();
		foreach ( $found as $item ) {
			// The search covers drafts and private posts, so check this user is
			// actually allowed to see each one before naming it back to them.
			if ( ! current_user_can( 'read_post', $item->ID ) ) {
				continue;
			}

			$obj       = get_post_type_object( $item->post_type );
			$results[] = array(
				'id'    => $item->ID,
				'title' => get_the_title( $item ) ? get_the_title( $item ) : __( '(no title)', 'popcorn-popups' ),
				'type'  => $obj ? $obj->labels->singular_name : $item->post_type,
			);
		}

		wp_send_json_success( $results );
	}
}
