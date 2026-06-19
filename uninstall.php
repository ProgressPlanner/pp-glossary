<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Cleans up all plugin data including glossary posts, post meta, and options.
 *
 * @package Inline_Glossary
 */

// If uninstall not called from WordPress, abort.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Delete all glossary posts and their meta.
$inline_glossary_posts = get_posts(
	[
		'post_type'      => 'inline_glossary',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	]
);

foreach ( $inline_glossary_posts as $inline_glossary_post_id ) {
	wp_delete_post( $inline_glossary_post_id, true );
}

// Delete plugin options.
delete_option( 'inline_glossary_settings' );
