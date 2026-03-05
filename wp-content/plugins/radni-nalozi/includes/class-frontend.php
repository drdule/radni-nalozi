<?php
if (!defined('ABSPATH')) {
    exit;
}

class RN_Frontend {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_shortcode('radni_nalozi', array($this, 'render_shortcode'));
    }
    
    public function render_shortcode($atts) {
        ob_start();
        
        if (!is_user_logged_in()) {
            $this->render_login_form();
        } else {
            $this->render_dashboard();
        }
        
        return ob_get_clean();
    }
    
    private function render_login_form() {
        ?>
        <div class="rn-container">
            <div class="rn-login-wrapper">
                <div class="rn-login-box">
                    <h2><?php _e('Prijava', 'radni-nalozi'); ?></h2>
                    <form id="rn-login-form" class="rn-form">
                        <div class="rn-form-group">
                            <label for="rn-username"><?php _e('Korisničko ime', 'radni-nalozi'); ?></label>
                            <input type="text" id="rn-username" name="username" required>
                        </div>
                        <div class="rn-form-group">
                            <label for="rn-password"><?php _e('Lozinka', 'radni-nalozi'); ?></label>
                            <input type="password" id="rn-password" name="password" required>
                        </div>
                        <div class="rn-form-group">
                            <button type="submit" class="rn-btn rn-btn-primary"><?php _e('Prijavi se', 'radni-nalozi'); ?></button>
                        </div>
                        <div class="rn-message" style="display: none;"></div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_dashboard() {
        $current_user = wp_get_current_user();
        $view = isset($_GET['rn_view']) ? sanitize_text_field($_GET['rn_view']) : 'list';
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        ?>
        <div class="rn-container">
            <div class="rn-header">
                <div class="rn-header-left">
                    <h2><?php printf(__('Dobrodošli, %s', 'radni-nalozi'), esc_html($current_user->display_name)); ?></h2>
                </div>
                <div class="rn-header-right">
                    <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="rn-btn rn-btn-secondary"><?php _e('Odjavi se', 'radni-nalozi'); ?></a>
                </div>
            </div>
            
            <div class="rn-nav">
                <a href="<?php echo esc_url(add_query_arg('rn_view', 'list', get_permalink())); ?>" 
                   class="rn-nav-item <?php echo $view === 'list' ? 'active' : ''; ?>">
                    <?php _e('Moji nalozi', 'radni-nalozi'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg('rn_view', 'new', get_permalink())); ?>" 
                   class="rn-nav-item <?php echo $view === 'new' ? 'active' : ''; ?>">
                    <?php _e('Novi nalog', 'radni-nalozi'); ?>
                </a>
            </div>
            
            <div class="rn-content">
                <?php
                switch ($view) {
                    case 'new':
                        $this->render_order_form();
                        break;
                    case 'edit':
                        $this->render_order_form($order_id);
                        break;
                    case 'view':
                        $this->render_order_view($order_id);
                        break;
                    default:
                        $this->render_orders_list();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    private function render_orders_list() {
        $orders = RN_Orders::get_user_orders(get_current_user_id());
        ?>
        <div class="rn-orders-list">
            <h3><?php _e('Vaši radni nalozi', 'radni-nalozi'); ?></h3>
            
            <?php if (empty($orders)): ?>
                <div class="rn-empty-state">
                    <p><?php _e('Nemate kreirane radne naloge.', 'radni-nalozi'); ?></p>
                    <a href="<?php echo esc_url(add_query_arg('rn_view', 'new', get_permalink())); ?>" class="rn-btn rn-btn-primary">
                        <?php _e('Kreiraj prvi nalog', 'radni-nalozi'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="rn-table-responsive">
                    <table class="rn-table">
                        <thead>
                            <tr>
                                <th><?php _e('Broj naloga', 'radni-nalozi'); ?></th>
                                <th><?php _e('Kupac', 'radni-nalozi'); ?></th>
                                <th><?php _e('Datum', 'radni-nalozi'); ?></th>
                                <th><?php _e('Iznos', 'radni-nalozi'); ?></th>
                                <th><?php _e('Status', 'radni-nalozi'); ?></th>
                                <th><?php _e('Akcije', 'radni-nalozi'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($order->order_number); ?></strong></td>
                                    <td><?php echo esc_html($order->customer_name); ?></td>
                                    <td><?php echo date_i18n('d.m.Y H:i', strtotime($order->created_at)); ?></td>
                                    <td><?php echo number_format($order->total_amount, 2, ',', '.'); ?> RSD</td>
                                    <td>
                                        <span class="rn-status <?php echo RN_Orders::get_status_class($order->status); ?>">
                                            <?php echo RN_Orders::get_status_label($order->status); ?>
                                        </span>
                                    </td>
                                    <td class="rn-actions">
                                        <?php if ($order->status === 'nov'): ?>
                                            <a href="<?php echo esc_url(add_query_arg(array('rn_view' => 'edit', 'order_id' => $order->id), get_permalink())); ?>" 
                                               class="rn-btn rn-btn-small rn-btn-primary"><?php _e('Izmeni', 'radni-nalozi'); ?></a>
                                        <?php endif; ?>
                                        
                                        <a href="<?php echo esc_url(add_query_arg(array('rn_view' => 'view', 'order_id' => $order->id), get_permalink())); ?>" 
                                           class="rn-btn rn-btn-small rn-btn-secondary"><?php _e('Pregled', 'radni-nalozi'); ?></a>
                                        
                                        <?php if ($order->status !== 'storniran'): ?>
                                            <button type="button" class="rn-btn rn-btn-small rn-btn-danger rn-cancel-order" 
                                                    data-order-id="<?php echo $order->id; ?>">
                                                <?php _e('Storniraj', 'radni-nalozi'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    private function render_order_form($order_id = 0) {
        $order = null;
        $items = array();
        $is_edit = false;
        
        if ($order_id) {
            $order = RN_Orders::get_order($order_id);
            if ($order && $order->user_id == get_current_user_id() && $order->status === 'nov') {
                $items = RN_Orders::get_order_items($order_id);
                $is_edit = true;
            } else {
                echo '<p class="rn-error">' . __('Nalog nije pronađen ili se ne može menjati.', 'radni-nalozi') . '</p>';
                return;
            }
        }
        ?>
        <div class="rn-order-form-wrapper">
            <h3><?php echo $is_edit ? __('Izmena radnog naloga', 'radni-nalozi') : __('Novi radni nalog', 'radni-nalozi'); ?></h3>
            
            <form id="rn-order-form" class="rn-form" data-order-id="<?php echo $order_id; ?>">
                <div class="rn-form-section">
                    <h4><?php _e('Podaci o kupcu', 'radni-nalozi'); ?></h4>
                    
                    <div class="rn-form-row">
                        <div class="rn-form-group rn-full-width">
                            <label for="customer_name"><?php _e('Ime kupca', 'radni-nalozi'); ?> *</label>
                            <input type="text" id="customer_name" name="customer_name" required
                                   value="<?php echo $order ? esc_attr($order->customer_name) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="rn-form-row">
                        <div class="rn-form-group rn-half-width">
                            <label for="customer_address"><?php _e('Ulica i broj', 'radni-nalozi'); ?></label>
                            <input type="text" id="customer_address" name="customer_address"
                                   value="<?php echo $order ? esc_attr($order->customer_address) : ''; ?>">
                        </div>
                        <div class="rn-form-group rn-quarter-width">
                            <label for="customer_postal"><?php _e('Poštanski broj', 'radni-nalozi'); ?></label>
                            <input type="text" id="customer_postal" name="customer_postal"
                                   value="<?php echo $order ? esc_attr($order->customer_postal) : ''; ?>">
                        </div>
                        <div class="rn-form-group rn-quarter-width">
                            <label for="customer_city"><?php _e('Grad', 'radni-nalozi'); ?></label>
                            <input type="text" id="customer_city" name="customer_city"
                                   value="<?php echo $order ? esc_attr($order->customer_city) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="rn-form-row">
                        <div class="rn-form-group rn-half-width">
                            <label for="customer_phone"><?php _e('Kontakt telefon', 'radni-nalozi'); ?></label>
                            <input type="tel" id="customer_phone" name="customer_phone"
                                   value="<?php echo $order ? esc_attr($order->customer_phone) : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="rn-form-section">
                    <h4><?php _e('Stavke naloga', 'radni-nalozi'); ?></h4>
                    
                    <div id="rn-items-container">
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $index => $item): ?>
                                <?php $this->render_item_template($index, $item); ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php $this->render_item_template(0); ?>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" id="rn-add-item" class="rn-btn rn-btn-secondary">
                        + <?php _e('Dodaj novu stavku', 'radni-nalozi'); ?>
                    </button>
                </div>
                
                <div class="rn-form-actions">
                    <a href="<?php echo esc_url(add_query_arg('rn_view', 'list', get_permalink())); ?>" class="rn-btn rn-btn-secondary">
                        <?php _e('Otkaži', 'radni-nalozi'); ?>
                    </a>
                    <button type="submit" class="rn-btn rn-btn-primary">
                        <?php echo $is_edit ? __('Sačuvaj izmene', 'radni-nalozi') : __('Kreiraj nalog', 'radni-nalozi'); ?>
                    </button>
                </div>
                
                <div class="rn-message" style="display: none;"></div>
            </form>
        </div>
        
        <template id="rn-item-template">
            <?php $this->render_item_template('{{index}}'); ?>
        </template>
        <?php
    }
    
    private function render_item_template($index, $item = null) {
        $sizes = RN_Sizes::get_sizes_for_select();
        $garment_types = RN_Garment_Types::get_types_for_select();
        $categories = RN_Categories::get_categories_for_select();
        ?>
        <div class="rn-item-block" data-index="<?php echo $index; ?>">
            <div class="rn-item-header">
                <span class="rn-item-title"><?php _e('Stavka', 'radni-nalozi'); ?> #<span class="rn-item-number"><?php echo is_numeric($index) ? $index + 1 : '{{number}}'; ?></span></span>
                <button type="button" class="rn-remove-item" title="<?php _e('Ukloni stavku', 'radni-nalozi'); ?>">&times;</button>
            </div>
            
            <div class="rn-form-row">
                <div class="rn-form-group rn-small-width">
                    <label><?php _e('Tip', 'radni-nalozi'); ?> *</label>
                    <select name="items[<?php echo $index; ?>][garment_type]" required>
                        <option value=""><?php _e('Izaberi', 'radni-nalozi'); ?></option>
                        <?php foreach ($garment_types as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php echo ($item && isset($item->garment_type) && $item->garment_type === $value) ? 'selected' : ''; ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="rn-form-group rn-small-width">
                    <label><?php _e('Pol', 'radni-nalozi'); ?> *</label>
                    <select name="items[<?php echo $index; ?>][category]" required>
                        <option value=""><?php _e('Izaberi', 'radni-nalozi'); ?></option>
                        <?php foreach ($categories as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php echo ($item && isset($item->category) && $item->category === $value) ? 'selected' : ''; ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="rn-form-group rn-small-width">
                    <label><?php _e('Veličina', 'radni-nalozi'); ?> *</label>
                    <select name="items[<?php echo $index; ?>][size]" required>
                        <option value=""><?php _e('Izaberi', 'radni-nalozi'); ?></option>
                        <?php foreach ($sizes as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php echo ($item && $item->size === $value) ? 'selected' : ''; ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="rn-form-group rn-small-width">
                    <label><?php _e('Kom', 'radni-nalozi'); ?> *</label>
                    <input type="number" name="items[<?php echo $index; ?>][quantity]" min="1" required
                           value="<?php echo $item ? intval($item->quantity) : '1'; ?>">
                </div>
                <div class="rn-form-group rn-small-width">
                    <label><?php _e('Boja', 'radni-nalozi'); ?> *</label>
                    <input type="text" name="items[<?php echo $index; ?>][color]" required
                           value="<?php echo $item ? esc_attr($item->color) : ''; ?>">
                </div>
            </div>
            
            <div class="rn-form-row">
                <div class="rn-form-group rn-half-width">
                    <label><?php _e('Naziv printa', 'radni-nalozi'); ?> *</label>
                    <input type="text" name="items[<?php echo $index; ?>][print_name]" required
                           value="<?php echo $item ? esc_attr($item->print_name) : ''; ?>">
                </div>
                <div class="rn-form-group rn-half-width">
                    <label><?php _e('Prodajna cena (RSD)', 'radni-nalozi'); ?> *</label>
                    <input type="number" name="items[<?php echo $index; ?>][price]" min="0" step="0.01" required
                           value="<?php echo $item ? floatval($item->price) : ''; ?>">
                </div>
            </div>
            
            <div class="rn-form-row">
                <div class="rn-form-group rn-half-width">
                    <label><?php _e('Slika', 'radni-nalozi'); ?></label>
                    <div class="rn-image-upload">
                        <input type="hidden" name="items[<?php echo $index; ?>][image_url]" class="rn-image-url"
                               value="<?php echo $item ? esc_url($item->image_url) : ''; ?>">
                        <div class="rn-image-preview <?php echo ($item && $item->image_url) ? 'has-image' : ''; ?>">
                            <?php if ($item && $item->image_url): ?>
                                <img src="<?php echo esc_url($item->image_url); ?>" alt="">
                            <?php endif; ?>
                        </div>
                        <button type="button" class="rn-btn rn-btn-small rn-btn-secondary rn-upload-btn">
                            <?php _e('Izaberi sliku', 'radni-nalozi'); ?>
                        </button>
                        <button type="button" class="rn-btn rn-btn-small rn-btn-danger rn-remove-image" 
                                style="<?php echo ($item && $item->image_url) ? '' : 'display:none;'; ?>">
                            <?php _e('Ukloni', 'radni-nalozi'); ?>
                        </button>
                    </div>
                </div>
                <div class="rn-form-group rn-half-width">
                    <label><?php _e('Napomena', 'radni-nalozi'); ?></label>
                    <textarea name="items[<?php echo $index; ?>][note]" rows="3"><?php echo $item ? esc_textarea($item->note) : ''; ?></textarea>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_order_view($order_id) {
        $order = RN_Orders::get_order($order_id);
        
        if (!$order || $order->user_id != get_current_user_id()) {
            echo '<p class="rn-error">' . __('Nalog nije pronađen.', 'radni-nalozi') . '</p>';
            return;
        }
        
        $items = RN_Orders::get_order_items($order_id);
        ?>
        <div class="rn-order-view">
            <div class="rn-order-header">
                <h3><?php printf(__('Radni nalog: %s', 'radni-nalozi'), esc_html($order->order_number)); ?></h3>
                <span class="rn-status <?php echo RN_Orders::get_status_class($order->status); ?>">
                    <?php echo RN_Orders::get_status_label($order->status); ?>
                </span>
            </div>
            
            <div class="rn-order-meta">
                <p><strong><?php _e('Datum kreiranja:', 'radni-nalozi'); ?></strong> <?php echo date_i18n('d.m.Y H:i', strtotime($order->created_at)); ?></p>
                <p><strong><?php _e('Poslednja izmena:', 'radni-nalozi'); ?></strong> <?php echo date_i18n('d.m.Y H:i', strtotime($order->updated_at)); ?></p>
            </div>
            
            <div class="rn-order-section">
                <h4><?php _e('Podaci o kupcu', 'radni-nalozi'); ?></h4>
                <div class="rn-order-details">
                    <p><strong><?php _e('Ime:', 'radni-nalozi'); ?></strong> <?php echo esc_html($order->customer_name); ?></p>
                    <p><strong><?php _e('Adresa:', 'radni-nalozi'); ?></strong> 
                        <?php echo esc_html($order->customer_address); ?>, 
                        <?php echo esc_html($order->customer_postal); ?> 
                        <?php echo esc_html($order->customer_city); ?>
                    </p>
                    <p><strong><?php _e('Telefon:', 'radni-nalozi'); ?></strong> <?php echo esc_html($order->customer_phone); ?></p>
                </div>
            </div>
            
            <div class="rn-order-section">
                <h4><?php _e('Stavke naloga', 'radni-nalozi'); ?></h4>
                
                <?php if (!empty($items)): ?>
                    <div class="rn-items-grid">
                        <?php foreach ($items as $index => $item): ?>
                            <div class="rn-item-card">
                                <div class="rn-item-card-header">
                                    <span class="rn-item-number"><?php echo $index + 1; ?></span>
                                    <span class="rn-item-name"><?php echo esc_html($item->print_name); ?></span>
                                </div>
                                
                                <?php if ($item->image_url): ?>
                                    <div class="rn-item-image">
                                        <img src="<?php echo esc_url($item->image_url); ?>" alt="<?php echo esc_attr($item->print_name); ?>">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="rn-item-details">
                                    <p><strong><?php _e('Tip:', 'radni-nalozi'); ?></strong> <?php echo esc_html($item->garment_type ?? 'Majica'); ?></p>
                                    <p><strong><?php _e('Pol:', 'radni-nalozi'); ?></strong> <?php echo esc_html($item->category ?? 'Muška'); ?></p>
                                    <p><strong><?php _e('Boja:', 'radni-nalozi'); ?></strong> <?php echo esc_html($item->color); ?></p>
                                    <p><strong><?php _e('Veličina:', 'radni-nalozi'); ?></strong> <?php echo esc_html($item->size); ?></p>
                                    <p><strong><?php _e('Količina:', 'radni-nalozi'); ?></strong> <?php echo intval($item->quantity); ?> kom.</p>
                                    <p><strong><?php _e('Cena:', 'radni-nalozi'); ?></strong> <?php echo number_format($item->price, 2, ',', '.'); ?> RSD</p>
                                    <p><strong><?php _e('Ukupno:', 'radni-nalozi'); ?></strong> <?php echo number_format($item->price * $item->quantity, 2, ',', '.'); ?> RSD</p>
                                    <?php if ($item->note): ?>
                                        <p><strong><?php _e('Napomena:', 'radni-nalozi'); ?></strong> <?php echo esc_html($item->note); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="rn-order-total">
                    <strong><?php _e('UKUPAN IZNOS:', 'radni-nalozi'); ?></strong>
                    <span class="rn-total-amount"><?php echo number_format($order->total_amount, 2, ',', '.'); ?> RSD</span>
                </div>
            </div>
            
            <div class="rn-order-actions">
                <a href="<?php echo esc_url(add_query_arg('rn_view', 'list', get_permalink())); ?>" class="rn-btn rn-btn-secondary">
                    <?php _e('Nazad na listu', 'radni-nalozi'); ?>
                </a>
                
                <?php if ($order->status === 'nov'): ?>
                    <a href="<?php echo esc_url(add_query_arg(array('rn_view' => 'edit', 'order_id' => $order->id), get_permalink())); ?>" 
                       class="rn-btn rn-btn-primary">
                        <?php _e('Izmeni nalog', 'radni-nalozi'); ?>
                    </a>
                <?php endif; ?>
                
                <?php if ($order->status !== 'storniran'): ?>
                    <button type="button" class="rn-btn rn-btn-danger rn-cancel-order" data-order-id="<?php echo $order->id; ?>">
                        <?php _e('Storniraj nalog', 'radni-nalozi'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
