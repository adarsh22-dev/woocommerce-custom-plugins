<?php
/**
 * Plugin Name: CISAI Enhanced Floating WC Product Button
 * Plugin URI: https://yoursite.com
 * Description: Beautiful floating button with hierarchical WooCommerce categories, products list, and optional lead capture form. Fully customizable colors, alignment, and messages. Mobile responsive.
 * Version: 2.3
 * Author: Your Name
 * Author URI: https://yoursite.com
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * License: GPL v2 or later
 * Text Domain: floating-wc-button
 */
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
// Plugin constants
define('FWB_VERSION', '2.3');
define('FWB_PATH', plugin_dir_path(__FILE__));
define('FWB_URL', plugin_dir_url(__FILE__));
/**
 * ============================================================================
 * ADMIN SETTINGS PAGE
 * ============================================================================
 */
add_action('admin_menu', 'fwb_admin_menu');
function fwb_admin_menu() {
    add_options_page(
        'Floating Product Button Settings',
        'Floating Button',
        'manage_options',
        'fwb-settings',
        'fwb_settings_page'
    );
}
function fwb_settings_page() {
    // Handle form submission
    if (isset($_POST['fwb_submit']) && check_admin_referer('fwb_save_settings', 'fwb_nonce')) {
        update_option('fwb_button_color', sanitize_hex_color($_POST['button_color']));
        update_option('fwb_button_text_color', sanitize_hex_color($_POST['button_text_color']));
        update_option('fwb_button_align', sanitize_text_field($_POST['button_align']));
        update_option('fwb_button_icon', sanitize_text_field($_POST['button_icon']));
        update_option('fwb_enable_message', isset($_POST['enable_message']) ? 1 : 0);
        update_option('fwb_custom_message', sanitize_textarea_field($_POST['custom_message']));
        update_option('fwb_message_title', sanitize_text_field($_POST['message_title']));
        update_option('fwb_dropdown_width', intval($_POST['dropdown_width']));
        update_option('fwb_products_per_page', intval($_POST['products_per_page']));
       
        echo '<div class="notice notice-success is-dismissible"><p><strong>Settings saved successfully!</strong></p></div>';
    }
   
    // Get current settings
    $color = get_option('fwb_button_color', '#2271b1');
    $text_color = get_option('fwb_button_text_color', '#ffffff');
    $align = get_option('fwb_button_align', 'right');
    $icon = get_option('fwb_button_icon', '🛒');
    $enable_msg = get_option('fwb_enable_message', 0);
    $msg_text = get_option('fwb_custom_message', 'Welcome! Please share your details so we can assist you better.');
    $msg_title = get_option('fwb_message_title', 'Get Personalized Help');
    $dropdown_width = get_option('fwb_dropdown_width', 400);
    $products_per_page = get_option('fwb_products_per_page', 12);
    ?>
    <div class="wrap">
        <h1>🛒 Floating Product Button Settings</h1>
        <p>Customize your floating button appearance and behavior.</p>
       
        <form method="post" action="">
            <?php wp_nonce_field('fwb_save_settings', 'fwb_nonce'); ?>
           
            <table class="form-table" role="presentation">
                <tbody>
                    <!-- Button Appearance -->
                    <tr>
                        <th colspan="2"><h2>Button Appearance</h2></th>
                    </tr>
                    <tr>
                        <th scope="row"><label for="button_color">Button Color</label></th>
                        <td>
                            <input type="color" id="button_color" name="button_color" value="<?php echo esc_attr($color); ?>" />
                            <p class="description">Choose the background color for the floating button.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="button_text_color">Icon/Text Color</label></th>
                        <td>
                            <input type="color" id="button_text_color" name="button_text_color" value="<?php echo esc_attr($text_color); ?>" />
                            <p class="description">Choose the icon/text color for the button.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="button_icon">Button Icon</label></th>
                        <td>
                            <input type="text" id="button_icon" name="button_icon" value="<?php echo esc_attr($icon); ?>" class="regular-text" maxlength="4" />
                            <p class="description">Enter an emoji or text (e.g., 🛒, 🛍️, 📦, SHOP)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Button Position</label></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="button_align" value="left" <?php checked($align, 'left'); ?> />
                                    Left
                                </label><br>
                                <label>
                                    <input type="radio" name="button_align" value="center" <?php checked($align, 'center'); ?> />
                                    Center
                                </label><br>
                                <label>
                                    <input type="radio" name="button_align" value="right" <?php checked($align, 'right'); ?> />
                                    Right
                                </label>
                            </fieldset>
                            <p class="description">Position of the floating button on desktop. Mobile will auto-adjust.</p>
                        </td>
                    </tr>
                   
                    <!-- Custom Message -->
                    <tr>
                        <th colspan="2"><h2>Lead Capture Form</h2></th>
                    </tr>
                    <tr>
                        <th scope="row"><label for="enable_message">Enable Custom Message</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="enable_message" name="enable_message" value="1" <?php checked($enable_msg, 1); ?> />
                                Show a form before displaying categories
                            </label>
                            <p class="description">If enabled, users will see a lead capture form when clicking the button.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="message_title">Form Title</label></th>
                        <td>
                            <input type="text" id="message_title" name="message_title" value="<?php echo esc_attr($msg_title); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="custom_message">Form Message</label></th>
                        <td>
                            <textarea id="custom_message" name="custom_message" rows="3" class="large-text"><?php echo esc_textarea($msg_text); ?></textarea>
                            <p class="description">This message will appear above the form fields.</p>
                        </td>
                    </tr>
                   
                    <!-- Dropdown Settings -->
                    <tr>
                        <th colspan="2"><h2>Dropdown Settings</h2></th>
                    </tr>
                    <tr>
                        <th scope="row"><label for="dropdown_width">Dropdown Width (px)</label></th>
                        <td>
                            <input type="number" id="dropdown_width" name="dropdown_width" value="<?php echo esc_attr($dropdown_width); ?>" min="300" max="800" step="10" />
                            <p class="description">Width of the categories/products dropdown (300-800px). Mobile will auto-adjust to full width.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="products_per_page">Products Per Category</label></th>
                        <td>
                            <input type="number" id="products_per_page" name="products_per_page" value="<?php echo esc_attr($products_per_page); ?>" min="4" max="50" />
                            <p class="description">Number of products to show when a category is clicked (4-50).</p>
                        </td>
                    </tr>
                </tbody>
            </table>
           
            <?php submit_button('Save Settings', 'primary', 'fwb_submit'); ?>
        </form>
       
        <hr>
        <div class="fwb-help-section">
            <h3>📖 How to Use</h3>
            <ol>
                <li><strong>Customize</strong> button colors and position above</li>
                <li><strong>Enable lead capture</strong> to collect customer info before showing products</li>
                <li><strong>The button</strong> will appear on all pages automatically</li>
                <li><strong>Categories</strong> load level-by-level with smooth right slide transitions, back navigation, and arrows (→ for subcategories)</li>
                <li><strong>Empty categories</strong> are hidden (no products)</li>
                <li><strong>Products</strong> display in vertical cards with image, name, price, stock status, and View/Add to Cart buttons (Add to Cart hidden for out of stock)</li>
                <li><strong>Mobile responsive</strong>: Full-width dropdown, smaller button, auto-scroll</li>
            </ol>
           
            <h3>🐛 Troubleshooting</h3>
            <ul>
                <li>If dropdown doesn't open: Check browser console (F12) for JavaScript errors</li>
                <li>Clear site cache after changing settings</li>
                <li>Ensure WooCommerce is active and has products</li>
            </ul>
        </div>
    </div>
   
    <style>
        .fwb-help-section {
            background: #f0f0f1;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .fwb-help-section h3 {
            margin-top: 0;
        }
    </style>
    <?php
}
/**
 * ============================================================================
 * FRONTEND ASSETS (CSS + JavaScript)
 * ============================================================================
 */
add_action('wp_enqueue_scripts', 'fwb_enqueue_assets');
function fwb_enqueue_assets() {
    if (is_admin()) {
        return;
    }
   
    // Ensure jQuery is loaded
    wp_enqueue_script('jquery');
   
    // Register dummy handle for inline styles
    wp_register_style('fwb-styles', false);
    wp_enqueue_style('fwb-styles');
   
    // Get settings for CSS
    $button_color = get_option('fwb_button_color', '#2271b1');
    $text_color = get_option('fwb_button_text_color', '#ffffff');
    $dropdown_width = get_option('fwb_dropdown_width', 400);
   
    // Inline CSS
    $css = "
    /* === Floating Button Container === */
    #fwb-container {
        position: fixed;
        bottom: 20px;
        z-index: 999999;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }
   
    /* === Floating Button === */
    #fwb-button {
    padding:10px;
        background: {$button_color};
        color: {$text_color};
        width: auto;
        height: 50px;
        border: none;
        border-radius: 50%;
        font-size: 28px;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    #fwb-button:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 25px rgba(0,0,0,0.4);
    }
    #fwb-button:active {
        transform: scale(0.95);
    }
   
    /* === Message Modal === */
    #fwb-message-modal {
        display: none;
        position: absolute;
        bottom: 80px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        padding: 25px;
        width: 320px;
        animation: slideUp 0.3s ease;
        max-height: 80vh;
        overflow-y: auto;
    }
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    #fwb-message-modal h4 {
        margin: 0 0 10px 0;
        font-size: 18px;
        color: #333;
    }
    #fwb-message-modal p {
        margin: 0 0 15px 0;
        font-size: 14px;
        color: #666;
        line-height: 1.5;
    }
    #fwb-message-modal input {
        width: 100%;
        padding: 10px;
        margin-bottom: 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        box-sizing: border-box;
        transition: border-color 0.2s;
    }
    #fwb-message-modal input:focus {
        outline: none;
        border-color: {$button_color};
    }
    #fwb-message-modal button {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
        margin-right: 8px;
    }
    #fwb-message-modal button[type='submit'] {
        background: {$button_color};
        color: white;
    }
    #fwb-message-modal button[type='submit']:hover {
        opacity: 0.9;
    }
    #fwb-message-modal button[type='button'] {
        background: #f0f0f0;
        color: #666;
    }
    #fwb-message-modal button[type='button']:hover {
        background: #e0e0e0;
    }
   
    /* === Dropdown Panel === */
    #fwb-dropdown {
        display: none;
        position: absolute;
        bottom: 80px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        width: {$dropdown_width}px;
        max-height: 70vh;
        overflow: hidden;
        animation: slideUp 0.3s ease;
    }
    #fwb-dropdown-header {
        background: linear-gradient(135deg, {$button_color}, " . fwb_adjust_brightness($button_color, -20) . ");
        color: white;
        padding: 15px 10px;
        display: flex;
        align-items: center;
        border-radius: 12px 12px 0 0;
    }
    #fwb-back {
        display: none;
        background: rgba(255,255,255,0.2);
        border: none;
        color: white;
        padding: 4px 4px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        line-height: 1;
        transition: background 0.2s;
        order: 1;
        margin-right: 5px;
        flex-shrink: 0;
    }
    #fwb-back:hover {
        background: rgba(255,255,255,0.3);
    }
    #fwb-breadcrumb {
        flex: 1;
        text-align: center;
        font-weight: 600;
        font-size: 12px;
        order: 2;
        margin: 0 5px;
    }
    #fwb-close-dropdown {
        background: rgba(255,255,255,0.2);
        border: none;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 20px;
        line-height: 1;
        transition: background 0.2s;
        order: 3;
        margin-left: auto;
        flex-shrink: 0;
    }
    #fwb-close-dropdown:hover {
        background: rgba(255,255,255,0.3);
    }
    #fwb-dropdown-content {
        position: relative;
        overflow-y: auto;
        max-height: calc(70vh - 70px);
        height:400px;
        padding: 15px;
    }
   
    /* === Slide Transitions === */
    #fwb-categories,
    #fwb-products-panel {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        transition: transform 0.2s ease, opacity 0.2s ease;
        opacity: 1;
        transform: translateX(0);
        z-index: 1;
    }
    #fwb-loading {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.9);
        z-index: 3;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0 0 12px 12px;
    }
    .fwb-slide-out-left {
        transform: translateX(-100%) !important;
        opacity: 0 !important;
    }
   
    /* === Categories List === */
    #fwb-categories-loading {
        text-align: center;
        padding: 30px;
        color: #999;
    }
    .fwb-cat-list {
        list-style: none;
        padding: 0;
        margin: 0 0 10px 0;
    }
    .fwb-cat-item {
        margin: 4px 0;
    }
    .fwb-cat-link {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: #333;
        padding: 10px 12px;
        border-radius: 6px;
        transition: all 0.2s;
        font-size: 14px;
        cursor: pointer;
    }
    .fwb-cat-link:hover {
        background: #f5f5f5;
        color: {$button_color};
    }
    .fwb-cat-link svg {
        margin-right: 8px;
        font-size: 16px;
        width: 20px;
        height: 20px;
        flex-shrink: 0;
    }
    .fwb-cat-link .fwb-cat-name {
        flex: 1;
    }
   
    /* === Products List === */
    #fwb-products-panel {
        background: white;
    }
    #fwb-products-panel h5 {
        margin: 0 0 15px 0;
        font-size: 16px;
        color: #333;
        display: flex;
        align-items: center;
    }
    #fwb-products-panel h5 .fwb-icon {
        margin-right: 8px;
        font-size: 18px;
    }
    .fwb-products-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .fwb-product-item {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 16px;
        display: flex;
        align-items: flex-start;
        transition: all 0.2s;
        cursor: pointer;
    }
    .fwb-product-item:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .fwb-product-item a.fwb-product-link {
        display: flex;
        align-items: flex-start;
        width: 100%;
        text-decoration: none;
        color: inherit;
    }
    .fwb-product-item img {
        width: 100px;
        height: auto;
        object-fit: cover;
        border-radius: 6px;
        margin-right: 12px;
        flex-shrink: 0;
    }
    .fwb-product-item .fwb-product-details {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .fwb-product-item .product-info {
        flex-grow: 1;
    }
    .fwb-product-item h6 {
        margin: 0 0 2px 0;
        font-size: 14px;
        color: #333;
        line-height: 1.4;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    .fwb-product-item h6:hover {
        color: {$button_color};
    }
    .fwb-product-item .price {
        font-size: 16px;
        font-weight: 600;
        margin: 2px 0;
        text-align: left;
    }
    .fwb-product-item .price ins {
        color: {$button_color};
        text-decoration: none;
        font-weight: 600;
    }
    .fwb-product-item .price del {
        color: #999;
        font-weight: normal;
        margin-right: 8px;
        font-size: 14px;
    }
    .fwb-product-item .stock-status {
        margin: 2px 0;
        font-size: 14px;
        text-align: left;
    }
   
    /* === Empty States === */
    .fwb-empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #999;
    }
    .fwb-empty-state-icon {
        font-size: 48px;
        margin-bottom: 10px;
    }
   
    /* === Loading Spinner === */
    .fwb-spinner {
        border: 3px solid #f3f3f3;
        border-top: 3px solid {$button_color};
        border-radius: 50%;
        width: 30px;
        height: 30px;
        animation: spin 1s linear infinite;
        margin: 20px auto;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
   
    /* === Media Queries for Mobile Responsiveness === */
    @media (max-width: 768px) {
        #fwb-container {
            left: 10% !important;
            transform: translateX(-50%) !important;
            right: auto !important;
        }
        #fwb-button {
            width: 56px;
            height: 56px;
            font-size: 24px;
        }
        #fwb-dropdown,
        #fwb-message-modal {
            width: 95vw !important;
            left: 2.5vw !important;
            right: auto !important;
            max-height: 80vh;
        }
        #fwb-dropdown-content {
            max-height: calc(80vh - 70px);
        }
        .fwb-cat-link {
            font-size: 16px;
            padding: 12px;
        }
        .fwb-product-item {
            flex-direction: column;
            text-align: center;
        }
        .fwb-product-item img {
            width: 50%;
            margin-right: 0;
            margin-bottom: 12px;
        }
        .fwb-product-item .fwb-product-details {
            padding-left: 0;
        }
        .fwb-product-item a.fwb-product-link {
            flex-direction: column;
        }
            .fwb-product-item a.fwb-product-link {
    display: flex;
    flex-direction: row !important;
    align-items: flex-start;
    width: 100%;
    text-decoration: none;
    color: inherit;
}
    }
   
    @media (max-width: 480px) {
        #fwb-dropdown-content,
        #fwb-message-modal {
            padding: 12px;
        }
        .fwb-cat-link {
            padding: 14px;
            font-size: 16px;
        }
        .fwb-product-item {
            padding: 16px;
        }
    }
        #fwb-products-panel {
    padding-left: 10px;
    padding-right: 10px;
    padding-top: 10px;
}
    ";
   
    wp_add_inline_style('fwb-styles', $css);
   
    // Register dummy handle for inline script
    wp_register_script('fwb-scripts', false, array('jquery'), FWB_VERSION, true);
    wp_enqueue_script('fwb-scripts');
   
    // Localize script
    wp_localize_script('fwb-scripts', 'fwbData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('fwb_ajax_nonce'),
        'enableMessage' => get_option('fwb_enable_message', 0),
        'messageTitle' => get_option('fwb_message_title', 'Get Personalized Help'),
        'messageText' => get_option('fwb_custom_message', ''),
    ));
   
    // Inline JavaScript
    $js = "
    jQuery(document).ready(function($) {
        console.log('✅ FWB Plugin Loaded');
       
        var \$button = $('#fwb-button');
        var \$dropdown = $('#fwb-dropdown');
        var \$messageModal = $('#fwb-message-modal');
        var \$categories = $('#fwb-categories');
        var \$productsPanel = $('#fwb-products-panel');
        var \$loading = $('#fwb-loading');
        var path = [];
        var isProducts = false;
        var enableMessage = parseInt(fwbData.enableMessage);
        var TRANSITION_TIME = 150;
       
        // Update breadcrumb with icons
        function updateBreadcrumb() {
            if (path.length === 0) {
                $('#fwb-breadcrumb').html('📁 Shop Categories');
                return;
            }
            var pathText = path.map(function(p, i) {
                var icon = (i === path.length - 1 && !isProducts) ? '📂 ' : '';
                return icon + p.name;
            }).join(' > ');
            var bcText = '📁 Shop Categories > ' + pathText;
            if (isProducts) {
                bcText += ' > 📦 Products';
            }
            $('#fwb-breadcrumb').html(bcText);
        }
       
        // Bind category click events
        function bindCategoryEvents() {
            $('.fwb-cat-link').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
               
                var catId = $(this).data('cat-id');
                var catName = $(this).find('.fwb-cat-name').text().trim();
               
                console.log('📁 Category clicked:', catId);
               
                path.push({id: catId, name: catName});
                loadForCat(catId);
            });
        }
       
        // Load top-level categories with transition
        function loadTopCategories() {
            console.log('📂 Loading top categories...');
            path = [];
            isProducts = false;
            updateBreadcrumb();
            $('#fwb-back').hide();
            var currentPanel = isProducts ? \$productsPanel : \$categories;
            currentPanel.addClass('fwb-slide-out-left');
            setTimeout(function() {
                currentPanel.hide().removeClass('fwb-slide-out-left');
                \$loading.show();
                $.post(fwbData.ajaxUrl, {
                    action: 'fwb_fetch_top_categories',
                    nonce: fwbData.nonce
                }, function(html) {
                    \$loading.hide();
                    console.log('✅ Top categories loaded');
                    \$categories.html(html).show().css({transform: 'translateX(100%)', opacity: 0});
                    setTimeout(function() {
                        \$categories.css({transform: 'translateX(0)', opacity: 1});
                    }, 10);
                    bindCategoryEvents();
                }).fail(function(xhr, status, error) {
                    \$loading.hide();
                    console.error('❌ Top categories error:', error);
                    \$categories.html('<div class=\"fwb-empty-state\"><div class=\"fwb-empty-state-icon\">⚠️</div><p>Error loading categories</p></div>').show().css({transform: 'translateX(100%)', opacity: 0});
                    setTimeout(function() {
                        \$categories.css({transform: 'translateX(0)', opacity: 1});
                    }, 10);
                });
            }, TRANSITION_TIME);
        }
       
        // Load content for a category with transition
        function loadForCat(catId) {
            console.log('📂 Loading for category:', catId);
            var currentPanel = isProducts ? \$productsPanel : \$categories;
            currentPanel.addClass('fwb-slide-out-left');
            setTimeout(function() {
                currentPanel.hide().removeClass('fwb-slide-out-left');
                \$loading.show();
                $.post(fwbData.ajaxUrl, {
                    action: 'fwb_load_for_category',
                    nonce: fwbData.nonce,
                    cat_id: catId
                }, function(resp) {
                    \$loading.hide();
                    console.log('✅ Category content loaded');
                    if (resp.success) {
                        var data = resp.data;
                        updateBreadcrumb();
                        $('#fwb-back').show();
                        var newPanel;
                        if (data.type === 'categories') {
                            isProducts = false;
                            newPanel = \$categories;
                            \$categories.html(data.html);
                            bindCategoryEvents();
                        } else if (data.type === 'products') {
                            isProducts = true;
                            newPanel = \$productsPanel;
                            \$productsPanel.html(data.html);
                            \$categories.hide();
                        }
                        newPanel.show().css({transform: 'translateX(100%)', opacity: 0});
                        setTimeout(function() {
                            newPanel.css({transform: 'translateX(0)', opacity: 1});
                        }, 10);
                    }
                }).fail(function(xhr, status, error) {
                    \$loading.hide();
                    console.error('❌ Category content error:', error);
                    var errHtml = '<div class=\"fwb-empty-state\"><div class=\"fwb-empty-state-icon\">⚠️</div><p>Error loading content</p></div>';
                    var newPanel = \$categories;
                    \$categories.html(errHtml).show().css({transform: 'translateX(100%)', opacity: 0});
                    setTimeout(function() {
                        newPanel.css({transform: 'translateX(0)', opacity: 1});
                    }, 10);
                    $('#fwb-back').show();
                });
            }, TRANSITION_TIME);
        }
       
        // Button click handler
        \$button.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🛒 Button clicked');
           
            if (enableMessage && !\$messageModal.data('submitted')) {
                \$messageModal.fadeIn(200);
                \$dropdown.hide();
            } else {
                toggleDropdown();
            }
        });
       
        // Toggle dropdown
        function toggleDropdown() {
            if (\$dropdown.is(':visible')) {
                \$dropdown.fadeOut(150);
                path = [];
                isProducts = false;
            } else {
                \$dropdown.fadeIn(150);
                loadTopCategories();
            }
        }
       
        // Message form submit
        $('#fwb-message-form').on('submit', function(e) {
            e.preventDefault();
            var formData = {
                action: 'fwb_submit_message',
                nonce: fwbData.nonce,
                name: $('input[name=\"fwb_name\"]').val(),
                email: $('input[name=\"fwb_email\"]').val(),
                phone: $('input[name=\"fwb_phone\"]').val()
            };
           
            console.log('📝 Submitting form:', formData);
           
            $.post(fwbData.ajaxUrl, formData, function(response) {
                console.log('✅ Form submitted:', response);
                \$messageModal.data('submitted', true).fadeOut(200);
                setTimeout(function() { toggleDropdown(); }, 250);
            }).fail(function(xhr, status, error) {
                console.error('❌ Form error:', error);
                alert('Error submitting form. Please try again.');
            });
        });
       
        // Skip message button
        $('#fwb-skip-message').on('click', function() {
            \$messageModal.data('submitted', true).fadeOut(200);
            setTimeout(function() { toggleDropdown(); }, 250);
        });
       
        // Back button
        $('#fwb-back').on('click', function() {
            path.pop();
            updateBreadcrumb();
            var currentPanel = isProducts ? \$productsPanel : \$categories;
            currentPanel.addClass('fwb-slide-out-left');
            setTimeout(function() {
                currentPanel.hide().removeClass('fwb-slide-out-left');
                \$loading.show();
                var ajaxAction = path.length === 0 ? 'fwb_fetch_top_categories' : 'fwb_load_for_category';
                var ajaxData = {
                    action: ajaxAction,
                    nonce: fwbData.nonce
                };
                if (path.length > 0) {
                    ajaxData.cat_id = path[path.length - 1].id;
                }
                $.post(fwbData.ajaxUrl, ajaxData, function(resp) {
                    \$loading.hide();
                    console.log('✅ Back content loaded');
                    var html;
                    var panelToUse = \$categories;
                    if (path.length === 0) {
                        html = resp;
                        isProducts = false;
                    } else if (resp.success) {
                        html = resp.data.html;
                        if (resp.data.type === 'products') {
                            isProducts = true;
                            panelToUse = \$productsPanel;
                            \$categories.hide();
                        } else {
                            isProducts = false;
                        }
                    } else {
                        html = '<div class=\"fwb-empty-state\"><div class=\"fwb-empty-state-icon\">⚠️</div><p>Error loading content</p></div>';
                        isProducts = false;
                    }
                    panelToUse.html(html).show().css({transform: 'translateX(100%)', opacity: 0});
                    setTimeout(function() {
                        panelToUse.css({transform: 'translateX(0)', opacity: 1});
                    }, 10);
                    if (!isProducts) {
                        bindCategoryEvents();
                    }
                    if (path.length === 0) {
                        $('#fwb-back').hide();
                    }
                }).fail(function(xhr, status, error) {
                    \$loading.hide();
                    console.error('❌ Back error:', error);
                    var errHtml = '<div class=\"fwb-empty-state\"><div class=\"fwb-empty-state-icon\">⚠️</div><p>Error loading content</p></div>';
                    \$categories.html(errHtml).show().css({transform: 'translateX(100%)', opacity: 0});
                    setTimeout(function() {
                        \$categories.css({transform: 'translateX(0)', opacity: 1});
                    }, 10);
                    if (path.length === 0) {
                        $('#fwb-back').hide();
                    }
                });
            }, TRANSITION_TIME);
        });
       
        // Close dropdown button
        $('#fwb-close-dropdown').on('click', function() {
            toggleDropdown();
        });
       
        // Click outside to close
        $(document).on('click', function(e) {
            if (!\$(e.target).closest('#fwb-container').length) {
                \$dropdown.fadeOut(150);
                \$messageModal.fadeOut(200);
                path = [];
                isProducts = false;
            }
        });
       
        // Add to Cart functionality (AJAX) - Removed buttons, so this is unused but kept for future
        $(document).on('click', '.add-to-cart-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var \$btn = $(this);
            var productId = \$btn.data('product-id');
            if (!productId) return;
           
            \$btn.prop('disabled', true).text('Adding...');
           
            $.post(fwbData.ajaxUrl, {
                action: 'woocommerce_add_to_cart',
                product_id: productId,
                nonce: fwbData.nonce
            }, function(response) {
                if (response.success) {
                    \$btn.text('Added!');
                    setTimeout(function() {
                        \$btn.text('Add to Cart').prop('disabled', false);
                    }, 2000);
                    // Trigger WooCommerce fragments update
                    $(document.body).trigger('added_to_cart', [response.data.fragments, response.data.cart_hash, \$btn]);
                } else {
                    alert(response.data.error || 'Error adding to cart');
                    \$btn.text('Add to Cart').prop('disabled', false);
                }
            }).fail(function() {
                alert('Error adding to cart');
                \$btn.text('Add to Cart').prop('disabled', false);
            });
        });
    });
    ";
   
    wp_add_inline_script('fwb-scripts', $js);
}
/**
 * ============================================================================
 * AJAX HANDLERS
 * ============================================================================
 */
// Handle message form submission
add_action('wp_ajax_fwb_submit_message', 'fwb_submit_message');
add_action('wp_ajax_nopriv_fwb_submit_message', 'fwb_submit_message');
function fwb_submit_message() {
    check_ajax_referer('fwb_ajax_nonce', 'nonce');
   
    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
   
    // Log to PHP error log (can also save to database or send email)
    error_log("FWB Lead Captured - Name: $name, Email: $email, Phone: $phone");
   
    // You can add custom logic here (save to DB, send email, etc.)
   
    wp_send_json_success(array(
        'message' => 'Thank you! Loading products for you...'
    ));
}
// Fetch top-level categories (hide empty)
add_action('wp_ajax_fwb_fetch_top_categories', 'fwb_fetch_top_categories');
add_action('wp_ajax_nopriv_fwb_fetch_top_categories', 'fwb_fetch_top_categories');
function fwb_fetch_top_categories() {
    check_ajax_referer('fwb_ajax_nonce', 'nonce');
   
    ob_start();
    $top_cats = get_terms(array(
        'taxonomy' => 'product_cat',
        'parent' => 0,
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC'
    ));
   
    $has_content = false;
    echo '<ul class="fwb-cat-list">';
    if (!is_wp_error($top_cats) && !empty($top_cats)) {
        $has_content = true;
        foreach ($top_cats as $cat) {
            echo '<li class="fwb-cat-item">
                <a href="#" class="fwb-cat-link" data-cat-id="' . esc_attr($cat->term_id) . '">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
  <path d="M8 5l8 7-8 7V5z" fill="currentColor"/>
</svg>
                    <span class="fwb-cat-name">' . esc_html($cat->name) . '</span>
                </a>
            </li>';
        }
    }
    echo '</ul>';
   
    if (!$has_content) {
        echo '<div class="fwb-empty-state"><div class="fwb-empty-state-icon">📦</div><p>No categories found</p></div>';
    }
   
    $html = ob_get_clean();
    echo $html;
    wp_die();
}
// Load content for a specific category (children or products, hide empty)
add_action('wp_ajax_fwb_load_for_category', 'fwb_load_for_category');
add_action('wp_ajax_nopriv_fwb_load_for_category', 'fwb_load_for_category');
function fwb_load_for_category() {
    check_ajax_referer('fwb_ajax_nonce', 'nonce');
   
    $cat_id = sanitize_text_field($_POST['cat_id'] ?? '');
    $per_page = get_option('fwb_products_per_page', 12);
   
    // WooCommerce category only
    $parent_id = intval($cat_id);
    $children = get_terms(array(
        'taxonomy' => 'product_cat',
        'parent' => $parent_id,
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC'
    ));
   
    if (!is_wp_error($children) && !empty($children)) {
        ob_start();
        echo '<ul class="fwb-cat-list">';
        foreach ($children as $child) {
            echo '<li class="fwb-cat-item">
                <a href="#" class="fwb-cat-link" data-cat-id="' . esc_attr($child->term_id) . '">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
  <path d="M8 5l8 7-8 7V5z" fill="currentColor"/>
</svg>
                    <span class="fwb-cat-name">' . esc_html($child->name) . '</span>
                </a>
            </li>';
        }
        echo '</ul>';
        $html = ob_get_clean();
        wp_send_json_success(array('type' => 'categories', 'html' => $html));
    } else {
        // No children, load products (only in-stock)
        $term = get_term($parent_id, 'product_cat');
        $cat_name = $term ? $term->name : 'Category';
       
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $per_page,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_stock_status',
                    'value' => 'instock',
                    'compare' => '='
                )
            ),
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $parent_id,
                ),
            ),
            'orderby' => 'menu_order title',
            'order' => 'ASC'
        );
       
        ob_start();
        $query = new WP_Query($args);
        echo '<h5><span class="fwb-icon">📦</span>Found ' . $query->found_posts . ' products in ' . esc_html($cat_name) . '</h5>';
        if ($query->have_posts()) {
            echo '<ul class="fwb-products-list">';
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());
                $img = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
                if (!$img) {
                    $img = wc_placeholder_img_src('thumbnail');
                }
                $stock_text = $product->is_in_stock() ? '✓ In stock' : '✗ Out of stock';
                $stock_color = $product->is_in_stock() ? '#28a745' : '#dc3545';
                // Custom price display
                $regular_price = $product->get_regular_price();
                $sale_price = $product->get_sale_price();
                $price_html = '';
                if ($product->is_on_sale() && $sale_price) {
                    $price_html = '<del>' . wc_price($regular_price) . '</del><ins>' . wc_price($sale_price) . '</ins>';
                } else {
                    $price_html = wc_price($regular_price);
                }
                $permalink = get_permalink();
                ?>
                <li class="fwb-product-item">
                    <a href="<?php echo esc_url($permalink); ?>" class="fwb-product-link" target="_blank" rel="noopener noreferrer">
                        <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" />
                        <div class="fwb-product-details">
                            <div class="product-info">
                                <h6><?php echo esc_html(get_the_title()); ?></h6>
                                <div class="price"><?php echo $price_html; ?></div>
                                <div class="stock-status">
                                    <span style="color: <?php echo $stock_color; ?>;"><?php echo $stock_text; ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                </li>
                <?php
            }
            echo '</ul>';
        } else {
            echo '<div class="fwb-empty-state"><div class="fwb-empty-state-icon">🔍</div><p>No products found in this category</p></div>';
        }
        wp_reset_postdata();
        $html = ob_get_clean();
        wp_send_json_success(array('type' => 'products', 'html' => $html));
    }
}
// WooCommerce Add to Cart AJAX
add_action('wp_ajax_woocommerce_add_to_cart', 'fwb_woocommerce_add_to_cart');
add_action('wp_ajax_nopriv_woocommerce_add_to_cart', 'fwb_woocommerce_add_to_cart');
function fwb_woocommerce_add_to_cart() {
    check_ajax_referer('fwb_ajax_nonce', 'nonce');
   
    $product_id = intval($_POST['product_id'] ?? 0);
    if (!$product_id) {
        wp_send_json_error(array('error' => 'Invalid product'));
    }
   
    $product = wc_get_product($product_id);
    if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
        wp_send_json_error(array('error' => $product ? $product->get_unavailable_message() : 'Invalid product'));
    }
   
    $cart_item_key = WC()->cart->add_to_cart($product_id, 1);
    if ($cart_item_key) {
        do_action('woocommerce_add_to_cart', $cart_item_key, $product_id, 1);
        $fragments = WC_AJAX::get_refreshed_fragments();
        wp_send_json_success(array(
            'fragments' => $fragments,
            'cart_hash' => WC()->cart->get_cart_hash(),
            'wc_notices' => wc_get_notices('success'),
        ));
    } else {
        wp_send_json_error(array('error' => 'Failed to add to cart'));
    }
}
/**
 * ============================================================================
 * HELPER FUNCTIONS
 * ============================================================================
 */
// Helper: Adjust color brightness for gradient
function fwb_adjust_brightness($hex, $steps) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
   
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));
   
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
               . str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
               . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}
/**
 * ============================================================================
 * RENDER FLOATING BUTTON (Frontend Output)
 * ============================================================================
 */
add_action('wp_footer', 'fwb_render_button');
function fwb_render_button() {
    // Don't show in admin or on store-manager pages
    if (is_admin() || strpos($_SERVER['REQUEST_URI'], '/store-manager/') !== false) {
        return;
    }
   
    $align = get_option('fwb_button_align', 'right');
    $icon = get_option('fwb_button_icon', '🛒');
    $enable_msg = get_option('fwb_enable_message', 0);
    $msg_title = get_option('fwb_message_title', 'Get Personalized Help');
    $msg_text = get_option('fwb_custom_message', '');
   
    // Position styles (desktop)
    $position_style = '';
    switch ($align) {
        case 'left':
            $position_style = 'left: 20px;';
            break;
        case 'center':
            $position_style = 'left: 50%; transform: translateX(-50%);';
            break;
        default: // right
            $position_style = 'right: 20px;';
    }
    ?>
    <div id="fwb-container" style="<?php echo esc_attr($position_style); ?>">
        <!-- Floating Button -->
        <button id="fwb-button" type="button" aria-label="Open Product Categories">
            <?php echo esc_html($icon); ?>
        </button>
       
        <!-- Message Modal (Optional) -->
        <?php if ($enable_msg) : ?>
        <div id="fwb-message-modal">
            <h4><?php echo esc_html($msg_title); ?></h4>
            <p><?php echo esc_html($msg_text); ?></p>
            <form id="fwb-message-form">
                <input type="text" name="fwb_name" placeholder="Your Name" required />
                <input type="email" name="fwb_email" placeholder="Your Email" required />
                <input type="tel" name="fwb_phone" placeholder="Phone Number (optional)" />
                <div>
                    <button type="submit">Submit</button>
                    <button type="button" id="fwb-skip-message">Skip</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
       
        <!-- Dropdown Panel -->
        <div id="fwb-dropdown">
            <div id="fwb-dropdown-header">
                <button id="fwb-back" type="button"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="14" viewBox="0 0 24 24" fill="none">
  <path d="M16 5l-8 7 8 7V5z" fill="currentColor"/>
</svg>
 Back</button>
                <span id="fwb-breadcrumb">📁 Shop Categories</span>
                <button id="fwb-close-dropdown" type="button" aria-label="Close">×</button>
            </div>
            <div id="fwb-dropdown-content">
                <div id="fwb-categories"></div>
                <div id="fwb-products-panel" style="display: none;"></div>
                <div id="fwb-loading" style="display: none;">
                    <div class="fwb-spinner"></div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
/**
 * ============================================================================
 * ACTIVATION/DEACTIVATION HOOKS
 * ============================================================================
 */
register_activation_hook(__FILE__, 'fwb_activate');
function fwb_activate() {
    // Set default options on activation
    add_option('fwb_button_color', '#2271b1');
    add_option('fwb_button_text_color', '#ffffff');
    add_option('fwb_button_align', 'right');
    add_option('fwb_button_icon', '🛒');
    add_option('fwb_enable_message', 0);
    add_option('fwb_message_title', 'Get Personalized Help');
    add_option('fwb_custom_message', 'Welcome! Please share your details so we can assist you better.');
    add_option('fwb_dropdown_width', 400);
    add_option('fwb_products_per_page', 12);
}
register_deactivation_hook(__FILE__, 'fwb_deactivate');
function fwb_deactivate() {
    // Cleanup if needed (optional)
}