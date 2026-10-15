<?php
/**
 * Template Name: Coupon Categories Listing
 *
 * Display Coupon Categories
 *
 * @package ST-Coupon
 * @since 1.0.
 */

get_header();
the_post();

/**
 * Hooks wpcoupon_after_header
 *
 * @see wpcoupon_page_header();
 *
 */
do_action( 'wpcoupon_after_header' );

?>
    <div id="content-wrap" class="container no-sidebar">
        <div id="primary" class="content-area">
            <main id="main" class="site-main cat-page-with-icon" role="main">
                <?php
                // Page content (editable in wp-admin) drives this page; until it has been
                // set up, show the two grids by default instead of a blank page.
                if ( '' === trim( get_the_content() ) ) {
                    echo do_shortcode( '[wpc_categories view="popular"][wpc_categories view="all"]' );
                } else {
                    the_content();
                }
                ?>

            </main><!-- #main -->
        </div><!-- #primary -->
        
    </div> <!-- /#content-wrap -->

<?php get_footer(); ?>
