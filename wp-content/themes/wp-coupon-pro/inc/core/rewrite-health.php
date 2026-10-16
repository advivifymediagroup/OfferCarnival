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
	);
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
			return rest_ensure_response( array( 'last_probe' => get_option( 'wpc_rewrite_health' ), 'htaccess' => $s, 'now' => time() ) );
		},
	) );
} );
