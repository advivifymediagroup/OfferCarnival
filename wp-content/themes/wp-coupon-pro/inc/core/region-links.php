<?php
/**
 * Links typed by hand into widgets (the footer logo, "Home", "Stores"...) point at the bare site address, so on a
 * region site (/ae/ ...) they would leave it. On region pages, put the region in front of such links.
 * Left alone: links that already carry a region, wp-* paths and files (images, pdf, xml...).
 */
function wpcoupon_region_links( $html ) {
	if ( ! defined( 'WPC_URL_COUNTRY' ) || ! is_string( $html ) || false === strpos( $html, 'href=' ) ) {
		return $html;
	}
	$home = untrailingslashit( get_option( 'home' ) );
	return preg_replace_callback( '#href=(["\'])' . preg_quote( $home, '#' ) . '(/[^"\']*|)\1#i', function ( $m ) use ( $home ) {
		$path = '' === $m[2] ? '/' : $m[2];
		if ( preg_match( '#^/(?:' . wpcoupon_regions_regex() . ')(?:[/?\#]|$)|^/wp-|\.[a-z0-9]{2,4}(?:[?\#]|$)#i', $path ) ) {
			return $m[0];
		}
		return 'href=' . $m[1] . $home . '/' . WPC_URL_COUNTRY . $path . $m[1];
	}, $html );
}
foreach ( array( 'widget_text', 'widget_text_content', 'widget_custom_html_content', 'widget_block_content' ) as $wpc_hook ) {
	add_filter( $wpc_hook, 'wpcoupon_region_links', 20 );
}
