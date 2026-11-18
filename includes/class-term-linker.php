<?php
/**
 * Term Linker Utility
 *
 * @package PP_Glossary
 */

namespace PP_Glossary;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Term_Linker
 *
 * Static utility for transforming text by linking glossary terms.
 */
class Term_Linker {

	/**
	 * Link glossary terms in text
	 *
	 * @param string $text       The text to process.
	 * @param int    $exclude_id Entry ID to exclude from linking (prevents self-links).
	 * @return string Modified text with linked terms.
	 */
	public static function link_terms_in_text( string $text, int $exclude_id = 0 ): string {
		if ( empty( $text ) ) {
			return $text;
		}

		$glossary_url = Settings::get_glossary_page_url();
		if ( empty( $glossary_url ) ) {
			return $text;
		}

		$entries = pp_glossary_get_linkable_entries();
		if ( empty( $entries ) ) {
			return $text;
		}

		foreach ( $entries as $entry ) {
			if ( $entry['id'] === $exclude_id ) {
				continue;
			}

			$text = self::replace_first_term_occurrence( $text, $entry, $glossary_url );
		}

		return $text;
	}

	/**
	 * Replace first occurrence of any term in entry
	 *
	 * Matches text outside markup and excluded elements to avoid creating
	 * nested anchors or linking inside attributes and character references.
	 *
	 * @param string               $text         The text to process.
	 * @param array<string, mixed> $entry        The glossary entry data.
	 * @param string               $glossary_url The glossary page URL.
	 * @return string Modified text.
	 */
	private static function replace_first_term_occurrence( string $text, array $entry, string $glossary_url ): string {
		foreach ( $entry['terms'] as $term ) {
			$match = Text_Matcher::find( $text, $term, (bool) $entry['case_sensitive'] );
			if ( null === $match ) {
				continue;
			}

			$link_html = sprintf(
				'<a href="%s#%s" class="pp-glossary-link">%s</a>',
				esc_url( $glossary_url ),
				esc_attr( $entry['slug'] ),
				esc_html( $match[0] )
			);

			return substr_replace( $text, $link_html, $match[1], strlen( $match[0] ) );
		}

		return $text;
	}
}
