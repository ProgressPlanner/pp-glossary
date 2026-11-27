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
				'glossary_page'            => 0,
				'excluded_tags'            => [ 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ],
				'excluded_post_types'      => [],
				'delete_data_on_uninstall' => false,
				'db_version'               => PP_GLOSSARY_VERSION,
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
				'glossary_page'            => 12,
				'excluded_tags'            => [ 'a', 'h2', 'blockquote', 'pre' ],
				'excluded_post_types'      => [ 'post', 'mytype' ],
				'delete_data_on_uninstall' => false,
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
			[
				'excluded_post_types'      => [],
				'delete_data_on_uninstall' => false,
			],
			Settings::sanitize_settings( [] )
		);
	}

	/**
	 * Explicit checkbox consent survives sanitization and option storage.
	 */
	public function test_save_uninstall_consent() {
		foreach ( [ '1', 1, true ] as $value ) {
			$settings = Settings::sanitize_settings( [ 'delete_data_on_uninstall' => $value ] );
			update_option( Settings::OPTION_NAME, Settings::sanitize_settings( $settings ) );

			$this->assertTrue( get_option( Settings::OPTION_NAME )['delete_data_on_uninstall'] );
		}
	}

	/**
	 * Saving an unchecked or invalid value clears previous deletion consent.
	 */
	public function test_clear_uninstall_consent() {
		foreach ( [ [], [ 'delete_data_on_uninstall' => '0' ], [ 'delete_data_on_uninstall' => 'false' ], [ 'delete_data_on_uninstall' => [ '1' ] ] ] as $input ) {
			update_option( Settings::OPTION_NAME, [ 'delete_data_on_uninstall' => true ] );
			update_option( Settings::OPTION_NAME, Settings::sanitize_settings( $input ) );

			$this->assertFalse( get_option( Settings::OPTION_NAME )['delete_data_on_uninstall'] );
		}
	}
}
