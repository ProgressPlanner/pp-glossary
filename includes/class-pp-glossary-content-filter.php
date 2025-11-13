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
	 * @var array<int, string>
	 */
	private static $popovers = [];

	/**
	 * Flag to track if helper text has been added
	 *
	 * @var bool
	 */
	private static $helper_added = false;

	/**
	 * Initialize the content filter
	 */
	public static function init(): void {

		// No need to filter content in Dashboard.
		if ( ! is_admin() ) {
			add_filter( 'the_content', [ __CLASS__, 'filter_content' ], 20 );
		}
	}

	/**
	 * Filter content to replace glossary terms
	 *
	 * @param string $content The post content.
	 * @return string Modified content.
	 */
	public static function filter_content( $content ): string {

		// Reset counters and storage for each content piece.
		self::$popover_counter = 0;
		self::$popovers        = [];
		self::$helper_added    = false;

		// Check if content filtering is disabled for this post type.
		$disabled_post_types = apply_filters( 'pp_glossary_disabled_post_types', [] );
		if ( ! empty( $disabled_post_types ) && is_array( $disabled_post_types ) ) {
			$current_post_type = get_post_type();
			if ( $current_post_type && in_array( $current_post_type, $disabled_post_types, true ) ) {
				return $content;
			}
		}

		// Don't process on the glossary page.
		$glossary_page_id = PP_Glossary_Settings::get_glossary_page_id();
		if ( $glossary_page_id && is_page( $glossary_page_id ) ) {
			return $content;
		}

		// Get all glossary entries.
		$glossary_entries = self::get_glossary_entries();

		if ( empty( $glossary_entries ) ) {
			return $content;
		}

		// Process each glossary entry.
		foreach ( $glossary_entries as $entry ) {
			$content = self::replace_first_occurrence( $content, $entry );
		}

		// Append all popovers at the end.
		if ( ! empty( self::$popovers ) ) {
			$content .= "\n" . implode( "\n", self::$popovers );

			// Add helper text once if we have any popovers.
			if ( self::$helper_added ) { // @phpstan-ignore-line -- self::$helper_added is set to true in the replace_first_occurrence method.
				$content .= self::get_helper_text();
			}
		}

		return $content;
	}

	/**
	 * Get all glossary entries with their metadata
	 *
	 * @return array<int, array<string, mixed>> Array of glossary entries.
	 */
	private static function get_glossary_entries(): array {
		$entries = [];

		$query = new WP_Query(
			[
				'post_type'      => 'pp_glossary',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = (int) get_the_ID();

				$short_description = get_post_meta( $post_id, '_pp_glossary_short_description', true );
				$long_description  = get_post_meta( $post_id, '_pp_glossary_long_description', true );
				$synonyms          = get_post_meta( $post_id, '_pp_glossary_synonyms', true );

				// Build array of terms (title + synonyms).
				$terms = [ get_the_title() ];

				if ( $synonyms && is_array( $synonyms ) ) {
					foreach ( $synonyms as $synonym ) {
						if ( ! empty( $synonym ) ) {
							$terms[] = $synonym;
						}
					}
				}

				$entries[] = [
					'id'                => $post_id,
					'slug'              => sanitize_title( get_the_title() ),
					'title'             => get_the_title(),
					'terms'             => $terms,
					'short_description' => $short_description,
					'long_description'  => $long_description,
				];
			}
			wp_reset_postdata();
		}

		// Sort by longest term first to handle overlapping terms correctly.
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
	 * @param string               $content The content.
	 * @param array<string, mixed> $entry   The glossary entry data.
	 * @return string Modified content.
	 */
	private static function replace_first_occurrence( $content, $entry ): string {
		// Define tags where terms should NOT be replaced.
		$excluded_tags = [ 'a' ];

		// Build the pattern for excluded tags only.
		$excluded_pattern = '';
		foreach ( $excluded_tags as $tag ) {
			$excluded_pattern .= '<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>|';
		}
		$excluded_pattern = rtrim( $excluded_pattern, '|' );

		// Split content ONCE by excluded tags.
		$parts = preg_split( '/(' . $excluded_pattern . ')/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		// Now iterate through terms.
		foreach ( $entry['terms'] as $term ) {
			$replaced  = false;
			$new_parts = [];

			foreach ( $parts as $part ) {
				// If already replaced or this matches an excluded tag pattern, keep as-is.
				if ( $replaced || preg_match( '/^<(?:' . implode( '|', $excluded_tags ) . ')\b/i', $part ) ) {
					$new_parts[] = $part;
					continue;
				}

				// Try to match term in this text chunk, but avoid HTML attributes.
				$pattern = '/\b(' . preg_quote( $term, '/' ) . ')\b(?![^<]*>)/iu';

				if ( preg_match( $pattern, $part, $matches, PREG_OFFSET_CAPTURE ) ) {
					$matched_term = $matches[1][0];
					$offset       = $matches[1][1];

					// Generate unique ID for this occurrence.
					++self::$popover_counter;
					$unique_id  = 'dfn-' . md5( sanitize_title( $entry['title'] ) ) . '-' . self::$popover_counter;
					$popover_id = 'pop-' . md5( sanitize_title( $entry['title'] ) ) . '-' . self::$popover_counter;

					// Create the replacement HTML.
					$replacement = self::create_term_button( $matched_term, $unique_id, $popover_id );

					// Replace only this occurrence in this chunk.
					$new_parts[] = substr_replace( $part, $replacement, $offset, strlen( $matched_term ) );

					// Store the popover for later.
					self::$popovers[] = self::create_popover( $entry, $unique_id, $popover_id );

					// Mark that we need helper text.
					self::$helper_added = true;

					$replaced = true;
				} else {
					$new_parts[] = $part;
				}
			}

			if ( $replaced ) {
				// Update parts for next term iteration.
				$parts = $new_parts;
				break;
			}
		}

		return implode( '', $parts );
	}

	/**
	 * Create the term button HTML
	 *
	 * @param string $term        The matched term.
	 * @param string $unique_id   The unique ID for the dfn element.
	 * @param string $popover_id  The popover target ID.
	 * @return string HTML for the term button.
	 */
	private static function create_term_button( $term, $unique_id, $popover_id ): string {
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
	 * @param array<string, mixed> $entry       The glossary entry data.
	 * @param string               $unique_id   The unique ID for the dfn element.
	 * @param string               $popover_id  The popover ID.
	 * @return string HTML for the popover.
	 */
	private static function create_popover( $entry, $unique_id, $popover_id ): string {
		$title       = esc_html( $entry['title'] );
		$anchor_name = '--' . $unique_id;

		$popover_html = sprintf(
			'<aside id="%s" popover="manual" role="tooltip" aria-labelledby="%s" style="position-anchor: %s;">',
			esc_attr( $popover_id ),
			esc_attr( $unique_id ),
			esc_attr( $anchor_name )
		);

		$popover_html .= sprintf( '<strong class="glossary-title">%s</strong>', $title );

		if ( ! empty( $entry['short_description'] ) ) {
			$popover_html .= sprintf( '<p>%s</p>', esc_html( $entry['short_description'] ) );
		}

		if ( ! empty( $entry['long_description'] ) ) {
			// Get glossary page URL from settings.
			$glossary_page_url = PP_Glossary_Settings::get_glossary_page_url();

			if ( $glossary_page_url ) {
				// Create anchor link to specific entry using slug.
				$entry_anchor = $entry['slug'];
				$full_url     = $glossary_page_url . '#' . $entry_anchor;

				$popover_html .= sprintf(
					'<p><a href="%s">%s <strong>%s</strong></a></p>',
					esc_url( $full_url ),
					esc_html__( 'Read more about', 'pp-glossary' ),
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
	private static function get_helper_text(): string {
		return '<p id="help-def" hidden>' . esc_html__( 'Hover or focus to see the definition of the term.', 'pp-glossary' ) . '</p>';
	}
}
