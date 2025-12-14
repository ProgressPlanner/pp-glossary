<?php
/**
 * Migrations for Glossary
 *
 * @package PP_Glossary
 */

namespace PP_Glossary;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Migrations
 */
class Migrations {

	/**
	 * Initialize migrations.
	 */
	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'run_migrations' ] );
	}

	/**
	 * Run necessary migrations based on stored version.
	 */
	public static function run_migrations(): void {
		// Get raw option to check if db_version is actually stored.
		$raw_settings = get_option( Settings::OPTION_NAME, [] );

		// If db_version is not stored, this is either:
		// - A fresh install (no option at all, or empty option) -> no migration needed.
		// - An upgrade from pre-1.0.4 (has glossary_page but no db_version) -> needs migration from 1.0.0.
		if ( ! isset( $raw_settings['db_version'] ) ) {
			// Check if this is an existing install by looking for glossary_page or any glossary posts.
			$is_existing_install = ! empty( $raw_settings['glossary_page'] ) || self::has_glossary_posts();

			if ( $is_existing_install ) {
				// Existing install upgrading - start from 1.0.0 to run all migrations.
				$current_version = '1.0.0';
			} else {
				// Fresh install - set to current version and skip migrations.
				Settings::update_setting( 'db_version', PP_GLOSSARY_VERSION );
				return;
			}
		} else {
			$current_version = $raw_settings['db_version'];
		}

		// Migration to 1.0.4: Consolidate meta fields into single array.
		if ( version_compare( $current_version, '1.1.0', '<' ) ) {
			self::migrate_to_1_1_0();
			Settings::update_setting( 'db_version', '1.1.0' );
		}
	}

	/**
	 * Check if there are any glossary posts.
	 *
	 * @return bool True if glossary posts exist.
	 */
	private static function has_glossary_posts(): bool {
		$query = new \WP_Query(
			[
				'post_type'      => 'pp_glossary',
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			]
		);

		return ! empty( $query->posts );
	}

	/**
	 * Migrate to 1.0.4: Consolidate individual meta fields into single array.
	 */
	private static function migrate_to_1_1_0(): void {
		$query = new \WP_Query(
			[
				'post_type'      => 'pp_glossary',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			]
		);

		if ( empty( $query->posts ) ) {
			return;
		}

		foreach ( $query->posts as $post ) {
			$post_id = $post instanceof \WP_Post ? $post->ID : (int) $post;

			// Check if already migrated (new data exists).
			$existing_data = get_post_meta( $post_id, '_pp_glossary_data', true );
			if ( is_array( $existing_data ) && ! empty( $existing_data ) ) {
				continue;
			}

			// Get old individual meta values.
			$short_description = get_post_meta( $post_id, '_pp_glossary_short_description', true );
			$long_description  = get_post_meta( $post_id, '_pp_glossary_long_description', true );
			$synonyms          = get_post_meta( $post_id, '_pp_glossary_synonyms', true );
			$case_sensitive    = get_post_meta( $post_id, '_pp_glossary_case_sensitive', true );
			$disable_autolink  = get_post_meta( $post_id, '_pp_glossary_disable_autolink', true );

			// Only migrate if there's actually old data.
			if ( empty( $short_description ) && empty( $long_description ) && empty( $synonyms ) ) {
				continue;
			}

			// Build new consolidated data array.
			$data = [
				'short_description' => ! empty( $short_description ) ? $short_description : '',
				'long_description'  => ! empty( $long_description ) ? $long_description : '',
				'synonyms'          => is_array( $synonyms ) ? $synonyms : [],
				'case_sensitive'    => '1' === $case_sensitive,
				'disable_autolink'  => '1' === $disable_autolink,
			];

			// Save new format.
			update_post_meta( $post_id, '_pp_glossary_data', $data );

			// Delete old meta keys.
			delete_post_meta( $post_id, '_pp_glossary_short_description' );
			delete_post_meta( $post_id, '_pp_glossary_long_description' );
			delete_post_meta( $post_id, '_pp_glossary_synonyms' );
		}
	}
}
