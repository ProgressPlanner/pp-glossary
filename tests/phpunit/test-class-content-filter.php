<?php
/**
 * Tests for the content filter.
 *
 * @package PP_Glossary
 */

namespace PP_Glossary\Tests;

use PP_Glossary\Content_Filter;
use PP_Glossary\Settings;

/**
 * Covers the automatic linking of glossary terms in post content.
 */
class Content_Filter_Test extends Glossary_TestCase {

	/**
	 * The filter is hooked into the_content.
	 */
	public function test_filter_is_hooked() {
		$this->assertSame( 20, has_filter( 'the_content', [ Content_Filter::class, 'filter_content' ] ) );
	}

	/**
	 * Content without glossary terms is returned untouched.
	 */
	public function test_content_without_terms_is_unchanged() {
		$content = '<p>Nothing to see here.</p>';

		$this->assertSame( $content, Content_Filter::filter_content( $content ) );
	}

	/**
	 * A term is wrapped in a dfn and gets a popover appended to the content.
	 */
	public function test_term_gets_button_and_popover() {
		$output = Content_Filter::filter_content( '<p>Your API is slow.</p>' );
		$hash   = md5( 'api' );

		$this->assertStringContainsString(
			'<p>Your <dfn id="dfn-' . $hash . '-1" class="pp-glossary-term" style="anchor-name: --dfn-' . $hash . '-1;">'
			. '<span data-glossary-popover="pop-' . $hash . '-1" tabindex="0" role="button" aria-expanded="false">API</span></dfn> is slow.</p>',
			$output
		);
		$this->assertStringContainsString(
			'<aside id="pop-' . $hash . '-1" popover="auto" role="tooltip" aria-labelledby="dfn-' . $hash . '-1" style="position-anchor: --dfn-' . $hash . '-1;">',
			$output
		);
		$this->assertStringContainsString( '<strong class="glossary-title" aria-hidden="true">API</strong>', $output );
		$this->assertStringContainsString( '<p>Application Programming Interface.</p>', $output );

		// The popover comes after the content.
		$this->assertGreaterThan( strpos( $output, '</p>' ), strpos( $output, '<aside' ) );
		$this->assertTrue( Content_Filter::$terms_found_on_page );
	}

	/**
	 * Only the first occurrence of a term is linked.
	 */
	public function test_only_first_occurrence_is_linked() {
		$output = Content_Filter::filter_content( '<p>LCP matters.</p><p>Really, LCP matters.</p>' );

		$this->assertSame( 1, substr_count( $output, '<dfn ' ) );
		$this->assertSame( 1, substr_count( $output, '<aside ' ) );
		$this->assertStringContainsString( '<p>Really, LCP matters.</p>', $output );
	}

	/**
	 * Matching is case-insensitive by default and keeps the casing of the content.
	 */
	public function test_case_insensitive_match_keeps_content_casing() {
		$output = Content_Filter::filter_content( '<p>Watch your largest contentful paint.</p>' );

		$this->assertStringContainsString( 'aria-expanded="false">largest contentful paint</span>', $output );
		$this->assertStringContainsString( '<strong class="glossary-title" aria-hidden="true">Largest Contentful Paint</strong>', $output );
	}

	/**
	 * Case-sensitive entries only match the exact casing.
	 */
	public function test_case_sensitive_entry() {
		$content = '<p>An api in lowercase.</p>';
		$this->assertSame( $content, Content_Filter::filter_content( $content ) );

		$output = Content_Filter::filter_content( '<p>An api, or rather an API.</p>' );
		$this->assertStringContainsString( 'An api, or rather an <dfn ', $output );
	}

	/**
	 * Terms only match on word boundaries.
	 */
	public function test_word_boundaries() {
		$content = '<p>Many APIs and a RAPID one.</p>';

		$this->assertSame( $content, Content_Filter::filter_content( $content ) );
	}

	/**
	 * A synonym links to its entry.
	 */
	public function test_synonym_is_linked() {
		$output = Content_Filter::filter_content( '<p>Reduce your CLS today.</p>' );

		$this->assertStringContainsString( 'aria-expanded="false">CLS</span>', $output );
		$this->assertStringContainsString( '<strong class="glossary-title" aria-hidden="true">Cumulative Layout Shift</strong>', $output );
	}

	/**
	 * An entry is linked once, even when both its title and a synonym occur.
	 */
	public function test_entry_is_linked_once_across_title_and_synonyms() {
		$output = Content_Filter::filter_content( '<p>CLS is short for Cumulative Layout Shift.</p>' );

		$this->assertSame( 1, substr_count( $output, '<dfn ' ) );
		$this->assertStringContainsString( '<p>CLS is short for <dfn ', $output );
	}

	/**
	 * Entries with auto-linking disabled are never linked.
	 */
	public function test_disable_autolink() {
		$content = '<p>This Hidden Term stays plain.</p>';

		$this->assertSame( $content, Content_Filter::filter_content( $content ) );
	}

	/**
	 * Terms inside excluded tags are skipped; the next occurrence is linked instead.
	 */
	public function test_excluded_tags_are_skipped() {
		$output = Content_Filter::filter_content(
			'<h2>About LCP</h2><p>See <a href="/lcp">LCP docs</a>.</p><p>So, LCP it is.</p>'
		);

		$this->assertStringContainsString( '<h2>About LCP</h2><p>See <a href="/lcp">LCP docs</a>.</p><p>So, <dfn ', $output );
		$this->assertSame( 1, substr_count( $output, '<dfn ' ) );
	}

	/**
	 * A term that only occurs inside excluded tags is not linked at all.
	 */
	public function test_term_only_in_excluded_tag() {
		$content = '<h3>LCP</h3><p>Text.</p>';

		$this->assertSame( $content, Content_Filter::filter_content( $content ) );
	}

	/**
	 * Terms inside HTML attributes are left alone.
	 */
	public function test_terms_in_attributes_are_skipped() {
		$content = '<p><img src="/lcp.png" alt="LCP chart" title="LCP"></p>';

		$this->assertSame( $content, Content_Filter::filter_content( $content ) );
	}

	/**
	 * Each linked term gets its own unique IDs.
	 */
	public function test_multiple_terms_get_unique_ids() {
		$output = Content_Filter::filter_content( '<p>API first, then LCP.</p>' );

		$this->assertSame( 2, substr_count( $output, '<dfn ' ) );
		$this->assertSame( 2, substr_count( $output, '<aside ' ) );

		preg_match_all( '/<dfn id="([^"]+)"/', $output, $matches );
		$this->assertCount( 2, array_unique( $matches[1] ) );

		foreach ( $matches[1] as $dfn_id ) {
			$this->assertStringContainsString( 'aria-labelledby="' . $dfn_id . '"', $output );
		}
	}

	/**
	 * Without a glossary page there is no "Read more" link.
	 */
	public function test_no_read_more_link_without_glossary_page() {
		$output = Content_Filter::filter_content( '<p>Reduce your CLS today.</p>' );

		$this->assertStringNotContainsString( 'Read more about', $output );
		$this->assertStringContainsString( '<span class="pp-glossary-sr-only">Definition of Cumulative Layout Shift.</span>', $output );
	}

	/**
	 * With a glossary page, entries with a long description link to it.
	 */
	public function test_read_more_link_points_to_glossary_page() {
		$page_id = $this->create_glossary_page();
		$output  = Content_Filter::filter_content( '<p>Reduce your CLS today.</p>' );

		$this->assertStringContainsString(
			'<a href="' . esc_url( get_permalink( $page_id ) . '#cumulative-layout-shift' ) . '" aria-label="Read more about Cumulative Layout Shift on the glossary page">',
			$output
		);
	}

	/**
	 * Entries without a long description get no "Read more" link.
	 */
	public function test_no_read_more_link_without_long_description() {
		$this->create_glossary_page();
		$output = Content_Filter::filter_content( '<p>Your API is slow.</p>' );

		$this->assertStringNotContainsString( 'Read more about', $output );
	}

	/**
	 * Other glossary terms in a popover's short description link to the glossary page.
	 */
	public function test_terms_in_short_description_are_linked() {
		$page_id = $this->create_glossary_page();
		$output  = Content_Filter::filter_content( '<p>LCP matters.</p>' );

		$this->assertStringContainsString(
			'See also <a href="' . esc_url( get_permalink( $page_id ) ) . '#cumulative-layout-shift" class="pp-glossary-link">Cumulative Layout Shift</a>.',
			$output
		);
	}

	/**
	 * Excluded post types are left alone.
	 */
	public function test_excluded_post_type_setting() {
		$content = '<p>Your API is slow.</p>';
		$this->go_to( get_permalink( self::factory()->post->create() ) );
		setup_postdata( get_post() );

		$this->assertNotSame( $content, Content_Filter::filter_content( $content ) );

		Settings::update_setting( 'excluded_post_types', [ 'post' ] );
		$this->assertSame( $content, Content_Filter::filter_content( $content ) );
	}

	/**
	 * Post types can be excluded with a filter.
	 */
	public function test_disabled_post_types_filter() {
		$content = '<p>Your API is slow.</p>';
		$this->go_to( get_permalink( self::factory()->post->create() ) );

		add_filter(
			'pp_glossary_disabled_post_types',
			static function () {
				return [ 'post' ];
			}
		);

		$this->assertSame( $content, Content_Filter::filter_content( $content ) );
	}

	/**
	 * The glossary page itself is not auto-linked, but does load the assets.
	 */
	public function test_glossary_page_is_not_filtered() {
		$content = '<p>Your API is slow.</p>';
		$page_id = $this->create_glossary_page();
		$this->go_to( get_permalink( $page_id ) );

		Content_Filter::$terms_found_on_page = false;

		$this->assertSame( $content, Content_Filter::filter_content( $content ) );
		$this->assertTrue( Content_Filter::$terms_found_on_page );
	}

	/**
	 * Feeds are not filtered.
	 */
	public function test_feed_is_not_filtered() {
		$content = '<p>Your API is slow.</p>';
		$this->go_to( get_feed_link() );

		$this->assertSame( $content, Content_Filter::filter_content( $content ) );
	}
}
