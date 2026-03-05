<?php
if (!defined('ABSPATH')) {
    exit;
}

class RN_Orders {
    
    public static function generate_order_number($user_id) {
        global $wpdb;
        
        $user = get_userdata($user_id);
        $username = $user ? sanitize_user($user->user_login) : 'user';
        $year = date('y');
        
        $orders_table = RN_Database::get_orders_table();
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $orders_table WHERE user_id = %d AND YEAR(created_at) = YEAR(CURDATE())",
            $user_id
        ));
        
        $next_number = intval($count) + 1;
        
        return sprintf('%s-%d-%s', $username, $next_number, $year);
    }
    
    public static function create_order($data) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('not_logged_in', __('Morate biti prijavljeni.', 'radni-nalozi'));
        }
        
        $order_number = self::generate_order_number($user_id);
        
        $orders_table = RN_Database::get_orders_table();
        $items_table = RN_Database::get_items_table();
        
        $wpdb->insert($orders_table, array(
            'user_id' => $user_id,
            'order_number' => $order_number,
            'customer_name' => sanitize_text_field($data['customer_name']),
            'customer_address' => sanitize_text_field($data['customer_address']),
            'customer_postal' => sanitize_text_field($data['customer_postal']),
            'customer_city' => sanitize_text_field($data['customer_city']),
            'customer_phone' => sanitize_text_field($data['customer_phone']),
            'status' => 'nov',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ), array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));
        
        $order_id = $wpdb->insert_id;
        
        if (!$order_id) {
            return new WP_Error('insert_failed', __('Greška prilikom kreiranja naloga.', 'radni-nalozi'));
        }
        
        $total_amount = 0;
        
        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $price = floatval($item['price']);
                $quantity = intval($item['quantity']);
                $total_amount += $price * $quantity;
                
                $wpdb->insert($items_table, array(
                    'order_id' => $order_id,
                    'print_name' => sanitize_text_field($item['print_name']),
                    'color' => sanitize_text_field($item['color']),
                    'garment_type' => sanitize_text_field($item['garment_type'] ?? 'Majica'),
                    'category' => sanitize_text_field($item['category'] ?? 'Muška'),
                    'size' => sanitize_text_field($item['size']),
                    'quantity' => $quantity,
                    'price' => $price,
                    'image_url' => esc_url_raw($item['image_url']),
                    'note' => sanitize_textarea_field($item['note']),
                    'created_at' => current_time('mysql')
                ), array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%s', '%s', '%s'));
            }
        }
        
        $wpdb->update($orders_table, 
            array('total_amount' => $total_amount),
            array('id' => $order_id),
            array('%f'),
            array('%d')
        );
        
        return array(
            'order_id' => $order_id,
            'order_number' => $order_number
        );
    }
    
    public static function update_order($order_id, $data) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $order = self::get_order($order_id);
        
        if (!$order || $order->user_id != $user_id) {
            return new WP_Error('not_authorized', __('Nemate dozvolu za ovu akciju.', 'radni-nalozi'));
        }
        
        if ($order->status !== 'nov') {
            return new WP_Error('cannot_edit', __('Nalog se ne može više menjati.', 'radni-nalozi'));
        }
        
        $orders_table = RN_Database::get_orders_table();
        $items_table = RN_Database::get_items_table();
        
        $wpdb->update($orders_table, array(
            'customer_name' => sanitize_text_field($data['customer_name']),
            'customer_address' => sanitize_text_field($data['customer_address']),
            'customer_postal' => sanitize_text_field($data['customer_postal']),
            'customer_city' => sanitize_text_field($data['customer_city']),
            'customer_phone' => sanitize_text_field($data['customer_phone']),
            'updated_at' => current_time('mysql')
        ), array('id' => $order_id), 
        array('%s', '%s', '%s', '%s', '%s', '%s'),
        array('%d'));
        
        $wpdb->delete($items_table, array('order_id' => $order_id), array('%d'));
        
        $total_amount = 0;
        
        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $price = floatval($item['price']);
                $quantity = intval($item['quantity']);
                $total_amount += $price * $quantity;
                
                $wpdb->insert($items_table, array(
                    'order_id' => $order_id,
                    'print_name' => sanitize_text_field($item['print_name']),
                    'color' => sanitize_text_field($item['color']),
                    'garment_type' => sanitize_text_field($item['garment_type'] ?? 'Majica'),
                    'category' => sanitize_text_field($item['category'] ?? 'Muška'),
                    'size' => sanitize_text_field($item['size']),
                    'quantity' => $quantity,
                    'price' => $price,
                    'image_url' => esc_url_raw($item['image_url']),
                    'note' => sanitize_textarea_field($item['note']),
                    'created_at' => current_time('mysql')
                ), array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%s', '%s', '%s'));
            }
        }
        
        $wpdb->update($orders_table, 
            array('total_amount' => $total_amount),
            array('id' => $order_id),
            array('%f'),
            array('%d')
        );
        
        return true;
    }
    
    public static function cancel_order($order_id) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $order = self::get_order($order_id);
        
        if (!$order || $order->user_id != $user_id) {
            return new WP_Error('not_authorized', __('Nemate dozvolu za ovu akciju.', 'radni-nalozi'));
        }
        
        if ($order->status === 'storniran') {
            return new WP_Error('already_cancelled', __('Nalog je već storniran.', 'radni-nalozi'));
        }
        
        $orders_table = RN_Database::get_orders_table();
        
        $wpdb->update($orders_table, 
            array(
                'status' => 'storniran',
                'updated_at' => current_time('mysql')
            ),
            array('id' => $order_id),
            array('%s', '%s'),
            array('%d')
        );
        
        return true;
    }
    
    public static function get_order($order_id) {
        global $wpdb;
        
        $orders_table = RN_Database::get_orders_table();
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $orders_table WHERE id = %d",
            $order_id
        ));
    }
    
    public static function get_order_items($order_id) {
        global $wpdb;
        
        $items_table = RN_Database::get_items_table();
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $items_table WHERE order_id = %d ORDER BY id ASC",
            $order_id
        ));
    }
    
    public static function get_user_orders($user_id, $status = null) {
        global $wpdb;
        
        $orders_table = RN_Database::get_orders_table();
        
        $sql = "SELECT * FROM $orders_table WHERE user_id = %d";
        $params = array($user_id);
        
        if ($status) {
            $sql .= " AND status = %s";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    public static function get_all_orders_paginated($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => '',
            'search' => '',
            'page' => 1,
            'per_page' => 50,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $orders_table = RN_Database::get_orders_table();
        $users_table = $wpdb->users;
        
        $sql = "SELECT o.*, u.user_login, u.display_name 
                FROM $orders_table o 
                LEFT JOIN $users_table u ON o.user_id = u.ID 
                WHERE 1=1";
        
        $count_sql = "SELECT COUNT(*) 
                      FROM $orders_table o 
                      LEFT JOIN $users_table u ON o.user_id = u.ID 
                      WHERE 1=1";
        
        $params = array();
        
        if ($args['status']) {
            $sql .= " AND o.status = %s";
            $count_sql .= " AND o.status = %s";
            $params[] = $args['status'];
        }
        
        if ($args['search']) {
            $sql .= " AND (o.order_number LIKE %s OR o.customer_name LIKE %s OR u.user_login LIKE %s)";
            $count_sql .= " AND (o.order_number LIKE %s OR o.customer_name LIKE %s OR u.user_login LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        // Ukupan broj naloga
        if (!empty($params)) {
            $total_items = $wpdb->get_var($wpdb->prepare($count_sql, $params));
        } else {
            $total_items = $wpdb->get_var($count_sql);
        }
        
        // Paginacija
        $offset = ($args['page'] - 1) * $args['per_page'];
        $sql .= " ORDER BY o.{$args['orderby']} {$args['order']}";
        $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $args['per_page'], $offset);
        
        // Dobijanje naloga
        if (!empty($params)) {
            $orders = $wpdb->get_results($wpdb->prepare($sql, $params));
        } else {
            $orders = $wpdb->get_results($sql);
        }
        
        return array(
            'orders' => $orders,
            'total' => $total_items,
            'per_page' => $args['per_page'],
            'current_page' => $args['page'],
            'total_pages' => ceil($total_items / $args['per_page'])
        );
    }
    
    public static function update_status($order_id, $new_status) {
        global $wpdb;
        
        $orders_table = RN_Database::get_orders_table();
        $valid_statuses = array_keys(self::get_all_statuses());
        
        if (!in_array($new_status, $valid_statuses)) {
            return false;
        }
        
        $result = $wpdb->update(
            $orders_table,
            array(
                'status' => $new_status,
                'updated_at' => current_time('mysql')
            ),
            array('id' => intval($order_id)),
            array('%s', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    public static function get_all_statuses() {
        return array(
            'nov' => __('Nov', 'radni-nalozi'),
            'u_izradi' => __('U izradi', 'radni-nalozi'),
            'zavrsen' => __('Završen', 'radni-nalozi'),
            'isporucen' => __('Isporučen', 'radni-nalozi'),
            'isplacen' => __('Isplaćen', 'radni-nalozi'),
            'storniran' => __('Storniran', 'radni-nalozi')
        );
    }
    
    public static function get_status_label($status) {
        $statuses = self::get_all_statuses();
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }
    
    public static function get_status_class($status) {
        $classes = array(
            'nov' => 'status-new',
            'u_izradi' => 'status-processing',
            'zavrsen' => 'status-completed',
            'isporucen' => 'status-delivered',
            'isplacen' => 'status-paid',
            'storniran' => 'status-cancelled'
        );
        
        return isset($classes[$status]) ? $classes[$status] : '';
    }
}
