<?php
if (!defined('ABSPATH')) {
    exit;
}

class reelswp_feedback
{
    private $plugin_version = WP_REELS_VER;
    private $plugin_name    = 'ReelsWP – Shoppable Videos & UGC Carousels for WooCommerce';
    private $plugin_slug    = 'ecomm-reels';
    private $feedback_url   = 'https://crm.dstudio.asia/api/feedback';

    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'reelswp_enqueue_feedback_scripts'));
        add_action('admin_head', array($this, 'reelswp_show_deactivate_feedback_popup'));
        add_action('wp_ajax_' . $this->plugin_slug . '_reelswp_submit_deactivation_response', array($this, 'reelswp_submit_deactivation_response'));
    }

    function reelswp_enqueue_feedback_scripts()
    {
        $screen = get_current_screen();
        if (isset($screen) && $screen->id == 'plugins') {
            $ajax_nonce = wp_create_nonce('reelswp_deactivate_plugin');
            wp_enqueue_style('reelswp-deactivation-css', ECOMMREELS_ASSETS . 'assets/admin/css/feedback.css', null, $this->plugin_version);
            wp_enqueue_script('reelswp-deactivation-script', ECOMMREELS_ASSETS . 'assets/admin/js/feedback.js', array('jquery'), $this->plugin_version, true);
            wp_localize_script('reelswp-deactivation-script', 'reelswp_ajax', array('nonce' => $ajax_nonce));
        }
    }

    public function reelswp_show_deactivate_feedback_popup()
    {
        $screen = get_current_screen();
        if (!isset($screen) || $screen->id != 'plugins') {
            return;
        }
?>
        <div class="reelswp-deactivation-popup reelswp-hidden" data-type="wrapper" data-slug="<?php echo esc_attr($this->plugin_slug); ?>">
            <div class="reelswp-overlay">
                <div class="reelswp-close"><i class="dashicons dashicons-no"></i></div>
                <div class="reelswp-body">
                    <div class="reelswp-title-wrap">
                        <div class="reelswp-img-wrap">
                            <img src="<?php echo esc_url(ECOMMREELS_ASSETS . 'assets/images/logo.webp'); ?>" alt="Reels WP">
                        </div>
                        <?php echo esc_html(__('Goodbyes are always hard', 'ecomm-reels')); ?>
                    </div>
                    <div class="reelswp-messages-wrap">
                        <p><?php echo esc_html(__('Would you quickly give us your reason for doing so?', 'ecomm-reels')); ?></p>
                    </div>
                    <div class="reelswp-options-wrap">
                        <label class="reelswp-option-label">
                            <input type="radio" name="feedback" value="temp" class="reelswp-radio">
                            <?php echo esc_html(__('Temporary deactivation', 'ecomm-reels')); ?>
                        </label>
                        <label class="reelswp-option-label">
                            <input type="radio" name="feedback" value="setup" class="reelswp-radio">
                            <?php echo esc_html(__('Set up is too difficult', 'ecomm-reels')); ?>
                        </label>
                        <label class="reelswp-option-label">
                            <input type="radio" name="feedback" value="e-issues" class="reelswp-radio">
                            <?php echo esc_html(__('Causes issues with Elementor', 'ecomm-reels')); ?>
                        </label>
                        <label class="reelswp-option-label">
                            <input type="radio" name="feedback" value="documentation" class="reelswp-radio">
                            <?php echo esc_html(__('Lack of documentation', 'ecomm-reels')); ?>
                        </label>
                        <label class="reelswp-option-label">
                            <input type="radio" name="feedback" value="features" class="reelswp-radio">
                            <?php echo esc_html(__('Not the features I wanted', 'ecomm-reels')); ?>
                        </label>
                        <label class="reelswp-option-label">
                            <input type="radio" name="feedback" value="better-plugin" class="reelswp-radio">
                            <?php echo esc_html(__('Found a better plugin', 'ecomm-reels')); ?>
                        </label>
                        <label class="reelswp-option-label">
                            <input type="radio" name="feedback" value="incompatibility" class="reelswp-radio">
                            <?php echo esc_html(__('Incompatible with theme or plugin', 'ecomm-reels')); ?>
                        </label>
                        <label class="reelswp-option-label">
                            <input type="radio" name="feedback" value="other" class="reelswp-radio">
                            <?php echo esc_html(__('Other', 'ecomm-reels')); ?>
                        </label>
                    </div>
                    <div class="reelswp-messages-wrap reelswp-hidden" data-feedback>
                        <p class="reelswp-hidden" data-feedback="setup"><?php echo esc_html(__('What was the difficult part?', 'ecomm-reels')); ?></p>
                        <p class="reelswp-hidden" data-feedback="e-issues"><?php echo esc_html(__('What was the issue?', 'ecomm-reels')); ?></p>
                        <p class="reelswp-hidden" data-feedback="documentation"><?php echo esc_html(__('What can we describe more?', 'ecomm-reels')); ?></p>
                        <p class="reelswp-hidden" data-feedback="features"><?php echo esc_html(__('How could we improve?', 'ecomm-reels')); ?></p>
                        <p class="reelswp-hidden" data-feedback="better-plugin"><?php echo esc_html(__('Can you mention it?', 'ecomm-reels')); ?></p>
                        <p class="reelswp-hidden" data-feedback="incompatibility"><?php echo esc_html(__('With what plugin or theme is incompatible?', 'ecomm-reels')); ?></p>
                        <p class="reelswp-hidden" data-feedback="other"><?php echo esc_html(__('Please specify:', 'ecomm-reels')); ?></p>
                    </div>
                    <div class="reelswp-options-wrap reelswp-hidden" data-feedback>
                        <label class="reelswp-textarea-label">
                            <textarea name="suggestions" rows="2" class="reelswp-textarea" placeholder="<?php echo esc_attr(__('Tell us more about your reason...', 'ecomm-reels')); ?>"></textarea>
                        </label>
                    </div>
                    <div class="reelswp-messages-wrap reelswp-hidden" data-feedback>
                        <p><?php echo esc_html(__('Would you like to share your e-mail and elements usage with us so that we can write you back?', 'ecomm-reels')); ?></p>
                    </div>
                    <div class="reelswp-options-wrap reelswp-hidden dennsimc-anonymous" data-feedback>
                        <label class="reelswp-checkbox-label">
                            <input type="checkbox" name="anonymous" value="1" class="reelswp-checkbox">
                            <?php echo esc_html(__('No, I\'d like to stay anonymous', 'ecomm-reels')); ?>
                        </label>
                    </div>

                    <div class="reelswp-buttons-wrap">
                        <button class="reelswp-btn reelswp-skip-btn"><?php echo esc_html(__('Skip & Deactivate', 'ecomm-reels')); ?></button>
                        <button class="reelswp-btn reelswp-submit-btn"><?php echo esc_html(__('Submit & Deactivate', 'ecomm-reels')); ?></button>
                    </div>
                </div>
            </div>
        </div>
<?php
    }

    /**
     * Collect comprehensive feedback data
     */
    private function reelswp_collect_feedback_data($reason, $message, $anonymous = false)
    {
        global $wpdb;

        // Basic plugin and server info
        $plugin_data = array(
            'deactivated_plugin' => array(
                'version' => $this->plugin_version,
                'memory'  => 'Memory: ' . size_format(wp_convert_hr_to_bytes(ini_get('memory_limit'))),
                'time'    => 'Time: ' . ini_get('max_execution_time'),
                'deactivate' => 'Deactivation: ' . gmdate('j F, Y', time()),
                'uninstall_reason' => $reason,
                'uninstall_details' => $message
            ),
        );

        // Add domain info only if not anonymous
        if (!$anonymous) {
            $plugin_data['deactivated_plugin']['domain'] = $this->reelswp_get_site_domain();
        }

        // WordPress environment data
        $wordpress_data = array(
            'wp_version' => get_bloginfo('version'),
            'wp_locale' => get_locale(),
            'wp_debug' => defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled',
            'wp_memory_limit' => ini_get('memory_limit'),
            'php_version' => phpversion(),
            'mysql_version' => $wpdb->db_version(),
            'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : 'N/A'
        );

        // Theme and plugins data
        $environment_data = array(
            'active_theme' => $this->reelswp_get_active_theme(),
            'active_plugins' => $this->reelswp_get_active_plugins(),
            'wp_multisite' => is_multisite() ? 'Yes' : 'No'
        );

        // User data (only if not anonymous)
        $user_data = array();
        if (!$anonymous) {
            $current_user = wp_get_current_user();
            $user_data = array(
                'email' => $current_user->user_email,
                'first_name' => $current_user->first_name,
                'last_name' => $current_user->last_name,
                'domain' => $this->reelswp_get_site_domain()
            );
        }

        return array(
            'plugin_data' => $plugin_data,
            'wordpress_data' => $wordpress_data,
            'environment_data' => $environment_data,
            'user_data' => $user_data,
            'anonymous' => $anonymous
        );
    }

    /**
     * Get active theme information
     */
    private function reelswp_get_active_theme()
    {
        $theme = wp_get_theme();
        return array(
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version'),
            'author' => $theme->get('Author')
        );
    }

    /**
     * Get active plugins list
     */
    private function reelswp_get_active_plugins()
    {
        if (!function_exists('get_plugins')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $active_plugins = get_option('active_plugins', array());
        $plugins_list = array();

        foreach ($active_plugins as $plugin_path) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_path);
            $plugins_list[] = array(
                'name' => $plugin_data['Name'],
                'version' => $plugin_data['Version']
            );
        }

        return $plugins_list;
    }

    /**
     * Get site domain
     */
    private function reelswp_get_site_domain()
    {
        return wp_parse_url(get_site_url(), PHP_URL_HOST);
    }

    function reelswp_submit_deactivation_response()
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce(sanitize_key(wp_unslash($_POST['_wpnonce'])), 'reelswp_deactivate_plugin')) {
            wp_send_json_error('Security check failed');
        }

        // Get the submitted data from JavaScript
        $reason = isset($_POST['reason']) ? sanitize_text_field(wp_unslash($_POST['reason'])) : 'other';
        $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : 'N/A';
        $anonymous = isset($_POST['anonymous']) && $_POST['anonymous'] === '1';

        // Map reasons to expected API values
        $reason_map = array(
            'temp' => 'temporary_deactivation',
            'setup' => 'setup_difficult',
            'e-issues' => 'elementor_issues',
            'documentation' => 'lack_documentation',
            'features' => 'missing_features',
            'better-plugin' => 'better_plugin',
            'incompatibility' => 'incompatible',
            'other' => 'other'
        );

        $final_reason = isset($reason_map[$reason]) ? $reason_map[$reason] : 'other';

        // Feedback data
        $feedback_data = $this->reelswp_collect_feedback_data($final_reason, $message, $anonymous);

        $api_data = array(
            'server_info' => $feedback_data['wordpress_data'],
            'extra_details' => array(
                'wp_theme'       => $feedback_data['environment_data']['active_theme'],
                'active_plugins' => $feedback_data['environment_data']['active_plugins'],
            ),
            'plugin_version' => $this->plugin_version,
            'plugin_name'    => $this->plugin_name,
            'reason'         => $final_reason,
            'review'         => $message,
            'email'          => $anonymous ? null : $feedback_data['user_data']['email'],
            'domain'         => $anonymous ? null : $feedback_data['user_data']['domain'],
            'site_id'        => $anonymous ? 'anonymous_' . wp_generate_password(12, false) : md5(get_site_url() . '-13'),
            'product_uuid' => '1761028288-947b05b40c4949f79499c65b3296b9e7',
            'anonymous'      => $anonymous,
            'comprehensive_data' => $feedback_data,
        );

        $args = array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8',
                'Accept'       => 'application/json',
            ),
            'body'    => wp_json_encode($api_data),
        );

        $response = wp_remote_post($this->feedback_url, $args);

        wp_send_json_success('Feedback submitted successfully');
    }
}

new reelswp_feedback();
