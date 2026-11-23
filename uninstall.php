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

// Keep each site's consent and cleanup within that site's database context.
$pp_glossary_cleanup_site = static function (): void {
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
};

if ( ! is_multisite() ) {
	$pp_glossary_cleanup_site();
	return;
}

// Process every site in bounded batches, including archived and deleted sites.
$pp_glossary_offset = 0;
do {
	$pp_glossary_site_ids   = get_sites(
		[
			'fields'  => 'ids',
			'number'  => 100,
			'offset'  => $pp_glossary_offset,
			'orderby' => 'id',
			'order'   => 'ASC',
		]
	);
	$pp_glossary_site_count = count( $pp_glossary_site_ids );

	foreach ( $pp_glossary_site_ids as $pp_glossary_site_id ) {
		switch_to_blog( (int) $pp_glossary_site_id );
		try {
			$pp_glossary_cleanup_site();
		} finally {
			restore_current_blog();
		}
	}

	$pp_glossary_offset += 100;
} while ( $pp_glossary_site_count === 100 );
