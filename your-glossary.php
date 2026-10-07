<?php
/**
 * Plugin Name: Your Glossary
 * Plugin URI: https://progressplanner.com
 * Description: A semantic, accessible glossary plugin that automatically links terms to popover definitions.
 * Version: 1.4.0
 * Author: Team Progress Planner
 * Author URI: https://progressplanner.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * GitHub Plugin URI: https://github.com/progressplanner/your-glossary
 * Primary Branch: main
 * Release Asset: true
 * Text Domain: your-glossary
 * Plugin ID: did:plc:m5tfrwxd3btacxlstcvop2ib
 * Security: security@progressplanner.com
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package Your_Glossary
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'YOUR_GLOSSARY_VERSION', '1.4.0' );
define( 'YOUR_GLOSSARY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'YOUR_GLOSSARY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once YOUR_GLOSSARY_PLUGIN_DIR . 'includes/functions.php';

/**
 * Autoloader for Your_Glossary classes.
 *
 * @param string $class_name The fully qualified class name to load.
 *
 * @return void
 */
function your_glossary_autoloader( string $class_name ): void {
	// Only handle Your_Glossary namespace classes.
	if ( strpos( $class_name, 'Your_Glossary\\' ) !== 0 ) {
		return;
	}

	// Remove the Your_Glossary\ namespace prefix.
	$class_name = substr( $class_name, strlen( 'Your_Glossary\\' ) );

	// Convert class name to file name format.
	// Your_Glossary\Settings -> class-settings.php -> includes/class-settings.php.
	$file_name = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';
	$file_path = YOUR_GLOSSARY_PLUGIN_DIR . 'includes/' . $file_name;

	// Load the file if it exists.
	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
}

// Register the autoloader.
spl_autoload_register( 'your_glossary_autoloader' );

/**
 * Initialize the plugin.
 */
function your_glossary_init(): void {
	// Initialize components.
	\Your_Glossary\Settings::init();
	\Your_Glossary\Post_Type::init();
	\Your_Glossary\Blocks::init();
	\Your_Glossary\Schema::init();

	if ( is_admin() ) {
		\Your_Glossary\Meta_Boxes::init();
		\Your_Glossary\Migrations::init();
	} else {
		\Your_Glossary\Content_Filter::init();
		\Your_Glossary\Assets::init();
	}
}
add_action( 'plugins_loaded', 'your_glossary_init' );

/**
 * Activation hook
 */
function your_glossary_activate(): void {
	// Flush rewrite rules.
	your_glossary_init();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'your_glossary_activate' );

/**
 * Deactivation hook
 */
function your_glossary_deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'your_glossary_deactivate' );
