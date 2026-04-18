<?php
if (!defined('ABSPATH')) exit;

class WooAI_Installer {
    
    public static function activate() {
        self::create_tables();
        self::insert_defaults();
        self::set_options();
        flush_rewrite_rules();
    }
    
    private static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Conversations
        $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wooai_conversations` (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            user_message text NOT NULL,
            ai_response text NOT NULL,
            intent varchar(50) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY created_at (created_at),
            KEY intent (intent)
        ) $charset;";
        dbDelta($sql);
        
        // Callbacks
        $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wooai_callbacks` (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            phone varchar(50) NOT NULL,
            email varchar(255) DEFAULT NULL,
            message text DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            session_id varchar(255) DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY user_id (user_id)
        ) $charset;";
        dbDelta($sql);
        
        // Policies
        $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wooai_policies` (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            summary text NOT NULL,
            url varchar(500) NOT NULL,
            page_id bigint(20) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY page_id (page_id)
        ) $charset;";
        dbDelta($sql);
        
        // Quick Actions
        $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wooai_actions` (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            label varchar(100) NOT NULL,
            icon text NOT NULL,
            action_type varchar(50) NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            is_custom tinyint(1) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset;";
        dbDelta($sql);
        
        // Product Assignments
        $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wooai_products` (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            category varchar(50) NOT NULL,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY product_cat (product_id, category)
        ) $charset;";
        dbDelta($sql);
    }
    
    private static function insert_defaults() {
        global $wpdb;
        
        // Default Policies - All 25 unique policies
        $policies_table = $wpdb->prefix . 'wooai_policies';
        
        // Only insert if table is empty
        $existing_count = $wpdb->get_var("SELECT COUNT(*) FROM $policies_table");
        
        if ($existing_count == 0) {
            $default_policies = array(
                array('title' => 'Return Policy', 'type' => 'return', 'summary' => 'You can return any item within 30 days of purchase if unused and in original packaging.'),
                array('title' => 'Shipping Policy', 'type' => 'shipping', 'summary' => 'Free shipping on orders over $50. Standard delivery takes 3-5 business days.'),
                array('title' => 'Privacy Policy', 'type' => 'privacy', 'summary' => 'We protect your personal information and do not share it with third parties.'),
                array('title' => 'Terms & Conditions', 'type' => 'terms', 'summary' => 'By using our website, you agree to our terms of service and conditions.'),
                array('title' => 'Refund Policy', 'type' => 'refund', 'summary' => 'Full refund available within 14 days if product is defective or damaged.'),
                array('title' => 'Warranty Policy', 'type' => 'warranty', 'summary' => 'All products come with a 1-year manufacturer warranty covering defects.'),
                array('title' => 'Cookie Policy', 'type' => 'cookie', 'summary' => 'We use cookies to improve your browsing experience and analyze site traffic.')
            );
            
            // Insert each policy
            foreach ($default_policies as $policy) {
                $wpdb->insert($policies_table, array(
                    'title' => $policy['title'],
                    'type' => $policy['type'],
                    'summary' => $policy['summary'],
                    'url' => '',
                    'is_active' => 0,
                    'created_at' => current_time('mysql')
                ));
            }
        }
        
        // Default Quick Actions
        $actions = $wpdb->prefix . 'wooai_actions';
        if ($wpdb->get_var("SELECT COUNT(*) FROM $actions") == 0) {
            $default_actions = array(
                array('label' => 'Bestselling', 'icon' => '⭐', 'action_type' => 'bestselling', 'sort_order' => 1),
                array('label' => 'Recommended', 'icon' => '👍', 'action_type' => 'recommended', 'sort_order' => 2),
                array('label' => 'New Arrivals', 'icon' => '⚡', 'action_type' => 'new_arrivals', 'sort_order' => 3),
                array('label' => 'Offers', 'icon' => '🏷️', 'action_type' => 'offers', 'sort_order' => 4),
                array('label' => 'Search Product', 'icon' => '🔍', 'action_type' => 'search', 'sort_order' => 5),
                array('label' => 'Policies', 'icon' => '📋', 'action_type' => 'policies', 'sort_order' => 6),
                array('label' => 'My Account', 'icon' => '👤', 'action_type' => 'account', 'sort_order' => 7),
                array('label' => 'Order Tracking', 'icon' => '🚚', 'action_type' => 'ordertracking', 'sort_order' => 8),
                array('label' => 'Callback', 'icon' => '📞', 'action_type' => 'callback', 'sort_order' => 9),
            );
            foreach ($default_actions as $action) {
                $wpdb->insert($actions, $action);
            }
        }
    }
    
    private static function set_options() {
        add_option('wooai_enabled', '1');
        add_option('wooai_greeting', 'Hello! How can I help you today?');
        add_option('wooai_color', '#7C3AED');
        add_option('wooai_ai_provider', 'gemini');
        add_option('wooai_gemini_key', '');
        add_option('wooai_openai_key', '');
        add_option('wooai_claude_key', '');
    }
}
