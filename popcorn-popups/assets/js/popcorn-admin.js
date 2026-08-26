/**
 * Popcorn Popups — builder screen behaviour.
 *
 * Tabs, conditional fields, the page/post picker, and the live preview.
 */
( function ( $ ) {
	'use strict';

	var builder = document.querySelector( '.pcp-builder' );

	/* ------------------------------------------------------------- values */

	/**
	 * Current value of a builder field, whatever control it uses.
	 */
	function val( key ) {
		var name = 'pcp[' + key + ']';
		var nodes = document.querySelectorAll( '[name="' + name.replace( /"/g, '\\"' ) + '"]' );
		var i;

		if ( ! nodes.length ) {
			return '';
		}

		for ( i = 0; i < nodes.length; i++ ) {
			if ( 'radio' === nodes[ i ].type ) {
				if ( nodes[ i ].checked ) {
					return nodes[ i ].value;
				}
			} else if ( 'checkbox' === nodes[ i ].type ) {
				return nodes[ i ].checked ? '1' : '0';
			} else if ( 'hidden' !== nodes[ i ].type || 1 === nodes.length ) {
				return nodes[ i ].value;
			}
		}

		return '';
	}

	function num( key ) {
		var parsed = parseInt( val( key ), 10 );
		return isNaN( parsed ) ? 0 : parsed;
	}

	function bool( key ) {
		return '1' === val( key ) ? 1 : 0;
	}

	/* --------------------------------------------------------------- tabs */

	function initTabs() {
		if ( ! builder ) {
			return;
		}

		builder.addEventListener( 'click', function ( event ) {
			var tab = event.target.closest( '.pcp-tab' );
			if ( ! tab ) {
				return;
			}

			builder.querySelectorAll( '.pcp-tab' ).forEach( function ( node ) {
				var active = node === tab;
				node.classList.toggle( 'is-active', active );
				node.setAttribute( 'aria-selected', active ? 'true' : 'false' );
			} );

			builder.querySelectorAll( '.pcp-panel' ).forEach( function ( panel ) {
				panel.classList.toggle( 'is-active', panel.dataset.panel === tab.dataset.tab );
			} );
		} );
	}

	/* ------------------------------------------------- conditional fields */

	function refreshConditionals() {
		document.querySelectorAll( '.pcp-field[data-show-if]' ).forEach( function ( field ) {
			var rules;
			try {
				rules = JSON.parse( field.dataset.showIf );
			} catch ( e ) {
				return;
			}

			var visible = Object.keys( rules ).every( function ( key ) {
				return rules[ key ].indexOf( val( key ) ) !== -1;
			} );

			field.classList.toggle( 'is-hidden', ! visible );
		} );
	}

	/* ------------------------------------------------------------ inputs */

	function initInputs() {
		if ( ! builder ) {
			return;
		}

		// Colour pickers.
		$( '.pcp-color' ).wpColorPicker();

		// Range readouts.
		builder.querySelectorAll( '.pcp-range input' ).forEach( function ( input ) {
			var out = input.parentNode.querySelector( '.pcp-range__out' );
			var suffix = out ? out.textContent.replace( /[\d.]/g, '' ) : '';
			input.addEventListener( 'input', function () {
				if ( out ) {
					out.textContent = input.value + suffix;
				}
			} );
		} );

		// Card selection highlight.
		builder.addEventListener( 'change', function ( event ) {
			if ( event.target.matches( '.pcp-card input' ) ) {
				var group = event.target.name;
				builder.querySelectorAll( '.pcp-card input[name="' + group.replace( /"/g, '\\"' ) + '"]' ).forEach( function ( input ) {
					input.closest( '.pcp-card' ).classList.toggle( 'is-selected', input.checked );
				} );
			}
			refreshConditionals();
		} );

		builder.addEventListener( 'input', refreshConditionals );
		refreshConditionals();
	}

	/* ------------------------------------------------------- post picker */

	function initPickers() {
		document.querySelectorAll( '.pcp-picker' ).forEach( function ( picker ) {
			var hidden = picker.querySelector( '.pcp-picker__value' );
			var list = picker.querySelector( '.pcp-picker__list' );
			var search = picker.querySelector( '.pcp-picker__search' );
			var results = picker.querySelector( '.pcp-picker__results' );
			var timer = null;

			function sync() {
				var ids = [];
				list.querySelectorAll( '.pcp-chip' ).forEach( function ( chip ) {
					ids.push( chip.dataset.id );
				} );
				hidden.value = ids.join( ',' );
			}

			function addChip( id, title ) {
				if ( list.querySelector( '.pcp-chip[data-id="' + id + '"]' ) ) {
					return;
				}
				var chip = document.createElement( 'li' );
				chip.className = 'pcp-chip';
				chip.dataset.id = id;
				chip.textContent = title + ' ';
				var remove = document.createElement( 'button' );
				remove.type = 'button';
				remove.className = 'pcp-chip__x';
				remove.innerHTML = '&times;';
				chip.appendChild( remove );
				list.appendChild( chip );
				sync();
			}

			list.addEventListener( 'click', function ( event ) {
				if ( event.target.matches( '.pcp-chip__x' ) ) {
					event.target.closest( '.pcp-chip' ).remove();
					sync();
				}
			} );

			search.addEventListener( 'input', function () {
				window.clearTimeout( timer );
				var term = search.value.trim();

				if ( term.length < 2 ) {
					results.hidden = true;
					results.innerHTML = '';
					return;
				}

				timer = window.setTimeout( function () {
					var url = PopcornAdmin.ajaxUrl +
						'?action=popcorn_search_posts&nonce=' + encodeURIComponent( PopcornAdmin.nonce ) +
						'&term=' + encodeURIComponent( term );

					window.fetch( url, { credentials: 'same-origin' } )
						.then( function ( response ) {
							return response.json();
						} )
						.then( function ( payload ) {
							results.innerHTML = '';
							results.hidden = false;

							if ( ! payload.success || ! payload.data.length ) {
								var none = document.createElement( 'li' );
								none.className = 'pcp-picker__none';
								none.textContent = PopcornAdmin.noResult;
								results.appendChild( none );
								return;
							}

							payload.data.forEach( function ( item ) {
								var row = document.createElement( 'li' );
								var button = document.createElement( 'button' );
								button.type = 'button';
								button.innerHTML = '';
								button.textContent = item.title;
								var badge = document.createElement( 'span' );
								badge.className = 'pcp-picker__type';
								badge.textContent = item.type;
								button.appendChild( badge );
								button.addEventListener( 'click', function () {
									addChip( item.id, item.title );
									search.value = '';
									results.hidden = true;
									results.innerHTML = '';
								} );
								row.appendChild( button );
								results.appendChild( row );
							} );
						} )
						.catch( function () {
							results.hidden = true;
						} );
				}, 250 );
			} );

			document.addEventListener( 'click', function ( event ) {
				if ( ! picker.contains( event.target ) ) {
					results.hidden = true;
				}
			} );
		} );
	}

	/* ----------------------------------------------------------- preview */

	/**
	 * Whatever is currently in the post editor, as HTML.
	 */
	function editorContent() {
		var editor = window.tinymce && window.tinymce.get( 'content' );

		if ( editor && ! editor.isHidden() ) {
			return editor.getContent();
		}

		var textarea = document.getElementById( 'content' );
		if ( ! textarea ) {
			return '';
		}

		// Rough stand-in for wpautop, good enough to preview with.
		return textarea.value
			.split( /\n\s*\n/ )
			.filter( function ( block ) {
				return block.trim().length;
			} )
			.map( function ( block ) {
				return /^\s*</.test( block ) ? block : '<p>' + block.replace( /\n/g, '<br>' ) + '</p>';
			} )
			.join( '' );
	}

	/**
	 * Mirror of Popcorn_Frontend::confetti_colors() so the preview shows the
	 * palette you actually picked.
	 */
	function confettiColors() {
		var accent = val( 'accent_color' ) || '#ff5c39';

		var palettes = {
			popcorn: [ accent, '#ffd166', '#fff3c4', '#ff9f1c', '#ffffff' ],
			party: [ '#ef476f', '#ffd166', '#06d6a0', '#118ab2', '#f78c6b' ],
			neon: [ '#39ff14', '#ff073a', '#00e5ff', '#ff00e6', '#faff00' ],
			gold: [ '#d4af37', '#f6e7b4', '#b8860b', '#fffdf3', '#e8c14f' ],
			mono: [ '#111111', '#555555', '#999999', '#dddddd', '#ffffff' ],
			accent: [ accent ]
		};

		var choice = val( 'confetti_palette' );

		if ( 'custom' === choice ) {
			var custom = [ val( 'confetti_c1' ), val( 'confetti_c2' ), val( 'confetti_c3' ), val( 'confetti_c4' ) ]
				.filter( function ( hex ) {
					return /^#[0-9a-f]{3,8}$/i.test( hex );
				} );

			if ( custom.length ) {
				return custom;
			}
		}

		return palettes[ choice ] || palettes.popcorn;
	}

	function previewConfig() {
		var title = document.getElementById( 'title' );
		var content = editorContent();

		if ( ! content.trim() ) {
			content = '<p><em>' + PopcornAdmin.empty + '</em></p>';
		}

		return {
			id: 'preview',
			name: title ? title.value : 'Preview',
			content: content,
			trigger: val( 'trigger' ),
			position: val( 'position' ),
			chrome: val( 'chrome' ),
			offset: num( 'corner_offset' ),
			animation: val( 'animation' ),
			width: num( 'width' ),
			radius: num( 'radius' ),
			borderStyle: val( 'border_style' ),
			borderWidth: num( 'border_width' ),
			borderColor: val( 'border_color' ),
			shadow: val( 'shadow' ),
			bg: val( 'bg_color' ),
			text: val( 'text_color' ),
			accent: val( 'accent_color' ),
			overlay: bool( 'overlay' ),
			overlayBg: val( 'overlay_color' ),
			blur: bool( 'overlay_blur' ),
			confetti: val( 'confetti_when' ),
			confettiFx: val( 'confetti_style' ),
			confettiHue: confettiColors(),
			sound: bool( 'sound' ),
			emojiRain: val( 'emoji_rain' ),
			ctaText: val( 'cta_text' ),
			ctaUrl: '',
			ctaNewTab: 0,
			dismissText: val( 'dismiss_text' ),
			closeDelay: num( 'close_delay' ),
			frequency: 'always',
			devices: 'all'
		};
	}

	function initPreview() {
		var button = document.querySelector( '.pcp-preview-btn' );
		if ( ! button || ! window.Popcorn ) {
			return;
		}

		button.addEventListener( 'click', function () {
			window.Popcorn.preview( previewConfig() );
		} );
	}

	/**
	 * Wipe every Popcorn cookie on this device, so the tester looks brand new
	 * to every popup and to the Hello Bar.
	 */
	function initForget() {
		var button = document.querySelector( '.pcp-forget-btn' );
		if ( ! button ) {
			return;
		}

		button.addEventListener( 'click', function () {
			var cleared = 0;

			document.cookie.split( ';' ).forEach( function ( pair ) {
				var name = pair.split( '=' )[ 0 ].trim();
				if ( 0 === name.indexOf( 'pcp_' ) ) {
					document.cookie = name + '=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
					cleared++;
				}
			} );

			button.textContent = cleared
				? '✅ ' + cleared + ' cookie' + ( 1 === cleared ? '' : 's' ) + ' cleared'
				: '✅ Nothing to clear — you are already new';

			window.setTimeout( function () {
				button.textContent = '🍪 Forget me on this device';
			}, 3000 );
		} );
	}

	/* -------------------------------------------------------------- boot */

	$( function () {
		initTabs();
		initInputs();
		initPickers();
		initPreview();
		initForget();
	} );
}( window.jQuery ) );
