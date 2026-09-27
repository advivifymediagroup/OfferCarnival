<?php
/**
 * Template Name: Full Width
 *
 * Display Coupon Categories
 *
 * @package WP Coupon
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
?>
<div id="content-wrap" class="container container-page <?php echo esc_attr( $layout ); ?> no-sidebar">
    <div id="primary" class="content-area">
        <main id="main" class="site-main" role="main">
            <?php
            get_template_part('content');

            wpcoupon_wp_link_pages( );

            // If comments are open or we have at least one comment, load up the comment template.
            if ( comments_open() || get_comments_number() ) :
                comments_template();
            endif;
            ?>
        </main><!-- #main -->
    </div><!-- #primary -->

</div> <!-- /#content-wrap -->

<?php get_footer(); ?>
