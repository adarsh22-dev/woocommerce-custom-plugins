<?php
/**
 * Admin Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class Exchange_Pro_Dashboard {
    
    private static $instance = null;
    private $db;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db = Exchange_Pro_Database::get_instance();

        // Phase 2: Exchange status management
        add_action('wp_ajax_exchange_pro_update_exchange_status', array($this, 'ajax_update_exchange_status'));
    }

    public function ajax_update_exchange_status() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'exchange-pro')));
        }
        $exchange_id = isset($_POST['exchange_id']) ? intval($_POST['exchange_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $allowed = array('pending', 'verified', 'cancelled', 'rejected');
        if (!$exchange_id || !in_array($status, $allowed, true)) {
            wp_send_json_error(array('message' => __('Invalid data', 'exchange-pro')));
        }
        $old = $this->db->get_exchange_device($exchange_id);
        $ok = $this->db->update_exchange_device_status($exchange_id, $status);
        if ($ok !== false) {
            $this->db->insert_exchange_log(array(
                'exchange_id' => $exchange_id,
                'order_id' => $old ? $old->order_id : null,
                'action' => 'exchange_status_update',
                'old_value' => maybe_serialize(array('status' => $old ? $old->status : null)),
                'new_value' => maybe_serialize(array('status' => $status)),
                'admin_user' => get_current_user_id()
            ));
            wp_send_json_success(array('status' => $status));
        }
        wp_send_json_error(array('message' => __('Failed to update status', 'exchange-pro')));
    }
    
    public function render() {
        $all_exchanges = $this->db->get_exchange_devices();
        $total_exchanges = is_array($all_exchanges) ? count($all_exchanges) : 0;

        $categories = $this->db->get_categories();
        $active_categories = is_array($categories) ? count($categories) : 0;

        // Status counts + totals
        $pending = 0; $verified = 0; $cancelled = 0;
        $total_value = 0;
        if (is_array($all_exchanges)) {
            foreach ($all_exchanges as $ex) {
                $st = isset($ex->status) ? $ex->status : 'pending';
                if ($st === 'verified') $verified++;
                elseif ($st === 'cancelled') $cancelled++;
                else $pending++;
                $total_value += floatval($ex->exchange_value);
            }
        }

        $recent_exchanges = is_array($all_exchanges) ? array_slice($all_exchanges, 0, 10) : array();
        
        ?>
        <div class="wrap exchange-pro-admin">
            <h1 class="wp-heading-inline"><?php _e('CISAI AWS Product Exchange Pro', 'exchange-pro'); ?></h1>
            
            <div class="exchange-pro-dashboard" style="margin-top: 24px;">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card shadow-sm" style="border:0; border-radius:14px;">
                            <div class="card-body" style="padding:18px;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted" style="font-size:12px; font-weight:600;"><?php _e('Total Exchanges', 'exchange-pro'); ?></div>
                                        <div style="font-size:30px; font-weight:800; line-height:1.1;"><?php echo intval($total_exchanges); ?></div>
                                    </div>
                                    <span class="dashicons dashicons-update" style="font-size:32px; width:32px; height:32px; opacity:.45;"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm" style="border:0; border-radius:14px;">
                            <div class="card-body" style="padding:18px;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted" style="font-size:12px; font-weight:600;"><?php _e('Active Categories', 'exchange-pro'); ?></div>
                                        <div style="font-size:30px; font-weight:800; line-height:1.1;"><?php echo intval($active_categories); ?></div>
                                    </div>
                                    <span class="dashicons dashicons-screenoptions" style="font-size:32px; width:32px; height:32px; opacity:.45;"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm" style="border:0; border-radius:14px;">
                            <div class="card-body" style="padding:18px;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted" style="font-size:12px; font-weight:600;"><?php _e('Pending / Verified', 'exchange-pro'); ?></div>
                                        <div style="font-size:22px; font-weight:800;"><?php echo intval($pending); ?> <span class="text-muted" style="font-weight:700;">/</span> <?php echo intval($verified); ?></div>
                                    </div>
                                    <span class="dashicons dashicons-yes" style="font-size:32px; width:32px; height:32px; opacity:.45;"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm" style="border:0; border-radius:14px;">
                            <div class="card-body" style="padding:18px;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted" style="font-size:12px; font-weight:600;"><?php _e('Total Exchange Value', 'exchange-pro'); ?></div>
                                        <div style="font-size:22px; font-weight:800;"><?php echo wc_price($total_value); ?></div>
                                    </div>
                                    <span class="dashicons dashicons-chart-area" style="font-size:32px; width:32px; height:32px; opacity:.45;"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><?php _e('Recent Exchanges', 'exchange-pro'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_exchanges)): ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th><?php _e('Order ID', 'exchange-pro'); ?></th>
                                        <th><?php _e('Device', 'exchange-pro'); ?></th>
                                        <th><?php _e('Status', 'exchange-pro'); ?></th>
                                        <th><?php _e('Exchange Value', 'exchange-pro'); ?></th>
                                        <th><?php _e('Date', 'exchange-pro'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_exchanges as $exchange): ?>
                                        <tr>
                                            <td>
                                                <?php
                                                $order_id = intval($exchange->order_id);
                                                $order_link = $order_id ? admin_url('post.php?post=' . $order_id . '&action=edit') : '';
                                                echo $order_link
                                                    ? '<a href="' . esc_url($order_link) . '">#' . $order_id . '</a>'
                                                    : '#' . $order_id;
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $data = json_decode($exchange->device_data, true);
                                                $device_label = 'Device #' . intval($exchange->id);
                                                if (is_array($data)) {
                                                    $bits = array();
                                                    if (!empty($data['brand_id'])) {
                                                        $b = $this->db->get_brand(intval($data['brand_id']));
                                                        if ($b) $bits[] = $b->name;
                                                    }
                                                    if (!empty($data['model_id'])) {
                                                        $m = $this->db->get_model(intval($data['model_id']));
                                                        if ($m) $bits[] = $m->name;
                                                    }
                                                    if (!empty($data['variant_id'])) {
                                                        $v = $this->db->get_variant(intval($data['variant_id']));
                                                        if ($v) $bits[] = $v->name;
                                                    }
                                                    if ($bits) $device_label = implode(' ', $bits);
                                                }
                                                echo esc_html($device_label);
                                                ?>
                                            </td>
                                            <td>
                                                <?php $st = $exchange->status ?: 'pending'; ?>
                                                <div class="d-flex align-items-center gap-2">
                                                    <select class="form-select form-select-sm exchange-status-select" data-exchange-id="<?php echo intval($exchange->id); ?>" style="max-width: 140px;">
                                                        <option value="pending" <?php selected($st, 'pending'); ?>>Pending</option>
                                                        <option value="verified" <?php selected($st, 'verified'); ?>>Verified</option>
                                                        <option value="cancelled" <?php selected($st, 'cancelled'); ?>>Cancelled</option>
                                                        <option value="rejected" <?php selected($st, 'rejected'); ?>>Rejected</option>
                                                    </select>
                                                    <button type="button" class="button button-small exchange-status-save" data-exchange-id="<?php echo intval($exchange->id); ?>">Save</button>
                                                </div>
                                            </td>
                                            <td><?php echo wc_price(floatval($exchange->exchange_value)); ?></td>
                                            <td><?php echo esc_html(date('Y-m-d H:i', strtotime($exchange->created_at))); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p><?php _e('No exchanges yet.', 'exchange-pro'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <script>
        (function($){
            const nonce = <?php echo wp_json_encode(wp_create_nonce('exchange_pro_admin_nonce')); ?>;
            $(document).on('click', '.exchange-status-save', function(){
                const exId = $(this).data('exchange-id');
                const status = $('.exchange-status-select[data-exchange-id="'+exId+'"]').val();
                const $btn = $(this);
                $btn.prop('disabled', true).text('Saving...');
                $.post(ajaxurl, { action:'exchange_pro_update_exchange_status', nonce: nonce, exchange_id: exId, status: status }, function(resp){
                    if (!(resp && resp.success)) {
                        alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Error');
                    }
                }).always(function(){
                    $btn.prop('disabled', false).text('Save');
                });
            });
        })(jQuery);
        </script>
        <?php
    }
}
