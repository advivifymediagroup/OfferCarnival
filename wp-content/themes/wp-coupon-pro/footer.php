<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package WP Coupon
 */
global $st_option;

if (wpcoupon_get_option('before_footer', '') != '') {
    if (wpcoupon_get_option('before_footer_apply', 'home') != 'all') {
        if (is_front_page()) {
            echo do_shortcode(wpcoupon_get_option('before_footer', ''));
        }
    } else {
        echo do_shortcode(wpcoupon_get_option('before_footer', ''));
    }
}
?>
</div> <!-- END .site-content -->

<footer id="colophon" class="site-footer <?php echo ($st_option['footer_widgets']) ? 'footer-widgets-on' : 'footer-widgets-off' ?>" role="contentinfo">
    <div class="container">
        <?php if ($st_option['footer_widgets']) { ?>
        <div class="footer-widgets-area">
            <div class="sidebar-footer footer-columns stackable ui grid clearfix">
                <?php if (is_active_sidebar('footer-left')) { ?>
                    <div id="footer-1" class="five wide column footer-column widget-area" role="complementary">
                        <?php dynamic_sidebar('footer-left'); ?>
                    </div>
                <?php } ?>

                <div id="footer-2" class="six wide column footer-column widget-area" role="complementary">
					<?php dynamic_sidebar('footer-center'); ?>
                </div>

                <div id="footer-4" class="five wide column footer-column widget-area" role="complementary">
                    <?php dynamic_sidebar('footer-right'); ?>
                </div>
            </div>
        </div>
        <?php } ?>

        <div class="footer_copy">
            <div class="cpy_text">
                <?php
                echo '<span>';
                if (wpcoupon_get_option('footer_copyright') == '') {
                    printf(esc_html__('Copyright &copy; %1$s %2$s. All Rights Reserved. ', 'wp-coupon-pro'), esc_attr(date('Y')), get_bloginfo('name'));
                } else {
                    echo wp_kses_post(wpcoupon_get_option('footer_copyright'));
                }
                echo '</span>';

                if (wpcoupon_get_option('enable_footer_author')) {
                    echo '<span>' . sprintf(esc_html__('WordPress Coupon Theme by %s', 'wp-coupon-pro'), '<a href="https://couponthemes.net">CouponThemes</a>') . '</span>';
                }
                ?>
            </div>
            <nav id="footer-nav" class="site-footer-nav">
                <?php wp_nav_menu(array('container' => false, 'theme_location' => 'footer', 'fallback_cb' => false)); ?>
            </nav>
        </div>
    </div>
</footer><!-- END #colophon-->

</div><!-- END #page -->

<?php wp_footer(); ?>
</body>
</html>
