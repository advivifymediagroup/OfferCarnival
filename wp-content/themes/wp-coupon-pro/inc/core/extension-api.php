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
			'domain'  => array(
				'required' => true,
				'type'     => 'string',
			),
			// Country slug (e.g. "ae"); omitted = the default country, so existing callers behave as before.
			'country' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
			),
		),
	) );
	// The list of store domains, so the extension can match the site a shopper is on without telling us what they browse.
	register_rest_route( 'wpc/v1', '/domains', array(
		'methods'             => 'GET',
		'callback'            => 'wpc_ext_get_domains',
		'permission_callback' => '__return_true',
	) );
} );

/** apex domain => [ store term_id, ... ] from each store's website and affiliate URL. Cached for an hour. */
function wpc_ext_domain_map() {
	$map = get_transient( 'wpc_ext_domain_map' );
	if ( false !== $map ) {
		return $map;
	}
	global $wpdb;
	$map  = array();
	$rows = $wpdb->get_results( "SELECT term_id, meta_value FROM {$wpdb->termmeta} WHERE meta_key IN ( '_wpc_store_domain', '_wpc_store_url', '_wpc_store_aff_url' ) AND meta_value <> ''" );
	foreach ( $rows as $row ) {
		$url  = trim( $row->meta_value );
		$host = wp_parse_url( false === strpos( $url, '//' ) ? '//' . $url : $url, PHP_URL_HOST ); // "noon.com" without a scheme too
		if ( $host ) {
			$map[ wpc_ext_apex_domain( $host ) ][ (int) $row->term_id ] = true;
		}
	}
	foreach ( $map as $apex => $ids ) {
		$map[ $apex ] = array_keys( $ids );
	}
	set_transient( 'wpc_ext_domain_map', $map, HOUR_IN_SECONDS );
	return $map;
}

function wpc_ext_get_domains() {
	$domains = array();
	foreach ( wpc_ext_domain_map() as $apex => $ids ) {
		$countries = array();
		foreach ( $ids as $id ) {
			$countries = array_merge( $countries, wpcoupon_get_store_countries( $id ) );
		}
		$domains[ $apex ] = array_values( array_unique( $countries ) );
	}
	$countries = array();
	foreach ( wpcoupon_get_countries() as $term ) {
		$countries[] = array( 'slug' => $term->slug, 'name' => $term->name );
	}
	$response = rest_ensure_response( array(
		'default'   => wpcoupon_get_default_country(),
		'countries' => $countries,
		'domains'   => $domains,
	) );
	$response->header( 'Cache-Control', 'public, max-age=3600' );
	return $response;
}

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

	$country = (string) $request->get_param( 'country' );
	if ( ! $country ) {
		$country = wpcoupon_get_default_country();
	}

	$cache_key = 'wpc_ext_coupons_' . md5( $apex . '|' . $country );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return rest_ensure_response( $cached );
	}

	$store = wpc_ext_find_store_by_apex( $apex, $country );
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
		if ( $coupon->has_expired() || ! in_array( $country, wpcoupon_get_coupon_countries( $post->ID ), true ) ) {
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

/** The store on this domain that operates in $country (several regional stores can share one domain). */
function wpc_ext_find_store_by_apex( $apex, $country ) {
	$map = wpc_ext_domain_map();
	foreach ( isset( $map[ $apex ] ) ? $map[ $apex ] : array() as $id ) {
		$term = get_term( $id, 'coupon_store' );
		if ( $term && ! is_wp_error( $term ) && in_array( $country, wpcoupon_get_store_countries( $id ), true ) ) {
			return $term;
		}
	}
	return null;
}
