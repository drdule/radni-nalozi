<?php
if (!defined('ABSPATH')) {
    exit;
}

class RN_Garment_Types {
    
    public static function get_garment_types_table() {
        global $wpdb;
        return $wpdb->prefix . 'radni_nalozi_garment_types';
    }
    
    public static function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table = self::get_garment_types_table();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            sort_order int(11) NOT NULL DEFAULT 0,
            active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY name (name)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        self::seed_default_types();
    }
    
    private static function seed_default_types() {
        global $wpdb;
        $table = self::get_garment_types_table();
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        
        if ($count == 0) {
            $default_types = array(
                array('name' => 'Majica', 'sort_order' => 1),
                array('name' => 'Duks', 'sort_order' => 2),
                array('name' => 'Kapa', 'sort_order' => 3),
            );
            
            foreach ($default_types as $type) {
                $wpdb->insert($table, $type);
            }
        }
    }
    
    public static function get_all_types($active_only = true) {
        global $wpdb;
        $table = self::get_garment_types_table();
        
        $where = $active_only ? "WHERE active = 1" : "";
        
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY sort_order ASC, name ASC");
    }
    
    public static function get_types_for_select() {
        $types = self::get_all_types(true);
        $options = array();
        
        foreach ($types as $type) {
            $options[$type->name] = $type->name;
        }
        
        return $options;
    }
    
    public static function get_type($id) {
        global $wpdb;
        $table = self::get_garment_types_table();
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    public static function add_type($name, $sort_order = 0) {
        global $wpdb;
        $table = self::get_garment_types_table();
        
        $result = $wpdb->insert($table, array(
            'name' => sanitize_text_field($name),
            'sort_order' => intval($sort_order),
            'active' => 1
        ));
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    public static function update_type($id, $data) {
        global $wpdb;
        $table = self::get_garment_types_table();
        
        $update_data = array();
        
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['sort_order'])) {
            $update_data['sort_order'] = intval($data['sort_order']);
        }
        if (isset($data['active'])) {
            $update_data['active'] = intval($data['active']) ? 1 : 0;
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        return $wpdb->update($table, $update_data, array('id' => intval($id)));
    }
    
    public static function delete_type($id) {
        global $wpdb;
        $table = self::get_garment_types_table();
        
        return $wpdb->delete($table, array('id' => intval($id)));
    }
    
    public static function toggle_status($id) {
        global $wpdb;
        $table = self::get_garment_types_table();
        
        $current = $wpdb->get_var($wpdb->prepare("SELECT active FROM $table WHERE id = %d", $id));
        $new_status = $current ? 0 : 1;
        
        return $wpdb->update($table, array('active' => $new_status), array('id' => intval($id)));
    }
}
