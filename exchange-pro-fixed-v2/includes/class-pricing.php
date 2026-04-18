<?php
/**
 * Pricing Calculation Class
 * Handles all pricing logic and calculations
 */

if (!defined('ABSPATH')) {
    exit;
}

class Exchange_Pro_Pricing {
    
    private static $instance = null;
    private $db;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db = Exchange_Pro_Database::get_instance();
    }
    
    /**
     * Calculate exchange value based on device details
     */
    public function calculate_exchange_value($data) {
        if (empty($data['variant_id']) || empty($data['condition'])) {
            Exchange_Pro_Logger::log('Missing variant_id or condition for pricing calculation', 'error', $data);
            return 0;
        }
        
        $price = 0;
        
        // Check if product has custom pricing enabled
        if (!empty($data['product_id'])) {
            $custom_pricing_enabled = get_post_meta($data['product_id'], '_exchange_pro_custom_pricing', true);
            
            if ($custom_pricing_enabled === 'yes') {
                $pricing_data = get_post_meta($data['product_id'], '_exchange_pro_pricing_data', true);
                
                if (is_array($pricing_data)) {
                    $condition_key = strtolower($data['condition']);

                    // 1) Prefer exact Variant match (new structured rows)
                    foreach ($pricing_data as $custom_price) {
                        $row_variant_id = isset($custom_price['variant_id']) ? intval($custom_price['variant_id']) : 0;
                        if ($row_variant_id && $row_variant_id === intval($data['variant_id'])) {
                            if (isset($custom_price[$condition_key]) && floatval($custom_price[$condition_key]) > 0) {
                                $price = floatval($custom_price[$condition_key]);
                                Exchange_Pro_Logger::log('Custom product pricing applied (exact variant)', 'info', array(
                                    'product_id' => $data['product_id'],
                                    'variant_id' => $data['variant_id'],
                                    'condition' => $data['condition'],
                                    'price' => $price
                                ));
                                return $price;
                            }
                        }
                    }

                    // 2) Backward compatible: try text match for older saved rows
                    $variant = $this->db->get_variant($data['variant_id']);
                    $model = $variant ? $this->db->get_model($variant->model_id) : null;
                    $brand = $model ? $this->db->get_brand($model->brand_id) : null;
                    if ($variant && $model && $brand) {
                        $device_identifier = strtolower($brand->name . ' ' . $model->name . ' ' . $variant->name);
                        foreach ($pricing_data as $custom_price) {
                            $custom_device = strtolower($custom_price['device_name'] ?? '');
                            if (!$custom_device) continue;
                            if (strpos($device_identifier, $custom_device) !== false ||
                                strpos($custom_device, $device_identifier) !== false ||
                                $custom_device === $device_identifier) {
                                if (isset($custom_price[$condition_key]) && floatval($custom_price[$condition_key]) > 0) {
                                    $price = floatval($custom_price[$condition_key]);
                                    Exchange_Pro_Logger::log('Custom product pricing applied (text match)', 'info', array(
                                        'product_id' => $data['product_id'],
                                        'device' => $custom_price['device_name'],
                                        'condition' => $data['condition'],
                                        'price' => $price
                                    ));
                                    return $price;
                                }
                            }
                        }
                    }

                    // 3) Custom pricing enabled but no matching row => disable global pricing
                    Exchange_Pro_Logger::log('Custom pricing enabled, but no matching device row found. Global pricing disabled for this product.', 'info', array(
                        'product_id' => $data['product_id'],
                        'variant_id' => $data['variant_id'],
                        'condition' => $data['condition']
                    ));
                    return 0;
                }
            }
        }
        
        // Fall back to global pricing if no custom pricing found
        $price = $this->db->get_price($data['variant_id'], $data['condition']);
        
        Exchange_Pro_Logger::log('Price calculated (global)', 'info', array(
            'variant_id' => $data['variant_id'],
            'condition' => $data['condition'],
            'price' => $price
        ));
        
        return floatval($price);
    }
    
    /**
     * Get pricing for all conditions of a variant
     */
    public function get_variant_pricing($variant_id) {
        $pricing = $this->db->get_pricing($variant_id);
        $result = array();
        
        foreach ($pricing as $p) {
            $result[$p->condition_type] = array(
                'price' => floatval($p->price),
                'formatted' => $this->format_price($p->price)
            );
        }
        
        return $result;
    }
    
    /**
     * Format price with currency symbol
     */
    public function format_price($price) {
        $symbol = get_option('exchange_pro_currency_symbol', '₹');
        return $symbol . number_format($price, 0, '.', ',');
    }
    
    /**
     * Check if exchange is allowed for product
     */
    public function is_exchange_enabled_for_product($product_id) {
        // Global switch in plugin settings
        $global_enabled = (get_option('exchange_pro_enable', 'yes') === 'yes');
        if (!$global_enabled) {
            return false;
        }

        // Product-level switch (must be explicitly enabled on the product edit page)
        $product_enabled = get_post_meta($product_id, '_exchange_pro_enable', true);

        // IMPORTANT: require explicit enable per-product (default is hidden)
        return ($product_enabled === 'yes');
    }
    
    /**
     * Get allowed categories for a product
     */
    public function get_allowed_categories_for_product($product_id) {
        $allowed = get_post_meta($product_id, '_exchange_pro_allowed_categories', true);
        
        if (empty($allowed)) {
            // If not set, allow all categories
            $categories = $this->db->get_categories();
            $allowed = array();
            foreach ($categories as $cat) {
                $allowed[] = $cat->id;
            }
        }
        
        return $allowed;
    }
    
    /**
     * Get max exchange cap for product
     */
    public function get_max_exchange_cap($product_id) {
        $product_cap = get_post_meta($product_id, '_exchange_pro_max_cap', true);
        
        if (empty($product_cap)) {
            $product_cap = get_option('exchange_pro_max_exchange_percentage', 80);
        }
        
        return intval($product_cap);
    }
    
    /**
     * Apply exchange cap
     */
    public function apply_exchange_cap($exchange_value, $product_price, $product_id) {
        $cap_percentage = $this->get_max_exchange_cap($product_id);
        $max_allowed = ($product_price * $cap_percentage) / 100;
        
        if ($exchange_value > $max_allowed) {
            Exchange_Pro_Logger::log('Exchange value capped', 'info', array(
                'original' => $exchange_value,
                'capped' => $max_allowed,
                'product_price' => $product_price,
                'cap_percentage' => $cap_percentage
            ));
            return $max_allowed;
        }
        
        return $exchange_value;
    }
    
    /**
     * Get condition options
     */
    public function get_condition_options() {
        return array(
            'excellent' => array(
                'label' => __('Excellent', 'exchange-pro'),
                'description' => __('No scratches / Brand new', 'exchange-pro')
            ),
            'good' => array(
                'label' => __('Good', 'exchange-pro'),
                'description' => __('Minor wear / Functional', 'exchange-pro')
            ),
            'fair' => array(
                'label' => __('Fair', 'exchange-pro'),
                'description' => __('Display/Body issues', 'exchange-pro')
            ),
            'poor' => array(
                'label' => __('Poor', 'exchange-pro'),
                'description' => __('Display/Body issues', 'exchange-pro')
            )
        );
    }
}
