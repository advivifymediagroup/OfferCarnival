<?php
/**
 * Template Name: Blog Template
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
<div id="content-wrap" class="container container-index <?php echo esc_attr( $layout ); ?>">
    <div id="primary" class="content-area">
        <main id="main" class="site-main" role="main">
            <?php
            $args = array(
                'post_type' => array( 'post')
            );
            $the_query = new WP_Query( $args );

            // The Loop
            if ( $the_query->have_posts() ) {
                while ( $the_query->have_posts() ) {
                    $the_query->the_post();
                    get_template_part( 'loop/loop' );
                }
            } else {
                // no posts found
                get_template_part('content','none');
            }
            /* Restore original Post Data */
            wp_reset_postdata();
            ?>
        </main><!-- #main -->
    </div><!-- #primary -->

    <?php

    if ( $layout != 'no-sidebar' ) {
        get_sidebar();
    }

    ?>

</div> <!-- /#content-wrap -->

<?php get_footer(); ?>
