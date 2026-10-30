<?php
/**
 * Import scripts run over SSH write straight to the database, but the web server keeps its own persistent object
 * cache and keeps serving the old values (widgets, options) until it is flushed. A script records the request in the
 * option wpc_flush_request; the first web request after that flushes the object and page cache once.
 * The very first run (no wpc_flush_done yet) flushes once as well.
 */
add_action( 'init', function () {
	global $wpdb;
	$rows = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name IN ('wpc_flush_request','wpc_flush_done')", OBJECT_K );
	$req  = isset( $rows['wpc_flush_request'] ) ? (string) $rows['wpc_flush_request']->option_value : '';
	$done = isset( $rows['wpc_flush_done'] ) ? (string) $rows['wpc_flush_done']->option_value : null;
	if ( null !== $done && ( '' === $req || $req === $done ) ) {
		return;
	}
	wp_cache_flush();
	update_option( 'wpc_flush_done', '' !== $req ? $req : 'initial', false );
	do_action( 'litespeed_purge_all' );
}, 0 );
