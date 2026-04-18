<?php
if (!defined('ABSPATH')) exit;

class WooAI_Admin {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }
    
    public static function add_menu() {
        add_menu_page(
            'WooAI Assistant',
            'WooAI Admin',
            'manage_options',
            'wooai',
            array(__CLASS__, 'render_dashboard'),
            'dashicons-format-chat',
            56
        );
        
        add_submenu_page('wooai', 'Dashboard', 'Dashboard', 'manage_options', 'wooai', array(__CLASS__, 'render_dashboard'));
        add_submenu_page('wooai', 'Product Assignments', 'Assignments', 'manage_options', 'wooai-assignments', array(__CLASS__, 'render_assignments'));
        add_submenu_page('wooai', 'Policies', 'Policies', 'manage_options', 'wooai-policies', array(__CLASS__, 'render_policies'));
        add_submenu_page('wooai', 'Quick Actions', 'Quick Actions', 'manage_options', 'wooai-actions', array(__CLASS__, 'render_actions'));
        add_submenu_page('wooai', 'Callback Requests', 'Callbacks', 'manage_options', 'wooai-callbacks', array(__CLASS__, 'render_callbacks'));
        add_submenu_page('wooai', 'Chat Logs', 'Chat Logs', 'manage_options', 'wooai-logs', array(__CLASS__, 'render_logs'));
        add_submenu_page('wooai', 'Plugin Settings', 'Settings', 'manage_options', 'wooai-settings', array(__CLASS__, 'render_settings'));
    }
    
    public static function enqueue_assets($hook) {
        if (strpos($hook, 'wooai') === false) return;
        
        wp_enqueue_style('wooai-admin', WOOAI_URL . 'admin/assets/css/admin.css', array(), WOOAI_VERSION);
        wp_enqueue_script('wooai-admin', WOOAI_URL . 'admin/assets/js/admin.js', array('jquery'), WOOAI_VERSION, true);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js', array(), '4.4.0', true);
        
        wp_localize_script('wooai-admin', 'wooaiAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wooai_admin_nonce')
        ));
    }
    
    public static function render_dashboard() {
        include WOOAI_PATH . 'admin/views/dashboard.php';
    }
    
    public static function render_assignments() {
        include WOOAI_PATH . 'admin/views/assignments.php';
    }
    
    public static function render_policies() {
        include WOOAI_PATH . 'admin/views/policies.php';
    }
    
    public static function render_actions() {
        include WOOAI_PATH . 'admin/views/actions.php';
    }
    
    public static function render_logs() {
        include WOOAI_PATH . 'admin/views/logs.php';
    }
    
    public static function render_callbacks() {
        include WOOAI_PATH . 'admin/views/callbacks.php';
    }
    
    public static function render_settings() {
        include WOOAI_PATH . 'admin/views/settings.php';
    }
}
