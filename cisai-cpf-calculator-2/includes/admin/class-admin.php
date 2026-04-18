<?php
/**
 * Admin Core
 * Manages admin menu and notices
 */

if (!defined('ABSPATH')) {
    exit;
}

class CISAI_CPF_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_' . CISAI_CPF_PLUGIN_BASENAME, [$this, 'add_settings_link']);
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Platform Fees', 'cisai-cpf'),
            __('Platform Fees', 'cisai-cpf'),
            'manage_woocommerce',
            'cisai-cpf-dashboard',
            [CISAI_CPF_Dashboard::get_instance(), 'render'],
            'dashicons-chart-line',
            56
        );
        
        // Dashboard submenu (same as main)
        add_submenu_page(
            'cisai-cpf-dashboard',
            __('Dashboard', 'cisai-cpf'),
            __('Dashboard', 'cisai-cpf'),
            'manage_woocommerce',
            'cisai-cpf-dashboard',
            [CISAI_CPF_Dashboard::get_instance(), 'render']
        );
        
        // Settings submenu
        add_submenu_page(
            'cisai-cpf-dashboard',
            __('Settings', 'cisai-cpf'),
            __('Settings', 'cisai-cpf'),
            'manage_woocommerce',
            'cisai-cpf-settings',
            [CISAI_CPF_Settings::get_instance(), 'render']
        );
    }
    
    /**
     * Add settings link to plugins page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=cisai-cpf-dashboard') . '">' . __('Dashboard', 'cisai-cpf') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}