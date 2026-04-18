<?php
/**
 * Plugin Name: CISAI WooAI Assistant Pro
 * Plugin URI: https://wooai-assistant.com
 * Description: Complete AI-powered chat assistant for WooCommerce stores with modern admin interface
 * Version: 3.2.0
 * Author: Adarsh singh
 * License: GPL v2 or later
 * Text Domain: wooai-assistant
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 */

if (!defined('ABSPATH')) exit;

define('WOOAI_VERSION', '3.2.0');
define('WOOAI_FILE', __FILE__);
define('WOOAI_PATH', plugin_dir_path(__FILE__));
define('WOOAI_URL', plugin_dir_url(__FILE__));
define('WOOAI_BASENAME', plugin_basename(__FILE__));

final class WooAI_Assistant {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }
    
    private function includes() {
        require_once WOOAI_PATH . 'includes/class-installer.php';
        require_once WOOAI_PATH . 'includes/class-ajax-handler.php';
        require_once WOOAI_PATH . 'includes/class-ai-engine.php';
        require_once WOOAI_PATH . 'includes/class-frontend.php';
        
        if (is_admin()) {
            require_once WOOAI_PATH . 'admin/class-admin.php';
        }
    }
    
    private function init_hooks() {
        register_activation_hook(WOOAI_FILE, array('WooAI_Installer', 'activate'));
        add_action('plugins_loaded', array($this, 'init'), 0);
    }
    
    public function init() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'wc_missing_notice'));
            return;
        }
        
        WooAI_Ajax_Handler::init();
        WooAI_Frontend::init();
        
        if (is_admin()) {
            WooAI_Admin::init();
        }
    }
    
    public function wc_missing_notice() {
        echo '<div class="error"><p>' . esc_html__('WooAI Assistant requires WooCommerce to be installed and active.', 'wooai-assistant') . '</p></div>';
    }
}

function wooai() {
    return WooAI_Assistant::instance();
}

wooai();
