<?php
/**
 * The header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="content">
 *
 * @package WP Coupon
 */

global $st_option;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
	<script rel=preconnect src="https://cdn.jsdelivr.net/ga-lite/latest/ga-lite.min.js" async></script> <script>var galite = galite || {}; galite.UA = 'UA-191557279-1';</script>
	<script src="https://t.contentsquare.net/uxa/c1f96a858838d.js"></script>
    <meta name="partnerboostverifycode" content="32dc01246faccb7f5b3cad5016dd5033" />
	<meta name="lhverifycode" content="32dc01246faccb7f5b3cad5016dd5033" />
	<meta name="mylead-verification" content="49d26be4d7b23b72bacd4db1cb00fa41">
	<meta name="fo-verify" content="05ac7849-95e2-4f95-8c8d-c8283bcedce1" />
	<meta name="convertiser-verification" content="5aeb1639a15fa7e736e51c5a3c6829570de57906" />
	
	<meta name='impact-site-verification' value='be3f651a-e8f4-4a6f-af52-f35ebb3042a5'>
    <?php wp_head(); ?>
	<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-M66H4KX4');</script>
<!-- End Google Tag Manager -->

	<!-- Google Tag Manager Unicus -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-T6BVMR8Q');</script>
<!-- End Google Tag Manager Unicus-->
	<!-- Nowp rocket-->
	<script nowprocket data-noptimize="1" data-cfasync="false" data-wpfc-render="false" seraph-accel-crit="1" data-no-defer="1" data-cmp-ab="2">
  (function () {
      var script = document.createElement("script");
      script.async = 1;
      script.setAttribute("data-cmp-ab","2");
      script.src = 'https://emrldco.com/NTU3OTc2.js?t=557976';
      document.head.appendChild(script);
  })();
</script>
	<!-- End Nowp rocket-->
</head>
<body <?php body_class(); ?>>
    <div id="page" class="hfeed site">
    	<header id="masthead" class="ui page site-header" role="banner">
            <?php do_action('wpcoupon_before_header_top'); ?>
            <div class="primary-header">
                <div class="container">
                    <div class="logo_area" style="<?php 
         
        $logo_width = wpcoupon_get_option('logo_width'); 
        
        
        if ($logo_width ) {
            echo 'width: ' . esc_attr($logo_width) . 'px;';
        } else {
            
            echo 'width: 20%; height: 40px;'; 
        }
    ?>">
    <?php
    // Country-aware logo: identical to wpcoupon_get_option('site_logo', false, 'url')
    // unless the active country has its own logo configured (Coupons > Countries).
    $wpc_header_logo_url = function_exists( 'wpcoupon_get_logo_for_country' )
        ? wpcoupon_get_logo_for_country( wpcoupon_get_current_country() )
        : wpcoupon_get_option( 'site_logo', false, 'url' );
    ?>
    <?php if ( $wpc_header_logo_url != '' ) { ?>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home">
            <img src="<?php echo esc_url( $wpc_header_logo_url ); ?>" alt="<?php echo get_bloginfo( 'name' ) ?>" />
        </a>
    <?php } else { ?>
        <div class="title_area">
            <?php if ( is_home() || is_front_page() ) { ?>
                <h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
            <?php } else {  ?>
                <h2 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h2>
            <?php } ?>
            <p class="site-description"><?php  bloginfo( 'description' ); ?></p>
        </div>
    <?php } ?>
</div>



<div class="nav-right">
                    <?php
                    //get login and coupon submit button option value
                    $is_enable_login_btn = wpcoupon_get_option( 'enable_header_login_button', true );
                    $is_enable_submit_btn = wpcoupon_get_option( 'enable_header_coupon_submit_button', true );

                    $hs_width = '';
                    $hr_display = '';
                    if ($is_enable_login_btn=="0" && $is_enable_submit_btn=="0"){
                        $hs_width = 'width:70%;';
                        $hr_display = 'display:none;';
                    }
                    ?>
                    
					  <nav class="primary-navigation clearfix" role="navigation">
                        <div id="nav-toggle" class="nav-toggle"><i class="bars icon"></i></div>
                        <div id="nav_toggle_close" class="nav-toggle">
                            <i class="close icon"></i>
                        </div>
                        <ul class="st-menu">
                           <?php wp_nav_menu( array('theme_location' => 'primary', 'container' => '', 'items_wrap' => '%3$s' ) ); ?>
                            <?php
                            if ( ! function_exists( 'WP_Users' ) ) {
                                return ;
                            }
                            $is_logged_in = is_user_logged_in();
                            $user =  wp_get_current_user();
                            $link =  WP_Users()->get_profile_link( $user );
                            ?>
                            <li <?php if ( $is_logged_in ) { ?> class="menu-item-has-children sm-enable" <?php } else { ?> class="sm-enable" <?php } ?>>
                                <?php

                                if($is_logged_in){
                                    ?>
                                    <a  data-is-logged="<?php echo is_user_logged_in() ? 'true' : 'false'; ?>"
                                        class="wpu-login-btn" href="<?php echo wp_logout_url(); ?>">
                                        <?php echo esc_html__( 'Sign Out', 'wp-coupon-pro'  );?>
                                    </a>
                                    <?php if ( $is_logged_in ) { ?>
                                        <ul class="sub-menu">
                                            <li><a href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'Dashboard', 'wp-coupon-pro' ); ?></a></li>
                                            <li><a href="<?php echo WP_Users()->get_edit_profile_link( $user ); ?>"><?php esc_html_e( 'Account Settings','wp-coupon-pro' ); ?></a></li>
                                        </ul>
                                    <?php } ?>
                                    <?php
                                }else{
                                    ?>
                                    <a  data-is-logged="<?php echo is_user_logged_in() ? 'true' : 'false'; ?>"
                                        class="wpu-login-btn" href="<?php echo WP_Users()->get_profile_link(); ?>">
                                        <?php echo esc_html__( 'Sign In', 'wp-coupon-pro' );?>
                                    </a>
                                    <?php
                                }
                                ?>
                            </li>
                        </ul>
                    </nav>
					<div class="header_search" style="<?php echo $hs_width; ?>">
                        <form action="<?php echo home_url( '/' ); ?>" method="get" id="header-search">
                            <div class="header-search-input ui search large action left icon input">
                                <input autocomplete="off" class="prompt" name="s" placeholder="<?php esc_attr_e( 'Search stores for coupons, deals ...', 'wp-coupon-pro' ); ?>" type="text">
                                    <i class="search icon"></i>
                                <div class="results"></div>
                            </div>
                            <div class="clear"></div>
                            <?php

                            $store_ids =  wpcoupon_get_option( 'top_search_stores' );

                            if ( ! empty( $store_ids ) ) {
                                $stores = wpcoupon_get_stores( array(  'include'=> $store_ids ) );
                                if ( $stores ) {

                                    $links = array();
                                    foreach ( $stores as $store ){
                                        $links[] = '<a href="'.get_term_link( $store, 'coupon_store' ).'">'.esc_html( $store->name ).'</a>';
                                    }

                                    $links = join( ', ', $links );
                                    if ( $links ) {
                                        ?>
                                        <div class="search-sample">
                                            <?php
                                            printf('<span>'.esc_html__( 'Top Searches:', 'wp-coupon-pro' ).'</span>%1$s,...', $links);
                                            ?>
                                        </div>
                                        <?php
                                    }
                                }
                            }
                            ?>
                        </form>
                    </div>
                    <?php if ( function_exists( 'wpcoupon_country_switcher' ) ) { wpcoupon_country_switcher(); } ?>
                    <div class="header_right" style="<?php echo $hr_display;?>">
                        <div class="nav-user-action fright clearfix">
                            <?php
                            if ( class_exists( 'WPCoupon_User' ) ) {
                                WPCoupon_User::nav();
                            }
                            ?>
                        </div> <!-- END .nav_user_action -->
                    </div>
                </div>
				</div>
            </div> <!-- END .header -->

            <?php do_action('wpcoupon_after_header_top'); ?>

            <div id="site-header-nav" class="site-navigation">
                <div class="container">
                  

                </div> <!-- END .container -->
            </div> <!-- END #primary-navigation -->
    	</header><!-- END #masthead -->
        <div id="content" class="site-content">
<?php
