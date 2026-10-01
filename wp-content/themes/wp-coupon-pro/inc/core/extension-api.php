<?php
/**
 * REST API for the Offer Carnival browser extension: given a domain the
 * shopper is currently on, returns that store's active coupons.
 *
 * GET /wp-json/wpc/v1/coupons?domain=noon.com
 */

add_action( 'rest_api_init', function () {
	register_rest_route( 'wpc/v1', '/coupons', array(
		'methods'             => 'GET',
		'callback'            => 'wpc_ext_get_coupons',
		'permission_callback' => '__return_true',
		'args'                => array(
			'domain' => array(
				'required' => true,
				'type'     => 'string',
			),
		),
	) );
} );

/**
 * Registrable domain for matching (e.g. "checkout.noon.com" -> "noon.com",
 * "store.something.co.uk" -> "something.co.uk"). Good enough for matching
 * store URLs against a shopper's current host without a full PSL.
 */
function wpc_ext_apex_domain( $host ) {
	$host  = strtolower( preg_replace( '#^www\.#', '', trim( $host ) ) );
	$parts = explode( '.', $host );
	if ( count( $parts ) <= 2 ) {
		return $host;
	}
	$second_level_cctlds = array( 'co', 'com', 'net', 'org', 'gov', 'ac', 'edu' );
	$take = in_array( $parts[ count( $parts ) - 2 ], $second_level_cctlds, true ) ? 3 : 2;
	return implode( '.', array_slice( $parts, -$take ) );
}

function wpc_ext_get_coupons( WP_REST_Request $request ) {
	$apex = wpc_ext_apex_domain( (string) $request->get_param( 'domain' ) );
	if ( ! $apex ) {
		return new WP_Error( 'wpc_bad_domain', 'Invalid domain', array( 'status' => 400 ) );
	}

	$cache_key = 'wpc_ext_coupons_' . md5( $apex );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return rest_ensure_response( $cached );
	}

	$store = wpc_ext_find_store_by_apex( $apex );
	if ( ! $store ) {
		$result = array( 'store' => null, 'coupons' => array() );
		set_transient( $cache_key, $result, 10 * MINUTE_IN_SECONDS );
		return rest_ensure_response( $result );
	}

	// The extension wants every coupon a store has, regardless of which
	// country the site would normally scope a listing to for a browsing
	// visitor — so this one query runs with that country filter suspended.
	remove_action( 'pre_get_posts', 'wpcoupon_filter_coupons_by_country', 20 );
	$posts = get_posts( array(
		'post_type'      => 'coupon',
		'post_status'    => 'publish',
		'posts_per_page' => 20,
		'tax_query'      => array(
			array(
				'taxonomy' => 'coupon_store',
				'field'    => 'term_id',
				'terms'    => $store->term_id,
			),
		),
	) );
	add_action( 'pre_get_posts', 'wpcoupon_filter_coupons_by_country', 20 );

	$coupons = array();
	foreach ( $posts as $post ) {
		$coupon = new WPCoupon_Coupon( $post );
		if ( $coupon->has_expired() ) {
			continue;
		}
		$coupons[] = array(
			'id'       => $post->ID,
			'title'    => get_the_title( $post ),
			'type'     => $coupon->get_type(),
			'code'     => 'code' === $coupon->get_type() ? $coupon->get_code() : null,
			'href'     => $coupon->get_href(),
		);
	}

	$result = array(
		'store'   => array(
			'name' => $store->name,
			'slug' => $store->slug,
			'href' => get_term_link( $store ),
		),
		'coupons' => $coupons,
	);
	set_transient( $cache_key, $result, 10 * MINUTE_IN_SECONDS );
	return rest_ensure_response( $result );
}

function wpc_ext_find_store_by_apex( $apex ) {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT tm.term_id, tm.meta_value FROM {$wpdb->termmeta} tm
		WHERE tm.meta_key IN ( '_wpc_store_url', '_wpc_store_aff_url' )
		AND tm.meta_value LIKE %s",
		'%' . $wpdb->esc_like( $apex ) . '%'
	) );

	foreach ( $rows as $row ) {
		$host = wp_parse_url( $row->meta_value, PHP_URL_HOST );
		if ( $host && wpc_ext_apex_domain( $host ) === $apex ) {
			$term = get_term( $row->term_id, 'coupon_store' );
			if ( $term && ! is_wp_error( $term ) ) {
				return $term;
			}
		}
	}
	return null;
}
