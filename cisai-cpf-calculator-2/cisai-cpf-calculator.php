<?php
/**
 * Plugin Name: CISAI CPF Calculator Pro
 * Plugin URI: https://cisai.com
 * Description: Advanced Customer Platform Fee (CPF) calculator with beautiful UI/UX, analytics, and complete admin controls
 * Version: 3.0.0
 * Author: Adarsh Singh
 * Author URI: https://cisai.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cisai-cpf
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('CISAI_CPF_VERSION', '3.0.0');
define('CISAI_CPF_PLUGIN_FILE', __FILE__);
define('CISAI_CPF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CISAI_CPF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CISAI_CPF_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class CISAI_CPF_Calculator {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', [$this, 'woocommerce_missing_notice']);
            return;
        }
        
        // Initialize plugin
        $this->init();
    }
    
    /**
     * Check if WooCommerce is active
     */
    private function is_woocommerce_active() {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="error"><p><strong>' . esc_html__('CISAI CPF Calculator Pro', 'cisai-cpf') . '</strong> ' . esc_html__('requires WooCommerce to be installed and active.', 'cisai-cpf') . '</p></div>';
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Load plugin text domain
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        // Include required files
        $this->includes();
        
        // Initialize components
        $this->init_components();
        
        // Register hooks
        $this->register_hooks();
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('cisai-cpf', false, dirname(CISAI_CPF_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once CISAI_CPF_PLUGIN_DIR . 'includes/class-calculator.php';
        require_once CISAI_CPF_PLUGIN_DIR . 'includes/class-cart.php';
        require_once CISAI_CPF_PLUGIN_DIR . 'includes/class-checkout.php';
        require_once CISAI_CPF_PLUGIN_DIR . 'includes/class-order.php';
        
        // Admin classes
        if (is_admin()) {
            require_once CISAI_CPF_PLUGIN_DIR . 'includes/admin/class-admin.php';
            require_once CISAI_CPF_PLUGIN_DIR . 'includes/admin/class-dashboard.php';
            require_once CISAI_CPF_PLUGIN_DIR . 'includes/admin/class-settings.php';
        }
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize core components
        CISAI_CPF_Calculator_Engine::get_instance();
        CISAI_CPF_Cart::get_instance();
        CISAI_CPF_Checkout::get_instance();
        CISAI_CPF_Order::get_instance();
        
        // Initialize admin components
        if (is_admin()) {
            CISAI_CPF_Admin::get_instance();
            CISAI_CPF_Dashboard::get_instance();
            CISAI_CPF_Settings::get_instance();
        }
    }
    
    /**
     * Register hooks
     */
    private function register_hooks() {
        // Activation hook
        register_activation_hook(__FILE__, [$this, 'activate']);
        
        // Deactivation hook
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'frontend_enqueue_scripts']);
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $defaults = [
            'cisai_cpf_enabled' => 'yes',
            'cisai_cpf_percentage' => '5',
            'cisai_cpf_flat_fee' => '2',
            'cisai_cpf_gateway_percentage' => '2',
            'cisai_cpf_gateway_fixed' => '3',
            'cisai_cpf_ops_cost' => '15',
            'cisai_cpf_show_breakdown' => 'yes',
            'cisai_cpf_display_mode' => 'detailed',
            'cisai_cpf_fee_label' => 'Platform Fee',
            'cisai_cpf_min_order' => '0',
        ];
        
        foreach ($defaults as $key => $value) {
            if (false === get_option($key)) {
                add_option($key, $value);
            }
        }
        
        // Initialize category fees
        $this->sync_category_fees();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Sync category fees
     */
    private function sync_category_fees() {
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
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'cisai-cpf') === false) {
            return;
        }
        
        // Admin CSS
        wp_enqueue_style(
            'cisai-cpf-admin',
            CISAI_CPF_PLUGIN_URL . 'assets/css/admin.css',
            [],
            CISAI_CPF_VERSION
        );
        
        // Chart.js for analytics
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );
        
        // Admin JS
        wp_enqueue_script(
            'cisai-cpf-admin',
            CISAI_CPF_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'chartjs'],
            CISAI_CPF_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('cisai-cpf-admin', 'cisaiCpfAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cisai_cpf_admin'),
            'currency' => get_woocommerce_currency_symbol(),
        ]);
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function frontend_enqueue_scripts() {
        if (!is_checkout() && !is_cart()) {
            return;
        }
        
        // Frontend CSS
        wp_enqueue_style(
            'cisai-cpf-frontend',
            CISAI_CPF_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            CISAI_CPF_VERSION
        );
        
        // Frontend JS
        wp_enqueue_script(
            'cisai-cpf-frontend',
            CISAI_CPF_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery'],
            CISAI_CPF_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('cisai-cpf-frontend', 'cisaiCpfFrontend', [
            'currency' => get_woocommerce_currency_symbol(),
            'displayMode' => get_option('cisai_cpf_display_mode', 'detailed'),
        ]);
    }
}

/**
 * Initialize the plugin
 */
function cisai_cpf() {
    return CISAI_CPF_Calculator::get_instance();
}

// Kick off the plugin
cisai_cpf();