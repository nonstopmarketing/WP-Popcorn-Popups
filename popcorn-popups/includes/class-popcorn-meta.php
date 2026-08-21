<?php
/**
 * The builder meta boxes: rendering and saving.
 *
 * @package PopcornPopups
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Popcorn_Meta
 */
class Popcorn_Meta {

	/**
	 * Nonce action.
	 */
	const NONCE = 'popcorn_save_popup';

	/**
	 * Hook it up.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_boxes' ) );
		add_action( 'save_post_' . POPCORN_CPT, array( __CLASS__, 'save' ), 10, 2 );
	}

	/**
	 * Register the boxes.
	 */
	public static function add_boxes() {
		add_meta_box(
			'popcorn-builder',
			__( '🍿 Popup Builder', 'popcorn-popups' ),
			array( __CLASS__, 'render_builder' ),
			POPCORN_CPT,
			'normal',
			'high'
		);

		add_meta_box(
			'popcorn-preview',
			__( '👀 Test Drive', 'popcorn-popups' ),
			array( __CLASS__, 'render_preview' ),
			POPCORN_CPT,
			'side',
			'high'
		);

		add_meta_box(
			'popcorn-stats',
			__( '📊 Scoreboard', 'popcorn-popups' ),
			array( __CLASS__, 'render_stats' ),
			POPCORN_CPT,
			'side',
			'default'
		);
	}

	/**
	 * The tabbed builder.
	 *
	 * @param WP_Post $post Current popup.
	 */
	public static function render_builder( $post ) {
		wp_nonce_field( self::NONCE, 'popcorn_nonce' );
		$schema = Popcorn_Fields::schema();
		$first  = true;
		?>
		<div class="pcp-builder">
			<div class="pcp-tabs" role="tablist">
				<?php foreach ( $schema as $tab_id => $tab ) : ?>
					<button type="button"
						class="pcp-tab<?php echo $first ? ' is-active' : ''; ?>"
						role="tab"
						aria-selected="<?php echo $first ? 'true' : 'false'; ?>"
						data-tab="<?php echo esc_attr( $tab_id ); ?>">
						<span class="pcp-tab__icon" aria-hidden="true"><?php echo esc_html( $tab['icon'] ); ?></span>
						<span><?php echo esc_html( $tab['label'] ); ?></span>
					</button>
					<?php $first = false; ?>
				<?php endforeach; ?>
			</div>

			<?php
			$first = true;
			foreach ( $schema as $tab_id => $tab ) :
				?>
				<div class="pcp-panel<?php echo $first ? ' is-active' : ''; ?>" data-panel="<?php echo esc_attr( $tab_id ); ?>">
					<p class="pcp-panel__blurb"><?php echo esc_html( $tab['blurb'] ); ?></p>
					<?php
					foreach ( $tab['fields'] as $key => $field ) {
						self::render_field( $key, $field, $post->ID );
					}
					?>
				</div>
				<?php
				$first = false;
			endforeach;
			?>
		</div>
		<?php
	}

	/**
	 * Render one field.
	 *
	 * @param string $key     Field key.
	 * @param array  $field   Field definition.
	 * @param int    $post_id Popup ID.
	 */
	protected static function render_field( $key, $field, $post_id ) {
		$value = popcorn_get( $post_id, $key );
		$name  = 'pcp[' . $key . ']';
		$id    = 'pcp-' . $key;
		$attrs = '';

		if ( ! empty( $field['show_if'] ) ) {
			$attrs = ' data-show-if="' . esc_attr( wp_json_encode( $field['show_if'] ) ) . '"';
		}

		echo '<div class="pcp-field pcp-field--' . esc_attr( $field['type'] ) . '" data-key="' . esc_attr( $key ) . '"' . $attrs . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( 'toggle' !== $field['type'] ) {
			echo '<label class="pcp-field__label" for="' . esc_attr( $id ) . '">' . esc_html( wp_strip_all_tags( $field['label'] ) ) . '</label>';
		}

		switch ( $field['type'] ) {

			case 'cards':
				$columns = isset( $field['columns'] ) ? (int) $field['columns'] : 3;
				echo '<div class="pcp-cards" style="--pcp-cols:' . esc_attr( $columns ) . '">';
				foreach ( $field['choices'] as $choice => $meta ) {
					$emoji = isset( $meta[0] ) ? $meta[0] : '';
					$title = isset( $meta[1] ) ? $meta[1] : $choice;
					$desc  = isset( $meta[2] ) ? $meta[2] : '';
					printf(
						'<label class="pcp-card%1$s"><input type="radio" name="%2$s" value="%3$s" %4$s><span class="pcp-card__emoji" aria-hidden="true">%5$s</span><span class="pcp-card__title">%6$s</span>%7$s</label>',
						checked( $value, $choice, false ) ? ' is-selected' : '',
						esc_attr( $name ),
						esc_attr( $choice ),
						checked( $value, $choice, false ),
						esc_html( $emoji ),
						esc_html( $title ),
						$desc ? '<span class="pcp-card__desc">' . esc_html( $desc ) . '</span>' : ''
					);
				}
				echo '</div>';
				break;

			case 'select':
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" class="pcp-input">';
				foreach ( $field['choices'] as $choice => $label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $choice ),
						selected( $value, $choice, false ),
						esc_html( $label )
					);
				}
				echo '</select>';
				break;

			case 'toggle':
				printf(
					'<label class="pcp-toggle" for="%1$s"><input type="hidden" name="%2$s" value="0"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s><span class="pcp-toggle__track" aria-hidden="true"><span class="pcp-toggle__thumb"></span></span><span class="pcp-toggle__label">%4$s</span></label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( $value, 1, false ),
					wp_kses( $field['label'], array( 'code' => array() ) )
				);
				break;

			case 'color':
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="pcp-color" data-default-color="%4$s">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( $field['default'] )
				);
				break;

			case 'range':
				printf(
					'<span class="pcp-range"><input type="range" id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s"><output class="pcp-range__out">%3$s%6$s</output></span>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( isset( $field['min'] ) ? $field['min'] : 0 ),
					esc_attr( isset( $field['max'] ) ? $field['max'] : 100 ),
					esc_html( isset( $field['suffix'] ) ? $field['suffix'] : '' )
				);
				break;

			case 'number':
				printf(
					'<input type="number" id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" step="1" class="pcp-input pcp-input--number">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( isset( $field['min'] ) ? $field['min'] : 0 ),
					esc_attr( isset( $field['max'] ) ? $field['max'] : 9999 )
				);
				break;

			case 'date':
				printf(
					'<input type="date" id="%1$s" name="%2$s" value="%3$s" class="pcp-input">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;

			case 'url':
				printf(
					'<input type="url" id="%1$s" name="%2$s" value="%3$s" class="pcp-input" placeholder="https://">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;

			case 'postpicker':
				self::render_postpicker( $id, $name, $value );
				break;

			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="3" class="pcp-input">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_textarea( $value )
				);
				break;

			case 'text':
			default:
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="pcp-input">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;
		}

		if ( ! empty( $field['help'] ) ) {
			echo '<p class="pcp-help">' . wp_kses( $field['help'], array( 'code' => array(), 'a' => array( 'href' => array() ), 'strong' => array() ) ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Search-and-pick control for page/post IDs.
	 *
	 * @param string $id    Element ID.
	 * @param string $name  Input name.
	 * @param string $value Comma separated IDs.
	 */
	protected static function render_postpicker( $id, $name, $value ) {
		$ids = array_filter( array_map( 'absint', explode( ',', (string) $value ) ) );
		echo '<div class="pcp-picker">';
		printf(
			'<input type="hidden" id="%1$s" name="%2$s" value="%3$s" class="pcp-picker__value">',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( implode( ',', $ids ) )
		);
		echo '<ul class="pcp-picker__list">';
		foreach ( $ids as $picked ) {
			$title = get_the_title( $picked );
			if ( ! $title ) {
				$title = sprintf( /* translators: %d: post ID */ __( '(deleted item #%d)', 'popcorn-popups' ), $picked );
			}
			printf(
				'<li class="pcp-chip" data-id="%1$d">%2$s <button type="button" class="pcp-chip__x" aria-label="%3$s">&times;</button></li>',
				(int) $picked,
				esc_html( $title ),
				esc_attr__( 'Remove', 'popcorn-popups' )
			);
		}
		echo '</ul>';
		printf(
			'<input type="search" class="pcp-picker__search pcp-input" placeholder="%s" autocomplete="off">',
			esc_attr__( 'Type to search pages and posts…', 'popcorn-popups' )
		);
		echo '<ul class="pcp-picker__results" hidden></ul>';
		echo '</div>';
	}

	/**
	 * Sidebar preview box.
	 *
	 * @param WP_Post $post Current popup.
	 */
	public static function render_preview( $post ) {
		?>
		<p class="pcp-side-blurb"><?php esc_html_e( 'Pop it right here in the admin, using whatever is on screen right now — no need to save first.', 'popcorn-popups' ); ?></p>
		<button type="button" class="button button-primary button-hero pcp-preview-btn" style="width:100%;">
			<?php esc_html_e( '🍿 Pop it!', 'popcorn-popups' ); ?>
		</button>
		<p class="pcp-help" style="margin-top:8px;">
			<?php esc_html_e( 'The preview ignores triggers and targeting — it just shows the look and the entrance.', 'popcorn-popups' ); ?>
		</p>
		<?php if ( 'publish' !== $post->post_status ) : ?>
			<p class="pcp-notice-mini">
				<?php esc_html_e( 'Heads up: this popup is not published yet, so visitors will not see it.', 'popcorn-popups' ); ?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Sidebar stats box.
	 *
	 * @param WP_Post $post Current popup.
	 */
	public static function render_stats( $post ) {
		$views  = (int) get_post_meta( $post->ID, POPCORN_PREFIX . 'stat_views', true );
		$clicks = (int) get_post_meta( $post->ID, POPCORN_PREFIX . 'stat_clicks', true );
		$closes = (int) get_post_meta( $post->ID, POPCORN_PREFIX . 'stat_closes', true );
		$rate   = $views ? round( ( $clicks / $views ) * 100, 1 ) : 0;
		?>
		<ul class="pcp-stats">
			<li><span class="pcp-stats__n"><?php echo esc_html( number_format_i18n( $views ) ); ?></span><span class="pcp-stats__l"><?php esc_html_e( 'times popped', 'popcorn-popups' ); ?></span></li>
			<li><span class="pcp-stats__n"><?php echo esc_html( number_format_i18n( $clicks ) ); ?></span><span class="pcp-stats__l"><?php esc_html_e( 'button clicks', 'popcorn-popups' ); ?></span></li>
			<li><span class="pcp-stats__n"><?php echo esc_html( number_format_i18n( $closes ) ); ?></span><span class="pcp-stats__l"><?php esc_html_e( 'dismissals', 'popcorn-popups' ); ?></span></li>
			<li class="pcp-stats__hero"><span class="pcp-stats__n"><?php echo esc_html( $rate ); ?>%</span><span class="pcp-stats__l"><?php esc_html_e( 'click rate', 'popcorn-popups' ); ?></span></li>
		</ul>
		<p class="pcp-help"><?php echo esc_html( Popcorn_Admin::pep_talk( $views, $rate ) ); ?></p>
		<?php
	}

	/**
	 * Persist the builder values.
	 *
	 * @param int     $post_id Popup ID.
	 * @param WP_Post $post    Popup object.
	 */
	public static function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['popcorn_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['popcorn_nonce'] ) ), self::NONCE ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw = isset( $_POST['pcp'] ) && is_array( $_POST['pcp'] ) ? wp_unslash( $_POST['pcp'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		foreach ( Popcorn_Fields::flat() as $key => $field ) {
			$value = isset( $raw[ $key ] ) ? $raw[ $key ] : ( 'toggle' === $field['type'] ? 0 : '' );
			$clean = Popcorn_Fields::sanitize( $key, $value );
			if ( null === $clean ) {
				continue;
			}
			update_post_meta( $post_id, POPCORN_PREFIX . $key, $clean );
		}
	}
}
