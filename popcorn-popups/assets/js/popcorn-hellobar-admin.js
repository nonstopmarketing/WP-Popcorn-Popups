/**
 * Popcorn Popups — Hello Bar settings screen.
 *
 * Keeps the preview at the top of the page in sync with the form below it.
 */
( function ( $ ) {
	'use strict';

	$( function () {
		var preview = document.getElementById( 'pcp-hb-preview' );

		if ( ! preview ) {
			return;
		}

		var parts = {
			emoji: document.getElementById( 'pcp-hb-p-emoji' ),
			msg: document.getElementById( 'pcp-hb-p-msg' ),
			cta: document.getElementById( 'pcp-hb-p-cta' )
		};

		var vars = {
			bg: '--pcp-hb-bg',
			text: '--pcp-hb-text',
			btn: '--pcp-hb-btn',
			btnink: '--pcp-hb-btn-ink'
		};

		function paint( input ) {
			var key = input.getAttribute( 'data-preview' );
			if ( key && vars[ key ] ) {
				preview.style.setProperty( vars[ key ], input.value );
			}
		}

		$( '.pcp-color' ).wpColorPicker( {
			change: function ( event, ui ) {
				// The input's own value updates a tick later than this callback.
				var input = event.target;
				window.setTimeout( function () {
					var key = input.getAttribute( 'data-preview' );
					if ( key && vars[ key ] ) {
						preview.style.setProperty( vars[ key ], ui.color.toString() );
					}
				}, 0 );
			},
			clear: function ( event ) {
				paint( event.target );
			}
		} );

		function syncText( id, target, fallback ) {
			var input = document.getElementById( id );
			if ( ! input || ! target ) {
				return;
			}
			input.addEventListener( 'input', function () {
				var value = input.value.trim();
				target.textContent = value || fallback;
				target.style.display = value ? '' : 'none';
			} );
		}

		syncText( 'pcp-hb-emoji', parts.emoji, '' );
		syncText( 'pcp-hb-link-text', parts.cta, '' );

		var message = document.getElementById( 'pcp-hb-message' );

		if ( message && parts.msg ) {
			message.addEventListener( 'input', function () {
				// Text only in the preview — the saved value still allows the
				// basic HTML that wp_kses_post permits.
				parts.msg.textContent = message.value;
			} );
		}

		var position = document.getElementById( 'pcp-hb-position' );

		if ( position ) {
			position.addEventListener( 'change', function () {
				preview.classList.toggle( 'popcorn-hello--top', 'top' === position.value );
				preview.classList.toggle( 'popcorn-hello--bottom', 'bottom' === position.value );
			} );
		}
	} );
}( window.jQuery ) );
