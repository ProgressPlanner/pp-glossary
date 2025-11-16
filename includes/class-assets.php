<?php
/**
 * Frontend Assets for Glossary.
 *
 * @package PP_Glossary
 */

namespace PP_Glossary;

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
		// Only load assets on the frontend, not in admin.
		if ( ! is_admin() ) {
			add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		}
	}

	/**
	 * Enqueue frontend assets.
	 */
	public static function enqueue_assets(): void {

		// Only enqueue assets if terms have been found in the post content or on Glossary page.
		if ( ! class_exists( '\\PP_Glossary\\Content_Filter' ) || ! Content_Filter::$terms_found_on_page ) {
			return;
		}

		wp_enqueue_style(
			'pp-glossary',
			\PP_GLOSSARY_PLUGIN_URL . 'assets/css/glossary.css',
			[],
			\PP_GLOSSARY_VERSION
		);

		wp_enqueue_script(
			'pp-glossary',
			\PP_GLOSSARY_PLUGIN_URL . 'assets/js/glossary.js',
			[],
			\PP_GLOSSARY_VERSION,
			[
				'strategy' => 'defer',
			]
		);
	}
}
