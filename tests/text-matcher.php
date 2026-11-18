<?php
/**
 * Standalone regression checks: php tests/text-matcher.php.
 *
 * @package PP_Glossary
 */

// Load only the matcher, without a WordPress installation.
require_once dirname( __DIR__ ) . '/includes/class-text-matcher.php';

/**
 * Supply configured exclusions without bootstrapping WordPress.
 *
 * @return array<int, string> Excluded tags.
 */
function pp_glossary_get_excluded_tags(): array {
	return [ 'a', 'h2', 'div' ];
}

$pp_glossary_fixtures = [
	[ '<img alt="CLS < 0.1 is good" src="x.png"> CLS', 'CLS' ],
	[ '<img alt="CLS <img src=x onerror=alert(1) " src="x.png"> CLS', 'CLS' ],
	[ "<img alt='CLS > CLS < CLS'> CLS", 'CLS' ],
	[ '<script>const CLS = "CLS";</script> CLS', 'CLS' ],
	[ '<style>.CLS{color:red}</style> CLS', 'CLS' ],
	[ '<textarea>CLS</textarea><select><option>CLS</option></select><button>CLS</button><svg><text>CLS</text></svg> CLS', 'CLS' ],
	[ '&amp; &#97; &#x61; &amp CLS amp', 'amp' ],
	[ '<!-- CLS --><h2>CLS <h2>CLS</h2> CLS</h2> CLS', 'CLS' ],
	[ '<a href="#">CLS</a><dfn><span>CLS</span></dfn> CLS', 'CLS' ],
	[ '<div><div>CLS</div> CLS</div> CLS', 'CLS' ],
	[ '<svg/> CLS', 'CLS' ],
	[ '<svg><svg/></svg> CLS', 'CLS' ],
];

foreach ( $pp_glossary_fixtures as $pp_glossary_fixture ) {
	$pp_glossary_match = PP_Glossary\Text_Matcher::find( $pp_glossary_fixture[0], $pp_glossary_fixture[1], false );
	if ( null === $pp_glossary_match || strrpos( $pp_glossary_fixture[0], $pp_glossary_fixture[1] ) !== $pp_glossary_match[1] ) {
		throw new RuntimeException( 'Only the final visible term should match.' );
	}
}

foreach ( [ '<img alt="CLS', '<script>CLS', '<!-- CLS', '<button>CLS' ] as $pp_glossary_html ) {
	if ( null !== PP_Glossary\Text_Matcher::find( $pp_glossary_html, 'CLS', false ) ) {
		throw new RuntimeException( 'Incomplete protected markup must remain unchanged.' );
	}
}

if ( null !== PP_Glossary\Text_Matcher::find( 'cls', 'CLS', true ) ) {
	throw new RuntimeException( 'Case-sensitive matching must preserve case.' );
}

if ( [ 'cls', 0 ] !== PP_Glossary\Text_Matcher::find( 'cls CLS', 'CLS', false ) ) {
	throw new RuntimeException( 'Case-insensitive matching must find the first term.' );
}

echo "Text matching regression checks passed.\n";
