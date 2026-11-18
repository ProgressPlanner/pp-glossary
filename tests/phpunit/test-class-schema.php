<?php
/**
 * Tests for the Schema.org output.
 *
 * @package PP_Glossary
 */

namespace PP_Glossary\Tests;

use PP_Glossary\Schema;

/**
 * Covers the Yoast SEO graph piece and the Microdata helpers.
 */
class Schema_Test extends Glossary_TestCase {

	/**
	 * Outside the glossary page the graph is left alone.
	 */
	public function test_graph_is_unchanged_outside_glossary_page() {
		$graph = [ [ '@type' => 'WebPage' ] ];
		$this->assertSame( $graph, Schema::add_to_yoast_schema_graph( $graph ) );

		$this->create_glossary_page();
		$this->go_to( get_permalink( self::factory()->post->create() ) );
		$this->assertSame( $graph, Schema::add_to_yoast_schema_graph( $graph ) );
	}

	/**
	 * On the glossary page a DefinedTermSet with all entries is added.
	 */
	public function test_defined_term_set_is_added_on_glossary_page() {
		$page_id = $this->create_glossary_page();
		$url     = get_permalink( $page_id );
		$this->go_to( $url );

		$graph = Schema::add_to_yoast_schema_graph( [ [ '@type' => 'WebPage' ] ] );

		$this->assertCount( 2, $graph );
		$set = $graph[1];

		$this->assertSame( 'DefinedTermSet', $set['@type'] );
		$this->assertSame( $url . '#glossary', $set['@id'] );
		$this->assertSame( 'Glossary', $set['name'] );
		$this->assertSame( 'All the terms.', $set['description'] );
		$this->assertSame(
			[
				'@type' => 'WebPage',
				'@id'   => $url,
			],
			$set['isPartOf']
		);

		// Entries with auto-linking disabled are still part of the glossary.
		$this->assertSame(
			[ 'API', 'Cumulative Layout Shift', 'Hidden Term', 'Largest Contentful Paint' ],
			array_column( $set['hasDefinedTerm'], 'name' )
		);

		$this->assertSame(
			[
				'@type'       => 'DefinedTerm',
				'@id'         => $url . '#api',
				'name'        => 'API',
				'description' => 'Application Programming Interface.',
				'url'         => $url . '#api',
			],
			$set['hasDefinedTerm'][0]
		);

		// The long description wins, stripped of HTML; synonyms become alternate names.
		$this->assertSame(
			[
				'@type'         => 'DefinedTerm',
				'@id'           => $url . '#cumulative-layout-shift',
				'name'          => 'Cumulative Layout Shift',
				'description'   => 'Detailed text about visual stability.',
				'url'           => $url . '#cumulative-layout-shift',
				'alternateName' => [ 'CLS', 'layout shift' ],
			],
			$set['hasDefinedTerm'][1]
		);
	}

	/**
	 * Without Yoast SEO the helpers output Microdata attributes.
	 */
	public function test_microdata_helpers() {
		if ( defined( 'WPSEO_VERSION' ) ) {
			$this->markTestSkipped( 'Microdata is only output without Yoast SEO.' );
		}

		$page_id = $this->create_glossary_page();

		$this->assertSame(
			' itemscope itemtype="https://schema.org/DefinedTermSet" itemid="' . esc_attr( get_permalink( $page_id ) . '#glossary' ) . '"',
			Schema::get_microdata_attributes( $page_id )
		);
		$this->assertSame(
			' itemprop="hasDefinedTerm" itemscope itemtype="https://schema.org/DefinedTerm"',
			Schema::get_entry_microdata_attributes()
		);
		$this->assertSame( ' itemprop="name"', Schema::get_itemprop( 'name' ) );
	}
}
