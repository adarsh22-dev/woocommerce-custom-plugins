<?php
if (!defined('ABSPATH')) exit;

class Exchange_Pro_Settings {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function register_settings() {
        register_setting('exchange_pro_settings', 'exchange_pro_enable');
        register_setting('exchange_pro_settings', 'exchange_pro_pincode_validation');
        register_setting('exchange_pro_settings', 'exchange_pro_popup_theme');
        register_setting('exchange_pro_settings', 'exchange_pro_primary_color');
        register_setting('exchange_pro_settings', 'exchange_pro_button_text');
        register_setting('exchange_pro_settings', 'exchange_pro_currency_symbol');
        register_setting('exchange_pro_settings', 'exchange_pro_max_exchange_percentage');
        register_setting('exchange_pro_settings', 'exchange_pro_imei_mandatory');
        register_setting('exchange_pro_settings', 'exchange_pro_enable_logging');
        register_setting('exchange_pro_settings', 'exchange_pro_custom_css');
    }
    
    public function render() {
        if (isset($_POST['submit'])) {
            check_admin_referer('exchange_pro_settings');
            
            update_option('exchange_pro_enable', isset($_POST['exchange_pro_enable']) ? 'yes' : 'no');
            update_option('exchange_pro_pincode_validation', isset($_POST['exchange_pro_pincode_validation']) ? 'yes' : 'no');
            update_option('exchange_pro_popup_theme', sanitize_text_field($_POST['exchange_pro_popup_theme']));
            update_option('exchange_pro_primary_color', sanitize_hex_color($_POST['exchange_pro_primary_color']));
            update_option('exchange_pro_button_text', sanitize_text_field($_POST['exchange_pro_button_text']));
            update_option('exchange_pro_currency_symbol', sanitize_text_field($_POST['exchange_pro_currency_symbol']));
            update_option('exchange_pro_max_exchange_percentage', intval($_POST['exchange_pro_max_exchange_percentage']));
            update_option('exchange_pro_imei_mandatory', isset($_POST['exchange_pro_imei_mandatory']) ? 'yes' : 'no');
            update_option('exchange_pro_enable_logging', isset($_POST['exchange_pro_enable_logging']) ? 'yes' : 'no');
            // Raw CSS (will be scoped to the modal container on output)
            if (isset($_POST['exchange_pro_custom_css'])) {
                update_option('exchange_pro_custom_css', wp_unslash($_POST['exchange_pro_custom_css']));
            }
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'exchange-pro') . '</p></div>';
        }
        
        $enable = get_option('exchange_pro_enable', 'yes');
        $pincode_validation = get_option('exchange_pro_pincode_validation', 'yes');
        $theme = get_option('exchange_pro_popup_theme', 'light');
        $color = get_option('exchange_pro_primary_color', '#ff6600');
        $button_text = get_option('exchange_pro_button_text', 'Get Exchange Value');
        $currency = get_option('exchange_pro_currency_symbol', '₹');
        $max_percentage = get_option('exchange_pro_max_exchange_percentage', 80);
        $imei_mandatory = get_option('exchange_pro_imei_mandatory', 'yes');
        $enable_logging = get_option('exchange_pro_enable_logging', 'yes');
        $custom_css = get_option('exchange_pro_custom_css', '');
        
        ?>
        <div class="wrap">
            <h1><?php _e('Exchange Pro Settings', 'exchange-pro'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('exchange_pro_settings'); ?>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><?php _e('General Settings', 'exchange-pro'); ?></h5>
                    </div>
                    <div class="card-body">
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Enable Exchange', 'exchange-pro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="exchange_pro_enable" value="yes" <?php checked($enable, 'yes'); ?>>
                                        <?php _e('Enable exchange system globally', 'exchange-pro'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Primary Color', 'exchange-pro'); ?></th>
                                <td>
                                    <input type="color" name="exchange_pro_primary_color" value="<?php echo esc_attr($color); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Button Text', 'exchange-pro'); ?></th>
                                <td>
                                    <input type="text" name="exchange_pro_button_text" value="<?php echo esc_attr($button_text); ?>" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Currency Symbol', 'exchange-pro'); ?></th>
                                <td>
                                    <input type="text" name="exchange_pro_currency_symbol" value="<?php echo esc_attr($currency); ?>" class="small-text">
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Max Exchange Percentage', 'exchange-pro'); ?></th>
                                <td>
                                    <input type="number" name="exchange_pro_max_exchange_percentage" value="<?php echo esc_attr($max_percentage); ?>" min="0" max="100" class="small-text"> %
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Pincode Validation', 'exchange-pro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="exchange_pro_pincode_validation" value="yes" <?php checked($pincode_validation, 'yes'); ?>>
                                        <?php _e('Enable pincode validation', 'exchange-pro'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('IMEI Mandatory for Mobiles', 'exchange-pro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="exchange_pro_imei_mandatory" value="yes" <?php checked($imei_mandatory, 'yes'); ?>>
                                        <?php _e('Require IMEI for mobile exchanges', 'exchange-pro'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Enable Logging', 'exchange-pro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="exchange_pro_enable_logging" value="yes" <?php checked($enable_logging, 'yes'); ?>>
                                        <?php _e('Enable debug logging', 'exchange-pro'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Custom CSS (scoped)', 'exchange-pro'); ?></th>
                                <td>
                                    <textarea name="exchange_pro_custom_css" rows="8" class="large-text code" placeholder="#exchangeProModal .modal-content { border-radius: 20px; }\n#exchangeProModal .btn-primary { font-weight: 700; }\n"><?php echo esc_textarea($custom_css); ?></textarea>
                                    <p class="description">
                                        <?php _e('This CSS is automatically scoped to the Exchange popup to avoid affecting your theme. Use normal selectors; they will be prefixed with #exchangeProModal.', 'exchange-pro'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <p class="submit">
                    <button type="submit" name="submit" class="btn btn-primary">
                        <?php _e('Save Settings', 'exchange-pro'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }
}
