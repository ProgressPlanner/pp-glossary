<?php
/**
 * Content Filter for Glossary Terms
 *
 * @package PP_Glossary
 */

namespace PP_Glossary;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Content_Filter
 */
class Content_Filter {

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
	 * Flag to track if the term(s) have been found in the content.
	 *
	 * @var bool
	 */
	public static $terms_found_on_page = false;

	/**
	 * Initialize the content filter
	 */
	public static function init(): void {
		add_filter( 'the_content', [ __CLASS__, 'filter_content' ], 20 );
	}

	/**
	 * Filter content to replace glossary terms
	 *
	 * @param string $content The post content.
	 * @return string Modified content.
	 */
	public static function filter_content( $content ): string {

		// No need to filter content in RSS feeds or REST API requests.
		if ( is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $content;
		}

		// Reset counters and storage for each content piece.
		self::$popover_counter = 0;
		self::$popovers        = [];

		// Check if content filtering is disabled for this post type.
		$disabled_post_types = Settings::get_excluded_post_types();

		/**
		 * Filter the disabled post types.
		 *
		 * @param array<int, string> $disabled_post_types The disabled post types.
		 *
		 * @return array<int, string> The disabled post types.
		 */
		$disabled_post_types = apply_filters( 'pp_glossary_disabled_post_types', $disabled_post_types );
		$current_post_type   = get_post_type();
		if ( $current_post_type && in_array( $current_post_type, $disabled_post_types, true ) ) {
			return $content;
		}

		// Don't process on the glossary page.
		$glossary_page_id = Settings::get_glossary_page_id();
		if ( $glossary_page_id && is_page( $glossary_page_id ) ) {
			self::$terms_found_on_page = true; // Set to true on glossary page.
			return $content;
		}

		// Get all glossary entries.
		$glossary_entries = pp_glossary_get_linkable_entries();

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

			// Set to true if terms have been found in the content.
			self::$terms_found_on_page = true;
		}

		return $content;
	}

	/**
	 * Replace first occurrence of glossary terms in content
	 *
	 * @param string               $content The content.
	 * @param array<string, mixed> $entry   The glossary entry data.
	 * @return string Modified content.
	 */
	private static function replace_first_occurrence( $content, $entry ): string {
		$excluded_tags    = pp_glossary_get_excluded_tags();
		$parts            = pp_glossary_split_by_excluded_tags( $content, $excluded_tags );
		$excluded_pattern = '/^<(?:' . implode( '|', $excluded_tags ) . ')\b/i';

		if ( false === $parts ) {
			return $content;
		}

		// Now iterate through terms.
		foreach ( $entry['terms'] as $term ) {
			$replaced  = false;
			$new_parts = [];

			foreach ( $parts as $part ) {
				// If already replaced or this matches an excluded tag pattern, keep as-is.
				if ( $replaced || preg_match( $excluded_pattern, $part ) ) {
					$new_parts[] = $part;
					continue;
				}

				// Try to match term in this text chunk, but avoid HTML attributes.
				// Use case-insensitive matching unless the entry is marked as case sensitive.
				$flags   = $entry['case_sensitive'] ? 'u' : 'iu';
				$pattern = '/\b(' . preg_quote( $term, '/' ) . ')\b(?![^<]*>)/' . $flags;

				if ( preg_match( $pattern, $part, $matches, PREG_OFFSET_CAPTURE ) ) {
					$matched_term = $matches[1][0];
					$offset       = $matches[1][1];

					// Generate unique ID for this occurrence.
					++self::$popover_counter;
					$hash       = md5( sanitize_title( $entry['title'] ) );
					$unique_id  = 'dfn-' . $hash . '-' . self::$popover_counter;
					$popover_id = 'pop-' . $hash . '-' . self::$popover_counter;

					// Create the replacement HTML.
					$replacement = self::create_term_button( $matched_term, $unique_id, $popover_id );

					// Replace only this occurrence in this chunk.
					$new_parts[] = substr_replace( $part, $replacement, $offset, strlen( $matched_term ) );

					// Store the popover for later.
					self::$popovers[] = self::create_popover( $entry, $unique_id, $popover_id );

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
			'<dfn id="%s" class="pp-glossary-term" style="anchor-name: %s;"><span data-glossary-popover="%s" tabindex="0" role="button" aria-expanded="false">%s</span></dfn>',
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
		$title             = esc_html( $entry['title'] );
		$anchor_name       = '--' . $unique_id;
		$glossary_page_url = Settings::get_glossary_page_url();

		$popover_html = sprintf(
			'<aside id="%s" popover="auto" role="tooltip" aria-labelledby="%s" style="position-anchor: %s;">',
			esc_attr( $popover_id ),
			esc_attr( $unique_id ),
			esc_attr( $anchor_name )
		);

		// Screen reader context: announce what follows (definition, and link if available).
		$has_read_more = ! empty( $entry['long_description'] ) && $glossary_page_url;
		$sr_text       = $has_read_more
			/* translators: %s: glossary term title */
			? sprintf( __( 'Definition of %s. Link to full glossary entry follows the description.', 'pp-glossary' ), $entry['title'] )
			/* translators: %s: glossary term title */
			: sprintf( __( 'Definition of %s.', 'pp-glossary' ), $entry['title'] );

		$popover_html .= sprintf( '<span class="pp-glossary-sr-only">%s</span>', esc_html( $sr_text ) );

		$popover_html .= sprintf( '<strong class="glossary-title" aria-hidden="true">%s</strong>', $title );

		if ( ! empty( $entry['short_description'] ) ) {
			// Link glossary terms within the short description, excluding self.
			$short_desc    = Term_Linker::link_terms_in_text(
				$entry['short_description'],
				$entry['id']
			);
			$popover_html .= sprintf( '<p>%s</p>', wp_kses_post( $short_desc ) );
		}

		if ( $has_read_more ) {
			// Create anchor link to specific entry using slug.
			$entry_anchor = $entry['slug'];
			$full_url     = $glossary_page_url . '#' . $entry_anchor;

			$popover_html .= sprintf(
				'<p><a href="%s" aria-label="%s">%s <strong>%s</strong></a></p>',
				esc_url( $full_url ),
				/* translators: %s: glossary term title */
				esc_attr( sprintf( __( 'Read more about %s on the glossary page', 'pp-glossary' ), $entry['title'] ) ),
				esc_html__( 'Read more about', 'pp-glossary' ),
				esc_html( $title )
			);
		}

		$popover_html .= '</aside>';

		return $popover_html;
	}
}
