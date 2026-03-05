<?php
/**
 * Plugin Name: RN Print Ponude
 * Plugin URI: https://example.com/rn-print-ponude
 * Description: Kalkulator cena + ponude/poručivanje za štampu (MVP).
 * Version: 0.1.0
 * Author: drdule
 * Text Domain: rn-print-ponude
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'RNP_PLUGIN_FILE', __FILE__ );
define( 'RNP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RNP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RNP_VERSION', '1.0.0' );

// Load main plugin class
require_once RNP_PLUGIN_DIR . 'includes/class-plugin.php';

/**
 * Initialize plugin
 */
function rn_print_ponude_init() {
	$plugin = RN_Print_Ponude\Plugin::instance();
	$plugin->run();
}
add_action( 'plugins_loaded', 'rn_print_ponude_init' );

// Activation hook
register_activation_hook( RNP_PLUGIN_FILE, array( 'RN_Print_Ponude\Plugin', 'activate' ) );

// Deactivation hook
register_deactivation_hook( RNP_PLUGIN_FILE, array( 'RN_Print_Ponude\Plugin', 'deactivate' ) );