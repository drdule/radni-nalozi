<?php
if (!defined('ABSPATH')) {
    exit;
}

class RN_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
        add_action('wp_ajax_rn_admin_update_status', array($this, 'ajax_update_status'));
        add_action('wp_ajax_rn_admin_bulk_print', array($this, 'ajax_bulk_print'));
        add_action('wp_ajax_rn_admin_bulk_status', array($this, 'ajax_bulk_status'));
        add_action('wp_ajax_rn_admin_delete_size', array($this, 'ajax_delete_size'));
        add_action('wp_ajax_rn_admin_add_size', array($this, 'ajax_add_size'));
        add_action('wp_ajax_rn_admin_update_size', array($this, 'ajax_update_size'));
        add_action('wp_ajax_rn_admin_delete_garment_type', array($this, 'ajax_delete_garment_type'));
        add_action('wp_ajax_rn_admin_add_garment_type', array($this, 'ajax_add_garment_type'));
        add_action('wp_ajax_rn_admin_update_garment_type', array($this, 'ajax_update_garment_type'));
        add_action('wp_ajax_rn_admin_delete_category', array($this, 'ajax_delete_category'));
        add_action('wp_ajax_rn_admin_add_category', array($this, 'ajax_add_category'));
        add_action('wp_ajax_rn_admin_update_category', array($this, 'ajax_update_category'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Radni Nalozi', 'radni-nalozi'),
            __('Radni Nalozi', 'radni-nalozi'),
            'manage_options',
            'radni-nalozi',
            array($this, 'render_orders_page'),
            'dashicons-clipboard',
            30
        );
        
        add_submenu_page(
            'radni-nalozi',
            __('Svi nalozi', 'radni-nalozi'),
            __('Svi nalozi', 'radni-nalozi'),
            'manage_options',
            'radni-nalozi',
            array($this, 'render_orders_page')
        );
        
        add_submenu_page(
            'radni-nalozi',
            __('Veličine', 'radni-nalozi'),
            __('Veličine', 'radni-nalozi'),
            'manage_options',
            'radni-nalozi-sizes',
            array($this, 'render_sizes_page')
        );
        
        add_submenu_page(
            'radni-nalozi',
            __('Tipovi odevnih predmeta', 'radni-nalozi'),
            __('Tipovi', 'radni-nalozi'),
            'manage_options',
            'radni-nalozi-garment-types',
            array($this, 'render_garment_types_page')
        );
        
        add_submenu_page(
            'radni-nalozi',
            __('Kategorije (Pol)', 'radni-nalozi'),
            __('Pol', 'radni-nalozi'),
            'manage_options',
            'radni-nalozi-categories',
            array($this, 'render_categories_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'radni-nalozi') === false) {
            return;
        }
        
        wp_enqueue_style(
            'radni-nalozi-admin-style',
            RN_PLUGIN_URL . 'assets/css/admin/admin-style.css',
            array(),
            RN_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'radni-nalozi-admin-script',
            RN_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery'),
            RN_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script('radni-nalozi-admin-script', 'rnAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rn_admin_nonce'),
            'printUrl' => admin_url('admin.php?page=radni-nalozi&action=print'),
            'messages' => array(
                'confirmPrint' => __('Da li želite da štampate selektovane naloge? Status će biti promenjen u "U izradi".', 'radni-nalozi'),
                'noSelection' => __('Morate selektovati najmanje jedan nalog.', 'radni-nalozi'),
                'onlyNew' => __('Možete štampati samo naloge sa statusom "Nov".', 'radni-nalozi'),
                'statusUpdated' => __('Status je uspešno ažuriran.', 'radni-nalozi'),
                'error' => __('Došlo je do greške.', 'radni-nalozi'),
                'confirmBulkStatus' => __('Da li ste sigurni da želite da promenite status selektovanih naloga?', 'radni-nalozi'),
                'confirmDeleteSize' => __('Da li ste sigurni da želite da obrišete ovu veličinu?', 'radni-nalozi'),
                'sizeDeleted' => __('Veličina je uspešno obrisana.', 'radni-nalozi'),
                'sizeAdded' => __('Veličina je uspešno dodata.', 'radni-nalozi'),
                'sizeUpdated' => __('Veličina je uspešno ažurirana.', 'radni-nalozi')
            )
        ));
    }
    
    public function handle_admin_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_GET['page']) && $_GET['page'] === 'radni-nalozi' && isset($_GET['action'])) {
            if ($_GET['action'] === 'print' && isset($_GET['order_ids'])) {
                $this->render_print_page();
                exit;
            }
            
            if ($_GET['action'] === 'view' && isset($_GET['order_id'])) {
                return;
            }
        }
        
        if (isset($_POST['rn_update_status']) && isset($_POST['order_id']) && isset($_POST['new_status'])) {
            if (!wp_verify_nonce($_POST['rn_admin_nonce'], 'rn_admin_nonce')) {
                wp_die(__('Sigurnosna provera nije uspela.', 'radni-nalozi'));
            }
            
            $order_id = intval($_POST['order_id']);
            $new_status = sanitize_text_field($_POST['new_status']);
            
            RN_Orders::update_status($order_id, $new_status);
            
            wp_redirect(add_query_arg(array(
                'page' => 'radni-nalozi',
                'action' => 'view',
                'order_id' => $order_id,
                'updated' => '1'
            ), admin_url('admin.php')));
            exit;
        }
    }
    
    public function render_orders_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Nemate dozvolu za pristup ovoj stranici.', 'radni-nalozi'));
        }
        
        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['order_id'])) {
            $this->render_order_details($_GET['order_id']);
            return;
        }
        
        $this->render_orders_list();
    }
    
    private function render_orders_list() {
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        
        // Dobijanje naloga sa paginacijom
        $result = RN_Orders::get_all_orders_paginated(array(
            'status' => $status_filter,
            'search' => $search,
            'page' => $current_page,
            'per_page' => 25
        ));
        
        $orders = $result['orders'];
        $total_items = $result['total'];
        $total_pages = $result['total_pages'];
        
        $statuses = RN_Orders::get_all_statuses();
        ?>
        <div class="wrap rn-admin-wrap">
            <h1 class="wp-heading-inline"><?php _e('Radni Nalozi', 'radni-nalozi'); ?></h1>
            
            <div class="rn-admin-toolbar">
                <form method="get" class="rn-filter-form">
                    <input type="hidden" name="page" value="radni-nalozi">
                    
                    <select name="status" id="filter-status">
                        <option value=""><?php _e('Svi statusi', 'radni-nalozi'); ?></option>
                        <?php foreach ($statuses as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($status_filter, $key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" 
                           placeholder="<?php _e('Pretraži...', 'radni-nalozi'); ?>">
                    
                    <button type="submit" class="button"><?php _e('Filtriraj', 'radni-nalozi'); ?></button>
                </form>
                
                <div class="rn-bulk-actions">
                    <select id="rn-bulk-status-select" class="rn-bulk-status-select">
                        <option value=""><?php _e('Promeni status u...', 'radni-nalozi'); ?></option>
                        <?php foreach ($statuses as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="rn-bulk-status-apply" class="button" disabled>
                        <?php _e('Primeni', 'radni-nalozi'); ?>
                    </button>
                    <button type="button" id="rn-print-selected" class="button button-primary" disabled>
                        <?php _e('Štampaj selektovane', 'radni-nalozi'); ?>
                    </button>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped rn-orders-table">
                <thead>
                    <tr>
                        <th class="check-column">
                            <input type="checkbox" id="rn-select-all">
                        </th>
                        <th class="column-user"><?php _e('Korisnik', 'radni-nalozi'); ?></th>
                        <th class="column-order-number"><?php _e('Broj naloga', 'radni-nalozi'); ?></th>
                        <th class="column-customer"><?php _e('Kupac', 'radni-nalozi'); ?></th>
                        <th class="column-date"><?php _e('Datum', 'radni-nalozi'); ?></th>
                        <th class="column-total"><?php _e('Iznos', 'radni-nalozi'); ?></th>
                        <th class="column-status"><?php _e('Status', 'radni-nalozi'); ?></th>
                        <th class="column-actions"><?php _e('Akcije', 'radni-nalozi'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8"><?php _e('Nema pronađenih naloga.', 'radni-nalozi'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr data-order-id="<?php echo $order->id; ?>" data-status="<?php echo esc_attr($order->status); ?>">
                                <td class="check-column">
                                    <input type="checkbox" class="rn-order-checkbox" value="<?php echo $order->id; ?>">
                                </td>
                                <td class="column-user">
                                    <strong><?php echo esc_html($order->display_name ?: $order->user_login); ?></strong>
                                </td>
                                <td class="column-order-number">
                                    <strong><?php echo esc_html($order->order_number); ?></strong>
                                </td>
                                <td class="column-customer">
                                    <?php echo esc_html($order->customer_name); ?>
                                </td>
                                <td class="column-date">
                                    <?php echo date_i18n('d.m.Y H:i', strtotime($order->created_at)); ?>
                                </td>
                                <td class="column-total">
                                    <?php echo number_format($order->total_amount, 2, ',', '.'); ?> RSD
                                </td>
                                <td class="column-status">
                                    <span class="rn-status <?php echo RN_Orders::get_status_class($order->status); ?>">
                                        <?php echo RN_Orders::get_status_label($order->status); ?>
                                    </span>
                                </td>
                                <td class="column-actions">
                                    <a href="<?php echo esc_url(add_query_arg(array(
                                        'page' => 'radni-nalozi',
                                        'action' => 'view',
                                        'order_id' => $order->id
                                    ), admin_url('admin.php'))); ?>" class="button button-small">
                                        <?php _e('Detalji', 'radni-nalozi'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php printf(_n('%s nalog', '%s naloga', $total_items, 'radni-nalozi'), number_format_i18n($total_items)); ?>
                        </span>
                        <?php
                        $page_links = paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo; Prethodna', 'radni-nalozi'),
                            'next_text' => __('Sledeća &raquo;', 'radni-nalozi'),
                            'total' => $total_pages,
                            'current' => $current_page,
                            'type' => 'plain'
                        ));
                        
                        if ($page_links) {
                            echo '<span class="pagination-links">' . $page_links . '</span>';
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    private function render_order_details($order_id) {
        $order = RN_Orders::get_order(intval($order_id));
        
        if (!$order) {
            echo '<div class="wrap"><p>' . __('Nalog nije pronađen.', 'radni-nalozi') . '</p></div>';
            return;
        }
        
        $items = RN_Orders::get_order_items($order->id);
        $user = get_userdata($order->user_id);
        $statuses = RN_Orders::get_all_statuses();
        
        $updated = isset($_GET['updated']) && $_GET['updated'] === '1';
        ?>
        <div class="wrap rn-admin-wrap">
            <h1>
                <?php printf(__('Radni nalog: %s', 'radni-nalozi'), esc_html($order->order_number)); ?>
                <a href="<?php echo admin_url('admin.php?page=radni-nalozi'); ?>" class="page-title-action">
                    <?php _e('Nazad na listu', 'radni-nalozi'); ?>
                </a>
            </h1>
            
            <?php if ($updated): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Status je uspešno ažuriran.', 'radni-nalozi'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="rn-order-details-wrap">
                <div class="rn-order-main">
                    <div class="rn-card">
                        <h3><?php _e('Podaci o kupcu', 'radni-nalozi'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Ime kupca', 'radni-nalozi'); ?></th>
                                <td><?php echo esc_html($order->customer_name); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Adresa', 'radni-nalozi'); ?></th>
                                <td>
                                    <?php echo esc_html($order->customer_address); ?><br>
                                    <?php echo esc_html($order->customer_postal); ?> <?php echo esc_html($order->customer_city); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Telefon', 'radni-nalozi'); ?></th>
                                <td><?php echo esc_html($order->customer_phone); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="rn-card">
                        <h3><?php _e('Stavke naloga', 'radni-nalozi'); ?></h3>
                        <?php if (!empty($items)): ?>
                            <div class="rn-items-list">
                                <?php foreach ($items as $index => $item): ?>
                                    <div class="rn-item-row">
                                        <div class="rn-item-number"><?php echo $index + 1; ?></div>
                                        <div class="rn-item-info">
                                            <strong><?php echo esc_html($item->print_name); ?></strong>
                                            <div class="rn-item-meta">
                                                <span><?php _e('Tip:', 'radni-nalozi'); ?> <?php echo esc_html($item->garment_type ?? 'Majica'); ?></span>
                                                <span><?php _e('Pol:', 'radni-nalozi'); ?> <?php echo esc_html($item->category ?? 'Muška'); ?></span>
                                                <span><?php _e('Boja:', 'radni-nalozi'); ?> <?php echo esc_html($item->color); ?></span>
                                                <span><?php _e('Veličina:', 'radni-nalozi'); ?> <?php echo esc_html($item->size); ?></span>
                                                <span><?php _e('Količina:', 'radni-nalozi'); ?> <?php echo intval($item->quantity); ?></span>
                                                <span><?php _e('Cena:', 'radni-nalozi'); ?> <?php echo number_format($item->price, 2, ',', '.'); ?> RSD</span>
                                            </div>
                                            <?php if ($item->note): ?>
                                                <div class="rn-item-note">
                                                    <strong><?php _e('Napomena:', 'radni-nalozi'); ?></strong> <?php echo esc_html($item->note); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($item->image_url): ?>
                                            <div class="rn-item-image">
                                                <img src="<?php echo esc_url($item->image_url); ?>" alt="">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="rn-order-total">
                                <strong><?php _e('UKUPNO:', 'radni-nalozi'); ?></strong>
                                <?php echo number_format($order->total_amount, 2, ',', '.'); ?> RSD
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="rn-order-sidebar">
                    <div class="rn-card">
                        <h3><?php _e('Informacije o nalogu', 'radni-nalozi'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Korisnik', 'radni-nalozi'); ?></th>
                                <td><?php echo $user ? esc_html($user->display_name) : '-'; ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Kreiran', 'radni-nalozi'); ?></th>
                                <td><?php echo date_i18n('d.m.Y H:i', strtotime($order->created_at)); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Izmenjen', 'radni-nalozi'); ?></th>
                                <td><?php echo date_i18n('d.m.Y H:i', strtotime($order->updated_at)); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="rn-card">
                        <h3><?php _e('Status naloga', 'radni-nalozi'); ?></h3>
                        <form method="post" action="<?php echo admin_url('admin.php?page=radni-nalozi'); ?>" class="rn-status-form">
                            <?php wp_nonce_field('rn_admin_nonce', 'rn_admin_nonce'); ?>
                            <input type="hidden" name="order_id" value="<?php echo $order->id; ?>">
                            
                            <p class="rn-current-status">
                                <?php _e('Trenutni status:', 'radni-nalozi'); ?>
                                <span class="rn-status <?php echo RN_Orders::get_status_class($order->status); ?>">
                                    <?php echo RN_Orders::get_status_label($order->status); ?>
                                </span>
                            </p>
                            
                            <select name="new_status" class="rn-status-select">
                                <?php foreach ($statuses as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($order->status, $key); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <button type="submit" name="rn_update_status" value="1" class="button button-primary">
                                <?php _e('Ažuriraj status', 'radni-nalozi'); ?>
                            </button>
                        </form>
                    </div>
                    
                    <div class="rn-card">
                        <a href="<?php echo esc_url(add_query_arg(array(
                            'page' => 'radni-nalozi',
                            'action' => 'print',
                            'order_ids' => $order->id,
                            'update_status' => '1'
                        ), admin_url('admin.php'))); ?>" class="button button-secondary" target="_blank">
                            <?php _e('Štampaj nalog', 'radni-nalozi'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_print_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Nemate dozvolu za pristup.', 'radni-nalozi'));
        }
        
        $order_ids = isset($_GET['order_ids']) ? sanitize_text_field($_GET['order_ids']) : '';
        $ids = array_filter(array_map('intval', explode(',', $order_ids)));
        
        if (empty($ids)) {
            wp_die(__('Nisu selektovani nalozi za štampu.', 'radni-nalozi'));
        }
        
        global $wpdb;
        $orders_table = RN_Database::get_orders_table();
        
        if (isset($_GET['update_status']) && $_GET['update_status'] === '1') {
            foreach ($ids as $id) {
                $order = RN_Orders::get_order($id);
                if ($order && $order->status === 'nov') {
                    RN_Orders::update_status($id, 'u_izradi');
                }
            }
        }
        
        $orders_data = array();
        foreach ($ids as $id) {
            $order = RN_Orders::get_order($id);
            if ($order) {
                $order->items = RN_Orders::get_order_items($id);
                $order->user = get_userdata($order->user_id);
                $orders_data[] = $order;
            }
        }
        
        include RN_PLUGIN_PATH . 'templates/admin/print-orders.php';
    }
    
    public function ajax_update_status() {
        if (!wp_verify_nonce($_POST['nonce'], 'rn_admin_nonce')) {
            wp_send_json_error(array('message' => __('Sigurnosna provera nije uspela.', 'radni-nalozi')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Nemate dozvolu.', 'radni-nalozi')));
        }
        
        $order_id = intval($_POST['order_id']);
        $new_status = sanitize_text_field($_POST['new_status']);
        
        $result = RN_Orders::update_status($order_id, $new_status);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Status ažuriran.', 'radni-nalozi')));
        } else {
            wp_send_json_error(array('message' => __('Greška pri ažuriranju.', 'radni-nalozi')));
        }
    }
    
    public function ajax_bulk_print() {
        if (!wp_verify_nonce($_POST['nonce'], 'rn_admin_nonce')) {
            wp_send_json_error(array('message' => __('Sigurnosna provera nije uspela.', 'radni-nalozi')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Nemate dozvolu.', 'radni-nalozi')));
        }
        
        $order_ids = isset($_POST['order_ids']) ? array_map('intval', $_POST['order_ids']) : array();
        
        if (empty($order_ids)) {
            wp_send_json_error(array('message' => __('Nisu selektovani nalozi.', 'radni-nalozi')));
        }
        
        $valid_ids = array();
        foreach ($order_ids as $id) {
            $order = RN_Orders::get_order($id);
            if ($order && $order->status === 'nov') {
                RN_Orders::update_status($id, 'u_izradi');
                $valid_ids[] = $id;
            }
        }
        
        if (empty($valid_ids)) {
            wp_send_json_error(array('message' => __('Nema naloga sa statusom "Nov" za štampu.', 'radni-nalozi')));
        }
        
        $print_url = add_query_arg(array(
            'page' => 'radni-nalozi',
            'action' => 'print',
            'order_ids' => implode(',', $valid_ids)
        ), admin_url('admin.php'));
        
        wp_send_json_success(array(
            'print_url' => $print_url,
            'count' => count($valid_ids)
        ));
    }
    
    public function ajax_bulk_status() {
        if (!wp_verify_nonce($_POST['nonce'], 'rn_admin_nonce')) {
            wp_send_json_error(array('message' => __('Sigurnosna provera nije uspela.', 'radni-nalozi')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Nemate dozvolu.', 'radni-nalozi')));
        }
        
        $order_ids = isset($_POST['order_ids']) ? array_map('intval', $_POST['order_ids']) : array();
        $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';
        
        if (empty($order_ids)) {
            wp_send_json_error(array('message' => __('Nisu selektovani nalozi.', 'radni-nalozi')));
        }
        
        if (empty($new_status)) {
            wp_send_json_error(array('message' => __('Status nije izabran.', 'radni-nalozi')));
        }
        
        $updated = 0;
        foreach ($order_ids as $id) {
            if (RN_Orders::update_status($id, $new_status)) {
                $updated++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('Ažurirano %d naloga.', 'radni-nalozi'), $updated),
            'count' => $updated
        ));
    }
    
    public function ajax_add_size() {
        if (!wp_verify_nonce($_POST['nonce'], 'rn_admin_nonce')) {
            wp_send_json_error(array('message' => __('Sigurnosna provera nije uspela.', 'radni-nalozi')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Nemate dozvolu.', 'radni-nalozi')));
        }
        
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
        
        if (empty($name)) {
            wp_send_json_error(array('message' => __('Naziv veličine je obavezan.', 'radni-nalozi')));
        }
        
        $result = RN_Sizes::add_size($name, $sort_order);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => __('Veličina je dodata.', 'radni-nalozi'),
            'id' => $result
        ));
    }
    
    public function ajax_update_size() {
        if (!wp_verify_nonce($_POST['nonce'], 'rn_admin_nonce')) {
            wp_send_json_error(array('message' => __('Sigurnosna provera nije uspela.', 'radni-nalozi')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Nemate dozvolu.', 'radni-nalozi')));
        }
        
        $id = isset($_POST['size_id']) ? intval($_POST['size_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
        $active = isset($_POST['active']) ? intval($_POST['active']) : 1;
        
        if (!$id) {
            wp_send_json_error(array('message' => __('ID veličine nije validan.', 'radni-nalozi')));
        }
        
        $result = RN_Sizes::update_size($id, array(
            'name' => $name,
            'sort_order' => $sort_order,
            'active' => $active
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Veličina je ažurirana.', 'radni-nalozi')));
    }
    
    public function ajax_delete_size() {
        if (!wp_verify_nonce($_POST['nonce'], 'rn_admin_nonce')) {
            wp_send_json_error(array('message' => __('Sigurnosna provera nije uspela.', 'radni-nalozi')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Nemate dozvolu.', 'radni-nalozi')));
        }
        
        $id = isset($_POST['size_id']) ? intval($_POST['size_id']) : 0;
        
        if (!$id) {
            wp_send_json_error(array('message' => __('ID veličine nije validan.', 'radni-nalozi')));
        }
        
        $result = RN_Sizes::delete_size($id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Veličina je obrisana.', 'radni-nalozi')));
        } else {
            wp_send_json_error(array('message' => __('Greška pri brisanju.', 'radni-nalozi')));
        }
    }
    
    public function render_sizes_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Nemate dozvolu za pristup ovoj stranici.', 'radni-nalozi'));
        }
        
        $sizes = RN_Sizes::get_all_sizes(false);
        ?>
        <div class="wrap rn-admin-wrap">
            <h1 class="wp-heading-inline"><?php _e('Upravljanje veličinama', 'radni-nalozi'); ?></h1>
            
            <div class="rn-sizes-wrapper">
                <div class="rn-sizes-form-card rn-card">
                    <h3><?php _e('Dodaj novu veličinu', 'radni-nalozi'); ?></h3>
                    <form id="rn-add-size-form" class="rn-size-form">
                        <div class="rn-form-row">
                            <div class="rn-form-group">
                                <label for="size_name"><?php _e('Naziv', 'radni-nalozi'); ?> *</label>
                                <input type="text" id="size_name" name="name" required>
                            </div>
                            <div class="rn-form-group">
                                <label for="size_sort"><?php _e('Redosled', 'radni-nalozi'); ?></label>
                                <input type="number" id="size_sort" name="sort_order" value="0" min="0">
                            </div>
                            <div class="rn-form-group rn-form-submit">
                                <button type="submit" class="button button-primary"><?php _e('Dodaj', 'radni-nalozi'); ?></button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="rn-card">
                    <h3><?php _e('Postojeće veličine', 'radni-nalozi'); ?></h3>
                    <table class="wp-list-table widefat fixed striped rn-sizes-table">
                        <thead>
                            <tr>
                                <th class="column-id"><?php _e('ID', 'radni-nalozi'); ?></th>
                                <th class="column-name"><?php _e('Naziv', 'radni-nalozi'); ?></th>
                                <th class="column-sort"><?php _e('Redosled', 'radni-nalozi'); ?></th>
                                <th class="column-status"><?php _e('Status', 'radni-nalozi'); ?></th>
                                <th class="column-actions"><?php _e('Akcije', 'radni-nalozi'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="rn-sizes-list">
                            <?php if (empty($sizes)): ?>
                                <tr class="rn-no-sizes">
                                    <td colspan="5"><?php _e('Nema definisanih veličina.', 'radni-nalozi'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sizes as $size): ?>
                                    <tr data-size-id="<?php echo $size->id; ?>">
                                        <td class="column-id"><?php echo $size->id; ?></td>
                                        <td class="column-name">
                                            <input type="text" class="rn-size-name-input" value="<?php echo esc_attr($size->name); ?>" data-original="<?php echo esc_attr($size->name); ?>">
                                        </td>
                                        <td class="column-sort">
                                            <input type="number" class="rn-size-sort-input" value="<?php echo intval($size->sort_order); ?>" min="0" data-original="<?php echo intval($size->sort_order); ?>">
                                        </td>
                                        <td class="column-status">
                                            <label class="rn-toggle">
                                                <input type="checkbox" class="rn-size-active-input" <?php checked($size->active, 1); ?>>
                                                <span class="rn-toggle-slider"></span>
                                            </label>
                                        </td>
                                        <td class="column-actions">
                                            <button type="button" class="button button-small rn-save-size" style="display:none;"><?php _e('Sačuvaj', 'radni-nalozi'); ?></button>
                                            <button type="button" class="button button-small button-link-delete rn-delete-size"><?php _e('Obriši', 'radni-nalozi'); ?></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function render_garment_types_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Nemate dozvolu za pristup ovoj stranici.', 'radni-nalozi'));
        }
        
        $message = '';
        $message_type = '';
        
        // Handle form submission (fallback when AJAX doesn't work)
        if (isset($_POST['rn_add_garment_type']) && $_POST['rn_add_garment_type'] == '1') {
            if (wp_verify_nonce($_POST['rn_garment_type_nonce'], 'rn_add_garment_type_form')) {
                $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
                $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
                
                if (!empty($name)) {
                    $id = RN_Garment_Types::add_type($name, $sort_order);
                    if ($id) {
                        $message = __('Tip uspešno dodat.', 'radni-nalozi');
                        $message_type = 'success';
                    } else {
                        $message = __('Greška prilikom dodavanja tipa.', 'radni-nalozi');
                        $message_type = 'error';
                    }
                } else {
                    $message = __('Naziv je obavezan.', 'radni-nalozi');
                    $message_type = 'error';
                }
            }
        }
        
        $garment_types = RN_Garment_Types::get_all_types(false);
        ?>
        <div class="wrap rn-admin-wrap">
            <h1 class="wp-heading-inline"><?php _e('Upravljanje tipovima odevnih predmeta', 'radni-nalozi'); ?></h1>
            
            <?php if ($message): ?>
                <div class="notice notice-<?php echo $message_type === 'success' ? 'success' : 'error'; ?> is-dismissible">
                    <p><?php echo esc_html($message); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="rn-sizes-wrapper">
                <div class="rn-sizes-form-card rn-card">
                    <h3><?php _e('Dodaj novi tip', 'radni-nalozi'); ?></h3>
                    <form id="rn-add-garment-type-form" class="rn-size-form" method="post" action="">
                        <?php wp_nonce_field('rn_add_garment_type_form', 'rn_garment_type_nonce'); ?>
                        <input type="hidden" name="rn_add_garment_type" value="1">
                        <div class="rn-form-row">
                            <div class="rn-form-group">
                                <label for="garment_type_name"><?php _e('Naziv', 'radni-nalozi'); ?> *</label>
                                <input type="text" id="garment_type_name" name="name" required placeholder="npr. Majica, Duks, Kapa">
                            </div>
                            <div class="rn-form-group">
                                <label for="garment_type_sort"><?php _e('Redosled', 'radni-nalozi'); ?></label>
                                <input type="number" id="garment_type_sort" name="sort_order" value="0" min="0">
                            </div>
                            <div class="rn-form-group rn-form-submit">
                                <button type="submit" class="button button-primary"><?php _e('Dodaj', 'radni-nalozi'); ?></button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="rn-card">
                    <h3><?php _e('Postojeći tipovi', 'radni-nalozi'); ?></h3>
                    <table class="wp-list-table widefat fixed striped rn-sizes-table">
                        <thead>
                            <tr>
                                <th class="column-id"><?php _e('ID', 'radni-nalozi'); ?></th>
                                <th class="column-name"><?php _e('Naziv', 'radni-nalozi'); ?></th>
                                <th class="column-sort"><?php _e('Redosled', 'radni-nalozi'); ?></th>
                                <th class="column-status"><?php _e('Status', 'radni-nalozi'); ?></th>
                                <th class="column-actions"><?php _e('Akcije', 'radni-nalozi'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="rn-garment-types-list">
                            <?php if (empty($garment_types)): ?>
                                <tr class="rn-no-garment-types">
                                    <td colspan="5"><?php _e('Nema definisanih tipova.', 'radni-nalozi'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($garment_types as $type): ?>
                                    <tr data-garment-type-id="<?php echo $type->id; ?>">
                                        <td class="column-id"><?php echo $type->id; ?></td>
                                        <td class="column-name">
                                            <input type="text" class="rn-garment-type-name-input" value="<?php echo esc_attr($type->name); ?>" data-original="<?php echo esc_attr($type->name); ?>">
                                        </td>
                                        <td class="column-sort">
                                            <input type="number" class="rn-garment-type-sort-input" value="<?php echo intval($type->sort_order); ?>" min="0" data-original="<?php echo intval($type->sort_order); ?>">
                                        </td>
                                        <td class="column-status">
                                            <label class="rn-toggle">
                                                <input type="checkbox" class="rn-garment-type-active-input" <?php checked($type->active, 1); ?>>
                                                <span class="rn-toggle-slider"></span>
                                            </label>
                                        </td>
                                        <td class="column-actions">
                                            <button type="button" class="button button-small rn-save-garment-type" style="display:none;"><?php _e('Sačuvaj', 'radni-nalozi'); ?></button>
                                            <button type="button" class="button button-small button-link-delete rn-delete-garment-type"><?php _e('Obriši', 'radni-nalozi'); ?></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function ajax_add_garment_type() {
        check_ajax_referer('rn_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Nemate dozvolu.', 'radni-nalozi')));
        }
        
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
        
        if (empty($name)) {
            wp_send_json_error(array('message' => __('Naziv je obavezan.', 'radni-nalozi')));
        }
        
        $id = RN_Garment_Types::add_type($name, $sort_order);
        
        if ($id) {
            wp_send_json_success(array(
                'message' => __('Tip uspešno dodat.', 'radni-nalozi'),
                'id' => $id,
                'name' => $name,
                'sort_order' => $sort_order
            ));
        } else {
            wp_send_json_error(array('message' => __('Greška pri dodavanju tipa. Možda već postoji.', 'radni-nalozi')));
        }
    }
    
    public function ajax_update_garment_type() {
        check_ajax_referer('rn_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Nemate dozvolu.', 'radni-nalozi')));
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!$id) {
            wp_send_json_error(array('message' => __('Nevažeći ID tipa.', 'radni-nalozi')));
        }
        
        $data = array();
        
        if (isset($_POST['name'])) {
            $data['name'] = sanitize_text_field($_POST['name']);
        }
        if (isset($_POST['sort_order'])) {
            $data['sort_order'] = intval($_POST['sort_order']);
        }
        if (isset($_POST['active'])) {
            $data['active'] = intval($_POST['active']);
        }
        
        $result = RN_Garment_Types::update_type($id, $data);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Tip uspešno ažuriran.', 'radni-nalozi')));
        } else {
            wp_send_json_error(array('message' => __('Greška pri ažuriranju tipa.', 'radni-nalozi')));
        }
    }
    
    public function ajax_delete_garment_type() {
        check_ajax_referer('rn_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Nemate dozvolu.', 'radni-nalozi')));
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!$id) {
            wp_send_json_error(array('message' => __('Nevažeći ID tipa.', 'radni-nalozi')));
        }
        
        $result = RN_Garment_Types::delete_type($id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Tip uspešno obrisan.', 'radni-nalozi')));
        } else {
            wp_send_json_error(array('message' => __('Greška pri brisanju tipa.', 'radni-nalozi')));
        }
    }
    
    public function render_categories_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Nemate dozvolu za pristup ovoj stranici.', 'radni-nalozi'));
        }
        
        $message = '';
        $message_type = '';
        
        if (isset($_POST['rn_add_category']) && $_POST['rn_add_category'] == '1') {
            if (wp_verify_nonce($_POST['rn_category_nonce'], 'rn_add_category_form')) {
                $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
                $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
                
                if (!empty($name)) {
                    $result = RN_Categories::add_category($name, $sort_order);
                    if ($result) {
                        $message = __('Kategorija uspešno dodata.', 'radni-nalozi');
                        $message_type = 'success';
                    } else {
                        $message = __('Greška pri dodavanju kategorije.', 'radni-nalozi');
                        $message_type = 'error';
                    }
                } else {
                    $message = __('Naziv kategorije je obavezan.', 'radni-nalozi');
                    $message_type = 'error';
                }
            }
        }
        
        $categories = RN_Categories::get_all_categories(false);
        ?>
        <div class="wrap rn-admin-wrap">
            <h1><?php _e('Upravljanje kategorijama (Pol)', 'radni-nalozi'); ?></h1>
            
            <?php if ($message): ?>
                <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
                    <p><?php echo esc_html($message); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="rn-admin-content">
                <div class="rn-card">
                    <h3><?php _e('Dodaj novu kategoriju', 'radni-nalozi'); ?></h3>
                    <form id="rn-add-category-form" class="rn-size-form" method="post" action="">
                        <?php wp_nonce_field('rn_add_category_form', 'rn_category_nonce'); ?>
                        <input type="hidden" name="rn_add_category" value="1">
                        <div class="rn-form-row">
                            <div class="rn-form-group">
                                <label for="category_name"><?php _e('Naziv', 'radni-nalozi'); ?> *</label>
                                <input type="text" id="category_name" name="name" required placeholder="npr. Muška, Ženska, Dečija">
                            </div>
                            <div class="rn-form-group">
                                <label for="category_sort"><?php _e('Redosled', 'radni-nalozi'); ?></label>
                                <input type="number" id="category_sort" name="sort_order" value="0" min="0">
                            </div>
                            <div class="rn-form-group rn-form-submit">
                                <button type="submit" class="button button-primary"><?php _e('Dodaj kategoriju', 'radni-nalozi'); ?></button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="rn-card">
                    <h3><?php _e('Postojeće kategorije', 'radni-nalozi'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th class="column-id">ID</th>
                                <th><?php _e('Naziv', 'radni-nalozi'); ?></th>
                                <th><?php _e('Redosled', 'radni-nalozi'); ?></th>
                                <th><?php _e('Status', 'radni-nalozi'); ?></th>
                                <th><?php _e('Akcije', 'radni-nalozi'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="rn-categories-list">
                            <?php if (empty($categories)): ?>
                                <tr class="rn-no-categories">
                                    <td colspan="5"><?php _e('Nema definisanih kategorija.', 'radni-nalozi'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $category): ?>
                                    <tr data-category-id="<?php echo $category->id; ?>">
                                        <td class="column-id"><?php echo $category->id; ?></td>
                                        <td class="column-name"><?php echo esc_html($category->name); ?></td>
                                        <td class="column-sort"><?php echo intval($category->sort_order); ?></td>
                                        <td class="column-status">
                                            <span class="rn-status <?php echo $category->active ? 'rn-status-active' : 'rn-status-inactive'; ?>">
                                                <?php echo $category->active ? __('Aktivna', 'radni-nalozi') : __('Neaktivna', 'radni-nalozi'); ?>
                                            </span>
                                        </td>
                                        <td class="column-actions">
                                            <button type="button" class="button button-small rn-toggle-category" data-id="<?php echo $category->id; ?>" data-active="<?php echo $category->active ? '0' : '1'; ?>">
                                                <?php echo $category->active ? __('Deaktiviraj', 'radni-nalozi') : __('Aktiviraj', 'radni-nalozi'); ?>
                                            </button>
                                            <button type="button" class="button button-small button-link-delete rn-delete-category" data-id="<?php echo $category->id; ?>">
                                                <?php _e('Obriši', 'radni-nalozi'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function ajax_add_category() {
        check_ajax_referer('rn_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Nemate dozvolu.', 'radni-nalozi')));
        }
        
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
        
        if (empty($name)) {
            wp_send_json_error(array('message' => __('Naziv je obavezan.', 'radni-nalozi')));
        }
        
        $result = RN_Categories::add_category($name, $sort_order);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Kategorija uspešno dodata.', 'radni-nalozi'),
                'id' => $result
            ));
        } else {
            wp_send_json_error(array('message' => __('Greška pri dodavanju kategorije. Možda već postoji.', 'radni-nalozi')));
        }
    }
    
    public function ajax_update_category() {
        check_ajax_referer('rn_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Nemate dozvolu.', 'radni-nalozi')));
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!$id) {
            wp_send_json_error(array('message' => __('Nevažeći ID kategorije.', 'radni-nalozi')));
        }
        
        $data = array();
        
        if (isset($_POST['name'])) {
            $data['name'] = sanitize_text_field($_POST['name']);
        }
        if (isset($_POST['sort_order'])) {
            $data['sort_order'] = intval($_POST['sort_order']);
        }
        if (isset($_POST['active'])) {
            $data['active'] = intval($_POST['active']);
        }
        
        $result = RN_Categories::update_category($id, $data);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Kategorija uspešno ažurirana.', 'radni-nalozi')));
        } else {
            wp_send_json_error(array('message' => __('Greška pri ažuriranju kategorije.', 'radni-nalozi')));
        }
    }
    
    public function ajax_delete_category() {
        check_ajax_referer('rn_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Nemate dozvolu.', 'radni-nalozi')));
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!$id) {
            wp_send_json_error(array('message' => __('Nevažeći ID kategorije.', 'radni-nalozi')));
        }
        
        $result = RN_Categories::delete_category($id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Kategorija uspešno obrisana.', 'radni-nalozi')));
        } else {
            wp_send_json_error(array('message' => __('Greška pri brisanju kategorije.', 'radni-nalozi')));
        }
    }
}
