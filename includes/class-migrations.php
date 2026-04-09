<?php
/**
 * Migrations for Glossary
 *
 * @package Your_Glossary
 */

namespace Your_Glossary;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Migrations
 */
class Migrations {

	/**
	 * Old option name before rename.
	 */
	const OLD_OPTION_NAME = 'pp_glossary_settings';

	/**
	 * Old post type slug before rename.
	 */
	const OLD_POST_TYPE = 'pp_glossary';

	/**
	 * Old meta key before rename.
	 */
	const OLD_META_KEY = '_pp_glossary_data';

	/**
	 * Old block name before rename.
	 */
	const OLD_BLOCK_NAME = 'wp:pp-glossary/glossary-list';

	/**
	 * New block name after rename.
	 */
	const NEW_BLOCK_NAME = 'wp:your-glossary/glossary-list';

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
		// Check new option first, fall back to old option name (pre-1.4.0).
		$raw_settings = get_option( Settings::OPTION_NAME, [] );
		if ( empty( $raw_settings ) ) {
			$raw_settings = get_option( self::OLD_OPTION_NAME, [] );
		}

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
				Settings::update_setting( 'db_version', YOUR_GLOSSARY_VERSION );
				return;
			}
		} else {
			$current_version = $raw_settings['db_version'];
		}

		// Migration to 1.1.0: Consolidate meta fields into single array.
		if ( version_compare( $current_version, '1.1.0', '<' ) ) {
			self::migrate_to_1_1_0();
			Settings::update_setting( 'db_version', '1.1.0' );
		}

		// Migration to 1.4.0: Rename all DB identifiers from pp_glossary to your_glossary.
		if ( version_compare( $current_version, '1.4.0', '<' ) ) {
			self::migrate_to_1_4_0();
			Settings::update_setting( 'db_version', '1.4.0' );
		}
	}

	/**
	 * Check if there are any glossary posts (checks both old and new post type slugs).
	 *
	 * @return bool True if glossary posts exist.
	 */
	private static function has_glossary_posts(): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN (%s, %s) LIMIT 1",
				self::OLD_POST_TYPE,
				'your_glossary'
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Migrate to 1.1.0: Consolidate individual meta fields into single array.
	 */
	private static function migrate_to_1_1_0(): void {
		global $wpdb;

		// Use direct query to find posts regardless of registered post type.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
				self::OLD_POST_TYPE
			)
		);

		if ( empty( $post_ids ) ) {
			return;
		}

		foreach ( $post_ids as $post_id ) {
			$post_id = (int) $post_id;

			// Check if already migrated (new data exists).
			$existing_data = get_post_meta( $post_id, self::OLD_META_KEY, true );
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
				'short_description' => (string) $short_description,
				'long_description'  => (string) $long_description,
				'synonyms'          => is_array( $synonyms ) ? $synonyms : [],
				'case_sensitive'    => '1' === $case_sensitive,
				'disable_autolink'  => '1' === $disable_autolink,
			];

			// Save new format.
			update_post_meta( $post_id, self::OLD_META_KEY, $data );

			// Delete old meta keys.
			delete_post_meta( $post_id, '_pp_glossary_short_description' );
			delete_post_meta( $post_id, '_pp_glossary_long_description' );
			delete_post_meta( $post_id, '_pp_glossary_synonyms' );
		}
	}

	/**
	 * Migrate to 1.4.0: Rename all DB identifiers from pp_glossary to your_glossary.
	 *
	 * Renames:
	 * - Post type: pp_glossary -> your_glossary
	 * - Meta key: _pp_glossary_data -> _your_glossary_data
	 * - Option name: pp_glossary_settings -> your_glossary_settings
	 * - Block name in post content: wp:pp-glossary/glossary-list -> wp:your-glossary/glossary-list
	 */
	private static function migrate_to_1_4_0(): void {
		global $wpdb;

		// 1. Rename post type.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->posts,
			[ 'post_type' => 'your_glossary' ],
			[ 'post_type' => self::OLD_POST_TYPE ]
		);

		// 2. Rename meta key.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->postmeta,
			[ 'meta_key' => '_your_glossary_data' ], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			[ 'meta_key' => self::OLD_META_KEY ] // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		);

		// 3. Rename option.
		$old_settings = get_option( self::OLD_OPTION_NAME, [] );
		if ( ! empty( $old_settings ) ) {
			update_option( Settings::OPTION_NAME, $old_settings );
			delete_option( self::OLD_OPTION_NAME );
		}

		// 4. Rename block name in post content.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE post_content LIKE %s",
				self::OLD_BLOCK_NAME,
				self::NEW_BLOCK_NAME,
				'%' . $wpdb->esc_like( self::OLD_BLOCK_NAME ) . '%'
			)
		);

		// Flush rewrite rules since the post type slug changed.
		flush_rewrite_rules();
	}
}
