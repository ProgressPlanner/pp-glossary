<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Cleans up glossary posts, post meta, and options only when explicitly enabled.
 *
 * @package PP_Glossary
 */

// If uninstall not called from WordPress, abort.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Preserve data unless an administrator explicitly opted into deletion.
$pp_glossary_settings = get_option( 'pp_glossary_settings', [] );
if ( ! is_array( $pp_glossary_settings ) || true !== ( $pp_glossary_settings['delete_data_on_uninstall'] ?? false ) ) {
	return;
}

// Include internal statuses such as trash and auto-draft, which 'any' excludes.
$pp_glossary_posts = get_posts(
	[
		'post_type'      => 'pp_glossary',
		'posts_per_page' => -1,
		'post_status'    => array_values( get_post_stati() ),
		'fields'         => 'ids',
	]
);

foreach ( $pp_glossary_posts as $pp_glossary_post_id ) {
	wp_delete_post( $pp_glossary_post_id, true );
}

// Delete plugin options.
delete_option( 'pp_glossary_settings' );
