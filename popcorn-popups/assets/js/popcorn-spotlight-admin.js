/**
 * Popcorn Popups — Spotlight Bar settings screen.
 *
 * Keeps the preview at the top of the page in sync with the form below it,
 * and shows only the fields that matter for the chosen content mode.
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
			msg: document.getElementById( 'pcp-hb-p-msg' )
		};

		var vars = {
			bg: '--pcp-sb-bg',
			text: '--pcp-sb-text',
			btn: '--pcp-sb-btn',
			btnink: '--pcp-sb-btn-ink'
		};

		/* ------------------------------------------------------- colours */

		$( '.pcp-color' ).wpColorPicker( {
			change: function ( event, ui ) {
				var input = event.target;
				window.setTimeout( function () {
					var key = input.getAttribute( 'data-preview' );
					if ( key && vars[ key ] ) {
						preview.style.setProperty( vars[ key ], ui.color.toString() );
					}
				}, 0 );
			},
			clear: function ( event ) {
				var key = event.target.getAttribute( 'data-preview' );
				if ( key && vars[ key ] ) {
					preview.style.setProperty( vars[ key ], event.target.value );
				}
			}
		} );

		/* --------------------------------------------------------- text */

		function syncText( id, target ) {
			var input = document.getElementById( id );
			if ( ! input || ! target ) {
				return;
			}
			input.addEventListener( 'input', function () {
				var value = input.value.trim();
				target.textContent = value;
				target.style.display = value ? '' : 'none';
			} );
		}

		syncText( 'pcp-hb-emoji', parts.emoji );

		var message = document.getElementById( 'pcp-hb-message' );

		if ( message && parts.msg ) {
			message.addEventListener( 'input', function () {
				// Text only in the preview — the saved value still allows the
				// HTML that wp_kses_post permits.
				parts.msg.textContent = message.value;
			} );
		}

		/* ------------------------------------------------- button styles */

		function syncStyle( selectId, target ) {
			var select = document.getElementById( selectId );
			if ( ! select || ! target ) {
				return;
			}
			select.addEventListener( 'change', function () {
				[ 'solid', 'outline', 'plain' ].forEach( function ( look ) {
					target.classList.toggle( 'popcorn-spotlight__cta--' + look, look === select.value );
				} );
			} );
		}

		// Every button drives its own span in the preview. The spans are read
		// from the page rather than counted here, so adding a fourth button
		// server-side needs no change in this file.
		preview.querySelectorAll( '.popcorn-spotlight__cta' ).forEach( function ( span ) {
			var n = ( span.id || '' ).replace( 'pcp-hb-p-cta', '' );
			if ( ! n ) {
				return;
			}
			syncText( 'pcp-hb-btn' + n + '-text', span );
			syncStyle( 'pcp-hb-btn' + n + '-style', span );
		} );

		/* ------------------------------------------------------ position */

		var position = document.getElementById( 'pcp-hb-position' );

		if ( position ) {
			position.addEventListener( 'change', function () {
				preview.classList.toggle( 'popcorn-spotlight--top', 'top' === position.value );
				preview.classList.toggle( 'popcorn-spotlight--bottom', 'bottom' === position.value );
			} );
		}

		/* -------------------------------------------------- content mode */

		var mode = document.getElementById( 'pcp-hb-mode' );

		function refreshMode() {
			if ( ! mode ) {
				return;
			}
			document.querySelectorAll( '.pcp-hb-mode' ).forEach( function ( block ) {
				var wants = block.classList.contains( 'pcp-hb-mode--' + mode.value );
				block.style.display = wants ? '' : 'none';
			} );
		}

		if ( mode ) {
			mode.addEventListener( 'change', refreshMode );
			refreshMode();
		}

		/* ------------------------------------------------- reappearance */

		var reappear = document.getElementById( 'pcp-hb-reappear' );
		var daysRow = document.getElementById( 'pcp-hb-days-row' );

		function refreshDays() {
			if ( reappear && daysRow ) {
				daysRow.style.display = 'days' === reappear.value ? '' : 'none';
			}
		}

		if ( reappear ) {
			reappear.addEventListener( 'change', refreshDays );
			refreshDays();
		}

		/* ------------------------------------------------------- forget */

		var forget = document.querySelector( '.pcp-hb-forget' );

		if ( forget ) {
			forget.addEventListener( 'click', function () {
				var cleared = 0;

				document.cookie.split( ';' ).forEach( function ( pair ) {
					var name = pair.split( '=' )[ 0 ].trim();
					if ( 0 === name.indexOf( 'pcp_spot_' ) || 0 === name.indexOf( 'pcp_hello_' ) ) {
						document.cookie = name + '=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
						cleared++;
					}
				} );

				forget.textContent = cleared
					? '✅ Cleared — the bar will show again'
					: '✅ Nothing to clear — it should already be showing';

				window.setTimeout( function () {
					forget.textContent = '🍪 Show it to me again on this device';
				}, 3000 );
			} );
		}
	} );
}( window.jQuery ) );
