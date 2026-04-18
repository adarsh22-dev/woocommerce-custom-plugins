<?php
/**
 * CPF Calculator Engine
 * 
 * Handles all calculations: AOV, CPF, PGF, Ops-Cost, Platform Net
 */

if (!defined('ABSPATH')) {
    exit;
}

class CISAI_CPF_Calculator_Engine {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Constructor logic if needed
    }
    
    /**
     * Calculate AOV (Average Order Value)
     * For single order: AOV = order subtotal
     */
    public function calculate_aov($cart_or_order = null) {
        if (is_a($cart_or_order, 'WC_Order')) {
            // For orders
            return floatval($cart_or_order->get_subtotal());
        } elseif (is_a($cart_or_order, 'WC_Cart')) {
            // For cart
            return floatval($cart_or_order->get_subtotal());
        } elseif (is_numeric($cart_or_order)) {
            // Direct amount
            return floatval($cart_or_order);
        }
        
        // Default: use current cart
        if (WC()->cart) {
            return floatval(WC()->cart->get_subtotal());
        }
        
        return 0;
    }
    
    /**
     * Calculate CPF (Customer Platform Fee)
     * Formula: CPF = (A% × AOV) + flat ₹f
     */
    public function calculate_cpf($aov = null) {
        if (null === $aov) {
            $aov = $this->calculate_aov();
        }
        
        $percentage = floatval(get_option('cisai_cpf_percentage', 5));
        $flat_fee = floatval(get_option('cisai_cpf_flat_fee', 2));
        
        $percentage_amount = ($percentage / 100) * $aov;
        $cpf = $percentage_amount + $flat_fee;
        
        return round($cpf, 2);
    }
    
    /**
     * Calculate PGF (Payment Gateway Fee)
     * Formula: PGF = (Gateway % × AOV) + fixed charge
     */
    public function calculate_pgf($aov = null) {
        if (null === $aov) {
            $aov = $this->calculate_aov();
        }
        
        $gateway_percentage = floatval(get_option('cisai_cpf_gateway_percentage', 2));
        $gateway_fixed = floatval(get_option('cisai_cpf_gateway_fixed', 3));
        
        $percentage_amount = ($gateway_percentage / 100) * $aov;
        $pgf = $percentage_amount + $gateway_fixed;
        
        return round($pgf, 2);
    }
    
    /**
     * Get Ops-Cost (Operational Cost)
     */
    public function get_ops_cost() {
        return floatval(get_option('cisai_cpf_ops_cost', 15));
    }
    
    /**
     * Calculate total category fees for cart/order
     */
    public function calculate_category_fees($cart_or_order = null) {
        $category_fees = get_option('cisai_category_fees', []);
        if (empty($category_fees)) {
            return 0;
        }
        
        $total_cat_fees = 0;
        $applied_categories = [];
        
        if (is_a($cart_or_order, 'WC_Order')) {
            // For orders
            foreach ($cart_or_order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'slugs']);
                
                foreach ($categories as $cat_slug) {
                    if (isset($category_fees[$cat_slug]) && !isset($applied_categories[$cat_slug])) {
                        $total_cat_fees += floatval($category_fees[$cat_slug]);
                        $applied_categories[$cat_slug] = true;
                    }
                }
            }
        } elseif (is_a($cart_or_order, 'WC_Cart')) {
            // For cart
            foreach ($cart_or_order->get_cart() as $cart_item) {
                $product_id = $cart_item['product_id'];
                $categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'slugs']);
                
                foreach ($categories as $cat_slug) {
                    if (isset($category_fees[$cat_slug]) && !isset($applied_categories[$cat_slug])) {
                        $total_cat_fees += floatval($category_fees[$cat_slug]);
                        $applied_categories[$cat_slug] = true;
                    }
                }
            }
        } elseif (WC()->cart) {
            // Default: use current cart
            foreach (WC()->cart->get_cart() as $cart_item) {
                $product_id = $cart_item['product_id'];
                $categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'slugs']);
                
                foreach ($categories as $cat_slug) {
                    if (isset($category_fees[$cat_slug]) && !isset($applied_categories[$cat_slug])) {
                        $total_cat_fees += floatval($category_fees[$cat_slug]);
                        $applied_categories[$cat_slug] = true;
                    }
                }
            }
        }
        
        return round($total_cat_fees, 2);
    }
    
    /**
     * Calculate Platform Net
     * Formula: Platform Net = CPF + Category Fees - PGF - Ops-Cost
     */
    public function calculate_platform_net($aov = null) {
        if (null === $aov) {
            $aov = $this->calculate_aov();
        }
        
        $cpf = $this->calculate_cpf($aov);
        $category_fees = $this->calculate_category_fees();
        $pgf = $this->calculate_pgf($aov);
        $ops_cost = $this->get_ops_cost();
        
        $platform_net = ($cpf + $category_fees) - $pgf - $ops_cost;
        
        return round($platform_net, 2);
    }
    
    /**
     * Calculate break-even point
     * The AOV where Platform Net = 0
     */
    public function calculate_breakeven() {
        $percentage = floatval(get_option('cisai_cpf_percentage', 5));
        $flat_fee = floatval(get_option('cisai_cpf_flat_fee', 2));
        $gateway_percentage = floatval(get_option('cisai_cpf_gateway_percentage', 2));
        $gateway_fixed = floatval(get_option('cisai_cpf_gateway_fixed', 3));
        $ops_cost = $this->get_ops_cost();
        $category_fees = $this->calculate_category_fees();
        
        // Formula derivation:
        // CPF + Cat Fees - PGF - Ops = 0
        // [(A% × AOV) + flat] + Cat Fees - [(G% × AOV) + fixed] - Ops = 0
        // (A% - G%) × AOV = fixed + Ops - flat - Cat Fees
        // AOV = (fixed + Ops - flat - Cat Fees) / (A% - G%)
        
        $numerator = $gateway_fixed + $ops_cost - $flat_fee - $category_fees;
        $denominator = ($percentage - $gateway_percentage) / 100;
        
        if ($denominator <= 0) {
            return 0; // Cannot break even with current settings
        }
        
        $breakeven = $numerator / $denominator;
        
        return round(max(0, $breakeven), 2);
    }
    
    /**
     * Get complete calculation breakdown
     */
    public function get_breakdown($aov = null) {
        if (null === $aov) {
            $aov = $this->calculate_aov();
        }
        
        $percentage = floatval(get_option('cisai_cpf_percentage', 5));
        $flat_fee = floatval(get_option('cisai_cpf_flat_fee', 2));
        $cpf = $this->calculate_cpf($aov);
        $category_fees = $this->calculate_category_fees();
        $pgf = $this->calculate_pgf($aov);
        $ops_cost = $this->get_ops_cost();
        $platform_net = $this->calculate_platform_net($aov);
        $breakeven = $this->calculate_breakeven();
        
        return [
            'aov' => $aov,
            'cpf_percentage' => $percentage,
            'cpf_flat_fee' => $flat_fee,
            'cpf_percentage_amount' => round(($percentage / 100) * $aov, 2),
            'cpf_total' => $cpf,
            'category_fees' => $category_fees,
            'pgf' => $pgf,
            'ops_cost' => $ops_cost,
            'platform_net' => $platform_net,
            'is_profitable' => $platform_net >= 0,
            'breakeven_point' => $breakeven,
        ];
    }
    
    /**
     * Get scenario testing results
     */
    public function get_scenarios() {
        $test_values = [500, 1000, 2500];
        $scenarios = [];
        
        foreach ($test_values as $value) {
            $scenarios[] = [
                'aov' => $value,
                'breakdown' => $this->get_breakdown($value),
            ];
        }
        
        return $scenarios;
    }
    
    /**
     * Format currency
     */
    public function format_currency($amount) {
        return wc_price($amount);
    }
    
    /**
     * Check if CPF should be applied
     */
    public function should_apply_cpf() {
        // Check if CPF is enabled
        if (get_option('cisai_cpf_enabled', 'yes') !== 'yes') {
            return false;
        }
        
        // Check minimum order value
        $min_order = floatval(get_option('cisai_cpf_min_order', 0));
        if ($min_order > 0 && WC()->cart) {
            $cart_total = WC()->cart->get_subtotal();
            if ($cart_total < $min_order) {
                return false;
            }
        }
        
        // Check user role exclusions
        $excluded_roles = get_option('cisai_cpf_excluded_roles', []);
        if (!empty($excluded_roles) && is_user_logged_in()) {
            $user = wp_get_current_user();
            $user_roles = $user->roles;
            
            if (array_intersect($user_roles, $excluded_roles)) {
                return false;
            }
        }
        
        return true;
    }
}