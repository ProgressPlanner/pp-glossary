<?php
/**
 * Tests for the helper functions.
 *
 * @package PP_Glossary
 */

namespace PP_Glossary\Tests;

use PP_Glossary\Settings;

/**
 * Covers includes/functions.php.
 */
class Functions_Test extends Glossary_TestCase {

	/**
	 * The string wrappers are multibyte safe.
	 */
	public function test_multibyte_string_wrappers() {
		$this->assertSame( 'élan', pp_glossary_strtolower( 'ÉLAN' ) );
		$this->assertSame( 'ÉLAN', pp_glossary_strtoupper( 'élan' ) );
		$this->assertSame( 'É', pp_glossary_substr( 'Élan', 0, 1 ) );
		$this->assertSame( 'lan', pp_glossary_substr( 'Élan', 1 ) );
	}

	/**
	 * Synonyms are stripped of empty values and re-indexed.
	 */
	public function test_filter_synonyms() {
		$this->assertSame( [ 'CLS', 'layout shift' ], pp_glossary_filter_synonyms( [ 'CLS', '', 'layout shift' ] ) );
		$this->assertSame( [], pp_glossary_filter_synonyms( 'not an array' ) );
		$this->assertSame( [], pp_glossary_filter_synonyms( null ) );
	}

	/**
	 * Entries are sorted by their longest term, longest first.
	 */
	public function test_sort_by_term_length() {
		$sorted = pp_glossary_sort_by_term_length(
			[
				[ 'terms' => [ 'CLS' ] ],
				[ 'terms' => [] ],
				[ 'terms' => [ 'LCP', 'Largest Contentful Paint' ] ],
				[ 'terms' => [ 'Layout shift' ] ],
			]
		);

		$this->assertSame(
			[
				[ 'LCP', 'Largest Contentful Paint' ],
				[ 'Layout shift' ],
				[ 'CLS' ],
				[],
			],
			array_column( $sorted, 'terms' )
		);
	}

	/**
	 * Excluded elements become their own parts so they can be skipped.
	 */
	public function test_split_by_excluded_tags() {
		$parts = pp_glossary_split_by_excluded_tags(
			'<p>Before <a href="/x">link text</a> middle</p><H2 class="t">Heading</H2><p>After</p>',
			[ 'a', 'h2' ]
		);

		$this->assertSame(
			[
				'<p>Before ',
				'<a href="/x">link text</a>',
				' middle</p>',
				'<H2 class="t">Heading</H2>',
				'<p>After</p>',
			],
			$parts
		);
	}

	/**
	 * Text without excluded elements stays in one piece.
	 */
	public function test_split_by_excluded_tags_without_matches() {
		$text = '<p>Just an <abbr>abbr</abbr> here.</p>';

		// The `a` tag must not match `<abbr>`.
		$this->assertSame( [ $text ], pp_glossary_split_by_excluded_tags( $text, [ 'a', 'h2' ] ) );
	}

	/**
	 * The excluded tags default to links and headings.
	 */
	public function test_get_excluded_tags_defaults() {
		$this->assertSame( [ 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ], pp_glossary_get_excluded_tags() );
	}

	/**
	 * The excluded tags come from the settings and can be filtered.
	 */
	public function test_get_excluded_tags_setting_and_filter() {
		Settings::update_setting( 'excluded_tags', [ 'a', 'blockquote' ] );
		$this->assertSame( [ 'a', 'blockquote' ], pp_glossary_get_excluded_tags() );

		add_filter(
			'pp_glossary_excluded_tags',
			static function ( $tags ) {
				$tags[] = 'code';
				return $tags;
			}
		);
		$this->assertSame( [ 'a', 'blockquote', 'code' ], pp_glossary_get_excluded_tags() );
	}

	/**
	 * All published entries are returned with their full data, ordered by title.
	 */
	public function test_get_entries() {
		$entries = pp_glossary_get_entries();

		$this->assertSame(
			[ 'API', 'Cumulative Layout Shift', 'Hidden Term', 'Largest Contentful Paint' ],
			array_column( $entries, 'title' )
		);

		$entry = $this->get_entry( 'Cumulative Layout Shift' );
		$this->assertSame( 'cumulative-layout-shift', $entry['slug'] );
		$this->assertSame( [ 'CLS', 'layout shift' ], $entry['synonyms'] );
		$this->assertSame( [ 'Cumulative Layout Shift', 'CLS', 'layout shift' ], $entry['terms'] );
		$this->assertSame( 'A metric measuring visual stability.', $entry['short_description'] );
		$this->assertFalse( $entry['case_sensitive'] );
		$this->assertFalse( $entry['disable_autolink'] );
		$this->assertIsInt( $entry['id'] );
	}

	/**
	 * Linkable entries skip disabled ones and put the longest term first.
	 */
	public function test_get_linkable_entries() {
		$this->assertSame(
			[ 'Largest Contentful Paint', 'Cumulative Layout Shift', 'API' ],
			array_column( pp_glossary_get_linkable_entries(), 'title' )
		);
	}
}
