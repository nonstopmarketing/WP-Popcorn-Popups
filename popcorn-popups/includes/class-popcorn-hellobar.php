<?php
/**
 * The Hello Bar: one global, full-width announcement bar.
 *
 * @package PopcornPopups
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Popcorn_Hellobar
 */
class Popcorn_Hellobar {

	/**
	 * Option name.
	 */
	const OPTION = 'popcorn_hellobar';

	/**
	 * Settings group.
	 */
	const GROUP = 'popcorn_hellobar_group';

	/**
	 * Has the bar already been printed this request?
	 *
	 * @var bool
	 */
	protected static $rendered = false;

	/**
	 * Pin the bar to the window even though it was set to sit in the flow,
	 * because the theme never fired wp_body_open.
	 *
	 * @var bool
	 */
	protected static $force_stuck = false;

	/**
	 * Hook it up.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 20 );
		add_action( 'wp_body_open', array( __CLASS__, 'render_inline' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_footer' ), 4 );
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'enabled'      => 0,
			'position'     => 'top',
			'sticky'       => 1,
			'push_page'    => 1,
			'emoji'        => '👋',
			'message'      => __( 'Hello! We have something to tell you.', 'popcorn-popups' ),
			'link_text'    => __( 'Take a look', 'popcorn-popups' ),
			'link_url'     => '',
			'new_tab'      => 0,
			'bg_color'     => '#1f1a17',
			'text_color'   => '#fffaf0',
			'btn_bg'       => '#ff5c39',
			'btn_text'     => '#ffffff',
			'dismissible'  => 1,
			'dismiss_days' => 30,
			'show_on'      => 'everywhere',
			'exclude_ids'  => '',
		);
	}

	/**
	 * Current settings, defaults filled in.
	 *
	 * @return array
	 */
	public static function settings() {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, self::defaults() );
	}

	/**
	 * Add the settings page.
	 */
	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=' . POPCORN_CPT,
			__( 'Hello Bar', 'popcorn-popups' ),
			__( '👋 Hello Bar', 'popcorn-popups' ),
			'manage_options',
			'popcorn-hellobar',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register the option with the Settings API.
	 */
	public static function register() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Clean submitted settings.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$clean    = $defaults;

		if ( ! is_array( $input ) ) {
			return $clean;
		}

		$clean['enabled']     = empty( $input['enabled'] ) ? 0 : 1;
		$clean['sticky']      = empty( $input['sticky'] ) ? 0 : 1;
		$clean['push_page']   = empty( $input['push_page'] ) ? 0 : 1;
		$clean['new_tab']     = empty( $input['new_tab'] ) ? 0 : 1;
		$clean['dismissible'] = empty( $input['dismissible'] ) ? 0 : 1;

		$clean['position'] = in_array( $input['position'] ?? '', array( 'top', 'bottom' ), true )
			? $input['position']
			: $defaults['position'];

		$clean['show_on'] = in_array( $input['show_on'] ?? '', array( 'everywhere', 'front', 'pages', 'posts' ), true )
			? $input['show_on']
			: $defaults['show_on'];

		$clean['emoji']     = sanitize_text_field( $input['emoji'] ?? '' );
		$clean['message']   = wp_kses_post( $input['message'] ?? '' );
		$clean['link_text'] = sanitize_text_field( $input['link_text'] ?? '' );
		$clean['link_url']  = esc_url_raw( trim( (string) ( $input['link_url'] ?? '' ) ) );

		foreach ( array( 'bg_color', 'text_color', 'btn_bg', 'btn_text' ) as $key ) {
			$hex           = sanitize_hex_color( $input[ $key ] ?? '' );
			$clean[ $key ] = $hex ? $hex : $defaults[ $key ];
		}

		$days                  = isset( $input['dismiss_days'] ) ? (int) $input['dismiss_days'] : $defaults['dismiss_days'];
		$clean['dismiss_days'] = max( 1, min( 3650, $days ) );

		$ids                  = array_filter( array_map( 'absint', preg_split( '/[\s,]+/', (string) ( $input['exclude_ids'] ?? '' ) ) ) );
		$clean['exclude_ids'] = implode( ',', array_unique( $ids ) );

		return $clean;
	}

	/**
	 * Colour picker on the settings page.
	 *
	 * @param string $hook Current admin page.
	 */
	public static function admin_assets( $hook ) {
		if ( false === strpos( (string) $hook, 'popcorn-hellobar' ) ) {
			return;
		}

		wp_enqueue_style( 'popcorn-admin', POPCORN_URL . 'assets/css/popcorn-admin.css', array(), POPCORN_VERSION );
		wp_enqueue_style( 'popcorn-hellobar', POPCORN_URL . 'assets/css/popcorn-hellobar.css', array(), POPCORN_VERSION );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'popcorn-hellobar-admin',
			POPCORN_URL . 'assets/js/popcorn-hellobar-admin.js',
			array( 'jquery', 'wp-color-picker' ),
			POPCORN_VERSION,
			true
		);
	}

	/**
	 * The settings screen.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to change the Hello Bar.', 'popcorn-popups' ) );
		}

		$s = self::settings();
		?>
		<div class="wrap pcp-hellobar-admin">
			<h1><?php esc_html_e( '👋 Hello Bar', 'popcorn-popups' ); ?></h1>
			<p class="pcp-muted">
				<?php esc_html_e( 'One full-width bar across every page of the site. Great for an announcement, a sale, or a slightly needy hello.', 'popcorn-popups' ); ?>
			</p>

			<div class="pcp-hb-preview-wrap">
				<span class="pcp-hb-preview-caption"><?php esc_html_e( 'Live preview', 'popcorn-popups' ); ?></span>
				<div class="popcorn-hello popcorn-hello--preview" id="pcp-hb-preview"
					style="--pcp-hb-bg:<?php echo esc_attr( $s['bg_color'] ); ?>;--pcp-hb-text:<?php echo esc_attr( $s['text_color'] ); ?>;--pcp-hb-btn:<?php echo esc_attr( $s['btn_bg'] ); ?>;--pcp-hb-btn-ink:<?php echo esc_attr( $s['btn_text'] ); ?>;">
					<div class="popcorn-hello__inner">
						<span class="popcorn-hello__emoji" id="pcp-hb-p-emoji"><?php echo esc_html( $s['emoji'] ); ?></span>
						<span class="popcorn-hello__msg" id="pcp-hb-p-msg"><?php echo wp_kses_post( $s['message'] ); ?></span>
						<span class="popcorn-hello__cta" id="pcp-hb-p-cta"><?php echo esc_html( $s['link_text'] ); ?></span>
					</div>
					<button type="button" class="popcorn-hello__x" aria-hidden="true" tabindex="-1">&times;</button>
				</div>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>
				<?php $name = self::OPTION; ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Turn it on', 'popcorn-popups' ); ?></th>
						<td>
							<label class="pcp-toggle" for="pcp-hb-enabled">
								<input type="hidden" name="<?php echo esc_attr( $name ); ?>[enabled]" value="0">
								<input type="checkbox" id="pcp-hb-enabled" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $s['enabled'], 1 ); ?>>
								<span class="pcp-toggle__track" aria-hidden="true"><span class="pcp-toggle__thumb"></span></span>
								<span class="pcp-toggle__label"><?php esc_html_e( 'Show the Hello Bar on the site', 'popcorn-popups' ); ?></span>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="pcp-hb-emoji"><?php esc_html_e( 'Emoji', 'popcorn-popups' ); ?></label></th>
						<td>
							<input type="text" id="pcp-hb-emoji" class="regular-text" style="max-width:80px;" name="<?php echo esc_attr( $name ); ?>[emoji]" value="<?php echo esc_attr( $s['emoji'] ); ?>">
							<p class="description"><?php esc_html_e( 'Sits before the message. Leave empty for none.', 'popcorn-popups' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="pcp-hb-message"><?php esc_html_e( 'Message', 'popcorn-popups' ); ?></label></th>
						<td>
							<textarea id="pcp-hb-message" name="<?php echo esc_attr( $name ); ?>[message]" rows="2" class="large-text"><?php echo esc_textarea( $s['message'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Basic HTML is allowed, so <strong>bold</strong> and inline links work.', 'popcorn-popups' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="pcp-hb-link-text"><?php esc_html_e( 'Button label', 'popcorn-popups' ); ?></label></th>
						<td>
							<input type="text" id="pcp-hb-link-text" class="regular-text" name="<?php echo esc_attr( $name ); ?>[link_text]" value="<?php echo esc_attr( $s['link_text'] ); ?>">
							<p class="description"><?php esc_html_e( 'Leave empty for a message-only bar with no button.', 'popcorn-popups' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="pcp-hb-link-url"><?php esc_html_e( 'Button link', 'popcorn-popups' ); ?></label></th>
						<td>
							<input type="url" id="pcp-hb-link-url" class="regular-text" placeholder="https://" name="<?php echo esc_attr( $name ); ?>[link_url]" value="<?php echo esc_attr( $s['link_url'] ); ?>">
							<p>
								<label>
									<input type="hidden" name="<?php echo esc_attr( $name ); ?>[new_tab]" value="0">
									<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[new_tab]" value="1" <?php checked( $s['new_tab'], 1 ); ?>>
									<?php esc_html_e( 'Open in a new tab', 'popcorn-popups' ); ?>
								</label>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Position', 'popcorn-popups' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $name ); ?>[position]" id="pcp-hb-position">
								<option value="top" <?php selected( $s['position'], 'top' ); ?>><?php esc_html_e( 'Top of the window', 'popcorn-popups' ); ?></option>
								<option value="bottom" <?php selected( $s['position'], 'bottom' ); ?>><?php esc_html_e( 'Bottom of the window', 'popcorn-popups' ); ?></option>
							</select>
							<p style="margin-top:10px;">
								<label>
									<input type="hidden" name="<?php echo esc_attr( $name ); ?>[sticky]" value="0">
									<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[sticky]" value="1" <?php checked( $s['sticky'], 1 ); ?>>
									<?php esc_html_e( 'Stick it to the window so it stays put while scrolling', 'popcorn-popups' ); ?>
								</label>
							</p>
							<p>
								<label>
									<input type="hidden" name="<?php echo esc_attr( $name ); ?>[push_page]" value="0">
									<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[push_page]" value="1" <?php checked( $s['push_page'], 1 ); ?>>
									<?php esc_html_e( 'Push the page down so the bar never covers the header', 'popcorn-popups' ); ?>
								</label>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Colours', 'popcorn-popups' ); ?></th>
						<td class="pcp-hb-colors">
							<span>
								<label for="pcp-hb-bg"><?php esc_html_e( 'Bar background', 'popcorn-popups' ); ?></label>
								<input type="text" id="pcp-hb-bg" class="pcp-color" data-preview="bg" name="<?php echo esc_attr( $name ); ?>[bg_color]" value="<?php echo esc_attr( $s['bg_color'] ); ?>">
							</span>
							<span>
								<label for="pcp-hb-text"><?php esc_html_e( 'Bar text', 'popcorn-popups' ); ?></label>
								<input type="text" id="pcp-hb-text" class="pcp-color" data-preview="text" name="<?php echo esc_attr( $name ); ?>[text_color]" value="<?php echo esc_attr( $s['text_color'] ); ?>">
							</span>
							<span>
								<label for="pcp-hb-btn"><?php esc_html_e( 'Button background', 'popcorn-popups' ); ?></label>
								<input type="text" id="pcp-hb-btn" class="pcp-color" data-preview="btn" name="<?php echo esc_attr( $name ); ?>[btn_bg]" value="<?php echo esc_attr( $s['btn_bg'] ); ?>">
							</span>
							<span>
								<label for="pcp-hb-btn-text"><?php esc_html_e( 'Button text', 'popcorn-popups' ); ?></label>
								<input type="text" id="pcp-hb-btn-text" class="pcp-color" data-preview="btnink" name="<?php echo esc_attr( $name ); ?>[btn_text]" value="<?php echo esc_attr( $s['btn_text'] ); ?>">
							</span>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Dismissing', 'popcorn-popups' ); ?></th>
						<td>
							<label>
								<input type="hidden" name="<?php echo esc_attr( $name ); ?>[dismissible]" value="0">
								<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[dismissible]" value="1" <?php checked( $s['dismissible'], 1 ); ?>>
								<?php esc_html_e( 'Let visitors close the bar', 'popcorn-popups' ); ?>
							</label>
							<p style="margin-top:10px;">
								<label for="pcp-hb-days"><?php esc_html_e( 'Stay closed for', 'popcorn-popups' ); ?></label>
								<input type="number" id="pcp-hb-days" min="1" max="3650" step="1" style="width:90px;" name="<?php echo esc_attr( $name ); ?>[dismiss_days]" value="<?php echo esc_attr( $s['dismiss_days'] ); ?>">
								<?php esc_html_e( 'days (stored in a cookie on their device)', 'popcorn-popups' ); ?>
							</p>
							<p class="description"><?php esc_html_e( 'Editing the message or the button brings the bar back for everyone, even people who closed the old one.', 'popcorn-popups' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Where', 'popcorn-popups' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $name ); ?>[show_on]">
								<option value="everywhere" <?php selected( $s['show_on'], 'everywhere' ); ?>><?php esc_html_e( '🌍 Everywhere', 'popcorn-popups' ); ?></option>
								<option value="front" <?php selected( $s['show_on'], 'front' ); ?>><?php esc_html_e( '🏠 Front page only', 'popcorn-popups' ); ?></option>
								<option value="pages" <?php selected( $s['show_on'], 'pages' ); ?>><?php esc_html_e( '📄 All pages', 'popcorn-popups' ); ?></option>
								<option value="posts" <?php selected( $s['show_on'], 'posts' ); ?>><?php esc_html_e( '📝 All posts', 'popcorn-popups' ); ?></option>
							</select>
							<p style="margin-top:10px;">
								<label for="pcp-hb-exclude"><?php esc_html_e( 'Never show on these page or post IDs', 'popcorn-popups' ); ?></label><br>
								<input type="text" id="pcp-hb-exclude" class="regular-text" placeholder="12, 34, 56" name="<?php echo esc_attr( $name ); ?>[exclude_ids]" value="<?php echo esc_attr( $s['exclude_ids'] ); ?>">
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save the Hello Bar', 'popcorn-popups' ) ); ?>
			</form>
		</div>
		<?php
	}

	/* ------------------------------------------------------- front end */

	/**
	 * Should the bar appear on this request?
	 *
	 * @return bool
	 */
	public static function visible() {
		if ( is_admin() || is_feed() || is_embed() || is_preview() ) {
			return false;
		}

		$s = self::settings();

		if ( empty( $s['enabled'] ) ) {
			return false;
		}

		if ( '' === trim( wp_strip_all_tags( $s['message'] ) ) && '' === trim( $s['link_text'] ) ) {
			return false;
		}

		$excluded = array_filter( array_map( 'absint', explode( ',', (string) $s['exclude_ids'] ) ) );
		if ( $excluded && is_singular() && in_array( (int) get_queried_object_id(), $excluded, true ) ) {
			return false;
		}

		switch ( $s['show_on'] ) {
			case 'front':
				$show = is_front_page();
				break;
			case 'pages':
				$show = is_page();
				break;
			case 'posts':
				$show = is_singular( 'post' );
				break;
			case 'everywhere':
			default:
				$show = true;
				break;
		}

		/**
		 * Filter whether the Hello Bar shows on this request.
		 *
		 * @param bool  $show Visible.
		 * @param array $s    Settings.
		 */
		return (bool) apply_filters( 'popcorn_hellobar_visible', $show, $s );
	}

	/**
	 * A short signature of the bar's content, so editing it brings the bar back
	 * for people who dismissed the previous version.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	public static function version( $s ) {
		return substr( md5( $s['message'] . '|' . $s['link_text'] . '|' . $s['link_url'] . '|' . $s['emoji'] ), 0, 8 );
	}

	/**
	 * Enqueue bar assets when it is going to show.
	 */
	public static function assets() {
		if ( ! self::visible() ) {
			return;
		}

		wp_enqueue_style( 'popcorn-hellobar', POPCORN_URL . 'assets/css/popcorn-hellobar.css', array(), POPCORN_VERSION );
		wp_enqueue_script( 'popcorn-hellobar', POPCORN_URL . 'assets/js/popcorn-hellobar.js', array(), POPCORN_VERSION, true );
	}

	/**
	 * Non-sticky top bars render in the normal flow, right after <body>.
	 */
	public static function render_inline() {
		$s = self::settings();
		if ( 'top' === $s['position'] && empty( $s['sticky'] ) ) {
			self::render();
		}
	}

	/**
	 * Everything else renders in the footer.
	 */
	public static function render_footer() {
		$s             = self::settings();
		$is_inline_top = ( 'top' === $s['position'] && empty( $s['sticky'] ) );

		// Themes older than WP 5.2 may not fire wp_body_open. Rather than strand
		// a top bar at the bottom of the page, pin it to the window instead.
		if ( $is_inline_top && ! self::$rendered ) {
			self::$force_stuck = true;
			self::render();
			return;
		}

		if ( ! $is_inline_top ) {
			self::render();
		}
	}

	/**
	 * Print the bar.
	 */
	public static function render() {
		if ( self::$rendered || ! self::visible() ) {
			return;
		}

		self::$rendered = true;

		$s       = self::settings();
		$version = self::version( $s );

		$stuck = $s['sticky'] || self::$force_stuck;

		$classes = array(
			'popcorn-hello',
			'popcorn-hello--' . $s['position'],
			$stuck ? 'popcorn-hello--stuck' : 'popcorn-hello--inline',
		);

		$style = sprintf(
			'--pcp-hb-bg:%1$s;--pcp-hb-text:%2$s;--pcp-hb-btn:%3$s;--pcp-hb-btn-ink:%4$s;',
			esc_attr( $s['bg_color'] ),
			esc_attr( $s['text_color'] ),
			esc_attr( $s['btn_bg'] ),
			esc_attr( $s['btn_text'] )
		);
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			style="<?php echo esc_attr( $style ); ?>"
			role="region"
			aria-label="<?php esc_attr_e( 'Site announcement', 'popcorn-popups' ); ?>"
			data-version="<?php echo esc_attr( $version ); ?>"
			data-days="<?php echo esc_attr( $s['dismiss_days'] ); ?>"
			data-push="<?php echo esc_attr( ( $stuck && $s['push_page'] ) ? '1' : '0' ); ?>"
			hidden>
			<div class="popcorn-hello__inner">
				<?php if ( '' !== trim( $s['emoji'] ) ) : ?>
					<span class="popcorn-hello__emoji" aria-hidden="true"><?php echo esc_html( $s['emoji'] ); ?></span>
				<?php endif; ?>

				<span class="popcorn-hello__msg"><?php echo wp_kses_post( $s['message'] ); ?></span>

				<?php if ( '' !== trim( $s['link_text'] ) ) : ?>
					<?php if ( $s['link_url'] ) : ?>
						<a class="popcorn-hello__cta"
							href="<?php echo esc_url( $s['link_url'] ); ?>"
							<?php echo $s['new_tab'] ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
							<?php echo esc_html( $s['link_text'] ); ?>
						</a>
					<?php else : ?>
						<span class="popcorn-hello__cta popcorn-hello__cta--flat"><?php echo esc_html( $s['link_text'] ); ?></span>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<?php if ( $s['dismissible'] ) : ?>
				<button type="button" class="popcorn-hello__x" aria-label="<?php esc_attr_e( 'Close announcement', 'popcorn-popups' ); ?>">&times;</button>
			<?php endif; ?>
		</div>
		<?php
	}
}
