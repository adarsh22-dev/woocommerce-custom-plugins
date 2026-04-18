<?php
/**
 * Product Page Handler
 * Adds exchange button to product pages
 */

if (!defined('ABSPATH')) {
    exit;
}

class Exchange_Pro_Product_Page {
    
    private static $instance = null;
    private $pricing;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->pricing = Exchange_Pro_Pricing::get_instance();
        
        // Add exchange button to product page
        add_action('woocommerce_before_add_to_cart_button', array($this, 'add_exchange_button'));
    }
    
    /**
     * Add exchange button to product page
     */
    public function add_exchange_button() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        $product_id = $product->get_id();
        
        // Check if exchange is enabled for this product
        if (!$this->pricing->is_exchange_enabled_for_product($product_id)) {
            return;
        }
        
        $button_text = get_option('exchange_pro_button_text', 'Get Exchange Value');
        $primary_color = get_option('exchange_pro_primary_color', '#ff6600');
        
        ?>
        <div class="exchange-pro-product-button" style="margin: 20px 0;">
            <button type="button" 
                    class="exchange-pro-open-popup btn btn-outline-primary" 
                    data-product-id="<?php echo esc_attr($product_id); ?>"
                    style="width: 100%; padding: 12px 24px; font-size: 16px; font-weight: 600; border: 2px solid <?php echo esc_attr($primary_color); ?>; color: <?php echo esc_attr($primary_color); ?>; background: white; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                <i class="fas fa-exchange-alt" style="margin-right: 8px;"></i>
                <?php echo esc_html($button_text); ?>
            </button>
            <p class="exchange-pro-hint" style="margin-top: 10px; font-size: 13px; color: #666; text-align: center;">
                <?php _e('Trade in your old device and get instant discount', 'exchange-pro'); ?>
            </p>
        </div>
        <style>
            .exchange-pro-open-popup:hover {
                background: <?php echo esc_attr($primary_color); ?> !important;
                color: white !important;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(255, 102, 0, 0.3);
            }
        </style>
        <?php
    }
}
