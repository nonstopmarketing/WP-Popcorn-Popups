/**
 * Popcorn Popups — Spotlight Bar behaviour.
 *
 * The bar is printed hidden so a visitor who already closed it never sees a
 * flash of it. That also keeps the markup identical for every visitor, which
 * matters when a full-page cache sits in front of the site.
 */
( function () {
	'use strict';

	var PREFIX = 'pcp_spot_';

	function setCookie( name, value, days ) {
		var bits = [ name + '=' + encodeURIComponent( value ), 'path=/', 'SameSite=Lax' ];

		if ( days > 0 ) {
			var expires = new Date();
			expires.setTime( expires.getTime() + days * 86400000 );
			bits.push( 'expires=' + expires.toUTCString() );
			bits.push( 'max-age=' + Math.round( days * 86400 ) );
		}

		if ( 'https:' === window.location.protocol ) {
			bits.push( 'Secure' );
		}

		try {
			document.cookie = bits.join( '; ' );
		} catch ( e ) {
			/* Cookies blocked — the bar just comes back next visit. */
		}
	}

	function getCookie( name ) {
		try {
			var escaped = name.replace( /([.*+?^${}()|[\]\\])/g, '\\$1' );
			var match = document.cookie.match( new RegExp( '(?:^|; )' + escaped + '=([^;]*)' ) );
			return match ? decodeURIComponent( match[ 1 ] ) : null;
		} catch ( e ) {
			return null;
		}
	}

	/**
	 * Clear every Spotlight Bar cookie, old and new.
	 */
	function forget() {
		var cleared = 0;

		document.cookie.split( ';' ).forEach( function ( pair ) {
			var name = pair.split( '=' )[ 0 ].trim();
			if ( 0 === name.indexOf( PREFIX ) || 0 === name.indexOf( 'pcp_hello_' ) ) {
				document.cookie = name + '=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
				cleared++;
			}
		} );

		return cleared;
	}

	function start() {
		var bar = document.querySelector( '.popcorn-spotlight:not(.popcorn-spotlight--preview)' );

		if ( ! bar ) {
			return;
		}

		// The cookie name carries a hash of what the bar says and how long it
		// should stay closed, so changing either brings it back for everyone —
		// including people who closed the previous version.
		var cookie = PREFIX + ( bar.getAttribute( 'data-version' ) || '1' );
		var reappear = bar.getAttribute( 'data-reappear' ) || 'days';
		var days = parseInt( bar.getAttribute( 'data-days' ), 10 ) || 30;
		var push = '1' === bar.getAttribute( 'data-push' );
		var isTop = bar.classList.contains( 'popcorn-spotlight--top' );

		// "Comes back on the very next page load" keeps no cookie at all.
		if ( 'always' !== reappear && getCookie( cookie ) ) {
			bar.parentNode.removeChild( bar );
			return;
		}

		bar.hidden = false;

		/**
		 * A stuck bar sits on top of the page, so nudge the page out of its way.
		 */
		function applyPush() {
			if ( ! push ) {
				return;
			}
			var size = bar.offsetHeight + 'px';
			if ( isTop ) {
				document.body.style.paddingTop = size;
				document.documentElement.style.scrollPaddingTop = size;
			} else {
				document.body.style.paddingBottom = size;
			}
		}

		function releasePush() {
			if ( ! push ) {
				return;
			}
			if ( isTop ) {
				document.body.style.paddingTop = '';
				document.documentElement.style.scrollPaddingTop = '';
			} else {
				document.body.style.paddingBottom = '';
			}
		}

		applyPush();

		if ( window.ResizeObserver ) {
			new window.ResizeObserver( applyPush ).observe( bar );
		} else {
			window.addEventListener( 'resize', applyPush );
		}

		var close = bar.querySelector( '.popcorn-spotlight__x' );

		if ( close ) {
			close.addEventListener( 'click', function () {
				switch ( reappear ) {
					case 'always':
						// Nothing remembered: gone for this page view only.
						break;

					case 'session':
						setCookie( cookie, '1', 0 );
						break;

					case 'forever':
						setCookie( cookie, '1', 3650 );
						break;

					case 'days':
					default:
						setCookie( cookie, '1', days );
						break;
				}

				releasePush();
				bar.classList.add( 'is-leaving' );

				window.setTimeout( function () {
					if ( bar.parentNode ) {
						bar.parentNode.removeChild( bar );
					}
				}, 280 );

				bar.dispatchEvent( new CustomEvent( 'popcorn:spotlight-dismissed', { bubbles: true } ) );
			} );
		}

		bar.dispatchEvent( new CustomEvent( 'popcorn:spotlight-shown', { bubbles: true } ) );
	}

	window.PopcornSpotlight = { reset: forget };

	// The bar used to be called the Hello Bar; keep the old handle working.
	window.PopcornHello = window.PopcornSpotlight;

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
