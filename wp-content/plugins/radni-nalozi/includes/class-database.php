<?php
if (!defined('ABSPATH')) {
    exit;
}

class RN_Database {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $orders_table = $wpdb->prefix . 'radni_nalozi';
        $items_table = $wpdb->prefix . 'radni_nalozi_items';
        
        $sql_orders = "CREATE TABLE IF NOT EXISTS $orders_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            order_number varchar(50) NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_address varchar(255) NOT NULL,
            customer_postal varchar(20) NOT NULL,
            customer_city varchar(100) NOT NULL,
            customer_phone varchar(50) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'u_obradi',
            total_amount decimal(10,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY order_number (order_number),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        $sql_items = "CREATE TABLE IF NOT EXISTS $items_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            print_name varchar(255) NOT NULL,
            color varchar(100) NOT NULL,
            size varchar(20) NOT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            image_url varchar(500) DEFAULT NULL,
            note text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_orders);
        dbDelta($sql_items);
        
        RN_Sizes::create_table();
        RN_Garment_Types::create_table();
        RN_Categories::create_table();
        
        self::add_garment_type_column();
        self::add_category_column();
    }
    
    public static function add_category_column() {
        global $wpdb;
        $items_table = self::get_items_table();
        
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $items_table LIKE 'category'");
        
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE $items_table ADD COLUMN category varchar(100) DEFAULT 'Muška' AFTER garment_type");
        }
    }
    
    public static function add_garment_type_column() {
        global $wpdb;
        $items_table = self::get_items_table();
        
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $items_table LIKE 'garment_type'");
        
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE $items_table ADD COLUMN garment_type varchar(100) DEFAULT 'Majica' AFTER color");
        }
    }
    
    public static function get_orders_table() {
        global $wpdb;
        return $wpdb->prefix . 'radni_nalozi';
    }
    
    public static function get_items_table() {
        global $wpdb;
        return $wpdb->prefix . 'radni_nalozi_items';
    }
}
