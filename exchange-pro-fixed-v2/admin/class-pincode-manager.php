<?php
if (!defined('ABSPATH')) exit;

class Exchange_Pro_Pincode_Manager {
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

        // AJAX handlers
        add_action('wp_ajax_exchange_pro_add_pincode', array($this, 'ajax_add_pincode'));
        add_action('wp_ajax_exchange_pro_edit_pincode', array($this, 'ajax_edit_pincode'));
        add_action('wp_ajax_exchange_pro_delete_pincode', array($this, 'ajax_delete_pincode'));
    }
    
    public function render() {
        $pincodes = $this->db->get_pincodes();
        ?>
        <div class="wrap exchange-pro-admin">
            <h1><?php _e('Pincode Management', 'exchange-pro'); ?></h1>
            
            <div class="card mt-4">
                <div class="card-body">
                    <button class="btn btn-primary mb-3" id="exchange-pro-add-pincode-btn">
                        <i class="fas fa-plus"></i> <?php _e('Add Pincode', 'exchange-pro'); ?>
                    </button>
                    
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><?php _e('Pincode', 'exchange-pro'); ?></th>
                                <th><?php _e('City', 'exchange-pro'); ?></th>
                                <th><?php _e('State', 'exchange-pro'); ?></th>
                                <th><?php _e('Serviceable', 'exchange-pro'); ?></th>
                                <th><?php _e('Actions', 'exchange-pro'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pincodes as $pin): ?>
                                <tr>
                                    <td><?php echo esc_html($pin->pincode); ?></td>
                                    <td><?php echo esc_html($pin->city); ?></td>
                                    <td><?php echo esc_html($pin->state); ?></td>
                                    <td><?php echo $pin->serviceable ? __('Yes', 'exchange-pro') : __('No', 'exchange-pro'); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-secondary exchange-pro-edit-pincode"
                                                data-id="<?php echo esc_attr($pin->id); ?>"
                                                data-pincode="<?php echo esc_attr($pin->pincode); ?>"
                                                data-city="<?php echo esc_attr($pin->city); ?>"
                                                data-state="<?php echo esc_attr($pin->state); ?>"
                                                data-serviceable="<?php echo esc_attr($pin->serviceable); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger exchange-pro-delete-pincode"
                                                data-id="<?php echo esc_attr($pin->id); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add/Edit Pincode Modal -->
        <div class="modal fade" id="exchangeProPincodeModal" tabindex="-1" style="background-color: #00000000;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exchangeProPincodeModalTitle"><?php _e('Add Pincode', 'exchange-pro'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding: 5% !important;">
                        <input type="hidden" id="exchange-pro-pin-id" value="">

                        <div class="mb-3">
                            <label class="form-label"><?php _e('Pincode', 'exchange-pro'); ?> *</label>
                            <input type="text" class="form-control" id="exchange-pro-pin-code" maxlength="10" placeholder="e.g., 682001">
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php _e('City', 'exchange-pro'); ?></label>
                            <input type="text" class="form-control" id="exchange-pro-pin-city" placeholder="e.g., Ernakulam">
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php _e('State', 'exchange-pro'); ?></label>
                            <input type="text" class="form-control" id="exchange-pro-pin-state" placeholder="e.g., Kerala">
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php _e('Serviceable', 'exchange-pro'); ?></label>
                            <select class="form-control" id="exchange-pro-pin-serviceable">
                                <option value="1"><?php _e('Yes', 'exchange-pro'); ?></option>
                                <option value="0"><?php _e('No', 'exchange-pro'); ?></option>
                            </select>
                        </div>

                        <div class="alert alert-warning" id="exchange-pro-pin-error" style="display:none;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel', 'exchange-pro'); ?></button>
                        <button type="button" class="btn btn-primary" id="exchange-pro-save-pincode"><?php _e('Save', 'exchange-pro'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($){
            function openPincodeModal(mode, data){
                $('#exchange-pro-pin-error').hide().text('');
                if (mode === 'add') {
                    $('#exchangeProPincodeModalTitle').text('Add Pincode');
                    $('#exchange-pro-pin-id').val('');
                    $('#exchange-pro-pin-code').val('');
                    $('#exchange-pro-pin-city').val('');
                    $('#exchange-pro-pin-state').val('');
                    $('#exchange-pro-pin-serviceable').val('1');
                } else {
                    $('#exchangeProPincodeModalTitle').text('Edit Pincode');
                    $('#exchange-pro-pin-id').val(data.id);
                    $('#exchange-pro-pin-code').val(data.pincode);
                    $('#exchange-pro-pin-city').val(data.city);
                    $('#exchange-pro-pin-state').val(data.state);
                    $('#exchange-pro-pin-serviceable').val(String(data.serviceable));
                }
                $('#exchangeProPincodeModal').modal('show');
            }

            $('#exchange-pro-add-pincode-btn').on('click', function(){
                openPincodeModal('add', {});
            });

            $(document).on('click', '.exchange-pro-edit-pincode', function(){
                openPincodeModal('edit', {
                    id: $(this).data('id'),
                    pincode: $(this).data('pincode'),
                    city: $(this).data('city') || '',
                    state: $(this).data('state') || '',
                    serviceable: $(this).data('serviceable') ? 1 : 0
                });
            });

            $(document).on('click', '.exchange-pro-delete-pincode', function(){
                if (!confirm(exchangeProAdmin.strings.confirm_delete || 'Delete this pincode?')) return;
                var id = $(this).data('id');
                $.post(exchangeProAdmin.ajax_url, {
                    action: 'exchange_pro_delete_pincode',
                    nonce: exchangeProAdmin.nonce,
                    id: id
                }, function(resp){
                    if (resp && resp.success) {
                        location.reload();
                    } else {
                        alert((resp && resp.data && resp.data.message) ? resp.data.message : exchangeProAdmin.strings.error);
                    }
                });
            });

            $('#exchange-pro-save-pincode').on('click', function(){
                var id = $('#exchange-pro-pin-id').val();
                var pincode = String($('#exchange-pro-pin-code').val()).trim();
                var city = String($('#exchange-pro-pin-city').val()).trim();
                var state = String($('#exchange-pro-pin-state').val()).trim();
                var serviceable = parseInt($('#exchange-pro-pin-serviceable').val(), 10);

                if (!pincode || pincode.length < 4) {
                    $('#exchange-pro-pin-error').show().text('Enter a valid pincode');
                    return;
                }

                var action = id ? 'exchange_pro_edit_pincode' : 'exchange_pro_add_pincode';

                $('#exchange-pro-save-pincode').prop('disabled', true).text('Saving...');
                $.post(exchangeProAdmin.ajax_url, {
                    action: action,
                    nonce: exchangeProAdmin.nonce,
                    id: id,
                    pincode: pincode,
                    city: city,
                    state: state,
                    serviceable: serviceable
                }, function(resp){
                    if (resp && resp.success) {
                        location.reload();
                    } else {
                        $('#exchange-pro-pin-error').show().text((resp && resp.data && resp.data.message) ? resp.data.message : exchangeProAdmin.strings.error);
                    }
                }).always(function(){
                    $('#exchange-pro-save-pincode').prop('disabled', false).text('Save');
                });
            });
        });
        </script>
        <?php
    }

    public function ajax_add_pincode() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'exchange-pro')));
        }

        $pincode = isset($_POST['pincode']) ? sanitize_text_field($_POST['pincode']) : '';
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
        $serviceable = isset($_POST['serviceable']) ? intval($_POST['serviceable']) : 1;

        if (empty($pincode)) {
            wp_send_json_error(array('message' => __('Pincode is required', 'exchange-pro')));
        }

        $ok = $this->db->insert_pincode(array(
            'pincode' => $pincode,
            'city' => $city,
            'state' => $state,
            'serviceable' => $serviceable ? 1 : 0,
        ));

        if ($ok) {
            wp_send_json_success();
        }
        wp_send_json_error(array('message' => __('Failed to save pincode (maybe duplicate?)', 'exchange-pro')));
    }

    public function ajax_edit_pincode() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'exchange-pro')));
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $pincode = isset($_POST['pincode']) ? sanitize_text_field($_POST['pincode']) : '';
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
        $serviceable = isset($_POST['serviceable']) ? intval($_POST['serviceable']) : 1;

        if (!$id || empty($pincode)) {
            wp_send_json_error(array('message' => __('Invalid request', 'exchange-pro')));
        }

        $ok = $this->db->update_pincode($id, array(
            'pincode' => $pincode,
            'city' => $city,
            'state' => $state,
            'serviceable' => $serviceable ? 1 : 0,
        ));

        if ($ok !== false) {
            wp_send_json_success();
        }
        wp_send_json_error(array('message' => __('Failed to update pincode', 'exchange-pro')));
    }

    public function ajax_delete_pincode() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'exchange-pro')));
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) {
            wp_send_json_error(array('message' => __('Invalid pincode', 'exchange-pro')));
        }

        $ok = $this->db->delete_pincode($id);
        if ($ok) {
            wp_send_json_success();
        }
        wp_send_json_error(array('message' => __('Failed to delete pincode', 'exchange-pro')));
    }
}
