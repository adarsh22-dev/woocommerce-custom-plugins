
<?php
/**
 * Plugin Name: Woo Variations – WCFM Vendor Dashboard v98
 * Version: 2.1.0
 */
if ( ! defined('ABSPATH') ) exit;

add_action('plugins_loaded', function () {
    if ( ! defined('WCFM_VERSION') ) return;
    require_once __DIR__ . '/includes/class-menu.php';
    require_once __DIR__ . '/includes/class-page.php';
    require_once __DIR__ . '/includes/class-ajax.php';
});

add_action('wp_enqueue_scripts', function () {
    if ( function_exists('wcfm_is_vendor') && wcfm_is_vendor() ) {
        wp_enqueue_style('wv-css', plugins_url('assets/css/style.css', __FILE__));
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
        wp_enqueue_script('wv-js', plugins_url('assets/js/main.js', __FILE__), ['jquery','chart-js'], null, true);
        wp_localize_script('wv-js', 'WV', [
            'ajax' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wv_nonce')
        ]);
    }
});
