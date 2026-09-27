<?php

/**
 * Theme Options Config
 */

if ( ! class_exists( 'WPCoupon_Theme_Options_Config' ) ) {

    class WPCoupon_Theme_Options_Config {

        public $args = array();
        public $sections = array();
        public $theme;
        public $ReduxFramework;

        public function __construct() {

            if ( ! class_exists( 'ReduxFramework' ) ) {
                return;
            }
            $this->initSettings();
        }


        public function initSettings() {

            // Set the default arguments
            $this->setArguments();

            // Set a few help tabs so you can see how it's done
            $this->setHelpTabs();

            // Create the sections and fields
            $this->setSections();

            if ( ! isset( $this->args['opt_name'] ) ) { // No errors please
                return;
            }

            $this->args = apply_filters( 'st_redux_theme_options_args', $this->args );

            $this->ReduxFramework = new ReduxFramework( $this->sections, $this->args );
        }

        public function setHelpTabs() {

            // Custom page help tabs, displayed using the help API. Tabs are shown in order of definition.
            $this->args['help_tabs'][] = array(
                'id'      => 'redux-help-tab-1',
                'title'   => esc_html__( 'Theme Information 1', 'wp-coupon-pro' ),
                'content' => esc_html__( '<p>This is the tab content, HTML is allowed.</p>', 'wp-coupon-pro' ),
            );

            $this->args['help_tabs'][] = array(
                'id'      => 'redux-help-tab-2',
                'title'   => esc_html__( 'Theme Information 2', 'wp-coupon-pro' ),
                'content' => esc_html__( '<p>This is the tab content, HTML is allowed.</p>', 'wp-coupon-pro' ),
            );

            // Set the help sidebar
            $this->args['help_sidebar'] = esc_html__( '<p>This is the sidebar content, HTML is allowed.</p>', 'wp-coupon-pro' );
        }

        /**
         * All the possible arguments for Redux.
         * For full documentation on arguments, please refer to: https://github.com/ReduxFramework/ReduxFramework/wiki/Arguments
         * */
        public function setArguments() {

            $theme = wp_get_theme(); // For use with some settings. Not necessary.

            $this->args = array(
                // TYPICAL -> Change these values as you need/desire
                'opt_name'           => 'st_options',
                // This is where your data is stored in the database and also becomes your global variable name.
                'display_name'       => $theme->get( 'Name' ).' Options',
                // Name that appears at the top of your panel
                'display_version'    => false,
                // Version that appears at the top of your panel
                'menu_type'          => 'menu', // submenu , menu
                // Specify if the admin menu should appear or not. Options: menu or submenu (Under appearance only)
                'allow_sub_menu'     => false,
                // Show the sections below the admin menu item or not
                'menu_title'         => esc_html__( 'Theme Options', 'wp-coupon-pro' ),
                'page_title'         => esc_html__( 'Theme Options', 'wp-coupon-pro' ),
                // You will need to generate a Google API key to use this feature.
                // Please visit: https://developers.google.com/fonts/docs/developer_api#Auth
                'google_api_key'     => '',
                // Must be defined to add google fonts to the typography module
                'async_typography'   => false,
                // Use a asynchronous font on the front end or font string
                'admin_bar'          => true,
                // Show the panel pages on the admin bar
                'global_variable'    => 'st_option',
                // Set a different name for your global variable other than the opt_name
                'dev_mode'           => false,
                // Show the time the page took to load, etc
                'customizer'         => true,
                // Enable basic customizer support
                // OPTIONAL -> Give you extra features
                 'page_priority'      => 26,
                // Order where the menu appears in the admin area. If there is any conflict, something will not show. Warning.
                'page_parent'        => 'themes.php', // themes.php
                // For a full list of options, visit: http://codex.wordpress.org/Function_Reference/add_submenu_page#Parameters
                'page_permissions'   => 'manage_options',
                // Permissions needed to access the options panel.
                'menu_icon'          => '',
                // Specify a custom URL to an icon
                'last_tab'           => '',
                // Force your panel to always open to a specific tab (by id)
                'page_icon'          => 'icon-themes',
                // Icon displayed in the admin panel next to your menu_title
                'page_slug'          => 'wpcoupon_options',
                // Page slug used to denote the panel
                'save_defaults'      => true,
                // On load save the defaults to DB before user clicks save or not
                'default_show'       => false,
                // If true, shows the default value next to each field that is not the default value.
                'default_mark'       => '',
                // What to print by the field's title if the value shown is default. Suggested: *
                'show_import_export' => true,
                // Shows the Import/Export panel when not used as a field.
                // CAREFUL -> These options are for advanced use only
                'transient_time'     => 60 * MINUTE_IN_SECONDS,

                'output'             => true,
                // Global shut-off for dynamic CSS output by the framework. Will also disable google fonts output
                'output_tag'         => true,
                // Allows dynamic CSS to be generated for customizer and google fonts, but stops the dynamic CSS from going to the head
                'footer_credit'     => 'Develop by <a href="http://couponthemes.net/">CouponThemes</a>',
                // Disable the footer credit of Redux. Please leave if you can help it.
                // FUTURE -> Not in use yet, but reserved or partially implemented. Use at your own risk.
                'database'           => '',
                // possible: options, theme_mods, theme_mods_expanded, transient. Not fully functional, warning!
                'system_info'        => false,
                // REMOVE
                // HINTS
                'hints'              => array(
                    'icon'          => 'icon-question-sign',
                    'icon_position' => 'right',
                    'icon_color'    => 'lightgray',
                    'icon_size'     => 'normal',
                    'tip_style'     => array(
                        'color'   => 'light',
                        'shadow'  => true,
                        'rounded' => false,
                        'style'   => '',
                    ),
                    'tip_position'  => array(
                        'my' => 'top left',
                        'at' => 'bottom right',
                    ),
                    'tip_effect'    => array(
                        'show' => array(
                            'effect'   => 'slide',
                            'duration' => '500',
                            'event'    => 'mouseover',
                        ),
                        'hide' => array(
                            'effect'   => 'slide',
                            'duration' => '500',
                            'event'    => 'click mouseleave',
                        ),
                    ),
                ),
            );

            // Panel Intro text -> before the form
            if ( ! isset( $this->args['global_variable'] ) || $this->args['global_variable'] !== false ) {
                if ( ! empty( $this->args['global_variable'] ) ) {
                    $v = $this->args['global_variable'];
                } else {
                    $v = str_replace( '-', '_', $this->args['opt_name'] );
                }
                // $this->args['intro_text'] = sprintf( __( '<p>Did you know that Redux sets a global variable for you? To access any of your saved options from within your code you can use your global variable: <strong>$%1$s</strong></p>', 'wp-coupon-pro' ), $v );
            } else {
                // $this->args['intro_text'] = __( '<p>This text is displayed above the options panel. It isn\'t required, but more info is always better! The intro_text field accepts all HTML.</p>', 'wp-coupon-pro' );
            }

            // Add content after the form.
            // $this->args['footer_text'] = __( '<p>This text is displayed below the options panel. It isn\'t required, but more info is always better! The footer_text field accepts all HTML.</p>', 'wp-coupon-pro' );
        }

        public function setSections() {

            /*
            --------------------------------------------------------*/
            /*
             GENERAL SETTINGS
            /*--------------------------------------------------------*/
            $this->sections[] = array(
                    'title'    => esc_html__('General', 'wp-coupon-pro'),
                    'desc'     => '',
                    'icon'     => 'el-icon-cog el-icon-large',
                    'submenu'  => true, // Setting submenu to true means this section will appear in the sidebar
                    'fields'   => array(
                    array(
                        'id'       => 'site_logo',
                        'type'     => 'media',
                        'title'    => esc_html__('Site Logo', 'wp-coupon-pro'),
                        'subtitle' => esc_html__('Upload your logo here. Recommended size: 190x40px.', 'wp-coupon-pro'),
                        'url'      => false,
                        'default'  => array('url' => get_template_directory_uri() . '/assets/images/logo.png'),
                        'compiler' => true,  // True if you want to compile CSS based on this image
                    ),
                    array(
                        'id'       => 'logo_width',
                        'type'     => 'slider',
                        'title'    => esc_html__('Logo Width', 'wp-coupon-pro'),
                        'subtitle' => esc_html__('Enter the width of the logo in pixels.', 'wp-coupon-pro'),
                        'default'  => 190,
                        'min'      => 190,
                        'step'     => 1,
                        'max'      => 500,
                        'validate' => 'numeric',
                    ),
                    array(
                        'id'       => 'site_logo_retina',
                        'type'     => 'media',
                        'title'    => esc_html__('Site Logo Retina', 'wp-coupon-pro'),
                        'subtitle' => esc_html__('Upload at exactly 2x the size of your standard logo (optional). The name should include @2x at the end, e.g., logo@2x.png.', 'wp-coupon-pro'),
                        'url'      => false,
                        'default'  => '',
                    ),
                    array(
                        'id'       => 'layout',
                        'type'     => 'button_set',
                        'title'    => esc_html__('Site Layout', 'wp-coupon-pro'),
                        'desc'     => esc_html__('Select the default site layout.', 'wp-coupon-pro'),
                        'default'  => 'right-sidebar',
                        'options'  => array(
                            'left-sidebar'  => esc_html__('Left Sidebar', 'wp-coupon-pro'),
                            'no-sidebar'    => esc_html__('No Sidebar', 'wp-coupon-pro'),
                            'right-sidebar' => esc_html__('Right Sidebar', 'wp-coupon-pro'),
                        ),
                    ),
                    array(
                        'id'      => 'coupons_listing_page',
                        'type'    => 'select',
                        'data'    => 'pages',
                        'title'   => esc_html__('Coupon Categories Listing Page', 'wp-coupon-pro'),
                        'default' => '',
                    ),
                    array(
                        'id'      => 'stores_listing_page',
                        'type'    => 'select',
                        'data'    => 'pages',
                        'title'   => esc_html__('Stores Listing Page', 'wp-coupon-pro'),
                        'default' => '',
                    ),
                    array(
                        'id'      => 'stores_listing_hide_child',
                        'type'    => 'checkbox',
                        'title'   => esc_html__('Hide Child Stores on Listing Page', 'wp-coupon-pro'),
                        'default' => 0,
                    ),
                    array(
                        'id'      => 'search_only_coupons',
                        'type'    => 'checkbox',
                        'title'   => esc_html__('Show Only Coupons in Search Results', 'wp-coupon-pro'),
                        'default' => 1,
                    ),
                    array(
                        'id'      => 'rewrite_store_slug',
                        'type'    => 'text',
                        'title'   => esc_html__('Custom Store Rewrite Slug', 'wp-coupon-pro'),
                        'subtitle'=> esc_html__('Default: store', 'wp-coupon-pro'),
                        'default' => 'store',
                        'desc'    => esc_html__('Change this option and refresh your permalink structure in Settings -> Permalinks for it to take effect.', 'wp-coupon-pro'),
                    ),
                    array(
                        'id'      => 'rewrite_category_slug',
                        'type'    => 'text',
                        'title'   => esc_html__('Custom Coupon Category Rewrite Slug', 'wp-coupon-pro'),
                        'subtitle'=> esc_html__('Default: coupon-category', 'wp-coupon-pro'),
                        'default' => 'coupon-category',
                        'desc'    => esc_html__('Change this option and refresh your permalink structure in Settings -> Permalinks for it to take effect.', 'wp-coupon-pro'),
                    ),
                    array(
                        'id'      => 'rewrite_tag_slug',
                        'type'    => 'text',
                        'title'   => esc_html__('Custom Coupon Tag Rewrite Slug', 'wp-coupon-pro'),
                        'subtitle'=> esc_html__('Default: coupon-tag', 'wp-coupon-pro'),
                        'default' => 'coupon-tag',
                        'desc'    => esc_html__('Change this option and refresh your permalink structure in Settings -> Permalinks for it to take effect.', 'wp-coupon-pro'),
                    ),
                    array(
                        'id'      => 'disable_feed_links',
                        'type'    => 'checkbox',
                        'title'   => esc_html__('Disable Feed Links', 'wp-coupon-pro'),
                        'subtitle'=> esc_html__('Check this option to disable feed links.', 'wp-coupon-pro'),
                    ),
                ),
            );

            /*
            --------------------------------------------------------*/
            /*
             TYPOGRAPHY
            /*--------------------------------------------------------*/
            $this->sections[] = array(
                'title'      => esc_html__( 'Typography', 'wp-coupon-pro' ),
                'header'     => '',
                'desc'       => '',
                'icon_class' => 'el-icon-large',
                'icon'       => 'el-icon-font',
                'submenu'    => true,
                'fields'     => array(
                    array(
                        'id'             => 'font_body',
                        'type'           => 'typography',
                        'title'          => esc_html__( 'Body', 'wp-coupon-pro' ),
                        'compiler'       => true,
                        'google'         => true,
                        'font-backup'    => false,
                        'font-weight'    => true,
                        'all_styles'     => true,
                        'font-style'     => false,
                        'subsets'        => true,
                        'font-size'      => true,
                        'line-height'    => false,
                        'word-spacing'   => false,
                        'letter-spacing' => false,
                        'color'          => true,
                        'preview'        => true,
                        'output'         => array( 'body, p' ),
                        'units'          => 'px',
                        'subtitle'       => esc_html__( 'Select custom font for your main body text.', 'wp-coupon-pro' ),
                        'default'        => array(
                            'font-family' => 'Open Sans',
                        ),
                    ),
                    array(
                        'id'             => 'font_heading',
                        'type'           => 'typography',
                        'title'          => esc_html__( 'Heading', 'wp-coupon-pro' ),
                        'compiler'       => true,
                        'google'         => true,
                        'font-backup'    => false,
                        'all_styles'     => true,
                        'font-weight'    => false,
                        'font-style'     => false,
                        'subsets'        => true,
                        'font-size'      => false,
                        'line-height'    => false,
                        'word-spacing'   => false,
                        'letter-spacing' => true,
                        'color'          => true,
                        'preview'        => true,
                        'output'         => array( 'h1,h2,h3,h4,h5,h6' ),
                        'units'          => 'px',
                        'subtitle'       => esc_html__( 'Select custom font for heading like h1, h2, h3, ...', 'wp-coupon-pro' ),
                        'default'        => array(),
                    ),
                    array(
                        'id'             => 'primary_menu_typography',
                        'type'           => 'typography',
                        'output'         => array(
                            '.primary-navigation .st-menu > li > a,
                                                    .nav-user-action .st-menu > li > a,
                                                    .nav-user-action .st-menu > li > ul > li > a
                                                    ',
                        ),
                        'title'          => esc_html__( 'Primary Menu Typography', 'wp-coupon-pro' ),
                        'compiler'       => true,
                        'google'         => true,
                        'font-backup'    => false,
                        'text-align'     => false,
                        'text-transform' => true,
                        'font-weight'    => true,
                        'all_styles'     => false,
                        'font-style'     => true,
                        'subsets'        => true,
                        'font-size'      => true,
                        'line-height'    => false,
                        'word-spacing'   => false,
                        'letter-spacing' => true,
                        'color'          => true,
                        'preview'        => true,
                        'units'          => 'px',
                        'subtitle'       => esc_html__( 'Custom typography for primary menu.', 'wp-coupon-pro' ),
                        'default'        => array(),
                    ),
                ),
            );

            /*
            --------------------------------------------------------*/
            /*
             Colors
            /*--------------------------------------------------------*/
            $this->sections[] = array(
                'title'  => esc_html__( 'Colors', 'wp-coupon-pro' ),
                'desc'   => '',
                'icon'   => 'el-icon-idea',
                'submenu' => true,
                'fields' => array(

                    array(
                        'id'       => 'style_primary',
                        'type'     => 'color',
                        'title'    => esc_html__( 'Primary', 'wp-coupon-pro' ),
                        'default'  => '#741fa2',
                        'output'    => array(
                            'background-color' => '
                                #header-search .header-search-submit, 
                                .newsletter-box-wrapper.shadow-box .input .ui.button,
                                .wpu-profile-wrapper .section-heading .button,
                                input[type="reset"], input[type="submit"], input[type="submit"],
                                .site-footer .widget_newsletter .newsletter-box-wrapper.shadow-box .sidebar-social a:hover,
                                .ui.button.btn_primary,
                                .site-footer .newsletter-box-wrapper .input .ui.button,
                                .site-footer .widget_newsletter .newsletter-box-wrapper.shadow-box .sidebar-social a:hover,
								.coupon-filter .ui.menu .item .offer-count,
								.coupon-filter .filter-coupons-buttons .store-filter-button .offer-count,
                                .newsletter-box-wrapper.shadow-box .input .ui.button,
                                .newsletter-box-wrapper.shadow-box .sidebar-social a:hover,
                                .wpu-profile-wrapper .section-heading .button,
                                .ui.btn.btn_primary,
								.ui.button.btn_primary,
								.coupon-filter .filter-coupons-buttons .submit-coupon-button:hover,
								.coupon-filter .filter-coupons-buttons .submit-coupon-button.active,
								.coupon-filter .filter-coupons-buttons .submit-coupon-button.active:hover,
								.coupon-filter .filter-coupons-buttons .submit-coupon-button.current::after,
                                .woocommerce #respond input#submit, .woocommerce a.button, .woocommerce button.button, .woocommerce input.button, .woocommerce button.button.alt,
                                .woocommerce #respond input#submit.alt, .woocommerce a.button.alt, .woocommerce button.button.alt, .woocommerce input.button.alt,
                                .primary-header .container .header_right .button:hover,
                                input[type=reset], input[type=submit], input[type=submit]
                            ',

                            'color' => '
                                    .primary-color,
                                    .primary-bg,
                                    .primary-colored,
                                    .nav-toggle,
                                    a,a:hover,
                                    .ui.breadcrumb a, .ui.breadcrumb a:hover,
                                    .screen-reader-text:hover,
                                    .screen-reader-text:active,
                                    .screen-reader-text:focus,
                                    .st-menu a:hover,
                                    .st-menu li.current-menu-item a,
                                    .st-menu li .current-menu-item a,
                                    .nav-user-action .st-menu .menu-box a,
                                    .nav-user-action .st-menu .menu-box a:hover,
                                    .site-footer .footer-social a:hover,
                                    .popular-stores .store-name a:hover,
                                    .store-listing-item .store-thumb-link .store-name a:hover,
                                    .store-listing-item .latest-coupon .coupon-title a,
                                    .store-listing-item .coupon-save:hover,
                                    .store-listing-item .coupon-saved,
                                    .coupon-modal .coupon-content .user-ratting .ui.button:hover i,
                                    .coupon-modal .coupon-content .show-detail a:hover,
                                    .coupon-modal .coupon-content .show-detail .show-detail-on,
                                    .coupon-modal .coupon-footer ul li a:hover,
                                    .coupon-listing-item .coupon-detail .user-ratting .ui.button:hover i,
                                    .coupon-listing-item .coupon-detail .user-ratting .ui.button.active i,
                                    .coupon-listing-item .coupon-listing-footer ul li a:hover, .coupon-listing-item .coupon-listing-footer ul li a.active,
                                    .coupon-listing-item .coupon-exclusive strong i,
                                    .cate-az a:hover,
                                    .cate-az .cate-parent > a,
                                    .site-footer a:hover,
                                    .site-breadcrumb .ui.breadcrumb a.section,
                                    .single-store-header .add-favorite:hover,
                                    .wpu-profile-wrapper .wpu-form-sidebar li a:hover,
                                    .ui.comments .comment a.author:hover,
                                    .primary-navigation .st-menu>li>a:hover,
                                    .primary-header .container .header_right .button,
                                    .site-footer .footer_copy ul li a,
                                    .cat_coupon_available span b,
                                    .cat_coupon_available p,
                                    .coupon_available span b,
                                    .coupon_available p,
                                    .ui.warning.message .header,
                                    .ui.warning.message .header
                                ',
                            'border-color' => '
                                textarea:focus,
                                input[type="date"]:focus,
                                input[type="datetime"]:focus,
                                input[type="datetime-local"]:focus,
                                input[type="email"]:focus,
                                input[type="month"]:focus,
                                input[type="number"]:focus,
                                input[type="password"]:focus,
                                input[type="search"]:focus,
                                input[type="tel"]:focus,
                                input[type="text"]:focus,
                                input[type="time"]:focus,
                                input[type="url"]:focus,
                                input[type="week"]:focus,
                               .primary-header .container .header_right .button,
                               .store-thumb:hover,
                               .cat-page-with-icon .cate-item,
                               .cat_coupon_available,
                               .coupon_available span,
                               .ui.warning.message,
                               .coupon-modal .coupon-content .modal-code .code-text
                            ',
                            'border-top-color' => '
                                .sf-arrows > li > .sf-with-ul:focus:after,
                                .sf-arrows > li:hover > .sf-with-ul:after,
                                .sf-arrows > .sfHover > .sf-with-ul:after
                            ',
                            'border-left-color' => '
                                .sf-arrows ul li > .sf-with-ul:focus:after,
                                .sf-arrows ul li:hover > .sf-with-ul:after,
                                .sf-arrows ul .sfHover > .sf-with-ul:after,
                                .entry-content blockquote,
                                .coupon-button-type .coupon-code .get-code:after
							',
                            'border-bottom-color' => '
								.coupon-filter .filter-coupons-buttons .submit-coupon-button.current::after
							',
                            'border-right-color' => '
								.coupon-filter .filter-coupons-buttons .submit-coupon-button.current::after,
								.no-copy-cmd .coupon-code .ui.action.input:not([class*="left action"]) > input.code-text,
							',
                        ),
                    ),

                    array(
                        'id'       => 'style_secondary',
                        'type'     => 'color',
                        'title'    => esc_html__( 'Secondary', 'wp-coupon-pro' ),
                        'default'  => '#2185d0',
                        'output'    => array(
                            'background-color' => '
                                .ui.btn,
                                .ui.btn:hover,
                                .ui.btn.btn_secondary,
                                .coupon-button-type .coupon-deal, .coupon-button-type .coupon-print, 
							    .coupon-button-type .coupon-code .get-code,
							    .coupon-filter .filter-coupons-buttons .submit-coupon-button.active.current
                            ',

                            'color' => '
                                .secondary-color, .secondary-bg,
                                .nav-user-action .st-menu .menu-box a:hover,
                                .store-listing-item .latest-coupon .coupon-title a:hover,
                                .ui.breadcrumb a:hover,
                                .site-footer .footer_copy ul li a:hover
                            ',

                            'border-color' => '
                                .store-thumb a:hover,
                                .coupon-modal .coupon-content .modal-code .code-text,
                                .single-store-header .header-thumb .header-store-thumb a:hover,
                                .ui.dimmer .ui.loader:before
                            ',
                            'border-left-color' => '
                                .coupon-button-type .coupon-code .get-code:after 
                            ',
                        ),
                    ),

                    array(
                        'id'       => 'style_c_code',
                        'type'     => 'color',
                        'title'    => esc_html__( 'Coupon code', 'wp-coupon-pro' ),
                        'default'  => '#21ba45',
                        'output'    => array(
                            'background-color' => '
                                .coupon-listing-item .c-type .c-code,
								.coupon-filter .ui.menu .item .code-count,
								.coupon-filter .filter-coupons-buttons .store-filter-button .offer-count.code-count
                            ',
                        ),
                    ),

                    array(
                        'id'       => 'style_c_sale',
                        'type'     => 'color',
                        'title'    => esc_html__( 'Coupon sale', 'wp-coupon-pro' ),
                        'default'  => '#ea4c89',
                        'output'    => array(
                            'background-color' => '
                                .coupon-listing-item .c-type .c-sale,
								.coupon-filter .ui.menu .item .sale-count,
								.coupon-filter .filter-coupons-buttons .store-filter-button .offer-count.sale-count
                            ',
                        ),
                    ),

                    array(
                        'id'       => 'style_c_print',
                        'type'     => 'color',
                        'title'    => esc_html__( 'Coupon print', 'wp-coupon-pro' ),
                        'default'  => '#2d3538',
                        'output'    => array(
                            'background-color' => '
                                .coupon-listing-item .c-type .c-print,
								.coupon-filter .ui.menu .item .print-count,
								.coupon-filter .filter-coupons-buttons .store-filter-button .offer-count.print-count
                            ',
                        ),
                    ),

                    array(
                        'id'       => 'style_body_bg',
                        'type'     => 'background',
                        'title'    => esc_html__( 'Body background', 'wp-coupon-pro' ),
                        'default'  => array(
                            'background-color' => '#f8f9f9',
                        ),
                        'output' => array( 'body' ),
                    ),
                ),
            );

            /*
            --------------------------------------------------------*/
            /*
             HEADER
            /*--------------------------------------------------------*/

            $this->sections[] = array(
                'title'  => esc_html__( 'Header', 'wp-coupon-pro' ),
                'desc'   => '',
                'icon'   => 'el-icon-file',
                'submenu' => true,
                'fields' => array(

                    array(
                        'id'       => 'header_sticky',
                        'url'      => false,
                        'type'     => 'checkbox',
                        'title'    => esc_html__( 'Enable Header Sticky', 'wp-coupon-pro' ),
                        'default'  => '',
                        'subtitle' => esc_html__( 'This function apply for desktop only.', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'       => 'enable_header_login_button',
                        'type'     => 'switch',
                        'title'    => esc_html__( 'Header Login button', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Enable the login/registration button in header area.', 'wp-coupon-pro' ),
                        'default'  => true,
                    ),

                    array(
                        'id'       => 'enable_header_coupon_submit_button',
                        'type'     => 'switch',
                        'title'    => esc_html__( 'Header Coupon Submit button', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Enable the coupon submit button in header area.', 'wp-coupon-pro' ),
                        'default'  => true,
                    ),
                    array(
                        'id'       => 'header_submit_button_text',
                        'url'      => false,
                        'type'     => 'text',
                        'title'    => esc_html__( 'Header Submit Button Text', 'wp-coupon-pro' ),
                        'subtitle'    => esc_html__( 'Enter the submit page link in this area.', 'wp-coupon-pro' ),
                        'required' => array( 'enable_header_coupon_submit_button', '=', true ),
                        'default'  => 'Submit a Coupon',
                    ),
                    array(
                        'id'       => 'header_submit_button_slug',
                        'url'      => true,
                        'type'     => 'text',
                        'title'    => esc_html__( 'Header Submit Button Link or Slug', 'wp-coupon-pro' ),
                        'subtitle'    => esc_html__( 'Enter the coupon Submit page link in this area. Default: /submit-coupon/', 'wp-coupon-pro' ),
                        'required' => array( 'enable_header_coupon_submit_button', '=', true ),
                        'default'  => '/submit-coupon/',
                        'desc'     => sprintf( esc_html__( 'Before change slug please make sure that this slug or link available. You can use full link. example: %s ', 'wp-coupon-pro' ), '<i>https://your_site.com/submit-coupon/</i>' ),
                    ),

                    array(
                        'id'       => 'header_custom_color',
                        'type'     => 'switch',
                        'title'    => esc_html__( 'Custom your header style?', 'wp-coupon-pro' ),
                        'default'  => false,
                    ),
                    array(
                        'id'       => 'header_bg',
                        'type'     => 'background',
                        // 'compiler' => true,
                        'output'   => array( '.primary-header' ),
                        'title'    => esc_html__( 'Header Background', 'wp-coupon-pro' ),
                        'required' => array( 'header_custom_color', '=', true ),
                        'default'  => array(),
                    ),

                    array(
                        'id'       => 'header_color',
                        'type'     => 'color',
                        'title'    => esc_html__( 'Color', 'wp-coupon-pro' ),
                        'output'   => array( '.header-highlight a .highlight-icon', '.header-highlight a .highlight-text', '.primary-header', '.primary-header a', '#header-search .search-sample a' ),
                        'required' => array( 'header_custom_color', '=', true ),
                    ),

                ),
            );


            /*
            --------------------------------------------------------*/
            /*
             STORE
            /*--------------------------------------------------------*/
            $this->sections[] = array(
                'title'  => esc_html__( 'Single Store', 'wp-coupon-pro' ),
                'desc'   => '',
                'icon'   => 'el-icon-shopping-cart',
                'submenu' => true,
                'fields' => array(

                    array(
                        'id'      => 'store_loop_tpl',
                        'title'   => esc_html__( 'Coupon Store template', 'wp-coupon-pro' ),
                        'desc'    => esc_html__( 'Select template for store coupons.', 'wp-coupon-pro' ),
                        'type'    => 'select',
                        'default' => 'full',
                        'options' => array(
                            'full'  => esc_html__( 'Full', 'wp-coupon-pro' ),
                            'cat'   => esc_html__( 'Less', 'wp-coupon-pro' ),
                        ),
                    ),

                    array(
                        'id'       => 'coupon_store_show_thumb',
                        'type'     => 'select',
                        'default' => 'default',
                        'title'    => esc_html__( 'Show coupon item thumbnails', 'wp-coupon-pro' ),
                        'options' => array(
                            'default'                   => esc_html__( 'Default, Show if has thumbnail else store thumbnail instead.', 'wp-coupon-pro' ),
                            'hide_if_no_thumb'          => esc_html__( 'Show if has thumbnail', 'wp-coupon-pro' ),
                            'save_value'                => esc_html__( 'Show discount value as coupon thumbnail', 'wp-coupon-pro' ),
                            'hide'                      => esc_html__( 'Hide All', 'wp-coupon-pro' ),
                        ),
                    ),

                    array(
                        'id'      => 'store_layout',
                        'title'   => esc_html__( 'Single Store Layout', 'wp-coupon-pro' ),
                        'desc'    => esc_html__( 'Default single store layout.', 'wp-coupon-pro' ),
                        'type'    => 'button_set',
                        'default' => 'left-sidebar',
                        // 'required' => array('footer_widgets','=',true, ),
                        'options' => array(
                            'left-sidebar'   => esc_html__( 'Left sidebar', 'wp-coupon-pro' ),
                            'right-sidebar'  => esc_html__( 'Right sidebar', 'wp-coupon-pro' ),
                        ),
                    ),

                    array(
                        'id'       => 'store_img_top_sm_enable',
                        'type'     => 'switch',
                        'title'    => esc_html__( 'Store image enable option in top heading area for small device', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Turn off to hide store image option in top heading area for small device', 'wp-coupon-pro' ),
                        'default'  => true,
                    ),

                    array(
                        'id'       => 'store_fav_enable',
                        'type'     => 'switch',
                        'title'    => esc_html__( 'Store favorite enable option', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Turn off to hide favorite this store option from single store sidebar', 'wp-coupon-pro' ),
                        'default'  => true,
                    ),

                    array(
                        'id'       => 'store_coupon_available_enable',
                        'type'     => 'switch',
                        'title'    => esc_html__( 'Store coupon available enable option', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Turn off to hide coupon available counter', 'wp-coupon-pro' ),
                        'default'  => true,
                    ),

                    array(
                        'id'       => 'store_socialshare',
                        'type'     => 'switch',
                        'title'    => esc_html__( 'Store social sharing', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Enable social share under store title', 'wp-coupon-pro' ),
                        'default'  => true,
                    ),
                    array(
                        'id'       => 'store_heading',
                        'type'     => 'textarea',
                        'default' => '<strong>%store_name%</strong> Coupons & Promo Codes',
                        'title'    => esc_html__( 'Store custom heading', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Custom heading text for display on single store page. Use %store_name% to replace with current store name.', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'       => 'coupon_des_sm_enable_store',
                        'type'     => 'checkbox',
                        'default'  => 0,
                        'title'    => esc_html__( 'Coupon Description In Small Device', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'To enable coupon description you need to check in this option', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'       => 'coupon_impression_enable',
                        'type'     => 'checkbox',
                        'default'  => 0,
                        'title'    => esc_html__( 'Coupon Impression In Store Single Page', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'To enable coupon impression you need to check in this option', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'       => 'coupon_button_sec',
                        'type'     => 'checkbox',
                        'default'  => 0,
                        'title'    => esc_html__( 'Coupon Get Button Enable For Small Device', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'To enable coupon get button into small device you need to check in this option', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'       => 'store_unpopular_coupon',
                        'type'     => 'text',
                        'default' => 'Unpopular %store_name% Coupons',
                        'title'    => esc_html__( 'Store unpopular coupon text', 'wp-coupon-pro' ),
                    ),
                    array(
                        'id'       => 'store_expired_coupon',
                        'type'     => 'text',
                        'default' => 'Recently Expired %store_name% Coupons',
                        'title'    => esc_html__( 'Store expired coupon text.', 'wp-coupon-pro' ),
                    ),
                    array(
                        'id'       => 'store_number_active',
                        'type'     => 'text',
                        'default' => '15',
                        'title'    => esc_html__( 'Number coupons to show', 'wp-coupon-pro' ),
                    ),
                    array(
                        'id'       => 'store_loop_order',
                        'type'     => 'select',
                        'default' => 'desc',
                        'title'    => esc_html__( 'Coupon loop order in single store page', 'wp-coupon-pro' ),
                        'options' => array(
                            'desc'          => esc_html__( 'DESC', 'wp-coupon-pro' ),
                            'asc'           => esc_html__( 'ASC', 'wp-coupon-pro' ),
                        ),
                    ),
                    array(
                        'id'       => 'go_store_slug',
                        'type'     => 'text',
                        'default' => 'go-store',
                        'title'    => esc_html__( 'Custom goto store slug', 'wp-coupon-pro' ),
                        'desc'    => sprintf( esc_html__( 'When you enable this option maybe the permalinks will effect, to resolve this go to %1$s and hit "Save Changes" button.', 'wp-coupon-pro' ), '<a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">' . esc_html__( 'Permalinks Settings', 'wp-coupon-pro' ) . '</a>' ),
                    ),

                    array(
                        'id'       => 'store_enable_sidebar_filter',
                        'type'     => 'switch',
                        'title'    => esc_html__( 'Enable store filter', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Enable the filter in sidebar of store page.', 'wp-coupon-pro' ),
                        'default'  => true,
                    ),
                    array(
                        'id'       => 'store_sidebar_filter_title',
                        'type'     => 'text',
                        'title'    => esc_html__( 'Store filter title', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Set title for the store filter in sidebar.', 'wp-coupon-pro' ),
                        'default'  => 'Filter Store',
                        'required' => array('store_enable_sidebar_filter','=',1)
                    ),

                    array(
                        'id'       => 'store_enable_sidebar_cat_filter',
                        'type'     => 'switch',
                        'title'    => esc_html__( 'Enable catgerory checkbox  filter', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Enable the category checkbox filter in sidebar of store page.', 'wp-coupon-pro' ),
                        'default'  => false,
                        'required' => array('store_enable_sidebar_filter','=',1)
                    ),

                    array(
                        'id'       => 'store_enable_submit_button',
                        'type'     => 'switch',
                        'title'    => esc_html__( 'Enable Coupon Submit button for Store Page', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Enable the coupon submit button in store page.', 'wp-coupon-pro' ),
                        'default'  => true,
                    ),

                ),
            );

            /*
            --------------------------------------------------------*/
            /*
             COUPON CATEGORY
            /*--------------------------------------------------------*/
            $this->sections[] = array(
                'title'  => esc_html__( 'Coupon Category', 'wp-coupon-pro' ),
                'desc'   => '',
                'icon'   => 'el-icon-tags',
                'submenu' => true,
                'fields' => array(

                    array(
                        'id'      => 'coupon_cate_tpl',
                        'title'   => esc_html__( 'Coupon Category template', 'wp-coupon-pro' ),
                        'desc'    => esc_html__( 'Select template for coupon category.', 'wp-coupon-pro' ),
                        'type'    => 'select',
                        'default' => 'cat',
                        'options' => array(
                            'cat'   => esc_html__( 'Less', 'wp-coupon-pro' ),
                            'full'  => esc_html__( 'Full', 'wp-coupon-pro' ),
                        ),
                    ),

                    array(
                        'id'       => 'coupon_cate_show_thumb',
                        'type'     => 'select',
                        'default'  => 'default',
                        'title'    => esc_html__( 'Show coupon item thumbnails', 'wp-coupon-pro' ),
                        'options'  => array(
                            'default'                   => esc_html__( 'Default, Show if has thumbnail else store thumbnail instead.', 'wp-coupon-pro' ),
                            'hide_if_no_thumb'          => esc_html__( 'Show if has thumbnail', 'wp-coupon-pro' ),
                            'save_value'                => esc_html__( 'Show discount value as coupon thumbnail', 'wp-coupon-pro' ),
                            'hide'                      => esc_html__( 'Hide All', 'wp-coupon-pro' ),
                        ),
                    ),

                    array(
                        'id'      => 'coupon_cate_layout',
                        'title'   => esc_html__( 'Coupon Category Layout', 'wp-coupon-pro' ),
                        'desc'    => esc_html__( 'Default coupon category layout.', 'wp-coupon-pro' ),
                        'type'    => 'button_set',
                        'default' => 'left-sidebar',
                        'options' => array(
                            'left-sidebar'   => esc_html__( 'Left sidebar', 'wp-coupon-pro' ),
                            'right-sidebar'  => esc_html__( 'Right sidebar', 'wp-coupon-pro' ),
                        ),
                    ),
                    array(
                        'id'       => 'coupon_cate_socialshare',
                        'type'     => 'switch',
                        'title'    => esc_html__( 'Coupon category social sharing', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Enable social share under coupon category title', 'wp-coupon-pro' ),
                        'default'  => true,
                    ),

                    array(
                        'id'       => 'cat_coupon_available_enable',
                        'type'     => 'switch',
                        'title'    => esc_html__( 'Category coupon available enable option', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Off this option to hide coupon available counter from single category page', 'wp-coupon-pro' ),
                        'default'  => true,
                    ),

                    array(
                        'id'       => 'coupon_cate_heading',
                        'type'     => 'textarea',
                        'default'  => '<strong>%coupon_cate%</strong> Coupons & Promo Codes',
                        'title'    => esc_html__( 'Coupon category custom heading', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Custom heading text for display on coupon category page. You can use %coupon_cate% to display category name.', 'wp-coupon-pro' ),
                    ),
                    array(
                        'id'       => 'coupon_cate_subheading',
                        'type'     => 'text',
                        'default'  => esc_html__( 'Newest %coupon_cate% Coupons', 'wp-coupon-pro' ),
                        'title'    => esc_html__( 'Coupon category sub-heading', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'You can use %coupon_cate% to display category name.', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'       => 'coupon_des_sm_enable',
                        'type'     => 'checkbox',
                        'default'  => 1,
                        'title'    => esc_html__( 'Coupon Description In Small Device', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'To enable coupon description you need to check in this option', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'       => 'coupon_cate_number',
                        'type'     => 'text',
                        'default'  => '15',
                        'title'    => esc_html__( 'How many coupons display by default?', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'       => 'cat_loop_order',
                        'type'     => 'select',
                        'default' => 'desc',
                        'title'    => esc_html__( 'Coupon loop order in single category page', 'wp-coupon-pro' ),
                        'options' => array(
                            'desc'          => esc_html__( 'DESC', 'wp-coupon-pro' ),
                            'asc'           => esc_html__( 'ASC', 'wp-coupon-pro' ),
                        ),
                    ),

                    array(
                        'id'      => 'coupon_cate_paging',
                        'title'   => esc_html__( 'Coupon listing paging', 'wp-coupon-pro' ),
                        'type'    => 'button_set',
                        'default' => 'ajax_loadmore',
                        'options' => array(
                            'paging_navigation' => esc_html__( 'Paging Navigation', 'wp-coupon-pro' ),
                            'ajax_loadmore'     => esc_html__( 'Load more with ajax', 'wp-coupon-pro' ),
                        ),
                    ),

                    array(
                        'id'       => 'coupon_cate_ads',
                        'title'    => esc_html__( 'Category advertisement', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Display custom ads after coupons listing on single category.', 'wp-coupon-pro' ),
                        'type'     => 'textarea',
                        'default'  => '',
                    ),
                    array(
                        'id'       => 'coupon_cate_sidebar_filter',
                        'type'     => 'switch',
                        'title'    => esc_html__( 'Enable category filter', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Enable the filter in sidebar of category page.', 'wp-coupon-pro' ),
                        'default'  => true,
                    ),
                    array(
                        'id'       => 'coupon_cate_filter_title',
                        'type'     => 'text',
                        'title'    => esc_html__( 'Category filter title', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Set title for the category filter in sidebar.', 'wp-coupon-pro' ),
                        'default'  => 'Filter',
                        'required' => array( 'coupon_cate_sidebar_filter', '=', 1 ),
                    ),
                ),
            );

            /*
            --------------------------------------------------------*/
            /*
             COUPON ITEM
            /*--------------------------------------------------------*/
            $this->sections[] = array(
                'title'  => esc_html__( 'Coupons', 'wp-coupon-pro' ),
                'desc'   => '',
                'icon'   => 'el-icon-tag',
                'submenu' => true,
                'fields' => array(

                    array(
                        'id'       => 'enable_single_coupon',
                        'type'     => 'checkbox',
                        'default'  => false,
                        'title'    => esc_html__( 'Enable single page for coupon.', 'wp-coupon-pro' ),
                        'desc'     => sprintf( esc_html__( 'When you enable this option maybe the permalinks will effect, to resolve this go to %1$s and hit "Save Changes" button.', 'wp-coupon-pro' ), '<a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">' . esc_html__( 'Permalinks Settings', 'wp-coupon-pro' ) . '</a>' ),
                    ),

                    array(
                        'id'           => 'coupon_filter_tabs',
                        'type'         => 'sorter',
                        'title'        => esc_html__( 'Coupon Filter', 'wp-coupon-pro' ),
                        'subtitle'     => esc_html__( 'Custom your coupon filter tabs.', 'wp-coupon-pro' ),
                        'options'      => array(
                            'enabled'  => array_merge( array( 'all' => __( wpcoupon_get_option( 'filter_item_all_lebel' ), 'wp-coupon-pro' ) ), wpcoupon_get_coupon_types( true ) ),
                            'disabled' => array(),
                        ),
                    ),

                    array(
                        'id'       => 'filter_item_all_lebel',
                        'type'     => 'text',
                        'title'    => esc_html__( 'Filter Item All Lebel ', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Enter the lebel of the filter item which name All.', 'wp-coupon-pro' ),
                        'default'  => 'All',
                    ),

                    array(
                        'id'       => 'filter_item_codes_lebel',
                        'type'     => 'text',
                        'title'    => esc_html__( 'Filter Item Codes Lebel ', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Enter the lebel of the filter item which name codes.', 'wp-coupon-pro' ),
                        'default'  => 'Codes',
                    ),

                    array(
                        'id'       => 'filter_item_sales_lebel',
                        'type'     => 'text',
                        'title'    => esc_html__( 'Filter Item Sales Lebel ', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Enter the lebel of the filter item which name sales.', 'wp-coupon-pro' ),
                        'default'  => 'Sales',
                    ),

                    array(
                        'id'       => 'filter_item_printable_lebel',
                        'type'     => 'text',
                        'title'    => esc_html__( 'Filter Item Printable Lebel ', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Enter the lebel of the filter item which name printable.', 'wp-coupon-pro' ),
                        'default'  => 'Printable',
                    ),

                    array(
                        'id'       => 'auto_open_coupon_modal',
                        'type'     => 'checkbox',
                        'default'  => false,
                        'title'    => esc_html__( 'Auto open coupon modal on single coupon.', 'wp-coupon-pro' ),
                        'required' => array( 'enable_single_coupon', 'equals', '1' ),
                    ),

                    array(
                        'id'       => 'enable_single_popular',
                        'type'     => 'checkbox',
                        'default'  => true,
                        'title'    => esc_html__( 'Enable popular coupons on single page.', 'wp-coupon-pro' ),
                        'required' => array( 'enable_single_coupon', 'equals', '1' ),
                    ),

                    array(
                        'id'       => 'single_popular_text',
                        'type'     => 'text',
                        'default'  => esc_html__( 'Most popular {store} coupons.', 'wp-coupon-pro' ),
                        'title'    => esc_html__( 'Custom popular text.', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Use {store} to display store name.', 'wp-coupon-pro' ),
                        'required' => array( 'enable_single_popular', 'equals', '1' ),
                    ),

                    array(
                        'id'       => 'single_popular_number',
                        'type'     => 'text',
                        'default'  => 3,
                        'title'    => esc_html__( 'Number popular coupons on single page.', 'wp-coupon-pro' ),
                        'required' => array( 'enable_single_popular', 'equals', '1' ),
                    ),

                    array(
                        'id'       => 'coupon_item_logo',
                        'type'     => 'select',
                        'default'  => 'default',
                        'title'    => esc_html__( 'Show coupon item thumbnails', 'wp-coupon-pro' ),
                        'options'  => array(
                            'default'                   => esc_html__( 'Default, Show if has thumbnail else store thumbnail instead.', 'wp-coupon-pro' ),
                            'hide_if_no_thumb'          => esc_html__( 'Show if has thumbnail', 'wp-coupon-pro' ),
                            'save_value'                => esc_html__( 'Show discount value as coupon thumbnail', 'wp-coupon-pro' ),
                            'hide'                      => esc_html__( 'Hide All', 'wp-coupon-pro' ),
                        ),
                    ),

                    array(
                        'id'       => 'get_code_btn_txt',
                        'type'     => 'text',
                        'title'    => esc_html__( 'Get code button text', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Enter the text of get code button text in coupon item area.', 'wp-coupon-pro' ),
                        'default'  => 'Get Code',
                    ),

                    array(
                        'id'       => 'get_deal_btn_txt',
                        'type'     => 'text',
                        'title'    => esc_html__( 'Get deal button text', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Enter the text of get deal button text in coupon item area.', 'wp-coupon-pro' ),
                        'default'  => 'Get Deal',
                    ),

                    array(
                        'id'       => 'print_coupon_btn_txt',
                        'type'     => 'text',
                        'title'    => esc_html__( 'Print coupon button text', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Enter the text of print coupon button text in coupon item area.', 'wp-coupon-pro' ),
                        'default'  => 'Print Coupon',
                    ),

                    array(
                        'id'       => 'coupon_more_desc',
                        'type'     => 'checkbox',
                        'default'  => 1,
                        'title'    => esc_html__( 'Show coupon read more description.', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'       => 'coupon_share',
                        'type'     => 'switch',
                        'title'    => esc_html__( 'Coupon Share  In Store Single Page', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'To enable store coupon share option you need to On this section and if you want disable it select Off', 'wp-coupon-pro' ),
                        'default'  => true,
                    ),

                    array(
                        'id'       => 'coupon_comment',
                        'type'     => 'switch',
                        'title'    => esc_html__( 'Coupon Comment In Store Single Page', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'To enable store coupon comment option you need to On this section and if you want disable it select Off', 'wp-coupon-pro' ),
                        'default'  => true,
                    ),

                    array(
                        'id'      => 'coupon_time_zone_local',
                        'type'    => 'checkbox',
                        'default' => false,
                        'title'   => esc_html__( 'Use Local Timezone', 'wp-coupon-pro' ),
                        'desc'    => esc_html__( 'Making the coupons get expired based on selected timezone.', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'       => 'coupon_human_time',
                        'type'     => 'checkbox',
                        'default' => 0,
                        'title'    => esc_html__( 'Coupon human time diff', 'wp-coupon-pro' ),
                        'desc'    => esc_html__( 'Show human time diff such as 3 days left, 2 days left,...', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'       => 'coupon_item_exclusive',
                        'type'     => 'text',
                        'default'  => '<strong><i class="protect icon"></i>Exclusive:</strong> This coupon can only be found at our website.',
                        'title'    => esc_html__( 'Exclusive Coupon Message', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'      => 'coupon_expires_action',
                        'title'   => esc_html__( 'When coupon expires', 'wp-coupon-pro' ),
                        'type'    => 'select',
                        'default' => 'do_nothing',
                        'options' => array(
                            'do_nothing' => esc_html__( 'Do Nothing', 'wp-coupon-pro' ),
                            'set_status' => esc_html__( 'Disable', 'wp-coupon-pro' ),
                            'remove'     => esc_html__( 'Remove', 'wp-coupon-pro' ),
                        ),
                    ),

                    array(
                        'id'      => 'coupon_expires_time',
                        'title'   => esc_html__( 'Run expires action time', 'wp-coupon-pro' ),
                        'desc'    => esc_html__( 'Run action after coupon expires x seconds., default: 604800 (1 week)', 'wp-coupon-pro' ),
                        'type'    => 'text',
                        'default' => 604800, // 1 week
                    ),

                    array(
                        'id'       => 'print_prev_tab',
                        'type'     => 'checkbox',
                        // 'required' => array( 'enable_single_coupon','!=','1'),
                        'default' => false,
                        'title'    => esc_html__( 'Open store website in new tab when click on print button.', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'       => 'sale_prev_tab',
                        'type'     => 'checkbox',
                        // 'required' => array( 'enable_single_coupon','!=','1'),
                        'default' => true,
                        'title'    => esc_html__( 'Open store website in new tab when click on "Get Deal" button.', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'       => 'code_prev_tab',
                        'type'     => 'checkbox',
                        // 'required' => array( 'enable_single_coupon','!=','1'),
                        'default' => true,
                        'title'    => esc_html__( 'Open store website in new tab when click on "Get Code" button.', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'       => 'coupon_click_action',
                        'type'     => 'button_set',
                        'default' => 'prev',
                        'options' => array(
                            'prev' => __( 'Previous Tab', 'wp-coupon-pro' ),
                            'next' => __( 'Next Tab', 'wp-coupon-pro' ),
                            'same_tab' => __( 'Same Tab', 'wp-coupon-pro' ),
                        ),
                        'title'    => esc_html__( 'Action when open store website.', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'      => 'coupon_num_words_excerpt',
                        'title'   => esc_html__( 'Default coupon excerpt length', 'wp-coupon-pro' ),
                        'type'    => 'text',
                        'default' => 10,
                    ),

                    array(
                        'id'       => 'go_out_slug',
                        'type'     => 'text',
                        'default' => 'out',
                        'title'    => esc_html__( 'Custom coupon go out slug', 'wp-coupon-pro' ),
                        'desc'    => sprintf( esc_html__( 'When you enable this option maybe the permalinks will effect, to resolve this go to %1$s and hit "Save Changes" button.', 'wp-coupon-pro' ), '<a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">' . esc_html__( 'Permalinks Settings', 'wp-coupon-pro' ) . '</a>' ),
                    ),

                    array(
                        'id'       => 'use_deal_txt',
                        'type'     => 'checkbox',
                        'default'  => 0,
                        'title'    => esc_html__( 'Use "Deal" text instead of "Sale"', 'wp-coupon-pro' ),
                    ),

                ),
            );

            /*
            --------------------------------------------------------*/
            /*
             PAGE
            /*--------------------------------------------------------*/
            $this->sections[] = array(
                'title'  => esc_html__( 'Page', 'wp-coupon-pro' ),
                'desc'   => '',
                'icon'   => 'el-icon-file',
                'submenu' => true,
                'fields' => array(

                    array(
                        'id'      => 'page_header',
                        'title'   => esc_html__( 'Page header', 'wp-coupon-pro' ),
                        'type'    => 'button_set',
                        'default' => 'on',
                        // 'required' => array('footer_widgets','=',true, ),
                        'options' => array(
                            'on'    => esc_html__( 'Show page header', 'wp-coupon-pro' ),
                            'off'   => esc_html__( 'Hide page header ', 'wp-coupon-pro' ),
                        ),
                    ),

                    array(
                        'id'      => 'page_header_breadcrumb',
                        'type'    => 'switch',
                        'title'   => esc_html__( 'Show header breadcrumb', 'wp-coupon-pro' ),
                        'required' => array( 'page_header', '=', array( 'on' ) ),
                        'default'  => true,
                        'desc'  => esc_html__( 'Check this if you want to show breadcrumb. NOTE: you must install plugin Breadcrumb Navxt to use this function.', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'      => 'page_header_cover',
                        'type'    => 'switch',
                        'title'   => esc_html__( 'Show cover image', 'wp-coupon-pro' ),
                        'required' => array( 'page_header', '=', array( 'on' ) ),
                        // 'subtitle' => esc_html__('Look, it\'s on!', 'redux-framework-demo'),
                        'default'  => false,
                    ),

                    array(
                        'id'      => 'page_header_cover_img',
                        'type'    => 'media',
                        'title'   => esc_html__( 'Header cover image', 'wp-coupon-pro' ),
                        'required' => array( 'page_header_cover', '=', array( true ) ),
                         'subtitle' => esc_html__('Please upload cover image, that show in page cover.', 'redux-framework-demo'),
                        'default'  => '',
                    ),

                ),
            );

            /*
            --------------------------------------------------------*/
            /*
             BLOG
            /*--------------------------------------------------------*/
            $this->sections[] = array(
                'title'  => esc_html__( 'Blog', 'wp-coupon-pro' ),
                'desc'   => '',
                'icon'   => 'el-icon-pencil',
                'submenu' => true,
                'fields' => array(

                    array(
                        'id'      => 'blog_header',
                        'title'   => esc_html__( 'Page header', 'wp-coupon-pro' ),
                        'type'    => 'button_set',
                        'default' => 'on',
                        'options' => array(
                            'on'    => esc_html__( 'Show page header', 'wp-coupon-pro' ),
                            'off'   => esc_html__( 'Hide page header ', 'wp-coupon-pro' ),
                        ),
                    ),

                    array(
                        'id'      => 'blog_header_title',
                        'type'    => 'text',
                        'title'   => esc_html__( 'Custom blog title', 'wp-coupon-pro' ),
                        'required' => array( 'blog_header', '=', array( 'on' ) ),
                        'default'  => '',
                    ),

                    array(
                        'id'      => 'blog_header_breadcrumb',
                        'type'    => 'switch',
                        'title'   => esc_html__( 'Show header breadcrumb', 'wp-coupon-pro' ),
                        'required' => array( 'blog_header', '=', array( 'on' ) ),
                        'default'  => true,
                        'desc'  => esc_html__( 'Check this if you want to show breadcrumb. NOTE: you must install plugin Breadcrumb Navxt to use this function.', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'      => 'blog_header_cover',
                        'type'    => 'switch',
                        'title'   => esc_html__( 'Show cover image', 'wp-coupon-pro' ),
                        'required' => array( 'blog_header', '=', array( 'on' ) ),
                        // 'subtitle' => esc_html__('Look, it\'s on!', 'redux-framework-demo'),
                        'default'  => false,
                    ),

                    array(
                        'id'      => 'blog_header_cover_img',
                        'type'    => 'media',
                        'title'   => esc_html__( 'Header cover image', 'wp-coupon-pro' ),
                        'required' => array( 'blog_header_cover', '=', array( true ) ),
                        'default'  => '',
                    ),
                    array(
                        'id'      => 'selected_blog_items',
                        'type'    => 'checkbox',
                        'title'   => esc_html__( 'Selected Author Meta', 'wp-coupon-pro' ),
                        'desc'    => esc_html__( 'Choose the items you want to display on blog posts.', 'coupon-hub' ),
                        
                        'options' => array(
                            'author_name'     => esc_html__( 'Author Name', 'wp-coupon-pro' ),
                            'published_date'  => esc_html__( 'Published Date', 'wp-coupon-pro' ),
                            'comment_number'  => esc_html__( 'Comment Number', 'wp-coupon-pro' ),
                        ),

                        'default' => array(
                            'author_name' => '1', 
                            'published_date' => '1', 
                            'comment_number' => '1'
                        )
                    ),
                ),
            );

            /*
            --------------------------------------------------------*/
            /*
             FOOTER
            /*--------------------------------------------------------*/
            $this->sections[] = array(
                'title'  => esc_html__( 'Footer', 'wp-coupon-pro' ),
                'desc'   => '',
                'icon'   => 'el-icon-photo',
                'submenu' => true,
                'fields' => array(

                    array(
                        'id'       => 'before_footer',
                        'type'     => 'editor',
                        'title'    => esc_html__( 'Before footer', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Note: This field only display on homepage', 'wp-coupon-pro' ),
                        'default'  => '',
                    ),

                    array(
                        'id'      => 'before_footer_apply',
                        'type'    => 'radio'
                    ,
                        'title'   => esc_html__( 'Before Footer Display', 'wp-coupon-pro' ),
                        'desc'    => esc_html__( 'Note: Setting home page goto Settings -> Reading -> Front page displays -> Check static page -> Select a page', 'wp-coupon-pro' ),
                        'default' => 'home',
                        'required' => array( 'footer_widgets', '=', true ),
                        'options' => array(
                            'home'   => esc_html__( 'Apply for home page only.', 'wp-coupon-pro' ),
                            'all'   => esc_html__( 'Apply for all pages.', 'wp-coupon-pro' ),
                        ),
                    ),

                    array(
                        'id'       => 'footer_widgets',
                        'type'     => 'switch',
                        'title'    => esc_html__( 'Enable footer widgets area.', 'wp-coupon-pro' ),
                        'default'  => true,
                    ),

                    array(
                        'id'       => 'footer_latest_stores_title',
                        'type'     => 'text',
                        'title'    => esc_html__( 'Footer Store Title.', 'wp-coupon-pro' ),
                        'default'  => 'Recommended Stores',
                    ),

                    array(
                        'id'       => 'footer_latest_stores_number',
                        'type'     => 'text',
                        'title'    => esc_html__( 'Number of Stores show in Footer.', 'wp-coupon-pro' ),
                        'default'  => '12',
                    ),
                    
                    array(
                        'id'       => 'footer_copyright',
                        'type'     => 'textarea',
                        'title'    => esc_html__( 'Footer Copyright', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Enter the copyright section text.', 'wp-coupon-pro' ),
                    ),

                    array(
                        'id'       => 'enable_footer_author',
                        'type'     => 'switch',
                        'title'    => esc_html__( 'Enable theme author links.', 'wp-coupon-pro' ),
                        'default'  => true,
                    ),

                    array(
                        'id'       => 'footer_custom_color',
                        'type'     => 'switch',
                        'title'    => esc_html__( 'Custom your footer style?', 'wp-coupon-pro' ),
                        'default'  => false,
                    ),
                    array(
                        'id'       => 'footer_bg',
                        'type'     => 'background',
                        // 'compiler' => true,
                        'output'   => array( '.site-footer ' ),
                        'title'    => esc_html__( 'Footer Background', 'wp-coupon-pro' ),
                        'required' => array( 'footer_custom_color', '=', true ),
                        'default'  => array(
                            'background-color' => '#222222',
                        ),
                    ),
                    array(
                        'id'       => 'footer_text_color',
                        'type'     => 'color',
                        'compiler' => true,
                        'output'   => array( '.site-footer, .site-footer .widget, .site-footer p' ),
                        'title'    => esc_html__( 'Footer Text Color', 'wp-coupon-pro' ),
                        'default'  => '#777777',
                        'required' => array( 'footer_custom_color', '=', true ),
                    ),
                    array(
                        'id'       => 'footer_link_color',
                        'type'     => 'color',
                        'compiler' => true,
                        'output'   => array( '.site-footer a, .site-footer .widget a' ),
                        'title'    => esc_html__( 'Footer Link Color', 'wp-coupon-pro' ),
                        'default'  => '#CCCCCC',
                        'required' => array( 'footer_custom_color', '=', true ),
                    ),
                    array(
                        'id'       => 'footer_link_color_hover',
                        'type'     => 'color',
                        'compiler' => true,
                        'output'   => array( '.site-footer a:hover, .site-footer .widget a:hover' ),
                        'title'    => esc_html__( 'Footer Link Color Hover', 'wp-coupon-pro' ),
                        'default'  => '#ffffff',
                        'required' => array( 'footer_custom_color', '=', true ),
                    ),
                    array(
                        'id'       => 'footer_widget_title_color',
                        'type'     => 'color',
                        'compiler' => true,
                        'output'   => array( '.site-footer .footer-columns .footer-column .widget .widget-title, .site-footer #wp-calendar caption' ),
                        'title'    => esc_html__( 'Footer Widget Title Color', 'wp-coupon-pro' ),
                        'default'  => '#777777',
                        'required' => array( 'footer_custom_color', '=', true ),
                    ),
                    array(
                        'id'       => 'footer_widget_title_color',
                        'type'     => 'color',
                        'compiler' => true,
                        'output'   => array( '.site-footer .footer-columns .footer-column .widget .widget-title, .site-footer #wp-calendar caption' ),
                        'title'    => esc_html__( 'Footer Widget Title Color', 'wp-coupon-pro' ),
                        'default'  => '#777777',
                        'required' => array( 'footer_custom_color', '=', true ),
                    ),
                ),
            );

            /*
            --------------------------------------------------------*/
            /*
             EMAIL Templates
            /*--------------------------------------------------------*/
            $this->sections[] = array(
                'title'  => esc_html__( 'Email Templates', 'wp-coupon-pro' ),
                'desc'   => '',
                'icon'   => 'el-icon-envelope',
                'submenu' => true,
                'fields' => array(

                    array(
                        'id'       => 'email_share_coupon_title',
                        'type'     => 'text',
                        'title'    => esc_html__( 'Share coupon code email title', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Available Tags: {coupon_title}, {coupon_description}, {coupon_destination_url}, {coupon_print_image_url}, {coupon_code}, {store_name}, {store_go_out_url}, {store_url}, {store_aff_url}, {home_url}, {share_email}', 'wp-coupon-pro' ),
                        'default'  => '{coupon_title}',
                    ),

                    array(
                        'id'       => 'email_share_coupon_code',
                        'type'     => 'editor',
                        'title'    => esc_html__( 'Share coupon code email template', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Available Tags: {coupon_title}, {coupon_description}, {coupon_destination_url}, {coupon_print_image}, {coupon_print_image_url}, {coupon_code}, {store_name}, {store_image}, {store_go_out_url}, {store_url}, {store_aff_url}, {home_url}, {share_email}', 'wp-coupon-pro' ),
                        'default'  => wpcoupon_get_share_email_template( 'code' ),
                    ),

                    array(
                        'id'       => 'email_share_coupon_sale',
                        'type'     => 'editor',
                        'title'    => esc_html__( 'Share coupon sale email template', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Available Tags: {coupon_title}, {coupon_description}, {coupon_destination_url}, {coupon_print_image}, {coupon_print_image_url}, {coupon_code}, {store_name}, {store_image}, {store_go_out_url}, {store_url}, {store_aff_url}, {home_url}, {share_email}', 'wp-coupon-pro' ),
                        'default'  => wpcoupon_get_share_email_template( 'sale' ),
                    ),

                    array(
                        'id'       => 'email_share_coupon_print',
                        'type'     => 'editor',
                        'title'    => esc_html__( 'Share coupon print email template', 'wp-coupon-pro' ),
                        'subtitle' => esc_html__( 'Available Tags: {coupon_title}, {coupon_description}, {coupon_destination_url}, {coupon_print_image}, {coupon_print_image_url}, {coupon_code}, {store_name}, {store_image}, {store_go_out_url}, {store_url}, {store_aff_url}, {home_url}, {share_email}', 'wp-coupon-pro' ),
                        'default'  => wpcoupon_get_share_email_template( 'print' ),
                    ),
                ),
            );

            $this->sections = apply_filters( 'wpcoupon_more_options_settings', $this->sections );

        }

    }

    global $reduxConfig;
    function wpcoupon_options_init() {
        global $reduxConfig;
        // force remove sample redux demo option
        delete_option( 'ReduxFrameworkPlugin' );
        $reduxConfig = new WPCoupon_Theme_Options_Config();
    }
    add_action( 'init', 'wpcoupon_options_init' );
}


/**
 * Removes the demo link and the notice of integrated demo from the redux-framework plugin
 */
if ( ! function_exists( 'wp_coupon_remove_demo' ) ) {
    function wp_coupon_remove_demo() {
        // Used to hide the demo mode link from the plugin page. Only used when Redux is a plugin.
        if ( class_exists( 'ReduxFrameworkPlugin' ) ) {
            remove_filter(
                'plugin_row_meta',
                array(
                    ReduxFrameworkPlugin::instance(),
                    'plugin_metalinks',
                ),
                null,
                2
            );

            // Used to hide the activation notice informing users of the demo panel. Only used when Redux is a plugin.
            remove_action( 'admin_notices', array( ReduxFrameworkPlugin::instance(), 'admin_notices' ) );
        }
    }
}
wp_coupon_remove_demo();



/*
 * Load Redux extensions
 */
function wpcoupon_register_redux_extensions( $ReduxFramework ) {
    $path    = get_template_directory() . '/inc/redux-extensions/';

    $folders = scandir( $path, 1 );

    foreach ( $folders as $folder ) {
        if ( $folder === '.' or $folder === '..' or ! is_dir( $path . $folder ) ) {
            continue;
        }
        $extension_class = 'ReduxFramework_extension_' . $folder;
        if ( ! class_exists( $extension_class ) ) {
            // In case you wanted override your override, hah.
            $class_file = $path . $folder . '/extension_' . $folder . '.php';

            if ( is_file( $class_file ) ) {
                require_once $class_file;
            }
        }

        if ( ! isset( $ReduxFramework->extensions[ $folder ] ) ) {
            $ReduxFramework->extensions[ $folder ] = new $extension_class( $ReduxFramework );
        }
    }
}
add_action( 'redux/extensions/before', 'wpcoupon_register_redux_extensions' );