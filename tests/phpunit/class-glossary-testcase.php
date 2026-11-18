<?php
/**
 * Shared base class for the glossary tests.
 *
 * @package PP_Glossary
 */

namespace PP_Glossary\Tests;

use PP_Glossary\Settings;

/**
 * Base test case that provides one fixed set of glossary entries.
 *
 * `pp_glossary_get_entries()` and `pp_glossary_get_linkable_entries()` cache
 * their result in a function-level static, which cannot be reset from the
 * outside. The whole test run therefore shares one set of entries: they are
 * created and the caches are primed the first time any test sets up. Tests
 * must not expect entries they create themselves to show up in those helpers.
 */
abstract class Glossary_TestCase extends \WP_UnitTestCase {

	/**
	 * Whether the entry caches have been primed.
	 *
	 * @var bool
	 */
	private static $primed = false;

	/**
	 * The fixed set of glossary entries, keyed by title.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected static function fixture_entries(): array {
		return [
			'Cumulative Layout Shift'  => [
				'short_description' => 'A metric measuring visual stability.',
				'long_description'  => '<p>Detailed text about <strong>visual stability</strong>.</p>',
				'synonyms'          => [ 'CLS', '', 'layout shift' ],
				'case_sensitive'    => false,
				'disable_autolink'  => false,
			],
			'Largest Contentful Paint' => [
				'short_description' => 'Measures loading speed. See also Cumulative Layout Shift.',
				'long_description'  => '',
				'synonyms'          => [ 'LCP' ],
				'case_sensitive'    => false,
				'disable_autolink'  => false,
			],
			'API'                      => [
				'short_description' => 'Application Programming Interface.',
				'long_description'  => '',
				'synonyms'          => [],
				'case_sensitive'    => true,
				'disable_autolink'  => false,
			],
			'Hidden Term'              => [
				'short_description' => 'Listed in the glossary, never linked.',
				'long_description'  => '',
				'synonyms'          => [],
				'case_sensitive'    => false,
				'disable_autolink'  => true,
			],
		];
	}

	/**
	 * Create the fixture entries once and prime the static caches.
	 */
	public function set_up() {
		parent::set_up();

		if ( self::$primed ) {
			return;
		}

		foreach ( self::fixture_entries() as $title => $data ) {
			$post_id = self::factory()->post->create(
				[
					'post_type'   => 'pp_glossary',
					'post_title'  => $title,
					'post_status' => 'publish',
				]
			);
			update_post_meta( $post_id, '_pp_glossary_data', $data );
		}

		// A draft entry must never be picked up.
		self::factory()->post->create(
			[
				'post_type'   => 'pp_glossary',
				'post_title'  => 'Draft Term',
				'post_status' => 'draft',
			]
		);

		pp_glossary_get_linkable_entries();
		self::$primed = true;
	}

	/**
	 * Get a cached glossary entry by its title.
	 *
	 * @param string $title The entry title.
	 * @return array<string, mixed>
	 */
	protected function get_entry( string $title ): array {
		foreach ( pp_glossary_get_entries() as $entry ) {
			if ( $entry['title'] === $title ) {
				return $entry;
			}
		}

		$this->fail( "Fixture entry '{$title}' not found." );
	}

	/**
	 * Create a page and configure it as the glossary page.
	 *
	 * @return int The page ID.
	 */
	protected function create_glossary_page(): int {
		$page_id = self::factory()->post->create(
			[
				'post_type'    => 'page',
				'post_title'   => 'Glossary',
				'post_excerpt' => 'All the terms.',
			]
		);
		Settings::update_setting( 'glossary_page', $page_id );

		return $page_id;
	}
}
