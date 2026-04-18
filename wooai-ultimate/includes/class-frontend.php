<?php
if (!defined('ABSPATH')) exit;

class WooAI_Frontend {
    
    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('wp_footer', array(__CLASS__, 'render_widget'));
    }
    
    public static function enqueue_assets() {
        if (!get_option('wooai_enabled', '1')) {
            return;
        }
        
        // Styles
        wp_enqueue_style('wooai-frontend', WOOAI_URL . 'assets/css/frontend.css', array(), WOOAI_VERSION);
        
        // Scripts
        wp_enqueue_script('wooai-frontend', WOOAI_URL . 'assets/js/frontend.js', array('jquery'), WOOAI_VERSION, true);
        
        // Localize
        $actions = self::get_quick_actions();
        
        wp_localize_script('wooai-frontend', 'wooaiConfig', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wooai_nonce'),
            'greeting' => get_option('wooai_greeting', 'Hello! How can I help you today?'),
            'color' => get_option('wooai_color', '#7C3AED'),
            'actions' => $actions,
            'session_id' => self::get_session_id(),
            'user_id' => get_current_user_id()
        ));
    }
    
    private static function get_quick_actions() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}wooai_actions WHERE is_active = 1 ORDER BY sort_order",
            ARRAY_A
        );
    }
    
    private static function get_session_id() {
        if (isset($_COOKIE['wooai_session'])) {
            return sanitize_text_field($_COOKIE['wooai_session']);
        }
        
        $session_id = 'wooai_' . wp_generate_password(32, false);
        setcookie('wooai_session', $session_id, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
        
        return $session_id;
    }
    
    public static function render_widget() {
        if (!get_option('wooai_enabled', '1')) {
            return;
        }
        
        include WOOAI_PATH . 'templates/widget.php';
    }
}
