<div id="secondary" class="widget-area sidebar store-sidebar" role="complementary">
    <div class="header-thumb">
        <div class="header-store-thumb">
            <a rel="nofollow" target="_blank" title="<?php esc_html_e( 'Shop ', 'wp-coupon-pro' );
            echo wpcoupon_store()->get_display_name(); ?>" href="<?php echo wpcoupon_store()->get_go_store_url(); ?>">
                <?php
                echo wpcoupon_store()->get_thumbnail();
                ?>
            </a>
        </div>
        <?php $store_fav_enable = wpcoupon_get_option( 'store_fav_enable' ); ?>
        <?php if ($store_fav_enable==true) {?>
         <a class="add-favorite" data-id="<?php echo wpcoupon_store()->term_id; ?>" href="#"><i class="outline heart icon"></i><span><?php esc_html_e( 'Favorite This Store', 'wp-coupon-pro' ); ?></span></a>
        <?php }?>
        <div class="rating">
            <?php if(function_exists("bp_star_ratings")) {
                echo bp_star_ratings();
                if(get_option('bpsr_show_in_archives')=='1'){
                    update_option('bpsr_show_in_archives','0');
                }
            } ?>
        </div>
    </div>

    <?php $store_coupon_available_enable = wpcoupon_get_option( 'store_coupon_available_enable' ); ?>
    <?php if ($store_coupon_available_enable==true) {?>

    <?php
    $count = wpcoupon_store()->count_coupon();
    $total = array_sum( $count );
    ?>
    <div class="coupon_available">
        <span>
            <b> <?php esc_html_e($total>0?$total:'0', 'wp-coupon-pro' )?> </b>
        </span>
        <p><?php esc_html_e( 'COUPONS AVAILABLE', 'wp-coupon-pro' ); ?></p>
    </div>

    <?php }?>

    <!--  Store Description  -->
    <h2 class="store-about-title"><?php esc_html_e( 'About', 'wp-coupon-pro' ); ?> <?php echo wpcoupon_store()->get_display_name();?></h2>
    <div class="inner">
        <div class="inner-content clearfix">
            <div class="header-content">
                <?php
                  wpcoupon_store()->get_content( true, true );
                ?>
            </div>
        </div>
    </div>
	<?php
		/**
		 * Hook: wpcoupon_coupon_store_before_sidebar
		 * @since 1.2.6
		 * hooked wpcoupon_store_cat_filter - 10
		 */
		do_action( 'wpcoupon_coupon_store_before_sidebar' );

	?>
    <?php dynamic_sidebar( 'sidebar-store' ); ?>
	<?php
		/**
		 * Hook: wpcoupon_coupon_store_after_sidebar
		 * @since 1.2.6
		 */
		do_action( 'wpcoupon_coupon_store_after_sidebar' );


	?>
</div><!-- #secondary -->