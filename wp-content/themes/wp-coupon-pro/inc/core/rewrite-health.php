<?php
/**
 * Keeps pretty URLs working when the web server stops applying WordPress's .htaccess rules.
 *
 * Symptom this guards against: URLs like /categories/ or /store/x/ answer with the host's own "This Page
 * Does Not Exist" page (no WordPress involved) while /?p=1 and /index.php/... still work, usually in waves.
 *
 * Every few minutes one front-end request probes a pretty URL (/wpc-health/). If the server answers 404
 * without WordPress having handled it, the WordPress block in .htaccess is restored if it is missing and
 * the file is touched, which makes the server reload it. At most once every 30 minutes, and it never
 * rewrites .htaccess while the probe passes.
 *
 * It also keeps a short history of what changed inside .htaccess (section names and sizes only, never the
 * contents), so it is possible to tell which software rewrote it and when.
 *
 * Read-only status (works even when pretty URLs do not):  /?rest_route=/wpc/v1/health
 */

// The probe target: answered by WordPress itself, with a header only WordPress sends.
add_action( 'init', function () {
	$path = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH );
	if ( '/wpc-health/' === $path || '/wpc-health' === $path ) {
		nocache_headers();
		header( 'X-WPC-Health: ok' );
		header( 'Content-Type: text/plain' );
		echo 'ok';
		exit;
	}
}, 1 );

/** Section name => [bytes, short hash] for every "# BEGIN x ... # END x" block, plus what lies outside the blocks. */
function wpcoupon_htaccess_sections( $file ) {
	$out = array();
	if ( ! is_readable( $file ) ) {
		return $out;
	}
	$text = (string) file_get_contents( $file );
	$rest = $text;
	if ( preg_match_all( '/^# BEGIN (.+?)[ \t]*\r?\n(.*?)^# END \1[ \t]*\r?$/ms', $text, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $block ) {
			$out[ $block[1] ] = array( strlen( $block[0] ), substr( md5( $block[0] ), 0, 8 ) );
			$rest             = str_replace( $block[0], '', $rest );
		}
	}
	$rest = trim( $rest );
	if ( '' !== $rest ) {
		$out['(outside any section)'] = array( strlen( $rest ), substr( md5( $rest ), 0, 8 ) );
	}
	return $out;
}

function wpcoupon_htaccess_state() {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	$file = get_home_path() . '.htaccess';
	return array(
		'file'      => $file,
		'exists'    => file_exists( $file ),
		'writable'  => is_writable( $file ),
		'has_block' => file_exists( $file ) && in_array( 'RewriteRule . /index.php [L]', (array) extract_from_markers( $file, 'WordPress' ), true ),
		'mtime'     => file_exists( $file ) ? filemtime( $file ) : 0,
		'size'      => file_exists( $file ) ? filesize( $file ) : 0,
		'sections'  => wpcoupon_htaccess_sections( $file ),
	);
}

/** Records a history entry whenever the set of sections (or their content) differs from the last look. */
function wpcoupon_htaccess_record_history( $state ) {
	$snapshot = $state['sections'];
	ksort( $snapshot );
	$sig  = md5( wp_json_encode( $snapshot ) );
	$hist = get_option( 'wpc_htaccess_history', array() );
	$hist = is_array( $hist ) ? $hist : array();
	$last = $hist ? end( $hist ) : null;
	if ( $last && $last['sig'] === $sig ) {
		return;
	}
	$changed = array();
	$before  = $last ? $last['sections'] : array();
	foreach ( array_unique( array_merge( array_keys( $before ), array_keys( $snapshot ) ) ) as $name ) {
		if ( ! isset( $before[ $name ] ) ) {
			$changed[ $name ] = 'added';
		} elseif ( ! isset( $snapshot[ $name ] ) ) {
			$changed[ $name ] = 'removed';
		} elseif ( $before[ $name ] !== $snapshot[ $name ] ) {
			$changed[ $name ] = 'changed';
		}
	}
	$hist[] = array( 'seen' => time(), 'file_mtime' => $state['mtime'], 'size' => $state['size'], 'sig' => $sig, 'sections' => $snapshot, 'changed' => $last ? $changed : 'first look' );
	update_option( 'wpc_htaccess_history', array_slice( $hist, -30 ), false );
}

function wpcoupon_rewrite_health_check() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ! get_option( 'permalink_structure' ) ) {
		return;
	}
	if ( get_transient( 'wpc_rewrite_checked' ) ) {
		return;
	}
	set_transient( 'wpc_rewrite_checked', 1, 5 * MINUTE_IN_SECONDS );
	// Do the probe after the visitor has their page.
	if ( function_exists( 'fastcgi_finish_request' ) ) {
		fastcgi_finish_request();
	} elseif ( function_exists( 'litespeed_finish_request' ) ) {
		litespeed_finish_request();
	}
	wpcoupon_htaccess_record_history( wpcoupon_htaccess_state() );
	$r      = wp_remote_get( apply_filters( 'wpc_rewrite_probe_url', home_url( '/wpc-health/' ) ), array( 'timeout' => 8, 'redirection' => 0, 'headers' => array( 'Cache-Control' => 'no-cache' ) ) );
	$code   = is_wp_error( $r ) ? 0 : (int) wp_remote_retrieve_response_code( $r );
	$ok     = ( 200 === $code && 'ok' === wp_remote_retrieve_header( $r, 'x-wpc-health' ) );
	$health = array( 'checked' => time(), 'ok' => $ok, 'code' => $code );
	update_option( 'wpc_rewrite_health', array_merge( (array) get_option( 'wpc_rewrite_health', array() ), $health ), false );
	// Only a server-level 404 means "rules not applied"; timeouts or resets are not acted on.
	if ( 404 !== $code || get_transient( 'wpc_rewrite_healed' ) ) {
		return;
	}
	set_transient( 'wpc_rewrite_healed', 1, 30 * MINUTE_IN_SECONDS );
	$state = wpcoupon_htaccess_state();
	if ( $state['writable'] ) {
		global $wp_rewrite;
		if ( ! $state['has_block'] ) {
			insert_with_markers( $state['file'], 'WordPress', explode( "\n", $wp_rewrite->mod_rewrite_rules() ) );
		}
		clearstatcache();
		touch( $state['file'] );
	}
	update_option( 'wpc_rewrite_health', array_merge( $health, array( 'healed' => time(), 'had_block' => $state['has_block'], 'writable' => $state['writable'] ) ), false );
}
add_action( 'shutdown', 'wpcoupon_rewrite_health_check' );

add_action( 'rest_api_init', function () {
	register_rest_route( 'wpc/v1', '/health', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function () {
			$s = wpcoupon_htaccess_state();
			unset( $s['file'] );
			// Other hidden config files in the web root: names and times only.
			$siblings = array();
			foreach ( (array) glob( get_home_path() . '.*' ) as $f ) {
				$n = basename( $f );
				if ( is_file( $f ) && preg_match( '/^\.(htaccess|litespeed|user\.ini|wp-)/', $n ) ) {
					$siblings[ $n ] = array( 'bytes' => filesize( $f ), 'mtime' => filemtime( $f ) );
				}
			}
			$ls = array();
			foreach ( array( 'cache', 'guest', 'object', 'crawler', 'cdn' ) as $k ) {
				$ls[ $k ] = get_option( 'litespeed.conf.' . $k, null );
			}
			return rest_ensure_response( array(
				'now'        => time(),
				'last_probe' => get_option( 'wpc_rewrite_health' ),
				'htaccess'   => $s,
				'history'    => get_option( 'wpc_htaccess_history', array() ),
				'other_files' => $siblings,
				'litespeed_cache_plugin' => array( 'active' => in_array( 'litespeed-cache/litespeed-cache.php', (array) get_option( 'active_plugins' ), true ), 'version' => defined( 'LSCWP_V' ) ? LSCWP_V : null, 'settings' => $ls ),
				'site_url'   => home_url(),
			) );
		},
	) );
} );
