<?php
/**
 * Text-only glossary matching, compatible with WordPress 6.0.
 *
 * @package PP_Glossary
 */

namespace PP_Glossary;

/**
 * Keep markup, character references, and excluded elements out of term matching.
 */
class Text_Matcher {

	/**
	 * Find the first eligible occurrence of a term.
	 *
	 * @param string $html           Original HTML.
	 * @param string $term           Term to find.
	 * @param bool   $case_sensitive Whether matching is case sensitive.
	 * @return array{0: string, 1: int}|null Matched text and byte offset, or null.
	 */
	public static function find( string $html, string $term, bool $case_sensitive ): ?array {
		// Quote-aware tokenization leaves attributes intact, including literal < and >.
		$token_pattern = <<<'REGEX'
~(<!--.*?(?:-->|$)|<!\[CDATA\[.*?(?:\]\]>|$)|<[!?][^>]*(?:>|$)|</?[a-z][a-z0-9:-]*(?=[\s/>]|$)(?:[^"'>]|"[^"]*(?:"|$)|'[^']*(?:'|$))*(?:>|$))~is
REGEX;
		$parts         = preg_split( $token_pattern, $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE );
		if ( false === $parts ) {
			return null;
		}

		$excluded = array_unique(
			array_merge(
				array_map( 'strtolower', pp_glossary_get_excluded_tags() ),
				[ 'a', 'dfn', 'script', 'style', 'textarea', 'select', 'button', 'svg', 'math', 'title', 'xmp', 'iframe', 'noembed', 'noframes', 'plaintext', 'template' ]
			)
		);
		$raw_tags = [ 'script', 'style', 'textarea', 'title', 'xmp', 'iframe', 'noembed', 'noframes', 'plaintext' ];
		$void     = [ 'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr' ];
		$stack    = [];
		$raw_tag  = '';
		$flags    = $case_sensitive ? 'u' : 'iu';
		// Skip entire character references, including legacy references without a semicolon.
		$pattern = '/&(?:\#[xX][0-9a-fA-F]+|\#[0-9]+|[a-zA-Z][a-zA-Z0-9]*);?(*SKIP)(*F)|\b(' . preg_quote( $term, '/' ) . ')\b/' . $flags;

		foreach ( $parts as $index => $part ) {
			if ( 1 === $index % 2 ) {
				if ( ! preg_match( '~^<(/?)([a-z][a-z0-9:-]*)\b~i', $part[0], $tag_match ) ) {
					continue;
				}
				$tag     = strtolower( $tag_match[2] );
				$closing = '/' === $tag_match[1];

				if ( '' !== $raw_tag ) {
					if ( $closing && $tag === $raw_tag ) {
						$raw_tag = '';
						array_pop( $stack );
					}
					continue;
				}

				if ( $closing ) {
					// Only close the matching excluded ancestor, preserving nested exclusions.
					$position = array_search( $tag, array_reverse( $stack, true ), true );
					if ( false !== $position ) {
						$stack = array_slice( $stack, 0, $position );
					}
				} elseif ( in_array( $tag, $excluded, true ) && ! in_array( $tag, $void, true ) ) {
					// HTML ignores self-closing slashes; SVG and MathML honor them.
					$foreign = in_array( $tag, [ 'svg', 'math' ], true ) || in_array( 'svg', $stack, true ) || in_array( 'math', $stack, true );
					if ( $foreign && '/>' === substr( $part[0], -2 ) ) {
						continue;
					}
					$stack[] = $tag;
					if ( in_array( $tag, $raw_tags, true ) ) {
						$raw_tag = $tag;
					}
				}
				continue;
			}

			if ( empty( $stack ) && preg_match( $pattern, $part[0], $match, PREG_OFFSET_CAPTURE ) ) {
				return [ $match[1][0], $part[1] + $match[1][1] ];
			}
		}

		return null;
	}
}
