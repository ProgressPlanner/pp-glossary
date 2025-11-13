<?php
/**
 * Functions for Glossary.
 *
 * @package PP_Glossary
 */

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
		if ( $length !== null ) {
			return mb_substr( $text, $start, $length, 'UTF-8' );
		}
		return mb_substr( $text, $start, null, 'UTF-8' );
	}
	if ( $length !== null ) {
		return substr( $text, $start, $length );
	}
	return substr( $text, $start );
}
