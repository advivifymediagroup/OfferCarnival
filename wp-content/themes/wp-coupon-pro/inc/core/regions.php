<?php
/**
 * Regions = every country except the default one (Worldwide). Adding a country (Coupons > Countries > Add) is all
 * it takes: this file then does the rest by itself.
 *
 *  - URL prefix: /<slug>/ routes to that country's site (list kept in the option wpc_regions, read by countries.php).
 *  - Widget areas: Footer Center, Frontpage Before Main, Main and After Main get a "(<Country>)" copy, registered
 *    for every region (see wpcoupon_widgets_init in functions.php). An empty area falls back to the shared one.
 *  - Starting content: when the country is created its Before Main, Main and Footer Center areas are filled with
 *    widgets that follow the country by themselves (Popular Stores, category grid, latest coupons, Top Stores), so
 *    the new site is not empty. Edit or replace them in Appearance > Widgets whenever you like.
 *  - Already automatic elsewhere: country switcher, "Show in countries" on menu items, store/coupon country
 *    fields, SEO per country, Rank Math > Country SEO, sitemap URLs.
 */

/** [ slug => name ] of every region. The fallback only covers the time before the first sync. */
function wpcoupon_regions() {
	$regions = get_option( 'wpc_regions' );
	return is_array( $regions ) && $regions ? $regions : array( 'ae' => 'United Arab Emirates', 'sea' => 'South East Asia' );
}

/** Short name for labels and widget areas: "UAE" and "SEA" as before, otherwise the country name. */
function wpcoupon_region_short_name( $slug, $name ) {
	$short = array( 'ae' => 'UAE', 'sea' => 'SEA' );
	return isset( $short[ $slug ] ) ? $short[ $slug ] : $name;
}

/** Regex alternation of the region slugs, e.g. "ae|sea" (for matching "/ae/..." in URLs). */
function wpcoupon_regions_regex() {
	return implode( '|', array_map( function ( $slug ) {
		return preg_quote( $slug, '#' );
	}, array_keys( wpcoupon_regions() ) ) );
}

/** Rebuilds the list from the countries that exist. A country whose slug is also a live page/post slug is skipped, so it can never hide that page. */
function wpcoupon_sync_regions() {
	global $wpdb;
	$default   = wpcoupon_get_default_country();
	$regions   = array();
	$conflicts = array();
	foreach ( wpcoupon_get_countries() as $term ) {
		if ( $term->slug === $default ) {
			continue;
		}
		if ( $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type IN ('page','post') AND post_status = 'publish' LIMIT 1", $term->slug ) ) ) {
			$conflicts[] = $term->slug;
			continue;
		}
		$regions[ $term->slug ] = $term->name;
	}
	update_option( 'wpc_regions', $regions );
	update_option( 'wpc_region_conflicts', $conflicts, false );
	if ( 'cli' === PHP_SAPI ) {
		update_option( 'wpc_flush_request', (string) time(), false ); // see cache-flush.php
	}
	return $regions;
}

/** Fills a new region's empty Before Main, Main and Footer Center areas with widgets that follow the country on their own. */
function wpcoupon_seed_region_widgets( $slug, $name ) {
	$short    = wpcoupon_region_short_name( $slug, $name );
	$stores   = array( 'orderby' => 'count', 'order' => 'desc', 'include' => '', 'exclude' => '' );
	$plan     = array(
		'frontpage-before-main-' . $slug => array(
			array( 'popular_stores', array_merge( $stores, array( 'title' => sprintf( 'Popular Stores in %s', $short ), 'number' => 20, 'item_per_row' => 5 ) ) ),
			array( 'st_categories_grid_view', array( 'title' => 'Shop Coupons by Category', 'orderby' => 'count', 'order' => 'desc', 'number' => '12', 'item_per_row' => '6' ) ),
		),
		'frontpage-main-' . $slug        => array(
			array( 'st_coupons', array( 'title' => sprintf( 'Latest Deals in %s', $short ), 'coupon_ids' => '' ) ), // no ids = the newest coupons of this country
		),
		'footer-center-' . $slug         => array(
			array( 'popular_stores', array_merge( $stores, array( 'title' => 'Top Stores', 'number' => 8, 'item_per_row' => 4 ) ) ),
		),
	);
	$sidebars = get_option( 'sidebars_widgets' );
	$sidebars = is_array( $sidebars ) ? $sidebars : array();
	foreach ( $plan as $sidebar_id => $widgets ) {
		if ( ! empty( $sidebars[ $sidebar_id ] ) ) {
			continue;
		}
		foreach ( $widgets as $widget ) {
			list( $base, $settings ) = $widget;
			$all  = get_option( 'widget_' . $base );
			$all  = is_array( $all ) ? $all : array( '_multiwidget' => 1 );
			$ints = array_filter( array_keys( $all ), 'is_int' );
			$n    = $ints ? max( $ints ) + 1 : 1;
			$all[ $n ] = $settings;
			update_option( 'widget_' . $base, $all );
			$sidebars[ $sidebar_id ][] = $base . '-' . $n;
		}
	}
	update_option( 'sidebars_widgets', $sidebars );
}

add_action( 'created_wpc_country', function ( $term_id ) {
	$term = get_term( $term_id, 'wpc_country' );
	if ( ! $term || is_wp_error( $term ) ) {
		return;
	}
	wpcoupon_sync_regions();
	if ( $term->slug !== wpcoupon_get_default_country() && isset( wpcoupon_regions()[ $term->slug ] ) ) {
		wpcoupon_seed_region_widgets( $term->slug, $term->name );
	}
} );
add_action( 'edited_wpc_country', 'wpcoupon_sync_regions', 20 );
add_action( 'delete_wpc_country', 'wpcoupon_sync_regions', 20 );

// First run (option not stored yet), e.g. right after this code is deployed.
add_action( 'init', function () {
	if ( false === get_option( 'wpc_regions' ) ) {
		wpcoupon_sync_regions();
	}
}, 20 );

add_action( 'admin_notices', function () {
	$conflicts = get_option( 'wpc_region_conflicts' );
	if ( $conflicts && current_user_can( 'manage_options' ) ) {
		echo '<div class="notice notice-warning"><p>' . esc_html( sprintf( 'Country slug "%s" is also the slug of an existing page or post, so it was not given its own /slug/ site. Change the country slug (or the page slug) to fix it.', implode( ', ', (array) $conflicts ) ) ) . '</p></div>';
	}
} );
