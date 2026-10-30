<?php
/**
 * Simple Coupons Widget - Display specific coupons by ID
 */
class WPCoupon_Coupons_Widget extends WP_Widget {

    function __construct() {
        parent::__construct(
            'st_coupons',
            esc_html__('WPCoupon Coupons', 'wp-coupon-pro'),
            array(
                'description' => esc_html__('Display specific coupons by ID (max 12)', 'wp-coupon-pro'),
                'classname' => 'st-coupons'
            )
        );
    }

    /**
     * Front-end display
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];

        // Get and sanitize title
        $title = !empty($instance['title']) ? apply_filters('widget_title', $instance['title']) : '';
        if ($title) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }

        // Get and process coupon IDs
        $post_ids = array();
        if (!empty($instance['coupon_ids'])) {
            $ids = array_map('trim', explode(',', $instance['coupon_ids']));
            $post_ids = array_filter(array_map('absint', $ids));
            $post_ids = array_slice(array_unique($post_ids), 0, 12);

        }

         // Only query if we have valid IDs

        if (!empty($post_ids)) {
            $query_args = array(
                'post_type' => 'coupon',
                'post__in' => $post_ids,
                'orderby' => 'post__in',
                'posts_per_page' => count($post_ids),
                'post_status' => 'publish',
                'ignore_sticky_posts' => true,
                'no_found_rows' => true
            );

            // Hide expired coupons if your theme supports it
            if (function_exists('wpcoupon_hide_expired_coupons_filter')) {
                $query_args['hide_expired'] = true;
            }
            $coupons_query = new WP_Query($query_args);
            if ($coupons_query->have_posts()) {
                echo '<div class="store-listings st-list-coupons">';
                // Store original post object
                global $post;
                $original_post = $post;
                while ($coupons_query->have_posts()) {
                    $coupons_query->the_post();
                    // Use your theme's coupon template
                    if (locate_template('coupon.php')) {
						 // Properly set up coupon data for your theme
                        if (function_exists('wpcoupon_setup_coupon')) {
                            wpcoupon_setup_coupon($post, get_permalink());
                        }                       
                        // Load the template with correct path
                        get_template_part('loop/loop-coupon', 'coupon');
                } else {
                        // Fallback display
                        echo '<div class="coupon-item">';
                        echo '<h3>' . get_the_title() . '</h3>';
                        echo '<div class="coupon-content">' . get_the_excerpt() . '</div>';
                        echo '</div>';
                    }
                }
                 // Restore original post data
                $post = $original_post;
                if (function_exists('wp_reset_postdata')) {
                    wp_reset_postdata();
                }
                echo '</div>';
            } else {
                echo '<div class="no-coupons">';
                esc_html_e('No valid coupons found.', 'wp-coupon-pro');
                echo '</div>';
            }
        } else {
            echo '<div class="no-coupons-message">';
            esc_html_e('No coupons found. Please check the coupon IDs.', 'wp-coupon-pro');
            echo '</div>';
        }

        echo $args['after_widget'];
    }

    /**
     * Back-end form
     */
    public function form($instance) {
        $instance = wp_parse_args($instance, array(
            'title' => '',
            'coupon_ids' => ''
        ));
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'wp-coupon-pro'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($instance['title']); ?>">
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('coupon_ids')); ?>">
                <?php esc_html_e('Coupon IDs (comma separated, max 12):', 'wp-coupon-pro'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('coupon_ids')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('coupon_ids')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($instance['coupon_ids']); ?>"
                    placeholder="e.g., 123, 456, 789">
            	<small><?php esc_html_e('Enter the numeric coupon post IDs separated by commas', 'wp-coupon-pro'); ?></small>
        </p>
        <?php
    }

    /**
     * Sanitize widget form values
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['coupon_ids'] = sanitize_text_field($new_instance['coupon_ids']);
        return $instance;
    }
}

// Register the widget
function wpcoupon_register_coupons_widget() {
    register_widget( 'WPCoupon_Coupons_Widget' );
}
add_action( 'widgets_init', 'wpcoupon_register_coupons_widget' );