<?php
/**
 * Order Handler Class
 * Manages order processing and exchange device storage
 */

if (!defined('ABSPATH')) {
    exit;
}

class Exchange_Pro_Order_Handler {
    
    private static $instance = null;
    private $session;
    private $db;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->session = Exchange_Pro_Session::get_instance();
        $this->db = Exchange_Pro_Database::get_instance();
        
        // Save exchange data to order
        add_action('woocommerce_checkout_order_processed', array($this, 'save_exchange_to_order'), 10, 3);
        
        // Display exchange data in order details
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_exchange_in_admin_order'), 10, 1);
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_exchange_in_customer_order'), 10, 1);
        // Include exchange details in emails / invoices
        add_action('woocommerce_email_after_order_table', array($this, 'display_exchange_in_email'), 10, 4);
        // Some invoice plugins hook into this one instead
        add_action('woocommerce_email_order_meta', array($this, 'display_exchange_in_email'), 99, 4);

        // Show a short exchange summary under line-items (many invoice plugins render this)
        add_action('woocommerce_order_item_meta_end', array($this, 'render_exchange_item_meta'), 10, 4);
        
        // Add order meta box
        add_action('add_meta_boxes', array($this, 'add_exchange_meta_box'));

        // Admin: allow marking exchange status (Pending / Verified / Cancelled)
        add_action('admin_post_exchange_pro_set_exchange_status', array($this, 'handle_set_exchange_status'));
    }

    /**
     * Display exchange details in WooCommerce emails.
     * Many invoice plugins reuse Woo email hooks, so this makes exchange info appear on invoices too.
     */
    public function display_exchange_in_email($order, $sent_to_admin, $plain_text, $email) {
        if (!is_a($order, 'WC_Order')) return;
        $exchange_data = get_post_meta($order->get_id(), '_exchange_pro_data', true);
        if (empty($exchange_data)) return;

        if ($plain_text) {
            $cat = !empty($exchange_data['category_id']) ? $this->db->get_category(intval($exchange_data['category_id'])) : null;
            $br  = !empty($exchange_data['brand_id']) ? $this->db->get_brand(intval($exchange_data['brand_id'])) : null;
            $mo  = !empty($exchange_data['model_id']) ? $this->db->get_model(intval($exchange_data['model_id'])) : null;
            $va  = !empty($exchange_data['variant_id']) ? $this->db->get_variant(intval($exchange_data['variant_id'])) : null;

            echo "\n" . __('Exchange Device Details', 'exchange-pro') . "\n";
            echo __('Category', 'exchange-pro') . ': ' . ($cat ? $cat->name : '-') . "\n";
            echo __('Brand', 'exchange-pro') . ': ' . ($br ? $br->name : '-') . "\n";
            echo __('Model', 'exchange-pro') . ': ' . ($mo ? $mo->name : '-') . "\n";
            echo __('Variant', 'exchange-pro') . ': ' . ($va ? $va->name : '-') . "\n";
            echo __('Condition', 'exchange-pro') . ': ' . (!empty($exchange_data['condition']) ? $exchange_data['condition'] : '-') . "\n";
            echo __('IMEI/Serial', 'exchange-pro') . ': ' . (!empty($exchange_data['imei_serial']) ? $exchange_data['imei_serial'] : '-') . "\n";
            echo __('Model Number', 'exchange-pro') . ': ' . (!empty($exchange_data['model_number']) ? $exchange_data['model_number'] : '-') . "\n";
            echo __('Pincode', 'exchange-pro') . ': ' . (!empty($exchange_data['pincode']) ? $exchange_data['pincode'] : '-') . "\n";
            echo __('Exchange Value', 'exchange-pro') . ': ' . (!empty($exchange_data['exchange_value']) ? $exchange_data['exchange_value'] : '0') . "\n";
            return;
        }

        echo '<h2 style="margin-top:24px;">' . esc_html__('Exchange Device Details', 'exchange-pro') . '</h2>';
        echo '<table cellspacing="0" cellpadding="6" style="width:100%; border:1px solid #e5e5e5;" border="1">';
        echo '<tbody>';
        $this->render_exchange_details_table($exchange_data);
        echo '</tbody></table>';
    }
    
    /**
     * Save exchange data to order
     */
    public function save_exchange_to_order($order_id, $posted_data, $order) {
        $exchange_data = $this->session->get_exchange_data();
        
        if (empty($exchange_data)) {
            return;
        }
        
        // Save exchange data as order meta
        update_post_meta($order_id, '_exchange_pro_data', $exchange_data);

        // Store exchange created date/time
        update_post_meta($order_id, '_exchange_pro_created_at', current_time('mysql'));

        // Default exchange status
        if (!get_post_meta($order_id, '_exchange_pro_status', true)) {
            update_post_meta($order_id, '_exchange_pro_status', 'pending');
        }

        // Track exchange status separately (used for handover verification workflow)
        if (!get_post_meta($order_id, '_exchange_pro_status', true)) {
            update_post_meta($order_id, '_exchange_pro_status', 'pending');
        }
        
        // Save to exchange devices table
        $device_data = array(
            'order_id' => $order_id,
            'product_id' => isset($exchange_data['product_id']) ? $exchange_data['product_id'] : 0,
            'category_id' => isset($exchange_data['category_id']) ? $exchange_data['category_id'] : null,
            'brand_id' => isset($exchange_data['brand_id']) ? $exchange_data['brand_id'] : null,
            'model_id' => isset($exchange_data['model_id']) ? $exchange_data['model_id'] : null,
            'variant_id' => isset($exchange_data['variant_id']) ? $exchange_data['variant_id'] : null,
            'condition_type' => isset($exchange_data['condition']) ? $exchange_data['condition'] : null,
            'imei_serial' => isset($exchange_data['imei_serial']) ? $exchange_data['imei_serial'] : null,
            'pincode' => isset($exchange_data['pincode']) ? $exchange_data['pincode'] : null,
            'exchange_value' => isset($exchange_data['exchange_value']) ? $exchange_data['exchange_value'] : 0,
            'device_data' => json_encode($exchange_data),
            'status' => 'pending'
        );
        
        $this->db->insert_exchange_device($device_data);

        // Capture insert id for audit trail
        $exchange_id = isset($this->db->wpdb->insert_id) ? intval($this->db->wpdb->insert_id) : 0;

        // DB audit log (Phase 2)
        $this->db->insert_exchange_log(array(
            'exchange_id' => $exchange_id ?: null,
            'order_id'    => $order_id,
            'action'      => 'exchange_created',
            'old_value'   => null,
            'new_value'   => maybe_serialize(array(
                'exchange_value' => isset($exchange_data['exchange_value']) ? $exchange_data['exchange_value'] : 0,
                'category_id'    => $exchange_data['category_id'] ?? null,
                'brand_id'       => $exchange_data['brand_id'] ?? null,
                'model_id'       => $exchange_data['model_id'] ?? null,
                'variant_id'     => $exchange_data['variant_id'] ?? null,
                'condition'      => $exchange_data['condition'] ?? null,
                'pricing_source' => $exchange_data['pricing_source'] ?? null,
            )),
            'admin_user'  => get_current_user_id() ?: null,
        ));
        
        Exchange_Pro_Logger::log('Exchange data saved to order', 'info', array(
            'order_id' => $order_id,
            'exchange_value' => $exchange_data['exchange_value']
        ));
        
        // Clear session data
        $this->session->clear_exchange_data();
    }
    
    /**
     * Display exchange data in admin order page
     */
    public function display_exchange_in_admin_order($order) {
        $exchange_data = get_post_meta($order->get_id(), '_exchange_pro_data', true);
        
        if (empty($exchange_data)) {
            return;
        }
        
        ?>
        <div class="exchange-pro-order-data" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-left: 4px solid #ff6600;">
            <h3 style="margin-top: 0; color: #ff6600;"><?php _e('Exchange Device Details', 'exchange-pro'); ?></h3>
            <?php $this->render_exchange_details($exchange_data); ?>
        </div>
        <?php
    }
    
    /**
     * Display exchange data in customer order page
     */
    public function display_exchange_in_customer_order($order) {
        $exchange_data = get_post_meta($order->get_id(), '_exchange_pro_data', true);
        
        if (empty($exchange_data)) {
            return;
        }
        
        ?>
        <section class="woocommerce-order-exchange-details" style="margin-top: 30px;">
            <h2><?php _e('Exchange Device Details', 'exchange-pro'); ?></h2>
            <table class="woocommerce-table shop_table exchange_details">
                <tbody>
                    <?php $this->render_exchange_details_table($exchange_data); ?>
                </tbody>
            </table>
        </section>
        <?php
    }
    
    /**
     * Render exchange details
     */
    private function render_exchange_details($exchange_data) {
        ?>
        <table class="widefat" style="margin-top: 10px;">
            <tbody>
                <?php $this->render_exchange_details_table($exchange_data); ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Render exchange details as table rows
     */
    private function render_exchange_details_table($exchange_data) {
        if (!empty($exchange_data['category_id'])) {
            $category = $this->db->get_category($exchange_data['category_id']);
            if ($category) {
                ?>
                <tr>
                    <th style="width: 200px;"><?php _e('Category:', 'exchange-pro'); ?></th>
                    <td><?php echo esc_html($category->name); ?></td>
                </tr>
                <?php
            }
        }
        
        if (!empty($exchange_data['brand_id'])) {
            $brand = $this->db->get_brand($exchange_data['brand_id']);
            if ($brand) {
                ?>
                <tr>
                    <th><?php _e('Brand:', 'exchange-pro'); ?></th>
                    <td><?php echo esc_html($brand->name); ?></td>
                </tr>
                <?php
            }
        }
        
        if (!empty($exchange_data['model_id'])) {
            $model = $this->db->get_model($exchange_data['model_id']);
            if ($model) {
                ?>
                <tr>
                    <th><?php _e('Model:', 'exchange-pro'); ?></th>
                    <td><?php echo esc_html($model->name); ?></td>
                </tr>
                <?php
            }
        }
        
        if (!empty($exchange_data['variant_id'])) {
            $variant = $this->db->get_variant($exchange_data['variant_id']);
            if ($variant) {
                ?>
                <tr>
                    <th><?php _e('Variant:', 'exchange-pro'); ?></th>
                    <td><?php echo esc_html($variant->name); ?></td>
                </tr>
                <?php
            }
        }
        
        if (!empty($exchange_data['condition'])) {
            $pricing = Exchange_Pro_Pricing::get_instance();
            $conditions = $pricing->get_condition_options();
            if (isset($conditions[$exchange_data['condition']])) {
                ?>
                <tr>
                    <th><?php _e('Condition:', 'exchange-pro'); ?></th>
                    <td><?php echo esc_html($conditions[$exchange_data['condition']]['label']); ?></td>
                </tr>
                <?php
            }
        }
        
        if (!empty($exchange_data['imei_serial'])) {
            ?>
            <tr>
                <th><?php _e('IMEI/Serial:', 'exchange-pro'); ?></th>
                <td><?php echo esc_html($exchange_data['imei_serial']); ?></td>
            </tr>
            <?php
        }

        if (!empty($exchange_data['model_number'])) {
            ?>
            <tr>
                <th><?php _e('Model Number:', 'exchange-pro'); ?></th>
                <td><?php echo esc_html($exchange_data['model_number']); ?></td>
            </tr>
            <?php
        }
        
        if (!empty($exchange_data['pincode'])) {
            ?>
            <tr>
                <th><?php _e('Pincode:', 'exchange-pro'); ?></th>
                <td><?php echo esc_html($exchange_data['pincode']); ?></td>
            </tr>
            <?php
        }
        
        if (!empty($exchange_data['exchange_value'])) {
            $pricing = Exchange_Pro_Pricing::get_instance();
            ?>
            <tr>
                <th><?php _e('Exchange Value:', 'exchange-pro'); ?></th>
                <td><strong style="color: #ff6600; font-size: 16px;"><?php echo $pricing->format_price($exchange_data['exchange_value']); ?></strong></td>
            </tr>
            <?php
        }
    }
    
    /**
     * Add exchange meta box to order page
     */
    public function add_exchange_meta_box() {
        add_meta_box(
            'exchange_pro_order_data',
            __('Exchange Device Information', 'exchange-pro'),
            array($this, 'render_exchange_meta_box'),
            'shop_order',
            'side',
            'default'
        );
        
        // Support for HPOS
        add_meta_box(
            'exchange_pro_order_data',
            __('Exchange Device Information', 'exchange-pro'),
            array($this, 'render_exchange_meta_box'),
            'woocommerce_page_wc-orders',
            'side',
            'default'
        );
    }
    
    /**
     * Render exchange meta box
     */
    public function render_exchange_meta_box($post_or_order) {
        $order = $post_or_order instanceof WP_Post ? wc_get_order($post_or_order->ID) : $post_or_order;
        $exchange_data = get_post_meta($order->get_id(), '_exchange_pro_data', true);
        
        if (empty($exchange_data)) {
            echo '<p>' . __('No exchange device data available.', 'exchange-pro') . '</p>';
            return;
        }
        
        $order_id = $order->get_id();
        $status = get_post_meta($order_id, '_exchange_pro_status', true);
        if (!$status) $status = 'pending';

        echo '<p><strong>' . esc_html(__('Exchange Status', 'exchange-pro')) . ':</strong> ' . esc_html(ucfirst($status)) . '</p>';

        $action_url = admin_url('admin-post.php');
        $nonce = wp_create_nonce('exchange_pro_set_exchange_status');
        echo '<div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px;">';
        foreach (array('pending' => __('Pending','exchange-pro'), 'verified' => __('Verified','exchange-pro'), 'cancelled' => __('Cancelled','exchange-pro')) as $key => $label) {
            echo '<form method="post" action="' . esc_url($action_url) . '" style="margin:0;">';
            echo '<input type="hidden" name="action" value="exchange_pro_set_exchange_status">';
            echo '<input type="hidden" name="order_id" value="' . (int)$order_id . '">';
            echo '<input type="hidden" name="status" value="' . esc_attr($key) . '">';
            echo '<input type="hidden" name="_wpnonce" value="' . esc_attr($nonce) . '">';
            echo '<button type="submit" class="button" ' . ($status === $key ? 'disabled' : '') . '>' . esc_html($label) . '</button>';
            echo '</form>';
        }
        echo '</div>';

        $this->render_exchange_details($exchange_data);
        echo '<p style="margin-top:10px; color:#666;">' . esc_html__('Note: Cancelling exchange only updates the exchange record. Payment adjustments depend on your checkout/payment flow and must be handled manually or via a custom gateway integration.', 'exchange-pro') . '</p>';
    }

    /**
     * Admin-post handler: update exchange status
     */
    public function handle_set_exchange_status() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Permission denied', 'exchange-pro'));
        }
        check_admin_referer('exchange_pro_set_exchange_status');
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_key($_POST['status']) : '';
        if (!$order_id || !in_array($status, array('pending','verified','cancelled'), true)) {
            wp_die(__('Invalid request', 'exchange-pro'));
        }
        update_post_meta($order_id, '_exchange_pro_status', $status);
        wp_safe_redirect(wp_get_referer() ? wp_get_referer() : admin_url('post.php?post=' . $order_id . '&action=edit'));
        exit;
    }

    /**
     * Render exchange summary in line item meta (commonly shown on PDF invoices).
     */
    public function render_exchange_item_meta($item_id, $item, $order, $plain_text) {
        if (!is_a($order, 'WC_Order')) return;

        $exchange_data = get_post_meta($order->get_id(), '_exchange_pro_data', true);
        if (empty($exchange_data) || empty($exchange_data['exchange_value'])) return;

        // Only print once, on the first line item.
        static $printed = array();
        if (isset($printed[$order->get_id()])) return;
        $printed[$order->get_id()] = true;

        $pricing = Exchange_Pro_Pricing::get_instance();

        $cat = !empty($exchange_data['category_id']) ? $this->db->get_category(intval($exchange_data['category_id'])) : null;
        $br  = !empty($exchange_data['brand_id']) ? $this->db->get_brand(intval($exchange_data['brand_id'])) : null;
        $mo  = !empty($exchange_data['model_id']) ? $this->db->get_model(intval($exchange_data['model_id'])) : null;
        $va  = !empty($exchange_data['variant_id']) ? $this->db->get_variant(intval($exchange_data['variant_id'])) : null;

        $device = trim(sprintf('%s %s %s', $br ? $br->name : '', $mo ? $mo->name : '', $va ? '(' . $va->name . ')' : ''));
        if (!$device) $device = __('Selected device', 'exchange-pro');

        if ($plain_text) {
            echo "\n" . __('Exchange:', 'exchange-pro') . ' ' . $device . ' - ' . $pricing->format_price($exchange_data['exchange_value']) . "\n";
            return;
        }

        echo '<div class="exchange-pro-item-meta" style="margin-top:6px; font-size:12px; color:#555;">';
        echo '<strong>' . esc_html__('Exchange', 'exchange-pro') . ':</strong> ' . esc_html($device) . ' — ';
        echo '<strong style="color:#ff6600;">' . esc_html($pricing->format_price($exchange_data['exchange_value'])) . '</strong>';
        if (!empty($exchange_data['model_number'])) {
            echo '<div style="margin-top:2px;">' . esc_html__('Model Number', 'exchange-pro') . ': ' . esc_html($exchange_data['model_number']) . '</div>';
        }
        if (!empty($exchange_data['imei_serial'])) {
            echo '<div style="margin-top:2px;">' . esc_html__('IMEI/Serial', 'exchange-pro') . ': ' . esc_html($exchange_data['imei_serial']) . '</div>';
        }
        if (!empty($exchange_data['pincode'])) {
            echo '<div style="margin-top:2px;">' . esc_html__('Pincode', 'exchange-pro') . ': ' . esc_html($exchange_data['pincode']) . '</div>';
        }
        echo '</div>';
    }
    
    /**
     * Get all exchange orders
     */
    public function get_exchange_orders() {
        $args = array(
            'limit' => -1,
            'meta_key' => '_exchange_pro_data',
            'meta_compare' => 'EXISTS'
        );
        
        return wc_get_orders($args);
    }
}
