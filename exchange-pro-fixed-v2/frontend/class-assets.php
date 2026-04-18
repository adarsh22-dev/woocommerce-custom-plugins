<?php
/**
 * Assets Handler
 * Manages CSS and JS enqueuing
 */

if (!defined('ABSPATH')) {
    exit;
}

class Exchange_Pro_Assets {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!is_product() && !is_cart() && !is_checkout()) {
            return;
        }
        
        // Bootstrap CSS
        wp_enqueue_style(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            array(),
            '5.3.0'
        );
        
        // Font Awesome
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );
        
        // Custom CSS
        wp_enqueue_style(
            'exchange-pro-frontend',
            EXCHANGE_PRO_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            EXCHANGE_PRO_VERSION
        );

        // Optional: site-specific CSS scoped to the modal so it won't affect the theme.
        $custom_css = get_option('exchange_pro_custom_css', '');
        if (!empty($custom_css)) {
            $scoped = $this->scope_css_to_modal($custom_css, '#exchangeProModal');
            if (!empty($scoped)) {
                wp_add_inline_style('exchange-pro-frontend', $scoped);
            }
        }
        
        // Bootstrap JS
        wp_enqueue_script(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            array('jquery'),
            '5.3.0',
            true
        );
        
        // Custom JS
        wp_enqueue_script(
            'exchange-pro-frontend',
            EXCHANGE_PRO_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'bootstrap'),
            EXCHANGE_PRO_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('exchange-pro-frontend', 'exchangeProData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('exchange_pro_nonce'),
            'product_id' => is_product() ? get_the_ID() : 0,
            'currency_symbol' => get_option('exchange_pro_currency_symbol', '₹'),
            'primary_color' => get_option('exchange_pro_primary_color', '#ff6600'),
            'strings' => array(
                'loading' => __('Loading...', 'exchange-pro'),
                'select_category' => __('Please select a category', 'exchange-pro'),
                'select_brand' => __('Please select a brand', 'exchange-pro'),
                'select_model' => __('Please select a model', 'exchange-pro'),
                'select_variant' => __('Please select a variant', 'exchange-pro'),
                'select_condition' => __('Please select a condition', 'exchange-pro'),
                'enter_imei' => __('Please enter IMEI/Serial number', 'exchange-pro'),
                'enter_pincode' => __('Please enter pincode', 'exchange-pro'),
                'verify_pincode' => __('Please verify pincode', 'exchange-pro'),
                'accept_terms' => __('Please accept the terms', 'exchange-pro'),
                'error' => __('An error occurred. Please try again.', 'exchange-pro'),
                'success' => __('Exchange value calculated successfully!', 'exchange-pro'),
            )
        ));
    }

    /**
     * Best-effort CSS scoping: prefixes selectors with the modal container.
     * This prevents accidental global CSS overrides.
     */
    private function scope_css_to_modal($css, $scope_selector = '#exchangeProModal') {
        $css = (string) $css;
        $css = str_replace(array('</style>', '<style>'), '', $css);
        $css = trim($css);
        if ($css === '') return '';

        // Split by "}" and re-prefix each selector block.
        $out = '';
        $blocks = preg_split('/}\s*/', $css);
        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') continue;
            $parts = explode('{', $block, 2);
            if (count($parts) !== 2) continue;
            $sel = trim($parts[0]);
            $body = $parts[1];
            // Keep at-rules unmodified (media queries etc.)
            if (strpos($sel, '@') === 0) {
                $out .= $sel . '{' . $body . '}\n';
                continue;
            }
            $selectors = array_map('trim', explode(',', $sel));
            $prefixed = array();
            foreach ($selectors as $s) {
                if ($s === '') continue;
                if (strpos($s, $scope_selector) === 0) {
                    $prefixed[] = $s;
                } else {
                    $prefixed[] = $scope_selector . ' ' . $s;
                }
            }
            if (!empty($prefixed)) {
                $out .= implode(', ', $prefixed) . '{' . $body . '}\n';
            }
        }
        return $out;
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'exchange-pro') === false && $hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        // Bootstrap CSS
        wp_enqueue_style(
            'bootstrap-admin',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            array(),
            '5.3.0'
        );
        
        // Font Awesome
        wp_enqueue_style(
            'font-awesome-admin',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );
        
        // Custom admin CSS
        wp_enqueue_style(
            'exchange-pro-admin',
            EXCHANGE_PRO_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            EXCHANGE_PRO_VERSION
        );
        
        // Bootstrap JS
        wp_enqueue_script(
            'bootstrap-admin',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            array('jquery'),
            '5.3.0',
            true
        );
        
        // Custom admin JS
        wp_enqueue_script(
            'exchange-pro-admin',
            EXCHANGE_PRO_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'bootstrap-admin'),
            EXCHANGE_PRO_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('exchange-pro-admin', 'exchangeProAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('exchange_pro_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'exchange-pro'),
                'loading' => __('Loading...', 'exchange-pro'),
                'saved' => __('Saved successfully!', 'exchange-pro'),
                'error' => __('An error occurred. Please try again.', 'exchange-pro'),
            )
        ));
    }

}
