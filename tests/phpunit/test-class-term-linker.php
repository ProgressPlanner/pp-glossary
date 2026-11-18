<?php
/**
 * Tests for the term linker.
 *
 * @package PP_Glossary
 */

namespace PP_Glossary\Tests;

use PP_Glossary\Term_Linker;

/**
 * Covers linking glossary terms inside descriptions.
 */
class Term_Linker_Test extends Glossary_TestCase {

	/**
	 * Without a glossary page there is nothing to link to.
	 */
	public function test_text_is_unchanged_without_glossary_page() {
		$this->assertSame( 'An API call.', Term_Linker::link_terms_in_text( 'An API call.' ) );
	}

	/**
	 * Empty text is returned as is.
	 */
	public function test_empty_text() {
		$this->create_glossary_page();

		$this->assertSame( '', Term_Linker::link_terms_in_text( '' ) );
	}

	/**
	 * The first occurrence of each term links to its anchor on the glossary page.
	 */
	public function test_first_occurrence_is_linked() {
		$url = esc_url( get_permalink( $this->create_glossary_page() ) );

		$this->assertSame(
			'An <a href="' . $url . '#api" class="pp-glossary-link">API</a> call improves lcp. Another API call.',
			Term_Linker::link_terms_in_text( 'An API call improves lcp. Another API call.', $this->get_entry( 'Largest Contentful Paint' )['id'] )
		);
	}

	/**
	 * Synonyms link to the entry they belong to, keeping the casing of the text.
	 */
	public function test_synonym_is_linked() {
		$url = esc_url( get_permalink( $this->create_glossary_page() ) );

		$this->assertSame(
			'A <a href="' . $url . '#cumulative-layout-shift" class="pp-glossary-link">Layout Shift</a> hurts.',
			Term_Linker::link_terms_in_text( 'A Layout Shift hurts.' )
		);
	}

	/**
	 * The excluded entry is not linked, to prevent self-links.
	 */
	public function test_excluded_entry_is_not_linked() {
		$this->create_glossary_page();

		$this->assertSame(
			'An API call.',
			Term_Linker::link_terms_in_text( 'An API call.', $this->get_entry( 'API' )['id'] )
		);
	}

	/**
	 * Entries with auto-linking disabled and case mismatches are not linked.
	 */
	public function test_disabled_and_case_sensitive_entries() {
		$this->create_glossary_page();
		$text = 'A Hidden Term and an api.';

		$this->assertSame( $text, Term_Linker::link_terms_in_text( $text ) );
	}

	/**
	 * Existing links and headings are not linked again.
	 */
	public function test_excluded_tags_are_skipped() {
		$this->create_glossary_page();
		$text = '<h4>API</h4> See the <a href="/docs">API docs</a>.';

		$this->assertSame( $text, Term_Linker::link_terms_in_text( $text ) );
	}
}
