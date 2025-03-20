<?php
/**
 * Class to handle the custom post type registration
 */
class Polaris_News_CPT {
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
    }

    public function register_post_type() {
        $labels = array(
            'name'                  => 'Polaris News & Announcements',
            'singular_name'         => 'News & Announcement',
            'menu_name'            => 'News & Announcements',
            'add_new'              => 'Add New',
            'add_new_item'         => 'Add New News & Announcement',
            'edit_item'            => 'Edit News & Announcement',
            'new_item'             => 'New News & Announcement',
            'view_item'            => 'View News & Announcement',
            'search_items'         => 'Search News & Announcements',
            'not_found'            => 'No news & announcements found',
            'not_found_in_trash'   => 'No news & announcements found in Trash'
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'has_archive'         => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'news-announcements'),
            'capability_type'     => 'post',
            'menu_icon'           => 'dashicons-megaphone',
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
            'taxonomies'          => array('pna_category'),
        );

        register_post_type('polaris_news', $args);
    }

    public function register_taxonomies() {
        $labels = array(
            'name'              => 'News Categories',
            'singular_name'     => 'News Category',
            'search_items'      => 'Search News Categories',
            'all_items'         => 'All News Categories',
            'parent_item'       => 'Parent News Category',
            'parent_item_colon' => 'Parent News Category:',
            'edit_item'         => 'Edit News Category',
            'update_item'       => 'Update News Category',
            'add_new_item'      => 'Add New News Category',
            'new_item_name'     => 'New News Category Name',
            'menu_name'         => 'Categories'
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'news-category'),
        );

        register_taxonomy('pna_category', array('polaris_news'), $args);
    }
}