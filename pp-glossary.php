<?php
/**
 * Plugin Name: Glossary by Progress Planner
 * Plugin URI: https://progressplanner.com
 * Description: A semantic, accessible glossary plugin that automatically links terms to popover definitions.
 * Version: 1.0.0
 * Author: Joost de Valk
 * Author URI: https://joost.blog
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/progressplanner/pp-glossary
 * GitHub Branch: main
 * Text Domain: pp-glossary
 * Plugin ID: did:plc:m5tfrwxd3btacxlstcvop2ib
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package PP_Glossary
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'PP_GLOSSARY_VERSION', '1.0.0' );
define( 'PP_GLOSSARY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PP_GLOSSARY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once PP_GLOSSARY_PLUGIN_DIR . 'includes/functions.php';

/**
 * Autoloader for PP_Glossary classes.
 *
 * @param string $class_name The fully qualified class name to load.
 *
 * @return void
 */
function pp_glossary_autoloader( string $class_name ): void {
	// Only handle PP_Glossary namespace classes.
	if ( strpos( $class_name, 'PP_Glossary\\' ) !== 0 ) {
		return;
	}

	// Remove the PP_Glossary\ namespace prefix.
	$class_name = substr( $class_name, strlen( 'PP_Glossary\\' ) );

	// Convert class name to file name format.
	// PP_Glossary\Settings -> class-settings.php -> includes/class-settings.php.
	$file_name = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';
	$file_path = PP_GLOSSARY_PLUGIN_DIR . 'includes/' . $file_name;

	// Load the file if it exists.
	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
}

// Register the autoloader.
spl_autoload_register( 'pp_glossary_autoloader' );

/**
 * Initialize the plugin.
 */
function pp_glossary_init(): void {
	// Initialize components.
	\PP_Glossary\Settings::init();
	\PP_Glossary\Post_Type::init();
	\PP_Glossary\Blocks::init();
	\PP_Glossary\Schema::init();

	if ( is_admin() ) {
		\PP_Glossary\Meta_Boxes::init();
	}

	if ( ! is_admin() ) {
		\PP_Glossary\Content_Filter::init();
		\PP_Glossary\Assets::init();
	}
}
add_action( 'plugins_loaded', 'pp_glossary_init' );

/**
 * Activation hook
 */
function pp_glossary_activate(): void {
	// Flush rewrite rules.
	pp_glossary_init();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'pp_glossary_activate' );

/**
 * Deactivation hook
 */
function pp_glossary_deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'pp_glossary_deactivate' );
