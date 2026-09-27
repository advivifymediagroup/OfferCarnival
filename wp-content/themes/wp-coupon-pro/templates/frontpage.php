<?php
/**
 * Template Name: Front Page
 *
 * @package wp-coupon-pro
 * @since 1.0.
 */

get_header();

/**
 * Hooks wpcoupon_after_header
 *
 * @see wpcoupon_page_header();
 *
 */
do_action( 'wpcoupon_after_header' );
$layout = wpcoupon_get_site_layout();
if ( ! is_active_sidebar( 'frontpage-sidebar' ) ) {
    $layout = 'no-sidebar';
}
?>
<div id="content-wrap" class="frontpage-container container <?php echo esc_attr( $layout ); ?>">
    <?php
    // Non-default countries (e.g. UAE) get their own version of each of these
    // three sidebars — see wpcoupon_get_country_sidebar_id() — so content
    // curated with specific India post/term IDs (Popular Stores, Daily
    // Deals) can be replaced per country instead of always showing India's.
    // For the default country every one of these resolves to the original
    // id, unchanged.
    $wpc_before_main_sidebar = wpcoupon_get_country_sidebar_id( 'frontpage-before-main' );
    if ( is_active_sidebar( $wpc_before_main_sidebar ) ){
        echo '<div class="content-widgets frontpage-before-main">';
        dynamic_sidebar( $wpc_before_main_sidebar );
        echo '</div>';
    }

    $wpc_main_sidebar = wpcoupon_get_country_sidebar_id( 'frontpage-main' );
    if ( is_active_sidebar( $wpc_main_sidebar ) ) {
        ?>
        <div id="primary" class="content-area">
            <main id="main" class="site-main content-widgets" role="main">
                <?php
                dynamic_sidebar( $wpc_main_sidebar );
                ?>
            </main>
            <!-- #main -->
        </div><!-- #primary -->
        <?php
    }
    ?>

    <?php
    $wpc_after_main_sidebar = wpcoupon_get_country_sidebar_id( 'frontpage-after-main' );
    if ( is_active_sidebar( $wpc_after_main_sidebar ) ){
        echo '<div class="content-widgets frontpage-after-main">';
        dynamic_sidebar( $wpc_after_main_sidebar );
        echo '</div>';
    }
    ?>

</div> <!-- /#content-wrap -->

<?php get_footer(); ?>
