<?php
/**
 * Plugin Name: CISAI WholesaleX Bulk Order & Sample Enquiry (WholesaleX + WCFM + Woocommerce + Wordpress)
 * Plugin URI: https://yourwebsite.com
 * Description: Separate bulk order and sample request system for WholesaleX with role-based access
 * Version: 2.1.0
 * Author: Adarsh Singh
 * Text Domain: wholesalex-bulk-enquiry
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */
if (!defined('ABSPATH')) exit;
class WholesaleX_Bulk_Sample_Enquiry {
    private $table_name;
    private $version = '2.1.0';
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wholesalex_bulk_enquiries';
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
        add_action('init', array($this, 'ensure_table_exists'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('init', array($this, 'wcfm_register_endpoints'));
        add_filter('wcfm_menus', array($this, 'add_wcfm_menus'), 10);
        add_filter('wcfm_query_vars', array($this, 'add_query_vars'), 10, 1);
        add_filter('wcfm_endpoint_title', array($this, 'endpoint_title'), 10, 2);
        add_action('wp_footer', array($this, 'wcfm_popup_content'));
        add_action('woocommerce_after_add_to_cart_form', array($this, 'render_product_page_forms'));
        add_action('wp_ajax_submit_bulk_enquiry', array($this, 'handle_bulk_submission'));
        add_action('wp_ajax_submit_sample_request', array($this, 'handle_sample_submission'));
        add_action('wp_ajax_update_enquiry_status', array($this, 'update_status'));
        add_action('wp_ajax_delete_enquiry', array($this, 'delete_enquiry'));
        add_action('wp_ajax_send_reply', array($this, 'send_reply'));
        add_action('wp_ajax_save_vendor_enabled_forms', array($this, 'save_vendor_enabled_forms'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
    }
    public function activate_plugin() {
        $this->ensure_table_exists();
        if (get_option('wholesalex_bulk_roles') === false) update_option('wholesalex_bulk_roles', array('administrator'));
        if (get_option('wholesalex_sample_roles') === false) update_option('wholesalex_sample_roles', array('administrator'));
        if (get_option('wholesalex_enable_bulk') === false) update_option('wholesalex_enable_bulk', 1);
        if (get_option('wholesalex_enable_sample') === false) update_option('wholesalex_enable_sample', 1);
        if (get_option('wholesalex_enabled_bulk_products') === false) update_option('wholesalex_enabled_bulk_products', array());
        if (get_option('wholesalex_enabled_sample_products') === false) update_option('wholesalex_enabled_sample_products', array());
        flush_rewrite_rules();
    }
    public function deactivate_plugin() {
        flush_rewrite_rules();
    }
    public function ensure_table_exists() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            user_name varchar(255) NOT NULL,
            user_email varchar(255) NOT NULL,
            enquiry_data longtext NOT NULL,
            message text NOT NULL,
            type varchar(20) NOT NULL DEFAULT 'bulk',
            status varchar(50) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type),
            KEY created_at (created_at)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        $wpdb->query("UPDATE {$this->table_name} SET created_at = NOW() WHERE created_at = '0000-00-00 00:00:00' OR created_at IS NULL");
    }
    private function is_vendor_user($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) return false;
        return in_array('wcfm_vendor', (array) $user->roles);
    }
    private function get_relative_date($date_str) {
        if (empty($date_str) || $date_str == '0000-00-00 00:00:00') return 'Unknown';
        $time = strtotime($date_str);
        $now = current_time('timestamp');
        $today = strtotime('today', $now);
        $yesterday = strtotime('yesterday', $now);
        $this_week_start = strtotime('monday this week', $now);
        $last_week_start = strtotime('monday last week', $now);
        if ($time >= $today) return 'Today';
        if ($time >= $yesterday && $time < $today) return 'Yesterday';
        if ($time >= $this_week_start) return 'This Week';
        if ($time >= $last_week_start && $time < $this_week_start) return 'Last Week';
        return 'Older';
    }
    private function is_wholesale() {
        if (!is_user_logged_in()) return false;
        $user = wp_get_current_user();
        if (function_exists('wholesalex')) {
            $role = wholesalex()->get_current_user_role();
            if (!empty($role)) return true;
        }
        foreach ($user->roles as $role) {
            if (strpos($role, 'wholesale') !== false) return true;
        }
        return false;
    }
    private function can_view($type) {
        $roles = get_option('wholesalex_' . $type . '_roles', array('administrator'));
        $user = wp_get_current_user();
        return current_user_can('manage_options') || (bool) array_intersect((array) $user->roles, $roles);
    }
    private function can_access_wcfm() {
        return current_user_can('manage_options') || (function_exists('wcfm_is_vendor') && wcfm_is_vendor());
    }
    private function should_show_form_on_product($product_id, $form_type) {
        $author_id = (int) get_post_field('post_author', $product_id);
        $enabled_products = array();
        if ($author_id > 0 && $this->is_vendor_user($author_id)) {
            $enabled_products = get_user_meta($author_id, 'wholesalex_enabled_' . $form_type . '_products', true);
            if (!is_array($enabled_products)) $enabled_products = array();
        } else {
            $enabled_products = get_option('wholesalex_enabled_' . $form_type . '_products', array());
        }
        return in_array($product_id, $enabled_products);
    }
    public function render_product_page_forms() {
        if (!is_user_logged_in()) return;
        global $product;
        if (!$product || !($product instanceof WC_Product)) return;
        $user = wp_get_current_user();
        $user_roles = (array) $user->roles;
        $bulk_roles = (array) get_option('wholesalex_bulk_roles', array());
        $sample_roles = (array) get_option('wholesalex_sample_roles', array());
        $can_see_bulk = (bool) array_intersect($user_roles, $bulk_roles);
        $can_see_sample = (bool) array_intersect($user_roles, $sample_roles);
        $enable_bulk = get_option('wholesalex_enable_bulk', 1);
        $enable_sample = get_option('wholesalex_enable_sample', 1);
        if ((!$enable_bulk || !$can_see_bulk) && (!$enable_sample || !$can_see_sample)) return;
        $pid = $product->get_id();
        $pname = $product->get_name();
        $show_bulk = $this->should_show_form_on_product($pid, 'bulk');
        $show_sample = $this->should_show_form_on_product($pid, 'sample');
        $bulk_to_show = $enable_bulk && $can_see_bulk && $show_bulk;
        $sample_to_show = $enable_sample && $can_see_sample && $show_sample;
        if (!$bulk_to_show && !$sample_to_show) return;
        $wholesale_price = null;
        if (function_exists('wholesalex') && $this->is_wholesale()) {
            $wholesale_role = wholesalex()->get_current_user_role();
            if (!empty($wholesale_role)) {
                $product_wholesale_prices = get_post_meta($pid, '_wholesalex_wholesale_prices', true);
                if (is_array($product_wholesale_prices) && isset($product_wholesale_prices[$wholesale_role])) {
                    $wholesale_price = $product_wholesale_prices[$wholesale_role]['price'];
                }
            }
        }
        ?>
        <div class="wxbe-section">
            <?php if ($wholesale_price): ?>
            <div class="wholesale-special-details">
                <h4><?php _e('WholesaleX B2B Special', 'wholesalex-bulk-enquiry'); ?></h4>
                <p><strong><?php _e('Wholesale Price for You:', 'wholesalex-bulk-enquiry'); ?></strong> <?php echo wc_price($wholesale_price); ?></p>
            </div>
            <?php endif; ?>
            <h3><?php _e('Wholesale Options', 'wholesalex-bulk-enquiry'); ?></h3>
            <?php if ($bulk_to_show) : ?>
            <div class="wxbe-bulk">
                <h4><?php _e('Bulk Order Enquiry', 'wholesalex-bulk-enquiry'); ?></h4>
                <label><input type="checkbox" id="wxbe-bulk-toggle"> <?php _e('Request bulk order', 'wholesalex-bulk-enquiry'); ?></label>
                <div id="wxbe-bulk-form" style="display:none">
                    <form class="wxbe-form-bulk">
                        <table>
                            <tr><th><?php _e('Product Name', 'wholesalex-bulk-enquiry'); ?></th><th><?php _e('Quantity', 'wholesalex-bulk-enquiry'); ?></th></tr>
                            <tr><td><?php echo esc_html($pname); ?></td><td><input type="number" name="qty" min="1" value="100" required></td></tr>
                        </table>
                        <textarea name="msg" placeholder="<?php _e('Additional message (optional)', 'wholesalex-bulk-enquiry'); ?>"></textarea>
                        <input type="hidden" name="pid" value="<?php echo $pid; ?>">
                        <input type="hidden" name="pname" value="<?php echo esc_attr($pname); ?>">
                        <button type="submit"><?php _e('Submit Bulk Enquiry', 'wholesalex-bulk-enquiry'); ?></button>
                        <div class="wxbe-response"></div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($sample_to_show) : ?>
            <div class="wxbe-sample">
                <h4><?php _e('Sample Request', 'wholesalex-bulk-enquiry'); ?></h4>
                <label><input type="checkbox" id="wxbe-sample-toggle"> <?php _e('Request sample', 'wholesalex-bulk-enquiry'); ?></label>
                <div id="wxbe-sample-form" style="display:none">
                    <div class="wxbe-warning"><?php _e('⚠️ Note: Sample products cannot be returned or replaced', 'wholesalex-bulk-enquiry'); ?></div>
                    <form class="wxbe-form-sample">
                        <table>
                            <tr><th><?php _e('Product Name', 'wholesalex-bulk-enquiry'); ?></th><th><?php _e('Sample', 'wholesalex-bulk-enquiry'); ?></th></tr>
                            <tr><td><?php echo esc_html($pname); ?></td><td><span class="wxbe-badge">✓ Sample</span></td></tr>
                        </table>
                        <textarea name="msg" placeholder="<?php _e('Additional message (optional)', 'wholesalex-bulk-enquiry'); ?>"></textarea>
                        <input type="hidden" name="pid" value="<?php echo $pid; ?>">
                        <input type="hidden" name="pname" value="<?php echo esc_attr($pname); ?>">
                        <button type="submit"><?php _e('Request Sample', 'wholesalex-bulk-enquiry'); ?></button>
                        <div class="wxbe-response"></div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <style>
        .wxbe-section{margin:20px 0;padding:20px;border:2px solid #05b895;border-radius:5px;background:#f9f9f9}
        .wxbe-section h3{margin-top:0;color:#05b895}
        .wholesale-special-details{background:#e7f3ff;padding:10px;border-radius:5px;margin-bottom:15px}
        .wholesale-special-details h4{margin:0 0 5px 0;color:#0073aa}
        .wxbe-bulk,.wxbe-sample{margin:15px 0;padding:15px;background:white;border-radius:5px}
        .wxbe-bulk h4,.wxbe-sample h4{margin-top:0}
        .wxbe-section table{width:100%;border-collapse:collapse;margin:15px 0}
        .wxbe-section th,.wxbe-section td{padding:10px;border:1px solid #ddd;text-align:left}
        .wxbe-section th{background:#f5f5f5;font-weight:bold}
        .wxbe-section input[type="number"],.wxbe-section textarea{width:100%;padding:8px;border:1px solid #ddd;border-radius:3px}
        .wxbe-section textarea{margin:15px 0;min-height:80px}
        .wxbe-section button{background:#05b895;color:white;border:none;padding:12px 24px;border-radius:3px;cursor:pointer;font-size:16px}
        .wxbe-section button:hover{background:#000}
        .wxbe-warning{background:#fff3cd;color:#856404;padding:12px;border:1px solid #ffeaa7;border-radius:3px;margin:15px 0}
        .wxbe-badge{background:#05b895;color:white;padding:5px 15px;border-radius:3px;font-weight:bold}
        .wxbe-response{margin-top:15px;padding:10px;border-radius:3px;display:none}
        .wxbe-response.success{background:#d4edda;color:#155724;border:1px solid #c3e6cb;display:block}
        .wxbe-response.error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;display:block}
        </style>
        <script>
        jQuery(function($){
            var ajax='<?php echo admin_url("admin-ajax.php"); ?>',nonce='<?php echo wp_create_nonce("wxbe_nonce"); ?>';
            $('#wxbe-bulk-toggle').change(function(){
                $('#wxbe-bulk-form').toggle(this.checked);
                if(this.checked){$('#wxbe-sample-toggle').prop('checked',false);$('#wxbe-sample-form').hide();}
            });
            $('#wxbe-sample-toggle').change(function(){
                $('#wxbe-sample-form').toggle(this.checked);
                if(this.checked){$('#wxbe-bulk-toggle').prop('checked',false);$('#wxbe-bulk-form').hide();}
            });
            $('.wxbe-form-bulk').submit(function(e){
                e.preventDefault();
                var $f=$(this),$r=$f.find('.wxbe-response');
                $r.show().text('Submitting...').removeClass('success error');
                $.post(ajax,{
                    action:'submit_bulk_enquiry',
                    nonce:nonce,
                    pid:$f.find('[name="pid"]').val(),
                    pname:$f.find('[name="pname"]').val(),
                    qty:$f.find('[name="qty"]').val(),
                    msg:$f.find('[name="msg"]').val()
                }).done(function(res){
                    if(res.success){
                        $r.removeClass('error').addClass('success').text(res.data.message);
                        $f[0].reset();
                        setTimeout(function(){$('#wxbe-bulk-toggle').click()},2000);
                    }else{
                        $r.removeClass('success').addClass('error').text(res.data?res.data.message:'Unknown error');
                    }
                }).fail(function(){
                    $r.removeClass('success').addClass('error').text('Network error. Please try again.');
                });
            });
            $('.wxbe-form-sample').submit(function(e){
                e.preventDefault();
                var $f=$(this),$r=$f.find('.wxbe-response');
                $r.show().text('Submitting...').removeClass('success error');
                $.post(ajax,{
                    action:'submit_sample_request',
                    nonce:nonce,
                    pid:$f.find('[name="pid"]').val(),
                    pname:$f.find('[name="pname"]').val(),
                    msg:$f.find('[name="msg"]').val()
                }).done(function(res){
                    if(res.success){
                        $r.removeClass('error').addClass('success').text(res.data.message);
                        $f[0].reset();
                        setTimeout(function(){$('#wxbe-sample-toggle').click()},2000);
                    }else{
                        $r.removeClass('success').addClass('error').text(res.data?res.data.message:'Unknown error');
                    }
                }).fail(function(){
                    $r.removeClass('success').addClass('error').text('Network error. Please try again.');
                });
            });
        });
        </script>
        <?php
    }
    public function handle_bulk_submission() {
        check_ajax_referer('wxbe_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error(array('message' => 'Please login first'));
        $user = wp_get_current_user();
        $user_roles = (array) $user->roles;
        $bulk_roles = (array) get_option('wholesalex_bulk_roles', array());
        if (!array_intersect($user_roles, $bulk_roles)) wp_send_json_error(array('message' => 'Unauthorized'));
        $pid = intval($_POST['pid'] ?? 0);
        $pname = sanitize_text_field($_POST['pname'] ?? '');
        $qty = intval($_POST['qty'] ?? 0);
        $msg = sanitize_textarea_field($_POST['msg'] ?? '');
        if (!$pid || !$qty) wp_send_json_error(array('message' => 'Invalid data'));
        global $wpdb;
        $data = wp_json_encode(array(array('product_id' => $pid, 'product_name' => $pname, 'quantity' => $qty)));
        $result = $wpdb->insert($this->table_name, array(
            'user_id' => get_current_user_id(),
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'enquiry_data' => $data,
            'message' => $msg,
            'type' => 'bulk',
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ), array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));
        if ($result) {
            wp_mail(get_option('admin_email'), 'New Bulk Enquiry', 'New bulk order enquiry submitted');
            wp_send_json_success(array('message' => 'Bulk enquiry submitted successfully!'));
        } else {
            wp_send_json_error(array('message' => 'Failed to submit. Please try again.'));
        }
    }
    public function handle_sample_submission() {
        check_ajax_referer('wxbe_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error(array('message' => 'Please login first'));
        $user = wp_get_current_user();
        $user_roles = (array) $user->roles;
        $sample_roles = (array) get_option('wholesalex_sample_roles', array());
        if (!array_intersect($user_roles, $sample_roles)) wp_send_json_error(array('message' => 'Unauthorized'));
        $pid = intval($_POST['pid'] ?? 0);
        $pname = sanitize_text_field($_POST['pname'] ?? '');
        $msg = sanitize_textarea_field($_POST['msg'] ?? '');
        if (!$pid) wp_send_json_error(array('message' => 'Invalid data'));
        global $wpdb;
        $data = wp_json_encode(array(array('product_id' => $pid, 'product_name' => $pname, 'quantity' => 1, 'is_sample' => true)));
        $result = $wpdb->insert($this->table_name, array(
            'user_id' => get_current_user_id(),
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'enquiry_data' => $data,
            'message' => $msg,
            'type' => 'sample',
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ), array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));
        if ($result) {
            wp_mail(get_option('admin_email'), 'New Sample Request', 'New sample request submitted');
            wp_send_json_success(array('message' => 'Sample request submitted successfully!'));
        } else {
            wp_send_json_error(array('message' => 'Failed to submit. Please try again.'));
        }
    }
    public function add_admin_menu() {
        add_menu_page('Bulk Enquiries', 'Bulk Enquiries', 'manage_options', 'wxbe-bulk', array($this, 'admin_bulk_page'), 'dashicons-clipboard', 56);
        add_submenu_page('wxbe-bulk', 'Sample Enquiries', 'Sample Enquiries', 'manage_options', 'wxbe-sample', array($this, 'admin_sample_page'));
        add_submenu_page('wxbe-bulk', 'Settings', 'Settings', 'manage_options', 'wxbe-settings', array($this, 'admin_settings_page'));
    }
    public function admin_settings_page() {
        if (isset($_POST['save_settings'])) {
            check_admin_referer('wxbe_settings');
            $bulk_roles = isset($_POST['bulk_roles']) ? array_map('sanitize_text_field', (array) $_POST['bulk_roles']) : array();
            $sample_roles = isset($_POST['sample_roles']) ? array_map('sanitize_text_field', (array) $_POST['sample_roles']) : array();
            $submitted_bulk = isset($_POST['enabled_bulk_products']) ? array_map('intval', (array) $_POST['enabled_bulk_products']) : array();
            $submitted_sample = isset($_POST['enabled_sample_products']) ? array_map('intval', (array) $_POST['enabled_sample_products']) : array();
            update_option('wholesalex_bulk_roles', $bulk_roles);
            update_option('wholesalex_sample_roles', $sample_roles);
            update_option('wholesalex_enable_bulk', isset($_POST['enable_bulk']) ? 1 : 0);
            update_option('wholesalex_enable_sample', isset($_POST['enable_sample']) ? 1 : 0);
            // Collect vendor product IDs and vendors
            $vendor_product_ids = array();
            $vendors = array();
            if (function_exists('wcfm_is_vendor')) {
                $vendors_temp = get_users(array('role' => 'wcfm_vendor', 'fields' => array('ID')));
                foreach ($vendors_temp as $vendor_temp) {
                    $v_products_temp = get_posts(array(
                        'post_type' => 'product',
                        'author' => $vendor_temp->ID,
                        'post_status' => 'publish',
                        'posts_per_page' => -1,
                        'fields' => 'ids'
                    ));
                    $vendor_product_ids = array_merge($vendor_product_ids, $v_products_temp);
                    $vendors[$vendor_temp->ID] = $vendor_temp;
                }
                $vendor_product_ids = array_unique($vendor_product_ids);
            }
            // Save admin global
            $admin_enabled_bulk = array_values(array_diff($submitted_bulk, $vendor_product_ids));
            update_option('wholesalex_enabled_bulk_products', $admin_enabled_bulk);
            $admin_enabled_sample = array_values(array_diff($submitted_sample, $vendor_product_ids));
            update_option('wholesalex_enabled_sample_products', $admin_enabled_sample);
            // Save per vendor
            foreach ($vendors as $v_id => $vendor) {
                $v_products = get_posts(array(
                    'post_type' => 'product',
                    'author' => $v_id,
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'fields' => 'ids'
                ));
                $v_enabled_bulk = array_values(array_intersect($submitted_bulk, $v_products));
                update_user_meta($v_id, 'wholesalex_enabled_bulk_products', $v_enabled_bulk);
                $v_enabled_sample = array_values(array_intersect($submitted_sample, $v_products));
                update_user_meta($v_id, 'wholesalex_enabled_sample_products', $v_enabled_sample);
            }
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }
        $bulk_roles = get_option('wholesalex_bulk_roles', array('administrator'));
        $sample_roles = get_option('wholesalex_sample_roles', array('administrator'));
        $enable_bulk = get_option('wholesalex_enable_bulk', 1);
        $enable_sample = get_option('wholesalex_enable_sample', 1);
        $roles = get_editable_roles();
        $all_products = get_posts(array('post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC'));
        // Collect vendor product IDs for display logic
        $vendor_product_ids = array();
        if (function_exists('wcfm_is_vendor')) {
            $vendors_temp = get_users(array('role' => 'wcfm_vendor', 'fields' => array('ID')));
            foreach ($vendors_temp as $vendor_temp) {
                $v_products_temp = get_posts(array(
                    'post_type' => 'product',
                    'author' => $vendor_temp->ID,
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'fields' => 'ids'
                ));
                $vendor_product_ids = array_merge($vendor_product_ids, $v_products_temp);
            }
            $vendor_product_ids = array_unique($vendor_product_ids);
        }
        ?>
        <div class="wrap">
            <h1>Wholesale Enquiry Settings</h1>
            <form method="post">
                <?php wp_nonce_field('wxbe_settings'); ?>
                <h2>Enable/Disable Forms Globally</h2>
                <label><input type="checkbox" name="enable_bulk" <?php checked($enable_bulk, 1); ?> value="1"> Enable Bulk Order Forms</label><br>
                <label><input type="checkbox" name="enable_sample" <?php checked($enable_sample, 1); ?> value="1"> Enable Sample Request Forms</label>
                <h2 style="margin-top:25px;">Roles Allowed to See Bulk Order Option</h2>
                <select name="bulk_roles[]" multiple style="width:300px;height:160px;">
                    <?php foreach($roles as $key => $role): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php echo in_array($key, $bulk_roles) ? 'selected' : ''; ?>><?php echo esc_html($role['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <h2 style="margin-top:25px;">Roles Allowed to See Sample Request Option</h2>
                <select name="sample_roles[]" multiple style="width:300px;height:160px;">
                    <?php foreach($roles as $key => $role): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php echo in_array($key, $sample_roles) ? 'selected' : ''; ?>><?php echo esc_html($role['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <h2 style="margin-top:25px;">Show Forms on Specific Products (All)</h2>
                <p><em>Check products where you want to SHOW the form. Unchecked products will HIDE the form. Admin can edit settings for all products, including vendors'.</em></p>
                <?php if (empty($all_products)): ?>
                    <p>No products found.</p>
                <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead><tr><th style="width:80px;">Enable Bulk</th><th style="width:80px;">Enable Sample</th><th>Product Name</th><th>ID</th><th>Owner</th></tr></thead>
                    <tbody>
                    <?php foreach($all_products as $prod):
                        $author_id = (int) $prod->post_author;
                        $owner_name = $author_id ? get_user_by('id', $author_id)->display_name : 'Admin';
                        if ($this->is_vendor_user($author_id)) {
                            $enabled_bulk = get_user_meta($author_id, 'wholesalex_enabled_bulk_products', true) ?: array();
                            $enabled_sample = get_user_meta($author_id, 'wholesalex_enabled_sample_products', true) ?: array();
                        } else {
                            $enabled_bulk = get_option('wholesalex_enabled_bulk_products', array());
                            $enabled_sample = get_option('wholesalex_enabled_sample_products', array());
                        }
                        $checked_bulk = in_array($prod->ID, $enabled_bulk) ? 'checked' : '';
                        $checked_sample = in_array($prod->ID, $enabled_sample) ? 'checked' : '';
                    ?>
                        <tr>
                            <td><input type="checkbox" name="enabled_bulk_products[]" value="<?php echo esc_attr($prod->ID); ?>" <?php echo $checked_bulk; ?>></td>
                            <td><input type="checkbox" name="enabled_sample_products[]" value="<?php echo esc_attr($prod->ID); ?>" <?php echo $checked_sample; ?>></td>
                            <td><?php echo esc_html($prod->post_title); ?></td>
                            <td><?php echo esc_html($prod->ID); ?></td>
                            <td><?php echo esc_html($owner_name); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
                <p style="margin-top:20px;"><input type="submit" name="save_settings" class="button-primary" value="Save Settings"></p>
            </form>
        </div>
        <?php
    }
    public function admin_bulk_page() { $this->admin_page('bulk'); }
    public function admin_sample_page() { $this->admin_page('sample'); }
    private function admin_page($type) {
        if (!$this->can_view($type)) wp_die('No permission');
        global $wpdb;
        $nonce = wp_create_nonce('wxbe_nonce');
        $is_admin = current_user_can('manage_options');
        if (isset($_POST['update_status'])) {
            check_admin_referer('wxbe_status');
            $wpdb->update($this->table_name, array('status' => sanitize_text_field($_POST['status'])), array('id' => intval($_POST['eid'])), array('%s'), array('%d'));
            echo '<div class="notice notice-success"><p>Status updated!</p></div>';
        }
        $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE type=%s ORDER BY created_at DESC", $type));
        ?>
        <div class="wrap">
            <h1><?php echo $type == 'bulk' ? 'Bulk Enquiries' : 'Sample Requests'; ?></h1>
            <div class="wxbe-notice" style="display:none;"></div>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>Customer</th><th>Email</th><th>Product</th><?php if ($is_admin): ?><th>Vendor</th><?php endif; ?><th>Qty</th><th>Message</th><th>Status</th><th>When</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="<?php echo $is_admin ? 11 : 10; ?>">No enquiries found.</td></tr>
                <?php else: foreach($items as $i): $d = json_decode($i->enquiry_data, true);
                    $display_date = date_i18n('M j, Y g:i A', strtotime($i->created_at));
                    $relative_date = $this->get_relative_date($i->created_at);
                    $vendor_name = '';
                    if ($is_admin && isset($d[0]['product_id'])) {
                        $pid = intval($d[0]['product_id']);
                        $author_id = (int) get_post_field('post_author', $pid);
                        if ($author_id > 0) {
                            if ($this->is_vendor_user($author_id)) {
                                $store_name = get_user_meta($author_id, 'wcfm_store_name', true);
                                $vendor_name = $store_name ? $store_name : get_user_by('id', $author_id)->display_name;
                            } else {
                                $vendor_name = 'Admin';
                            }
                        } else {
                            $vendor_name = 'N/A';
                        }
                    }
                ?>
                    <tr>
                        <td><?php echo esc_html($i->id); ?></td>
                        <td><?php echo esc_html($i->user_name); ?></td>
                        <td><?php echo esc_html($i->user_email); ?></td>
                        <td><?php echo esc_html($d[0]['product_name'] ?? 'N/A'); ?></td>
                        <?php if ($is_admin): ?><td><?php echo esc_html($vendor_name); ?></td><?php endif; ?>
                        <td><?php echo isset($d[0]['is_sample']) && $d[0]['is_sample'] ? 'Sample' : esc_html($d[0]['quantity'] ?? 'N/A'); ?></td>
                        <td><?php echo esc_html(wp_trim_words($i->message, 8, '...')); ?></td>
                        <td><span class="wxbe-status-<?php echo esc_attr($i->status); ?>"><?php echo esc_html(ucfirst($i->status)); ?></span></td>
                        <td><strong><?php echo esc_html($relative_date); ?></strong></td>
                        <td><?php echo $display_date; ?></td>
                        <td>
                            <form method="post" style="display:inline">
                                <?php wp_nonce_field('wxbe_status'); ?>
                                <input type="hidden" name="eid" value="<?php echo esc_attr($i->id); ?>">
                                <select name="status">
                                    <option value="pending"<?php selected($i->status, 'pending'); ?>>Pending</option>
                                    <option value="processing"<?php selected($i->status, 'processing'); ?>>Processing</option>
                                    <option value="completed"<?php selected($i->status, 'completed'); ?>>Completed</option>
                                    <option value="cancelled"<?php selected($i->status, 'cancelled'); ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_status" class="button">Update</button>
                            </form>
                            <button class="reply-btn-admin button" data-eid="<?php echo esc_attr($i->id); ?>" data-email="<?php echo esc_attr($i->user_email); ?>">Reply</button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            <div id="reply-form-admin" class="reply-form" style="display:none;margin-top:20px;padding:15px;border:1px solid #ddd;border-radius:5px;">
                <h4>Reply to Enquiry</h4>
                <textarea name="msg" placeholder="Your reply message..." rows="4" style="width:100%;margin-bottom:10px;"></textarea>
                <input type="hidden" name="eid" class="reply-eid">
                <input type="hidden" name="email" class="reply-email">
                <button type="button" class="send-reply-btn-admin button-primary">Send Reply</button>
                <button type="button" class="cancel-reply-btn-admin button-secondary">Cancel</button>
            </div>
        </div>
        <style>
        .wxbe-status-pending{background:#f0ad4e;color:white;padding:3px 8px;border-radius:3px;font-size:11px}
        .wxbe-status-processing{background:#5bc0de;color:white;padding:3px 8px;border-radius:3px;font-size:11px}
        .wxbe-status-completed{background:#5cb85c;color:white;padding:3px 8px;border-radius:3px;font-size:11px}
        .wxbe-status-cancelled{background:#d9534f;color:white;padding:3px 8px;border-radius:3px;font-size:11px}
        .wxbe-notice{padding:10px;margin-bottom:10px;border-radius:3px;display:none}
        .wxbe-notice.success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
        .wxbe-notice.error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
        .reply-btn-admin, .send-reply-btn-admin, .cancel-reply-btn-admin {margin:2px;font-size:12px;padding:5px 10px;}
        .reply-btn-admin:hover, .send-reply-btn-admin:hover {background:#005a87;}
        .cancel-reply-btn-admin:hover {background:#545b62;}
        </style>
        <script>
        var wxbe_nonce='<?php echo $nonce; ?>';
        jQuery(function($){
            var ajax='<?php echo admin_url("admin-ajax.php"); ?>';
            var $notice=$('.wxbe-notice');
            var $form=$('#reply-form-admin');
            var $replyEid=$form.find('.reply-eid');
            var $replyEmail=$form.find('.reply-email');
            $('.reply-btn-admin').click(function(){
                var eid=$(this).data('eid');
                var email=$(this).data('email');
                $replyEid.val(eid);
                $replyEmail.val(email);
                $form.insertAfter($(this).closest('tr')).show();
            });
            $('.cancel-reply-btn-admin').click(function(){$form.hide();$form.find('textarea[name="msg"]').val('');});
            $('.send-reply-btn-admin').click(function(){
                var eid=$replyEid.val();
                var email=$replyEmail.val();
                var msg=$form.find('textarea[name="msg"]').val();
                if(!msg.trim()){alert('Please enter a message.');return;}
                $notice.hide();
                $.post(ajax,{action:'send_reply',nonce:wxbe_nonce,eid:eid,msg:msg}).done(function(res){
                    if(res.success){
                        $notice.addClass('success').removeClass('error').text(res.data.message).show();
                        $form.find('textarea[name="msg"]').val('');
                        $form.hide();
                        setTimeout(function(){$notice.hide();},3000);
                    }else{
                        $notice.addClass('error').removeClass('success').text(res.data?res.data.message:'Unknown error').show();
                    }
                }).fail(function(){$notice.addClass('error').removeClass('success').text('Network error. Please try again.').show();});
            });
        });
        </script>
        <?php
    }
    public function wcfm_register_endpoints() {
        if (class_exists('WCFM')) {
            add_rewrite_endpoint('bulk-enquiries', EP_PAGES);
            add_rewrite_endpoint('sample-enquiries', EP_PAGES);
            add_rewrite_endpoint('enabled-form-products', EP_PAGES);
        }
    }
    public function add_query_vars($v) {
        $v[] = 'bulk-enquiries';
        $v[] = 'sample-enquiries';
        $v[] = 'enabled-form-products';
        return $v;
    }
    public function endpoint_title($t, $e) {
        if ($e == 'bulk-enquiries') return 'Bulk Enquiries';
        if ($e == 'sample-enquiries') return 'Sample Enquiries';
        if ($e == 'enabled-form-products') return 'Enabled Form Products';
        return $t;
    }
    public function add_wcfm_menus($m) {
        if (!function_exists('wcfm_is_vendor')) return $m;
        if ($this->can_access_wcfm()) {
            $m['bulk-enquiries'] = array('label' => 'Bulk Enquiries', 'url' => '#bulk-enquiries', 'icon' => 'file-text', 'priority' => 55);
            $m['sample-enquiries'] = array('label' => 'Sample Enquiries', 'url' => '#sample-enquiries', 'icon' => 'flask', 'priority' => 56);
            $m['enabled-form-products'] = array('label' => 'Enabled Form Products', 'url' => '#enabled-form-products', 'icon' => 'clipboard-list', 'priority' => 57);
        }
        return $m;
    }
    public function wcfm_popup_content() {
        if (!function_exists('wcfm_is_vendor') && !current_user_can('manage_options')) return;
        if ($this->can_access_wcfm()) {
            $this->wcfm_popup('bulk');
            $this->wcfm_popup('sample');
            $this->wcfm_enabled_products_popup();
        }
    }
    private function wcfm_popup($type) {
        global $wpdb;
        $items = array();
        if (current_user_can('manage_options')) {
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE type=%s ORDER BY created_at DESC", $type));
        } elseif (function_exists('wcfm_is_vendor') && wcfm_is_vendor()) {
            $user_id = get_current_user_id();
            $vendor_id = function_exists('wcfm_get_vendor_id_by_user') ? wcfm_get_vendor_id_by_user($user_id) : $user_id;
            $vendor_products = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_author = %d AND post_type = 'product' AND post_status = 'publish'", $vendor_id));
            if (!empty($vendor_products)) {
                $placeholders = implode(',', array_fill(0, count($vendor_products), '%d'));
                $query = $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE type = %s AND JSON_EXTRACT(enquiry_data, '$[0].product_id') IN ($placeholders) ORDER BY created_at DESC", array_merge([$type], $vendor_products));
                $items = $wpdb->get_results($query);
            }
        }
        $pid = $type == 'bulk' ? 'wcfmBulkPop' : 'wcfmSamplePop';
        $title = $type == 'bulk' ? 'Bulk Enquiries' : 'Sample Requests';
        $nonce = wp_create_nonce('wxbe_nonce');
        ?>
        <div id="<?php echo esc_attr($pid); ?>" class="wcfm-popup-modal">
            <div class="wcfm-popup-wrapper">
                <div class="wcfm-popup-content">
                    <div class="wcfm-popup-header">
                        <h2><?php echo esc_html($title); ?></h2>
                        <button class="fullscreen-btn"> Full Screen</button>
                        <span class="wcfm-popup-close">&times;</span>
                    </div>
                    <div class="wcfm-popup-body">
                        <div class="wxbe-notice"></div>
                        <?php if (empty($items)): ?>
                            <p>No enquiries found.</p>
                        <?php else: ?>
                        <table class="display dataTable" style="width:100%">
                            <thead><tr><th>ID</th><th>Customer</th><th>Email</th><th>Product</th><th>Qty</th><th>Message</th><th>Status</th><th>When</th><th>Date</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach($items as $i): $d = json_decode($i->enquiry_data, true);
                                $display_date = date_i18n('M j, Y g:i A', strtotime($i->created_at));
                                $relative_date = $this->get_relative_date($i->created_at);
                            ?>
                                <tr>
                                    <td><?php echo esc_html($i->id); ?></td>
                                    <td><?php echo esc_html($i->user_name); ?></td>
                                    <td><?php echo esc_html($i->user_email); ?></td>
                                    <td><?php echo esc_html($d[0]['product_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo isset($d[0]['is_sample']) && $d[0]['is_sample'] ? 'Sample' : esc_html($d[0]['quantity'] ?? 'N/A'); ?></td>
                                    <td><?php echo esc_html(wp_trim_words($i->message, 10, '...')); ?></td>
                                    <td class="status-cell"><?php echo esc_html(ucfirst($i->status)); ?></td>
                                    <td><strong><?php echo esc_html($relative_date); ?></strong></td>
                                    <td><?php echo $display_date; ?></td>
                                    <td>
                                        <select class="status-select" data-eid="<?php echo esc_attr($i->id); ?>">
                                            <option value="pending"<?php selected($i->status, 'pending'); ?>>Pending</option>
                                            <option value="processing"<?php selected($i->status, 'processing'); ?>>Processing</option>
                                            <option value="completed"<?php selected($i->status, 'completed'); ?>>Completed</option>
                                            <option value="cancelled"<?php selected($i->status, 'cancelled'); ?>>Cancelled</option>
                                        </select>
                                        <button class="update-status-btn" data-eid="<?php echo esc_attr($i->id); ?>">Update</button>
                                        <button class="reply-btn" data-eid="<?php echo esc_attr($i->id); ?>" data-email="<?php echo esc_attr($i->user_email); ?>">Reply</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div id="reply-form-<?php echo esc_attr($type); ?>" class="reply-form" style="display:none;margin-top:20px;padding:15px;border:1px solid #ddd;border-radius:5px;">
                            <h4>Reply to Enquiry</h4>
                            <textarea name="msg" placeholder="Your reply message..." rows="4" style="width:100%;margin-bottom:10px;"></textarea>
                            <input type="hidden" name="eid" class="reply-eid">
                            <input type="hidden" name="email" class="reply-email">
                            <button type="button" class="send-reply-btn">Send Reply</button>
                            <button type="button" class="cancel-reply-btn">Cancel</button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <style>
        .wcfm-popup-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:99999;overflow-y:auto}
        .wcfm-popup-wrapper{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
        .wcfm-popup-content{background:white;border-radius:8px;max-width:90%;width:100%;max-height:90vh;display:flex;flex-direction:column}
        .wcfm-popup-header{padding:20px;border-bottom:1px solid #ddd;display:flex;justify-content:space-between;align-items:center}
        .wcfm-popup-header h2{margin:0;font-size:20px}
        .fullscreen-btn{background:#0073aa;color:white;border:none;padding:5px 10px;border-radius:3px;cursor:pointer;font-size:14px}
        .fullscreen-btn:hover{background:#005a87}
        .wcfm-popup-close{font-size:28px;cursor:pointer;color:#999;line-height:1}
        .wcfm-popup-close:hover{color:#333}
        .wcfm-popup-body{padding:20px;overflow-y:auto;flex:1}
        .wcfm-popup-content.fullscreen{max-width:100%!important;max-height:100vh!important;width:100%!important;height:100vh!important;border-radius:0;margin:0}
        .wcfm-popup-wrapper.fullscreen{padding:0}
        .update-status-btn,.send-reply-btn,.reply-btn{background:#0073aa;color:white;border:none;padding:5px 10px;border-radius:3px;cursor:pointer;margin:2px;font-size:12px}
        .update-status-btn:hover,.send-reply-btn:hover,.reply-btn:hover{background:#005a87}
        .cancel-reply-btn{background:#6c757d;color:white;border:none;padding:5px 10px;border-radius:3px;cursor:pointer;margin:2px;font-size:12px}
        .cancel-reply-btn:hover{background:#545b62}
        .status-select{padding:2px 5px;margin-right:5px;font-size:12px}
        .wxbe-notice{padding:10px;margin-bottom:10px;border-radius:3px;display:none}
        .wxbe-notice.success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
        .wxbe-notice.error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
        .status-cell{font-weight:bold}
        </style>
        <script>
        var wxbe_nonce='<?php echo $nonce; ?>';
        jQuery(function($){
            var ajax='<?php echo admin_url("admin-ajax.php"); ?>';
            var $popup=$('#<?php echo esc_attr($pid); ?>');
            var $notice=$popup.find('.wxbe-notice');
            var $form=$popup.find('.reply-form');
            var $replyEid=$form.find('.reply-eid');
            var $replyEmail=$form.find('.reply-email');
            var $content=$popup.find('.wcfm-popup-content');
            var $wrapper=$popup.find('.wcfm-popup-wrapper');
            $(document).on('click','a[href="#<?php echo esc_attr($type); ?>-enquiries"]',function(e){e.preventDefault();$popup.fadeIn();});
            $(document).on('click','.wcfm-popup-close',function(){$(this).closest('.wcfm-popup-modal').fadeOut();$form.hide();$content.removeClass('fullscreen');$wrapper.removeClass('fullscreen');});
            $(document).on('click','.wcfm-popup-modal',function(e){if($(e.target).is('.wcfm-popup-modal')){$(this).fadeOut();$form.hide();$content.removeClass('fullscreen');$wrapper.removeClass('fullscreen');}});
            $popup.on('click','.fullscreen-btn',function(){
                var isFullscreen=$content.hasClass('fullscreen');
                if(isFullscreen){$content.removeClass('fullscreen');$wrapper.removeClass('fullscreen');$(this).text(' Full Screen');}
                else{$content.addClass('fullscreen');$wrapper.addClass('fullscreen');$(this).text(' Normal');}
            });
            $popup.on('click','.update-status-btn',function(){
                var $btn=$(this);
                var eid=$btn.data('eid');
                var status=$btn.siblings('.status-select').val();
                $notice.hide();
                $.post(ajax,{action:'update_enquiry_status',nonce:wxbe_nonce,eid:eid,status:status}).done(function(res){
                    if(res.success){
                        $notice.addClass('success').removeClass('error').text(res.data.message).show();
                        $btn.closest('tr').find('.status-cell').text(status.charAt(0).toUpperCase()+status.slice(1));
                        setTimeout(function(){$notice.hide();},3000);
                    }else{
                        $notice.addClass('error').removeClass('success').text(res.data?res.data.message:'Unknown error').show();
                    }
                }).fail(function(){$notice.addClass('error').removeClass('success').text('Network error. Please try again.').show();});
            });
            $popup.on('click','.reply-btn',function(){
                var eid=$(this).data('eid');
                var email=$(this).data('email');
                $replyEid.val(eid);
                $replyEmail.val(email);
                $form.insertAfter($(this).closest('tr')).show();
            });
            $popup.on('click','.cancel-reply-btn',function(){$form.hide();$form.find('textarea[name="msg"]').val('');});
            $popup.on('click','.send-reply-btn',function(){
                var eid=$replyEid.val();
                var email=$replyEmail.val();
                var msg=$form.find('textarea[name="msg"]').val();
                if(!msg.trim()){alert('Please enter a message.');return;}
                $notice.hide();
                $.post(ajax,{action:'send_reply',nonce:wxbe_nonce,eid:eid,msg:msg}).done(function(res){
                    if(res.success){
                        $notice.addClass('success').removeClass('error').text(res.data.message).show();
                        $form.find('textarea[name="msg"]').val('');
                        $form.hide();
                        setTimeout(function(){$notice.hide();},3000);
                    }else{
                        $notice.addClass('error').removeClass('success').text(res.data?res.data.message:'Unknown error').show();
                    }
                }).fail(function(){$notice.addClass('error').removeClass('success').text('Network error. Please try again.').show();});
            });
        });
        </script>
        <?php
    }
    private function wcfm_enabled_products_popup() {
        if (!function_exists('wcfm_is_vendor') || !wcfm_is_vendor()) return;
        global $wpdb;
        $user_id = get_current_user_id();
        $vendor_id = function_exists('wcfm_get_vendor_id_by_user') ? wcfm_get_vendor_id_by_user($user_id) : $user_id;
        $vendor_products = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_author = %d AND post_type = 'product' AND post_status = 'publish' ORDER BY post_title ASC", $vendor_id));
        $enabled_bulk = get_user_meta($vendor_id, 'wholesalex_enabled_bulk_products', true);
        if (!is_array($enabled_bulk)) $enabled_bulk = array();
        $enabled_sample = get_user_meta($vendor_id, 'wholesalex_enabled_sample_products', true);
        if (!is_array($enabled_sample)) $enabled_sample = array();
        $enable_bulk = get_option('wholesalex_enable_bulk', 1);
        $enable_sample = get_option('wholesalex_enable_sample', 1);
        $nonce = wp_create_nonce('wxbe_nonce');
        ?>
        <div id="wcfmEnabledFormPop" class="wcfm-popup-modal">
            <div class="wcfm-popup-wrapper">
                <div class="wcfm-popup-content">
                    <div class="wcfm-popup-header">
                        <h2>Manage Form Visibility on Products</h2>
                        <span class="wcfm-popup-close">&times;</span>
                    </div>
                    <div class="wcfm-popup-body">
                        <div class="wxbe-notice"></div>
                        <?php if (empty($vendor_products)): ?>
                            <p>No products found.</p>
                        <?php else: ?>
                        <form id="enabled-products-form">
                            <p><em>Check products where you want to SHOW the form. Unchecked products will HIDE the form.</em></p>
                            <table class="wp-list-table widefat fixed striped" style="width:100%">
                                <thead><tr><th style="width:80px;">Enable Bulk</th><th style="width:80px;">Enable Sample</th><th>Product Name</th><th>ID</th><th style="width:100px;">Preview</th></tr></thead>
                                <tbody>
                                <?php foreach($vendor_products as $prod):
                                    $bulk_enabled = $enable_bulk && in_array($prod->ID, $enabled_bulk);
                                    $sample_enabled = $enable_sample && in_array($prod->ID, $enabled_sample);
                                    $complete_disabled = !$bulk_enabled && !$sample_enabled;
                                ?>
                                    <tr data-product-id="<?php echo esc_attr($prod->ID); ?>" data-product-name="<?php echo esc_attr($prod->post_title); ?>" data-bulk-enabled="<?php echo $bulk_enabled ? '1' : '0'; ?>" data-sample-enabled="<?php echo $sample_enabled ? '1' : '0'; ?>" data-complete-disabled="<?php echo $complete_disabled ? '1' : '0'; ?>">
                                        <td><input type="checkbox" name="enabled_bulk[]" value="<?php echo esc_attr($prod->ID); ?>" <?php echo in_array($prod->ID, $enabled_bulk) ? 'checked' : ''; ?>></td>
                                        <td><input type="checkbox" name="enabled_sample[]" value="<?php echo esc_attr($prod->ID); ?>" <?php echo in_array($prod->ID, $enabled_sample) ? 'checked' : ''; ?>></td>
                                        <td><?php echo esc_html($prod->post_title); ?></td>
                                        <td><?php echo esc_html($prod->ID); ?></td>
                                        <td><button type="button" class="preview-btn button" style="padding: 2px 8px; font-size: 12px;">Preview</button></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div style="margin-top: 15px; padding: 10px; background: #f9f9f9; border-radius: 3px;">
                                <p><strong>Note:</strong> Changes will apply globally based on the checkboxes above. Bulk and Sample forms can be enabled/disabled independently or completely disabled for a product.</p>
                            </div>
                            <button type="submit" class="button-primary" style="margin-top:15px;">Save Settings</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div id="preview-modal" class="wcfm-popup-modal" style="display:none;">
            <div class="wcfm-popup-wrapper">
                <div class="wcfm-popup-content" style="max-width: 400px;">
                    <div class="wcfm-popup-header">
                        <h3 id="preview-title">Form Visibility Preview</h3>
                        <span class="wcfm-popup-close">&times;</span>
                    </div>
                    <div class="wcfm-popup-body">
                        <div id="preview-content">
                            <p><strong>Bulk Form:</strong> <span id="bulk-status"></span></p>
                            <p><strong>Sample Form:</strong> <span id="sample-status"></span></p>
                            <p id="complete-status" style="display:none;"><strong>Status:</strong> Complete forms disabled for this product.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <style>
        .wcfm-popup-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:99999;overflow-y:auto}
        .wcfm-popup-wrapper{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
        .wcfm-popup-content{background:white;border-radius:8px;max-width:90%;width:100%;max-height:90vh;display:flex;flex-direction:column}
        .wcfm-popup-header{padding:20px;border-bottom:1px solid #ddd;display:flex;justify-content:space-between;align-items:center}
        .wcfm-popup-header h2{margin:0;font-size:20px}
        .wcfm-popup-close{font-size:28px;cursor:pointer;color:#999;line-height:1}
        .wcfm-popup-close:hover{color:#333}
        .wcfm-popup-body{padding:20px;overflow-y:auto;flex:1}
        .preview-btn:hover { background: #0073aa; color: white; }
        #preview-content p { margin: 10px 0; }
        #bulk-status.enabled { color: green; font-weight: bold; }
        #bulk-status.disabled { color: red; font-weight: bold; }
        #sample-status.enabled { color: green; font-weight: bold; }
        #sample-status.disabled { color: red; font-weight: bold; }
        #complete-status { color: orange; font-weight: bold; }
        .wxbe-notice{padding:10px;margin-bottom:10px;border-radius:3px;display:none}
        .wxbe-notice.success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
        .wxbe-notice.error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
        table.dataTable.display tbody td {border-top: 1px solid #ddd !important;border-left: 1px solid #ddd !important;}
        tbody, td, tfoot, th, thead, tr {border-color: inherit;border-style: solid;border-width: 1px;}
        table.dataTable.display tbody tr td:last-child {text-align: center;display: flex !important;}
        </style>
        <script>
        var wxbe_nonce='<?php echo $nonce; ?>';
        jQuery(function($){
            var ajax='<?php echo admin_url("admin-ajax.php"); ?>';
            var $popup=$('#wcfmEnabledFormPop');
            var $notice=$popup.find('.wxbe-notice');
            var $previewModal = $('#preview-modal');
            $(document).on('click','a[href="#enabled-form-products"]',function(e){e.preventDefault();$popup.fadeIn();});
            $(document).on('click','.wcfm-popup-close',function(){$(this).closest('.wcfm-popup-modal').fadeOut();});
            $(document).on('click','.wcfm-popup-modal',function(e){if($(e.target).is('.wcfm-popup-modal')){$(this).fadeOut();}});
            // Preview functionality
            $popup.on('click', '.preview-btn', function() {
                var $row = $(this).closest('tr');
                var productId = $row.data('product-id');
                var productName = $row.data('product-name');
                var bulkEnabled = $row.data('bulk-enabled');
                var sampleEnabled = $row.data('sample-enabled');
                var completeDisabled = $row.data('complete-disabled');
                $('#preview-title').text('Form Visibility for: ' + productName);
                $('#bulk-status').text(bulkEnabled ? 'Enabled' : 'Disabled').removeClass('enabled disabled').addClass(bulkEnabled ? 'enabled' : 'disabled');
                $('#sample-status').text(sampleEnabled ? 'Enabled' : 'Disabled').removeClass('enabled disabled').addClass(sampleEnabled ? 'enabled' : 'disabled');
                $('#complete-status').toggle(completeDisabled);
                $previewModal.fadeIn();
            });
            // Close preview modal
            $previewModal.on('click', '.wcfm-popup-close, .wcfm-popup-modal', function(e) {
                if ($(e.target).is('.wcfm-popup-close') || $(e.target).is('.wcfm-popup-modal')) {
                    $previewModal.fadeOut();
                }
            });
            // Form submission with confirmation
            $('#enabled-products-form').submit(function(e){
                e.preventDefault();
                if (!confirm('Are you sure you want to save these settings? This will update form visibility for your products.')) {
                    return;
                }
                $notice.hide();
                var enabled_bulk=$(this).find('input[name="enabled_bulk[]"]:checked').map(function(){return this.value;}).get();
                var enabled_sample=$(this).find('input[name="enabled_sample[]"]:checked').map(function(){return this.value;}).get();
                $.post(ajax,{action:'save_vendor_enabled_forms',nonce:wxbe_nonce,enabled_bulk:enabled_bulk,enabled_sample:enabled_sample}).done(function(res){
                    if(res.success){
                        $notice.addClass('success').removeClass('error').text(res.data.message).show();
                        setTimeout(function(){$notice.hide();$popup.fadeOut();},2000);
                    }else{
                        $notice.addClass('error').removeClass('success').text(res.data?res.data.message:'Unknown error').show();
                    }
                }).fail(function(){$notice.addClass('error').removeClass('success').text('Network error. Please try again.').show();});
            });
            // Dynamic update preview on checkbox change (optional real-time preview)
            $popup.on('change', 'input[type="checkbox"]', function() {
                var $row = $(this).closest('tr');
                var productId = $row.data('product-id');
                var $bulkCb = $row.find('input[name="enabled_bulk[]"]');
                var $sampleCb = $row.find('input[name="enabled_sample[]"]');
                var bulkEnabled = <?php echo json_encode($enable_bulk); ?> && $bulkCb.is(':checked');
                var sampleEnabled = <?php echo json_encode($enable_sample); ?> && $sampleCb.is(':checked');
                var completeDisabled = !bulkEnabled && !sampleEnabled;
                $row.data('bulk-enabled', bulkEnabled ? 1 : 0);
                $row.data('sample-enabled', sampleEnabled ? 1 : 0);
                $row.data('complete-disabled', completeDisabled ? 1 : 0);
            });
        });
        </script>
        <?php
    }
    public function save_vendor_enabled_forms() {
        check_ajax_referer('wxbe_nonce', 'nonce');
        if (!function_exists('wcfm_is_vendor') || !wcfm_is_vendor()) wp_send_json_error(array('message' => 'Unauthorized'));
        $user_id = get_current_user_id();
        $vendor_id = function_exists('wcfm_get_vendor_id_by_user') ? wcfm_get_vendor_id_by_user($user_id) : $user_id;
        $enabled_bulk = isset($_POST['enabled_bulk']) ? array_map('intval', (array) $_POST['enabled_bulk']) : array();
        $enabled_sample = isset($_POST['enabled_sample']) ? array_map('intval', (array) $_POST['enabled_sample']) : array();
        update_user_meta($vendor_id, 'wholesalex_enabled_bulk_products', $enabled_bulk);
        update_user_meta($vendor_id, 'wholesalex_enabled_sample_products', $enabled_sample);
        wp_send_json_success(array('message' => 'Settings saved successfully!'));
    }
    public function update_status() {
        check_ajax_referer('wxbe_nonce', 'nonce');
        global $wpdb;
        $eid = intval($_POST['eid'] ?? 0);
        if (!$eid) wp_send_json_error(array('message' => 'Invalid enquiry ID'));
        $enquiry = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $eid));
        if (!$enquiry) wp_send_json_error(array('message' => 'Enquiry not found'));
        $d = json_decode($enquiry->enquiry_data, true);
        $pid = $d[0]['product_id'] ?? 0;
        if (!$pid) wp_send_json_error(array('message' => 'Invalid product ID'));
        $vendor_id = get_post_field('post_author', $pid);
        $current_user_id = get_current_user_id();
        $current_vendor_id = function_exists('wcfm_get_vendor_id_by_user') ? wcfm_get_vendor_id_by_user($current_user_id) : $current_user_id;
        $is_authorized = current_user_can('manage_options') || ($vendor_id == $current_vendor_id);
        if (!$is_authorized) wp_send_json_error(array('message' => 'Unauthorized'));
        $status = sanitize_text_field($_POST['status'] ?? '');
        if (!in_array($status, ['pending', 'processing', 'completed', 'cancelled'])) wp_send_json_error(array('message' => 'Invalid status'));
        $r = $wpdb->update($this->table_name, array('status' => $status), array('id' => $eid), array('%s'), array('%d'));
        if ($r !== false) {
            wp_send_json_success(array('message' => 'Status updated successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update status'));
        }
    }
    public function send_reply() {
        check_ajax_referer('wxbe_nonce', 'nonce');
        global $wpdb;
        $eid = intval($_POST['eid'] ?? 0);
        if (!$eid) wp_send_json_error(array('message' => 'Invalid enquiry ID'));
        $enquiry = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $eid));
        if (!$enquiry) wp_send_json_error(array('message' => 'Enquiry not found'));
        $d = json_decode($enquiry->enquiry_data, true);
        $pid = $d[0]['product_id'] ?? 0;
        if (!$pid) wp_send_json_error(array('message' => 'Invalid product ID'));
        $vendor_id = get_post_field('post_author', $pid);
        $current_user_id = get_current_user_id();
        $current_vendor_id = function_exists('wcfm_get_vendor_id_by_user') ? wcfm_get_vendor_id_by_user($current_user_id) : $current_user_id;
        $is_authorized = current_user_can('manage_options') || ($vendor_id == $current_vendor_id);
        if (!$is_authorized) wp_send_json_error(array('message' => 'Unauthorized'));
        $msg = sanitize_textarea_field($_POST['msg'] ?? '');
        if (empty($msg)) wp_send_json_error(array('message' => 'Message is required'));
        $email = sanitize_email($enquiry->user_email);
        $subject = 'Re: ' . ($enquiry->type === 'bulk' ? 'Bulk Enquiry' : 'Sample Request');
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($email, $subject, $msg, $headers);
        if ($sent) {
            wp_send_json_success(array('message' => 'Reply sent successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to send reply'));
        }
    }
    public function delete_enquiry() {
        check_ajax_referer('wxbe_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized'));
        global $wpdb;
        $r = $wpdb->delete($this->table_name, array('id' => intval($_POST['eid'])), array('%d'));
        $r ? wp_send_json_success(array('message' => 'Deleted')) : wp_send_json_error(array('message' => 'Failed'));
    }
    public function enqueue_scripts() {
        if (!is_admin()) wp_enqueue_script('jquery');
    }
    public function admin_scripts($h) {
        if (isset($_GET['page']) && strpos($_GET['page'], 'wxbe-') === 0) wp_enqueue_script('jquery');
    }
}
new WholesaleX_Bulk_Sample_Enquiry();