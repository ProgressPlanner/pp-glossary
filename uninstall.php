<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Cleans up all plugin data including glossary posts, post meta, and options.
 *
 * @package PP_Glossary
 */

// If uninstall not called from WordPress, abort.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Delete all glossary posts and their meta.
$pp_glossary_posts = get_posts(
	[
		'post_type'      => 'pp_glossary',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	]
);

foreach ( $pp_glossary_posts as $pp_glossary_post_id ) {
	wp_delete_post( $pp_glossary_post_id, true );
}

// Delete plugin options.
delete_option( 'pp_glossary_settings' );
