<?php
/**
 * Class to handle the shortcode functionality
 */
class Polaris_News_Shortcode {
    public function __construct() {
        add_shortcode('polaris_news_announce', array($this, 'render_grid'));
        add_action('wp_ajax_polaris_load_paginated_news', array($this, 'load_paginated_news'));
        add_action('wp_ajax_nopriv_polaris_load_paginated_news', array($this, 'load_paginated_news'));
    }

    public function render_grid($atts) {
        // Default attributes
        $atts = shortcode_atts(
            array(
                'number'         => -1,      // Query all posts by default
                'posts_per_page' => 6,       // Default to 6 posts per page
                'category'       => '',      // Accept category IDs
            ),
            $atts,
            'polaris_news_announce'
        );

        // Get current page
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

        // Convert category IDs into an array if provided
        $category_ids = array();
        if (!empty($atts['category'])) {
            $category_ids = array_map('intval', explode(',', $atts['category']));
        }

        // Query arguments
        $args = array(
            'post_type'      => 'polaris_news',
            'posts_per_page' => intval($atts['posts_per_page']),
            'paged'          => $paged,
        );

        // Apply category filter if provided
        if (!empty($category_ids)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'pna_category',
                    'field'    => 'term_id',
                    'terms'    => $category_ids,
                ),
            );
        }

        if ($atts['number'] != -1) {
            $args['posts_per_page'] = intval($atts['number']);
        }

        $query = new WP_Query($args);
        $output = '<div class="pna-custom-masonry-grid" id="polaris-posts-container"
                    data-post-type="polaris_news"
                    data-category="' . esc_attr($atts['category']) . '"
                    data-posts-per-page="' . esc_attr($atts['posts_per_page']) . '">';

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $featured_image = get_the_post_thumbnail_url(get_the_ID(), 'large');
                $post_title = get_the_title();
                $external_url = get_post_meta(get_the_ID(), '_external_url', true);
                $permalink = !empty($external_url) ? $external_url : get_the_permalink();
                $target = !empty($external_url) ? ' target="_blank" rel="noopener noreferrer"' : '';

                // Get category name from custom taxonomy
                $categories = get_the_terms(get_the_ID(), 'pna_category');
                $category_label = !empty($categories) ? esc_html($categories[0]->name) : '';

                $output .= '<div class="pna-masonry-item">';

                // Category Ribbon
                if (!empty($category_label)) {
                    $output .= '<div class="pna-category-ribbon">' . $category_label . '</div>';
                }

                if ($featured_image) {
                    $output .= '<a href="' . esc_url($permalink) . '"' . $target . ' class="pna-masonry-image" style="background-image: url(' . esc_url($featured_image) . ');">';
                    $output .= '<div class="pna-masonry-title">' . esc_html($post_title) . '</div>';
                    $output .= '</a>';
                } else {
                    $output .= '<a href="' . esc_url($permalink) . '"' . $target . ' class="pna-masonry-no-image">';
                    $output .= '<div class="pna-masonry-title pna-masonry-title-no-image">' . esc_html($post_title) . '</div>';
                    $output .= '</a>';
                }

                $output .= '</div>'; // End masonry item
            }
        } else {
            $output .= '<p>No posts found.</p>';
        }

        $output .= '</div>'; // End masonry grid

        // Pagination
        if ($query->max_num_pages > 1) {
            $output .= '<div class="pna-pagination">';
            for ($i = 1; $i <= $query->max_num_pages; $i++) {
                $output .= '<span class="pna-page-link' . ($paged === $i ? ' active' : '') . '" data-page="' . $i . '">' . $i . '</span>';
            }
            $output .= '</div>';
        }

        wp_reset_postdata();

        return $output;
    }

    public function load_paginated_news() {
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 6;
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';

        $args = array(
            'post_type'      => 'polaris_news',
            'posts_per_page' => $posts_per_page,
            'paged'          => $page,
        );

        // Add category filter if provided
        if (!empty($category)) {
            $category_ids = array_map('intval', explode(',', $category));
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'pna_category',
                    'field'    => 'term_id',
                    'terms'    => $category_ids,
                ),
            );
        }

        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $featured_image = get_the_post_thumbnail_url(get_the_ID(), 'large');
                $post_title = get_the_title();
                $external_url = get_post_meta(get_the_ID(), '_external_url', true);
                $permalink = !empty($external_url) ? $external_url : get_the_permalink();
                $target = !empty($external_url) ? ' target="_blank" rel="noopener noreferrer"' : '';
                
                // Get category name from custom taxonomy
                $categories = get_the_terms(get_the_ID(), 'pna_category');
                $category_label = !empty($categories) ? esc_html($categories[0]->name) : '';
                ?>
                <div class="pna-masonry-item">
                    <?php if (!empty($category_label)) : ?>
                        <div class="pna-category-ribbon"><?php echo $category_label; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($featured_image) : ?>
                        <a href="<?php echo esc_url($permalink); ?>"<?php echo $target; ?> class="pna-masonry-image" style="background-image: url(<?php echo esc_url($featured_image); ?>);">
                            <div class="pna-masonry-title"><?php echo esc_html($post_title); ?></div>
                        </a>
                    <?php else : ?>
                        <a href="<?php echo esc_url($permalink); ?>"<?php echo $target; ?> class="pna-masonry-no-image">
                            <div class="pna-masonry-title pna-masonry-title-no-image"><?php echo esc_html($post_title); ?></div>
                        </a>
                    <?php endif; ?>
                </div>
                <?php
            }
        }
        wp_reset_postdata();
        die();
    }
}