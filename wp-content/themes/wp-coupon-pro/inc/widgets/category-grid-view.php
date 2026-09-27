<?php
/**
 * Add Taxonomy widget.
 */
class WPCoupon_Categories_grid_view extends WP_Widget {
    /**
     * Register widget with WordPress.
     */
    function __construct() {
        parent::__construct(
            'st_categories_grid_view', // Base ID
            esc_html__( 'WPCoupon Categories Grid View', 'wp-coupon-pro' ), // Name
            array(
                'description' => esc_html__( 'Display any taxonomies as categories list', 'wp-coupon-pro' ),
                'classname' => 'widget_wpc_categories_grid_view'
            ), // Args
            array(
                'width' => 430
            )
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {

        $instance =  wp_parse_args( $instance, array(
            'title'         => '',
            'number'        => 8,
            'taxonomy'      => 'coupon_category',
            'orderby'       => 'name',
            'order'         => 'ASC',
            'depth'         => '1',
            'show_count'    => 0,
            'item_per_row'  => 4,
        ) );

      ?>

    <h3 class="widget-title fav-cat-title">
        <?php
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
        }
        ?>
    </h3>
    <div id="" class="fav-category-area">
            <?php
            if ( taxonomy_exists( 'coupon_store' ) ) {
                $args = array(
                    'type'                     => 'post',
                    'child_of'                 => 0,
                    'orderby'                  => 'name',
                    'order'                    => 'ASC',
                    'hide_empty'               => 0,
                    'hierarchical'             => 1,
                    'taxonomy'                 => 'coupon_category',
                    'pad_counts'               => false
                );

                $args =  wp_parse_args( $instance , $args );

                $categories = get_categories( $args );
                $_categories = array();
                $count_cat=1;
                foreach ( $categories as $k => $c ) {
                    $count_cat ++;
                    if ( $c->parent == 0 ) {
                        $_categories[ $c->term_id ] = array();
                        $_categories[ $c->term_id ]['data'] = $c;
                        unset( $categories[ $k ] );
                        if ( ! empty ( $categories ) ) {
                            foreach ( $categories as $ck => $cc) {
                                if ( $cc->parent == $c->term_id ) {
                                    if (!isset($_categories[ $c->term_id ]['child'])) {
                                        $_categories[ $c->term_id ]['child'] = array();
                                    }
                                    $_categories[ $c->term_id ]['child'][] = $cc;
                                    unset( $categories[$ck] );
                                }
                            }
                        }
                    }
                }
                ?>
                <?php
                $grid_temp = '';
                for ($k = 1; $k <= $instance['item_per_row']; $k++ ){
                    $grid_temp .='auto ';
                }
                ?>
                <ul class="cate-parent" style="grid-template-columns:<?php echo $grid_temp;?>;">
                    <?php
                    foreach ( $_categories as $cat_id => $c ) {
                        ?>
                        <li class="cate-item">
                            <a href="<?php echo get_term_link( $c['data'], 'coupon_category' ); ?>" class="category-name category-parent">
                                <?php
                                $image_id = get_term_meta( $cat_id, '_wpc_cat_image_id', true );
                                $thumb = false;
                                if ( $image_id > 0 ) {
                                    $image= wp_get_attachment_image_src( $image_id, 'medium' );
                                    if ( $image ) {
                                        $thumb = '<span class="thumb-img">
                                        <img src="'.esc_attr( $image[0] ).'" alt="' . esc_html( $c["data"]->name ) . ' ">
                                            <div class="cat-title">'.esc_html( $c["data"]->name ).'</div>
                                        </span>';
                                    }
                                }
                                if ( ! $thumb ) {
                                    $thumb = '<span class="thumb-img">
                                        <img src="'. get_template_directory_uri() . '/assets/images/category.png" alt="' . esc_html( $c["data"]->name ) . '">
                                        <div class="cat-title">' . esc_html( $c["data"]->name ) . '</div>
                                    </span>';
                                }
                                
                                echo $thumb;
                                ?>
                            </a>
                        </li>
                        <?php
                    }
                    ?>
                </ul>
            <?php } else { ?>
                <div class="ui warning message">
                    <div class="header">
                        <?php esc_html_e( 'Oops! No categories found', 'wp-coupon-pro' ); ?>
                    </div>
                    <p><?php esc_html_e( 'You must activate wpcoupons plugin to use this template.', 'wp-coupon-pro' ); ?></p>
                </div>
            <?php } ?>
    </div><!-- .fav-category-area -->


    <?php

    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {

        $instance =  wp_parse_args( $instance, array(
            'title'         => 'Categories',
            'number'        => 6,
            'taxonomy'      => 'coupon_category',
            'orderby'       => 'name',
            'order'         => 'ASC',
            'item_per_row'  => 4,
        ) );

        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';

        $taxonomies = get_taxonomies( array(
            'public'   => true,
        ), 'objects' );

        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'wp-coupon-pro' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'orderby' ); ?>"><?php esc_html_e( 'Order by:', 'wp-coupon-pro' ); ?></label>
            <select name="<?php echo $this->get_field_name( 'orderby' ); ?>">
                <?php
                $a = array(
                    'count' => esc_html__( 'Count', 'wp-coupon-pro' ),
                    'name'  => esc_html__( 'Name', 'wp-coupon-pro' ),
                    'rand'  => esc_html__( 'Random', 'wp-coupon-pro' ),
                ) ;
                foreach ( $a as $k => $v ) {
                    echo '<option value="'.$k.'" '.selected( $instance['orderby'], $k, false ).' >'.$v.'</option>';
                } ?>
            </select>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'order' ); ?>"><?php esc_html_e( 'Order:', 'wp-coupon-pro' ); ?></label>
            <select name="<?php echo $this->get_field_name( 'order' ); ?>">
                <?php
                $a = array(
                    'desc' => esc_html__( 'Desc', 'wp-coupon-pro' ),
                    'asc'  => esc_html__( 'Asc', 'wp-coupon-pro' ),
                );
                foreach (  $a as $k => $v ) {
                    echo '<option value="'.$k.'" '.selected( $instance['order'], $k, false ).' >'.$v.'</option>';
                } ?>
            </select>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php esc_html_e( 'Number store to show:', 'wp-coupon-pro' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo esc_attr( $instance['number'] ); ?>">
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'item_per_row' ); ?>"><?php esc_html_e( 'Number item per row:', 'wp-coupon-pro' ); ?></label>
            <select name="<?php echo $this->get_field_name( 'item_per_row' ); ?>">
                <?php for (  $i = 1; $i <=8 ; $i++ ) {
                    echo '<option value="'.$i.'" '.selected( $instance['item_per_row'], $i, false ).' >'.$i.'</option>';
                } ?>
            </select>

        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
        //$instance = array();
        $new_instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        return $new_instance;
        // return $instance;
    }

} // class Popular_Store


function wpcoupon_register_categories_grid_view_widget() {
    register_widget( 'WPCoupon_Categories_grid_view' );
}
add_action( 'widgets_init', 'wpcoupon_register_categories_grid_view_widget' );



class WPCoupon_Walker_Category_grid_view extends Walker_Category{

    /**
     * Start the element output.
     *
     * @see Walker::start_el()
     *
     * @since 2.1.0
     *
     * @param string $output   Passed by reference. Used to append additional content.
     * @param object $category Category data object.
     * @param int    $depth    Depth of category in reference to parents. Default 0.
     * @param array  $args     An array of arguments. @see wp_list_categories()
     * @param int    $id       ID of the current category.
     */
    public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
        /** This filter is documented in wp-includes/category-template.php */
        $cat_name = apply_filters(
            'list_cats',
            esc_attr( $category->name ),
            $category
        );

        // Don't generate an element if the category name is empty.
        if ( ! $cat_name ) {
            return;
        }

        $link = '<a href="' . esc_url( get_term_link( $category ) ) . '" ';
        if ( $args['use_desc_for_title'] && ! empty( $category->description ) ) {
            /**
             * Filter the category description for display.
             *
             * @since 1.2.0
             *
             * @param string $description Category description.
             * @param object $category    Category object.
             */
            $link .= 'title="' . esc_attr( strip_tags( apply_filters( 'category_description', $category->description, $category ) ) ) . '"';
        }

        $link .= '>';

        if ( ! empty( $args['show_count'] ) ) {
            $link .= ' <span class="coupon-count">' . number_format_i18n( $category->count ) . '</span> ';
        }

        $link .= $cat_name . '</a>';

        if ( ! empty( $args['feed_image'] ) || ! empty( $args['feed'] ) ) {
            $link .= ' ';

            if ( empty( $args['feed_image'] ) ) {
                $link .= '(';
            }

            $link .= '<a href="' . esc_url( get_term_feed_link( $category->term_id, $category->taxonomy, $args['feed_type'] ) ) . '"';

            if ( empty( $args['feed'] ) ) {
                $alt = ' alt="' . sprintf(esc_html__( 'Feed for all posts filed under %s', 'wp-coupon-pro' ), $cat_name ) . '"';
            } else {
                $alt = ' alt="' . $args['feed'] . '"';
                $name = $args['feed'];
                $link .= empty( $args['title'] ) ? '' : $args['title'];
            }

            $link .= '>';

            if ( empty( $args['feed_image'] ) ) {
                $link .= $name;
            } else {
                $link .= "<img src='" . $args['feed_image'] . "'$alt" . ' />';
            }
            $link .= '</a>';

            if ( empty( $args['feed_image'] ) ) {
                $link .= ')';
            }
        }



        if ( 'list' == $args['style'] ) {
            $output .= "\t<li";
            $css_classes = array(
                'column',
                'cat-item',
                'cat-item-' . $category->term_id,
            );

            if ( ! empty( $args['current_category'] ) ) {
                $_current_category = get_term( $args['current_category'], $category->taxonomy );
                if ( $category->term_id == $args['current_category'] ) {
                    $css_classes[] = 'current-cat';
                } elseif ( $category->term_id == $_current_category->parent ) {
                    $css_classes[] = 'current-cat-parent';
                }
            }

            /**
             * Filter the list of CSS classes to include with each category in the list.
             *
             * @since 4.2.0
             *
             * @see wp_list_categories()
             *
             * @param array  $css_classes An array of CSS classes to be applied to each list item.
             * @param object $category    Category data object.
             * @param int    $depth       Depth of page, used for padding.
             * @param array  $args        An array of wp_list_categories() arguments.
             */
            $css_classes = implode( ' ', apply_filters( 'category_css_class', $css_classes, $category, $depth, $args ) );

            $output .=  ' class="' . $css_classes . '"';
            $output .= ">$link\n";
        } else {
            $output .= "\t$link<br />\n";
        }
    }

}
