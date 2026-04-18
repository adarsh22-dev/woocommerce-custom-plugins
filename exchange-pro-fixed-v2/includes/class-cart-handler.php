<?php
/**
 * Cart Handler Class
 * Manages cart integration and exchange value application
 */

if (!defined('ABSPATH')) {
    exit;
}

class Exchange_Pro_Cart_Handler {
    
    private static $instance = null;
    private $session;
    private $pricing;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->session = Exchange_Pro_Session::get_instance();
        $this->pricing = Exchange_Pro_Pricing::get_instance();
        
        // Add exchange fee to cart
        add_action('woocommerce_cart_calculate_fees', array($this, 'add_exchange_fee'));
        
        // Display exchange details in cart
        add_action('woocommerce_cart_totals_before_order_total', array($this, 'display_exchange_details'));
        add_action('woocommerce_review_order_before_order_total', array($this, 'display_exchange_details'));
        
        // Add exchange data to cart item
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_exchange_to_cart_item'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_exchange_in_cart'), 10, 2);
    }
    
    /**
     * Add exchange value as negative fee to cart
     */
    public function add_exchange_fee($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        $exchange_data = $this->session->get_exchange_data();
        
        if (empty($exchange_data) || empty($exchange_data['exchange_value'])) {
            return;
        }
        
        $exchange_value = floatval($exchange_data['exchange_value']);
        
        if ($exchange_value <= 0) {
            Exchange_Pro_Logger::log('Exchange value is zero or negative, not applying fee', 'warning', $exchange_data);
            return;
        }
        
        // Get cart total to apply cap
        $cart_total = $cart->get_subtotal();
        
        // Apply cap if needed
        $product_id = isset($exchange_data['product_id']) ? $exchange_data['product_id'] : 0;
        if ($product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $exchange_value = $this->pricing->apply_exchange_cap($exchange_value, $product->get_price(), $product_id);
            }
        }
        
        // Create label with device details
        $label = $this->get_exchange_fee_label($exchange_data);
        
        // Add negative fee (discount)
        $cart->add_fee($label, -$exchange_value, false);
        
        Exchange_Pro_Logger::log('Exchange fee added to cart', 'info', array(
            'label' => $label,
            'value' => $exchange_value,
            'cart_total' => $cart_total
        ));
    }
    
    /**
     * Generate exchange fee label
     */
    private function get_exchange_fee_label($exchange_data) {
        $db = Exchange_Pro_Database::get_instance();
        
        $parts = array();
        
        if (!empty($exchange_data['category_id'])) {
            $category = $db->get_category($exchange_data['category_id']);
            if ($category) {
                $parts[] = $category->name;
            }
        }
        
        if (!empty($exchange_data['brand_id'])) {
            $brand = $db->get_brand($exchange_data['brand_id']);
            if ($brand) {
                $parts[] = $brand->name;
            }
        }
        
        if (!empty($exchange_data['model_id'])) {
            $model = $db->get_model($exchange_data['model_id']);
            if ($model) {
                $parts[] = $model->name;
            }
        }
        
        if (!empty($exchange_data['variant_id'])) {
            $variant = $db->get_variant($exchange_data['variant_id']);
            if ($variant) {
                $parts[] = '(' . $variant->name . ')';
            }
        }
        
        if (!empty($exchange_data['condition'])) {
            $conditions = $this->pricing->get_condition_options();
            if (isset($conditions[$exchange_data['condition']])) {
                $parts[] = '- ' . $conditions[$exchange_data['condition']]['label'];
            }
        }
        
        $label = __('Exchange Device: ', 'exchange-pro') . implode(' ', $parts);
        
        return $label;
    }
    
    /**
     * Display exchange details before order total
     */
    public function display_exchange_details() {
        $exchange_data = $this->session->get_exchange_data();
        
        if (empty($exchange_data)) {
            return;
        }
        
        $db = Exchange_Pro_Database::get_instance();
        ?>
        <tr class="exchange-pro-details">
            <td colspan="2" style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <strong style="color: #ff6600; font-size: 14px;"><?php _e('Exchange Device Details', 'exchange-pro'); ?></strong>
                <div style="margin-top: 10px; font-size: 13px; line-height: 1.8;">
                    <?php
                    if (!empty($exchange_data['category_id'])) {
                        $category = $db->get_category($exchange_data['category_id']);
                        if ($category) {
                            echo '<div><strong>' . __('Category:', 'exchange-pro') . '</strong> ' . esc_html($category->name) . '</div>';
                        }
                    }
                    
                    if (!empty($exchange_data['brand_id'])) {
                        $brand = $db->get_brand($exchange_data['brand_id']);
                        if ($brand) {
                            echo '<div><strong>' . __('Brand:', 'exchange-pro') . '</strong> ' . esc_html($brand->name) . '</div>';
                        }
                    }
                    
                    if (!empty($exchange_data['model_id'])) {
                        $model = $db->get_model($exchange_data['model_id']);
                        if ($model) {
                            echo '<div><strong>' . __('Model:', 'exchange-pro') . '</strong> ' . esc_html($model->name) . '</div>';
                        }
                    }
                    
                    if (!empty($exchange_data['variant_id'])) {
                        $variant = $db->get_variant($exchange_data['variant_id']);
                        if ($variant) {
                            echo '<div><strong>' . __('Variant:', 'exchange-pro') . '</strong> ' . esc_html($variant->name) . '</div>';
                        }
                    }
                    
                    if (!empty($exchange_data['condition'])) {
                        $conditions = $this->pricing->get_condition_options();
                        if (isset($conditions[$exchange_data['condition']])) {
                            echo '<div><strong>' . __('Condition:', 'exchange-pro') . '</strong> ' . esc_html($conditions[$exchange_data['condition']]['label']) . '</div>';
                        }
                    }
                    
                    if (!empty($exchange_data['imei_serial'])) {
                        echo '<div><strong>' . __('IMEI/Serial:', 'exchange-pro') . '</strong> ' . esc_html($exchange_data['imei_serial']) . '</div>';
                    }
                    
                    if (!empty($exchange_data['pincode'])) {
                        echo '<div><strong>' . __('Pincode:', 'exchange-pro') . '</strong> ' . esc_html($exchange_data['pincode']) . '</div>';
                    }
                    ?>
                </div>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Add exchange data to cart item
     */
    public function add_exchange_to_cart_item($cart_item_data, $product_id, $variation_id) {
        $exchange_data = $this->session->get_exchange_data();
        
        if (!empty($exchange_data)) {
            $cart_item_data['exchange_pro_data'] = $exchange_data;
        }
        
        return $cart_item_data;
    }
    
    /**
     * Display exchange data in cart
     */
    public function display_exchange_in_cart($item_data, $cart_item) {
        if (isset($cart_item['exchange_pro_data'])) {
            $exchange_data = $cart_item['exchange_pro_data'];
            $label = $this->get_exchange_fee_label($exchange_data);
            
            $item_data[] = array(
                'key'   => __('Exchange Device', 'exchange-pro'),
                'value' => $label,
            );
        }
        
        return $item_data;
    }
    
    /**
     * Clear exchange data after order
     */
    public function clear_exchange_after_order() {
        $this->session->clear_exchange_data();
    }
}
