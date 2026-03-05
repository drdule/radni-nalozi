<?php
/**
 * Plugin Name: Radni Nalozi
 * Plugin URI: https://example.com/radni-nalozi
 * Description: WordPress plugin za kreiranje i upravljanje radnim nalozima za štampu odeće
 * Version: 1.4.0
 * Author: Your Name
 * Text Domain: radni-nalozi
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('RN_PLUGIN_VERSION', '1.4.0');
define('RN_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('RN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RN_PLUGIN_BASENAME', plugin_basename(__FILE__));

class Radni_Nalozi {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        require_once RN_PLUGIN_PATH . 'includes/class-database.php';
        require_once RN_PLUGIN_PATH . 'includes/class-sizes.php';
        require_once RN_PLUGIN_PATH . 'includes/class-garment-types.php';
        require_once RN_PLUGIN_PATH . 'includes/class-categories.php';
        require_once RN_PLUGIN_PATH . 'includes/class-orders.php';
        require_once RN_PLUGIN_PATH . 'includes/class-ajax.php';
        require_once RN_PLUGIN_PATH . 'includes/class-frontend.php';
        require_once RN_PLUGIN_PATH . 'includes/class-admin.php';
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('init', array($this, 'init'));
        
        add_filter('theme_page_templates', array($this, 'add_page_template'));
        add_filter('template_include', array($this, 'load_page_template'));
    }
    
    public function add_page_template($templates) {
        $templates['templates/page-radni-nalozi.php'] = __('Radni Nalozi', 'radni-nalozi');
        return $templates;
    }
    
    public function load_page_template($template) {
        if (is_page()) {
            $page_template = get_page_template_slug();
            if ($page_template === 'templates/page-radni-nalozi.php') {
                $plugin_template = RN_PLUGIN_PATH . 'templates/page-radni-nalozi.php';
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }
        return $template;
    }
    
    public function init() {
        $this->maybe_upgrade();
        
        RN_Frontend::get_instance();
        RN_Ajax::get_instance();
        
        if (is_admin()) {
            RN_Admin::get_instance();
        }
    }
    
    private function maybe_upgrade() {
        $db_version = get_option('rn_db_version', '1.0.0');
        
        if (version_compare($db_version, '1.2.0', '<')) {
            RN_Database::add_garment_type_column();
            RN_Garment_Types::create_table();
            update_option('rn_db_version', '1.2.0');
        }
    }
    
    public function activate() {
        RN_Database::create_tables();
        $this->create_radni_nalozi_page();
        flush_rewrite_rules();
    }
    
    private function create_radni_nalozi_page() {
        $page_title = __('Radni Nalozi', 'radni-nalozi');
        $page_slug = 'radni-nalozi';
        
        $existing_page = get_page_by_path($page_slug);
        
        if (!$existing_page) {
            $page_id = wp_insert_post(array(
                'post_title'     => $page_title,
                'post_name'      => $page_slug,
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'post_content'   => '',
                'page_template'  => 'templates/page-radni-nalozi.php',
                'comment_status' => 'closed'
            ));
            
            if ($page_id && !is_wp_error($page_id)) {
                update_option('rn_radni_nalozi_page_id', $page_id);
            }
        }
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style(
            'radni-nalozi-style',
            RN_PLUGIN_URL . 'assets/css/style.css',
            array(),
            RN_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'radni-nalozi-script',
            RN_PLUGIN_URL . 'assets/js/script.js',
            array('jquery'),
            RN_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script('radni-nalozi-script', 'radniNalozi', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('radni_nalozi_nonce'),
            'uploadNonce' => wp_create_nonce('radni_nalozi_upload'),
            'messages' => array(
                'confirmDelete' => __('Da li ste sigurni da želite da stornirate ovaj radni nalog?', 'radni-nalozi'),
                'saving' => __('Čuvanje...', 'radni-nalozi'),
                'saved' => __('Sačuvano!', 'radni-nalozi'),
                'error' => __('Greška prilikom čuvanja.', 'radni-nalozi'),
                'uploading' => __('Upload u toku...', 'radni-nalozi'),
                'uploadError' => __('Greška prilikom upload-a slike.', 'radni-nalozi')
            )
        ));
        
        wp_enqueue_media();
    }
}

function radni_nalozi() {
    return Radni_Nalozi::get_instance();
}

radni_nalozi();
