<?php
/**
 * Functions for Glossary.
 *
 * @package PP_Glossary
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Multibyte-safe string to lowercase wrapper.
 *
 * @param string $text The string to convert to lowercase.
 * @return string Lowercase string.
 */
function pp_glossary_strtolower( string $text ): string {
	if ( function_exists( 'mb_strtolower' ) ) {
		return mb_strtolower( $text, 'UTF-8' );
	}
	return strtolower( $text );
}

/**
 * Multibyte-safe string to uppercase wrapper.
 *
 * @param string $text The string to convert to uppercase.
 * @return string Uppercase string.
 */
function pp_glossary_strtoupper( string $text ): string {
	if ( function_exists( 'mb_strtoupper' ) ) {
		return mb_strtoupper( $text, 'UTF-8' );
	}
	return strtoupper( $text );
}

/**
 * Multibyte-safe substring wrapper.
 *
 * @param string   $text The input string.
 * @param int      $start  The starting position.
 * @param int|null $length Optional. Maximum length of the substring.
 * @return string The substring.
 */
function pp_glossary_substr( string $text, int $start, ?int $length = null ): string {
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $text, $start, $length, 'UTF-8' );
	}
	return substr( $text, $start, $length );
}

/**
 * Get the HTML tags that should be excluded from glossary term linking.
 *
 * Retrieves the configured excluded tags and applies the pp_glossary_excluded_tags filter.
 *
 * @return array<int, string> Array of HTML tag names.
 */
function pp_glossary_get_excluded_tags(): array {
	$excluded_tags = PP_Glossary\Settings::get_excluded_tags();

	/**
	 * Filter the excluded tags.
	 *
	 * @param array<int, string> $excluded_tags The excluded tags.
	 *
	 * @return array<int, string> The excluded tags.
	 */
	return apply_filters( 'pp_glossary_excluded_tags', $excluded_tags );
}

/**
 * Split text into parts, separating excluded HTML tag elements.
 *
 * Returns an array of text chunks where excluded tag elements (e.g. <a>...</a>)
 * are their own entries. Check if a part starts with an excluded tag using
 * preg_match to skip it during processing.
 *
 * @param string            $text          The text to split.
 * @param array<int,string> $excluded_tags Array of HTML tag names to split on.
 * @return array<int, string>|false Array of parts, or false on regex failure.
 */
function pp_glossary_split_by_excluded_tags( string $text, array $excluded_tags ) {
	$excluded_pattern = implode(
		'|',
		array_map(
			function ( $tag ) {
				return '<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>';
			},
			$excluded_tags
		)
	);

	return preg_split( '/(' . $excluded_pattern . ')/is', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
}

/**
 * Get all published glossary entries with their metadata.
 *
 * This is a shared helper function used by multiple components
 * (Content_Filter, Term_Linker, Blocks, Schema) to avoid query duplication.
 *
 * @return array<int, array<string, mixed>> Array of glossary entries with full metadata.
 */
function pp_glossary_get_entries(): array {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

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

	foreach ( $query->posts as $post ) {
		$post_id = ( $post instanceof \WP_Post ) ? $post->ID : (int) $post;
		$title   = ( $post instanceof \WP_Post ) ? $post->post_title : get_the_title( $post_id );
		$data    = PP_Glossary\Meta_Boxes::get_entry_data( $post_id );

		// Build array of terms (title + non-empty synonyms).
		$terms    = [ $title ];
		$synonyms = pp_glossary_filter_synonyms( $data['synonyms'] );
		$terms    = array_merge( $terms, $synonyms );

		$entries[] = [
			'id'                => $post_id,
			'slug'              => sanitize_title( $title ),
			'title'             => $title,
			'terms'             => $terms,
			'short_description' => $data['short_description'],
			'long_description'  => $data['long_description'],
			'synonyms'          => $synonyms,
			'case_sensitive'    => $data['case_sensitive'],
			'disable_autolink'  => $data['disable_autolink'],
		];
	}

	$cache = $entries;

	return $entries;
}

/**
 * Filter out empty synonym values from an array.
 *
 * @param mixed $synonyms The synonyms array (or other value).
 * @return array<int, string> Filtered array of non-empty synonym strings.
 */
function pp_glossary_filter_synonyms( $synonyms ): array {
	if ( ! is_array( $synonyms ) ) {
		return [];
	}

	return array_values( array_filter( $synonyms ) );
}

/**
 * Sort glossary entries by longest term first.
 *
 * This ensures overlapping terms are handled correctly (longer terms match first).
 *
 * @param array<int, array<string, mixed>> $entries Array of glossary entries.
 * @return array<int, array<string, mixed>> Sorted array of glossary entries.
 */
function pp_glossary_sort_by_term_length( array $entries ): array {
	usort(
		$entries,
		function ( $a, $b ) {
			$lengths_a = array_map( 'strlen', $a['terms'] );
			$lengths_b = array_map( 'strlen', $b['terms'] );

			// Fallback to 0 for empty terms arrays (shouldn't happen in practice).
			$max_len_a = empty( $lengths_a ) ? 0 : max( $lengths_a );
			$max_len_b = empty( $lengths_b ) ? 0 : max( $lengths_b );

			return $max_len_b - $max_len_a;
		}
	);

	return $entries;
}

/**
 * Get glossary entries that are linkable (auto-linking not disabled).
 *
 * Filters out entries with disable_autolink=true and sorts by longest term first.
 * Used by Content_Filter and Term_Linker.
 *
 * @return array<int, array<string, mixed>> Array of linkable glossary entries.
 */
function pp_glossary_get_linkable_entries(): array {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$all_entries = pp_glossary_get_entries();

	// Filter out entries with auto-linking disabled.
	$linkable_entries = array_filter(
		$all_entries,
		function ( $entry ) {
			return ! $entry['disable_autolink'];
		}
	);

	// Sort by longest term first.
	$cache = pp_glossary_sort_by_term_length( array_values( $linkable_entries ) );

	return $cache;
}
