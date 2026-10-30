/**
 * Appearance > Widgets (block-based screen): this theme's widgets are old-style widgets, and the screen loads every one
 * of them with its own server request, all at the same moment. On this hosting some of those requests are refused
 * ("Error establishing a database connection", HTTP 500) and the editor then marks the block as "affected by errors".
 * This sends those requests a few at a time, and sends a refused one again, so every widget loads.
 */
( function ( wp ) {
	'use strict';
	if ( ! wp || ! wp.apiFetch ) {
		return;
	}

	var MAX_AT_ONCE = 3, RETRIES = 4;
	var running = 0, waiting = [];

	function next() {
		while ( running < MAX_AT_ONCE && waiting.length ) {
			running++;
			waiting.shift()();
		}
	}

	function done() {
		running--;
		next();
	}

	function refused( err ) {
		// A server error, a database-connection error page, or a request that did not get through.
		return !! err && ( err.code === 'wp_die' || err.code === 'invalid_json' || err.code === 'fetch_error' || ( err.data && err.data.status >= 500 ) );
	}

	function withRetry( send, left ) {
		return send().catch( function ( err ) {
			if ( left > 0 && refused( err ) ) {
				return new Promise( function ( resolve ) {
					setTimeout( resolve, 700 * ( RETRIES - left + 1 ) );
				} ).then( function () {
					return withRetry( send, left - 1 );
				} );
			}
			throw err;
		} );
	}

	wp.apiFetch.use( function ( options, forward ) {
		var target = options.path || options.url || '';
		if ( ! /widget-types|legacy-widget/.test( target ) ) {
			return forward( options );
		}
		return new Promise( function ( resolve, reject ) {
			waiting.push( function () {
				withRetry( function () {
					return forward( options );
				}, RETRIES ).then( resolve, reject ).then( done, done );
			} );
			next();
		} );
	} );
}( window.wp ) );
