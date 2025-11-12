<?php
/**
 * Frontend Assets for Glossary.
 *
 * @package PP_Glossary
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class PP_Glossary_Assets.
 */
class PP_Glossary_Assets {

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
		wp_enqueue_style(
			'pp-glossary',
			PP_GLOSSARY_PLUGIN_URL . 'assets/css/glossary.css',
			[],
			PP_GLOSSARY_VERSION
		);

		wp_enqueue_script(
			'pp-glossary',
			PP_GLOSSARY_PLUGIN_URL . 'assets/js/glossary.js',
			[],
			PP_GLOSSARY_VERSION,
			[
				'strategy' => 'defer',
			]
		);
	}
}
