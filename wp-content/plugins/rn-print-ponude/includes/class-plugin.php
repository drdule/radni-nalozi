<?php
/**
 * Main plugin class
 *
 * @package RN_Print_Ponude
 */

namespace RN_Print_Ponude;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 */
class Plugin {

	/**
	 * Plugin instance
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Get plugin instance
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->load_dependencies();
	}

	/**
	 * Load plugin dependencies
	 */
	private function load_dependencies() {
		require_once RNP_PLUGIN_DIR . 'includes/class-database.php';
		require_once RNP_PLUGIN_DIR . 'includes/class-admin.php';
		require_once RNP_PLUGIN_DIR . 'includes/class-frontend.php';
		require_once RNP_PLUGIN_DIR . 'includes/class-ajax.php';
	}

	/**
	 * Run the plugin
	 */
	public function run() {
		// Register shortcodes
		add_shortcode( 'rn_print_ponuda', array( $this, 'shortcode' ) );

		// Load admin
		if ( is_admin() ) {
			new Admin();
		} else {
			new Frontend();
		}

		// Load AJAX handlers
		new Ajax();
	}

	/**
	 * Shortcode callback
	 *
	 * @return string
	 */
	public function shortcode() {
		wp_enqueue_style( 'rnp-style', RNP_PLUGIN_URL . 'assets/css/style.css', array(), RNP_VERSION );
		wp_enqueue_script( 'rnp-script', RNP_PLUGIN_URL . 'assets/js/script.js', array( 'jquery' ), RNP_VERSION, true );
		wp_enqueue_script( 'rnp-quote-status', RNP_PLUGIN_URL . 'assets/js/quote-status.js', array( 'jquery' ), RNP_VERSION, true );

		// Localize script dengan data
		wp_localize_script( 'rnp-script', 'rnpData', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'rnp_nonce' ),
		) );

		// Return form
		$frontend = new Frontend();
		ob_start();
		$frontend->render_form();
		return ob_get_clean();
	}

	/**
	 * Plugin activation
	 */
	public static function activate() {
		// Activation hook runs before plugins_loaded, so load DB class explicitly.
		if ( ! class_exists( __NAMESPACE__ . '\\Database' ) ) {
			require_once RNP_PLUGIN_DIR . 'includes/class-database.php';
		}

		// Create tables
		$database = new Database();
		$database->create_tables();

		// Create protected upload directory and .htaccess
		$upload_dir = wp_upload_dir();
		$rnp_upload_path = $upload_dir['basedir'] . '/rnp-quotes/';
		
		if ( ! is_dir( $rnp_upload_path ) ) {
			wp_mkdir_p( $rnp_upload_path );
		}

		// Create .htaccess file to protect upload folder
		$htaccess_file = $rnp_upload_path . '.htaccess';
		$htaccess_content = "# Protect RNP quotes directory\n";
		$htaccess_content .= "Options -Indexes\n";
		$htaccess_content .= "<FilesMatch \"\\.php$\">\n";
		$htaccess_content .= "    Deny from all\n";
		$htaccess_content .= "</FilesMatch>\n";

		if ( ! file_exists( $htaccess_file ) ) {
			file_put_contents( $htaccess_file, $htaccess_content );
		}

		// Flush rewrite rules
		flush_rewrite_rules();

		// Add activation notice
		set_transient( 'rnp_activation_notice', true );
	}

	/**
	 * Plugin deactivation
	 */
	public static function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();
	}
}
