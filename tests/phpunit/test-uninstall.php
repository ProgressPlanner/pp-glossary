<?php
/**
 * Tests for uninstall data retention.
 *
 * @package PP_Glossary
 */

namespace PP_Glossary\Tests;

use PP_Glossary\Settings;

/**
 * Exercise the real uninstall entry point against WordPress data.
 */
class Uninstall_Test extends \WP_UnitTestCase {

	/**
	 * Run uninstall without loading the plugin again.
	 */
	private function run_uninstall(): void {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'pp-glossary/pp-glossary.php' );
		}
		include dirname( __DIR__, 2 ) . '/uninstall.php';
	}

	/**
	 * Create glossary entries in every built-in content status.
	 *
	 * @return int[] Entry IDs.
	 */
	private function create_entries(): array {
		$ids = [];
		foreach ( [ 'publish', 'draft', 'private', 'pending', 'future', 'trash', 'auto-draft' ] as $status ) {
			$id = self::factory()->post->create(
				[
					'post_type'   => 'pp_glossary',
					'post_status' => $status,
					'post_date'   => 'future' === $status ? '2035-01-01 12:00:00' : '2020-01-01 12:00:00',
				]
			);
			update_post_meta( $id, '_pp_glossary_data', [ 'short_description' => $status ] );
			$ids[] = $id;
		}
		return $ids;
	}

	/**
	 * Missing or non-boolean consent must preserve entries and settings.
	 */
	public function test_retention_requires_explicit_boolean_consent() {
		$ids = $this->create_entries();
		foreach ( [ null, [], [ 'delete_data_on_uninstall' => false ], [ 'delete_data_on_uninstall' => '1' ] ] as $settings ) {
			delete_option( Settings::OPTION_NAME );
			if ( null !== $settings ) {
				update_option( Settings::OPTION_NAME, $settings );
			}
			$this->run_uninstall();
			$this->assertSame( $settings, get_option( Settings::OPTION_NAME, null ) );
			foreach ( $ids as $id ) {
				$this->assertInstanceOf( \WP_Post::class, get_post( $id ) );
				$this->assertTrue( metadata_exists( 'post', $id, '_pp_glossary_data' ) );
			}
		}
	}

	/**
	 * Consent deletes every glossary status and its metadata, but no other posts.
	 */
	public function test_opt_in_deletes_only_glossary_data() {
		$ids  = $this->create_entries();
		$page = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$post = self::factory()->post->create();
		update_option(
			Settings::OPTION_NAME,
			[
				'delete_data_on_uninstall' => true,
				'glossary_page'            => $page,
			]
		);

		$this->run_uninstall();

		foreach ( $ids as $id ) {
			$this->assertNull( get_post( $id ) );
			$this->assertFalse( metadata_exists( 'post', $id, '_pp_glossary_data' ) );
		}
		$this->assertNull( get_option( Settings::OPTION_NAME, null ) );
		$this->assertInstanceOf( \WP_Post::class, get_post( $page ) );
		$this->assertInstanceOf( \WP_Post::class, get_post( $post ) );
	}

	/**
	 * Each site's consent is independent of the main site's preference.
	 *
	 * @dataProvider multisite_consent_provider
	 * @param bool $main_consent Main site's deletion preference.
	 */
	public function test_multisite_respects_each_sites_consent( bool $main_consent ) {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$main_id     = get_current_blog_id();
		$retain_id   = self::factory()->blog->create();
		$delete_id   = self::factory()->blog->create();
		$site_ids    = [ $main_id, $retain_id, $delete_id ];
		$entries     = [];
		$settings    = [];
		$pages       = [];
		$preferences = [
			$main_id   => $main_consent,
			$retain_id => false,
			$delete_id => true,
		];

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );
			$entries[ $site_id ]  = $this->create_entries();
			$pages[ $site_id ]    = self::factory()->post->create( [ 'post_type' => 'page' ] );
			$settings[ $site_id ] = [
				'delete_data_on_uninstall' => $preferences[ $site_id ],
				'glossary_page'            => $pages[ $site_id ],
			];
			update_option( Settings::OPTION_NAME, $settings[ $site_id ] );
			restore_current_blog();
		}

		// Starting from a subsite also verifies the caller's switch stack is restored.
		switch_to_blog( $retain_id );
		try {
			$this->run_uninstall();
			$this->assertSame( $retain_id, get_current_blog_id() );
		} finally {
			restore_current_blog();
		}
		$this->assertSame( $main_id, get_current_blog_id() );

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );
			try {
				foreach ( $entries[ $site_id ] as $id ) {
					if ( $preferences[ $site_id ] ) {
						$this->assertNull( get_post( $id ) );
						$this->assertFalse( metadata_exists( 'post', $id, '_pp_glossary_data' ) );
					} else {
						$this->assertInstanceOf( \WP_Post::class, get_post( $id ) );
						$this->assertTrue( metadata_exists( 'post', $id, '_pp_glossary_data' ) );
					}
				}
				$this->assertSame( $preferences[ $site_id ] ? null : $settings[ $site_id ], get_option( Settings::OPTION_NAME, null ) );
				$this->assertInstanceOf( \WP_Post::class, get_post( $pages[ $site_id ] ) );
			} finally {
				restore_current_blog();
			}
		}
	}

	/**
	 * Main-site settings must not control subsite cleanup.
	 *
	 * @return array<string, array<bool>> Test cases.
	 */
	public function multisite_consent_provider(): array {
		return [
			'main retains' => [ false ],
			'main deletes' => [ true ],
		];
	}
}
