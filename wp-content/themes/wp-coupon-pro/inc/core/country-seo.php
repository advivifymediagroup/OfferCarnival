<?php
/**
 * Separate Rank Math title and description per country for stores and categories.
 *
 * A store (or category) that exists in more than one country is one record, so Rank Math can only hold one title for
 * it. Two ways to set the other countries' text, both used only on that country's site (/ae/ ...):
 *
 *  1. Rank Math > Country SEO: one template per country for ALL stores (and all categories) at once, using Rank Math
 *     variables (%term% %sep% %sitename% ...).
 *  2. The store/category edit screen, "SEO per country": a text for just that one store, which wins over the template.
 *
 * Empty = Rank Math's own value, as before. The default country (Worldwide) is not touched.
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
	$country = wpcoupon_get_current_country();
	$custom  = trim( (string) get_term_meta( $term->term_id, '_wpc_seo_' . $field . '_' . $country, true ) );
	if ( '' === $custom ) {
		$templates = get_option( 'wpc_country_seo', array() );
		$custom    = isset( $templates[ $country ][ $term->taxonomy ][ $field ] ) ? trim( (string) $templates[ $country ][ $term->taxonomy ][ $field ] ) : '';
	}
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

/** Rank Math > Country SEO: the per-country templates. */
add_action( 'admin_menu', function () {
	add_submenu_page( class_exists( '\RankMath\Helper' ) ? 'rank-math' : 'options-general.php', esc_html__( 'Country SEO', 'wp-coupon-pro' ), esc_html__( 'Country SEO', 'wp-coupon-pro' ), 'manage_options', 'wpc-country-seo', 'wpcoupon_country_seo_page' );
}, 20 );

add_action( 'admin_init', function () {
	register_setting( 'wpc_country_seo', 'wpc_country_seo', array( 'sanitize_callback' => function ( $in ) {
		$out = array();
		foreach ( wpcoupon_get_countries() as $country ) {
			foreach ( wpcoupon_country_seo_taxonomies() as $tax ) {
				foreach ( array( 'title' => 'sanitize_text_field', 'description' => 'sanitize_textarea_field' ) as $field => $clean ) {
					$out[ $country->slug ][ $tax ][ $field ] = isset( $in[ $country->slug ][ $tax ][ $field ] ) ? $clean( wp_unslash( $in[ $country->slug ][ $tax ][ $field ] ) ) : '';
				}
			}
		}
		return $out;
	} ) );
} );

function wpcoupon_country_seo_page() {
	$default = wpcoupon_get_default_country();
	$saved   = get_option( 'wpc_country_seo', array() );
	$labels  = array( 'coupon_store' => esc_html__( 'Stores', 'wp-coupon-pro' ), 'coupon_category' => esc_html__( 'Categories', 'wp-coupon-pro' ) );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Country SEO', 'wp-coupon-pro' ); ?></h1>
		<p><?php esc_html_e( 'Title and description for every store / category on a country site, in one place. Leave a field empty to use the normal Rank Math title/description. A text set on the edit screen of one store (SEO per country) wins over these. The Worldwide site is set in Rank Math as usual.', 'wp-coupon-pro' ); ?></p>
		<p><?php esc_html_e( 'Variables: %term% (store/category name) %sep% %sitename% %term_description% %page%', 'wp-coupon-pro' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'wpc_country_seo' ); ?>
			<?php foreach ( wpcoupon_get_countries() as $country ) : ?>
				<?php if ( $country->slug === $default ) { continue; } ?>
				<h2><?php echo esc_html( $country->name ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( $labels as $tax => $label ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( sprintf( __( '%s title', 'wp-coupon-pro' ), $label ) ); ?></th>
							<td><input type="text" class="large-text" name="wpc_country_seo[<?php echo esc_attr( $country->slug ); ?>][<?php echo esc_attr( $tax ); ?>][title]" value="<?php echo esc_attr( isset( $saved[ $country->slug ][ $tax ]['title'] ) ? $saved[ $country->slug ][ $tax ]['title'] : '' ); ?>" placeholder="%term% Coupons &amp; Offers in <?php echo esc_attr( $country->name ); ?> %sep% %sitename%"></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html( sprintf( __( '%s description', 'wp-coupon-pro' ), $label ) ); ?></th>
							<td><textarea class="large-text" rows="2" name="wpc_country_seo[<?php echo esc_attr( $country->slug ); ?>][<?php echo esc_attr( $tax ); ?>][description]"><?php echo esc_textarea( isset( $saved[ $country->slug ][ $tax ]['description'] ) ? $saved[ $country->slug ][ $tax ]['description'] : '' ); ?></textarea></td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endforeach; ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
