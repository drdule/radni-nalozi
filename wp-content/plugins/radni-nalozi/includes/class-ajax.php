<?php
if (!defined('ABSPATH')) {
    exit;
}

class RN_Ajax {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_rn_create_order', array($this, 'create_order'));
        add_action('wp_ajax_rn_update_order', array($this, 'update_order'));
        add_action('wp_ajax_rn_cancel_order', array($this, 'cancel_order'));
        add_action('wp_ajax_rn_get_order', array($this, 'get_order'));
        add_action('wp_ajax_rn_upload_image', array($this, 'upload_image'));
        add_action('wp_ajax_rn_login', array($this, 'login'));
        add_action('wp_ajax_nopriv_rn_login', array($this, 'login'));
    }
    
    private function verify_nonce() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'radni_nalozi_nonce')) {
            wp_send_json_error(array('message' => __('Sigurnosna provera nije uspela.', 'radni-nalozi')));
            exit;
        }
    }
    
    public function login() {
        $this->verify_nonce();
        
        $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        if (empty($username) || empty($password)) {
            wp_send_json_error(array('message' => __('Unesite korisničko ime i lozinku.', 'radni-nalozi')));
        }
        
        $user = wp_signon(array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => true
        ), false);
        
        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => __('Pogrešno korisničko ime ili lozinka.', 'radni-nalozi')));
        }
        
        wp_send_json_success(array('message' => __('Uspešna prijava!', 'radni-nalozi')));
    }
    
    public function create_order() {
        $this->verify_nonce();
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Morate biti prijavljeni.', 'radni-nalozi')));
        }
        
        $data = array(
            'customer_name' => isset($_POST['customer_name']) ? $_POST['customer_name'] : '',
            'customer_address' => isset($_POST['customer_address']) ? $_POST['customer_address'] : '',
            'customer_postal' => isset($_POST['customer_postal']) ? $_POST['customer_postal'] : '',
            'customer_city' => isset($_POST['customer_city']) ? $_POST['customer_city'] : '',
            'customer_phone' => isset($_POST['customer_phone']) ? $_POST['customer_phone'] : '',
            'items' => isset($_POST['items']) ? $_POST['items'] : array()
        );
        
        if (empty($data['customer_name'])) {
            wp_send_json_error(array('message' => __('Ime kupca je obavezno.', 'radni-nalozi')));
        }
        
        $result = RN_Orders::create_order($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => __('Radni nalog je uspešno kreiran!', 'radni-nalozi'),
            'order_id' => $result['order_id'],
            'order_number' => $result['order_number']
        ));
    }
    
    public function update_order() {
        $this->verify_nonce();
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Morate biti prijavljeni.', 'radni-nalozi')));
        }
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        if (!$order_id) {
            wp_send_json_error(array('message' => __('Nevažeći ID naloga.', 'radni-nalozi')));
        }
        
        $data = array(
            'customer_name' => isset($_POST['customer_name']) ? $_POST['customer_name'] : '',
            'customer_address' => isset($_POST['customer_address']) ? $_POST['customer_address'] : '',
            'customer_postal' => isset($_POST['customer_postal']) ? $_POST['customer_postal'] : '',
            'customer_city' => isset($_POST['customer_city']) ? $_POST['customer_city'] : '',
            'customer_phone' => isset($_POST['customer_phone']) ? $_POST['customer_phone'] : '',
            'items' => isset($_POST['items']) ? $_POST['items'] : array()
        );
        
        $result = RN_Orders::update_order($order_id, $data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Radni nalog je uspešno ažuriran!', 'radni-nalozi')));
    }
    
    public function cancel_order() {
        $this->verify_nonce();
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Morate biti prijavljeni.', 'radni-nalozi')));
        }
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        if (!$order_id) {
            wp_send_json_error(array('message' => __('Nevažeći ID naloga.', 'radni-nalozi')));
        }
        
        $result = RN_Orders::cancel_order($order_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Radni nalog je storniran.', 'radni-nalozi')));
    }
    
    public function get_order() {
        $this->verify_nonce();
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Morate biti prijavljeni.', 'radni-nalozi')));
        }
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        if (!$order_id) {
            wp_send_json_error(array('message' => __('Nevažeći ID naloga.', 'radni-nalozi')));
        }
        
        $order = RN_Orders::get_order($order_id);
        
        if (!$order || $order->user_id != get_current_user_id()) {
            wp_send_json_error(array('message' => __('Nalog nije pronađen.', 'radni-nalozi')));
        }
        
        $items = RN_Orders::get_order_items($order_id);
        
        wp_send_json_success(array(
            'order' => $order,
            'items' => $items
        ));
    }
    
    public function upload_image() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'radni_nalozi_upload')) {
            wp_send_json_error(array('message' => __('Sigurnosna provera nije uspela.', 'radni-nalozi')));
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Morate biti prijavljeni.', 'radni-nalozi')));
        }
        
        if (empty($_FILES['image'])) {
            wp_send_json_error(array('message' => __('Slika nije priložena.', 'radni-nalozi')));
        }
        
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('image', 0);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
        }
        
        $image_url = wp_get_attachment_url($attachment_id);
        
        wp_send_json_success(array(
            'message' => __('Slika je uspešno učitana!', 'radni-nalozi'),
            'image_url' => $image_url,
            'attachment_id' => $attachment_id
        ));
    }
}
