<?php
/**
 * Cart Integration
 * Adds CPF and category fees to cart
 */

if (!defined('ABSPATH')) {
    exit;
}

class CISAI_CPF_Cart {
    
    private static $instance = null;
    private $calculator;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->calculator = CISAI_CPF_Calculator_Engine::get_instance();
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Add fees to cart
        add_action('woocommerce_cart_calculate_fees', [$this, 'add_fees_to_cart'], 20);
        
        // Sync category fees on category changes
        add_action('created_product_cat', [$this, 'sync_category_fees']);
        add_action('edited_product_cat', [$this, 'sync_category_fees']);
        add_action('delete_product_cat', [$this, 'sync_category_fees']);
    }
    
    /**
     * Add CPF and category fees to cart
     */
    public function add_fees_to_cart($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        if (!$this->calculator->should_apply_cpf()) {
            return;
        }
        
        // Add CPF
        $this->add_cpf_fee($cart);
        
        // Add category fees
        $this->add_category_fees($cart);
    }
    
    /**
     * Add CPF to cart
     */
    private function add_cpf_fee($cart) {
        $cpf_amount = $this->calculator->calculate_cpf();
        
        if ($cpf_amount > 0) {
            $fee_label = get_option('cisai_cpf_fee_label', 'Platform Fee');
            $cart->add_fee($fee_label, $cpf_amount, true);
        }
    }
    
    /**
     * Add category fees to cart
     */
    private function add_category_fees($cart) {
        $category_fees = get_option('cisai_category_fees', []);
        if (empty($category_fees)) {
            return;
        }
        
        $applied_fees = [];
        
        foreach ($cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            $categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'slugs']);
            
            foreach ($categories as $cat_slug) {
                if (isset($category_fees[$cat_slug]) && !isset($applied_fees[$cat_slug])) {
                    $fee_amount = floatval($category_fees[$cat_slug]);
                    
                    if ($fee_amount > 0) {
                        $term = get_term_by('slug', $cat_slug, 'product_cat');
                        $fee_name = sprintf('%s Fee', $term->name);
                        $cart->add_fee($fee_name, $fee_amount, true);
                        $applied_fees[$cat_slug] = true;
                    }
                }
            }
        }
    }
    
    /**
     * Sync category fees when categories change
     */
    public function sync_category_fees() {
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);
        
        $existing_fees = get_option('cisai_category_fees', []);
        $new_fees = [];
        
        foreach ($categories as $cat) {
            $new_fees[$cat->slug] = isset($existing_fees[$cat->slug]) ? $existing_fees[$cat->slug] : 0;
        }
        
        update_option('cisai_category_fees', $new_fees);
    }
}