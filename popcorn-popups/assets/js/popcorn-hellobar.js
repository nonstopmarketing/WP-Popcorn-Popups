/**
 * Popcorn Popups — Hello Bar behaviour.
 *
 * The bar is printed hidden so a visitor who already dismissed it never sees a
 * flash of it. This also keeps the markup identical for every visitor, which
 * matters when a full-page cache is in front of the site.
 */
( function () {
	'use strict';

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

	function start() {
		var bar = document.querySelector( '.popcorn-hello:not(.popcorn-hello--preview)' );

		if ( ! bar ) {
			return;
		}

		// The cookie name carries a hash of the bar's content, so editing the
		// message brings it back for people who closed the previous one.
		var cookie = 'pcp_hello_' + ( bar.getAttribute( 'data-version' ) || '1' );
		var days = parseInt( bar.getAttribute( 'data-days' ), 10 ) || 30;
		var push = '1' === bar.getAttribute( 'data-push' );
		var isTop = bar.classList.contains( 'popcorn-hello--top' );

		if ( getCookie( cookie ) ) {
			bar.parentNode.removeChild( bar );
			return;
		}

		bar.hidden = false;

		/**
		 * A fixed bar sits on top of the page, so nudge the page out of its way.
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

		var close = bar.querySelector( '.popcorn-hello__x' );

		if ( close ) {
			close.addEventListener( 'click', function () {
				setCookie( cookie, '1', days );
				releasePush();
				bar.classList.add( 'is-leaving' );

				window.setTimeout( function () {
					if ( bar.parentNode ) {
						bar.parentNode.removeChild( bar );
					}
				}, 280 );

				bar.dispatchEvent( new CustomEvent( 'popcorn:hello-dismissed', { bubbles: true } ) );
			} );
		}

		bar.dispatchEvent( new CustomEvent( 'popcorn:hello-shown', { bubbles: true } ) );
	}

	/**
	 * Bring the bar back on this device — handy while testing.
	 */
	window.PopcornHello = {
		reset: function () {
			document.cookie.split( ';' ).forEach( function ( pair ) {
				var name = pair.split( '=' )[ 0 ].trim();
				if ( 0 === name.indexOf( 'pcp_hello_' ) ) {
					document.cookie = name + '=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
				}
			} );
		}
	};

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
