<?php
/**
 * Plugin Name: CISAI AWS Product Exchange Pro for WooCommerce v323
 * Plugin URI: 
 * Description: Complete Amazon-style product exchange system with dynamic pricing, condition-based valuation, and multi-device support
 * Version: 1.0.0
 * Author: Adarsh Singh
 * Author URI: 
 * Text Domain: exchange-pro
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.5
 * License: GPL v2 or later
 * License URI: 
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EXCHANGE_PRO_VERSION', '1.0.0');
define('EXCHANGE_PRO_PLUGIN_FILE', __FILE__);
define('EXCHANGE_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EXCHANGE_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EXCHANGE_PRO_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if WooCommerce is active
function exchange_pro_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'exchange_pro_woocommerce_missing_notice');
        return false;
    }
    return true;
}

function exchange_pro_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('Amazon-Style Product Exchange Pro requires WooCommerce to be installed and activated.', 'exchange-pro'); ?></p>
    </div>
    <?php
}

// Main Plugin Class
final class Amazon_Exchange_Pro {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
        $this->includes();
        $this->init_classes();
    }
    
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('exchange-pro', false, dirname(EXCHANGE_PRO_PLUGIN_BASENAME) . '/languages');
    }
    
    private function includes() {
        // Core includes
        $core_files = array(
            'includes/class-database.php',
            'includes/class-session.php',
            'includes/class-pricing.php',
            'includes/class-cart-handler.php',
            'includes/class-order-handler.php',
            'includes/class-ajax-handler.php',
            'includes/class-logger.php',
        );
        
        foreach ($core_files as $file) {
            $filepath = EXCHANGE_PRO_PLUGIN_DIR . $file;
            if (file_exists($filepath)) {
                require_once $filepath;
            } else {
                add_action('admin_notices', function() use ($file) {
                    echo '<div class="notice notice-error"><p>Exchange Pro: Missing file ' . esc_html($file) . '</p></div>';
                });
            }
        }
        
        // Admin includes
        if (is_admin()) {
            $admin_files = array(
                'admin/class-admin-menu.php',
                'admin/class-dashboard.php',
                'admin/class-category-manager.php',
                'admin/class-devices.php',
                'admin/class-pricing-manager.php',
                'admin/class-product-settings.php',
                'admin/class-pincode-manager.php',
                'admin/class-settings.php',
            );
            
            foreach ($admin_files as $file) {
                $filepath = EXCHANGE_PRO_PLUGIN_DIR . $file;
                if (file_exists($filepath)) {
                    require_once $filepath;
                }
            }
        }
        
        // Frontend includes
        $frontend_files = array(
            'frontend/class-product-page.php',
            'frontend/class-popup.php',
            'frontend/class-assets.php',
        );
        
        foreach ($frontend_files as $file) {
            $filepath = EXCHANGE_PRO_PLUGIN_DIR . $file;
            if (file_exists($filepath)) {
                require_once $filepath;
            }
        }
    }
    
    private function init_classes() {
        // Initialize core classes
        try {
            if (class_exists('Exchange_Pro_Database')) {
                Exchange_Pro_Database::get_instance();
            }
            if (class_exists('Exchange_Pro_Session')) {
                Exchange_Pro_Session::get_instance();
            }
            if (class_exists('Exchange_Pro_Pricing')) {
                Exchange_Pro_Pricing::get_instance();
            }
            if (class_exists('Exchange_Pro_Cart_Handler')) {
                Exchange_Pro_Cart_Handler::get_instance();
            }
            if (class_exists('Exchange_Pro_Order_Handler')) {
                Exchange_Pro_Order_Handler::get_instance();
            }
            if (class_exists('Exchange_Pro_Ajax_Handler')) {
                Exchange_Pro_Ajax_Handler::get_instance();
            }
            if (class_exists('Exchange_Pro_Logger')) {
                Exchange_Pro_Logger::get_instance();
            if (class_exists('Exchange_Pro_WCFM_Integration')) {
                Exchange_Pro_WCFM_Integration::get_instance();
            }
            }
            
            // Initialize admin classes
            if (is_admin()) {
                if (class_exists('Exchange_Pro_Admin_Menu')) {
                    Exchange_Pro_Admin_Menu::get_instance();
                }
                if (class_exists('Exchange_Pro_Dashboard')) {
                    Exchange_Pro_Dashboard::get_instance();
                }
                if (class_exists('Exchange_Pro_Category_Manager')) {
                    Exchange_Pro_Category_Manager::get_instance();
                }
                if (class_exists('Exchange_Pro_Devices')) {
                    Exchange_Pro_Devices::get_instance();
                }
                if (class_exists('Exchange_Pro_Pricing_Manager')) {
                    Exchange_Pro_Pricing_Manager::get_instance();
                }
                if (class_exists('Exchange_Pro_Product_Settings')) {
                    Exchange_Pro_Product_Settings::get_instance();
                }
                if (class_exists('Exchange_Pro_Pincode_Manager')) {
                    Exchange_Pro_Pincode_Manager::get_instance();
                }
                if (class_exists('Exchange_Pro_Settings')) {
                    Exchange_Pro_Settings::get_instance();
                }
            }
            
            // Initialize frontend classes
            if (class_exists('Exchange_Pro_Product_Page')) {
                Exchange_Pro_Product_Page::get_instance();
            }
            if (class_exists('Exchange_Pro_Popup')) {
                Exchange_Pro_Popup::get_instance();
            }
            if (class_exists('Exchange_Pro_Assets')) {
                Exchange_Pro_Assets::get_instance();
            }
        } catch (Exception $e) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>Exchange Pro Error: ' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
    }
    
    public function activate() {
        // Create database tables
        if (class_exists('Exchange_Pro_Database')) {
            $db = Exchange_Pro_Database::get_instance();
            $db->create_tables();
        }
        
        // Set default options
        $this->set_default_options();
        
        // Clear rewrite rules
        flush_rewrite_rules();
        
        // Log activation
        if (class_exists('Exchange_Pro_Logger')) {
            Exchange_Pro_Logger::log('Plugin activated', 'info');
        }
    }
    
    public function deactivate() {
        // Clear scheduled events if any
        wp_clear_scheduled_hook('exchange_pro_daily_cleanup');
        
        // Log deactivation
        Exchange_Pro_Logger::log('Plugin deactivated', 'info');
    }
    
    private function set_default_options() {
        $defaults = array(
            'exchange_pro_enable' => 'yes',
            'exchange_pro_pincode_validation' => 'yes',
            'exchange_pro_popup_theme' => 'light',
            'exchange_pro_primary_color' => '#ff6600',
            'exchange_pro_button_text' => 'Get Exchange Value',
            'exchange_pro_currency_symbol' => '₹',
            'exchange_pro_max_exchange_percentage' => 80,
            'exchange_pro_imei_mandatory' => 'yes',
            // If you want demo starter categories/pincodes, set this to 'yes'.
            // Default is 'no' to avoid showing sample data on the storefront.
            'exchange_pro_seed_demo_data' => 'no',
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }
    }
}

// Initialize the plugin
function exchange_pro() {
    return Amazon_Exchange_Pro::get_instance();
}

// Start the plugin after all plugins are loaded
add_action('plugins_loaded', function() {
    if (exchange_pro_check_woocommerce()) {
        exchange_pro();
    }
}, 20);
