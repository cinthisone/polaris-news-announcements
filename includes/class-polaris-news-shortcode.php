<?php
/**
 * Class to handle the shortcode functionality
 */
class Polaris_News_Shortcode {
    private static $instance_count = 0;

    public function __construct() {
        add_shortcode('polaris_news_announce', array($this, 'render_grid'));
        add_action('wp_ajax_polaris_load_paginated_news', array($this, 'load_paginated_news'));
        add_action('wp_ajax_nopriv_polaris_load_paginated_news', array($this, 'load_paginated_news'));
    }

    public function render_grid($atts) {
        // Increment instance counter
        self::$instance_count++;
        $instance_id = self::$instance_count;

        // Default attributes
        $atts = shortcode_atts(
            array(
                'number'         => -1,      // Query all posts by default
                'posts_per_page' => 6,       // Default to 6 posts per page
                'category'       => '',      // Accept category IDs
                'exclude'        => '',      // Exclude category IDs
                'arrows'         => 'false'  // Use arrows instead of numbers for pagination
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

        // Convert exclude category IDs into an array if provided
        $exclude_ids = array();
        if (!empty($atts['exclude'])) {
            $exclude_ids = array_map('intval', explode(',', $atts['exclude']));
        }

        // Query arguments
        $args = array(
            'post_type'      => 'polaris_news',
            'posts_per_page' => intval($atts['posts_per_page']),
            'paged'          => $paged,
        );

        // Build tax query
        $tax_query = array();

        // Include categories if specified
        if (!empty($category_ids)) {
            $tax_query[] = array(
                'taxonomy' => 'pna_category',
                'field'    => 'term_id',
                'terms'    => $category_ids,
            );
        }

        // Exclude categories if specified
        if (!empty($exclude_ids)) {
            $tax_query[] = array(
                'taxonomy' => 'pna_category',
                'field'    => 'term_id',
                'terms'    => $exclude_ids,
                'operator' => 'NOT IN',
            );
        }

        // Add tax query to args if we have any
        if (!empty($tax_query)) {
            if (count($tax_query) > 1) {
                $tax_query['relation'] = 'AND';
            }
            $args['tax_query'] = $tax_query;
        }

        if ($atts['number'] != -1) {
            $args['posts_per_page'] = intval($atts['number']);
        }

        $query = new WP_Query($args);
        
        // Start the container
        $output = '<div class="pna-container" id="pna-container-' . $instance_id . '">';
        
        // Add left arrow if using arrows
        if ($atts['arrows'] === 'true') {
            $output .= '<div class="pna-arrow-nav prev' . ($paged <= 1 ? ' disabled' : '') . '" data-instance="' . $instance_id . '">
                <span class="pna-arrow-icon">←</span>
            </div>';
        }

        // Main content container
        $output .= '<div class="pna-custom-masonry-grid" id="polaris-posts-container-' . $instance_id . '"
                    data-instance="' . $instance_id . '"
                    data-post-type="polaris_news"
                    data-category="' . esc_attr($atts['category']) . '"
                    data-exclude="' . esc_attr($atts['exclude']) . '"
                    data-posts-per-page="' . esc_attr($atts['posts_per_page']) . '"
                    data-use-arrows="' . esc_attr($atts['arrows']) . '"
                    data-current-page="' . esc_attr($paged) . '"
                    data-max-pages="' . esc_attr($query->max_num_pages) . '">';

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $featured_image = get_the_post_thumbnail_url(get_the_ID(), 'large');
                $post_title = get_the_title();
                $post_excerpt = get_the_excerpt();
                $external_url = get_post_meta(get_the_ID(), '_external_url', true);
                $permalink = !empty($external_url) ? $external_url : get_the_permalink();
                $target = !empty($external_url) ? ' target="_blank" rel="noopener noreferrer"' : '';
                
                // Get category name from custom taxonomy
                $categories = get_the_terms(get_the_ID(), 'pna_category');
                $category_label = !empty($categories) ? esc_html($categories[0]->name) : '';
                $is_announcement = !empty($categories) && strtolower($categories[0]->slug) === 'announcements';

                // Get post date
                $post_date = get_the_date('F j, Y');

                $output .= sprintf(
                    '<div class="pna-masonry-item%s">',
                    $is_announcement ? ' pna-announcement' : ''
                );

                if (!empty($category_label)) {
                    $output .= '<div class="pna-category-ribbon">' . $category_label . '</div>';
                }

                if ($is_announcement) {
                    $output .= '<div class="pna-announcement-content">';
                    $output .= '<h3 class="pna-announcement-title">' . esc_html($post_title) . '</h3>';
                    $output .= '<div class="pna-announcement-date">' . esc_html($post_date) . '</div>';
                    $output .= '<div class="pna-announcement-excerpt">' . wp_kses_post($post_excerpt) . '</div>';
                    $output .= '<a href="' . esc_url($permalink) . '"' . $target . ' class="pna-announcement-link">Read More</a>';
                    $output .= '</div>';
                } else {
                    if ($featured_image) {
                        $output .= '<a href="' . esc_url($permalink) . '"' . $target . ' class="pna-masonry-image" style="background-image: url(' . esc_url($featured_image) . ');">';
                        $output .= '<div class="pna-masonry-title">' . esc_html($post_title) . '</div>';
                        $output .= '</a>';
                    } else {
                        $output .= '<a href="' . esc_url($permalink) . '"' . $target . ' class="pna-masonry-no-image">';
                        $output .= '<div class="pna-masonry-title pna-masonry-title-no-image">' . esc_html($post_title) . '</div>';
                        $output .= '</a>';
                    }
                }

                $output .= '</div>'; // End masonry item
            }
        } else {
            $output .= '<p>No posts found.</p>';
        }

        $output .= '</div>'; // End masonry grid

        // Add right arrow if using arrows
        if ($atts['arrows'] === 'true') {
            $output .= '<div class="pna-arrow-nav next' . ($paged >= $query->max_num_pages ? ' disabled' : '') . '" data-instance="' . $instance_id . '">
                <span class="pna-arrow-icon">→</span>
            </div>';
        }

        $output .= '</div>'; // End container

        // Regular pagination (if not using arrows)
        if ($query->max_num_pages > 1 && $atts['arrows'] !== 'true') {
            $output .= '<div class="pna-pagination" data-instance="' . $instance_id . '">';
            for ($i = 1; $i <= $query->max_num_pages; $i++) {
                $output .= '<span class="pna-page-link' . ($paged === $i ? ' active' : '') . '" data-page="' . $i . '" data-instance="' . $instance_id . '">' . $i . '</span>';
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
        $exclude = isset($_POST['exclude']) ? sanitize_text_field($_POST['exclude']) : '';
        $instance = isset($_POST['instance']) ? intval($_POST['instance']) : 1;

        $args = array(
            'post_type'      => 'polaris_news',
            'posts_per_page' => $posts_per_page,
            'paged'          => $page,
        );

        // Build tax query
        $tax_query = array();

        // Add category filter if provided
        if (!empty($category)) {
            $category_ids = array_map('intval', explode(',', $category));
            $tax_query[] = array(
                'taxonomy' => 'pna_category',
                'field'    => 'term_id',
                'terms'    => $category_ids,
            );
        }

        // Add exclude filter if provided
        if (!empty($exclude)) {
            $exclude_ids = array_map('intval', explode(',', $exclude));
            $tax_query[] = array(
                'taxonomy' => 'pna_category',
                'field'    => 'term_id',
                'terms'    => $exclude_ids,
                'operator' => 'NOT IN',
            );
        }

        // Add tax query to args if we have any
        if (!empty($tax_query)) {
            if (count($tax_query) > 1) {
                $tax_query['relation'] = 'AND';
            }
            $args['tax_query'] = $tax_query;
        }

        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $featured_image = get_the_post_thumbnail_url(get_the_ID(), 'large');
                $post_title = get_the_title();
                $post_excerpt = get_the_excerpt();
                $external_url = get_post_meta(get_the_ID(), '_external_url', true);
                $permalink = !empty($external_url) ? $external_url : get_the_permalink();
                $target = !empty($external_url) ? ' target="_blank" rel="noopener noreferrer"' : '';
                
                // Get category name from custom taxonomy
                $categories = get_the_terms(get_the_ID(), 'pna_category');
                $category_label = !empty($categories) ? esc_html($categories[0]->name) : '';
                $is_announcement = !empty($categories) && strtolower($categories[0]->slug) === 'announcements';

                // Get post date
                $post_date = get_the_date('F j, Y');
                ?>
                <div class="pna-masonry-item<?php echo $is_announcement ? ' pna-announcement' : ''; ?>">
                    <?php if (!empty($category_label)) : ?>
                        <div class="pna-category-ribbon"><?php echo $category_label; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($is_announcement) : ?>
                        <div class="pna-announcement-content">
                            <h3 class="pna-announcement-title"><?php echo esc_html($post_title); ?></h3>
                            <div class="pna-announcement-date"><?php echo esc_html($post_date); ?></div>
                            <div class="pna-announcement-excerpt"><?php echo wp_kses_post($post_excerpt); ?></div>
                            <a href="<?php echo esc_url($permalink); ?>"<?php echo $target; ?> class="pna-announcement-link">Read More</a>
                        </div>
                    <?php else : ?>
                        <?php if ($featured_image) : ?>
                            <a href="<?php echo esc_url($permalink); ?>"<?php echo $target; ?> class="pna-masonry-image" style="background-image: url(<?php echo esc_url($featured_image); ?>);">
                                <div class="pna-masonry-title"><?php echo esc_html($post_title); ?></div>
                            </a>
                        <?php else : ?>
                            <a href="<?php echo esc_url($permalink); ?>"<?php echo $target; ?> class="pna-masonry-no-image">
                                <div class="pna-masonry-title pna-masonry-title-no-image"><?php echo esc_html($post_title); ?></div>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php
            }
        }
        wp_reset_postdata();
        die();
    }
}