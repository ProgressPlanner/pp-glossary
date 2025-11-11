<?php
/**
 * Plugin Name: Glossary by Progress Planner
 * Plugin URI: https://progressplanner.com
 * Description: A semantic, accessible glossary plugin that automatically links terms to popover definitions.
 * Version: 1.0.0
 * Author: Joost de Valk
 * Author URI: https://joost.blog
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pp-glossary
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'PP_GLOSSARY_VERSION', '1.0.0' );
define( 'PP_GLOSSARY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PP_GLOSSARY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Initialize the plugin
 */
function pp_glossary_init() {
	// Load required files
	require_once PP_GLOSSARY_PLUGIN_DIR . 'includes/post-type.php';
	require_once PP_GLOSSARY_PLUGIN_DIR . 'includes/meta-boxes.php';
	require_once PP_GLOSSARY_PLUGIN_DIR . 'includes/content-filter.php';
	require_once PP_GLOSSARY_PLUGIN_DIR . 'includes/settings.php';
	require_once PP_GLOSSARY_PLUGIN_DIR . 'includes/blocks.php';
	require_once PP_GLOSSARY_PLUGIN_DIR . 'includes/schema.php';

	// Initialize components
	PP_Glossary_Post_Type::init();
	PP_Glossary_Meta_Boxes::init();
	PP_Glossary_Content_Filter::init();
	PP_Glossary_Settings::init();
	PP_Glossary_Blocks::init();
	PP_Glossary_Schema::init();
}
add_action( 'plugins_loaded', 'pp_glossary_init' );

/**
 * Enqueue frontend assets
 */
function pp_glossary_enqueue_assets() {
	wp_enqueue_style(
		'pp-glossary',
		PP_GLOSSARY_PLUGIN_URL . 'assets/css/glossary.css',
		array(),
		PP_GLOSSARY_VERSION
	);

	wp_enqueue_script(
		'pp-glossary',
		PP_GLOSSARY_PLUGIN_URL . 'assets/js/glossary.js',
		array(),
		PP_GLOSSARY_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'pp_glossary_enqueue_assets' );

/**
 * Activation hook
 */
function pp_glossary_activate() {
	// Flush rewrite rules
	pp_glossary_init();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'pp_glossary_activate' );

/**
 * Deactivation hook
 */
function pp_glossary_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'pp_glossary_deactivate' );
