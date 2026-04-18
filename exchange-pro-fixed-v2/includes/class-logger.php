<?php
/**
 * Logger Class
 * Handles logging for debugging and monitoring
 */

if (!defined('ABSPATH')) {
    exit;
}

class Exchange_Pro_Logger {
    
    private static $instance = null;
    private static $log_file;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/exchange-pro-logs';
        
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        self::$log_file = $log_dir . '/exchange-pro-' . date('Y-m-d') . '.log';
    }
    
    /**
     * Log message
     */
    public static function log($message, $level = 'info', $data = array()) {
        if (get_option('exchange_pro_enable_logging', 'yes') !== 'yes') {
            return;
        }
        
        $timestamp = current_time('mysql');
        $log_entry = sprintf(
            "[%s] [%s] %s\n",
            $timestamp,
            strtoupper($level),
            $message
        );
        
        if (!empty($data)) {
            $log_entry .= "Data: " . print_r($data, true) . "\n";
        }
        
        $log_entry .= str_repeat('-', 80) . "\n";
        
        error_log($log_entry, 3, self::$log_file);
    }
    
    /**
     * Get log contents
     */
    public static function get_logs($date = null) {
        if ($date) {
            $upload_dir = wp_upload_dir();
            $log_dir = $upload_dir['basedir'] . '/exchange-pro-logs';
            $log_file = $log_dir . '/exchange-pro-' . $date . '.log';
        } else {
            $log_file = self::$log_file;
        }
        
        if (file_exists($log_file)) {
            return file_get_contents($log_file);
        }
        
        return '';
    }
    
    /**
     * Clear logs
     */
    public static function clear_logs() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/exchange-pro-logs';
        
        if (is_dir($log_dir)) {
            $files = glob($log_dir . '/*.log');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        
        self::log('Logs cleared', 'info');
    }
    
    /**
     * Get available log dates
     */
    public static function get_log_dates() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/exchange-pro-logs';
        $dates = array();
        
        if (is_dir($log_dir)) {
            $files = glob($log_dir . '/*.log');
            foreach ($files as $file) {
                $filename = basename($file);
                if (preg_match('/exchange-pro-(\d{4}-\d{2}-\d{2})\.log/', $filename, $matches)) {
                    $dates[] = $matches[1];
                }
            }
        }
        
        rsort($dates);
        return $dates;
    }
}
