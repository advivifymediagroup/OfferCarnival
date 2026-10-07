<?php
/**
 * Metabox config file
 *
 * @package WP Coupon/inc/config
 * @license  http://www.opensource.org/licenses/gpl-license.php GPL v2.0 (or later)
 * @link     https://github.com/webdevstudios/Custom-Metaboxes-and-Fields-for-WordPress
 */

/**
 * Get the bootstrap!
 */
if ( file_exists(  get_template_directory() . '/inc/metabox/init.php' ) ) {
	require_once  get_template_directory() . '/inc/metabox/init.php';
    require_once  get_template_directory() . '/inc/metabox-addons/extra-types.php';
    require_once  get_template_directory() . '/inc/metabox-addons/icon/icon.php';
}


function cmb2_change_minutes_step( $l10n ){
    $l10n['defaults']['time_picker']['stepMinute'] = 1;
    return $l10n;
}

add_filter( 'cmb2_localized_data', 'cmb2_change_minutes_step' );


/**
 * Sanitizes WYSIWYG fields like WordPress does for post_content fields.
 */
function cmb2_html_content_sanitize( $content ) {
    return apply_filters( 'content_save_pre', $content );
}



/**
 * Metabox for Show on page IDs callback
 * @author Tom Morton
 * @link https://github.com/WebDevStudios/CMB2/wiki/Adding-your-own-show_on-filters
 *
 * @param bool $display
 * @param array $meta_box
 * @return bool display metabox
 */
function wpcoupon_metabox_show_on_cb( $field ) {
    global $post;

    $meta_box = $field->args;
    if ( ! isset( $meta_box['show_on_page'] ) ) {
        return true ;
    }

    $post_id = $post->ID;

    if ( ! $post_id ) {
        return false;
    }

    // See if there's a match
    return in_array( $post_id, (array) $meta_box['show_on_page'] );
}



add_action( 'cmb2_init', 'wpcoupon_coupon_meta_boxes' );
add_action( 'cmb2_init', 'wpcoupon_page_meta_boxes' );

/**
 * Add metabox for coupon
 * @since 1.0.0
 */
function wpcoupon_coupon_meta_boxes() {
    // Start with an underscore to hide fields from custom fields list
    $prefix = '_wpc_';

    $coupon_meta = new_cmb2_box( array(
        'id'            => $prefix . 'coupon',
        'title'         => esc_html__( 'Coupon Settings', 'wp-coupon-pro' ),
        'object_types'  => array( 'coupon', ), // Post type
        // 'show_on_cb' => 'yourprefix_show_if_front_page', // function should return a bool value
        // 'context'    => 'normal',
        // 'priority'   => 'high',
        // 'show_names' => true, // Show field names on the left
        // 'cmb_styles' => false, // false to disable the CMB stylesheet
        // 'closed'     => true, // true to keep the metabox closed by default
    ) );


    $coupon_meta->add_field( array(
        'name'             => esc_html__( 'Coupon Type', 'wp-coupon-pro' ),
        'id'               => $prefix . 'coupon_type',
        'type'             => 'select',
        'show_option_none' => false,
        'options'          => wpcoupon_get_coupon_types(),
    ) );


    $coupon_meta->add_field( array(
        'name'          => esc_html__( 'Coupon Code', 'wp-coupon-pro' ),
        'id'            => $prefix . 'coupon_type_code',
        'type'          => 'text_medium',
        'attributes'    => array(
            'placeholder'   => esc_html__( 'Example: EMIAXHGF', 'wp-coupon-pro' ),
        ),
        'before_row'    => '<div class="st-condition-field cmb-row" data-show-when = "code" data-show-on="' . $prefix . 'coupon_type' . '">',
        'after_row'     => '</div>'

    ) );

    $coupon_meta->add_field( array(
        'name'          => esc_html__( 'Coupon Printable Image', 'wp-coupon-pro' ),
        'id'            => $prefix . 'coupon_type_printable',
        'type'          => 'file',
        'attributes'    => array(
            'placeholder'   => esc_html__( 'http://...', 'wp-coupon-pro' ),
        ),
        'before_row'    => '<div class="st-condition-field cmb-row" data-show-when = "print" data-show-on="' . $prefix . 'coupon_type' . '">',
        'after_row'     => '</div>'
    ) );

    $coupon_meta->add_field( array(
        'name'          => esc_html__( 'Coupon URL', 'wp-coupon-pro' ),
        'id'            => $prefix . 'destination_url',
        'type'          => 'text_url',
        'desc'          => esc_html__( 'Coupon URL, if this field empty then Store Aff URL will be use.', 'wp-coupon-pro' ),
        'attributes'    => array(
            'placeholder'   => esc_html__( 'http://...', 'wp-coupon-pro' ),
        ),
    ) );

    $coupon_meta->add_field( array(
        'name'       => esc_html__( 'Expires', 'wp-coupon-pro' ),
        'id'         => $prefix . 'expires',
        'type'       => 'text_datetime_timestamp',
        'desc'       => sprintf( __( 'Set expires for coupon. By default expires date based on GMT+0, <a href="%1$s" target="_blank">Click here</a> to making the coupons get expired based on selected timezone.', 'wp-coupon-pro' ), esc_url( admin_url( 'admin.php?page=wpcoupon_options&tab=9' ) ) ),
    ) );

    $coupon_meta->add_field( array(
        'name'       => esc_html__( 'Start Date', 'wp-coupon-pro' ),
        'id'         => $prefix . 'start_on',
        'type'       => 'text_datetime_timestamp',
        'desc'       => sprintf( __( 'Set start date for coupon. By default start date based on GMT+0, <a href="%1$s" target="_blank">Click here</a> making the coupons start date based on selected timezone.', 'wp-coupon-pro' ), esc_url( admin_url( 'admin.php?page=wpcoupon_options&tab=9' ) ) ),
    ) );

    $coupon_meta->add_field( array(
        'name'          => esc_html__( 'Discount Value', 'wp-coupon-pro' ),
        'id'            => $prefix . 'coupon_save',
        'type'          => 'text_medium',
        'attributes'    => array(
            'placeholder'   => esc_html__( 'Example: 15% Off', 'wp-coupon-pro' ),
        ),
        'desc'          => esc_html__( 'This text maybe display as coupon thumbnail.', 'wp-coupon-pro' ),
        'before_row'    => '<div class="st-condition-field cmb-row">',
        'after_row'     => '</div>'
    ) );

    $coupon_meta->add_field( array(
        'name'          => esc_html__( 'Free Shipping Coupon', 'wp-coupon-pro' ),
        'desc'          => esc_html__( 'This coupon is free shipping coupon', 'wp-coupon-pro' ),
        'id'            => $prefix . 'free_shipping',
        'type'          => 'checkbox'
    ) );

    $coupon_meta->add_field( array(
        'name'          => esc_html__( 'Exclusive Coupon', 'wp-coupon-pro' ),
        'desc'          => esc_html__( 'This coupon is exclusive', 'wp-coupon-pro' ),
        'id'            => $prefix . 'exclusive',
        'type'          => 'checkbox'
    ) );


    // Custom tracking
    $coupon_meta->add_field( array(
        'name'          => esc_html__( 'Number Coupon Used', 'wp-coupon-pro' ),
        'desc'          => esc_html__( '', 'wp-coupon-pro' ),
        'id'            => $prefix . 'used',
        'type'          => 'text'
    ) );

    $coupon_meta->add_field( array(
        'name'          => esc_html__( 'Number Views', 'wp-coupon-pro' ),
        'desc'          => esc_html__( '', 'wp-coupon-pro' ),
        'id'            => $prefix . 'views',
        'type'          => 'text'
    ) );

    $coupon_meta->add_field( array(
        'name'          => esc_html__( 'Vote Up', 'wp-coupon-pro' ),
        'desc'          => esc_html__( '', 'wp-coupon-pro' ),
        'id'            => $prefix . 'vote_up',
        'type'          => 'text'
    ) );

    $coupon_meta->add_field( array(
        'name'          => esc_html__( 'Vote Down', 'wp-coupon-pro' ),
        'desc'          => esc_html__( '', 'wp-coupon-pro' ),
        'id'            => $prefix . 'vote_down',
        'type'          => 'text'
    ) );


}

/**
 * Add meta box for pages
 */
function wpcoupon_page_meta_boxes() {
    // Start with an underscore to hide fields from custom fields list
    $prefix = '_wpc_';

    $page_meta = new_cmb2_box( array(
        'id'            => $prefix . 'page',
        'title'         => esc_html__( 'Page Settings', 'wp-coupon-pro' ),
        'object_types'  => array( 'page' ), // Post type
    ) );

    $page_meta->add_field( array(
        'name'             => esc_html__( 'Page layout', 'wp-coupon-pro' ),
        'desc'             => esc_html__( 'Select page layout to display, leave empty to use theme option settings.', 'wp-coupon-pro' ),
        'id'               => $prefix . 'layout',
        'type'             => 'select',
        'show_option_none' => esc_html__( 'Default (Theme options)', 'wp-coupon-pro' ),
        'default'          => '',
        'options'          => array(
            'right-sidebar'   => esc_html__( 'Right sidebar', 'wp-coupon-pro' ),
            'left-sidebar'    => esc_html__( 'Left sidebar', 'wp-coupon-pro' ),
            'no-sidebar'      => esc_html__( 'No sidebar', 'wp-coupon-pro' ),
        ),
    ) );

    $page_meta->add_field( array(
        'name'             => esc_html__( 'Display content in shadow box', 'wp-coupon-pro' ),
        'desc'             => esc_html__( 'Wrapper content by shadow box.', 'wp-coupon-pro' ),
        'id'               => $prefix . 'shadow_box',
        'type'             => 'select',
        'default'          => 'yes',
        'options'          => array(
            'no'    => esc_html__( 'No, display by default', 'wp-coupon-pro' ),
            'yes'   => esc_html__( 'Yes, Display content in a shadow box', 'wp-coupon-pro' ),
        ),
    ) );

    $page_meta->add_field( array(
        'name'             => esc_html__( 'Custom page header', 'wp-coupon-pro' ),
        'desc'             => esc_html__( 'Custom page header.', 'wp-coupon-pro' ),
        'id'               => $prefix . 'show_header',
        'type'             => 'select',
        'show_option_none' => esc_html__( 'Default (Theme options)', 'wp-coupon-pro' ),
        'default'          => 'on',
        'options'          => array(
            'on'    => esc_html__( 'Show page title', 'wp-coupon-pro' ),
            'off'   => esc_html__( 'Hide page title', 'wp-coupon-pro' ),

        ),
    ) );

    $page_meta->add_field( array(
        'name'          => esc_html__( 'Hide breadcrumb', 'wp-coupon-pro' ),
        'id'            => $prefix . 'hide_breadcrumb',
        'desc'          => sprintf( esc_html__( 'Check this if you want to hide breadcrumb. NOTE: you must install plugin %1$s to use Breadcrumb.', 'wp-coupon-pro' ),  '<a target="_blank" href="'.admin_url( 'update.php?action=install-plugin&plugin=breadcrumb-navxt&_wpnonce='.wp_create_nonce() ).'">'.esc_html__( 'Breadcrumb Navxt', 'wp-coupon-pro' ).'</a>' ),
        'type'          => 'checkbox',
        //'default'       => 'on'
    ) );

    $page_meta->add_field( array(
        'name'          => esc_html__( 'Custom page title', 'wp-coupon-pro' ),
        'id'            => $prefix . 'custom_title',
        'desc'          => esc_html__( 'Display page title difference the title above.', 'wp-coupon-pro' ),
        'type'          => 'text_medium',
    ) );

    $page_meta->add_field( array(
        'name'          => esc_html__( 'Hide Header cover', 'wp-coupon-pro' ),
        'id'            => $prefix . 'hide_cover',
        'desc'          => esc_html__( 'Check this if you want to hide header cover', 'wp-coupon-pro' ),
        'type'          => 'checkbox',
        //'default'       => 'on'
    ) );

    $page_meta->add_field( array(
        'name'          => esc_html__( 'Cover background image', 'wp-coupon-pro' ),
        'id'            => $prefix . 'cover_image',
        'type'          => 'file',
    ) );

    $page_meta->add_field( array(
        'name'    => esc_html__( 'Cover background color', 'wp-coupon-pro' ),
        'id'      => $prefix . 'cover_color',
        'type'    => 'colorpicker',
        'default' => '',
    ) );


    if ( wpcoupon_is_wc() ) {
        $shop_id = wc_get_page_id( 'shop' );
        $page_meta->add_field(array(
            'name' => esc_html__('Number products to show', 'wp-coupon-pro'),
            'id' => $prefix . 'shop_number_products',
            'type' => 'text',
            'default' => '',
            'show_on_cb' => 'wpcoupon_metabox_show_on_cb',
            'show_on_page' => $shop_id, // Specific post IDs to display this metabox
        ));

        $page_meta->add_field(array(
            'name' => esc_html__('Number products per row', 'wp-coupon-pro'),
            'id' => $prefix . 'shop_number_products_per_row',
            'type' => 'select',
            'default' => '',
            'show_on_cb' => 'wpcoupon_metabox_show_on_cb',
            'show_on_page' => $shop_id, // Specific post IDs to display this metabox
            'show_option_none' => esc_html__( 'Default', 'wp-coupon-pro' ),
            'options'          => array(
                '2'   => esc_html__( '2 columns', 'wp-coupon-pro' ),
                '3'   => esc_html__( '3 Columns', 'wp-coupon-pro' ),
                '4'   => esc_html__( '4 Columns', 'wp-coupon-pro' ),
                '5'   => esc_html__( '5 Columns', 'wp-coupon-pro' ),
                '6'   => esc_html__( '6 Columns', 'wp-coupon-pro' ),
            ),
        ));


    }



}



add_action( 'cmb2_admin_init', 'wpcoupon_register_coupon_store_taxonomy_metabox' );
/**
 * Hook in and add a metabox to add fields to taxonomy terms
 */
function wpcoupon_register_coupon_store_taxonomy_metabox() {
    $prefix = '_wpc_';

    /**
     * Metabox to add fields to coupon store
     */
    $store_meta = new_cmb2_box( array(
        'id'               => $prefix . 'store_meta',
        'title'            => esc_html__( 'Store Descriptions', 'wp-coupon-pro' ),
        'object_types'     => array( 'term' ), // Tells CMB2 to use term_meta vs post_meta
        'taxonomies'       => array( 'coupon_store' ), // Tells CMB2 which taxonomies should have these fields
        // 'new_term_section' => true, // Will display in the "Add New Category" section
    ) );

	$store_meta->add_field( array(
        'name'          => esc_html__( 'Home URL', 'wp-coupon-pro' ),
        'id'            => $prefix . 'store_url',
        'desc'          => esc_html__( 'Store Website home page URL.', 'wp-coupon-pro' ),
        'type'          => 'text_url',
        'attributes'    => array(
            'placeholder'   => esc_html__( 'http://example.com', 'wp-coupon-pro' ),
        ),
    ) );

    $store_meta->add_field( array(
        'name'          => esc_html__( 'Affiliate URL', 'wp-coupon-pro' ),
        'id'            => $prefix . 'store_aff_url',
        'desc'          => esc_html__( 'Store Affiliate URL.', 'wp-coupon-pro' ),
        'type'          => 'text_url',
        'attributes'    => array(
            'placeholder'   => esc_html__( 'http://example.com', 'wp-coupon-pro' ),
        ),
    ) );


    $store_meta->add_field( array(
        'name'          => esc_html__( 'Auto generate thumbnail', 'wp-coupon-pro' ),
        'desc'          => esc_html__( 'Auto download store home page screenshoot and set it as thumbnail for this store if store url is correct. This function is disable automatically if the thumbnail bellow has data.', 'wp-coupon-pro' ),
        'id'            => $prefix . 'auto_thumbnail',
        'type'          => 'checkbox'
    ) );

    $store_meta->add_field( array(
        'name'    => esc_html__( 'Thumbnail', 'wp-coupon-pro' ),
        'id'      => $prefix . 'store_image',
        'type'    => 'file',
        // Optional:
        'options' => array(
            'url' => false, // Hide the text input for the url
            //'add_upload_file_text' => 'Add File' // Change upload button text. Default: "Add or Upload File"
        ),
    ) );

	$store_meta->add_field( array(
        'name'          => esc_html__( 'Custom store heading', 'wp-coupon-pro' ),
        'id'            => $prefix . 'store_heading',
        'desc'          => esc_html__( 'The title will display in single store, example: Macy\'s Coupon Code and Deals, if empty then store custom heading from theme option will be used. You can use %store_name% for current store name.', 'wp-coupon-pro' ),
        'type'          => 'text_medium',
        'sanitization_cb'    => 'cmb2_html_content_sanitize'
    ) );

    $store_meta->add_field( array(
        'name'          => esc_html__( 'Featured Store', 'wp-coupon-pro' ),
        'desc'          => esc_html__( 'Check this if you want to this store is featured.', 'wp-coupon-pro' ),
        'id'            => $prefix . 'is_featured',
        'type'          => 'checkbox'
    ) );

    $store_meta->add_field( array(
        'name'          => esc_html__( 'Countries', 'wp-coupon-pro' ),
        'desc'          => esc_html__( 'Which countries does this store operate in? Leave all unchecked to keep the current (India-only) behaviour.', 'wp-coupon-pro' ),
        'id'            => $prefix . 'store_countries',
        'type'          => 'multicheck',
        'select_all_button' => false,
        'options_cb'    => 'wpcoupon_get_country_field_options',
    ) );


	$store_meta->add_field( array(
        'name'     => esc_html__( 'Extra Info', 'wp-coupon-pro' ),
        'desc'     => esc_html__( 'This content display after product listing on single store page.', 'wp-coupon-pro' ),
        'id'       => $prefix . 'extra_info',
        'type'     => 'wysiwyg',
        'options' => array(
            'wpautop' => true, // use wpautop?
            'media_buttons' => true, // show insert/upload button(s)
            ///'textarea_name' => $editor_id, // set the textarea name to something different, square brackets [] can be used here
            'textarea_rows' => get_option('default_post_edit_rows', 6), // rows="..."
            'tabindex' => '',
            'editor_css' => '', // intended for extra styles for both visual and HTML editors buttons, needs to include the `<style>` tags, can use "scoped".
            'editor_class' => '', // add extra class(es) to the editor textarea
            'teeny' => false, // output the minimal editor config used in Press This
            'dfw' => false, // replace the default fullscreen with DFW (needs specific css)
            'tinymce' => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
            'quicktags' => true // load Quicktags, can be used to pass settings directly to Quicktags using an array()
        ),
        'on_front' => true,
    ) );


    /**
     * Metabox to add fields to Coupon categories
     */
    $cat_meta = new_cmb2_box( array(
        'id'               => $prefix . 'coupon_category_meta',
        'title'            => esc_html__( 'Category info', 'wp-coupon-pro' ),
        'object_types'     => array( 'term' ), // Tells CMB2 to use term_meta vs post_meta
        'taxonomies'       => array( 'coupon_category' ), // Tells CMB2 which taxonomies should have these fields
        // 'new_term_section' => true, // Will display in the "Add New Category" section
    ) );

    $cat_meta->add_field( array(
        'name'          => esc_html__( 'Icon', 'wp-coupon-pro' ),
        'id'            => $prefix . 'icon',
        'type'          => 'icon',
        'desc'          => 'Category icon',
    ) );


    $cat_meta->add_field( array(
        'name'          => esc_html__( 'Popular category', 'wp-coupon-pro' ),
        'id'            => $prefix . 'cat_popular',
        'type'          => 'checkbox',
        'desc'          => 'Show in the "Popular Categories" block on the Categories page.',
    ) );

    $cat_meta->add_field( array(
        'name'    => esc_html__( 'Image', 'wp-coupon-pro' ),
        'desc'    => 'The image use as thumbnail on single category page',
        'id'      => $prefix . 'cat_image',
        'type'    => 'file',
        // Optional:
        'options' => array(
            'url' => false, // Hide the text input for the url
            //'add_upload_file_text' => 'Add File' // Change upload button text. Default: "Add or Upload File"
        ),
    ) );


    /**
     * Metabox to add fields to Countries (wpc_country terms)
     *
     * Multi-country support, Step A (admin fields only).
     */
    $country_meta = new_cmb2_box( array(
        'id'               => $prefix . 'country_meta',
        'title'            => esc_html__( 'Country Details', 'wp-coupon-pro' ),
        'object_types'     => array( 'term' ),
        'taxonomies'       => array( 'wpc_country' ),
    ) );

    $country_meta->add_field( array(
        'name'    => esc_html__( 'Flag', 'wp-coupon-pro' ),
        'desc'    => esc_html__( 'Small flag icon shown next to the country name in the header switcher.', 'wp-coupon-pro' ),
        'id'      => $prefix . 'flag_image',
        'type'    => 'file',
        'options' => array(
            'url' => false,
        ),
    ) );

    $country_meta->add_field( array(
        'name'    => esc_html__( 'Site Logo for this Country', 'wp-coupon-pro' ),
        'desc'    => esc_html__( 'Optional. Shown in the header instead of the default logo (Theme Options → Site Logo) when a visitor has this country active. Leave empty to keep using the default logo.', 'wp-coupon-pro' ),
        'id'      => $prefix . 'country_logo',
        'type'    => 'file',
        'options' => array(
            'url' => false,
        ),
    ) );

    $country_meta->add_field( array(
        'name'          => esc_html__( 'Currency Symbol', 'wp-coupon-pro' ),
        'desc'          => esc_html__( 'e.g. ₹ for India, AED for United Arab Emirates.', 'wp-coupon-pro' ),
        'id'            => $prefix . 'currency_symbol',
        'type'          => 'text_small',
    ) );

    $country_meta->add_field( array(
        'name' => esc_html__( 'Default Country', 'wp-coupon-pro' ),
        'desc' => esc_html__( 'The site currently falls back to this country wherever a store/coupon has no country set. Only one country should be marked default.', 'wp-coupon-pro' ),
        'id'   => $prefix . 'is_default',
        'type' => 'checkbox',
    ) );

}
