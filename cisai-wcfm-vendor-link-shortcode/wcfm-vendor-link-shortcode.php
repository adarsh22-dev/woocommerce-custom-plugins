<?php
/**
 * Plugin Name: CISAI WCFM Vendor Link Shortcode
 * Plugin URI: https://example.com/wcfm-vendor-link
 * Description: Adds a [vendor_link] shortcode to display a vendor's name as a clickable link to their WCFM store/profile page. Auto-detects on product/store pages.
 * Version: 1.1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: wcfm-vendor-link
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if WCFM is active
function wcfm_vendor_link_check_dependencies() {
    if (!class_exists('WCFM')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>WCFM Vendor Link Shortcode:</strong> Requires WCFM Marketplace plugin to be active.</p></div>';
        });
        return false;
    }
    return true;
}

// Activation hook
register_activation_hook(__FILE__, function() {
    if (wcfm_vendor_link_check_dependencies()) {
        flush_rewrite_rules();
    }
});

// Deactivation hook
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

// Enqueue optional styles (on frontend)
add_action('wp_enqueue_scripts', function() {
    if (wcfm_vendor_link_check_dependencies()) {
        wp_add_inline_style('woocommerce', '
            .vendor-link {
                color: #0073aa;
                text-decoration: none;
                font-weight: bold;
            }
            .vendor-link:hover {
                text-decoration: underline;
                color: #005a87;
            }
            .vendor-link-error {
                color: #d63638;
                font-style: italic;
            }
        ');
    }
});

// Custom Shortcode: Linked Vendor Name for WCFM
function custom_vendor_link_shortcode($atts) {
    // Dependencies check
    if (!wcfm_vendor_link_check_dependencies()) {
        return '<span class="vendor-link-error">WCFM not active</span>';
    }

    // Shortcode attributes (optional vendor ID)
    $atts = shortcode_atts(array(
        'id' => '', // Vendor ID (auto-detects if empty)
    ), $atts);

    // Get vendor ID (auto-detect from product or current vendor)
    $vendor_id = $atts['id'];
    if (empty($vendor_id)) {
        global $product;
        if ($product && function_exists('wcfm_get_vendor_id_by_post')) {
            $vendor_id = wcfm_get_vendor_id_by_post($product->get_id());
        } elseif (function_exists('wcfm_is_store_page') && wcfm_is_store_page()) {
            $vendor_id = apply_filters('wcfm_current_vendor_id', get_current_user_id());
        }
    }

    if (empty($vendor_id) || !get_user_by('id', $vendor_id)) {
        return '<span class="vendor-link-error"></span>'; // Fallback
    }

    // Fetch store name (prefer WCFM function, fallback to user meta)
    $store_name = '';
    if (function_exists('wcfmmp_get_store_name')) {
        $store_name = wcfmmp_get_store_name($vendor_id);
    }
    if (empty($store_name)) {
        $store_name = get_user_meta($vendor_id, 'store_name', true);
    }
    if (empty($store_name)) {
        $store_name = get_user_by('id', $vendor_id)->display_name; // Final fallback
    }

    // Fetch store URL (prefer WCFM function, fallback to meta-based URL)
    $store_url = '';
    if (function_exists('wcfmmp_get_store_url')) {
        $store_url = wcfmmp_get_store_url($vendor_id);
    }
    if (empty($store_url)) {
        $store_slug = get_user_meta($vendor_id, 'store_slug', true);
        if (!empty($store_slug)) {
            $store_url = home_url('/store/' . $store_slug . '/');
        } else {
            $store_url = home_url('/'); // Ultimate fallback
        }
    }

    // Build the link
    $link_class = 'vendor-link'; // Optional CSS class for styling
    $output = sprintf(
        '<a href="%s" class="%s" title="Visit %s Store">%s</a>',
        esc_url($store_url),
        esc_attr($link_class),
        esc_attr($store_name),
        esc_html($store_name)
    );

    return $output;
}
add_shortcode('vendor_link', 'custom_vendor_link_shortcode');