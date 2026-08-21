/**
 * Popcorn Popups — front-end engine.
 *
 * Builds every popup from the JSON payload printed in the footer, arms its
 * trigger, and handles the party effects. No dependencies, no jQuery.
 */
( function () {
	'use strict';

	var ANIMATIONS = [ 'pop', 'slide', 'fly', 'flip', 'jelly', 'drop' ];
	var POSITIONS = [ 'center', 'top-bar', 'bottom-bar', 'top-left', 'top-right', 'bottom-left', 'bottom-right' ];
	var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])';

	var settings = { endpoint: '', nonce: '', track: false };
	var instances = {};
	var somethingIsOpen = false;

	/* ------------------------------------------------------------ helpers */

	function el( tag, className, html ) {
		var node = document.createElement( tag );
		if ( className ) {
			node.className = className;
		}
		if ( undefined !== html && null !== html ) {
			node.innerHTML = html;
		}
		return node;
	}

	function pick( list ) {
		return list[ Math.floor( Math.random() * list.length ) ];
	}

	function store( type ) {
		try {
			var s = 'session' === type ? window.sessionStorage : window.localStorage;
			s.setItem( '__pcp', '1' );
			s.removeItem( '__pcp' );
			return s;
		} catch ( e ) {
			return null;
		}
	}

	function readFlag( type, key ) {
		var s = store( type );
		return s ? s.getItem( key ) : null;
	}

	function writeFlag( type, key, value ) {
		var s = store( type );
		if ( s ) {
			try {
				s.setItem( key, value );
			} catch ( e ) {
				/* Storage full or blocked — the popup just shows again. */
			}
		}
	}

	/**
	 * Readable ink colour for a background, so button labels never vanish.
	 */
	function inkFor( hex ) {
		var c = String( hex || '' ).replace( '#', '' );
		if ( 3 === c.length ) {
			c = c[ 0 ] + c[ 0 ] + c[ 1 ] + c[ 1 ] + c[ 2 ] + c[ 2 ];
		}
		if ( 6 !== c.length ) {
			return '#ffffff';
		}
		var r = parseInt( c.slice( 0, 2 ), 16 ) / 255;
		var g = parseInt( c.slice( 2, 4 ), 16 ) / 255;
		var b = parseInt( c.slice( 4, 6 ), 16 ) / 255;
		var lin = function ( v ) {
			return v <= 0.03928 ? v / 12.92 : Math.pow( ( v + 0.055 ) / 1.055, 2.4 );
		};
		var l = 0.2126 * lin( r ) + 0.7152 * lin( g ) + 0.0722 * lin( b );
		return l > 0.45 ? '#1f1a17' : '#ffffff';
	}

	function hexToRgba( hex, alpha ) {
		var c = String( hex || '' ).replace( '#', '' );
		if ( 3 === c.length ) {
			c = c[ 0 ] + c[ 0 ] + c[ 1 ] + c[ 1 ] + c[ 2 ] + c[ 2 ];
		}
		if ( 6 !== c.length ) {
			return 'rgba(31, 26, 23, ' + alpha + ')';
		}
		return 'rgba(' + parseInt( c.slice( 0, 2 ), 16 ) + ',' + parseInt( c.slice( 2, 4 ), 16 ) + ',' + parseInt( c.slice( 4, 6 ), 16 ) + ',' + alpha + ')';
	}

	function isMobile() {
		var narrow = window.matchMedia && window.matchMedia( '(max-width: 900px)' ).matches;
		var touch = ( 'ontouchstart' in window ) || navigator.maxTouchPoints > 0;
		return !! ( narrow || touch );
	}

	function reducedMotion() {
		return window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	}

	function track( id, event ) {
		if ( ! settings.track || ! settings.endpoint ) {
			return;
		}
		var body = JSON.stringify( { id: id, event: event } );
		try {
			if ( 'close' === event && navigator.sendBeacon ) {
				navigator.sendBeacon( settings.endpoint, new Blob( [ body ], { type: 'application/json' } ) );
				return;
			}
			window.fetch( settings.endpoint, {
				method: 'POST',
				keepalive: true,
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': settings.nonce },
				body: body
			} ).catch( function () {} );
		} catch ( e ) {
			/* Counting is a nice-to-have; never let it break the popup. */
		}
	}

	/* ------------------------------------------------------ party effects */

	function confetti( accent ) {
		if ( reducedMotion() ) {
			return;
		}

		var canvas = el( 'canvas', 'popcorn-confetti' );
		var dpr = window.devicePixelRatio || 1;
		var w = window.innerWidth;
		var h = window.innerHeight;

		canvas.width = w * dpr;
		canvas.height = h * dpr;
		canvas.style.width = w + 'px';
		canvas.style.height = h + 'px';
		document.body.appendChild( canvas );

		var ctx = canvas.getContext( '2d' );
		ctx.scale( dpr, dpr );

		var colors = [ accent, '#ffd166', '#06d6a0', '#118ab2', '#ef476f', '#ffffff' ];
		var bits = [];
		var i;

		for ( i = 0; i < 140; i++ ) {
			bits.push( {
				x: w / 2 + ( Math.random() - 0.5 ) * w * 0.5,
				y: h * 0.55 + ( Math.random() - 0.5 ) * 60,
				vx: ( Math.random() - 0.5 ) * 14,
				vy: -( 8 + Math.random() * 14 ),
				size: 5 + Math.random() * 7,
				color: colors[ i % colors.length ],
				spin: ( Math.random() - 0.5 ) * 0.4,
				angle: Math.random() * Math.PI
			} );
		}

		var start = null;

		function frame( time ) {
			if ( null === start ) {
				start = time;
			}
			var elapsed = time - start;
			ctx.clearRect( 0, 0, w, h );

			bits.forEach( function ( b ) {
				b.vy += 0.42;
				b.vx *= 0.995;
				b.x += b.vx;
				b.y += b.vy;
				b.angle += b.spin;

				ctx.save();
				ctx.translate( b.x, b.y );
				ctx.rotate( b.angle );
				ctx.globalAlpha = Math.max( 0, 1 - elapsed / 2600 );
				ctx.fillStyle = b.color;
				ctx.fillRect( -b.size / 2, -b.size / 2, b.size, b.size * 0.6 );
				ctx.restore();
			} );

			if ( elapsed < 2600 ) {
				window.requestAnimationFrame( frame );
			} else if ( canvas.parentNode ) {
				canvas.parentNode.removeChild( canvas );
			}
		}

		window.requestAnimationFrame( frame );
	}

	function emojiRain( emoji ) {
		var chars = Array.from( String( emoji || '' ).trim() ).filter( function ( ch ) {
			return ch.trim().length > 0;
		} );

		if ( ! chars.length || reducedMotion() ) {
			return;
		}

		var layer = el( 'div', 'popcorn-rain' );
		document.body.appendChild( layer );

		for ( var i = 0; i < 26; i++ ) {
			var bit = el( 'span', 'popcorn-rain__bit', pick( chars ) );
			bit.style.left = Math.random() * 100 + 'vw';
			bit.style.fontSize = 20 + Math.random() * 26 + 'px';
			bit.style.animationDuration = 2.6 + Math.random() * 2.6 + 's';
			bit.style.animationDelay = Math.random() * 1.2 + 's';
			layer.appendChild( bit );
		}

		window.setTimeout( function () {
			if ( layer.parentNode ) {
				layer.parentNode.removeChild( layer );
			}
		}, 6500 );
	}

	function popSound() {
		try {
			var Ctx = window.AudioContext || window.webkitAudioContext;
			if ( ! Ctx ) {
				return;
			}
			var ctx = new Ctx();
			var osc = ctx.createOscillator();
			var gain = ctx.createGain();
			var t = ctx.currentTime;

			osc.type = 'sine';
			osc.frequency.setValueAtTime( 180, t );
			osc.frequency.exponentialRampToValueAtTime( 900, t + 0.07 );

			gain.gain.setValueAtTime( 0.0001, t );
			gain.gain.exponentialRampToValueAtTime( 0.25, t + 0.015 );
			gain.gain.exponentialRampToValueAtTime( 0.0001, t + 0.18 );

			osc.connect( gain ).connect( ctx.destination );
			osc.start( t );
			osc.stop( t + 0.2 );
			osc.onended = function () {
				ctx.close();
			};
		} catch ( e ) {
			/* No audio, no problem. */
		}
	}

	/* -------------------------------------------------------- the popup */

	function Popup( config, isPreview ) {
		this.config = config;
		this.isPreview = !! isPreview;
		this.id = config.id;
		this.open = false;
		this.timers = [];
		this.build();
	}

	Popup.prototype.build = function () {
		var c = this.config;
		var self = this;

		var position = c.position || 'center';
		var animation = c.animation || 'pop';

		if ( 'chaos' === c.trigger ) {
			position = pick( POSITIONS );
			animation = pick( ANIMATIONS );
		}

		var root = el( 'div', 'popcorn popcorn--' + position + ' popcorn--anim-' + animation );
		root.id = 'popcorn-' + c.id;
		root.setAttribute( 'data-popcorn-id', c.id );

		if ( ! c.overlay ) {
			root.classList.add( 'popcorn--no-overlay' );
		}
		if ( c.blur ) {
			root.classList.add( 'popcorn--blur' );
		}

		root.style.setProperty( '--pcp-bg', c.bg );
		root.style.setProperty( '--pcp-text', c.text );
		root.style.setProperty( '--pcp-accent', c.accent );
		root.style.setProperty( '--pcp-accent-ink', inkFor( c.accent ) );
		root.style.setProperty( '--pcp-overlay', hexToRgba( c.overlayBg, 0.62 ) );
		root.style.setProperty( '--pcp-radius', ( c.radius || 0 ) + 'px' );
		root.style.setProperty( '--pcp-width', ( c.width || 480 ) + 'px' );

		var overlay = el( 'div', 'popcorn__overlay' );
		var box = el( 'div', 'popcorn__box' );

		box.setAttribute( 'role', 'dialog' );
		box.setAttribute( 'aria-modal', c.overlay ? 'true' : 'false' );
		box.setAttribute( 'aria-label', c.name || 'Popup' );
		box.setAttribute( 'tabindex', '-1' );

		var close = el( 'button', 'popcorn__close', '&times;' );
		close.type = 'button';
		close.setAttribute( 'aria-label', 'Close' );
		close.addEventListener( 'click', function () {
			self.hide( 'close' );
		} );

		var content = el( 'div', 'popcorn__content', c.content || '' );

		box.appendChild( close );
		box.appendChild( content );

		if ( c.ctaText || c.dismissText ) {
			var actions = el( 'div', 'popcorn__actions' );

			if ( c.ctaText ) {
				var cta;
				if ( c.ctaUrl ) {
					cta = el( 'a', 'popcorn__cta', c.ctaText );
					cta.href = c.ctaUrl;
					if ( c.ctaNewTab ) {
						cta.target = '_blank';
						cta.rel = 'noopener noreferrer';
					}
				} else {
					cta = el( 'button', 'popcorn__cta', c.ctaText );
					cta.type = 'button';
				}
				cta.addEventListener( 'click', function ( event ) {
					self.onCta( event );
				} );
				actions.appendChild( cta );
			}

			if ( c.dismissText ) {
				var dismiss = el( 'button', 'popcorn__dismiss', c.dismissText );
				dismiss.type = 'button';
				dismiss.addEventListener( 'click', function () {
					self.remember( true );
					self.hide( 'close' );
				} );
				actions.appendChild( dismiss );
			}

			box.appendChild( actions );
		}

		overlay.addEventListener( 'click', function () {
			self.hide( 'close' );
		} );

		root.appendChild( overlay );
		root.appendChild( box );
		document.body.appendChild( root );

		this.root = root;
		this.box = box;
		this.closeBtn = close;
		this.position = position;
	};

	/**
	 * Has this visitor already had their fill of this popup?
	 */
	Popup.prototype.alreadySeen = function () {
		if ( this.isPreview ) {
			return false;
		}

		if ( readFlag( 'local', 'popcorn_dismissed_' + this.id ) ) {
			return true;
		}

		var key = 'popcorn_seen_' + this.id;

		switch ( this.config.frequency ) {
			case 'always':
				return false;

			case 'session':
				return !! readFlag( 'session', key );

			case 'once':
				return !! readFlag( 'local', key );

			case 'days':
				var stamp = parseInt( readFlag( 'local', key ), 10 );
				if ( ! stamp ) {
					return false;
				}
				var days = Math.max( 1, this.config.freqDays || 7 );
				return ( Date.now() - stamp ) < days * 86400000;

			default:
				return false;
		}
	};

	Popup.prototype.remember = function ( forever ) {
		if ( this.isPreview ) {
			return;
		}

		if ( forever ) {
			writeFlag( 'local', 'popcorn_dismissed_' + this.id, '1' );
			return;
		}

		var key = 'popcorn_seen_' + this.id;

		if ( 'session' === this.config.frequency ) {
			writeFlag( 'session', key, '1' );
		} else if ( 'once' === this.config.frequency || 'days' === this.config.frequency ) {
			writeFlag( 'local', key, String( Date.now() ) );
		}
	};

	/**
	 * Device rule — checked here rather than on the server so page caches
	 * cannot serve a desktop-only popup to a phone.
	 */
	Popup.prototype.rightDevice = function () {
		var rule = this.config.devices || 'all';
		if ( 'desktop' === rule ) {
			return ! isMobile();
		}
		if ( 'mobile' === rule ) {
			return isMobile();
		}
		return true;
	};

	Popup.prototype.later = function ( fn, ms ) {
		this.timers.push( window.setTimeout( fn, ms ) );
	};

	Popup.prototype.clearTimers = function () {
		this.timers.forEach( window.clearTimeout );
		this.timers = [];
	};

	/**
	 * Wire up whatever makes this popup appear.
	 */
	Popup.prototype.arm = function () {
		var self = this;
		var c = this.config;

		// Click triggers stay armed even if the popup was already seen —
		// the visitor is asking for it on purpose.
		if ( 'click' === c.trigger ) {
			if ( c.selector ) {
				document.addEventListener( 'click', function ( event ) {
					var target = event.target.closest ? event.target.closest( c.selector ) : null;
					if ( target ) {
						event.preventDefault();
						self.show();
					}
				} );
			}
			return;
		}

		if ( this.alreadySeen() || ! this.rightDevice() ) {
			return;
		}

		switch ( c.trigger ) {
			case 'time':
				this.later( function () {
					self.show();
				}, Math.max( 0, c.delay ) * 1000 );
				break;

			case 'scroll':
				this.armScroll();
				break;

			case 'exit':
				this.armExit();
				break;

			case 'idle':
				this.armIdle();
				break;

			case 'chaos':
				this.later( function () {
					self.show();
				}, ( 4 + Math.random() * 16 ) * 1000 );
				break;
		}
	};

	Popup.prototype.armScroll = function () {
		var self = this;
		var target = Math.min( 100, Math.max( 1, self.config.scroll || 50 ) );

		var onScroll = function () {
			var doc = document.documentElement;
			var scrollable = doc.scrollHeight - window.innerHeight;
			var percent = scrollable > 0 ? ( window.pageYOffset / scrollable ) * 100 : 100;

			if ( percent >= target ) {
				window.removeEventListener( 'scroll', onScroll );
				self.show();
			}
		};

		window.addEventListener( 'scroll', onScroll, { passive: true } );
		onScroll();
	};

	Popup.prototype.armExit = function () {
		var self = this;

		if ( isMobile() ) {
			// Phones have no cursor to lose, so watch for a fast flick upwards
			// near the top of the page instead.
			var last = window.pageYOffset;
			var onScroll = function () {
				var now = window.pageYOffset;
				if ( last - now > 90 && now < 220 ) {
					window.removeEventListener( 'scroll', onScroll );
					self.show();
				}
				last = now;
			};
			window.addEventListener( 'scroll', onScroll, { passive: true } );
			return;
		}

		var onLeave = function ( event ) {
			if ( event.clientY > 8 || event.relatedTarget ) {
				return;
			}
			document.removeEventListener( 'mouseout', onLeave );
			self.show();
		};

		// A short grace period stops the popup firing before the page is read.
		this.later( function () {
			document.addEventListener( 'mouseout', onLeave );
		}, 2500 );
	};

	Popup.prototype.armIdle = function () {
		var self = this;
		var wait = Math.max( 3, self.config.idle || 20 ) * 1000;
		var timer = null;

		var reset = function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( function () {
				events.forEach( function ( name ) {
					window.removeEventListener( name, reset );
				} );
				self.show();
			}, wait );
		};

		var events = [ 'mousemove', 'keydown', 'scroll', 'touchstart', 'click' ];
		events.forEach( function ( name ) {
			window.addEventListener( name, reset, { passive: true } );
		} );

		reset();
	};

	/* -------------------------------------------------------- open/close */

	Popup.prototype.show = function () {
		if ( this.open ) {
			return;
		}

		// One popup at a time. Nobody enjoys a stack.
		if ( somethingIsOpen && ! this.isPreview ) {
			return;
		}

		var self = this;
		var c = this.config;

		this.open = true;
		somethingIsOpen = true;
		this.lastFocus = document.activeElement;

		this.root.classList.remove( 'is-closing' );
		this.root.classList.add( 'is-open' );

		if ( c.closeDelay > 0 ) {
			this.closeBtn.hidden = true;
			this.later( function () {
				self.closeBtn.hidden = false;
			}, c.closeDelay * 1000 );
		}

		if ( c.overlay && ( 'center' === this.position || 'fullscreen' === this.position ) ) {
			this.lockedScroll = true;
			document.body.style.overflow = 'hidden';
		}

		this.onKey = function ( event ) {
			if ( 'Escape' === event.key ) {
				self.hide( 'close' );
			} else if ( 'Tab' === event.key ) {
				self.trapFocus( event );
			}
		};
		document.addEventListener( 'keydown', this.onKey );

		window.setTimeout( function () {
			var first = self.box.querySelector( '.popcorn__cta' ) || self.box;
			first.focus( { preventScroll: true } );
		}, 60 );

		if ( c.sound ) {
			popSound();
		}
		if ( c.emojiRain ) {
			emojiRain( c.emojiRain );
		}

		this.remember( false );
		track( this.id, 'view' );

		this.root.dispatchEvent( new CustomEvent( 'popcorn:open', { bubbles: true, detail: { id: this.id } } ) );
	};

	Popup.prototype.hide = function ( reason ) {
		if ( ! this.open ) {
			return;
		}

		var self = this;
		this.open = false;
		somethingIsOpen = false;

		this.clearTimers();
		document.removeEventListener( 'keydown', this.onKey );

		if ( this.lockedScroll ) {
			document.body.style.overflow = '';
			this.lockedScroll = false;
		}

		this.root.classList.add( 'is-closing' );

		window.setTimeout( function () {
			self.root.classList.remove( 'is-open', 'is-closing' );
		}, 240 );

		if ( this.lastFocus && this.lastFocus.focus ) {
			this.lastFocus.focus( { preventScroll: true } );
		}

		if ( 'close' === reason ) {
			track( this.id, 'close' );
		}

		this.root.dispatchEvent( new CustomEvent( 'popcorn:close', { bubbles: true, detail: { id: this.id, reason: reason } } ) );
	};

	Popup.prototype.trapFocus = function ( event ) {
		var items = Array.prototype.slice.call( this.box.querySelectorAll( FOCUSABLE ) ).filter( function ( node ) {
			return ! node.hidden && null !== node.offsetParent;
		} );

		if ( ! items.length ) {
			return;
		}

		var first = items[ 0 ];
		var last = items[ items.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	};

	Popup.prototype.onCta = function ( event ) {
		var self = this;
		var c = this.config;

		track( this.id, 'click' );

		if ( c.confetti ) {
			confetti( c.accent );
		}

		if ( ! c.ctaUrl ) {
			this.hide( 'click' );
			return;
		}

		if ( c.ctaNewTab ) {
			// New tab keeps this page around — let the confetti finish here.
			this.later( function () {
				self.hide( 'click' );
			}, 900 );
			return;
		}

		if ( c.confetti && ! reducedMotion() ) {
			// Hold the navigation just long enough to enjoy the confetti.
			event.preventDefault();
			var href = c.ctaUrl;
			window.setTimeout( function () {
				window.location.href = href;
			}, 850 );
		}
	};

	/* -------------------------------------------------------------- boot */

	var Popcorn = {
		/**
		 * Build and arm every popup in the payload.
		 */
		boot: function ( data ) {
			if ( ! data || ! data.popups ) {
				return;
			}

			settings.endpoint = data.endpoint || '';
			settings.nonce = data.nonce || '';
			settings.track = !! data.track;

			data.popups.forEach( function ( config ) {
				if ( instances[ config.id ] ) {
					return;
				}
				var popup = new Popup( config );
				instances[ config.id ] = popup;
				popup.arm();
			} );

			// Any element can open a popup: data-popcorn-open="12".
			document.addEventListener( 'click', function ( event ) {
				var opener = event.target.closest ? event.target.closest( '[data-popcorn-open]' ) : null;
				if ( ! opener ) {
					return;
				}
				var id = opener.getAttribute( 'data-popcorn-open' );
				if ( instances[ id ] ) {
					event.preventDefault();
					instances[ id ].show();
				}
			} );
		},

		open: function ( id ) {
			if ( instances[ id ] ) {
				instances[ id ].show();
			}
		},

		close: function ( id ) {
			if ( instances[ id ] ) {
				instances[ id ].hide( 'close' );
			}
		},

		/**
		 * Throwaway popup used by the admin preview. Never tracked, never
		 * remembered, and it replaces the previous preview each time.
		 */
		preview: function ( config ) {
			if ( this.previewInstance ) {
				this.previewInstance.hide( 'preview' );
				if ( this.previewInstance.root.parentNode ) {
					this.previewInstance.root.parentNode.removeChild( this.previewInstance.root );
				}
			}

			config.id = 'preview';
			somethingIsOpen = false;

			var popup = new Popup( config, true );
			this.previewInstance = popup;

			// Let the browser see the element before the animation starts.
			window.requestAnimationFrame( function () {
				popup.show();
			} );

			return popup;
		},

		confetti: confetti,
		instances: instances
	};

	window.Popcorn = Popcorn;

	function start() {
		Popcorn.boot( window.PopcornData );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
