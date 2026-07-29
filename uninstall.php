<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Cleans up all plugin data including glossary posts, post meta, and options.
 *
 * @package Your_Glossary
 */

// If uninstall not called from WordPress, abort.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Delete all glossary posts and their meta (both new and old post type in case migration didn't run).
$your_glossary_posts = get_posts(
	[
		'post_type'      => [ 'your_glossary', 'pp_glossary' ],
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	]
);

foreach ( $your_glossary_posts as $your_glossary_post_id ) {
	wp_delete_post( $your_glossary_post_id, true );
}

// Delete plugin options (new and old name in case migration didn't run).
delete_option( 'your_glossary_settings' );
delete_option( 'pp_glossary_settings' );
