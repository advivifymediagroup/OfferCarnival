<?php
// Get the post author's data
$author_id = get_the_author_meta('ID');
$authordata = get_userdata($author_id);

// Get the selected blog items from theme options
$selected_blog_items = array(
    'author_name'    => true, // Sample default values
    'published_date' => true,
    'comment_number' => true,
);


$theme_options = get_option('st_options');
if ($theme_options && isset($theme_options['selected_blog_items']) && is_array($theme_options['selected_blog_items'])) {
    $selected_blog_items = $theme_options['selected_blog_items'];
}
?>

<div <?php post_class('post-entry shadow-box content-box'); ?>>
    <?php if (has_post_thumbnail()) { ?>
        <div class="shadow-box post-thumbnail">
            <?php the_post_thumbnail('wpcoupon_blog_medium'); ?>
        </div>
    <?php } ?>

    <?php if ($authordata) { ?>
        <div class="post-meta">
            <?php
            echo '<div class="author-avatar">';
            echo get_avatar(get_the_author_meta('email', $authordata->ID), 50);
            echo '</div>';

            echo '<div class="post-meta-data">';

            the_title('<h1 class="post-title"><a href="' . esc_url(get_permalink()) . '">', '</a></h1>');

            echo '<p class="meta-line-2">';
            foreach ($selected_blog_items as $item => $value) {
                switch ($item) {
                    case 'author_name':
                        if ($value && is_object($authordata)) {
                            echo '<span class="author-name">';
                            printf(
                                esc_html__('Posts by %s', 'wp-coupon-pro'),
                                sprintf('<a href="' . esc_url(get_author_posts_url($authordata->ID, $authordata->user_nicename)) . '" title="' . esc_attr(sprintf(esc_html__('Posts by %s', 'wp-coupon-pro'), get_the_author())) . '" rel="author">' . get_the_author() . '</a>')
                            );
                            echo '</span>';
                        }
                        break;
                    case 'published_date':
                        if ($value) {
                            echo '<span class="published-date">' . esc_html(get_the_date()) . '</span>';
                        }
                        break;
                    case 'comment_number':
                        if ($value) {
                            echo '<span class="comment-number">';
                            comments_number(
                                esc_html__('0 Comments', 'wp-coupon-pro'),
                                esc_html__('1 Comment', 'wp-coupon-pro'),
                                esc_html__('% Comments', 'wp-coupon-pro')
                            );
                            echo '</span>';
                        }
                        break;
                    default:
                        // Handle other cases if needed
                        break;
                }
            }
            echo '</p>';
            echo '</div>'; // .post-meta-data
            ?>

        </div>
    <?php } ?>

    <div class="post-content">
        <?php the_excerpt(); ?>
        <a class="read-more tiny ui button btn_primary" href="<?php the_permalink(); ?>"><?php esc_html_e('Read More', 'wp-coupon-pro') ?></a>
    </div>

</div>
