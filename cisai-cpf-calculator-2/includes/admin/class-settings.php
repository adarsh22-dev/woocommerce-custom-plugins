<?php
/**
 * Settings Page
 * Complete configuration interface
 */

if (!defined('ABSPATH')) {
    exit;
}

class CISAI_CPF_Settings {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('admin_init', [$this, 'handle_form_submission']);
    }
    
    /**
     * Handle form submission
     */
    public function handle_form_submission() {
        if (!isset($_POST['cisai_cpf_settings_nonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['cisai_cpf_settings_nonce'], 'cisai_cpf_save_settings')) {
            return;
        }
        
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        
        // Save general settings
        update_option('cisai_cpf_enabled', isset($_POST['cisai_cpf_enabled']) ? 'yes' : 'no');
        update_option('cisai_cpf_fee_label', sanitize_text_field($_POST['cisai_cpf_fee_label']));
        
        // Save calculation settings
        update_option('cisai_cpf_percentage', floatval($_POST['cisai_cpf_percentage']));
        update_option('cisai_cpf_flat_fee', floatval($_POST['cisai_cpf_flat_fee']));
        update_option('cisai_cpf_gateway_percentage', floatval($_POST['cisai_cpf_gateway_percentage']));
        update_option('cisai_cpf_gateway_fixed', floatval($_POST['cisai_cpf_gateway_fixed']));
        update_option('cisai_cpf_ops_cost', floatval($_POST['cisai_cpf_ops_cost']));
        
        // Save display settings
        update_option('cisai_cpf_show_breakdown', isset($_POST['cisai_cpf_show_breakdown']) ? 'yes' : 'no');
        update_option('cisai_cpf_display_mode', sanitize_text_field($_POST['cisai_cpf_display_mode']));
        
        // Save advanced settings
        update_option('cisai_cpf_min_order', floatval($_POST['cisai_cpf_min_order']));
        
        // Save excluded roles
        $excluded_roles = isset($_POST['cisai_cpf_excluded_roles']) ? array_map('sanitize_text_field', $_POST['cisai_cpf_excluded_roles']) : [];
        update_option('cisai_cpf_excluded_roles', $excluded_roles);
        
        // Save category fees
        if (isset($_POST['category_fees'])) {
            $category_fees = [];
            foreach ($_POST['category_fees'] as $slug => $fee) {
                $category_fees[sanitize_text_field($slug)] = floatval($fee);
            }
            update_option('cisai_category_fees', $category_fees);
        }
        
        // Redirect with success message
        wp_redirect(add_query_arg('updated', 'true', wp_get_referer()));
        exit;
    }
    
    /**
     * Render settings page
     */
    public function render() {
        $category_fees = get_option('cisai_category_fees', []);
        $excluded_roles = get_option('cisai_cpf_excluded_roles', []);
        
        // Get available user roles
        global $wp_roles;
        $all_roles = $wp_roles->roles;
        
        ?>
        <div class="wrap cisai-cpf-settings-wrap">
            <h1><?php esc_html_e('Platform Fee Settings', 'cisai-cpf'); ?></h1>
            
            <?php if (isset($_GET['updated'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong><?php esc_html_e('Settings saved successfully!', 'cisai-cpf'); ?></strong></p>
            </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('cisai_cpf_save_settings', 'cisai_cpf_settings_nonce'); ?>
                
                <!-- General Settings -->
                <div class="cisai-cpf-settings-section">
                    <h2><?php esc_html_e('General Settings', 'cisai-cpf'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable CPF Calculator', 'cisai-cpf'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="cisai_cpf_enabled" value="1" <?php checked(get_option('cisai_cpf_enabled', 'yes'), 'yes'); ?>>
                                    <?php esc_html_e('Enable platform fee calculations', 'cisai-cpf'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Turn on/off the entire CPF system', 'cisai-cpf'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Fee Label', 'cisai-cpf'); ?></th>
                            <td>
                                <input type="text" name="cisai_cpf_fee_label" value="<?php echo esc_attr(get_option('cisai_cpf_fee_label', 'Platform Fee')); ?>" class="regular-text">
                                <p class="description"><?php esc_html_e('Label shown to customers at checkout', 'cisai-cpf'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Calculation Settings -->
                <div class="cisai-cpf-settings-section">
                    <h2><?php esc_html_e('CPF Calculation Settings', 'cisai-cpf'); ?></h2>
                    <p class="description"><?php esc_html_e('Formula: CPF = (A% × AOV) + flat ₹f', 'cisai-cpf'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Platform Share (A%)', 'cisai-cpf'); ?></th>
                            <td>
                                <input type="number" step="0.01" name="cisai_cpf_percentage" value="<?php echo esc_attr(get_option('cisai_cpf_percentage', 5)); ?>" class="small-text">
                                <span>%</span>
                                <p class="description"><?php esc_html_e('Percentage of order value charged as CPF (e.g., 5 for 5%)', 'cisai-cpf'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Flat Fee (₹f)', 'cisai-cpf'); ?></th>
                            <td>
                                <span>₹</span>
                                <input type="number" step="0.01" name="cisai_cpf_flat_fee" value="<?php echo esc_attr(get_option('cisai_cpf_flat_fee', 2)); ?>" class="small-text">
                                <p class="description"><?php esc_html_e('Fixed amount added to CPF per order', 'cisai-cpf'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <h3><?php esc_html_e('Internal Costs (Not shown to customers)', 'cisai-cpf'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Gateway Percentage', 'cisai-cpf'); ?></th>
                            <td>
                                <input type="number" step="0.01" name="cisai_cpf_gateway_percentage" value="<?php echo esc_attr(get_option('cisai_cpf_gateway_percentage', 2)); ?>" class="small-text">
                                <span>%</span>
                                <p class="description"><?php esc_html_e('Payment gateway percentage fee (e.g., Razorpay charges 2%)', 'cisai-cpf'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Gateway Fixed Charge', 'cisai-cpf'); ?></th>
                            <td>
                                <span>₹</span>
                                <input type="number" step="0.01" name="cisai_cpf_gateway_fixed" value="<?php echo esc_attr(get_option('cisai_cpf_gateway_fixed', 3)); ?>" class="small-text">
                                <p class="description"><?php esc_html_e('Fixed charge per transaction (e.g., ₹3)', 'cisai-cpf'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Operational Cost', 'cisai-cpf'); ?></th>
                            <td>
                                <span>₹</span>
                                <input type="number" step="0.01" name="cisai_cpf_ops_cost" value="<?php echo esc_attr(get_option('cisai_cpf_ops_cost', 15)); ?>" class="small-text">
                                <p class="description"><?php esc_html_e('Per-order operational cost (hosting, support, processing, etc.)', 'cisai-cpf'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Display Settings -->
                <div class="cisai-cpf-settings-section">
                    <h2><?php esc_html_e('Customer Display Settings', 'cisai-cpf'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Show Breakdown', 'cisai-cpf'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="cisai_cpf_show_breakdown" value="1" <?php checked(get_option('cisai_cpf_show_breakdown', 'yes'), 'yes'); ?>>
                                    <?php esc_html_e('Show fee breakdown to customers at checkout', 'cisai-cpf'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Displays how CPF is calculated (recommended for transparency)', 'cisai-cpf'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Display Mode', 'cisai-cpf'); ?></th>
                            <td>
                                <select name="cisai_cpf_display_mode" class="regular-text">
                                    <option value="detailed" <?php selected(get_option('cisai_cpf_display_mode', 'detailed'), 'detailed'); ?>><?php esc_html_e('Detailed - Full card with breakdown and info', 'cisai-cpf'); ?></option>
                                    <option value="minimal" <?php selected(get_option('cisai_cpf_display_mode', 'detailed'), 'minimal'); ?>><?php esc_html_e('Minimal - Formula only', 'cisai-cpf'); ?></option>
                                    <option value="tooltip" <?php selected(get_option('cisai_cpf_display_mode', 'detailed'), 'tooltip'); ?>><?php esc_html_e('Tooltip - Icon with hover popup', 'cisai-cpf'); ?></option>
                                </select>
                                <p class="description">
                                    <strong><?php esc_html_e('Detailed:', 'cisai-cpf'); ?></strong> <?php esc_html_e('Beautiful card with full breakdown (recommended)', 'cisai-cpf'); ?><br>
                                    <strong><?php esc_html_e('Minimal:', 'cisai-cpf'); ?></strong> <?php esc_html_e('Clean formula: (5% × ₹600) + ₹2 = ₹32', 'cisai-cpf'); ?><br>
                                    <strong><?php esc_html_e('Tooltip:', 'cisai-cpf'); ?></strong> <?php esc_html_e('Most subtle, shows on hover', 'cisai-cpf'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Category Fees -->
                <div class="cisai-cpf-settings-section">
                    <h2><?php esc_html_e('Category Fees', 'cisai-cpf'); ?></h2>
                    <p class="description"><?php esc_html_e('Set additional fees per product category. These are added to CPF.', 'cisai-cpf'); ?></p>
                    
                    <?php if (empty($category_fees)) : ?>
                        <div class="notice notice-info inline">
                            <p><?php esc_html_e('No product categories found. Create categories first in Products → Categories.', 'cisai-cpf'); ?></p>
                        </div>
                    <?php else : ?>
                        <table class="widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 40%;"><?php esc_html_e('Category Name', 'cisai-cpf'); ?></th>
                                    <th style="width: 30%;"><?php esc_html_e('Slug', 'cisai-cpf'); ?></th>
                                    <th style="width: 30%;"><?php esc_html_e('Fee Amount (₹)', 'cisai-cpf'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($category_fees as $slug => $fee) :
                                    $term = get_term_by('slug', $slug, 'product_cat');
                                    if (!$term) continue;
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html($term->name); ?></strong></td>
                                    <td><code><?php echo esc_html($slug); ?></code></td>
                                    <td>
                                        <span>₹</span>
                                        <input type="number" step="0.01" min="0" name="category_fees[<?php echo esc_attr($slug); ?>]" value="<?php echo esc_attr($fee); ?>" class="small-text" style="width: 100px;">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="description" style="margin-top: 10px;">
                            <?php esc_html_e('Note: Category fees are applied once per cart, not per product. If cart has multiple categories, all applicable fees are added.', 'cisai-cpf'); ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Advanced Settings -->
                <div class="cisai-cpf-settings-section">
                    <h2><?php esc_html_e('Advanced Settings', 'cisai-cpf'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Minimum Order Value', 'cisai-cpf'); ?></th>
                            <td>
                                <span>₹</span>
                                <input type="number" step="0.01" min="0" name="cisai_cpf_min_order" value="<?php echo esc_attr(get_option('cisai_cpf_min_order', 0)); ?>" class="small-text">
                                <p class="description"><?php esc_html_e('Only apply CPF if cart subtotal is above this amount (0 = always apply)', 'cisai-cpf'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Exclude User Roles', 'cisai-cpf'); ?></th>
                            <td>
                                <fieldset>
                                    <?php foreach ($all_roles as $role_key => $role_info) : ?>
                                        <label style="display: block; margin-bottom: 8px;">
                                            <input type="checkbox" name="cisai_cpf_excluded_roles[]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, $excluded_roles)); ?>>
                                            <?php echo esc_html($role_info['name']); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </fieldset>
                                <p class="description"><?php esc_html_e('Users with these roles will not be charged CPF', 'cisai-cpf'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Save Button -->
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary button-large" value="<?php esc_attr_e('Save All Settings', 'cisai-cpf'); ?>">
                </p>
            </form>
            
            <!-- Quick Tips -->
            <div class="cisai-cpf-settings-section" style="background: #f0f6fc; border-left: 4px solid #0073aa;">
                <h2><?php esc_html_e('💡 Quick Tips', 'cisai-cpf'); ?></h2>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><strong><?php esc_html_e('Start Conservative:', 'cisai-cpf'); ?></strong> <?php esc_html_e('Begin with 3-5% and adjust based on data', 'cisai-cpf'); ?></li>
                    <li><strong><?php esc_html_e('Monitor Break-Even:', 'cisai-cpf'); ?></strong> <?php esc_html_e('Check Dashboard to see your break-even order value', 'cisai-cpf'); ?></li>
                    <li><strong><?php esc_html_e('Category Strategy:', 'cisai-cpf'); ?></strong> <?php esc_html_e('Charge more for high-margin or premium categories', 'cisai-cpf'); ?></li>
                    <li><strong><?php esc_html_e('Test Display Modes:', 'cisai-cpf'); ?></strong> <?php esc_html_e('Try all 3 modes to see what customers respond to best', 'cisai-cpf'); ?></li>
                    <li><strong><?php esc_html_e('Transparency Wins:', 'cisai-cpf'); ?></strong> <?php esc_html_e('Showing breakdown builds trust and reduces cart abandonment', 'cisai-cpf'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
}