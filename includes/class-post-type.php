<?php
/**
 * Glossary Post Type Registration.
 *
 * @package Inline_Glossary
 */

namespace Inline_Glossary;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Post_Type
 */
class Post_Type {

	/**
	 * Initialize the post type
	 */
	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register_post_type' ] );

		// Exclude from Yoast SEO indexables and XML sitemaps.
		add_filter( 'wpseo_indexable_excluded_post_types', [ __CLASS__, 'exclude_from_yoast_indexables' ] );
		add_filter( 'wpseo_sitemap_exclude_post_type', [ __CLASS__, 'exclude_from_yoast_sitemap' ], 10, 2 );
	}

	/**
	 * Exclude glossary entries from Yoast SEO indexables.
	 *
	 * @param array<int, string> $excluded_post_types Array of excluded post types.
	 *
	 * @return array<int, string> Modified array of excluded post types.
	 */
	public static function exclude_from_yoast_indexables( array $excluded_post_types ): array {
		$excluded_post_types[] = 'inline_glossary';
		return $excluded_post_types;
	}

	/**
	 * Exclude glossary entries from Yoast SEO XML sitemaps.
	 *
	 * @param bool   $exclude  Whether to exclude this post type.
	 * @param string $post_type The post type.
	 *
	 * @return bool Whether to exclude this post type.
	 */
	public static function exclude_from_yoast_sitemap( bool $exclude, string $post_type ): bool {
		if ( 'inline_glossary' === $post_type ) {
			return true;
		}
		return $exclude;
	}

	/**
	 * Register the Glossary post type
	 */
	public static function register_post_type(): void {
		$labels = [
			'name'                  => _x( 'Glossary', 'Post Type General Name', 'inline-glossary' ),
			'singular_name'         => _x( 'Entry', 'Post Type Singular Name', 'inline-glossary' ),
			'menu_name'             => __( 'Glossary', 'inline-glossary' ),
			'name_admin_bar'        => __( 'Glossary entry', 'inline-glossary' ),
			'archives'              => __( 'Glossary archives', 'inline-glossary' ),
			'attributes'            => __( 'Entry attributes', 'inline-glossary' ),
			'all_items'             => __( 'All entries', 'inline-glossary' ),
			'add_new_item'          => __( 'Add new entry', 'inline-glossary' ),
			'add_new'               => __( 'Add new', 'inline-glossary' ),
			'new_item'              => __( 'New entry', 'inline-glossary' ),
			'edit_item'             => __( 'Edit entry', 'inline-glossary' ),
			'update_item'           => __( 'Update entry', 'inline-glossary' ),
			'view_item'             => __( 'View entry', 'inline-glossary' ),
			'view_items'            => __( 'View entries', 'inline-glossary' ),
			'search_items'          => __( 'Search entries', 'inline-glossary' ),
			'not_found'             => __( 'Not found', 'inline-glossary' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'inline-glossary' ),
			'insert_into_item'      => __( 'Insert into entry', 'inline-glossary' ),
			'uploaded_to_this_item' => __( 'Uploaded to this entry', 'inline-glossary' ),
			'items_list'            => __( 'Entries list', 'inline-glossary' ),
			'items_list_navigation' => __( 'Entries list navigation', 'inline-glossary' ),
			'filter_items_list'     => __( 'Filter entries list', 'inline-glossary' ),
		];

		$args = [
			'label'               => __( 'Entry', 'inline-glossary' ),
			'description'         => __( 'Glossary entries with definitions', 'inline-glossary' ),
			'labels'              => $labels,
			'supports'            => [ 'title' ],
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-index-card',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
			'rewrite'             => false,
		];

		register_post_type( 'inline_glossary', $args );
	}
}
