<?php
/**
 * Plugin Name: Inline Glossary by Progress Planner
 * Plugin URI: https://progressplanner.com
 * Description: A semantic, accessible glossary plugin that automatically links terms to popover definitions.
 * Version: 1.3.1
 * Author: Team Progress Planner
 * Author URI: https://progressplanner.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * GitHub Plugin URI: https://github.com/progressplanner/pp-glossary
 * Primary Branch: main
 * Release Asset: true
 * Text Domain: inline-glossary
 * Plugin ID: did:plc:m5tfrwxd3btacxlstcvop2ib
 * Security: security@progressplanner.com
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package Inline_Glossary
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'INLINE_GLOSSARY_VERSION', '1.3.1' );
define( 'INLINE_GLOSSARY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'INLINE_GLOSSARY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once INLINE_GLOSSARY_PLUGIN_DIR . 'includes/functions.php';

/**
 * Autoloader for Inline_Glossary classes.
 *
 * @param string $class_name The fully qualified class name to load.
 *
 * @return void
 */
function inline_glossary_autoloader( string $class_name ): void {
	// Only handle Inline_Glossary namespace classes.
	if ( strpos( $class_name, 'Inline_Glossary\\' ) !== 0 ) {
		return;
	}

	// Remove the Inline_Glossary\ namespace prefix.
	$class_name = substr( $class_name, strlen( 'Inline_Glossary\\' ) );

	// Convert class name to file name format.
	// Inline_Glossary\Settings -> class-settings.php -> includes/class-settings.php.
	$file_name = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';
	$file_path = INLINE_GLOSSARY_PLUGIN_DIR . 'includes/' . $file_name;

	// Load the file if it exists.
	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
}

// Register the autoloader.
spl_autoload_register( 'inline_glossary_autoloader' );

/**
 * Initialize the plugin.
 */
function inline_glossary_init(): void {
	// Initialize components.
	\Inline_Glossary\Settings::init();
	\Inline_Glossary\Post_Type::init();
	\Inline_Glossary\Blocks::init();
	\Inline_Glossary\Schema::init();

	if ( is_admin() ) {
		\Inline_Glossary\Meta_Boxes::init();
		\Inline_Glossary\Migrations::init();
	} else {
		\Inline_Glossary\Content_Filter::init();
		\Inline_Glossary\Assets::init();
	}
}
add_action( 'plugins_loaded', 'inline_glossary_init' );

/**
 * Activation hook
 */
function inline_glossary_activate(): void {
	// Flush rewrite rules.
	inline_glossary_init();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'inline_glossary_activate' );

/**
 * Deactivation hook
 */
function inline_glossary_deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'inline_glossary_deactivate' );
