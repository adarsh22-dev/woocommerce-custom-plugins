<?php
/**
 * AJAX Handler Class
 * Handles all AJAX requests from frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class Exchange_Pro_Ajax_Handler {
    
    private static $instance = null;
    private $db;
    private $pricing;
    private $session;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db = Exchange_Pro_Database::get_instance();
        $this->pricing = Exchange_Pro_Pricing::get_instance();
        $this->session = Exchange_Pro_Session::get_instance();
        
        // Frontend AJAX actions
        add_action('wp_ajax_exchange_pro_get_brands', array($this, 'get_brands'));
        add_action('wp_ajax_nopriv_exchange_pro_get_brands', array($this, 'get_brands'));
        
        add_action('wp_ajax_exchange_pro_get_models', array($this, 'get_models'));
        add_action('wp_ajax_nopriv_exchange_pro_get_models', array($this, 'get_models'));
        
        add_action('wp_ajax_exchange_pro_get_variants', array($this, 'get_variants'));
        add_action('wp_ajax_nopriv_exchange_pro_get_variants', array($this, 'get_variants'));
        
        add_action('wp_ajax_exchange_pro_get_pricing', array($this, 'get_pricing'));
        add_action('wp_ajax_nopriv_exchange_pro_get_pricing', array($this, 'get_pricing'));
        
        add_action('wp_ajax_exchange_pro_check_pincode', array($this, 'check_pincode'));
        add_action('wp_ajax_nopriv_exchange_pro_check_pincode', array($this, 'check_pincode'));
        
        add_action('wp_ajax_exchange_pro_save_exchange', array($this, 'save_exchange'));
        add_action('wp_ajax_nopriv_exchange_pro_save_exchange', array($this, 'save_exchange'));
        
        add_action('wp_ajax_exchange_pro_remove_exchange', array($this, 'remove_exchange'));
        add_action('wp_ajax_nopriv_exchange_pro_remove_exchange', array($this, 'remove_exchange'));

        // Admin helpers for product-edit dropdowns (uses admin nonce)
        add_action('wp_ajax_exchange_pro_admin_get_brands', array($this, 'admin_get_brands'));
        add_action('wp_ajax_exchange_pro_admin_get_models', array($this, 'admin_get_models'));
        add_action('wp_ajax_exchange_pro_admin_get_variants', array($this, 'admin_get_variants'));
    }

    public function admin_get_brands() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'exchange-pro')));
        }
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $product_id  = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        if (!$category_id) {
            wp_send_json_error(array('message' => __('Invalid category', 'exchange-pro')));
        }
        // If product is in custom pricing mode, only show brands configured for this product/category.
        if ($this->is_product_custom_mode($product_id)) {
            $rows = $this->get_product_custom_rows($product_id);
            $brand_ids = array();
            foreach ($rows as $row) {
                if (intval($row['category_id'] ?? 0) === $category_id) {
                    $bid = intval($row['brand_id'] ?? 0);
                    if ($bid) $brand_ids[$bid] = true;
                }
            }
            $brands = array();
            foreach (array_keys($brand_ids) as $bid) {
                $b = $this->db->get_brand($bid);
                if ($b && $b->status === 'active') $brands[] = $b;
            }
        } else {
            $brands = $this->db->get_brands($category_id);
        }
        wp_send_json_success(array('brands' => $brands));
    }

    public function admin_get_models() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'exchange-pro')));
        }
        $brand_id = isset($_POST['brand_id']) ? intval($_POST['brand_id']) : 0;
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        if (!$brand_id) {
            wp_send_json_error(array('message' => __('Invalid brand', 'exchange-pro')));
        }
        if ($this->is_product_custom_mode($product_id)) {
            $rows = $this->get_product_custom_rows($product_id);
            $model_ids = array();
            foreach ($rows as $row) {
                if (intval($row['brand_id'] ?? 0) === $brand_id) {
                    $mid = intval($row['model_id'] ?? 0);
                    if ($mid) $model_ids[$mid] = true;
                }
            }
            $models = array();
            foreach (array_keys($model_ids) as $mid) {
                $m = $this->db->get_model($mid);
                if ($m && $m->status === 'active') $models[] = $m;
            }
        } else {
            $models = $this->db->get_models($brand_id);
        }
        wp_send_json_success(array('models' => $models));
    }

    public function admin_get_variants() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'exchange-pro')));
        }
        $model_id = isset($_POST['model_id']) ? intval($_POST['model_id']) : 0;
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        if (!$model_id) {
            wp_send_json_error(array('message' => __('Invalid model', 'exchange-pro')));
        }
        if ($this->is_product_custom_mode($product_id)) {
            $rows = $this->get_product_custom_rows($product_id);
            $variant_ids = array();
            foreach ($rows as $row) {
                if (intval($row['model_id'] ?? 0) === $model_id) {
                    $vid = intval($row['variant_id'] ?? 0);
                    if ($vid) $variant_ids[$vid] = true;
                }
            }
            $variants = array();
            foreach (array_keys($variant_ids) as $vid) {
                $v = $this->db->get_variant($vid);
                if ($v && $v->status === 'active') $variants[] = $v;
            }
        } else {
            $variants = $this->db->get_variants($model_id);
        }
        wp_send_json_success(array('variants' => $variants));
    }
    

    /**
     * Get custom pricing rows for a product (structured rows).
     */
    private function get_product_custom_rows($product_id) {
        if (!$product_id) return array();
        $enabled = get_post_meta($product_id, '_exchange_pro_custom_pricing', true);
        if ($enabled !== 'yes') return array();
        $rows = get_post_meta($product_id, '_exchange_pro_pricing_data', true);
        return is_array($rows) ? $rows : array();
    }

    /**
     * Determine whether to use product custom device list for selection.
     */
    private function is_product_custom_mode($product_id) {
        if (!$product_id) return false;
        $src = get_post_meta($product_id, '_exchange_pro_pricing_source', true);
        if (empty($src)) {
            // Backward compatible
            $enabled = get_post_meta($product_id, '_exchange_pro_custom_pricing', true);
            return ($enabled === 'yes');
        }
        return ($src === 'custom');
    }

    /**
     * Validate category is allowed for product (if product_id present)
     */
    private function is_category_allowed_for_product($product_id, $category_id) {
        if (!$product_id || !$category_id) return true;
        $allowed = get_post_meta($product_id, '_exchange_pro_allowed_categories', true);
        if (!is_array($allowed)) $allowed = array();
        return in_array(intval($category_id), array_map('intval', $allowed), true);
    }

    /**
     * Get brands by category
     */
    public function get_brands() {
        check_ajax_referer('exchange_pro_nonce', 'nonce');
        $product_id  = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        
        if (!$category_id) {
            wp_send_json_error(array('message' => __('Invalid category', 'exchange-pro')));
        }

        // If product is provided, enforce allowed categories.
        if (!$this->is_category_allowed_for_product($product_id, $category_id)) {
            wp_send_json_error(array('message' => __('Category not allowed for this product', 'exchange-pro')));
        }
        
        // If product is in custom pricing mode, only show brands configured for this product/category.
        if ($this->is_product_custom_mode($product_id)) {
            $rows = $this->get_product_custom_rows($product_id);
            $brand_ids = array();
            foreach ($rows as $row) {
                if (intval($row['category_id'] ?? 0) === $category_id) {
                    $bid = intval($row['brand_id'] ?? 0);
                    if ($bid) $brand_ids[$bid] = true;
                }
            }
            $brands = array();
            foreach (array_keys($brand_ids) as $bid) {
                $b = $this->db->get_brand($bid);
                if ($b && $b->status === 'active') $brands[] = $b;
            }
        } else {
            $brands = $this->db->get_brands($category_id);
        }
        
        wp_send_json_success(array('brands' => $brands));
    }
    
    /**
     * Get models by brand
     */
    public function get_models() {
        check_ajax_referer('exchange_pro_nonce', 'nonce');
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $brand_id   = isset($_POST['brand_id']) ? intval($_POST['brand_id']) : 0;
        
        if (!$brand_id) {
            wp_send_json_error(array('message' => __('Invalid brand', 'exchange-pro')));
        }
        
        if ($this->is_product_custom_mode($product_id)) {
            $rows = $this->get_product_custom_rows($product_id);
            $model_ids = array();
            foreach ($rows as $row) {
                if (intval($row['brand_id'] ?? 0) === $brand_id) {
                    $mid = intval($row['model_id'] ?? 0);
                    if ($mid) $model_ids[$mid] = true;
                }
            }
            $models = array();
            foreach (array_keys($model_ids) as $mid) {
                $m = $this->db->get_model($mid);
                if ($m && $m->status === 'active') $models[] = $m;
            }
        } else {
            $models = $this->db->get_models($brand_id);
        }
        
        wp_send_json_success(array('models' => $models));
    }
    
    /**
     * Get variants by model
     */
    public function get_variants() {
        check_ajax_referer('exchange_pro_nonce', 'nonce');
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $model_id   = isset($_POST['model_id']) ? intval($_POST['model_id']) : 0;
        
        if (!$model_id) {
            wp_send_json_error(array('message' => __('Invalid model', 'exchange-pro')));
        }
        
        if ($this->is_product_custom_mode($product_id)) {
            $rows = $this->get_product_custom_rows($product_id);
            $variant_ids = array();
            foreach ($rows as $row) {
                if (intval($row['model_id'] ?? 0) === $model_id) {
                    $vid = intval($row['variant_id'] ?? 0);
                    if ($vid) $variant_ids[$vid] = true;
                }
            }
            $variants = array();
            foreach (array_keys($variant_ids) as $vid) {
                $v = $this->db->get_variant($vid);
                if ($v && $v->status === 'active') $variants[] = $v;
            }
        } else {
            $variants = $this->db->get_variants($model_id);
        }
        
        wp_send_json_success(array('variants' => $variants));
    }
    
    /**
     * Get pricing by variant
     */
    public function get_pricing() {
        check_ajax_referer('exchange_pro_nonce', 'nonce');
        
        $variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : 0;
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if (!$variant_id) {
            wp_send_json_error(array('message' => __('Invalid variant', 'exchange-pro')));
        }
        
        // Custom pricing rules (product-specific): if enabled, ONLY the explicitly configured
        // variants should be eligible for exchange for this product.
        $use_custom = false;
        $custom_prices = array();

        if ($product_id) {
            $custom_pricing_enabled = get_post_meta($product_id, '_exchange_pro_custom_pricing', true);
            if ($custom_pricing_enabled === 'yes') {
                $pricing_data = get_post_meta($product_id, '_exchange_pro_pricing_data', true);

                if (is_array($pricing_data)) {
                    foreach ($pricing_data as $row) {
                        $row_variant_id = isset($row['variant_id']) ? intval($row['variant_id']) : 0;
                        if ($row_variant_id && $row_variant_id === $variant_id) {
                            $use_custom = true;
                            $currency_symbol = get_option('exchange_pro_currency_symbol', '₹');
                            foreach (array('excellent', 'good', 'fair', 'poor') as $condition) {
                                $price = floatval($row[$condition] ?? 0);
                                if ($price > 0) {
                                    $custom_prices[$condition] = array(
                                        'price' => $price,
                                        'formatted' => $currency_symbol . number_format($price, 0, '.', ',')
                                    );
                                }
                            }
                            break;
                        }
                    }
                }

                // If custom pricing is enabled but this variant isn't configured, block exchange.
                if (!$use_custom) {
                    wp_send_json_error(array(
                        'message' => __('This product uses custom exchange pricing, but no price is configured for the selected device. Please contact the store.', 'exchange-pro'),
                        'code' => 'no_custom_price'
                    ));
                }
            }
        }

        // Use custom prices if custom pricing enabled, otherwise use global pricing matrix.
        $pricing = ($use_custom && !empty($custom_prices))
            ? $custom_prices
            : $this->pricing->get_variant_pricing($variant_id);
        
        $conditions = $this->pricing->get_condition_options();
        
        wp_send_json_success(array(
            'pricing' => $pricing,
            'conditions' => $conditions,
            'custom_pricing_used' => $use_custom
        ));
    }
    
    /**
     * Check pincode serviceability
     */
    public function check_pincode() {
        check_ajax_referer('exchange_pro_nonce', 'nonce');
        
        $pincode = isset($_POST['pincode']) ? sanitize_text_field($_POST['pincode']) : '';
        
        if (empty($pincode)) {
            wp_send_json_error(array('message' => __('Please enter a pincode', 'exchange-pro')));
        }
        
        $is_serviceable = $this->db->check_pincode($pincode);
        
        if ($is_serviceable) {
            wp_send_json_success(array(
                'message' => __('Exchange service available in your area', 'exchange-pro'),
                'serviceable' => true
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Sorry, exchange service not available in your area', 'exchange-pro'),
                'serviceable' => false
            ));
        }
    }
    
    /**
     * Save exchange data to session
     */
    public function save_exchange() {
        check_ajax_referer('exchange_pro_nonce', 'nonce');
        
        // Validate required fields
        $required_fields = array('category_id', 'brand_id', 'model_id', 'variant_id', 'condition');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => sprintf(__('Missing required field: %s', 'exchange-pro'), $field)));
            }
        }
        
        // Sanitize and collect data
        $exchange_data = array(
            'product_id' => isset($_POST['product_id']) ? intval($_POST['product_id']) : 0,
            'category_id' => intval($_POST['category_id']),
            'brand_id' => intval($_POST['brand_id']),
            'model_id' => intval($_POST['model_id']),
            'variant_id' => intval($_POST['variant_id']),
            'condition' => sanitize_text_field($_POST['condition']),
            'imei_serial' => isset($_POST['imei_serial']) ? sanitize_text_field($_POST['imei_serial']) : '',
            'model_number' => isset($_POST['model_number']) ? sanitize_text_field($_POST['model_number']) : '',
            'pincode' => isset($_POST['pincode']) ? sanitize_text_field($_POST['pincode']) : '',
        );
        
        // Validate pincode if required
        if (get_option('exchange_pro_pincode_validation', 'yes') === 'yes' && !empty($exchange_data['pincode'])) {
            if (!$this->db->check_pincode($exchange_data['pincode'])) {
                wp_send_json_error(array('message' => __('Exchange service not available in your pincode', 'exchange-pro')));
            }
        }
        
        // Validate IMEI if required for mobiles
        $category = $this->db->get_category($exchange_data['category_id']);
        if ($category && $category->slug === 'mobile') {
            if (get_option('exchange_pro_imei_mandatory', 'yes') === 'yes' && empty($exchange_data['imei_serial'])) {
                wp_send_json_error(array('message' => __('IMEI/Serial number is required for mobiles', 'exchange-pro')));
            }
        } else {
            // For other devices, require model number
            if (empty($exchange_data['model_number'])) {
                wp_send_json_error(array('message' => __('Model number is required for this device type', 'exchange-pro')));
            }
        }
        
        // Calculate exchange value
        $exchange_value = $this->pricing->calculate_exchange_value($exchange_data);
        
        if ($exchange_value <= 0) {
            wp_send_json_error(array('message' => __('Unable to calculate exchange value. Please try again.', 'exchange-pro')));
        }
        
        $exchange_data['exchange_value'] = $exchange_value;
        
        // Save to session
        $this->session->set_exchange_data($exchange_data);
        
        // Get formatted details for display
        $brand = $this->db->get_brand($exchange_data['brand_id']);
        $model = $this->db->get_model($exchange_data['model_id']);
        $variant = $this->db->get_variant($exchange_data['variant_id']);
        
        $device_name = sprintf('%s %s (%s)', $brand->name, $model->name, $variant->name);
        
        wp_send_json_success(array(
            'message' => __('Exchange value calculated successfully', 'exchange-pro'),
            'exchange_value' => $exchange_value,
            'formatted_value' => $this->pricing->format_price($exchange_value),
            'device_name' => $device_name,
            'redirect_to_cart' => true
        ));
    }
    
    /**
     * Remove exchange from session
     */
    public function remove_exchange() {
        check_ajax_referer('exchange_pro_nonce', 'nonce');
        
        $this->session->clear_exchange_data();
        
        wp_send_json_success(array('message' => __('Exchange removed', 'exchange-pro')));
    }
}
