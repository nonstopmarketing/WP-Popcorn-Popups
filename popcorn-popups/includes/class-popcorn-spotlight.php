<?php
/**
 * The Spotlight Bar: one global, full-width announcement bar.
 *
 * @package PopcornPopups
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Popcorn_Spotlight
 */
class Popcorn_Spotlight {

	/**
	 * Option name.
	 */
	const OPTION = 'popcorn_spotlight';

	/**
	 * Option name used before the bar was renamed, kept so existing settings
	 * survive the update.
	 */
	const LEGACY_OPTION = 'popcorn_hellobar';

	/**
	 * Settings group.
	 */
	const GROUP = 'popcorn_spotlight_group';

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
		add_action( 'admin_init', array( __CLASS__, 'migrate' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 20 );
		add_action( 'wp_body_open', array( __CLASS__, 'render_inline' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_footer' ), 4 );
	}

	/**
	 * Carry settings over from the old Hello Bar option name, once.
	 */
	public static function migrate() {
		if ( false !== get_option( self::OPTION, false ) ) {
			return;
		}

		$legacy = get_option( self::LEGACY_OPTION, false );

		if ( is_array( $legacy ) ) {
			update_option( self::OPTION, wp_parse_args( $legacy, self::defaults() ) );
			delete_option( self::LEGACY_OPTION );
		}
	}

	/**
	 * How many buttons the bar offers.
	 */
	const BUTTONS = 3;

	/**
	 * Setting keys for one button.
	 *
	 * The first button keeps the unprefixed names it shipped with, so settings
	 * saved before there was a second or third button still load.
	 *
	 * @param int $n Button number, from 1.
	 * @return array
	 */
	protected static function button_keys( $n ) {
		if ( 1 === $n ) {
			return array(
				'text'    => 'link_text',
				'url'     => 'link_url',
				'new_tab' => 'new_tab',
				'style'   => 'link_style',
			);
		}

		return array(
			'text'    => 'link' . $n . '_text',
			'url'     => 'link' . $n . '_url',
			'new_tab' => 'link' . $n . '_new_tab',
			'style'   => 'link' . $n . '_style',
		);
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'enabled'       => 0,
			'position'      => 'top',
			'sticky'        => 1,
			'push_page'     => 1,

			'content_mode'  => 'simple',
			'emoji'         => '💡',
			'message'       => __( 'Something worth shouting about goes here.', 'popcorn-popups' ),
			'custom_html'   => '',

			'link_text'     => __( 'Take a look', 'popcorn-popups' ),
			'link_url'      => '',
			'new_tab'       => 0,
			'link_style'    => 'solid',

			'link2_text'    => '',
			'link2_url'     => '',
			'link2_new_tab' => 0,
			'link2_style'   => 'outline',

			'link3_text'    => '',
			'link3_url'     => '',
			'link3_new_tab' => 0,
			'link3_style'   => 'plain',

			'bg_color'      => '#1f1a17',
			'text_color'    => '#fffaf0',
			'btn_bg'        => '#ff5c39',
			'btn_text'      => '#ffffff',

			'dismissible'   => 1,
			'reappear'      => 'days',
			'dismiss_days'  => 30,

			'show_on'       => 'everywhere',
			'exclude_ids'   => '',
		);
	}

	/**
	 * Current settings, defaults filled in.
	 *
	 * @return array
	 */
	public static function settings() {
		$saved = get_option( self::OPTION, false );

		if ( false === $saved ) {
			$saved = get_option( self::LEGACY_OPTION, array() );
		}

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
			__( 'Spotlight Bar', 'popcorn-popups' ),
			__( '💡 Spotlight Bar', 'popcorn-popups' ),
			'manage_options',
			'popcorn-spotlight',
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

		$flags = array( 'enabled', 'sticky', 'push_page', 'dismissible' );
		$enums = array(
			'position'     => array( 'top', 'bottom' ),
			'content_mode' => array( 'simple', 'html' ),
			'reappear'     => array( 'always', 'session', 'days', 'forever' ),
			'show_on'      => array( 'everywhere', 'front', 'pages', 'posts' ),
		);

		for ( $n = 1; $n <= self::BUTTONS; $n++ ) {
			$keys                    = self::button_keys( $n );
			$flags[]                 = $keys['new_tab'];
			$enums[ $keys['style'] ] = array( 'solid', 'outline', 'plain' );
			$clean[ $keys['text'] ]  = sanitize_text_field( $input[ $keys['text'] ] ?? '' );
			$clean[ $keys['url'] ]   = esc_url_raw( trim( (string) ( $input[ $keys['url'] ] ?? '' ) ) );
		}

		foreach ( $flags as $flag ) {
			$clean[ $flag ] = empty( $input[ $flag ] ) ? 0 : 1;
		}

		foreach ( $enums as $key => $allowed ) {
			$value         = isset( $input[ $key ] ) ? $input[ $key ] : '';
			$clean[ $key ] = in_array( $value, $allowed, true ) ? $value : $defaults[ $key ];
		}

		$clean['emoji']   = sanitize_text_field( $input['emoji'] ?? '' );
		$clean['message'] = wp_kses_post( $input['message'] ?? '' );

		/**
		 * Filter the tags allowed in the Spotlight Bar's custom HTML.
		 *
		 * Defaults to the same set WordPress allows in post content. Widen it
		 * here if you need something exotic, and know what you are letting in.
		 *
		 * @param array $tags Allowed tags, in wp_kses format.
		 */
		$allowed_html         = apply_filters( 'popcorn_spotlight_allowed_html', wp_kses_allowed_html( 'post' ) );
		$clean['custom_html'] = wp_kses( (string) ( $input['custom_html'] ?? '' ), $allowed_html );

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
	 * Settings screen assets.
	 *
	 * @param string $hook Current admin page.
	 */
	public static function admin_assets( $hook ) {
		if ( false === strpos( (string) $hook, 'popcorn-spotlight' ) ) {
			return;
		}

		wp_enqueue_style( 'popcorn-admin', POPCORN_URL . 'assets/css/popcorn-admin.css', array(), POPCORN_VERSION );
		wp_enqueue_style( 'popcorn-spotlight', POPCORN_URL . 'assets/css/popcorn-spotlight.css', array(), POPCORN_VERSION );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'popcorn-spotlight-admin',
			POPCORN_URL . 'assets/js/popcorn-spotlight-admin.js',
			array( 'jquery', 'wp-color-picker' ),
			POPCORN_VERSION,
			true
		);
	}

	/**
	 * One button's row of settings fields.
	 *
	 * @param int    $n    Button number, from 1.
	 * @param array  $s    Current settings.
	 * @param string $name Option name, for the field names.
	 */
	protected static function button_row( $n, $s, $name ) {
		$keys = self::button_keys( $n );
		$id   = 'pcp-hb-btn' . $n;

		$titles = array(
			1 => __( 'Button one', 'popcorn-popups' ),
			2 => __( 'Button two', 'popcorn-popups' ),
			3 => __( 'Button three', 'popcorn-popups' ),
		);

		$looks = array(
			'solid'   => __( 'Solid button', 'popcorn-popups' ),
			'outline' => __( 'Outline button', 'popcorn-popups' ),
			'plain'   => __( 'Plain link', 'popcorn-popups' ),
		);
		?>
		<tr>
			<th scope="row">
				<?php echo esc_html( isset( $titles[ $n ] ) ? $titles[ $n ] : sprintf( /* translators: %d: button number */ __( 'Button %d', 'popcorn-popups' ), $n ) ); ?>
			</th>
			<td class="pcp-hb-btnfields" data-button="<?php echo esc_attr( $n ); ?>">
				<span>
					<label for="<?php echo esc_attr( $id ); ?>-text"><?php esc_html_e( 'Label', 'popcorn-popups' ); ?></label>
					<input type="text" id="<?php echo esc_attr( $id ); ?>-text" class="regular-text"
						name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $keys['text'] ); ?>]"
						value="<?php echo esc_attr( $s[ $keys['text'] ] ); ?>">
				</span>
				<span>
					<label for="<?php echo esc_attr( $id ); ?>-url"><?php esc_html_e( 'Link', 'popcorn-popups' ); ?></label>
					<input type="url" id="<?php echo esc_attr( $id ); ?>-url" class="regular-text" placeholder="https://"
						name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $keys['url'] ); ?>]"
						value="<?php echo esc_attr( $s[ $keys['url'] ] ); ?>">
				</span>
				<span>
					<label for="<?php echo esc_attr( $id ); ?>-style"><?php esc_html_e( 'Look', 'popcorn-popups' ); ?></label>
					<select id="<?php echo esc_attr( $id ); ?>-style" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $keys['style'] ); ?>]">
						<?php foreach ( $looks as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $s[ $keys['style'] ], $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</span>
				<span class="pcp-hb-newtab">
					<label>
						<input type="hidden" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $keys['new_tab'] ); ?>]" value="0">
						<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $keys['new_tab'] ); ?>]" value="1" <?php checked( $s[ $keys['new_tab'] ], 1 ); ?>>
						<?php esc_html_e( 'New tab', 'popcorn-popups' ); ?>
					</label>
				</span>
				<p class="description">
					<?php
					echo 1 === $n
						? esc_html__( 'Leave the label empty to hide this button.', 'popcorn-popups' )
						: esc_html__( 'Another link, styled however you like. Leave the label empty to hide it.', 'popcorn-popups' );
					?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * The settings screen.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to change the Spotlight Bar.', 'popcorn-popups' ) );
		}

		$s    = self::settings();
		$name = self::OPTION;
		?>
		<div class="wrap pcp-spotlight-admin">
			<h1><?php esc_html_e( '💡 Spotlight Bar', 'popcorn-popups' ); ?></h1>
			<p class="pcp-muted">
				<?php esc_html_e( 'One full-width bar across every page of the site. Great for an announcement, a sale, or anything that deserves the spotlight.', 'popcorn-popups' ); ?>
			</p>

			<div class="pcp-hb-preview-wrap">
				<span class="pcp-hb-preview-caption"><?php esc_html_e( 'Live preview', 'popcorn-popups' ); ?></span>
				<div class="popcorn-spotlight popcorn-spotlight--preview popcorn-spotlight--<?php echo esc_attr( $s['position'] ); ?>" id="pcp-hb-preview"
					style="--pcp-sb-bg:<?php echo esc_attr( $s['bg_color'] ); ?>;--pcp-sb-text:<?php echo esc_attr( $s['text_color'] ); ?>;--pcp-sb-btn:<?php echo esc_attr( $s['btn_bg'] ); ?>;--pcp-sb-btn-ink:<?php echo esc_attr( $s['btn_text'] ); ?>;">
					<div class="popcorn-spotlight__inner">
						<span class="popcorn-spotlight__emoji" id="pcp-hb-p-emoji"><?php echo esc_html( $s['emoji'] ); ?></span>
						<span class="popcorn-spotlight__msg" id="pcp-hb-p-msg"><?php echo wp_kses_post( $s['message'] ); ?></span>
						<?php for ( $n = 1; $n <= self::BUTTONS; $n++ ) : ?>
							<?php $pk = self::button_keys( $n ); ?>
							<span class="popcorn-spotlight__cta popcorn-spotlight__cta--<?php echo esc_attr( $s[ $pk['style'] ] ); ?>"
								id="pcp-hb-p-cta<?php echo esc_attr( $n ); ?>"
								<?php echo '' === trim( $s[ $pk['text'] ] ) ? ' style="display:none"' : ''; ?>><?php echo esc_html( $s[ $pk['text'] ] ); ?></span>
						<?php endfor; ?>
					</div>
					<button type="button" class="popcorn-spotlight__x" aria-hidden="true" tabindex="-1">&times;</button>
				</div>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Turn it on', 'popcorn-popups' ); ?></th>
						<td>
							<label class="pcp-toggle" for="pcp-hb-enabled">
								<input type="hidden" name="<?php echo esc_attr( $name ); ?>[enabled]" value="0">
								<input type="checkbox" id="pcp-hb-enabled" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $s['enabled'], 1 ); ?>>
								<span class="pcp-toggle__track" aria-hidden="true"><span class="pcp-toggle__thumb"></span></span>
								<span class="pcp-toggle__label"><?php esc_html_e( 'Show the Spotlight Bar on the site', 'popcorn-popups' ); ?></span>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Content', 'popcorn-popups' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $name ); ?>[content_mode]" id="pcp-hb-mode">
								<option value="simple" <?php selected( $s['content_mode'], 'simple' ); ?>><?php esc_html_e( 'Built for me — emoji, message and buttons', 'popcorn-popups' ); ?></option>
								<option value="html" <?php selected( $s['content_mode'], 'html' ); ?>><?php esc_html_e( 'My own HTML — I will build the whole thing', 'popcorn-popups' ); ?></option>
							</select>
						</td>
					</tr>
				</table>

				<div class="pcp-hb-mode pcp-hb-mode--simple">
					<table class="form-table" role="presentation">
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
								<p class="description">
									<?php
									echo wp_kses(
										__( 'HTML is allowed here, so you can put as many inline links in the message as you like: <code>&lt;a href="/sale"&gt;the sale&lt;/a&gt;</code>. The buttons below are extra, not instead.', 'popcorn-popups' ),
										array( 'code' => array() )
									);
									?>
								</p>
							</td>
						</tr>

						<?php
						for ( $n = 1; $n <= self::BUTTONS; $n++ ) {
							self::button_row( $n, $s, $name );
						}
						?>
					</table>
				</div>

				<div class="pcp-hb-mode pcp-hb-mode--html">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="pcp-hb-html"><?php esc_html_e( 'Bar HTML', 'popcorn-popups' ); ?></label></th>
							<td>
								<textarea id="pcp-hb-html" name="<?php echo esc_attr( $name ); ?>[custom_html]" rows="6" class="large-text code"><?php echo esc_textarea( $s['custom_html'] ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'You own the whole bar. Put in as many links, spans and images as you want — nothing is wrapped around it.', 'popcorn-popups' ); ?>
								</p>
								<p class="description">
									<?php
									echo wp_kses(
										__( 'Example: <code>&lt;strong&gt;Two events!&lt;/strong&gt; &lt;a href="/london"&gt;London&lt;/a&gt; or &lt;a href="/leeds"&gt;Leeds&lt;/a&gt;</code>', 'popcorn-popups' ),
										array( 'code' => array() )
									);
									?>
								</p>
								<p class="description">
									<?php esc_html_e( 'The same tags WordPress allows in a post are allowed here. Scripts are stripped. The close button is added for you.', 'popcorn-popups' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<table class="form-table" role="presentation">
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
						<th scope="row"><?php esc_html_e( 'Closing it', 'popcorn-popups' ); ?></th>
						<td>
							<label class="pcp-toggle" for="pcp-hb-dismissible" style="margin-bottom:12px;">
								<input type="hidden" name="<?php echo esc_attr( $name ); ?>[dismissible]" value="0">
								<input type="checkbox" id="pcp-hb-dismissible" name="<?php echo esc_attr( $name ); ?>[dismissible]" value="1" <?php checked( $s['dismissible'], 1 ); ?>>
								<span class="pcp-toggle__track" aria-hidden="true"><span class="pcp-toggle__thumb"></span></span>
								<span class="pcp-toggle__label"><?php esc_html_e( 'Let visitors close the bar', 'popcorn-popups' ); ?></span>
							</label>

							<p>
								<label for="pcp-hb-reappear"><strong><?php esc_html_e( 'Once closed, it comes back…', 'popcorn-popups' ); ?></strong></label><br>
								<select name="<?php echo esc_attr( $name ); ?>[reappear]" id="pcp-hb-reappear">
									<option value="always" <?php selected( $s['reappear'], 'always' ); ?>><?php esc_html_e( 'On the very next page load', 'popcorn-popups' ); ?></option>
									<option value="session" <?php selected( $s['reappear'], 'session' ); ?>><?php esc_html_e( 'Next time they visit the site', 'popcorn-popups' ); ?></option>
									<option value="days" <?php selected( $s['reappear'], 'days' ); ?>><?php esc_html_e( 'After a number of days', 'popcorn-popups' ); ?></option>
									<option value="forever" <?php selected( $s['reappear'], 'forever' ); ?>><?php esc_html_e( 'Never — closed is closed, for good', 'popcorn-popups' ); ?></option>
								</select>
							</p>

							<p id="pcp-hb-days-row">
								<label for="pcp-hb-days"><?php esc_html_e( 'Days before it returns', 'popcorn-popups' ); ?></label>
								<input type="number" id="pcp-hb-days" min="1" max="3650" step="1" style="width:90px;" name="<?php echo esc_attr( $name ); ?>[dismiss_days]" value="<?php echo esc_attr( $s['dismiss_days'] ); ?>">
							</p>

							<p class="description">
								<?php esc_html_e( 'Whichever you pick, editing the bar\'s wording or buttons brings it back for everyone, including people who closed the old version.', 'popcorn-popups' ); ?>
							</p>

							<p style="margin-top:14px;">
								<button type="button" class="button pcp-hb-forget"><?php esc_html_e( '🍪 Show it to me again on this device', 'popcorn-popups' ); ?></button>
							</p>
							<p class="description"><?php esc_html_e( 'Clears your own "closed" cookie so you can see the bar again while setting it up.', 'popcorn-popups' ); ?></p>
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

				<?php submit_button( __( 'Save the Spotlight Bar', 'popcorn-popups' ) ); ?>
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

		if ( ! self::has_content( $s ) ) {
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
		 * Filter whether the Spotlight Bar shows on this request.
		 *
		 * @param bool  $show Visible.
		 * @param array $s    Settings.
		 */
		return (bool) apply_filters( 'popcorn_spotlight_visible', $show, $s );
	}

	/**
	 * Is there anything to say?
	 *
	 * @param array $s Settings.
	 * @return bool
	 */
	protected static function has_content( $s ) {
		if ( 'html' === $s['content_mode'] ) {
			return '' !== trim( wp_strip_all_tags( $s['custom_html'] ) ) || false !== strpos( $s['custom_html'], '<img' );
		}

		if ( '' !== trim( wp_strip_all_tags( $s['message'] ) ) ) {
			return true;
		}

		for ( $n = 1; $n <= self::BUTTONS; $n++ ) {
			$keys = self::button_keys( $n );
			if ( '' !== trim( $s[ $keys['text'] ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Every button's label and link, flattened, for the version hash.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	protected static function button_seed( $s ) {
		$parts = array();

		for ( $n = 1; $n <= self::BUTTONS; $n++ ) {
			$keys    = self::button_keys( $n );
			$parts[] = $s[ $keys['text'] ] . '>' . $s[ $keys['url'] ];
		}

		return implode( ',', $parts );
	}

	/**
	 * A short signature of the bar's content and its closing rules.
	 *
	 * Baked into the dismissal cookie name, so changing what the bar says or
	 * how long it stays closed brings it back for everyone.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	public static function version( $s ) {
		$seed = implode(
			'|',
			array(
				$s['content_mode'],
				$s['emoji'],
				$s['message'],
				$s['custom_html'],
				self::button_seed( $s ),
				$s['reappear'],
				(int) $s['dismiss_days'],
			)
		);

		return substr( md5( $seed ), 0, 8 );
	}

	/**
	 * Enqueue bar assets when it is going to show.
	 */
	public static function assets() {
		if ( ! self::visible() ) {
			return;
		}

		wp_enqueue_style( 'popcorn-spotlight', POPCORN_URL . 'assets/css/popcorn-spotlight.css', array(), POPCORN_VERSION );
		wp_enqueue_script( 'popcorn-spotlight', POPCORN_URL . 'assets/js/popcorn-spotlight.js', array(), POPCORN_VERSION, true );
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
	 * One button, however it is styled.
	 *
	 * @param string $text   Label.
	 * @param string $url    Destination.
	 * @param bool   $new    Open in a new tab.
	 * @param string $style  solid | outline | plain.
	 */
	protected static function button( $text, $url, $new, $style ) {
		if ( '' === trim( (string) $text ) ) {
			return;
		}

		$class = 'popcorn-spotlight__cta popcorn-spotlight__cta--' . $style;

		if ( ! $url ) {
			printf( '<span class="%1$s">%2$s</span>', esc_attr( $class ), esc_html( $text ) );
			return;
		}

		printf(
			'<a class="%1$s" href="%2$s"%3$s>%4$s</a>',
			esc_attr( $class ),
			esc_url( $url ),
			$new ? ' target="_blank" rel="noopener noreferrer"' : '',
			esc_html( $text )
		);
	}

	/**
	 * Print the bar.
	 */
	public static function render() {
		if ( self::$rendered || ! self::visible() ) {
			return;
		}

		self::$rendered = true;

		$s     = self::settings();
		$stuck = $s['sticky'] || self::$force_stuck;

		$classes = array(
			'popcorn-spotlight',
			'popcorn-spotlight--' . $s['position'],
			$stuck ? 'popcorn-spotlight--stuck' : 'popcorn-spotlight--inline',
		);

		// Re-run through sanitize_hex_color rather than trusting what is stored:
		// a value written straight to the option by other code still cannot
		// break out of the attribute.
		$style = sprintf(
			'--pcp-sb-bg:%1$s;--pcp-sb-text:%2$s;--pcp-sb-btn:%3$s;--pcp-sb-btn-ink:%4$s;',
			sanitize_hex_color( $s['bg_color'] ),
			sanitize_hex_color( $s['text_color'] ),
			sanitize_hex_color( $s['btn_bg'] ),
			sanitize_hex_color( $s['btn_text'] )
		);
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			style="<?php echo esc_attr( $style ); ?>"
			role="region"
			aria-label="<?php esc_attr_e( 'Site announcement', 'popcorn-popups' ); ?>"
			data-version="<?php echo esc_attr( self::version( $s ) ); ?>"
			data-reappear="<?php echo esc_attr( $s['reappear'] ); ?>"
			data-days="<?php echo esc_attr( $s['dismiss_days'] ); ?>"
			data-push="<?php echo esc_attr( ( $stuck && $s['push_page'] ) ? '1' : '0' ); ?>"
			hidden>
			<div class="popcorn-spotlight__inner">
				<?php if ( 'html' === $s['content_mode'] ) : ?>
					<?php
					/**
					 * Filter the Spotlight Bar's custom HTML just before output.
					 *
					 * @param string $html Sanitized HTML.
					 * @param array  $s    Settings.
					 */
					echo wp_kses(
						apply_filters( 'popcorn_spotlight_html', $s['custom_html'], $s ),
						apply_filters( 'popcorn_spotlight_allowed_html', wp_kses_allowed_html( 'post' ) )
					);
					?>
				<?php else : ?>
					<?php if ( '' !== trim( $s['emoji'] ) ) : ?>
						<span class="popcorn-spotlight__emoji" aria-hidden="true"><?php echo esc_html( $s['emoji'] ); ?></span>
					<?php endif; ?>

					<span class="popcorn-spotlight__msg"><?php echo wp_kses_post( $s['message'] ); ?></span>

					<?php
					for ( $n = 1; $n <= self::BUTTONS; $n++ ) {
						$keys = self::button_keys( $n );
						self::button( $s[ $keys['text'] ], $s[ $keys['url'] ], (bool) $s[ $keys['new_tab'] ], $s[ $keys['style'] ] );
					}
					?>
				<?php endif; ?>
			</div>

			<?php if ( $s['dismissible'] ) : ?>
				<button type="button" class="popcorn-spotlight__x" aria-label="<?php esc_attr_e( 'Close announcement', 'popcorn-popups' ); ?>">&times;</button>
			<?php endif; ?>
		</div>
		<?php
	}
}
