<?php
/**
 * Plugin Name: Polaris News & Announcements
 * Description: Custom post type for news and announcements with external URL support
 * Version: 1.0.0
 * Author: Chan Inthisone
 * Text Domain: polaris-news
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('PNA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PNA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once PNA_PLUGIN_PATH . 'includes/class-polaris-news-cpt.php';
require_once PNA_PLUGIN_PATH . 'includes/class-polaris-news-meta.php';
require_once PNA_PLUGIN_PATH . 'includes/class-polaris-news-shortcode.php';

// Enqueue scripts and styles
function pna_enqueue_scripts() {
    wp_enqueue_style('pna-styles', PNA_PLUGIN_URL . 'style.css', array(), '1.0.0');
    wp_enqueue_script('pna-script', PNA_PLUGIN_URL . 'script.js', array('jquery'), '1.0.0', true);
    wp_localize_script('pna-script', 'polaris_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'pna_enqueue_scripts');

// Initialize the plugin
function pna_init() {
    new Polaris_News_CPT();
    new Polaris_News_Meta();
    new Polaris_News_Shortcode();
}
add_action('plugins_loaded', 'pna_init');