<?php
/**
 * Popup Handler
 * Renders the exchange popup modal
 */

if (!defined('ABSPATH')) {
    exit;
}

class Exchange_Pro_Popup {
    
    private static $instance = null;
    private $db;
    private $pricing;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db = Exchange_Pro_Database::get_instance();
        $this->pricing = Exchange_Pro_Pricing::get_instance();
        
        // Add popup to footer
        add_action('wp_footer', array($this, 'render_popup'));
    }
    
    /**
     * Render exchange popup
     */
    public function render_popup() {
        if (!is_product()) {
            return;
        }
        
        global $product;
        if (!$product) {
            return;
        }
        
        $product_id = $product->get_id();
        
        if (!$this->pricing->is_exchange_enabled_for_product($product_id)) {
            return;
        }
        
        $categories = $this->db->get_categories();
        $allowed_categories = $this->pricing->get_allowed_categories_for_product($product_id);

        // If admin has not configured categories for this product (or there are none),
        // do NOT show the popup. This prevents "sample"/empty UIs on the storefront.
        if (empty($categories) || empty($allowed_categories)) {
            return;
        }

        // Compute intersection; if nothing allowed, don't render.
        $has_allowed = false;
        foreach ($categories as $c) {
            if (in_array($c->id, $allowed_categories)) {
                $has_allowed = true;
                break;
            }
        }
        if (!$has_allowed) {
            return;
        }
        $conditions = $this->pricing->get_condition_options();
        $primary_color = get_option('exchange_pro_primary_color', '#ff6600');
        $theme = get_option('exchange_pro_popup_theme', 'light');
        $imei_mandatory = get_option('exchange_pro_imei_mandatory', 'yes') === 'yes';
        $pincode_validation = get_option('exchange_pro_pincode_validation', 'yes') === 'yes';
        
        ?>
        <!-- Exchange Pro Modal -->
        <div class="modal fade" id="exchangeProModal" tabindex="-1" aria-labelledby="exchangeProModalLabel" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                    <div class="modal-header" style="background: linear-gradient(135deg, <?php echo esc_attr($primary_color); ?> 0%, <?php echo esc_attr($this->adjust_brightness($primary_color, -20)); ?> 100%); color: white; border-radius: 16px 16px 0 0; padding: 24px;">
                        <div>
                            <h5 class="modal-title" id="exchangeProModalLabel" style="font-size: 24px; font-weight: 700; margin: 0;">
                                <?php _e('Device Exchange', 'exchange-pro'); ?>
                            </h5>
                            <p style="margin: 8px 0 0 0; opacity: 0.9; font-size: 14px;">
                                <span id="exchange-step-indicator"><?php _e('Step 1 of 5', 'exchange-pro'); ?></span>
                            </p>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="padding: 32px;">
                        <!-- Step 1: Category Selection -->
                        <div id="exchange-step-1" class="exchange-step">
                            <h6 class="mb-4" style="color: #2c3e50; font-weight: 600; font-size: 16px;">
                                <?php _e('Select Device Category', 'exchange-pro'); ?>
                            </h6>
                            <div class="mb-3">
                                <label class="form-label" style="font-weight: 600; color: #2c3e50;">
                                    <?php _e('Device Category', 'exchange-pro'); ?>
                                </label>
                                <select id="exchange-category" class="form-select" style="border-radius: 10px; padding: 10px;">
                                    <option value=""><?php _e('Select category...', 'exchange-pro'); ?></option>
                                    <?php foreach ($categories as $category): ?>
                                        <?php if (in_array($category->id, $allowed_categories)): ?>
                                            <option value="<?php echo esc_attr($category->id); ?>" data-category-slug="<?php echo esc_attr($category->slug); ?>">
                                                <?php echo esc_html($category->name); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <div class="small text-muted" style="margin-top: 8px;">
                                    <?php _e('Choose the type of device you want to exchange.', 'exchange-pro'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 2: Brand Selection -->
                        <div id="exchange-step-2" class="exchange-step" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h6 class="mb-0" style="color: #2c3e50; font-weight: 600; font-size: 16px;">
                                    <?php _e('Select Brand', 'exchange-pro'); ?>
                                </h6>
                                <button type="button" class="btn btn-sm btn-outline-secondary exchange-back-btn">
                                    <i class="fas fa-arrow-left"></i> <?php _e('Back', 'exchange-pro'); ?>
                                </button>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="font-weight: 600; color: #2c3e50;">
                                    <?php _e('Brand', 'exchange-pro'); ?>
                                </label>
                                <select id="exchange-brand" class="form-select" style="border-radius: 10px; padding: 10px;">
                                    <option value=""><?php _e('Select brand...', 'exchange-pro'); ?></option>
                                </select>
                                <div id="brands-container" class="small text-muted" style="margin-top: 8px;"></div>
                            </div>
                        </div>
                        
                        <!-- Step 3: Model Selection -->
                        <div id="exchange-step-3" class="exchange-step" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h6 class="mb-0" style="color: #2c3e50; font-weight: 600; font-size: 16px;">
                                    <?php _e('Select Model', 'exchange-pro'); ?>
                                </h6>
                                <button type="button" class="btn btn-sm btn-outline-secondary exchange-back-btn">
                                    <i class="fas fa-arrow-left"></i> <?php _e('Back', 'exchange-pro'); ?>
                                </button>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="font-weight: 600; color: #2c3e50;">
                                    <?php _e('Model', 'exchange-pro'); ?>
                                </label>
                                <select id="exchange-model" class="form-select" style="border-radius: 10px; padding: 10px;" disabled>
                                    <option value=""><?php _e('Select model...', 'exchange-pro'); ?></option>
                                </select>
                                <div id="models-container" class="small text-muted" style="margin-top: 8px;"></div>
                            </div>
                        </div>
                        
                        <!-- Step 4: Variant & Condition Selection -->
                        <div id="exchange-step-4" class="exchange-step" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h6 class="mb-0" style="color: #2c3e50; font-weight: 600; font-size: 16px;">
                                    <?php _e('Select Variant & Condition', 'exchange-pro'); ?>
                                </h6>
                                <button type="button" class="btn btn-sm btn-outline-secondary exchange-back-btn">
                                    <i class="fas fa-arrow-left"></i> <?php _e('Back', 'exchange-pro'); ?>
                                </button>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label" style="font-weight: 600; color: #2c3e50;">
                                    <?php _e('Variant / Storage', 'exchange-pro'); ?>
                                </label>
                                <select id="exchange-variant" class="form-select" style="border-radius: 8px; padding: 10px;">
                                    <option value=""><?php _e('Select variant...', 'exchange-pro'); ?></option>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label" style="font-weight: 600; color: #2c3e50;">
                                    <?php _e('Device Condition', 'exchange-pro'); ?>
                                </label>
                                <div id="conditions-container">
                                    <?php foreach ($conditions as $key => $condition): ?>
                                        <div class="condition-card mb-3" data-condition="<?php echo esc_attr($key); ?>" style="cursor: pointer; border: 2px solid #e0e0e0; border-radius: 12px; padding: 16px; transition: all 0.3s;">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div style="font-weight: 600; font-size: 15px; color: #2c3e50; margin-bottom: 4px;">
                                                        <?php echo esc_html($condition['label']); ?>
                                                    </div>
                                                    <div style="font-size: 13px; color: #7f8c8d;">
                                                        <?php echo esc_html($condition['description']); ?>
                                                    </div>
                                                </div>
                                                <div class="condition-price" style="font-weight: 700; font-size: 20px; color: <?php echo esc_attr($primary_color); ?>;">
                                                    --
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 5: Additional Details -->
                        <div id="exchange-step-5" class="exchange-step" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h6 class="mb-0" style="color: #2c3e50; font-weight: 600; font-size: 16px;">
                                    <?php _e('Additional Details', 'exchange-pro'); ?>
                                </h6>
                                <button type="button" class="btn btn-sm btn-outline-secondary exchange-back-btn">
                                    <i class="fas fa-arrow-left"></i> <?php _e('Back', 'exchange-pro'); ?>
                                </button>
                            </div>
                            
                            <div class="mb-4" id="imei-container">
                                <label class="form-label" style="font-weight: 600; color: #2c3e50;">
                                    <?php _e('IMEI / Serial Number', 'exchange-pro'); ?>
                                    <?php if ($imei_mandatory): ?>
                                        <span style="color: red;">*</span>
                                    <?php endif; ?>
                                </label>
                                <input type="text" id="exchange-imei" class="form-control" placeholder="<?php _e('Enter IMEI or Serial Number', 'exchange-pro'); ?>" style="border-radius: 8px; padding: 12px;">
                                <small class="text-muted"><?php _e('For mobiles, dial *#06# to get IMEI', 'exchange-pro'); ?></small>
                            </div>

                            <div class="mb-4" id="model-number-container">
                                <label class="form-label" style="font-weight: 600; color: #2c3e50;">
                                    <?php _e('Model Number', 'exchange-pro'); ?> <span style="color: red;">*</span>
                                </label>
                                <input type="text" id="exchange-model-number" class="form-control" placeholder="<?php _e('Enter model number', 'exchange-pro'); ?>" style="border-radius: 8px; padding: 12px;">
                                <small class="text-muted"><?php _e('Required for non-mobile devices (laptop/camera/printer/tablet).', 'exchange-pro'); ?></small>
                            </div>
                            
                            <?php if ($pincode_validation): ?>
                            <div class="mb-4">
                                <label class="form-label" style="font-weight: 600; color: #2c3e50;">
                                    <?php _e('Pincode', 'exchange-pro'); ?> <span style="color: red;">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="text" id="exchange-pincode" class="form-control" placeholder="<?php _e('Enter pincode', 'exchange-pro'); ?>" style="border-radius: 8px 0 0 8px; padding: 12px;">
                                    <button type="button" id="check-pincode-btn" class="btn btn-outline-secondary" style="border-radius: 0 8px 8px 0;">
                                        <?php _e('Verify', 'exchange-pro'); ?>
                                    </button>
                                </div>
                                <div id="pincode-status" class="mt-2"></div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-check mb-4">
                                <input type="checkbox" class="form-check-input" id="exchange-confirm" style="border-radius: 4px;">
                                <label class="form-check-label" for="exchange-confirm" style="font-size: 14px;">
                                    <?php _e('I confirm that the device information provided is accurate', 'exchange-pro'); ?>
                                </label>
                            </div>
                            
                            <div class="exchange-summary" style="background: #f8f9fa; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span style="font-size: 14px; color: #7f8c8d;"><?php _e('Exchange Value:', 'exchange-pro'); ?></span>
                                    <span id="final-exchange-value" style="font-size: 28px; font-weight: 700; color: <?php echo esc_attr($primary_color); ?>;">₹0</span>
                                </div>
                                <div id="device-summary" style="font-size: 13px; color: #2c3e50; line-height: 1.8;"></div>
                            </div>
                            
                            <button type="button" id="confirm-exchange-btn" class="btn btn-primary w-100" style="padding: 14px; font-size: 16px; font-weight: 600; border-radius: 8px; background: <?php echo esc_attr($primary_color); ?>; border: none;">
                                <?php _e('Continue with Exchange', 'exchange-pro'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .category-card:hover,
            .condition-card:hover,
            .brand-card:hover,
            .model-card:hover {
                border-color: <?php echo esc_attr($primary_color); ?> !important;
                transform: translateY(-4px);
                box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            }
            
            .category-card.selected,
            .condition-card.selected,
            .brand-card.selected,
            .model-card.selected {
                border-color: <?php echo esc_attr($primary_color); ?> !important;
                background: <?php echo esc_attr($this->adjust_brightness($primary_color, 95)); ?> !important;
            }
            
            .condition-card.disabled {
                opacity: 0.5;
                cursor: not-allowed !important;
            }
            
            #exchange-step-indicator {
                font-size: 13px;
            }
        </style>
        <?php
    }
    
    /**
     * Adjust color brightness
     */
    private function adjust_brightness($hex, $steps) {
        $steps = max(-255, min(255, $steps));
        $hex = str_replace('#', '', $hex);
        
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }
        
        $rgb = array(hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
        
        for ($i = 0; $i < 3; $i++) {
            if ($steps > 0) {
                $rgb[$i] = min(255, $rgb[$i] + $steps);
            } else {
                $rgb[$i] = max(0, $rgb[$i] + $steps);
            }
            $rgb[$i] = str_pad(dechex($rgb[$i]), 2, '0', STR_PAD_LEFT);
        }
        
        return '#' . implode('', $rgb);
    }
}
