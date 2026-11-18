<?php
/**
 * Tests for the glossary list block and the post type behind it.
 *
 * @package PP_Glossary
 */

namespace PP_Glossary\Tests;

use PP_Glossary\Blocks;

/**
 * Covers the registration and server-side rendering of the glossary.
 */
class Blocks_Test extends Glossary_TestCase {

	/**
	 * The post type has no public pages of its own.
	 */
	public function test_post_type_is_registered() {
		$post_type = get_post_type_object( 'pp_glossary' );

		$this->assertNotNull( $post_type );
		$this->assertFalse( $post_type->publicly_queryable );
		$this->assertFalse( $post_type->has_archive );
		$this->assertTrue( $post_type->exclude_from_search );
		$this->assertFalse( post_type_supports( 'pp_glossary', 'editor' ) );
	}

	/**
	 * The block is registered with a render callback.
	 */
	public function test_block_is_registered() {
		$block = \WP_Block_Type_Registry::get_instance()->get_registered( 'pp-glossary/glossary-list' );

		$this->assertNotNull( $block );
		$this->assertSame( [ Blocks::class, 'render_glossary_list_block' ], $block->render_callback );
	}

	/**
	 * The block lists every entry, grouped by first letter.
	 */
	public function test_block_lists_entries_by_letter() {
		$this->create_glossary_page();
		$output = Blocks::render_glossary_list_block();

		foreach ( [ 'a', 'c', 'h', 'l' ] as $letter ) {
			$this->assertStringContainsString( '<a href="#letter-' . $letter . '">', $output );
			$this->assertStringContainsString( 'id="letter-' . $letter . '"', $output );
		}
		$this->assertSame( 4, substr_count( $output, '<section class="glossary-letter-section"' ) );

		foreach ( [ 'api', 'cumulative-layout-shift', 'hidden-term', 'largest-contentful-paint' ] as $slug ) {
			$this->assertStringContainsString( '<article id="' . $slug . '" class="glossary-entry"', $output );
		}

		// Sections are in alphabetical order.
		$this->assertLessThan( strpos( $output, 'id="letter-c"' ), strpos( $output, 'id="letter-a"' ) );
		$this->assertLessThan( strpos( $output, 'id="letter-l"' ), strpos( $output, 'id="letter-h"' ) );
	}

	/**
	 * Entries show their synonyms and the long description, or the short one as fallback.
	 */
	public function test_block_entry_content() {
		$url    = esc_url( get_permalink( $this->create_glossary_page() ) );
		$output = Blocks::render_glossary_list_block();

		$this->assertStringContainsString( '<span>CLS, layout shift</span>', $output );
		$this->assertStringContainsString( '<p>Detailed text about <strong>visual stability</strong>.</p>', $output );
		$this->assertStringNotContainsString( 'A metric measuring visual stability.', $output );

		// The short description is the fallback, with other terms linked.
		$this->assertStringContainsString(
			'Measures loading speed. See also <a href="' . $url . '#cumulative-layout-shift" class="pp-glossary-link">Cumulative Layout Shift</a>.',
			$output
		);
	}

	/**
	 * Without Yoast SEO the block carries Microdata.
	 */
	public function test_block_microdata() {
		if ( defined( 'WPSEO_VERSION' ) ) {
			$this->markTestSkipped( 'Microdata is only output without Yoast SEO.' );
		}

		$this->create_glossary_page();
		$output = Blocks::render_glossary_list_block();

		$this->assertStringContainsString( 'itemscope itemtype="https://schema.org/DefinedTermSet"', $output );
		$this->assertStringContainsString( '<meta itemprop="name" content="Glossary">', $output );
		$this->assertSame( 4, substr_count( $output, 'itemtype="https://schema.org/DefinedTerm"' ) );
		$this->assertStringContainsString( '<meta itemprop="alternateName" content="CLS">', $output );
		$this->assertStringContainsString( '<meta itemprop="alternateName" content="layout shift">', $output );
	}

	/**
	 * Edit links are only shown to users who can edit entries.
	 */
	public function test_block_edit_links() {
		$this->assertStringNotContainsString( 'glossary-edit-link', Blocks::render_glossary_list_block() );
	}
}
