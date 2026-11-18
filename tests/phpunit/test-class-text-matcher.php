<?php
/**
 * Tests for the text matcher.
 *
 * @package PP_Glossary
 */

namespace PP_Glossary\Tests;

use PP_Glossary\Settings;
use PP_Glossary\Text_Matcher;

/**
 * Covers matching terms in visible text only, never inside markup.
 */
class Text_Matcher_Test extends Glossary_TestCase {

	/**
	 * Configure the excluded tags these regression checks were written for.
	 */
	public function set_up() {
		parent::set_up();
		Settings::update_setting( 'excluded_tags', [ 'a', 'h2', 'div' ] );
	}

	/**
	 * Only the final, visible occurrence of the term is matched.
	 *
	 * @dataProvider data_only_final_visible_term_matches
	 *
	 * @param string $html The HTML to search.
	 * @param string $term The term to find.
	 */
	public function test_only_final_visible_term_matches( string $html, string $term ) {
		$match = Text_Matcher::find( $html, $term, false );

		$this->assertNotNull( $match );
		$this->assertSame( $term, $match[0] );
		$this->assertSame( strrpos( $html, $term ), $match[1] );
	}

	/**
	 * HTML in which every occurrence but the last one is protected.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function data_only_final_visible_term_matches(): array {
		return [
			'attribute containing <'           => [ '<img alt="CLS < 0.1 is good" src="x.png"> CLS', 'CLS' ],
			'attribute containing markup'      => [ '<img alt="CLS <img src=x onerror=alert(1) " src="x.png"> CLS', 'CLS' ],
			'single-quoted attribute with < >' => [ "<img alt='CLS > CLS < CLS'> CLS", 'CLS' ],
			'script element'                   => [ '<script>const CLS = "CLS";</script> CLS', 'CLS' ],
			'style element'                    => [ '<style>.CLS{color:red}</style> CLS', 'CLS' ],
			'form controls and svg'            => [ '<textarea>CLS</textarea><select><option>CLS</option></select><button>CLS</button><svg><text>CLS</text></svg> CLS', 'CLS' ],
			'character references'             => [ '&amp; &#97; &#x61; &amp CLS amp', 'amp' ],
			'comment and nested excluded tag'  => [ '<!-- CLS --><h2>CLS <h2>CLS</h2> CLS</h2> CLS', 'CLS' ],
			'link and existing dfn'            => [ '<a href="#">CLS</a><dfn><span>CLS</span></dfn> CLS', 'CLS' ],
			'nested excluded elements'         => [ '<div><div>CLS</div> CLS</div> CLS', 'CLS' ],
			'self-closing svg'                 => [ '<svg/> CLS', 'CLS' ],
			'self-closing svg inside svg'      => [ '<svg><svg/></svg> CLS', 'CLS' ],
		];
	}

	/**
	 * Incomplete protected markup is left unchanged.
	 *
	 * @dataProvider data_incomplete_markup
	 *
	 * @param string $html The HTML to search.
	 */
	public function test_incomplete_protected_markup_does_not_match( string $html ) {
		$this->assertNull( Text_Matcher::find( $html, 'CLS', false ) );
	}

	/**
	 * HTML that opens a protected context without closing it.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function data_incomplete_markup(): array {
		return [
			'unclosed attribute' => [ '<img alt="CLS' ],
			'unclosed script'    => [ '<script>CLS' ],
			'unclosed comment'   => [ '<!-- CLS' ],
			'unclosed button'    => [ '<button>CLS' ],
		];
	}

	/**
	 * Case-sensitive matching requires the exact casing.
	 */
	public function test_case_sensitive_matching() {
		$this->assertNull( Text_Matcher::find( 'cls', 'CLS', true ) );
		$this->assertSame( [ 'CLS', 4 ], Text_Matcher::find( 'cls CLS', 'CLS', true ) );
	}

	/**
	 * Case-insensitive matching finds the first occurrence and keeps its casing.
	 */
	public function test_case_insensitive_matching() {
		$this->assertSame( [ 'cls', 0 ], Text_Matcher::find( 'cls CLS', 'CLS', false ) );
	}
}
