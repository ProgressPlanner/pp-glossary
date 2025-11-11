<?php
/**
 * Content Filter for Glossary Terms
 *
 * @package PP_Glossary
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class PP_Glossary_Content_Filter
 */
class PP_Glossary_Content_Filter {

	/**
	 * Counter for unique IDs
	 *
	 * @var int
	 */
	private static $popover_counter = 0;

	/**
	 * Array to store popovers to be appended
	 *
	 * @var array
	 */
	private static $popovers = array();

	/**
	 * Flag to track if helper text has been added
	 *
	 * @var bool
	 */
	private static $helper_added = false;

	/**
	 * Initialize the content filter
	 */
	public static function init() {
		add_filter( 'the_content', array( __CLASS__, 'filter_content' ), 20 );
	}

	/**
	 * Filter content to replace glossary terms
	 *
	 * @param string $content The post content.
	 * @return string Modified content.
	 */
	public static function filter_content( $content ) {
		// Reset counters and storage for each content piece
		self::$popover_counter = 0;
		self::$popovers        = array();
		self::$helper_added    = false;

		// Don't process on glossary entries themselves
		if ( is_singular( 'pp_glossary' ) ) {
			return $content;
		}

		// Don't process on the glossary page
		$glossary_page_id = PP_Glossary_Settings::get_glossary_page_id();
		if ( $glossary_page_id && is_page( $glossary_page_id ) ) {
			return $content;
		}

		// Get all glossary entries
		$glossary_entries = self::get_glossary_entries();

		if ( empty( $glossary_entries ) ) {
			return $content;
		}

		// Process each glossary entry
		foreach ( $glossary_entries as $entry ) {
			$content = self::replace_first_occurrence( $content, $entry );
		}

		// Append all popovers at the end
		if ( ! empty( self::$popovers ) ) {
			$content .= "\n" . implode( "\n", self::$popovers );

			// Add helper text once if we have any popovers
			if ( self::$helper_added ) {
				$content .= self::get_helper_text();
			}
		}

		return $content;
	}

	/**
	 * Get all glossary entries with their metadata
	 *
	 * @return array Array of glossary entries.
	 */
	private static function get_glossary_entries() {
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

				$short_description = get_post_meta( $post_id, '_pp_glossary_short_description', true );
				$long_description  = get_post_meta( $post_id, '_pp_glossary_long_description', true );
				$synonyms          = get_post_meta( $post_id, '_pp_glossary_synonyms', true );

				// Build array of terms (title + synonyms)
				$terms = array( get_the_title() );

				if ( $synonyms && is_array( $synonyms ) ) {
					foreach ( $synonyms as $synonym ) {
						if ( ! empty( $synonym ) ) {
							$terms[] = $synonym;
						}
					}
				}

				$entries[] = array(
					'id'                => $post_id,
					'slug'              => sanitize_title( get_the_title() ),
					'title'             => get_the_title(),
					'terms'             => $terms,
					'short_description' => $short_description,
					'long_description'  => $long_description,
				);
			}
			wp_reset_postdata();
		}

		// Sort by longest term first to handle overlapping terms correctly
		usort(
			$entries,
			function ( $a, $b ) {
				$max_len_a = max( array_map( 'strlen', $a['terms'] ) );
				$max_len_b = max( array_map( 'strlen', $b['terms'] ) );
				return $max_len_b - $max_len_a;
			}
		);

		return $entries;
	}

	/**
	 * Replace first occurrence of glossary terms in content
	 *
	 * @param string $content The content.
	 * @param array  $entry   The glossary entry data.
	 * @return string Modified content.
	 */
	private static function replace_first_occurrence( $content, $entry ) {
		foreach ( $entry['terms'] as $term ) {
			// Create a pattern that matches the term as a whole word, case-insensitive
			// but not within HTML tags
			$pattern = '/\b(' . preg_quote( $term, '/' ) . ')\b(?![^<]*>)/iu';

			// Check if term exists in content
			if ( preg_match( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
				$matched_term = $matches[1][0];
				$offset       = $matches[1][1];

				// Generate unique ID for this occurrence
				self::$popover_counter++;
				$unique_id = 'dfn-' . sanitize_title( $entry['title'] ) . '-' . self::$popover_counter;
				$popover_id = 'pop-' . sanitize_title( $entry['title'] ) . '-' . self::$popover_counter;

				// Create the replacement HTML
				$replacement = self::create_term_button( $matched_term, $unique_id, $popover_id );

				// Replace only the first occurrence
				$content = substr_replace( $content, $replacement, $offset, strlen( $matched_term ) );

				// Store the popover for later
				self::$popovers[] = self::create_popover( $entry, $unique_id, $popover_id );

				// Mark that we need helper text
				self::$helper_added = true;

				// Break after first replacement for this entry
				break;
			}
		}

		return $content;
	}

	/**
	 * Create the term button HTML
	 *
	 * @param string $term        The matched term.
	 * @param string $unique_id   The unique ID for the dfn element.
	 * @param string $popover_id  The popover target ID.
	 * @return string HTML for the term button.
	 */
	private static function create_term_button( $term, $unique_id, $popover_id ) {
		$anchor_name = '--' . $unique_id;
		return sprintf(
			'<dfn id="%s" class="pp-glossary-term" style="anchor-name: %s;"><span data-glossary-popover="%s" aria-describedby="help-def" tabindex="0" role="button" aria-expanded="false">%s</span></dfn>',
			esc_attr( $unique_id ),
			esc_attr( $anchor_name ),
			esc_attr( $popover_id ),
			esc_html( $term )
		);
	}

	/**
	 * Create the popover HTML
	 *
	 * @param array  $entry       The glossary entry data.
	 * @param string $unique_id   The unique ID for the dfn element.
	 * @param string $popover_id  The popover ID.
	 * @return string HTML for the popover.
	 */
	private static function create_popover( $entry, $unique_id, $popover_id ) {
		$title = esc_html( $entry['title'] );
		$anchor_name = '--' . $unique_id;

		$popover_html = sprintf(
			'<aside id="%s" popover="manual" role="tooltip" aria-labelledby="%s" style="position-anchor: %s;">',
			esc_attr( $popover_id ),
			esc_attr( $unique_id ),
			esc_attr( $anchor_name )
		);

		$popover_html .= sprintf( '<strong>%s</strong>', $title );

		if ( ! empty( $entry['short_description'] ) ) {
			$popover_html .= sprintf( '<p>%s</p>', esc_html( $entry['short_description'] ) );
		}

		if ( ! empty( $entry['long_description'] ) ) {
			// Get glossary page URL from settings
			$glossary_page_url = PP_Glossary_Settings::get_glossary_page_url();

			if ( $glossary_page_url ) {
				// Create anchor link to specific entry using slug
				$entry_anchor = $entry['slug'];
				$full_url     = $glossary_page_url . '#' . $entry_anchor;

				$popover_html .= sprintf(
					'<p><a href="%s">Read more about <strong>%s</strong></a></p>',
					esc_url( $full_url ),
					esc_html( $title )
				);
			}
		}

		$popover_html .= '</aside>';

		return $popover_html;
	}

	/**
	 * Get the helper text for screen readers
	 *
	 * @return string HTML for helper text.
	 */
	private static function get_helper_text() {
		return '<p id="help-def" hidden>Hover or focus to see the definition of the term.</p>';
	}
}
