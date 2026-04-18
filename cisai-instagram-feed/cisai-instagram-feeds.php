<?php
/*
Plugin Name: CISAI WooCommerce Instagram Feeds
Plugin URI: https://example.com
Description: Display Instagram images and videos linked to WooCommerce products in a responsive slider. Designed by Adarsh Singh
Version: 1.2
Author: Designed by Adarsh Singh
Author URI: https://adarshsingh.dev
Text Domain: cisai-instagram-feed
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) exit;

class CISAI_Instagram_Feed {

    private $option_key = 'cisai_instagram_settings';
    private $mapping_key = 'cisai_instagram_mapping';
    private $transient_key = 'cisai_instagram_cache';

    public function __construct() {
        add_action('admin_menu', array($this,'admin_menu'));
        add_action('admin_init', array($this,'register_settings'));
        add_action('admin_enqueue_scripts', array($this,'admin_assets'));
        add_action('wp_enqueue_scripts', array($this,'frontend_assets'));
        add_action('wp_ajax_cisai_fetch_instagram', array($this,'ajax_fetch_instagram'));
        add_action('wp_ajax_cisai_save_mapping', array($this,'ajax_save_mapping'));
        add_action('wp_ajax_cisai_get_product_data', array($this,'ajax_get_product_data'));
        add_action('wp_ajax_nopriv_cisai_get_product_data', array($this,'ajax_get_product_data'));
        add_shortcode('cisai_instagram_feed', array($this,'shortcode_feed'));
        add_action('plugins_loaded', array($this,'maybe_woocommerce_check'));
    }

    public function maybe_woocommerce_check(){
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function(){
                echo '<div class="notice notice-warning"><p><strong>CISAI Instagram Feed:</strong> WooCommerce not found — plugin requires WooCommerce.</p></div>';
            });
        }
    }

    public function admin_menu(){
        add_submenu_page(
            'woocommerce',
            'CISAI Instagram Feed',
            'Instagram Feed',
            'manage_options',
            'cisai-instagram-feed',
            array($this,'admin_page')
        );
    }

    public function register_settings(){
        register_setting('cisai_instagram_group', $this->option_key);
    }

    public function admin_assets($hook){
        if ($hook !== 'woocommerce_page_cisai-instagram-feed') return;
        
        // Inline CSS
        wp_add_inline_style('wp-admin', $this->get_admin_css());
        
        // Inline JS
        wp_enqueue_script('cisai-admin-js', '', array('jquery'), null, true);
        wp_add_inline_script('cisai-admin-js', $this->get_admin_js());
        wp_localize_script('cisai-admin-js','cisaiAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cisai_admin_nonce'),
            'mapping_key' => $this->mapping_key,
            'transient_key' => $this->transient_key,
        ));
        wp_enqueue_media();
    }

    public function frontend_assets(){
        // Swiper.js for slider
        wp_enqueue_style('swiper-css','https://unpkg.com/swiper/swiper-bundle.min.css');
        wp_enqueue_script('swiper-js','https://unpkg.com/swiper/swiper-bundle.min.js', array(), null, true);

        // Inline frontend CSS
        wp_add_inline_style('swiper-css', $this->get_frontend_css());
        
        // Inline frontend JS
        wp_enqueue_script('cisai-frontend-js', '', array('jquery','swiper-js'), null, true);
        wp_add_inline_script('cisai-frontend-js', $this->get_frontend_js());
        wp_localize_script('cisai-frontend-js', 'cisaiFront', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cisai_front_nonce'),
            'siteurl' => get_site_url(),
        ));
    }

    // Admin page HTML
    public function admin_page(){
        $settings = get_option($this->option_key, array('access_token' => ''));
        $mapping = get_option($this->mapping_key, array());
        ?>
        <div class="wrap">
            <h1>CISAI Instagram Feed <small style="font-weight:normal">— Designed by Adarsh Singh</small></h1>
            <form method="post" action="options.php">
                <?php settings_fields('cisai_instagram_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Instagram Access Token</th>
                        <td>
                            <input type="text" name="<?php echo esc_attr($this->option_key); ?>[access_token]" value="<?php echo esc_attr($settings['access_token'] ?? ''); ?>" style="width:60%">
                            <p class="description">Use Instagram Basic Display API Access Token. <strong>Note:</strong> token refresh is required periodically. </p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th>Settings</th>
                        <td>
                            <label><input type="checkbox" name="<?php echo esc_attr($this->option_key); ?>[autoplay_videos]" <?php checked(!empty($settings['autoplay_videos'])); ?>> Autoplay videos in popup (muted)</label><br>
                            <label><input type="checkbox" name="<?php echo esc_attr($this->option_key); ?>[show_likes]" <?php checked(!empty($settings['show_likes'])); ?>> Show likes count in popup (if available)</label>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>

            <h2>Instagram Posts</h2>
            <p><button id="cisai-fetch-posts" class="button button-primary">Fetch Latest Posts</button> <span id="cisai-fetch-status"></span></p>
            <div id="cisai-instagram-grid" class="cisai-grid">
                <?php
                $cached = get_transient($this->transient_key);
                if ($cached && is_array($cached)) {
                    foreach ($cached as $item) $this->render_admin_item($item, $mapping);
                } else {
                    echo '<p>No posts cached yet. Click <strong>Fetch Latest Posts</strong>.</p>';
                }
                ?>
            </div>

            <p><em>Tip:</em> Click a post's checkbox to enable it in the feed, and use the product dropdown to link it to a WooCommerce product. Click <strong>Save Selections</strong> when finished.</p>
            <p><button id="cisai-save-mapping" class="button button-primary">Save Selections</button> <span id="cisai-save-status"></span></p>
        </div>
        <?php
    }

    private function render_admin_item($item, $mapping){
        $id = esc_attr($item['id']);
        $media_url = esc_url($item['media_url']);
        $thumb = esc_url($item['thumbnail_url'] ?? $item['media_url']);
        $type = esc_attr($item['media_type']);
        $caption = esc_html(wp_trim_words($item['caption'] ?? '', 12, '...'));
        $selected = isset($mapping[$id]) ? $mapping[$id] : array('enabled' => 0, 'product_id' => '', 'label' => '');
        ?>
        <div class="cisai-item" data-id="<?php echo $id; ?>">
            <div class="cisai-thumb">
                <?php if ($type === 'VIDEO'): ?>
                    <div class="cisai-video-thumb"><video src="<?php echo $media_url; ?>" muted preload="metadata" playsinline></video></div>
                <?php else: ?>
                    <img src="<?php echo $thumb; ?>" alt="">
                <?php endif; ?>
            </div>
            <div class="cisai-meta">
                <p class="cisai-caption"><?php echo $caption; ?></p>
                <p><a href="<?php echo esc_url($item['permalink']); ?>" target="_blank">View on Instagram</a></p>
                <p>
                    <label><input type="checkbox" class="cisai-enable" <?php checked(!empty($selected['enabled'])); ?>> Enable</label>
                </p>
                <p>
                    <select class="cisai-product-select">
                        <option value="">— Link to product (optional) —</option>
                        <?php
                        $products = wc_get_products(array('limit'=> -1,'status'=>'publish'));
                        foreach ($products as $p) {
                            $sel = ($p->get_id() == ($selected['product_id'] ?? '')) ? 'selected' : '';
                            echo "<option value=\"{$p->get_id()}\" {$sel}>".esc_html($p->get_name())."</option>";
                        }
                        ?>
                    </select>
                </p>
                <p><input type="text" class="cisai-custom-label" placeholder="Button label (e.g. Shop Now)" value="<?php echo esc_attr($selected['label'] ?? ''); ?>"></p>
            </div>
        </div>
        <?php
    }

    // AJAX: fetch instagram posts
    public function ajax_fetch_instagram(){
        check_ajax_referer('cisai_admin_nonce','nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permission denied');

        $settings = get_option($this->option_key, array());
        $token = $settings['access_token'] ?? '';
        if (empty($token)) wp_send_json_error('Missing Access Token. Save settings first.');

        $endpoint = "https://graph.instagram.com/me/media?fields=id,caption,media_url,media_type,thumbnail_url,permalink&access_token=" . rawurlencode($token);
        $resp = wp_remote_get($endpoint, array('timeout'=>20));
        if (is_wp_error($resp)) wp_send_json_error($resp->get_error_message());
        $body = wp_remote_retrieve_body($resp);
        $json = json_decode($body, true);
        if (empty($json['data'])) wp_send_json_error('No data returned');

        $items = array();
        foreach ($json['data'] as $d){
            $items[] = array(
                'id' => sanitize_text_field($d['id'] ?? ''),
                'caption' => sanitize_text_field($d['caption'] ?? ''),
                'media_url' => esc_url_raw($d['media_url'] ?? ($d['thumbnail_url'] ?? '')),
                'media_type' => sanitize_text_field($d['media_type'] ?? 'IMAGE'),
                'thumbnail_url' => esc_url_raw($d['thumbnail_url'] ?? ($d['media_url'] ?? '')),
                'permalink' => esc_url_raw($d['permalink'] ?? ''),
            );
        }

        set_transient($this->transient_key, $items, HOUR_IN_SECONDS);
        wp_send_json_success($items);
    }

    // AJAX: save mapping
    public function ajax_save_mapping(){
        check_ajax_referer('cisai_admin_nonce','nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permission denied');

        $data = isset($_POST['mapping']) ? wp_unslash($_POST['mapping']) : array();
        $clean = array();
        if (is_array($data)) {
            foreach ($data as $mid => $rec) {
                $mid_s = sanitize_text_field($mid);
                $enabled = !empty($rec['enabled']) ? 1 : 0;
                $product_id = !empty($rec['product_id']) ? intval($rec['product_id']) : '';
                $label = !empty($rec['label']) ? sanitize_text_field($rec['label']) : '';
                $clean[$mid_s] = array('enabled'=>$enabled,'product_id'=>$product_id,'label'=>$label);
            }
        }
        update_option($this->mapping_key, $clean);
        wp_send_json_success('Saved');
    }

    // AJAX: get product data
    public function ajax_get_product_data() {
        check_ajax_referer('cisai_front_nonce', 'nonce');
        
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        if (!$product_id) wp_send_json_error('Invalid product ID');
        
        $product = wc_get_product($product_id);
        if (!$product) wp_send_json_error('Product not found');
        
        $data = array(
            'name' => $product->get_name(),
            'price' => $product->get_price_html(),
            'description' => wp_trim_words($product->get_short_description() ?: $product->get_description(), 30),
            'url' => get_permalink($product_id),
            'button_label' => 'Shop Now'
        );
        
        wp_send_json_success($data);
    }

    // Shortcode render
    public function shortcode_feed($atts){
        $atts = shortcode_atts(array('style'=>'default','limit'=>12), $atts, 'cisai_instagram_feed');
        $mapping = get_option($this->mapping_key, array());
        $cached = get_transient($this->transient_key);
        if (empty($cached) || !is_array($cached)) return '<p>No Instagram posts available. Fetch from admin.</p>';

        $slides = array();
        foreach ($cached as $item){
            $id = $item['id'];
            if (!empty($mapping[$id]) && !empty($mapping[$id]['enabled'])) {
                $entry = $item;
                $entry['product_id'] = $mapping[$id]['product_id'] ?? '';
                $entry['label'] = $mapping[$id]['label'] ?: 'Shop Now';
                $slides[] = $entry;
                if (count($slides) >= intval($atts['limit'])) break;
            }
        }

        if (empty($slides)) return '<p>No selected Instagram posts to show.</p>';

        ob_start();
        ?>
        <div class="cisai-swiper-container">
            <div class="swiper cisai-swiper">
                <div class="swiper-wrapper">
                    <?php foreach ($slides as $s): 
                        $is_video = strtoupper($s['media_type']) === 'VIDEO';
                        $thumb = esc_url($s['thumbnail_url'] ?? $s['media_url']);
                        $media = esc_url($s['media_url']);
                        $pid = intval($s['product_id']);
                        $purl = $pid ? get_permalink($pid) : '#';
                    ?>
                        <div class="swiper-slide cisai-slide" data-media="<?php echo esc_attr($media); ?>" data-media-type="<?php echo esc_attr($s['media_type']); ?>" data-product="<?php echo esc_attr($pid); ?>">
                            <?php if ($is_video): ?>
                                <div class="cisai-slide-media">
                                    <video src="<?php echo $media; ?>" preload="metadata" playsinline muted loop></video>
                                </div>
                            <?php else: ?>
                                <div class="cisai-slide-media">
                                    <img src="<?php echo $thumb; ?>" alt="">
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="cisai-swiper-button-prev"></div>
                <div class="cisai-swiper-button-next"></div>
                <div class="cisai-swiper-pagination"></div>
            </div>
        </div>

        <div id="cisai-popup" class="cisai-popup" style="display:none;">
            <div class="cisai-popup-inner">
                <button class="cisai-popup-close">&times;</button>
                <div class="cisai-popup-media"></div>
                <div class="cisai-popup-info">
                    <h3 class="cisai-popup-title"></h3>
                    <p class="cisai-popup-price"></p>
                    <p class="cisai-popup-caption"></p>
                    <div class="cisai-popup-actions">
                        <a href="#" class="button cisai-shop-btn" target="_blank"></a>
                        <button class="button cisai-addcart-btn">Add to cart</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // Admin CSS
    private function get_admin_css() {
        return "
        .cisai-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin: 30px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .cisai-item {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .cisai-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .cisai-thumb {
            width: 100%;
            height: 200px;
            overflow: hidden;
            border-radius: 6px;
            background: #f0f0f0;
            margin-bottom: 15px;
            position: relative;
        }
        .cisai-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .cisai-video-thumb {
            width: 100%;
            height: 100%;
            position: relative;
        }
        .cisai-video-thumb::after {
            content: '▶';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 50px;
            height: 50px;
            background: rgba(0,0,0,0.7);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            pointer-events: none;
        }
        .cisai-video-thumb video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .cisai-meta {
            font-size: 13px;
        }
        .cisai-caption {
            font-weight: 600;
            margin: 0 0 10px 0;
            color: #333;
            line-height: 1.4;
        }
        .cisai-meta p {
            margin: 8px 0;
        }
        .cisai-meta a {
            color: #2271b1;
            text-decoration: none;
        }
        .cisai-meta a:hover {
            text-decoration: underline;
        }
        .cisai-enable {
            margin-right: 5px;
        }
        .cisai-product-select,
        .cisai-custom-label {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }
        .cisai-custom-label {
            margin-top: 5px;
        }
        #cisai-fetch-status,
        #cisai-save-status {
            display: inline-block;
            margin-left: 10px;
            font-weight: 600;
        }
        #cisai-fetch-status.loading,
        #cisai-save-status.loading {
            color: #2271b1;
        }
        #cisai-fetch-status.success,
        #cisai-save-status.success {
            color: #00a32a;
        }
        #cisai-fetch-status.error,
        #cisai-save-status.error {
            color: #d63638;
        }
        ";
    }

    // Admin JS
    private function get_admin_js() {
        return "
        jQuery(document).ready(function($) {
            $('#cisai-fetch-posts').on('click', function(e) {
                e.preventDefault();
                var btn = $(this);
                var status = $('#cisai-fetch-status');
                
                btn.prop('disabled', true).text('Fetching...');
                status.removeClass('success error').addClass('loading').text('Loading...');
                
                $.ajax({
                    url: cisaiAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cisai_fetch_instagram',
                        nonce: cisaiAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            status.removeClass('loading').addClass('success').text('✓ Posts fetched successfully!');
                            setTimeout(function() { location.reload(); }, 1500);
                        } else {
                            status.removeClass('loading').addClass('error').text('✗ Error: ' + (response.data || 'Unknown error'));
                        }
                    },
                    error: function() {
                        status.removeClass('loading').addClass('error').text('✗ Request failed');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Fetch Latest Posts');
                    }
                });
            });
            
            $('#cisai-save-mapping').on('click', function(e) {
                e.preventDefault();
                var btn = $(this);
                var status = $('#cisai-save-status');
                
                var mapping = {};
                $('.cisai-item').each(function() {
                    var item = $(this);
                    var id = item.data('id');
                    mapping[id] = {
                        enabled: item.find('.cisai-enable').is(':checked') ? 1 : 0,
                        product_id: item.find('.cisai-product-select').val(),
                        label: item.find('.cisai-custom-label').val()
                    };
                });
                
                btn.prop('disabled', true).text('Saving...');
                status.removeClass('success error').addClass('loading').text('Saving...');
                
                $.ajax({
                    url: cisaiAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cisai_save_mapping',
                        nonce: cisaiAdmin.nonce,
                        mapping: mapping
                    },
                    success: function(response) {
                        if (response.success) {
                            status.removeClass('loading').addClass('success').text('✓ Saved successfully!');
                            setTimeout(function() {
                                status.text('');
                            }, 3000);
                        } else {
                            status.removeClass('loading').addClass('error').text('✗ Error saving');
                        }
                    },
                    error: function() {
                        status.removeClass('loading').addClass('error').text('✗ Request failed');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Save Selections');
                    }
                });
            });
        });
        ";
    }

    // Frontend CSS
    private function get_frontend_css() {
        return "
        .cisai-swiper-container {
            position: relative;
            width: 100%;
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 50px;
        }
        .cisai-swiper {
            width: 100%;
            height: auto;
            overflow: hidden;
        }
        .cisai-slide {
            position: relative;
            cursor: pointer;
            border-radius: 12px;
            overflow: hidden;
            aspect-ratio: 1;
            background: #f0f0f0;
            transition: transform 0.3s ease;
        }
        .cisai-slide:hover {
            transform: scale(1.02);
        }
        .cisai-slide-media {
            width: 100%;
            height: 100%;
            position: relative;
        }
        .cisai-slide-media img,
        .cisai-slide-media video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .cisai-slide-media video {
            background: #000;
        }
        .cisai-swiper-button-prev,
        .cisai-swiper-button-next {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            background: rgba(255,255,255,0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .cisai-swiper-button-prev:hover,
        .cisai-swiper-button-next:hover {
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .cisai-swiper-button-prev {
            left: 10px;
        }
        .cisai-swiper-button-next {
            right: 10px;
        }
        .cisai-swiper-button-prev::after,
        .cisai-swiper-button-next::after {
            content: '';
            width: 12px;
            height: 12px;
            border-top: 2px solid #333;
            border-right: 2px solid #333;
        }
        .cisai-swiper-button-prev::after {
            transform: rotate(-135deg);
            margin-left: 4px;
        }
        .cisai-swiper-button-next::after {
            transform: rotate(45deg);
            margin-right: 4px;
        }
        .cisai-swiper-pagination {
            position: relative;
            margin-top: 20px;
            text-align: center;
        }
        .cisai-swiper-pagination .swiper-pagination-bullet {
            width: 10px;
            height: 10px;
            background: #ccc;
            opacity: 1;
            margin: 0 5px;
            transition: all 0.3s ease;
        }
        .cisai-swiper-pagination .swiper-pagination-bullet-active {
            background: #333;
            width: 24px;
            border-radius: 5px;
        }
        .cisai-popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.85);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .cisai-popup-inner {
            background: white;
            border-radius: 12px;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            position: relative;
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .cisai-popup-close {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 36px;
            height: 36px;
            background: rgba(0,0,0,0.6);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease;
        }
        .cisai-popup-close:hover {
            background: rgba(0,0,0,0.8);
        }
        .cisai-popup-media {
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .cisai-popup-media img,
        .cisai-popup-media video {
            width: 100%;
            height: 100%;
            object-fit: contain;
            max-height: 90vh;
        }
        .cisai-popup-info {
            padding: 30px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .cisai-popup-title {
            font-size: 24px;
            margin: 0 0 15px 0;
            color: #333;
        }
        .cisai-popup-price {
            font-size: 20px;
            font-weight: bold;
            color: #27ae60;
            margin: 0 0 15px 0;
        }
        .cisai-popup-caption {
            font-size: 14px;
            line-height: 1.6;
            color: #666;
            margin: 0 0 25px 0;
            flex-grow: 1;
        }
        .cisai-popup-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }
        .cisai-popup-actions .button {
            flex: 1;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .cisai-shop-btn {
            background: #3498db;
            color: white !important;
            border: 2px solid #3498db;
        }
        .cisai-shop-btn:hover {
            background: #2980b9;
            border-color: #2980b9;
        }
        .cisai-addcart-btn {
            background: #27ae60;
            color: white;
            border: 2px solid #27ae60;
        }
        .cisai-addcart-btn:hover {
            background: #229954;
            border-color: #229954;
        }
        @media (max-width: 768px) {
            .cisai-swiper-container {
                padding: 0 40px;
            }
            .cisai-popup-inner {
                grid-template-columns: 1fr;
                max-height: 95vh;
            }
            .cisai-popup-media {
                max-height: 50vh;
            }
            .cisai-popup-info {
                padding: 20px;
            }
            .cisai-popup-actions {
                flex-direction: column;
            }
            .cisai-swiper-button-prev,
            .cisai-swiper-button-next {
                width: 36px;
                height: 36px;
            }
            .cisai-swiper-button-prev {
                left: 5px;
            }
            .cisai-swiper-button-next {
                right: 5px;
            }
        }
        ";
    }

    // Frontend JS
    private function get_frontend_js() {
        return "
        jQuery(document).ready(function($) {
            if ($('.cisai-swiper').length > 0) {
                var swiper = new Swiper('.cisai-swiper', {
                    slidesPerView: 1,
                    spaceBetween: 20,
                    loop: true,
                    autoplay: {
                        delay: 4000,
                        disableOnInteraction: false,
                    },
                    navigation: {
                        nextEl: '.cisai-swiper-button-next',
                        prevEl: '.cisai-swiper-button-prev',
                    },
                    pagination: {
                        el: '.cisai-swiper-pagination',
                        clickable: true,
                    },
                    breakpoints: {
                        640: {
                            slidesPerView: 2,
                            spaceBetween: 20,
                        },
                        768: {
                            slidesPerView: 3,
                            spaceBetween: 25,
                        },
                        1024: {
                            slidesPerView: 4,
                            spaceBetween: 30,
                        },
                    }
                });
            }
            
            $(document).on('click', '.cisai-slide', function() {
                var slide = $(this);
                var mediaUrl = slide.data('media');
                var mediaType = slide.data('media-type');
                var productId = slide.data('product');
                
                if (!productId) {
                    return;
                }
                
                var popup = $('#cisai-popup');
                var media = popup.find('.cisai-popup-media');
                
                media.empty();
                
                if (mediaType === 'VIDEO') {
                    var video = $('<video>', {
                        src: mediaUrl,
                        controls: true,
                        autoplay: true,
                        loop: true,
                        playsinline: true
                    });
                    media.append(video);
                } else {
                    var img = $('<img>', {
                        src: mediaUrl,
                        alt: 'Instagram post'
                    });
                    media.append(img);
                }
                
                loadProductData(productId, popup);
                
                popup.fadeIn(300);
                $('body').css('overflow', 'hidden');
            });
            
            $(document).on('click', '.cisai-popup-close, .cisai-popup', function(e) {
                if (e.target === this) {
                    closePopup();
                }
            });
            
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#cisai-popup').is(':visible')) {
                    closePopup();
                }
            });
            
            $(document).on('click', '.cisai-addcart-btn', function(e) {
                e.preventDefault();
                var productId = $(this).data('product-id');
                
                if (!productId) return;
                
                var btn = $(this);
                var originalText = btn.text();
                btn.prop('disabled', true).text('Adding...');
                
                $.ajax({
                    url: cisaiFront.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'woocommerce_add_to_cart',
                        product_id: productId,
                        quantity: 1
                    },
                    success: function(response) {
                        btn.text('✓ Added!');
                        
                        $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash]);
                        
                        setTimeout(function() {
                            btn.prop('disabled', false).text(originalText);
                        }, 2000);
                    },
                    error: function() {
                        btn.text('Error').prop('disabled', false);
                        setTimeout(function() {
                            btn.text(originalText);
                        }, 2000);
                    }
                });
            });
            
            function loadProductData(productId, popup) {
                $.ajax({
                    url: cisaiFront.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cisai_get_product_data',
                        product_id: productId,
                        nonce: cisaiFront.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            var product = response.data;
                            
                            popup.find('.cisai-popup-title').text(product.name);
                            popup.find('.cisai-popup-price').html(product.price);
                            popup.find('.cisai-popup-caption').text(product.description || '');
                            
                            var shopBtn = popup.find('.cisai-shop-btn');
                            shopBtn.attr('href', product.url).text(product.button_label || 'Shop Now');
                            
                            var cartBtn = popup.find('.cisai-addcart-btn');
                            cartBtn.data('product-id', productId);
                        }
                    }
                });
            }
            
            function closePopup() {
                var popup = $('#cisai-popup');
                popup.fadeOut(300);
                $('body').css('overflow', '');
                
                popup.find('video').each(function() {
                    this.pause();
                    this.currentTime = 0;
                });
            }
        });
        ";
    }
}

new CISAI_Instagram_Feed();