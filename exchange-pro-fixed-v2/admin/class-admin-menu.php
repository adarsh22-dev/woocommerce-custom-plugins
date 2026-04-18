<?php
/**
 * Admin Menu Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class Exchange_Pro_Admin_Menu {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 56);
    }
    
    public function add_admin_menu() {
        // Main menu with icon
        add_menu_page(
            __('CISAI AWS Product Exchange Pro', 'exchange-pro'),
            __('CISAI AWS Exchange', 'exchange-pro'),
            'manage_woocommerce',
            'exchange-pro',
            array($this, 'dashboard_page'),
            'dashicons-update',
            56
        );
        
        // Dashboard submenu
        add_submenu_page(
            'exchange-pro',
            __('Dashboard', 'exchange-pro'),
            __('Dashboard', 'exchange-pro'),
            'manage_woocommerce',
            'exchange-pro',
            array($this, 'dashboard_page')
        );
        
        
        // Categories (NEW - DYNAMIC)
        add_submenu_page(
            'exchange-pro',
            __('Categories', 'exchange-pro'),
            __('Categories', 'exchange-pro'),
            'manage_woocommerce',
            'exchange-pro-categories',
            array($this, 'categories_page')
        );
        
        // Devices & Pricing
        add_submenu_page(
            'exchange-pro',
            __('Devices & Pricing', 'exchange-pro'),
            __('Devices & Pricing', 'exchange-pro'),
            'manage_woocommerce',
            'exchange-pro-devices',
            array($this, 'devices_page')
        );
        
        // Pricing Matrix
        add_submenu_page(
            'exchange-pro',
            __('Pricing Matrix', 'exchange-pro'),
            __('Pricing Matrix', 'exchange-pro'),
            'manage_woocommerce',
            'exchange-pro-pricing',
            array($this, 'pricing_page')
        );
        
        // Pincodes
        add_submenu_page(
            'exchange-pro',
            __('Pincodes', 'exchange-pro'),
            __('Pincodes', 'exchange-pro'),
            'manage_woocommerce',
            'exchange-pro-pincodes',
            array($this, 'pincodes_page')
        );
        
        // Settings
        add_submenu_page(
            'exchange-pro',
            __('Settings', 'exchange-pro'),
            __('Settings', 'exchange-pro'),
            'manage_woocommerce',
            'exchange-pro-settings',
            array($this, 'settings_page')
        );

        // Logs (Phase 2)
        add_submenu_page(
            'exchange-pro',
            __('Logs', 'exchange-pro'),
            __('Logs', 'exchange-pro'),
            'manage_woocommerce',
            'exchange-pro-logs',
            array($this, 'logs_page')
        );
    }

    /**
     * Dashboard page callback (prevents fatal errors if missing).
     */
    public function dashboard_page() {
        if ( ! current_user_can('manage_woocommerce') ) {
            wp_die( esc_html__('Permission denied', 'exchange-pro') );
        }
        if ( class_exists('Exchange_Pro_Dashboard') ) {
            Exchange_Pro_Dashboard::get_instance()->render();
            return;
        }
        echo '<div class="wrap"><h1>' . esc_html__('CISAI AWS Product Exchange Pro', 'exchange-pro') . '</h1>';
        echo '<p>' . esc_html__('Dashboard component is not available.', 'exchange-pro') . '</p></div>';
    }

    
    public function categories_page() {
        $manager = Exchange_Pro_Category_Manager::get_instance();
        $manager->render();
    }
    
    public function devices_page() {
        Exchange_Pro_Devices::get_instance()->render();
    }
    
    public function pricing_page() {
        Exchange_Pro_Pricing_Manager::get_instance()->render();
    }
    
    public function pincodes_page() {
        Exchange_Pro_Pincode_Manager::get_instance()->render();
    }
    
    public function settings_page() {
        Exchange_Pro_Settings::get_instance()->render();
    }

    /**
     * Logs page (Phase 2)
     * - Shows recent audit events stored in DB + latest file log
     */
    public function logs_page() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Permission denied', 'exchange-pro'));
        }
        $db = Exchange_Pro_Database::get_instance();
        $rows = $db->get_exchange_logs(100);
        ?>
        <div class="wrap">
            <h1><?php _e('Exchange Pro Logs', 'exchange-pro'); ?></h1>

            <p class="description"><?php _e('This is an audit view of admin actions (deactivate/restore/edit) and exchange status changes.', 'exchange-pro'); ?></p>

            <div class="card" style="max-width: 1200px; padding: 20px;">
                <h2 style="margin-top:0;"><?php _e('Recent Audit Events', 'exchange-pro'); ?></h2>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Time', 'exchange-pro'); ?></th>
                            <th><?php _e('Action', 'exchange-pro'); ?></th>
                            <th><?php _e('Order', 'exchange-pro'); ?></th>
                            <th><?php _e('Exchange', 'exchange-pro'); ?></th>
                            <th><?php _e('Admin', 'exchange-pro'); ?></th>
                            <th><?php _e('Details', 'exchange-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="6"><?php _e('No logs yet.', 'exchange-pro'); ?></td></tr>
                    <?php else: foreach ($rows as $r): ?>
                        <tr>
                            <td><?php echo esc_html($r->created_at); ?></td>
                            <td><strong><?php echo esc_html($r->action); ?></strong></td>
                            <td><?php echo $r->order_id ? esc_html($r->order_id) : '—'; ?></td>
                            <td><?php echo $r->exchange_id ? esc_html($r->exchange_id) : '—'; ?></td>
                            <td><?php echo $r->admin_user ? esc_html($r->admin_user) : '—'; ?></td>
                            <td>
                                <div style="max-width:520px; white-space:normal;">
                                    <?php
                                    $old = $r->old_value ? maybe_unserialize($r->old_value) : null;
                                    $new = $r->new_value ? maybe_unserialize($r->new_value) : null;
                                    if ($old || $new) {
                                        echo '<code style="display:block;">' . esc_html(wp_json_encode(array('old'=>$old,'new'=>$new))) . '</code>';
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}
