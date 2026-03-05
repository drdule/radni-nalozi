<?php
if (!defined('ABSPATH')) {
    exit;
}

class RN_Sizes {
    
    public static function get_sizes_table() {
        global $wpdb;
        return $wpdb->prefix . 'radni_nalozi_sizes';
    }
    
    public static function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table = self::get_sizes_table();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(50) NOT NULL,
            sort_order int(11) NOT NULL DEFAULT 0,
            active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY name (name)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        self::seed_default_sizes();
    }
    
    private static function seed_default_sizes() {
        global $wpdb;
        $table = self::get_sizes_table();
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        
        if ($count == 0) {
            $default_sizes = array('XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL');
            
            foreach ($default_sizes as $index => $size) {
                $wpdb->insert($table, array(
                    'name' => $size,
                    'sort_order' => $index,
                    'active' => 1
                ), array('%s', '%d', '%d'));
            }
        }
    }
    
    public static function get_all_sizes($active_only = true) {
        global $wpdb;
        $table = self::get_sizes_table();
        
        $sql = "SELECT * FROM $table";
        if ($active_only) {
            $sql .= " WHERE active = 1";
        }
        $sql .= " ORDER BY sort_order ASC, name ASC";
        
        return $wpdb->get_results($sql);
    }
    
    public static function get_size($id) {
        global $wpdb;
        $table = self::get_sizes_table();
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
    }
    
    public static function add_size($name, $sort_order = 0) {
        global $wpdb;
        $table = self::get_sizes_table();
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE name = %s",
            $name
        ));
        
        if ($existing) {
            return new WP_Error('exists', __('Veličina sa tim nazivom već postoji.', 'radni-nalozi'));
        }
        
        $result = $wpdb->insert($table, array(
            'name' => sanitize_text_field($name),
            'sort_order' => intval($sort_order),
            'active' => 1
        ), array('%s', '%d', '%d'));
        
        if ($result === false) {
            return new WP_Error('insert_failed', __('Greška pri dodavanju veličine.', 'radni-nalozi'));
        }
        
        return $wpdb->insert_id;
    }
    
    public static function update_size($id, $data) {
        global $wpdb;
        $table = self::get_sizes_table();
        
        $update_data = array();
        $format = array();
        
        if (isset($data['name'])) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE name = %s AND id != %d",
                $data['name'], $id
            ));
            
            if ($existing) {
                return new WP_Error('exists', __('Veličina sa tim nazivom već postoji.', 'radni-nalozi'));
            }
            
            $update_data['name'] = sanitize_text_field($data['name']);
            $format[] = '%s';
        }
        
        if (isset($data['sort_order'])) {
            $update_data['sort_order'] = intval($data['sort_order']);
            $format[] = '%d';
        }
        
        if (isset($data['active'])) {
            $update_data['active'] = $data['active'] ? 1 : 0;
            $format[] = '%d';
        }
        
        if (empty($update_data)) {
            return true;
        }
        
        $result = $wpdb->update($table, $update_data, array('id' => $id), $format, array('%d'));
        
        return $result !== false;
    }
    
    public static function delete_size($id) {
        global $wpdb;
        $table = self::get_sizes_table();
        
        return $wpdb->delete($table, array('id' => $id), array('%d'));
    }
    
    public static function get_sizes_for_select() {
        $sizes = self::get_all_sizes(true);
        $options = array();
        
        foreach ($sizes as $size) {
            $options[$size->name] = $size->name;
        }
        
        return $options;
    }
}
