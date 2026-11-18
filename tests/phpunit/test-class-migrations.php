<?php
/**
 * Tests for the data migrations.
 *
 * @package PP_Glossary
 */

namespace PP_Glossary\Tests;

use PP_Glossary\Migrations;
use PP_Glossary\Settings;

/**
 * Covers the upgrade routine.
 */
class Migrations_Test extends Glossary_TestCase {

	/**
	 * Create a glossary entry that still uses the pre-1.1.0 meta keys.
	 *
	 * @return int The post ID.
	 */
	private function create_legacy_entry(): int {
		$post_id = self::factory()->post->create( [ 'post_type' => 'pp_glossary' ] );
		update_post_meta( $post_id, '_pp_glossary_short_description', 'Old short' );
		update_post_meta( $post_id, '_pp_glossary_long_description', '<p>Old long</p>' );
		update_post_meta( $post_id, '_pp_glossary_synonyms', [ 'old synonym' ] );
		update_post_meta( $post_id, '_pp_glossary_case_sensitive', '1' );

		return $post_id;
	}

	/**
	 * Legacy meta keys are consolidated into one array.
	 */
	public function test_legacy_meta_is_migrated() {
		$post_id = $this->create_legacy_entry();

		Migrations::run_migrations();

		$this->assertSame(
			[
				'short_description' => 'Old short',
				'long_description'  => '<p>Old long</p>',
				'synonyms'          => [ 'old synonym' ],
				'case_sensitive'    => true,
				'disable_autolink'  => false,
			],
			get_post_meta( $post_id, '_pp_glossary_data', true )
		);
		$this->assertSame( '', get_post_meta( $post_id, '_pp_glossary_short_description', true ) );
		$this->assertSame( '', get_post_meta( $post_id, '_pp_glossary_long_description', true ) );
		$this->assertSame( '', get_post_meta( $post_id, '_pp_glossary_synonyms', true ) );
		$this->assertSame( '1.1.0', get_option( Settings::OPTION_NAME )['db_version'] );
	}

	/**
	 * Entries that already have consolidated data are left alone.
	 */
	public function test_migrated_entries_are_not_overwritten() {
		$post_id = $this->create_legacy_entry();
		$data    = [ 'short_description' => 'New short' ];
		update_post_meta( $post_id, '_pp_glossary_data', $data );

		Migrations::run_migrations();

		$this->assertSame( $data, get_post_meta( $post_id, '_pp_glossary_data', true ) );
		$this->assertSame( 'Old short', get_post_meta( $post_id, '_pp_glossary_short_description', true ) );
	}

	/**
	 * The migration does not run again once the database version is current.
	 */
	public function test_migration_is_skipped_when_up_to_date() {
		Settings::update_setting( 'db_version', '1.1.0' );
		$post_id = $this->create_legacy_entry();

		Migrations::run_migrations();

		$this->assertSame( '', get_post_meta( $post_id, '_pp_glossary_data', true ) );
		$this->assertSame( 'Old short', get_post_meta( $post_id, '_pp_glossary_short_description', true ) );
	}

	/**
	 * A fresh install only records the current version.
	 */
	public function test_fresh_install_sets_current_version() {
		foreach ( get_posts(
			[
				'post_type'   => 'pp_glossary',
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			]
		) as $post_id ) {
			wp_delete_post( $post_id, true );
		}

		Migrations::run_migrations();

		$this->assertSame( PP_GLOSSARY_VERSION, get_option( Settings::OPTION_NAME )['db_version'] );
	}
}
