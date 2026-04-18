<?php
/**
 * Session Handler Class
 * Manages session data for exchange flow
 */

if (!defined('ABSPATH')) {
    exit;
}

class Exchange_Pro_Session {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Use WooCommerce session if available
        add_action('woocommerce_init', array($this, 'init_session'));
    }
    
    public function init_session() {
        if (function_exists('WC') && WC()->session) {
            if (!WC()->session->has_session()) {
                WC()->session->set_customer_session_cookie(true);
            }
        }
    }
    
    public function set($key, $value) {
        if (function_exists('WC') && WC()->session) {
            WC()->session->set('exchange_pro_' . $key, $value);
        }
    }
    
    public function get($key, $default = null) {
        if (function_exists('WC') && WC()->session) {
            return WC()->session->get('exchange_pro_' . $key, $default);
        }
        return $default;
    }
    
    public function delete($key) {
        if (function_exists('WC') && WC()->session) {
            WC()->session->__unset('exchange_pro_' . $key);
        }
    }
    
    public function set_exchange_data($data) {
        $this->set('exchange_data', $data);
        Exchange_Pro_Logger::log('Exchange data saved to session', 'info', $data);
    }
    
    public function get_exchange_data() {
        return $this->get('exchange_data');
    }
    
    public function clear_exchange_data() {
        $this->delete('exchange_data');
        Exchange_Pro_Logger::log('Exchange data cleared from session', 'info');
    }
    
    public function has_exchange_data() {
        $data = $this->get_exchange_data();
        return !empty($data);
    }
}
