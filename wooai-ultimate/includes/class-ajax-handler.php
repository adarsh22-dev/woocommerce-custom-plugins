<?php
if (!defined('ABSPATH')) exit;

class WooAI_Ajax_Handler {
    
    public static function init() {
        // Frontend endpoints
        add_action('wp_ajax_wooai_send_message', array(__CLASS__, 'send_message'));
        add_action('wp_ajax_nopriv_wooai_send_message', array(__CLASS__, 'send_message'));
        
        add_action('wp_ajax_wooai_get_products', array(__CLASS__, 'get_products'));
        add_action('wp_ajax_nopriv_wooai_get_products', array(__CLASS__, 'get_products'));
        
        add_action('wp_ajax_wooai_submit_callback', array(__CLASS__, 'submit_callback'));
        add_action('wp_ajax_nopriv_wooai_submit_callback', array(__CLASS__, 'submit_callback'));
        
        add_action('wp_ajax_wooai_get_policy', array(__CLASS__, 'get_policy'));
        add_action('wp_ajax_nopriv_wooai_get_policy', array(__CLASS__, 'get_policy'));
        
        add_action('wp_ajax_wooai_search_products', array(__CLASS__, 'search_products'));
        add_action('wp_ajax_nopriv_wooai_search_products', array(__CLASS__, 'search_products'));
        
        add_action('wp_ajax_wooai_get_all_policies', array(__CLASS__, 'get_all_policies'));
        add_action('wp_ajax_nopriv_wooai_get_all_policies', array(__CLASS__, 'get_all_policies'));
        
        // Admin endpoints
        add_action('wp_ajax_wooai_get_stats', array(__CLASS__, 'get_stats'));
        add_action('wp_ajax_wooai_get_callbacks', array(__CLASS__, 'get_callbacks'));
        add_action('wp_ajax_wooai_update_callback', array(__CLASS__, 'update_callback'));
        add_action('wp_ajax_wooai_get_products_list', array(__CLASS__, 'get_products_list'));
        add_action('wp_ajax_wooai_save_assignments', array(__CLASS__, 'save_assignments'));
        add_action('wp_ajax_wooai_get_assignments', array(__CLASS__, 'get_assignments'));
        add_action('wp_ajax_wooai_save_settings', array(__CLASS__, 'save_settings'));
        add_action('wp_ajax_wooai_toggle_action', array(__CLASS__, 'toggle_action'));
        add_action('wp_ajax_wooai_get_analytics', array(__CLASS__, 'get_analytics'));
        add_action('wp_ajax_wooai_get_conversation_logs', array(__CLASS__, 'get_conversation_logs'));
        add_action('wp_ajax_wooai_get_wc_categories', array(__CLASS__, 'get_wc_categories'));
        add_action('wp_ajax_wooai_get_wc_tags', array(__CLASS__, 'get_wc_tags'));
        
        add_action('wp_ajax_wooai_get_account_tabs', array(__CLASS__, 'get_account_tabs'));
        add_action('wp_ajax_nopriv_wooai_get_account_tabs', array(__CLASS__, 'get_account_tabs'));
        
        add_action('wp_ajax_wooai_get_user_orders', array(__CLASS__, 'get_user_orders'));
        add_action('wp_ajax_nopriv_wooai_get_user_orders', array(__CLASS__, 'get_user_orders'));
        
        add_action('wp_ajax_wooai_get_user_addresses', array(__CLASS__, 'get_user_addresses'));
        add_action('wp_ajax_nopriv_wooai_get_user_addresses', array(__CLASS__, 'get_user_addresses'));
        
        add_action('wp_ajax_wooai_get_page_url', array(__CLASS__, 'get_page_url'));
        
        // Order Tracking Shortcode
        add_action('wp_ajax_wooai_render_tracking_shortcode', array(__CLASS__, 'render_tracking_shortcode'));
        add_action('wp_ajax_nopriv_wooai_render_tracking_shortcode', array(__CLASS__, 'render_tracking_shortcode'));
    }
    
    public static function send_message() {
        check_ajax_referer('wooai_nonce', 'nonce');
        
        $message = sanitize_text_field($_POST['message']);
        $session_id = sanitize_text_field($_POST['session_id']);
        $geolocation = isset($_POST['geolocation']) ? $_POST['geolocation'] : null;
        
        $ai_engine = new WooAI_AI_Engine();
        $response = $ai_engine->process($message, $session_id);
        
        // Enhanced logging with geolocation
        global $wpdb;
        $log_data = array(
            'session_id' => $session_id,
            'user_id' => get_current_user_id(),
            'user_message' => $message,
            'ai_response' => wp_json_encode($response),
            'intent' => $response['intent'] ?? 'general'
        );
        
        // Add geolocation if available
        if ($geolocation && isset($geolocation['lat']) && isset($geolocation['lng'])) {
            $log_data['user_agent'] = json_encode(array(
                'lat' => floatval($geolocation['lat']),
                'lng' => floatval($geolocation['lng']),
                'ip' => self::get_client_ip(),
                'browser' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : ''
            ));
        }
        
        $wpdb->insert($wpdb->prefix . 'wooai_conversations', $log_data);
        
        wp_send_json_success($response);
    }
    
    public static function get_products() {
        check_ajax_referer('wooai_nonce', 'nonce');
        
        $category = sanitize_text_field($_POST['category']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'wooai_products';
        
        $product_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT product_id FROM $table WHERE category = %s ORDER BY sort_order",
            $category
        ));
        
        $products = array();
        foreach ($product_ids as $id) {
            $product = wc_get_product($id);
            if ($product && $product->is_visible()) {
                $products[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => $product->get_price_html(),
                    'image' => wp_get_attachment_url($product->get_image_id()),
                    'url' => $product->get_permalink()
                );
            }
        }
        
        wp_send_json_success(array('products' => $products));
    }
    
    public static function submit_callback() {
        check_ajax_referer('wooai_nonce', 'nonce');
        
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'wooai_callbacks', array(
            'name' => sanitize_text_field($_POST['name']),
            'phone' => sanitize_text_field($_POST['phone']),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'message' => sanitize_textarea_field($_POST['message'] ?? ''),
            'session_id' => sanitize_text_field($_POST['session_id'])
        ));
        
        wp_send_json_success(array('message' => 'Callback request submitted successfully!'));
    }
    
    public static function get_policy() {
        check_ajax_referer('wooai_nonce', 'nonce');
        
        global $wpdb;
        $policy = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wooai_policies WHERE type = %s AND is_active = 1 LIMIT 1",
            sanitize_text_field($_POST['type'])
        ), ARRAY_A);
        
        wp_send_json_success(array('policy' => $policy));
    }
    
    public static function search_products() {
        check_ajax_referer('wooai_nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query']);
        $session_id = sanitize_text_field($_POST['session_id']);
        
        // Log search
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'wooai_conversations', array(
            'session_id' => $session_id,
            'user_id' => get_current_user_id(),
            'user_message' => 'Product search: ' . $query,
            'ai_response' => '',
            'intent' => 'product_search'
        ));
        
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 10,
            's' => $query,
            'post_status' => 'publish'
        );
        
        $products_query = new WP_Query($args);
        $products = array();
        
        if ($products_query->have_posts()) {
            while ($products_query->have_posts()) {
                $products_query->the_post();
                $product = wc_get_product(get_the_ID());
                
                if ($product && $product->is_visible()) {
                    $products[] = array(
                        'id' => $product->get_id(),
                        'name' => $product->get_name(),
                        'price' => $product->get_price_html(),
                        'image' => wp_get_attachment_url($product->get_image_id()),
                        'url' => $product->get_permalink()
                    );
                }
            }
        }
        
        wp_reset_postdata();
        
        wp_send_json_success(array('products' => $products));
    }
    
    public static function get_all_policies() {
        check_ajax_referer('wooai_nonce', 'nonce');
        
        global $wpdb;
        $policies = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}wooai_policies WHERE is_active = 1 ORDER BY created_at DESC",
            ARRAY_A
        );
        
        wp_send_json_success(array('policies' => $policies));
    }
    
    public static function get_stats() {
        check_ajax_referer('wooai_admin_nonce', 'nonce');
        
        global $wpdb;
        
        // Get stats with fallbacks
        $conversations = $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM {$wpdb->prefix}wooai_conversations") ?: 0;
        $callbacks = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wooai_callbacks WHERE status = 'pending'") ?: 0;
        $products = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wooai_products") ?: 0;
        $active_users = $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM {$wpdb->prefix}wooai_conversations WHERE DATE(created_at) = CURDATE()") ?: 0;
        
        // Get 7-day trend
        $trend = $wpdb->get_results(
            "SELECT DATE(created_at) as date, COUNT(DISTINCT session_id) as count 
            FROM {$wpdb->prefix}wooai_conversations 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
            GROUP BY DATE(created_at) 
            ORDER BY date",
            ARRAY_A
        );
        
        // Ensure we have 7 days of data (fill missing days with 0)
        $filled_trend = array();
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $found = false;
            foreach ($trend as $day) {
                if ($day['date'] === $date) {
                    $filled_trend[] = $day;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $filled_trend[] = array('date' => $date, 'count' => '0');
            }
        }
        
        $stats = array(
            'conversations' => intval($conversations),
            'callbacks' => intval($callbacks),
            'products' => intval($products),
            'active_users' => intval($active_users),
            'trend' => $filled_trend
        );
        
        wp_send_json_success($stats);
    }
    
    public static function get_callbacks() {
        check_ajax_referer('wooai_admin_nonce', 'nonce');
        
        global $wpdb;
        $callbacks = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}wooai_callbacks ORDER BY created_at DESC LIMIT 100",
            ARRAY_A
        );
        
        wp_send_json_success(array('callbacks' => $callbacks));
    }
    
    public static function update_callback() {
        check_ajax_referer('wooai_admin_nonce', 'nonce');
        
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'wooai_callbacks',
            array('status' => sanitize_text_field($_POST['status'])),
            array('id' => intval($_POST['id']))
        );
        
        wp_send_json_success();
    }
    
    public static function get_products_list() {
        check_ajax_referer('wooai_admin_nonce', 'nonce');
        
        $products = wc_get_products(array(
            'limit' => 100,
            'status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $result = array();
        foreach ($products as $product) {
            $result[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price_html(),
                'image' => wp_get_attachment_url($product->get_image_id()),
                'category' => wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'))[0] ?? ''
            );
        }
        
        wp_send_json_success(array('products' => $result));
    }
    
    public static function get_assignments() {
        check_ajax_referer('wooai_admin_nonce', 'nonce');
        
        $category = sanitize_text_field($_POST['category']);
        
        global $wpdb;
        $assignments = $wpdb->get_results($wpdb->prepare(
            "SELECT product_id FROM {$wpdb->prefix}wooai_products WHERE category = %s ORDER BY sort_order",
            $category
        ), ARRAY_A);
        
        $product_ids = array_column($assignments, 'product_id');
        
        // Get full product data
        $products_data = array();
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $products_data[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => $product->get_price_html(),
                    'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
                );
            }
        }
        
        wp_send_json_success(array(
            'assignments' => $product_ids,
            'products_data' => $products_data
        ));
    }
    
    public static function save_assignments() {
        check_ajax_referer('wooai_admin_nonce', 'nonce');
        
        $category = sanitize_text_field($_POST['category']);
        $product_ids = array_map('intval', $_POST['products']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'wooai_products';
        
        // Delete existing
        $wpdb->delete($table, array('category' => $category));
        
        // Insert new
        foreach ($product_ids as $index => $product_id) {
            $wpdb->insert($table, array(
                'product_id' => $product_id,
                'category' => $category,
                'sort_order' => $index
            ));
        }
        
        wp_send_json_success(array('message' => 'Assignments saved!'));
    }
    
    public static function save_settings() {
        check_ajax_referer('wooai_admin_nonce', 'nonce');
        
        update_option('wooai_greeting', sanitize_textarea_field($_POST['greeting']));
        update_option('wooai_color', sanitize_hex_color($_POST['color']));
        update_option('wooai_ai_provider', sanitize_text_field($_POST['provider']));
        update_option('wooai_gemini_key', sanitize_text_field($_POST['gemini_key']));
        update_option('wooai_openai_key', sanitize_text_field($_POST['openai_key']));
        update_option('wooai_claude_key', sanitize_text_field($_POST['claude_key']));
        
        wp_send_json_success(array('message' => 'Settings saved!'));
    }
    
    public static function toggle_action() {
        check_ajax_referer('wooai_admin_nonce', 'nonce');
        
        global $wpdb;
        $action = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wooai_actions WHERE id = %d",
            intval($_POST['id'])
        ));
        
        $wpdb->update(
            $wpdb->prefix . 'wooai_actions',
            array('is_active' => !$action->is_active),
            array('id' => $action->id)
        );
        
        wp_send_json_success();
    }
    
    /**
     * Get analytics data
     */
    public static function get_analytics() {
        check_admin_referer('wooai_admin_nonce', 'nonce');
        
        global $wpdb;
        $table = $wpdb->prefix . 'wooai_conversations';
        
        // Product searches
        $product_searches = $wpdb->get_results(
            "SELECT user_message, COUNT(*) as count 
            FROM $table 
            WHERE intent = 'product_search' 
            GROUP BY user_message 
            ORDER BY count DESC 
            LIMIT 10",
            ARRAY_A
        );
        
        // Popular intents
        $intents = $wpdb->get_results(
            "SELECT intent, COUNT(*) as count 
            FROM $table 
            WHERE intent IS NOT NULL 
            GROUP BY intent 
            ORDER BY count DESC",
            ARRAY_A
        );
        
        // Sessions by location (from user_agent JSON)
        $sessions = $wpdb->get_results(
            "SELECT session_id, user_agent, created_at 
            FROM $table 
            WHERE user_agent IS NOT NULL 
            AND user_agent != '' 
            GROUP BY session_id 
            ORDER BY created_at DESC 
            LIMIT 50",
            ARRAY_A
        );
        
        // Hourly activity
        $hourly = $wpdb->get_results(
            "SELECT HOUR(created_at) as hour, COUNT(*) as count 
            FROM $table 
            WHERE DATE(created_at) = CURDATE() 
            GROUP BY hour 
            ORDER BY hour",
            ARRAY_A
        );
        
        wp_send_json_success(array(
            'product_searches' => $product_searches,
            'intents' => $intents,
            'sessions' => $sessions,
            'hourly_activity' => $hourly
        ));
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return sanitize_text_field($ip);
    }
    
    /**
     * Get WooCommerce categories
     */
    public static function get_wc_categories() {
        check_admin_referer('wooai_admin_nonce', 'nonce');
        
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false
        ));
        
        $result = array();
        foreach ($categories as $cat) {
            $result[] = array(
                'term_id' => $cat->term_id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'count' => $cat->count
            );
        }
        
        wp_send_json_success(array('categories' => $result));
    }
    
    /**
     * Get WooCommerce tags
     */
    public static function get_wc_tags() {
        check_admin_referer('wooai_admin_nonce', 'nonce');
        
        $tags = get_terms(array(
            'taxonomy' => 'product_tag',
            'hide_empty' => false
        ));
        
        $result = array();
        foreach ($tags as $tag) {
            $result[] = array(
                'term_id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'count' => $tag->count
            );
        }
        
        wp_send_json_success(array('tags' => $result));
    }
    
    /**
     * Get account tabs data
     */
    public static function get_account_tabs() {
        check_ajax_referer('wooai_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Please log in to view your account.'));
        }
        
        $user = get_userdata($user_id);
        $customer = new WC_Customer($user_id);
        
        // Get order count
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'limit' => -1
        ));
        
        wp_send_json_success(array(
            'display_name' => $user->display_name,
            'user_email' => $user->user_email,
            'user_registered' => date('F j, Y', strtotime($user->user_registered)),
            'order_count' => count($orders)
        ));
    }
    
    /**
     * Get user orders
     */
    public static function get_user_orders() {
        check_ajax_referer('wooai_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Please log in to view orders.'));
        }
        
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $result = array();
        foreach ($orders as $order) {
            $result[] = array(
                'id' => $order->get_id(),
                'status' => $order->get_status(),
                'date' => $order->get_date_created()->date('F j, Y'),
                'total' => $order->get_formatted_order_total(),
                'item_count' => $order->get_item_count(),
                'view_url' => $order->get_view_order_url()
            );
        }
        
        wp_send_json_success(array('orders' => $result));
    }
    
    /**
     * Get user addresses
     */
    public static function get_user_addresses() {
        check_ajax_referer('wooai_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Please log in to view addresses.'));
        }
        
        $customer = new WC_Customer($user_id);
        
        $billing = array(
            $customer->get_billing_first_name() . ' ' . $customer->get_billing_last_name(),
            $customer->get_billing_address_1(),
            $customer->get_billing_address_2(),
            $customer->get_billing_city() . ', ' . $customer->get_billing_state() . ' ' . $customer->get_billing_postcode(),
            $customer->get_billing_country()
        );
        
        $shipping = array(
            $customer->get_shipping_first_name() . ' ' . $customer->get_shipping_last_name(),
            $customer->get_shipping_address_1(),
            $customer->get_shipping_address_2(),
            $customer->get_shipping_city() . ', ' . $customer->get_shipping_state() . ' ' . $customer->get_shipping_postcode(),
            $customer->get_shipping_country()
        );
        
        wp_send_json_success(array(
            'billing' => implode('<br>', array_filter($billing)),
            'shipping' => implode('<br>', array_filter($shipping))
        ));
    }
    
    public static function get_conversation_logs() {
        check_ajax_referer('wooai_admin_nonce', 'nonce');
        
        global $wpdb;
        $table = $wpdb->prefix . 'wooai_conversations';
        
        $logs = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 100", ARRAY_A);
        
        wp_send_json_success(array('logs' => $logs));
    }
    
    public static function get_page_url() {
        $page_id = intval($_POST['page_id']);
        
        if (!$page_id) {
            wp_send_json_error('Invalid page ID');
        }
        
        $url = get_permalink($page_id);
        
        if (!$url) {
            wp_send_json_error('Page not found');
        }
        
        wp_send_json_success(array('url' => $url));
    }
    
    public static function render_tracking_shortcode() {
        check_ajax_referer('wooai_nonce', 'nonce');
        
        $shortcode = sanitize_text_field($_POST['shortcode']);
        $html = do_shortcode($shortcode);
        
        if (empty($html) || $html === $shortcode) {
            wp_send_json_error(array('message' => 'Tracking form not available'));
        }
        
        wp_send_json_success(array('html' => $html));
    }
}
