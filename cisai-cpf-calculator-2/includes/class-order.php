<?php
/**
 * Order Management
 * Saves CPF data and displays in admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class CISAI_CPF_Order {
    
    private static $instance = null;
    private $calculator;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->calculator = CISAI_CPF_Calculator_Engine::get_instance();
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Save CPF data to order
        add_action('woocommerce_checkout_create_order', [$this, 'save_cpf_data'], 20, 2);
        
        // Display CPF data in admin order page
        add_action('woocommerce_admin_order_data_after_order_details', [$this, 'display_admin_cpf_data']);
        
        // Add Platform Net column to orders list
        add_filter('manage_edit-shop_order_columns', [$this, 'add_platform_net_column']);
        add_action('manage_shop_order_posts_custom_column', [$this, 'display_platform_net_column'], 20, 2);
    }
    
    /**
     * Save CPF calculation data to order
     */
    public function save_cpf_data($order, $data) {
        $breakdown = $this->calculator->get_breakdown($order->get_subtotal());
        
        // Save all calculation data as order meta
        $order->update_meta_data('_cisai_cpf_aov', $breakdown['aov']);
        $order->update_meta_data('_cisai_cpf_percentage', $breakdown['cpf_percentage']);
        $order->update_meta_data('_cisai_cpf_flat_fee', $breakdown['cpf_flat_fee']);
        $order->update_meta_data('_cisai_cpf_total', $breakdown['cpf_total']);
        $order->update_meta_data('_cisai_category_fees', $breakdown['category_fees']);
        $order->update_meta_data('_cisai_cpf_pgf', $breakdown['pgf']);
        $order->update_meta_data('_cisai_cpf_ops_cost', $breakdown['ops_cost']);
        $order->update_meta_data('_cisai_cpf_platform_net', $breakdown['platform_net']);
        $order->update_meta_data('_cisai_cpf_is_profitable', $breakdown['is_profitable'] ? 'yes' : 'no');
        $order->update_meta_data('_cisai_cpf_breakeven', $breakdown['breakeven_point']);
    }
    
    /**
     * Display CPF data in admin order page
     */
    public function display_admin_cpf_data($order) {
        $platform_net = $order->get_meta('_cisai_cpf_platform_net');
        $is_profitable = $order->get_meta('_cisai_cpf_is_profitable');
        
        if ($platform_net === '') {
            return;
        }
        
        $cpf_total = $order->get_meta('_cisai_cpf_total');
        $category_fees = $order->get_meta('_cisai_category_fees');
        $pgf = $order->get_meta('_cisai_cpf_pgf');
        $ops_cost = $order->get_meta('_cisai_cpf_ops_cost');
        
        ?>
        <div class="cisai-cpf-order-data">
            <h3>Platform Fee Analytics</h3>
            <table class="cisai-cpf-order-table">
                <tbody>
                    <tr>
                        <th>CPF (Revenue):</th>
                        <td><?php echo wc_price($cpf_total); ?></td>
                    </tr>
                    <?php if ($category_fees > 0) : ?>
                    <tr>
                        <th>Category Fees:</th>
                        <td><?php echo wc_price($category_fees); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Payment Gateway Fee:</th>
                        <td><?php echo wc_price($pgf); ?></td>
                    </tr>
                    <tr>
                        <th>Operational Cost:</th>
                        <td><?php echo wc_price($ops_cost); ?></td>
                    </tr>
                    <tr class="cisai-cpf-platform-net <?php echo $is_profitable === 'yes' ? 'profitable' : 'loss'; ?>">
                        <th><strong>Platform Net:</strong></th>
                        <td>
                            <strong><?php echo wc_price($platform_net); ?></strong>
                            <?php if ($is_profitable === 'yes') : ?>
                                <span class="cisai-cpf-status-badge cisai-cpf-profit">✓ Profit</span>
                            <?php else : ?>
                                <span class="cisai-cpf-status-badge cisai-cpf-loss">✗ Loss</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Add Platform Net column to orders list
     */
    public function add_platform_net_column($columns) {
        $new_columns = [];
        
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            
            if ($key === 'order_total') {
                $new_columns['platform_net'] = __('Platform Net', 'cisai-cpf');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Display Platform Net in orders list column
     */
    public function display_platform_net_column($column, $post_id) {
        if ($column === 'platform_net') {
            $order = wc_get_order($post_id);
            $platform_net = $order->get_meta('_cisai_cpf_platform_net');
            $is_profitable = $order->get_meta('_cisai_cpf_is_profitable');
            
            if ($platform_net !== '') {
                $class = $is_profitable === 'yes' ? 'cisai-cpf-profit' : 'cisai-cpf-loss';
                $icon = $is_profitable === 'yes' ? '✓' : '✗';
                echo '<span class="' . esc_attr($class) . '">' . esc_html($icon) . ' ' . wc_price($platform_net) . '</span>';
            } else {
                echo '<span class="cisai-cpf-na">—</span>';
            }
        }
    }
}