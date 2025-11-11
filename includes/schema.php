<?php
/**
 * Schema.org Integration for Glossary
 *
 * @package PP_Glossary
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class PP_Glossary_Schema
 */
class PP_Glossary_Schema {

	/**
	 * Initialize the schema integration
	 */
	public static function init() {
		// Check if Yoast SEO is active
		if ( defined( 'WPSEO_VERSION' ) ) {
			add_filter( 'wpseo_schema_graph', array( __CLASS__, 'add_to_yoast_schema_graph' ), 10, 2 );
		}
	}

	/**
	 * Add glossary entries to Yoast SEO schema graph
	 *
	 * @param array  $graph  The schema graph array.
	 * @param object $context The schema context object.
	 * @return array Modified schema graph.
	 */
	public static function add_to_yoast_schema_graph( $graph, $context ) {
		// Only add on the glossary page
		$glossary_page_id = PP_Glossary_Settings::get_glossary_page_id();
		if ( ! $glossary_page_id || ! is_page( $glossary_page_id ) ) {
			return $graph;
		}

		// Get all glossary entries
		$entries = self::get_glossary_entries_for_schema();

		if ( empty( $entries ) ) {
			return $graph;
		}

		// Create DefinedTermSet
		$defined_term_set = array(
			'@type'          => 'DefinedTermSet',
			'@id'            => get_permalink( $glossary_page_id ) . '#glossary',
			"isPartOf"       => array(
				'@type' => 'WebPage',
				'@id'   => get_permalink( $glossary_page_id ),
			),
			'name'           => get_the_title( $glossary_page_id ),
			'hasDefinedTerm' => array(),
		);

		$excerpt = wp_strip_all_tags( get_the_excerpt( $glossary_page_id ) );
		if ( ! empty( $excerpt ) ) {
			$defined_term_set['description'] = $excerpt;
		}

		// Add each entry as a DefinedTerm
		foreach ( $entries as $entry ) {
			$defined_term = self::create_defined_term_schema( $entry, $glossary_page_id );
			$defined_term_set['hasDefinedTerm'][] = $defined_term;
		}

		// Add to graph
		$graph[] = $defined_term_set;

		return $graph;
	}

	/**
	 * Get glossary entries formatted for schema
	 *
	 * @return array Array of glossary entries.
	 */
	private static function get_glossary_entries_for_schema() {
		$entries = array();

		$query = new WP_Query(
			array(
				'post_type'      => 'pp_glossary',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();

				$entries[] = array(
					'id'                => $post_id,
					'slug'              => sanitize_title( get_the_title() ),
					'title'             => get_the_title(),
					'short_description' => get_post_meta( $post_id, '_pp_glossary_short_description', true ),
					'long_description'  => get_post_meta( $post_id, '_pp_glossary_long_description', true ),
					'synonyms'          => get_post_meta( $post_id, '_pp_glossary_synonyms', true ),
				);
			}
			wp_reset_postdata();
		}

		return $entries;
	}

	/**
	 * Create a DefinedTerm schema object
	 *
	 * @param array $entry           The glossary entry data.
	 * @param int   $glossary_page_id The glossary page ID.
	 * @return array Schema.org DefinedTerm object.
	 */
	private static function create_defined_term_schema( $entry, $glossary_page_id ) {
		$glossary_url = get_permalink( $glossary_page_id );
		$entry_url    = $glossary_url . '#' . $entry['slug'];

		$defined_term = array(
			'@type'       => 'DefinedTerm',
			'@id'         => $entry_url,
			'name'        => $entry['title'],
			'description' => $entry['short_description'],
			'url'         => $entry_url,
		);

		// Add detailed description if available
		if ( ! empty( $entry['long_description'] ) ) {
			// Strip HTML tags for schema.
			$defined_term['description'] = wp_strip_all_tags( $entry['long_description'] );
		}

		// Add synonyms as alternateName
		if ( ! empty( $entry['synonyms'] ) && is_array( $entry['synonyms'] ) ) {
			$defined_term['alternateName'] = implode( ', ', $entry['synonyms'] );
		}

		return $defined_term;
	}

	/**
	 * Generate Microdata markup for glossary entries
	 *
	 * This is used when Yoast SEO is not active
	 *
	 * @param array $entries          Array of glossary entries.
	 * @param int   $glossary_page_id The glossary page ID.
	 * @return string Microdata attributes and invisible schema markup.
	 */
	public static function get_microdata_attributes( $entries, $glossary_page_id ) {
		// If Yoast SEO is active, don't output Microdata (use their JSON-LD instead)
		if ( defined( 'WPSEO_VERSION' ) ) {
			return '';
		}

		$glossary_url = get_permalink( $glossary_page_id );

		$output = sprintf(
			' itemscope itemtype="https://schema.org/DefinedTermSet" itemid="%s"',
			esc_attr( $glossary_url . '#glossary' )
		);

		return $output;
	}

	/**
	 * Generate Microdata for a single glossary entry
	 *
	 * @param array $entry The glossary entry data.
	 * @return string Microdata attributes.
	 */
	public static function get_entry_microdata_attributes( $entry ) {
		// If Yoast SEO is active, don't output Microdata
		if ( defined( 'WPSEO_VERSION' ) ) {
			return '';
		}

		return ' itemprop="hasDefinedTerm" itemscope itemtype="https://schema.org/DefinedTerm"';
	}

	/**
	 * Get itemprop attribute for entry elements
	 *
	 * @param string $prop The property name.
	 * @return string Itemprop attribute or empty string if Yoast is active.
	 */
	public static function get_itemprop( $prop ) {
		// If Yoast SEO is active, don't output Microdata
		if ( defined( 'WPSEO_VERSION' ) ) {
			return '';
		}

		return sprintf( ' itemprop="%s"', esc_attr( $prop ) );
	}
}
