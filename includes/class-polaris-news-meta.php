<?php
/**
 * Class to handle the external URL meta box
 */
class Polaris_News_Meta {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));
        add_action('wp_head', array($this, 'add_meta_robots'), 1);
        add_filter('template_redirect', array($this, 'handle_external_redirect'));
    }

    public function add_meta_box() {
        add_meta_box(
            'external_url_meta_box',
            'External URL',
            array($this, 'render_meta_box'),
            'polaris_news',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('external_url_meta_box', 'external_url_meta_box_nonce');
        $external_url = get_post_meta($post->ID, '_external_url', true);
        ?>
        <p>
            <label for="external_url">External URL:</label>
            <input type="url" id="external_url" name="external_url" value="<?php echo esc_url($external_url); ?>" size="50" />
            <br>
            <span class="description">If provided, clicking the post will redirect to this URL instead of the post page.</span>
        </p>
        <?php
    }

    public function save_meta_box($post_id) {
        if (!isset($_POST['external_url_meta_box_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['external_url_meta_box_nonce'], 'external_url_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['external_url'])) {
            update_post_meta($post_id, '_external_url', esc_url_raw($_POST['external_url']));
        }
    }

    /**
     * Add meta robots tag for external URL posts
     */
    public function add_meta_robots() {
        if (is_singular('polaris_news')) {
            $external_url = get_post_meta(get_the_ID(), '_external_url', true);
            if (!empty($external_url)) {
                echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
            }
        }
    }

    /**
     * Handle redirect for external URL posts
     */
    public function handle_external_redirect() {
        if (is_singular('polaris_news')) {
            $external_url = get_post_meta(get_the_ID(), '_external_url', true);
            if (!empty($external_url)) {
                wp_redirect($external_url, 301);
                exit;
            }
        }
    }
}