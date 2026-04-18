<?php
/**
 * Product-Level Exchange Settings
 * Allows custom exchange pricing per product
 */

if (!defined('ABSPATH')) {
    exit;
}

class Exchange_Pro_Product_Settings {
    
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
        
        // Add product meta box
        add_action('add_meta_boxes', array($this, 'add_exchange_meta_box'));
        
        // Save product meta
        add_action('woocommerce_process_product_meta', array($this, 'save_product_meta'));
        
        // Add custom tab
        add_filter('woocommerce_product_data_tabs', array($this, 'add_exchange_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'add_exchange_panel'));
    }
    
    /**
     * Add Exchange tab to product data
     */
    public function add_exchange_tab($tabs) {
        $tabs['exchange_pro'] = array(
            'label' => __('Exchange', 'exchange-pro'),
            'target' => 'exchange_pro_product_data',
            'class' => array('show_if_simple', 'show_if_variable'),
        );
        return $tabs;
    }
    
    /**
     * Add Exchange panel content
     */
    public function add_exchange_panel() {
        global $post;
        
        $enabled = get_post_meta($post->ID, '_exchange_pro_enable', true);

        // Pricing source selector (global|custom). Backward compatible with legacy _exchange_pro_custom_pricing.
        $pricing_source = get_post_meta($post->ID, '_exchange_pro_pricing_source', true);
        $legacy_custom  = get_post_meta($post->ID, '_exchange_pro_custom_pricing', true);
        if (empty($pricing_source)) {
            $pricing_source = ($legacy_custom === 'yes') ? 'custom' : 'global';
        }
        $pricing_data = get_post_meta($post->ID, '_exchange_pro_pricing_data', true);
        $allowed_categories = get_post_meta($post->ID, '_exchange_pro_allowed_categories', true);
        $max_cap = get_post_meta($post->ID, '_exchange_pro_max_cap', true);
        
        if (!is_array($allowed_categories)) {
            $allowed_categories = array();
        }
        
        $categories = $this->db->get_categories();
        
        ?>
        <div id="exchange_pro_product_data" class="panel woocommerce_options_panel" style="display: none;">
            
            <div class="options_group">
                <h3 style="padding: 15px; margin: 0; border-bottom: 1px solid #eee;">
                    <?php _e('Exchange Configuration', 'exchange-pro'); ?>
                </h3>
                
                <?php
                woocommerce_wp_checkbox(array(
                    'id' => '_exchange_pro_enable',
                    'label' => __('Enable Exchange', 'exchange-pro'),
                    'description' => __('Allow exchange for this product', 'exchange-pro'),
                    'value' => $enabled === 'yes' ? 'yes' : 'no'
                ));
                ?>
                
                <div class="exchange-settings-wrapper" style="<?php echo $enabled !== 'yes' ? 'display:none;' : ''; ?>">
                    
                    <?php
                    woocommerce_wp_text_input(array(
                        'id' => '_exchange_pro_max_cap',
                        'label' => __('Max Exchange Cap (%)', 'exchange-pro'),
                        'description' => __('Maximum exchange value as % of product price. Leave empty to use global setting.', 'exchange-pro'),
                        'type' => 'number',
                        'custom_attributes' => array(
                            'step' => '1',
                            'min' => '0',
                            'max' => '100'
                        ),
                        'value' => $max_cap,
                        'desc_tip' => true
                    ));
                    ?>
                    
                    <p class="form-field">
                        <label><?php _e('Allowed Device Categories', 'exchange-pro'); ?></label>
                        <span class="description" style="margin-bottom: 10px; display: block;">
                            <?php _e('Select which device categories customers can exchange for this product', 'exchange-pro'); ?>
                        </span>
                    </p>
                    
                    <div style="padding: 0 12px;">
                        <select name="_exchange_pro_allowed_categories[]" id="_exchange_pro_allowed_categories" multiple="multiple" style="width: 100%; max-width: 420px;">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo esc_attr($category->id); ?>" <?php selected(in_array($category->id, $allowed_categories)); ?>>
                                    <?php echo esc_html($category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description" style="margin-top: 6px;">
                            <?php _e('Hold Ctrl/Cmd to select multiple categories.', 'exchange-pro'); ?>
                        </p>
                    </div>
                    
                    <hr style="margin: 20px 0;">
                    
                    <p class="form-field">
                        <label><strong><?php _e('Exchange Pricing Source', 'exchange-pro'); ?></strong></label>
                        <span class="description" style="display:block; margin-top: 5px;">
                            <?php _e('Choose where the popup loads devices/prices from. Global = Devices & Pricing Management. Custom = this product\'s custom rows only.', 'exchange-pro'); ?>
                        </span>
                        <fieldset style="margin-top: 10px;">
                            <label style="margin-right: 18px;">
                                <input type="radio" name="_exchange_pro_pricing_source" value="global" <?php checked($pricing_source, 'global'); ?> />
                                <?php _e('Use Global Device Pricing', 'exchange-pro'); ?>
                            </label>
                            <label>
                                <input type="radio" name="_exchange_pro_pricing_source" value="custom" <?php checked($pricing_source, 'custom'); ?> />
                                <?php _e('Use Custom Pricing for this Product', 'exchange-pro'); ?>
                            </label>
                        </fieldset>
                    </p>
                    
                    <div id="custom-pricing-section" style="<?php echo $pricing_source !== 'custom' ? 'display:none;' : ''; ?>">
                        <div style="background: #f8f9fa; padding: 15px; margin: 15px 12px; border-radius: 4px;">
                            <h4 style="margin-top: 0;"><?php _e('Custom Exchange Pricing', 'exchange-pro'); ?></h4>
                            <p class="description">
                                <?php _e('Set custom exchange values for specific devices. These will override the global pricing matrix.', 'exchange-pro'); ?>
                            </p>
                            
                            <button type="button" class="button button-primary" id="add-custom-pricing-row">
                                <?php _e('+ Add Device Pricing', 'exchange-pro'); ?>
                            </button>
                            
                            <div id="custom-pricing-rows" style="margin-top: 15px;">
                                <?php $this->render_custom_pricing_rows($pricing_data); ?>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Toggle exchange settings
            $('#_exchange_pro_enable').change(function() {
                if ($(this).is(':checked')) {
                    $('.exchange-settings-wrapper').slideDown();
                } else {
                    $('.exchange-settings-wrapper').slideUp();
                }
            });
            
            // Toggle custom pricing section based on pricing source
            $('input[name="_exchange_pro_pricing_source"]').change(function() {
                if ($(this).val() === 'custom') {
                    $('#custom-pricing-section').slideDown();
                } else {
                    $('#custom-pricing-section').slideUp();
                }
            });
            
            // Add custom pricing row
            $('#add-custom-pricing-row').click(function() {
                var rowIndex = $('#custom-pricing-rows .pricing-row').length;
                var row = `
                    <div class="pricing-row" style="background: white; padding: 15px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <div class="exchange-pro-device-selects" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                            <div>
                                <label><?php _e('Category', 'exchange-pro'); ?></label>
                                <select class="exchange-pro-category form-control" name="_exchange_pro_pricing_data[${rowIndex}][category_id]" style="width: 100%;">
                                    <option value=""><?php _e('Select category...', 'exchange-pro'); ?></option>
                                    <?php
                                    $db = Exchange_Pro_Database::get_instance();
                                    $cats = $db->get_categories('');
                                    foreach ($cats as $c) {
                                        echo '<option value="' . (int) $c->id . '">' . esc_html($c->name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label><?php _e('Brand', 'exchange-pro'); ?></label>
                                <select class="exchange-pro-brand form-control" name="_exchange_pro_pricing_data[${rowIndex}][brand_id]" style="width: 100%;" data-selected="">
                                    <option value=""><?php _e('Select brand...', 'exchange-pro'); ?></option>
                                </select>
                            </div>
                            <div>
                                <label><?php _e('Model', 'exchange-pro'); ?></label>
                                <select class="exchange-pro-model form-control" name="_exchange_pro_pricing_data[${rowIndex}][model_id]" style="width: 100%;" data-selected="">
                                    <option value=""><?php _e('Select model...', 'exchange-pro'); ?></option>
                                </select>
                            </div>
                            <div>
                                <label><?php _e('Variant', 'exchange-pro'); ?></label>
                                <select class="exchange-pro-variant form-control" name="_exchange_pro_pricing_data[${rowIndex}][variant_id]" style="width: 100%;" data-selected="">
                                    <option value=""><?php _e('Select variant...', 'exchange-pro'); ?></option>
                                </select>
                            </div>
                        </div>

                        <input type="hidden" class="exchange-pro-device-name" name="_exchange_pro_pricing_data[${rowIndex}][device_name]" value="">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                            <div>
                                <label><?php _e('Excellent', 'exchange-pro'); ?></label>
                                <input type="number" 
                                       name="_exchange_pro_pricing_data[${rowIndex}][excellent]" 
                                       placeholder="0"
                                       step="0.01"
                                       min="0"
                                       style="width: 100%;">
                            </div>
                            <div>
                                <label><?php _e('Good', 'exchange-pro'); ?></label>
                                <input type="number" 
                                       name="_exchange_pro_pricing_data[${rowIndex}][good]" 
                                       placeholder="0"
                                       step="0.01"
                                       min="0"
                                       style="width: 100%;">
                            </div>
                            <div>
                                <label><?php _e('Fair', 'exchange-pro'); ?></label>
                                <input type="number" 
                                       name="_exchange_pro_pricing_data[${rowIndex}][fair]" 
                                       placeholder="0"
                                       step="0.01"
                                       min="0"
                                       style="width: 100%;">
                            </div>
                            <div>
                                <label><?php _e('Poor', 'exchange-pro'); ?></label>
                                <input type="number" 
                                       name="_exchange_pro_pricing_data[${rowIndex}][poor]" 
                                       placeholder="0"
                                       step="0.01"
                                       min="0"
                                       style="width: 100%;">
                            </div>
                        </div>
                        
                        <button type="button" class="button remove-pricing-row" style="background: #dc3545; color: white; border-color: #dc3545;">
                            <?php _e('Remove', 'exchange-pro'); ?>
                        </button>
                    </div>
                `;
                $('#custom-pricing-rows').append(row);
            });
            
            // Remove pricing row
            $(document).on('click', '.remove-pricing-row', function() {
                $(this).closest('.pricing-row').remove();
            });
        });
        </script>
        
        <style>
        #exchange_pro_product_data input[type="number"],
        #exchange_pro_product_data input[type="text"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        #exchange_pro_product_data label {
            font-weight: 600;
            color: #2c3e50;
            display: block;
            margin-bottom: 5px;
        }
        </style>
        <?php
    }
    
    /**
     * Render existing custom pricing rows
     */
    private function render_custom_pricing_rows($pricing_data) {
        if (!is_array($pricing_data) || empty($pricing_data)) {
            return;
        }

        // Load categories for the first dropdown. Brand/model/variant are loaded dynamically via AJAX.
        $db = Exchange_Pro_Database::get_instance();
        $categories = $db->get_categories('');
        
        foreach ($pricing_data as $index => $data) {
            ?>
            <div class="pricing-row" style="background: white; padding: 15px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <div class="exchange-pro-device-selects" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div>
                        <label><?php _e('Category', 'exchange-pro'); ?></label>
                        <select class="exchange-pro-category form-control" name="_exchange_pro_pricing_data[<?php echo $index; ?>][category_id]" style="width: 100%;">
                            <option value=""><?php _e('Select category...', 'exchange-pro'); ?></option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int) $cat->id; ?>" <?php selected((int)($data['category_id'] ?? 0), (int) $cat->id); ?>>
                                    <?php echo esc_html($cat->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label><?php _e('Brand', 'exchange-pro'); ?></label>
                        <select class="exchange-pro-brand form-control" name="_exchange_pro_pricing_data[<?php echo $index; ?>][brand_id]" style="width: 100%;" data-selected="<?php echo esc_attr($data['brand_id'] ?? ''); ?>">
                            <option value=""><?php _e('Select brand...', 'exchange-pro'); ?></option>
                        </select>
                    </div>
                    <div>
                        <label><?php _e('Model', 'exchange-pro'); ?></label>
                        <select class="exchange-pro-model form-control" name="_exchange_pro_pricing_data[<?php echo $index; ?>][model_id]" style="width: 100%;" data-selected="<?php echo esc_attr($data['model_id'] ?? ''); ?>">
                            <option value=""><?php _e('Select model...', 'exchange-pro'); ?></option>
                        </select>
                    </div>
                    <div>
                        <label><?php _e('Variant', 'exchange-pro'); ?></label>
                        <select class="exchange-pro-variant form-control" name="_exchange_pro_pricing_data[<?php echo $index; ?>][variant_id]" style="width: 100%;" data-selected="<?php echo esc_attr($data['variant_id'] ?? ''); ?>">
                            <option value=""><?php _e('Select variant...', 'exchange-pro'); ?></option>
                        </select>
                    </div>
                </div>

                <input type="hidden" class="exchange-pro-device-name"
                       name="_exchange_pro_pricing_data[<?php echo $index; ?>][device_name]"
                       value="<?php echo esc_attr($data['device_name'] ?? ''); ?>">
                <div class="description" style="margin: 6px 0 14px; color: #666;">
                    <?php _e('Tip: pick a Variant to lock this row to an exact device. When custom pricing is enabled, ONLY the variants you add here will be eligible for exchange for this product.', 'exchange-pro'); ?>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div>
                        <label><?php _e('Excellent', 'exchange-pro'); ?></label>
                        <input type="number" 
                               name="_exchange_pro_pricing_data[<?php echo $index; ?>][excellent]" 
                               value="<?php echo esc_attr($data['excellent'] ?? ''); ?>"
                               placeholder="0"
                               step="0.01"
                               min="0"
                               style="width: 100%;">
                    </div>
                    <div>
                        <label><?php _e('Good', 'exchange-pro'); ?></label>
                        <input type="number" 
                               name="_exchange_pro_pricing_data[<?php echo $index; ?>][good]" 
                               value="<?php echo esc_attr($data['good'] ?? ''); ?>"
                               placeholder="0"
                               step="0.01"
                               min="0"
                               style="width: 100%;">
                    </div>
                    <div>
                        <label><?php _e('Fair', 'exchange-pro'); ?></label>
                        <input type="number" 
                               name="_exchange_pro_pricing_data[<?php echo $index; ?>][fair]" 
                               value="<?php echo esc_attr($data['fair'] ?? ''); ?>"
                               placeholder="0"
                               step="0.01"
                               min="0"
                               style="width: 100%;">
                    </div>
                    <div>
                        <label><?php _e('Poor', 'exchange-pro'); ?></label>
                        <input type="number" 
                               name="_exchange_pro_pricing_data[<?php echo $index; ?>][poor]" 
                               value="<?php echo esc_attr($data['poor'] ?? ''); ?>"
                               placeholder="0"
                               step="0.01"
                               min="0"
                               style="width: 100%;">
                    </div>
                </div>
                
                <button type="button" class="button remove-pricing-row" style="background: #dc3545; color: white; border-color: #dc3545;">
                    <?php _e('Remove', 'exchange-pro'); ?>
                </button>
            </div>
            <?php
        }
    }
    
    /**
     * Add meta box for quick view
     */
    public function add_exchange_meta_box() {
        add_meta_box(
            'exchange_pro_product_meta',
            __('Exchange Quick Settings', 'exchange-pro'),
            array($this, 'render_meta_box'),
            'product',
            'side',
            'high'
        );
    }
    
    /**
     * Render meta box
     */
    public function render_meta_box($post) {
        $enabled = get_post_meta($post->ID, '_exchange_pro_enable', true);
        $pricing_source = get_post_meta($post->ID, '_exchange_pro_pricing_source', true);
        if (empty($pricing_source)) {
            $custom_pricing = get_post_meta($post->ID, '_exchange_pro_custom_pricing', true);
            $pricing_source = ($custom_pricing === 'yes') ? 'custom' : 'global';
        }
        
        ?>
        <div style="padding: 10px 0;">
            <p>
                <label>
                    <input type="checkbox" 
                           name="_exchange_pro_enable_quick" 
                           value="yes"
                           <?php checked($enabled, 'yes'); ?>>
                    <strong><?php _e('Enable Exchange', 'exchange-pro'); ?></strong>
                </label>
            </p>
            
            <?php if ($pricing_source === 'custom'): ?>
                <p style="background: #d4edda; padding: 8px; border-radius: 4px; font-size: 12px;">
                    <span class="dashicons dashicons-yes" style="color: #28a745;"></span>
                    <?php _e('Custom pricing active', 'exchange-pro'); ?>
                </p>
            <?php endif; ?>
            
            <p style="font-size: 12px; color: #666;">
                <?php _e('For advanced settings, see the Exchange tab in product data.', 'exchange-pro'); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Save product meta
     */
    public function save_product_meta($post_id) {
        // Enable/disable from quick checkbox
        if (isset($_POST['_exchange_pro_enable_quick'])) {
            update_post_meta($post_id, '_exchange_pro_enable', 'yes');
        } else {
            update_post_meta($post_id, '_exchange_pro_enable', 'no');
        }
        
        // Enable/disable from main tab
        if (isset($_POST['_exchange_pro_enable'])) {
            update_post_meta($post_id, '_exchange_pro_enable', 'yes');
        }
        
        // Max cap
        if (isset($_POST['_exchange_pro_max_cap'])) {
            update_post_meta($post_id, '_exchange_pro_max_cap', sanitize_text_field($_POST['_exchange_pro_max_cap']));
        }
        
        // Allowed categories
        $allowed_categories = isset($_POST['_exchange_pro_allowed_categories']) ? array_map('intval', $_POST['_exchange_pro_allowed_categories']) : array();
        update_post_meta($post_id, '_exchange_pro_allowed_categories', $allowed_categories);
        
        // Pricing source (global|custom). Also keep legacy _exchange_pro_custom_pricing for backward compatibility.
        $pricing_source = isset($_POST['_exchange_pro_pricing_source']) ? sanitize_key($_POST['_exchange_pro_pricing_source']) : 'global';
        if (!in_array($pricing_source, array('global', 'custom'), true)) {
            $pricing_source = 'global';
        }
        update_post_meta($post_id, '_exchange_pro_pricing_source', $pricing_source);
        update_post_meta($post_id, '_exchange_pro_custom_pricing', ($pricing_source === 'custom') ? 'yes' : 'no');
        
        // Custom pricing data
        // IMPORTANT: Persist structured IDs (category_id/brand_id/model_id/variant_id)
        // so pricing works reliably after reload and can be matched exactly.
        if (isset($_POST['_exchange_pro_pricing_data']) && is_array($_POST['_exchange_pro_pricing_data'])) {
            $pricing_data = array();

            foreach ($_POST['_exchange_pro_pricing_data'] as $index => $data) {
                $category_id = isset($data['category_id']) ? intval($data['category_id']) : 0;
                $brand_id    = isset($data['brand_id']) ? intval($data['brand_id']) : 0;
                $model_id    = isset($data['model_id']) ? intval($data['model_id']) : 0;
                $variant_id  = isset($data['variant_id']) ? intval($data['variant_id']) : 0;
                $device_name = isset($data['device_name']) ? sanitize_text_field($data['device_name']) : '';

                // We consider a row valid if it targets an exact variant OR has a legacy device_name.
                if ($variant_id || $device_name) {
                    $pricing_data[] = array(
                        'category_id' => $category_id,
                        'brand_id'    => $brand_id,
                        'model_id'    => $model_id,
                        'variant_id'  => $variant_id,
                        'device_name' => $device_name,
                        'excellent'   => floatval($data['excellent'] ?? 0),
                        'good'        => floatval($data['good'] ?? 0),
                        'fair'        => floatval($data['fair'] ?? 0),
                        'poor'        => floatval($data['poor'] ?? 0),
                    );
                }
            }

            update_post_meta($post_id, '_exchange_pro_pricing_data', $pricing_data);
        } else {
            delete_post_meta($post_id, '_exchange_pro_pricing_data');
        }
    }
}
