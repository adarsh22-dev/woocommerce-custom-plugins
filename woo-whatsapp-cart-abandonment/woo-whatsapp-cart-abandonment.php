<?php
/**
 * Plugin Name: CISAI WooCart WhatsApp Abandonment (Free)
 * Description: Single-file WooCommerce plugin to track cart abandonment and send WhatsApp notifications. Free version supports:
 *  - Tracking carts (guest & logged-in)
 *  - Admin settings page
 *  - Two sending modes: WhatsApp Cloud API (Meta) and Manual wa.me fallback (admin-click)
 *  - WP-Cron scanner that finds abandoned carts and sends notifications
 *  - Simple analytics/logging
 * Version: 1.0.0
 * Author: CISAI (generated)
 * Text Domain: cisai-wc-wa-abandon
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

register_activation_hook(__FILE__, 'cisai_wc_wa_activate');
register_deactivation_hook(__FILE__, 'cisai_wc_wa_deactivate');

function cisai_wc_wa_activate(){
    cisai_wc_wa_create_db();
    if ( ! wp_next_scheduled( 'cisai_wc_wa_cron_hook' ) ) {
        wp_schedule_event( time(), 'five_minutes', 'cisai_wc_wa_cron_hook' );
    }
}

function cisai_wc_wa_deactivate(){
    wp_clear_scheduled_hook( 'cisai_wc_wa_cron_hook' );
}

// Add custom interval 5 minutes
add_filter('cron_schedules', function($schedules){
    if(!isset($schedules['five_minutes'])){
        $schedules['five_minutes'] = array('interval' => 300, 'display' => 'Every 5 Minutes');
    }
    return $schedules;
});

// Create DB table for carts and logs
function cisai_wc_wa_create_db(){
    global $wpdb;
    $table = $wpdb->prefix . 'cisai_wc_wa_carts';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        session_key varchar(191) DEFAULT '' NOT NULL,
        user_id bigint(20) DEFAULT 0,
        phone varchar(60) DEFAULT '' NOT NULL,
        cart_hash varchar(191) DEFAULT '' NOT NULL,
        cart_contents longtext,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        notified tinyint(1) DEFAULT 0,
        converted tinyint(1) DEFAULT 0,
        PRIMARY KEY  (id),
        KEY session_key (session_key),
        KEY user_id (user_id),
        KEY notified (notified),
        KEY converted (converted)
    ) $charset;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    $log_table = $wpdb->prefix . 'cisai_wc_wa_logs';
    $sql2 = "CREATE TABLE IF NOT EXISTS $log_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        cart_id bigint(20) unsigned DEFAULT 0,
        event varchar(100) DEFAULT '',
        meta longtext,
        created_at datetime DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (id),
        KEY cart_id (cart_id)
    ) $charset;";
    dbDelta($sql2);
}

// Admin menu & settings
add_action('admin_menu', 'cisai_wc_wa_admin_menu');
add_action('admin_init', 'cisai_wc_wa_register_settings');

function cisai_wc_wa_admin_menu(){
    add_submenu_page('woocommerce', 'WhatsApp Abandonment', 'WhatsApp Abandonment', 'manage_options', 'cisai-wc-wa', 'cisai_wc_wa_admin_page');
}

function cisai_wc_wa_register_settings(){
    register_setting('cisai_wc_wa_group', 'cisai_wc_wa_options');
}

function cisai_wc_wa_get_options(){
    $defaults = array(
        'method' => 'manual', // manual | meta
        'abandon_minutes' => 30,
        'admin_email' => get_option('admin_email'),
        'meta_phone_id' => '',
        'meta_token' => '',
        'message_template' => "Hi {first_name}, we noticed you left items in your cart: {items}. Get it now: {checkout_url}",
    );
    $opts = get_option('cisai_wc_wa_options', array());
    return wp_parse_args($opts, $defaults);
}

function cisai_wc_wa_admin_page(){
    if(!current_user_can('manage_options')) return;
    $opts = cisai_wc_wa_get_options();
    if($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('cisai_wc_wa_save','cisai_wc_wa_nonce')){
        $save = array();
        $save['method'] = sanitize_text_field($_POST['method']);
        $save['abandon_minutes'] = intval($_POST['abandon_minutes']);
        $save['admin_email'] = sanitize_email($_POST['admin_email']);
        $save['meta_phone_id'] = sanitize_text_field($_POST['meta_phone_id']);
        $save['meta_token'] = sanitize_text_field($_POST['meta_token']);
        $save['message_template'] = wp_kses_post($_POST['message_template']);
        update_option('cisai_wc_wa_options', $save);
        $opts = cisai_wc_wa_get_options();
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }
    // Show admin UI (settings and recent logs)
    ?>
    <div class="wrap">
        <h1>WooCart WhatsApp Abandonment — Settings</h1>
        <form method="post">
            <?php wp_nonce_field('cisai_wc_wa_save','cisai_wc_wa_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th>Sending Method</th>
                    <td>
                        <select name="method">
                            <option value="manual" <?php selected($opts['method'],'manual'); ?>>Manual / wa.me fallback (Free)</option>
                            <option value="meta" <?php selected($opts['method'],'meta'); ?>>WhatsApp Cloud API (Meta) — requires phone number id & token</option>
                        </select>
                        <p class="description">Manual mode will create a clickable wa.me link and send admin an email to click and send manually. Meta mode will try to send messages server-side using WhatsApp Cloud API.</p>
                    </td>
                </tr>
                <tr>
                    <th>Abandonment timeout (minutes)</th>
                    <td><input type="number" name="abandon_minutes" value="<?php echo esc_attr($opts['abandon_minutes']); ?>" min="5"/></td>
                </tr>
                <tr>
                    <th>Admin email (receive manual send notifications)</th>
                    <td><input type="email" name="admin_email" value="<?php echo esc_attr($opts['admin_email']); ?>"/></td>
                </tr>
                <tr>
                    <th>WhatsApp Cloud Phone Number ID</th>
                    <td><input type="text" name="meta_phone_id" value="<?php echo esc_attr($opts['meta_phone_id']); ?>" style="width:40%;"/>
                    <p class="description">Used only for Meta Cloud API mode. Example: 110000... (see Meta docs)</p></td>
                </tr>
                <tr>
                    <th>WhatsApp Cloud Access Token</th>
                    <td><input type="text" name="meta_token" value="<?php echo esc_attr($opts['meta_token']); ?>" style="width:60%;"/>
                    <p class="description">Your long-lived Access Token. Keep private.</p></td>
                </tr>
                <tr>
                    <th>Message Template</th>
                    <td>
                        <textarea name="message_template" rows="5" cols="60"><?php echo esc_textarea($opts['message_template']); ?></textarea>
                        <p class="description">Placeholders: {first_name}, {items}, {checkout_url}</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <h2>Recent Abandonment Logs</h2>
        <?php cisai_wc_wa_admin_logs_table(); ?>
    </div>
    <?php
}

function cisai_wc_wa_admin_logs_table(){
    global $wpdb;
    $table = $wpdb->prefix . 'cisai_wc_wa_logs';
    $c_table = $wpdb->prefix . 'cisai_wc_wa_carts';
    $rows = $wpdb->get_results("SELECT l.*, c.phone, c.cart_contents FROM $table l LEFT JOIN $c_table c ON c.id = l.cart_id ORDER BY l.created_at DESC LIMIT 100");
    echo '<table class="widefat fixed striped"><thead><tr><th>ID</th><th>Cart ID</th><th>Phone</th><th>Event</th><th>When</th><th>Preview</th></tr></thead><tbody>';
    if($rows){
        foreach($rows as $r){
            $meta = maybe_unserialize($r->meta);
            $preview = is_array($meta) && !empty($meta['message']) ? esc_html(substr($meta['message'],0,120)) : '';
            echo '<tr><td>'.intval($r->id).'</td><td>'.intval($r->cart_id).'</td><td>'.esc_html($r->phone).'</td><td>'.esc_html($r->event).'</td><td>'.esc_html($r->created_at).'</td><td>'.esc_html($preview).'</td></tr>';
        }
    } else {
        echo '<tr><td colspan="6">No logs yet.</td></tr>';
    }
    echo '</tbody></table>';
}

// Hook into cart add/update
add_action('woocommerce_add_to_cart', 'cisai_wc_wa_track_cart', 10, 6);
add_action('woocommerce_cart_updated', 'cisai_wc_wa_track_cart_update');
add_action('woocommerce_after_cart_item_quantity_update', 'cisai_wc_wa_track_cart_update');

function cisai_wc_wa_get_session_key(){
    if(function_exists('WC') && WC()->session){
        $sess = WC()->session->get_session_cookie();
        if(is_array($sess) && !empty($sess[0])) return $sess[0];
    }
    // fallback to PHP session id
    if(session_id()) return session_id();
    return wp_generate_uuid4();
}

function cisai_wc_wa_get_cart_hash(){
    if(!function_exists('WC') || !WC()->cart) return '';
    return md5( maybe_serialize( WC()->cart->get_cart() ) );
}

function cisai_wc_wa_track_cart(){
    cisai_wc_wa_store_cart_snapshot();
}
function cisai_wc_wa_track_cart_update(){
    cisai_wc_wa_store_cart_snapshot();
}

function cisai_wc_wa_store_cart_snapshot(){
    if(!function_exists('WC')) return;
    global $wpdb;
    $table = $wpdb->prefix . 'cisai_wc_wa_carts';
    $session_key = cisai_wc_wa_get_session_key();
    $user_id = get_current_user_id();
    $cart_hash = cisai_wc_wa_get_cart_hash();
    $cart_contents = maybe_serialize(WC()->cart->get_cart_for_session());

    // try to get phone: from billing phone if during checkout saved, or from user meta
    $phone = '';
    if($user_id){
        $phone = get_user_meta($user_id, 'billing_phone', true);
    }
    // also check session billing_phone
    $session_phone = WC()->session->get('billing_phone');
    if(!$phone && $session_phone) $phone = $session_phone;

    // find existing
    $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE session_key=%s", $session_key));
    $now = current_time('mysql');
    if($existing){
        $wpdb->update($table, array('cart_hash'=>$cart_hash,'cart_contents'=>$cart_contents,'updated_at'=>$now,'phone'=>$phone,'user_id'=>$user_id), array('id'=>$existing->id));
    } else {
        $wpdb->insert($table, array('session_key'=>$session_key,'user_id'=>$user_id,'phone'=>$phone,'cart_hash'=>$cart_hash,'cart_contents'=>$cart_contents,'created_at'=>$now,'updated_at'=>$now));
    }
}

// When an order is created/paid mark cart as converted
add_action('woocommerce_checkout_order_processed', 'cisai_wc_wa_order_created', 10, 1);
add_action('woocommerce_payment_complete', 'cisai_wc_wa_order_completed', 10, 1);

function cisai_wc_wa_order_created($order_id){
    cisai_wc_wa_mark_converted($order_id);
}
function cisai_wc_wa_order_completed($order_id){
    cisai_wc_wa_mark_converted($order_id);
}

function cisai_wc_wa_mark_converted($order_id){
    if(!$order_id) return;
    $order = wc_get_order($order_id);
    if(!$order) return;
    // attempt to match by user id or billing phone
    global $wpdb;
    $table = $wpdb->prefix . 'cisai_wc_wa_carts';
    $phone = $order->get_billing_phone();
    $user_id = $order->get_user_id();
    if($phone){
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE phone=%s ORDER BY created_at DESC LIMIT 1", $phone));
        if($row) $wpdb->update($table, array('converted'=>1), array('id'=>$row->id));
    }
    if(!$phone && $user_id){
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id=%d ORDER BY created_at DESC LIMIT 1", $user_id));
        if($row) $wpdb->update($table, array('converted'=>1), array('id'=>$row->id));
    }
}

// Cron scanner
add_action('cisai_wc_wa_cron_hook', 'cisai_wc_wa_cron_scan');
function cisai_wc_wa_cron_scan(){
    global $wpdb;
    $opts = cisai_wc_wa_get_options();
    $minutes = max(5, intval($opts['abandon_minutes']));
    $threshold = date('Y-m-d H:i:s', strtotime(current_time('mysql') . " - {$minutes} minutes"));
    $table = $wpdb->prefix . 'cisai_wc_wa_carts';
    // select carts older than threshold and not notified and not converted and that have items
    $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE updated_at <= %s AND notified = 0 AND converted = 0", $threshold));
    if(!$rows) return;
    foreach($rows as $r){
        // parse cart contents
        $cart = maybe_unserialize($r->cart_contents);
        $items = cisai_wc_wa_cart_items_text($cart);
        if(empty($items)) continue;
        // build checkout url with cart restored (we can append a query param to prefill? For free plugin we'll link to cart url)
        $checkout_url = wc_get_cart_url();
        $message = cisai_wc_wa_prepare_message($r, $items, $checkout_url);
        $sent = cisai_wc_wa_send_notification($r->phone, $message, $r);
        if($sent){
            $wpdb->update($table, array('notified'=>1), array('id'=>$r->id));
            cisai_wc_wa_log_event($r->id, 'notified', array('message'=>$message,'method'=>cisai_wc_wa_get_options()['method']));
        } else {
            cisai_wc_wa_log_event($r->id, 'notify_failed', array('message'=>$message));
        }
    }
}

function cisai_wc_wa_cart_items_text($cart){
    if(empty($cart) || !is_array($cart)) return '';
    $lines = array();
    foreach($cart as $item){
        $product_name = isset($item['data']) && is_object($item['data']) ? $item['data']->get_name() : (isset($item['name']) ? $item['name'] : 'Product');
        $qty = isset($item['quantity']) ? $item['quantity'] : 1;
        $lines[] = $product_name . ' x ' . $qty;
    }
    return implode(', ', $lines);
}

function cisai_wc_wa_prepare_message($cart_row, $items, $checkout_url){
    $opts = cisai_wc_wa_get_options();
    // try to get first name if user exists
    $first_name = '';
    if($cart_row->user_id){
        $first_name = get_user_meta($cart_row->user_id, 'first_name', true);
    }
    $message = $opts['message_template'];
    $message = str_replace('{first_name}', $first_name ? $first_name : '', $message);
    $message = str_replace('{items}', $items, $message);
    $message = str_replace('{checkout_url}', $checkout_url, $message);
    return $message;
}

function cisai_wc_wa_send_notification($phone, $message, $cart_row){
    $opts = cisai_wc_wa_get_options();
    if($opts['method'] === 'meta' && !empty($opts['meta_phone_id']) && !empty($opts['meta_token']) && !empty($phone)){
        // send via WhatsApp Cloud API
        $sent = cisai_wc_wa_send_meta($phone, $message, $opts['meta_phone_id'], $opts['meta_token']);
        return $sent;
    }
    // fallback: manual wa.me link. Send email to admin with wa.me link
    $wa_phone = preg_replace('/[^0-9]/','',$phone);
    if(empty($wa_phone)){
        // no phone available — log and fail
        return false;
    }
    $prefilled = rawurlencode($message);
    $wa_link = 'https://wa.me/' . $wa_phone . '?text=' . $prefilled;
    $admin_email = $opts['admin_email'];
    $subject = 'Cart Abandonment — Send WhatsApp message to ' . $phone;
    $body = "A cart appears abandoned. Click to send WhatsApp message:\n\n" . $wa_link . "\n\nMessage preview:\n" . $message;
    wp_mail($admin_email, $subject, $body);
    cisai_wc_wa_log_event($cart_row->id, 'manual_email_sent', array('wa_link'=>$wa_link,'message'=>$message));
    return true;
}

function cisai_wc_wa_send_meta($phone, $message, $phone_number_id, $token){
    // phone must be in international format without +
    $phone = preg_replace('/[^0-9]/','',$phone);
    if(empty($phone)) return false;
    $endpoint = 'https://graph.facebook.com/v16.0/' . $phone_number_id . '/messages';
    $body = array(
        'messaging_product' => 'whatsapp',
        'to' => $phone,
        'type' => 'text',
        'text' => array('body' => $message)
    );
    $args = array(
        'body' => wp_json_encode($body),
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ),
        'timeout' => 20,
    );
    $resp = wp_remote_post($endpoint, $args);
    if(is_wp_error($resp)){
        return false;
    }
    $code = wp_remote_retrieve_response_code($resp);
    $body = wp_remote_retrieve_body($resp);
    if($code >=200 && $code <300) return true;
    cisai_wc_wa_log_event(0, 'meta_send_failed', array('response_code'=>$code,'response_body'=>$body));
    return false;
}

function cisai_wc_wa_log_event($cart_id, $event, $meta = array()){
    global $wpdb;
    $table = $wpdb->prefix . 'cisai_wc_wa_logs';
    $wpdb->insert($table, array('cart_id'=>$cart_id,'event'=>$event,'meta'=>maybe_serialize($meta),'created_at'=>current_time('mysql')));
}

// Shortcode to show widget with "send me cart on WhatsApp" (optional)
add_shortcode('cisai_wc_wa_send_on_whatsapp', 'cisai_wc_wa_send_widget');
function cisai_wc_wa_send_widget($atts){
    if(!function_exists('WC')) return '';
    $cart = WC()->cart->get_cart();
    if(empty($cart)) return '';
    $items = cisai_wc_wa_cart_items_text($cart);
    $opts = cisai_wc_wa_get_options();
    ob_start();
    ?>
    <div class="cisai-wa-send">
        <p>Send this cart to your WhatsApp:</p>
        <a target="_blank" href="<?php echo esc_url('https://wa.me/?text=' . rawurlencode($items . ' - ' . wc_get_cart_url())); ?>" class="button">Send to WhatsApp</a>
    </div>
    <?php
    return ob_get_clean();
}

// Provide a small REST endpoint to accept phone number from checkout (ajax)
add_action('wp_ajax_cisai_save_checkout_phone', 'cisai_save_checkout_phone');
add_action('wp_ajax_nopriv_cisai_save_checkout_phone', 'cisai_save_checkout_phone');
function cisai_save_checkout_phone(){
    if(!isset($_POST['phone']) || !isset($_POST['session_key'])) wp_send_json_error('missing');
    global $wpdb;
    $table = $wpdb->prefix . 'cisai_wc_wa_carts';
    $phone = sanitize_text_field($_POST['phone']);
    $session_key = sanitize_text_field($_POST['session_key']);
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE session_key=%s", $session_key));
    if($row){
        $wpdb->update($table, array('phone'=>$phone,'updated_at'=>current_time('mysql')), array('id'=>$row->id));
    }
    wp_send_json_success();
}

// Add session key to checkout page and save phone via ajax when billing phone changes
add_action('wp_footer', 'cisai_wc_wa_frontend_js');
function cisai_wc_wa_frontend_js(){
    if(!is_checkout()) return;
    $session_key = cisai_wc_wa_get_session_key();
    ?>
    <script>
    (function(){
        var sessionKey = '<?php echo esc_js($session_key); ?>';
        function savePhone(){
            var phone = document.querySelector('input[name="billing_phone"]');
            if(!phone) return;
            var val = phone.value;
            var data = new FormData();
            data.append('action','cisai_save_checkout_phone');
            data.append('phone', val);
            data.append('session_key', sessionKey);
            fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {method:'POST', body: data, credentials: 'same-origin'}).catch(function(){});
        }
        document.addEventListener('change', function(e){
            if(e.target && e.target.matches('input[name="billing_phone"]')) savePhone();
        });
        // also save on load
        window.addEventListener('load', savePhone);
    })();
    </script>
    <?php
}

// Provide uninstall hook (optional) - DOES NOT delete data by default
// add_action('uninstall_' . plugin_basename(__FILE__), 'cisai_wc_wa_uninstall');
// function cisai_wc_wa_uninstall(){
//     // remove tables if desired
// }

// End of plugin
