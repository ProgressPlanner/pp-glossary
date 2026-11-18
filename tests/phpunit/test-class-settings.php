<?php
/**
 * Tests for the settings.
 *
 * @package PP_Glossary
 */

namespace PP_Glossary\Tests;

use PP_Glossary\Settings;

/**
 * Covers reading, updating and sanitizing the plugin settings.
 */
class Settings_Test extends Glossary_TestCase {

	/**
	 * Defaults apply when nothing has been saved.
	 */
	public function test_defaults() {
		$this->assertSame(
			[
				'glossary_page'       => 0,
				'excluded_tags'       => [ 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ],
				'excluded_post_types' => [],
				'db_version'          => PP_GLOSSARY_VERSION,
			],
			Settings::get_settings()
		);
		$this->assertSame( 0, Settings::get_glossary_page_id() );
		$this->assertSame( '', Settings::get_glossary_page_url() );
		$this->assertSame( [], Settings::get_excluded_post_types() );
	}

	/**
	 * Saved values are merged over the defaults.
	 */
	public function test_update_setting() {
		Settings::update_setting( 'excluded_post_types', [ 'page' ] );

		$this->assertSame( [ 'page' ], Settings::get_excluded_post_types() );
		$this->assertSame( [ 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ], Settings::get_excluded_tags() );
	}

	/**
	 * The glossary page URL is the permalink of the configured page.
	 */
	public function test_glossary_page_url() {
		$page_id = $this->create_glossary_page();

		$this->assertSame( $page_id, Settings::get_glossary_page_id() );
		$this->assertSame( get_permalink( $page_id ), Settings::get_glossary_page_url() );
	}

	/**
	 * Input is sanitized on save.
	 */
	public function test_sanitize_settings() {
		$this->assertSame(
			[
				'glossary_page'       => 12,
				'excluded_tags'       => [ 'a', 'h2', 'blockquote', 'pre' ],
				'excluded_post_types' => [ 'post', 'mytype' ],
			],
			Settings::sanitize_settings(
				[
					'glossary_page'       => '12abc',
					'excluded_tags'       => ' A, h2 ,, Block-Quote, pre!, ?',
					'excluded_post_types' => [ 'post', 'My Type!' ],
				]
			)
		);
	}

	/**
	 * With no post type checkbox ticked, the list is saved empty.
	 */
	public function test_sanitize_settings_without_post_types() {
		$this->assertSame(
			[ 'excluded_post_types' => [] ],
			Settings::sanitize_settings( [] )
		);
	}
}
