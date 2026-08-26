<?php
/**
 * The field schema. One source of truth for rendering, saving and defaults.
 *
 * @package PopcornPopups
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Popcorn_Fields
 */
class Popcorn_Fields {

	/**
	 * Cached schema.
	 *
	 * @var array|null
	 */
	protected static $schema = null;

	/**
	 * Full field schema grouped into builder tabs.
	 *
	 * @return array
	 */
	public static function schema() {
		if ( null !== self::$schema ) {
			return self::$schema;
		}

		$schema = array(

			'trigger' => array(
				'label'  => __( 'Trigger', 'popcorn-popups' ),
				'icon'   => '⏱️',
				'blurb'  => __( 'What makes this thing pop?', 'popcorn-popups' ),
				'fields' => array(
					'trigger'          => array(
						'type'    => 'cards',
						'label'   => __( 'Pop when…', 'popcorn-popups' ),
						'default' => 'time',
						'columns' => 3,
						'choices' => array(
							'time'   => array( '⏰', __( 'After a delay', 'popcorn-popups' ), __( 'Wait, then pop.', 'popcorn-popups' ) ),
							'scroll' => array( '📜', __( 'On scroll', 'popcorn-popups' ), __( 'They got curious.', 'popcorn-popups' ) ),
							'exit'   => array( '🚪', __( 'Exit intent', 'popcorn-popups' ), __( 'Leaving? Not so fast.', 'popcorn-popups' ) ),
							'click'  => array( '👆', __( 'On click', 'popcorn-popups' ), __( 'They asked for it.', 'popcorn-popups' ) ),
							'idle'   => array( '😴', __( 'When idle', 'popcorn-popups' ), __( 'Still there?', 'popcorn-popups' ) ),
							'chaos'  => array( '🎲', __( 'Chaos mode', 'popcorn-popups' ), __( 'Random timing. Live a little.', 'popcorn-popups' ) ),
						),
					),
					'trigger_delay'    => array(
						'type'    => 'number',
						'label'   => __( 'Delay (seconds)', 'popcorn-popups' ),
						'default' => 3,
						'min'     => 0,
						'max'     => 600,
						'show_if' => array( 'trigger' => array( 'time' ) ),
					),
					'trigger_scroll'   => array(
						'type'    => 'range',
						'label'   => __( 'Scroll depth', 'popcorn-popups' ),
						'default' => 50,
						'min'     => 1,
						'max'     => 100,
						'suffix'  => '%',
						'show_if' => array( 'trigger' => array( 'scroll' ) ),
					),
					'trigger_selector' => array(
						'type'    => 'text',
						'label'   => __( 'CSS selector to click', 'popcorn-popups' ),
						'default' => '',
						'help'    => __( 'e.g. <code>.my-button</code> or <code>#signup</code>. Anything matching this opens the popup.', 'popcorn-popups' ),
						'show_if' => array( 'trigger' => array( 'click' ) ),
					),
					'trigger_idle'     => array(
						'type'    => 'number',
						'label'   => __( 'Idle for (seconds)', 'popcorn-popups' ),
						'default' => 20,
						'min'     => 3,
						'max'     => 900,
						'show_if' => array( 'trigger' => array( 'idle' ) ),
					),
					'frequency'        => array(
						'type'    => 'select',
						'label'   => __( 'How often per visitor?', 'popcorn-popups' ),
						'default' => 'session',
						'choices' => array(
							'always'  => __( 'Every single page view (bold)', 'popcorn-popups' ),
							'session' => __( 'Once per browsing session', 'popcorn-popups' ),
							'days'    => __( 'Once every X days', 'popcorn-popups' ),
							'once'    => __( 'Once. Ever. Forever.', 'popcorn-popups' ),
						),
					),
					'frequency_days'   => array(
						'type'    => 'number',
						'label'   => __( 'Days between pops', 'popcorn-popups' ),
						'default' => 7,
						'min'     => 1,
						'max'     => 365,
						'show_if' => array( 'frequency' => array( 'days' ) ),
					),
					'max_shows'        => array(
						'type'    => 'number',
						'label'   => __( 'Stop after this many pops per visitor', 'popcorn-popups' ),
						'default' => 0,
						'min'     => 0,
						'max'     => 500,
						'help'    => __( 'A lifetime cap counted in a cookie on the visitor’s device. <code>0</code> means no cap. Set it to <code>3</code> and the fourth site visit will not see this popup, whatever the setting above says.', 'popcorn-popups' ),
					),
					'cookie_days'      => array(
						'type'    => 'number',
						'label'   => __( 'Remember visitors for (days)', 'popcorn-popups' ),
						'default' => 365,
						'min'     => 1,
						'max'     => 3650,
						'help'    => __( 'How long the counting cookie lives. When it expires the visitor is treated as brand new and the count starts again.', 'popcorn-popups' ),
					),
					'close_delay'      => array(
						'type'    => 'number',
						'label'   => __( 'Reveal close button after (seconds)', 'popcorn-popups' ),
						'default' => 0,
						'min'     => 0,
						'max'     => 30,
						'help'    => __( 'Zero means instantly. Be kind — anything over 3 is a bit rude.', 'popcorn-popups' ),
					),
				),
			),

			'design' => array(
				'label'  => __( 'Design', 'popcorn-popups' ),
				'icon'   => '🎨',
				'blurb'  => __( 'Make it look like you mean it.', 'popcorn-popups' ),
				'fields' => array(
					'position'      => array(
						'type'    => 'cards',
						'label'   => __( 'Where does it land?', 'popcorn-popups' ),
						'default' => 'center',
						'columns' => 4,
						'choices' => array(
							'center'       => array( '🎯', __( 'Dead center', 'popcorn-popups' ), '' ),
							'top-bar'      => array( '📏', __( 'Top bar', 'popcorn-popups' ), '' ),
							'bottom-bar'   => array( '📐', __( 'Bottom bar', 'popcorn-popups' ), '' ),
							'fullscreen'   => array( '🖼️', __( 'Full screen', 'popcorn-popups' ), '' ),
							'top-left'     => array( '↖️', __( 'Top left', 'popcorn-popups' ), '' ),
							'top-right'    => array( '↗️', __( 'Top right', 'popcorn-popups' ), '' ),
							'bottom-left'  => array( '↙️', __( 'Bottom left', 'popcorn-popups' ), '' ),
							'bottom-right' => array( '↘️', __( 'Bottom right', 'popcorn-popups' ), '' ),
						),
					),
					'chrome'        => array(
						'type'    => 'cards',
						'label'   => __( 'Frame', 'popcorn-popups' ),
						'default' => 'card',
						'columns' => 2,
						'choices' => array(
							'card' => array( '🗂️', __( 'Card', 'popcorn-popups' ), __( 'Background, padding, rounded corners, shadow.', 'popcorn-popups' ) ),
							'bare' => array( '👻', __( 'Bare', 'popcorn-popups' ), __( 'Transparent. No background, border, shadow, margin or padding — just your content and the ✕.', 'popcorn-popups' ) ),
						),
					),
					'corner_offset' => array(
						'type'    => 'range',
						'label'   => __( 'Distance from the corner', 'popcorn-popups' ),
						'default' => 20,
						'min'     => 0,
						'max'     => 90,
						'suffix'  => 'px',
						'show_if' => array( 'position' => array( 'top-left', 'top-right', 'bottom-left', 'bottom-right' ) ),
					),
					'animation'     => array(
						'type'    => 'cards',
						'label'   => __( 'Entrance', 'popcorn-popups' ),
						'default' => 'pop',
						'columns' => 3,
						'choices' => array(
							'pop'   => array( '🍿', __( 'Pop', 'popcorn-popups' ), '' ),
							'slide' => array( '🛝', __( 'Slide up', 'popcorn-popups' ), '' ),
							'fly'   => array( '🚀', __( 'Fly in', 'popcorn-popups' ), '' ),
							'flip'  => array( '🔄', __( 'Flip', 'popcorn-popups' ), '' ),
							'jelly' => array( '🍮', __( 'Jelly wobble', 'popcorn-popups' ), '' ),
							'drop'  => array( '🪂', __( 'Drop in', 'popcorn-popups' ), '' ),
						),
					),
					'width'         => array(
						'type'    => 'number',
						'label'   => __( 'Max width (px)', 'popcorn-popups' ),
						'default' => 480,
						'min'     => 200,
						'max'     => 1200,
					),
					'radius'        => array(
						'type'    => 'range',
						'label'   => __( 'Corner roundness', 'popcorn-popups' ),
						'default' => 18,
						'min'     => 0,
						'max'     => 60,
						'suffix'  => 'px',
						'show_if' => array( 'chrome' => array( 'card' ) ),
					),
					'border_style'  => array(
						'type'    => 'select',
						'label'   => __( 'Border', 'popcorn-popups' ),
						'default' => 'none',
						'choices' => array(
							'none'   => __( 'No border', 'popcorn-popups' ),
							'solid'  => __( 'Solid', 'popcorn-popups' ),
							'dashed' => __( 'Dashed', 'popcorn-popups' ),
							'dotted' => __( 'Dotted', 'popcorn-popups' ),
							'double' => __( 'Double', 'popcorn-popups' ),
						),
					),
					'border_width'  => array(
						'type'    => 'range',
						'label'   => __( 'Border thickness', 'popcorn-popups' ),
						'default' => 2,
						'min'     => 1,
						'max'     => 16,
						'suffix'  => 'px',
						'show_if' => array( 'border_style' => array( 'solid', 'dashed', 'dotted', 'double' ) ),
					),
					'border_color'  => array(
						'type'    => 'color',
						'label'   => __( 'Border colour', 'popcorn-popups' ),
						'default' => '#1f1a17',
						'show_if' => array( 'border_style' => array( 'solid', 'dashed', 'dotted', 'double' ) ),
					),
					'shadow'        => array(
						'type'    => 'select',
						'label'   => __( 'Drop shadow', 'popcorn-popups' ),
						'default' => 'soft',
						'choices' => array(
							'none'     => __( 'None — completely flat', 'popcorn-popups' ),
							'soft'     => __( 'Soft', 'popcorn-popups' ),
							'medium'   => __( 'Medium', 'popcorn-popups' ),
							'dramatic' => __( 'Dramatic', 'popcorn-popups' ),
						),
						'show_if' => array( 'chrome' => array( 'card' ) ),
					),
					'bg_color'      => array(
						'type'    => 'color',
						'label'   => __( 'Background', 'popcorn-popups' ),
						'default' => '#fffaf0',
						'show_if' => array( 'chrome' => array( 'card' ) ),
					),
					'text_color'    => array(
						'type'    => 'color',
						'label'   => __( 'Text', 'popcorn-popups' ),
						'default' => '#1f1a17',
					),
					'accent_color'  => array(
						'type'    => 'color',
						'label'   => __( 'Accent / button', 'popcorn-popups' ),
						'default' => '#ff5c39',
					),
					'overlay'       => array(
						'type'    => 'toggle',
						'label'   => __( 'Dim the page behind it', 'popcorn-popups' ),
						'default' => 1,
					),
					'overlay_color' => array(
						'type'    => 'color',
						'label'   => __( 'Overlay colour', 'popcorn-popups' ),
						'default' => '#1f1a17',
						'show_if' => array( 'overlay' => array( '1' ) ),
					),
					'overlay_blur'  => array(
						'type'    => 'toggle',
						'label'   => __( 'Blur the background too', 'popcorn-popups' ),
						'default' => 0,
						'show_if' => array( 'overlay' => array( '1' ) ),
					),
					'confetti_when'    => array(
						'type'    => 'cards',
						'label'   => __( '🎉 Fire the confetti…', 'popcorn-popups' ),
						'default' => 'click',
						'columns' => 4,
						'choices' => array(
							'off'   => array( '🚫', __( 'Never', 'popcorn-popups' ), __( 'A quiet life.', 'popcorn-popups' ) ),
							'open'  => array( '💥', __( 'The moment it pops', 'popcorn-popups' ), __( 'Blows up the window.', 'popcorn-popups' ) ),
							'click' => array( '🔘', __( 'On button click', 'popcorn-popups' ), __( 'Reward the click.', 'popcorn-popups' ) ),
							'both'  => array( '🎊', __( 'Both, obviously', 'popcorn-popups' ), __( 'No notes.', 'popcorn-popups' ) ),
						),
					),
					'confetti_style'   => array(
						'type'    => 'cards',
						'label'   => __( 'Confetti style', 'popcorn-popups' ),
						'default' => 'cannons',
						'columns' => 4,
						'choices' => array(
							'cannons'   => array( '🎊', __( 'Corner cannons', 'popcorn-popups' ), __( 'Fires in from both bottom corners.', 'popcorn-popups' ) ),
							'burst'     => array( '💥', __( 'Center burst', 'popcorn-popups' ), __( 'One big bang, middle out.', 'popcorn-popups' ) ),
							'fireworks' => array( '🎆', __( 'Fireworks', 'popcorn-popups' ), __( 'Several pops across the window.', 'popcorn-popups' ) ),
							'rain'      => array( '🌧️', __( 'Confetti rain', 'popcorn-popups' ), __( 'Drifts down the whole page.', 'popcorn-popups' ) ),
						),
						'show_if' => array( 'confetti_when' => array( 'open', 'click', 'both' ) ),
					),
					'confetti_palette' => array(
						'type'    => 'select',
						'label'   => __( 'Confetti colours', 'popcorn-popups' ),
						'default' => 'popcorn',
						'choices' => array(
							'popcorn' => __( '🍿 Popcorn (your accent + warm brights)', 'popcorn-popups' ),
							'party'   => __( '🎈 Party (classic multicolour)', 'popcorn-popups' ),
							'neon'    => __( '⚡ Neon', 'popcorn-popups' ),
							'gold'    => __( '🥂 Gold & cream', 'popcorn-popups' ),
							'mono'    => __( '⚫ Monochrome', 'popcorn-popups' ),
							'accent'  => __( '🎯 Just my accent colour', 'popcorn-popups' ),
							'custom'  => __( '🎨 Custom — pick your own below', 'popcorn-popups' ),
						),
						'show_if' => array( 'confetti_when' => array( 'open', 'click', 'both' ) ),
					),
					'confetti_c1'      => array(
						'type'    => 'color',
						'label'   => __( 'Confetti colour 1', 'popcorn-popups' ),
						'default' => '#ff5c39',
						'show_if' => array( 'confetti_palette' => array( 'custom' ) ),
					),
					'confetti_c2'      => array(
						'type'    => 'color',
						'label'   => __( 'Confetti colour 2', 'popcorn-popups' ),
						'default' => '#ffd166',
						'show_if' => array( 'confetti_palette' => array( 'custom' ) ),
					),
					'confetti_c3'      => array(
						'type'    => 'color',
						'label'   => __( 'Confetti colour 3', 'popcorn-popups' ),
						'default' => '#06d6a0',
						'show_if' => array( 'confetti_palette' => array( 'custom' ) ),
					),
					'confetti_c4'      => array(
						'type'    => 'color',
						'label'   => __( 'Confetti colour 4', 'popcorn-popups' ),
						'default' => '#118ab2',
						'show_if' => array( 'confetti_palette' => array( 'custom' ) ),
					),
					'sound'         => array(
						'type'    => 'toggle',
						'label'   => __( '🔊 Play a little “pop” on open', 'popcorn-popups' ),
						'default' => 0,
						'help'    => __( 'Browsers mute audio until the visitor has interacted with the page, so treat this as a bonus, not a promise.', 'popcorn-popups' ),
					),
					'emoji_rain'    => array(
						'type'    => 'text',
						'label'   => __( 'Emoji rain on open', 'popcorn-popups' ),
						'default' => '',
						'help'    => __( 'Drop in a few emoji (e.g. <code>🍿🎉✨</code>) and they will rain down. Leave empty for restraint.', 'popcorn-popups' ),
					),
				),
			),

			'cta' => array(
				'label'  => __( 'Button', 'popcorn-popups' ),
				'icon'   => '🔘',
				'blurb'  => __( 'The bit you actually want them to click.', 'popcorn-popups' ),
				'fields' => array(
					'cta_text'     => array(
						'type'    => 'text',
						'label'   => __( 'Button label', 'popcorn-popups' ),
						'default' => '',
						'help'    => __( 'Leave empty to hide the button entirely.', 'popcorn-popups' ),
					),
					'cta_url'      => array(
						'type'    => 'url',
						'label'   => __( 'Button link', 'popcorn-popups' ),
						'default' => '',
						'help'    => __( 'Leave empty and the button simply closes the popup — it still counts as a click.', 'popcorn-popups' ),
					),
					'cta_new_tab'  => array(
						'type'    => 'toggle',
						'label'   => __( 'Open in a new tab', 'popcorn-popups' ),
						'default' => 0,
					),
					'dismiss_text' => array(
						'type'    => 'text',
						'label'   => __( '“No thanks” link text', 'popcorn-popups' ),
						'default' => '',
						'help'    => __( 'Optional small link under the button, e.g. <code>Maybe later</code>. Clicking it retires the popup on that device.', 'popcorn-popups' ),
					),
				),
			),

			'targeting' => array(
				'label'  => __( 'Where', 'popcorn-popups' ),
				'icon'   => '🎯',
				'blurb'  => __( 'Pages, posts, and everything in between.', 'popcorn-popups' ),
				'fields' => array(
					'show_on'     => array(
						'type'    => 'cards',
						'label'   => __( 'Show this popup on…', 'popcorn-popups' ),
						'default' => 'everywhere',
						'columns' => 3,
						'choices' => array(
							'everywhere' => array( '🌍', __( 'Everywhere', 'popcorn-popups' ), __( 'The whole site.', 'popcorn-popups' ) ),
							'front'      => array( '🏠', __( 'Front page only', 'popcorn-popups' ), __( 'Home sweet home.', 'popcorn-popups' ) ),
							'pages'      => array( '📄', __( 'All pages', 'popcorn-popups' ), __( 'Every static page.', 'popcorn-popups' ) ),
							'posts'      => array( '📝', __( 'All posts', 'popcorn-popups' ), __( 'Every single blog post.', 'popcorn-popups' ) ),
							'archives'   => array( '🗂️', __( 'Archives & blog index', 'popcorn-popups' ), __( 'Category, tag, date, author.', 'popcorn-popups' ) ),
							'selected'   => array( '✅', __( 'Only what I pick', 'popcorn-popups' ), __( 'Hand-picked below.', 'popcorn-popups' ) ),
						),
					),
					'include_ids' => array(
						'type'    => 'postpicker',
						'label'   => __( 'Show on these pages / posts', 'popcorn-popups' ),
						'default' => '',
						'help'    => __( 'Search and pick. Mix pages and posts freely.', 'popcorn-popups' ),
						'show_if' => array( 'show_on' => array( 'selected' ) ),
					),
					'terms'       => array(
						'type'    => 'text',
						'label'   => __( '…or any post in these categories / tags', 'popcorn-popups' ),
						'default' => '',
						'help'    => __( 'Comma-separated slugs, e.g. <code>news, recipes</code>.', 'popcorn-popups' ),
						'show_if' => array( 'show_on' => array( 'selected' ) ),
					),
					'exclude_ids' => array(
						'type'    => 'postpicker',
						'label'   => __( 'Never show on these', 'popcorn-popups' ),
						'default' => '',
						'help'    => __( 'Exclusions always win, whatever the rule above says.', 'popcorn-popups' ),
					),
					'devices'     => array(
						'type'    => 'select',
						'label'   => __( 'Devices', 'popcorn-popups' ),
						'default' => 'all',
						'choices' => array(
							'all'     => __( 'Everything with a screen', 'popcorn-popups' ),
							'desktop' => __( 'Desktop only 🖥️', 'popcorn-popups' ),
							'mobile'  => __( 'Mobile & tablet only 📱', 'popcorn-popups' ),
						),
					),
					'visitors'    => array(
						'type'    => 'select',
						'label'   => __( 'Visitors', 'popcorn-popups' ),
						'default' => 'all',
						'choices' => array(
							'all'        => __( 'Everyone', 'popcorn-popups' ),
							'logged_out' => __( 'Logged-out visitors only', 'popcorn-popups' ),
							'logged_in'  => __( 'Logged-in users only', 'popcorn-popups' ),
						),
					),
					'start_date'  => array(
						'type'    => 'date',
						'label'   => __( 'Start showing on', 'popcorn-popups' ),
						'default' => '',
						'help'    => __( 'Optional. Uses your site timezone.', 'popcorn-popups' ),
					),
					'end_date'    => array(
						'type'    => 'date',
						'label'   => __( 'Stop showing after', 'popcorn-popups' ),
						'default' => '',
						'help'    => __( 'Optional. Uses your site timezone.', 'popcorn-popups' ),
					),
					'priority'    => array(
						'type'    => 'number',
						'label'   => __( 'Priority', 'popcorn-popups' ),
						'default' => 10,
						'min'     => 1,
						'max'     => 100,
						'help'    => __( 'If several popups qualify on one page, the lowest number wins. No pile-ups.', 'popcorn-popups' ),
					),
				),
			),
		);

		/**
		 * Filter the popup builder field schema.
		 *
		 * @param array $schema Field schema.
		 */
		self::$schema = apply_filters( 'popcorn_field_schema', $schema );

		return self::$schema;
	}

	/**
	 * Flat list of every field keyed by name.
	 *
	 * @return array
	 */
	public static function flat() {
		$flat = array();
		foreach ( self::schema() as $tab ) {
			foreach ( $tab['fields'] as $key => $field ) {
				$flat[ $key ] = $field;
			}
		}
		return $flat;
	}

	/**
	 * Default value for every field.
	 *
	 * @return array
	 */
	public static function defaults() {
		static $defaults = null;

		if ( null === $defaults ) {
			$defaults = array();
			foreach ( self::flat() as $key => $field ) {
				$defaults[ $key ] = isset( $field['default'] ) ? $field['default'] : '';
			}
		}

		return $defaults;
	}

	/**
	 * Sanitize a submitted value according to its field type.
	 *
	 * @param string $key   Field key.
	 * @param mixed  $value Raw value.
	 * @return mixed Null when the key is unknown.
	 */
	public static function sanitize( $key, $value ) {
		$fields = self::flat();
		if ( ! isset( $fields[ $key ] ) ) {
			return null;
		}
		$field   = $fields[ $key ];
		$default = isset( $field['default'] ) ? $field['default'] : '';

		// No field here accepts an array, so treat one as a tampered request.
		if ( is_array( $value ) || is_object( $value ) ) {
			return $default;
		}

		switch ( $field['type'] ) {
			case 'number':
			case 'range':
				$value = is_numeric( $value ) ? (float) $value : (float) $default;
				if ( isset( $field['min'] ) ) {
					$value = max( (float) $field['min'], $value );
				}
				if ( isset( $field['max'] ) ) {
					$value = min( (float) $field['max'], $value );
				}
				return (int) round( $value );

			case 'toggle':
				return empty( $value ) ? 0 : 1;

			case 'color':
				$hex = sanitize_hex_color( $value );
				return $hex ? $hex : $default;

			case 'url':
				return esc_url_raw( trim( (string) $value ) );

			case 'select':
			case 'cards':
				return isset( $field['choices'][ $value ] ) ? $value : $default;

			case 'date':
				$value = trim( (string) $value );
				return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';

			case 'postpicker':
				$ids = array_filter( array_map( 'absint', preg_split( '/[\s,]+/', (string) $value ) ) );
				return implode( ',', array_unique( $ids ) );

			case 'textarea':
				return sanitize_textarea_field( $value );

			case 'text':
			default:
				return sanitize_text_field( $value );
		}
	}
}
