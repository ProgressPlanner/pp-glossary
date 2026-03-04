<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Cleans up all plugin data including options and post meta.
 * Does not delete glossary posts to preserve user content.
 *
 * @package PP_Glossary
 */

// If uninstall not called from WordPress, abort.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Delete plugin options.
delete_option( 'pp_glossary_settings' );

// Delete all glossary post meta.
global $wpdb;

$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => '_pp_glossary_data' ] ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
