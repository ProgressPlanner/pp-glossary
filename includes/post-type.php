<?php
/**
 * Glossary Post Type Registration
 *
 * @package PP_Glossary
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class PP_Glossary_Post_Type
 */
class PP_Glossary_Post_Type {

	/**
	 * Initialize the post type
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
	}

	/**
	 * Register the Glossary post type
	 */
	public static function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Glossary', 'Post Type General Name', 'pp-glossary' ),
			'singular_name'         => _x( 'Entry', 'Post Type Singular Name', 'pp-glossary' ),
			'menu_name'             => __( 'Glossary', 'pp-glossary' ),
			'name_admin_bar'        => __( 'Glossary Entry', 'pp-glossary' ),
			'archives'              => __( 'Glossary Archives', 'pp-glossary' ),
			'attributes'            => __( 'Entry Attributes', 'pp-glossary' ),
			'parent_item_colon'     => __( 'Parent Entry:', 'pp-glossary' ),
			'all_items'             => __( 'All Entries', 'pp-glossary' ),
			'add_new_item'          => __( 'Add New Entry', 'pp-glossary' ),
			'add_new'               => __( 'Add New', 'pp-glossary' ),
			'new_item'              => __( 'New Entry', 'pp-glossary' ),
			'edit_item'             => __( 'Edit Entry', 'pp-glossary' ),
			'update_item'           => __( 'Update Entry', 'pp-glossary' ),
			'view_item'             => __( 'View Entry', 'pp-glossary' ),
			'view_items'            => __( 'View Entries', 'pp-glossary' ),
			'search_items'          => __( 'Search Entry', 'pp-glossary' ),
			'not_found'             => __( 'Not found', 'pp-glossary' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'pp-glossary' ),
			'featured_image'        => __( 'Featured Image', 'pp-glossary' ),
			'set_featured_image'    => __( 'Set featured image', 'pp-glossary' ),
			'remove_featured_image' => __( 'Remove featured image', 'pp-glossary' ),
			'use_featured_image'    => __( 'Use as featured image', 'pp-glossary' ),
			'insert_into_item'      => __( 'Insert into entry', 'pp-glossary' ),
			'uploaded_to_this_item' => __( 'Uploaded to this entry', 'pp-glossary' ),
			'items_list'            => __( 'Entries list', 'pp-glossary' ),
			'items_list_navigation' => __( 'Entries list navigation', 'pp-glossary' ),
			'filter_items_list'     => __( 'Filter entries list', 'pp-glossary' ),
		);

		$args = array(
			'label'               => __( 'Entry', 'pp-glossary' ),
			'description'         => __( 'Glossary entries with definitions', 'pp-glossary' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'revisions' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-book-alt',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => false,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
			'rewrite'             => false,
		);

		register_post_type( 'pp_glossary', $args );
	}
}
