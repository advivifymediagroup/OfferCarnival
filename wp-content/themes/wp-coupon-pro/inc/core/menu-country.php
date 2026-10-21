<?php
/**
 * Per-country visibility for navigation menu items.
 *
 * Appearance > Menus: every menu item gets a "Show in countries" checklist. Nothing ticked = shown everywhere
 * (so existing menus are unchanged); otherwise the item is shown only on the ticked countries' sites.
 * A hidden item hides its sub-items too.
 */

/** Admin: the checklist inside each menu item. */
add_action( 'wp_nav_menu_item_custom_fields', function ( $item_id ) {
	$chosen = get_post_meta( $item_id, '_wpc_menu_countries', true );
	$chosen = is_array( $chosen ) ? $chosen : array();
	?>
	<fieldset class="field-wpc-countries description-wide" style="margin:6px 0;">
		<legend><strong><?php esc_html_e( 'Show in countries', 'wp-coupon' ); ?></strong></legend>
		<?php foreach ( wpcoupon_get_country_field_options() as $slug => $name ) : ?>
			<label style="margin-right:12px;">
				<input type="checkbox" name="menu-item-wpc-countries[<?php echo (int) $item_id; ?>][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $chosen, true ) ); ?>>
				<?php echo esc_html( $name ); ?>
			</label>
		<?php endforeach; ?>
		<br><span class="description"><?php esc_html_e( 'Leave all unticked to show this item in every country.', 'wp-coupon' ); ?></span>
	</fieldset>
	<?php
} );

/** Admin: save it. */
add_action( 'wp_update_nav_menu_item', function ( $menu_id, $item_id ) {
	// phpcs:ignore WordPress.Security.NonceVerification -- the menu editor verifies its own nonce before this hook fires.
	$posted = isset( $_POST['menu-item-wpc-countries'][ $item_id ] ) ? (array) wp_unslash( $_POST['menu-item-wpc-countries'][ $item_id ] ) : array();
	$clean  = array_values( array_intersect( array_map( 'sanitize_key', $posted ), array_keys( wpcoupon_get_country_field_options() ) ) );
	if ( $clean ) {
		update_post_meta( $item_id, '_wpc_menu_countries', $clean );
	} else {
		delete_post_meta( $item_id, '_wpc_menu_countries' );
	}
}, 10, 2 );

/** Front end: drop the items that are not for the country being viewed (and their sub-items). */
add_filter( 'wp_nav_menu_objects', function ( $items ) {
	if ( is_admin() ) {
		return $items;
	}
	$current = wpcoupon_get_current_country();
	$hidden  = array();
	foreach ( $items as $i => $item ) {
		$only = get_post_meta( $item->ID, '_wpc_menu_countries', true );
		if ( ( is_array( $only ) && $only && ! in_array( $current, $only, true ) ) || in_array( (int) $item->menu_item_parent, $hidden, true ) ) {
			$hidden[] = (int) $item->ID;
			unset( $items[ $i ] );
		}
	}
	return $items;
}, 5 );
