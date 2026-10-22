<?php
/**
 * Separate Rank Math title and description per country for stores and categories.
 *
 * A store (or category) that exists in more than one country is one record, so Rank Math can only hold one title for
 * it. Here the store/category edit screen gets "<Country> SEO title / description" fields for every country except the
 * default one (which keeps using Rank Math's own fields). On that country's site (/ae/ ...) the filled-in value
 * replaces Rank Math's title/description; empty = Rank Math's value, as before. Rank Math variables such as
 * %term% %sep% %sitename% work in these fields.
 */
function wpcoupon_country_seo_taxonomies() {
	return array( 'coupon_store', 'coupon_category' );
}

add_action( 'cmb2_admin_init', function () {
	$default = wpcoupon_get_default_country();
	$box     = new_cmb2_box( array(
		'id'           => '_wpc_country_seo',
		'title'        => esc_html__( 'SEO per country', 'wp-coupon-pro' ),
		'object_types' => array( 'term' ),
		'taxonomies'   => wpcoupon_country_seo_taxonomies(),
	) );
	foreach ( wpcoupon_get_countries() as $country ) {
		if ( $country->slug === $default ) {
			continue;
		}
		$box->add_field( array(
			'name' => sprintf( esc_html__( '%s SEO title', 'wp-coupon-pro' ), $country->name ),
			'id'   => '_wpc_seo_title_' . $country->slug,
			'desc' => esc_html__( 'Shown instead of the Rank Math title on this country\'s site. Leave empty to use the Rank Math title. Variables: %term% %sep% %sitename%', 'wp-coupon-pro' ),
			'type' => 'text',
		) );
		$box->add_field( array(
			'name' => sprintf( esc_html__( '%s SEO description', 'wp-coupon-pro' ), $country->name ),
			'id'   => '_wpc_seo_description_' . $country->slug,
			'desc' => esc_html__( 'Shown instead of the Rank Math description on this country\'s site. Leave empty to use the Rank Math description.', 'wp-coupon-pro' ),
			'type' => 'textarea_small',
		) );
	}
} );

function wpcoupon_country_seo_value( $text, $field ) {
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term || ! in_array( $term->taxonomy, wpcoupon_country_seo_taxonomies(), true ) ) {
		return $text;
	}
	$custom = trim( (string) get_term_meta( $term->term_id, '_wpc_seo_' . $field . '_' . wpcoupon_get_current_country(), true ) );
	if ( '' === $custom ) {
		return $text;
	}
	return class_exists( '\RankMath\Helper' ) ? \RankMath\Helper::replace_vars( $custom, $term ) : $custom;
}
add_filter( 'rank_math/frontend/title', function ( $title ) {
	return wpcoupon_country_seo_value( $title, 'title' );
}, 20 );
add_filter( 'rank_math/frontend/description', function ( $description ) {
	return wpcoupon_country_seo_value( $description, 'description' );
}, 20 );
