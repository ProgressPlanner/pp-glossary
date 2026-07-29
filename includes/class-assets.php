<?php
/**
 * Frontend Assets for Glossary.
 *
 * @package Your_Glossary
 */

namespace Your_Glossary;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Assets.
 */
class Assets {

	/**
	 * Initialize assets.
	 */
	public static function init(): void {
		add_action( 'wp_footer', [ __CLASS__, 'enqueue_assets' ], 1 );
	}

	/**
	 * Enqueue frontend assets.
	 */
	public static function enqueue_assets(): void {

		// Only enqueue assets if terms have been found in the post content or on Glossary page.
		if ( ! class_exists( '\\Your_Glossary\\Content_Filter' ) || ! Content_Filter::$terms_found_on_page ) {
			return;
		}

		wp_enqueue_style(
			'your-glossary',
			\YOUR_GLOSSARY_PLUGIN_URL . 'assets/css/glossary.css',
			[],
			\YOUR_GLOSSARY_VERSION
		);

		wp_enqueue_script(
			'your-glossary',
			\YOUR_GLOSSARY_PLUGIN_URL . 'assets/js/glossary.js',
			[],
			\YOUR_GLOSSARY_VERSION,
			[
				'strategy' => 'defer',
			]
		);
	}
}
