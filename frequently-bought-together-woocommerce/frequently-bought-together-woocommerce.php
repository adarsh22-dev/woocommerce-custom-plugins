<?php
/*
Plugin Name: CISAI Frequently Bought Together for WooCommerce
Description: A custom plugin to display frequently bought together (cross-sell) products with dynamic pricing. Uses [fbt_woocommerce] shortcode, hides if no cross-sells, and integrates with WooCommerce cross-sell settings.
Version: 1.0
Author: Grok
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load translations
load_plugin_textdomain('fbt-woocommerce-cisai', false, dirname(plugin_basename(__FILE__)) . '/languages');

// Enqueue optimized assets
function fbt_enqueue_assets() {
    if (!is_product()) {
        return;
    }
    wp_enqueue_script('fbt-js', plugin_dir_url(__FILE__) . 'assets/fbt.js', ['jquery'], '1.0', true);
    wp_localize_script('fbt-js', 'fbt_data', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'currency' => get_woocommerce_currency_symbol(),
        'position' => get_option('woocommerce_currency_pos'),
        'thousand_sep' => wc_get_price_thousand_separator(),
        'decimal_sep' => wc_get_price_decimal_separator(),
        'decimals' => wc_get_price_decimals(),
    ]);
    wp_enqueue_style('fbt-css', plugin_dir_url(__FILE__) . 'assets/fbt.css', [], '1.0');
}
add_action('wp_enqueue_scripts', 'fbt_enqueue_assets');

// Efficient word truncation
function get_first_words($text, $word_count = 2) {
    $words = array_filter(explode(' ', trim($text)));
    return implode(' ', array_slice($words, 0, $word_count));
}

// Shortcode with early returns
function fbt_shortcode() {
    global $product;

    if (!is_a($product, 'WC_Product')) {
        return '';
    }

    $cross_sell_ids = $product->get_cross_sell_ids();
    if (empty($cross_sell_ids)) {
        return '';
    }

    $cross_sells = array_filter(array_map('wc_get_product', $cross_sell_ids));
    if (empty($cross_sells)) {
        return '';
    }

    $main_id = $product->get_id();
    $main_price = (float) $product->get_price('edit');
    $main_price_html = $product->get_price_html();

    ob_start();
    ?>
    <div class="fbt-section">
        <h2><?php _e('Frequently Bought Together', 'fbt-woocommerce-cisai'); ?></h2>
        <div class="fbt-container">
            <div class="fbt-products" data-slider-index="0">
                <div class="fbt-product main" data-price="<?php echo esc_attr($main_price); ?>">
                    <?php echo $product->get_image('thumbnail'); ?>
                    <p class="product-name"><?php echo esc_html(__(get_first_words($product->get_name(), 2), 'fbt-woocommerce-cisai')); ?></p>
                    <p class="product-price"><?php echo wp_kses_post($main_price_html); ?></p>
                </div>
                <?php foreach ($cross_sells as $index => $cross_product) :
                    if (!$cross_product->is_purchasable()) continue;
                    $cp_id = $cross_product->get_id();
                    $cp_price = (float) $cross_product->get_price('edit');
                    $cp_price_html = $cross_product->get_price_html();
                    ?>
                    <span class="fbt-separator">+</span>
                    <div class="fbt-product" data-id="<?php echo esc_attr($cp_id); ?>" data-price="<?php echo esc_attr($cp_price); ?>">
                        <input type="checkbox" class="fbt-checkbox" checked>
                        <?php echo $cross_product->get_image('thumbnail'); ?>
                        <p class="product-name"><?php echo esc_html(__(get_first_words($cross_product->get_name(), 2), 'fbt-woocommerce-cisai')); ?></p>
                        <p class="product-price"><?php echo wp_kses_post($cp_price_html); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="fbt-total-section">
                <div class="fbt-total">
                    <span><?php _e('Total:', 'fbt-woocommerce-cisai'); ?></span>
                    <span class="fbt-total-price"></span>
                </div>
                <button class="fbt-add-to-cart" data-main-id="<?php echo esc_attr($main_id); ?>"><?php _e('Add to Cart', 'fbt-woocommerce-cisai'); ?></button>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('fbt_woocommerce', 'fbt_shortcode');

// AJAX handler with error handling
function fbt_add_to_cart_handler() {
    if (!isset($_POST['main_id']) || !isset($_POST['products'])) {
        wp_send_json_error(['message' => 'Invalid request']);
        return;
    }

    $main_id = intval($_POST['main_id']);
    $product_ids = array_map('intval', (array) $_POST['products']);

    $cart = WC()->cart;
    if (!$cart->add_to_cart($main_id, 1)) {
        wp_send_json_error(['message' => 'Failed to add main product']);
        return;
    }

    foreach ($product_ids as $pid) {
        $cart->add_to_cart($pid, 1);
    }

    wp_send_json_success(['message' => __('Products added to cart', 'fbt-woocommerce-cisai')]);
}
add_action('wp_ajax_fbt_add_to_cart', 'fbt_add_to_cart_handler');
add_action('wp_ajax_nopriv_fbt_add_to_cart', 'fbt_add_to_cart_handler');