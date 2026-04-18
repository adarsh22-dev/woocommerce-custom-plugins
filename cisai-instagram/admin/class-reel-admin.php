<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Story Admin
 *
 * Handles the admin functionality for the ecommreels plugin.
 */
class Ecommreels_Story_Admin
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'ecommreels_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'ecommreels_admin_enqueue_scripts']);
        add_shortcode('ecommreels-reel', [$this, 'ecommreels_shortcode']);
        add_shortcode('reelswp-group', [$this, 'reelswp_group_shortcode']);
        add_shortcode('reelswp-reel', [$this, 'reelswp_reel_shortcode']);
        add_action('wp_ajax_reelswp_upload_cropped_image', [$this, 'reelswp_upload_cropped_image']);
    }

    /**
     * Adds admin menu pages.
     */
    public function ecommreels_admin_menu()
    {
        add_menu_page(
            __('ReelsWP', 'ecomm-reels'),
            __('ReelsWP', 'ecomm-reels'),
            'manage_options',
            'reels-wp-groups',
            [$this, 'reelswp_admin_root'],
            'dashicons-editor-video'
        );

        add_submenu_page(
            'reels-wp-groups',
            __('ReelsWP - All Groups', 'ecomm-reels'),
            __('All Groups', 'ecomm-reels'),
            'manage_options',
            'reels-wp-groups',
            [$this, 'reelswp_admin_root']
        );

        add_submenu_page(
            'reels-wp-groups',
            __('ReelsWP - All Reels', 'ecomm-reels'),
            __('All Reels', 'ecomm-reels'),
            'manage_options',
            'reels-wp',
            [$this, 'ecommreels_reels_redirect']
        );

        add_submenu_page(
            'reels-wp-groups',
            __('Settings', 'ecomm-reels'),
            __('Settings', 'ecomm-reels'),
            'manage_options',
            'ecomm-reels-settings',
            [$this, 'ecommreels_settings_redirect']
        );
    }

    public function ecommreels_settings_redirect()
    {
        wp_safe_redirect(admin_url('admin.php?page=reels-wp-groups#/?view=settings'));
        exit;
    }

    public function ecommreels_reels_redirect()
    {
        wp_safe_redirect(admin_url('admin.php?page=reels-wp-groups#/?view=all-reels'));
        exit;
    }
    /**
     * Displays the story list page.
     */
    public function reelswp_admin_root()
    {
        echo '<div id="ecommreels-admin-app"></div>';
    }

    /**
     * Enqueues admin scripts and styles.
     */
    public function ecommreels_admin_enqueue_scripts()
    {
        $current_screen = get_current_screen();
        if ($current_screen && strpos($current_screen->base, 'reels-wp') === false) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'ecommreels-fonts',
            ECOMMREELS_ASSETS . 'public/global.css',
            [],
            WP_REELS_VER
        );

        $is_pro_active = class_exists('ReelsWPPro_Main');
        $is_pro_license_active = false;
        if ($is_pro_active && class_exists('EcommreelsPro_Story_Admin') && method_exists('EcommreelsPro_Story_Admin', 'get_license_status')) {
            $pro_admin = new EcommreelsPro_Story_Admin();
            if ($pro_admin->get_license_status()) {
                $is_pro_license_active = true;
            }
        }

        if (!$is_pro_license_active) {
            wp_enqueue_script(
                'ecommreels-ecomm-reels-build-script',
                ECOMMREELS_ASSETS . 'build/index.js',
                ['react', 'react-jsx-runtime', 'wp-dom-ready', 'wp-element', 'wp-i18n'],
                WP_REELS_VER,
                true
            );
            // wp_enqueue_style(
            //     'ecommreels-ecomm-reels-build-styles',
            //     ECOMMREELS_ASSETS . 'build/index.css',
            //     [],
            //     WP_REELS_VER
            // );
        }
        wp_localize_script(
            'ecommreels-ecomm-reels-build-script',
            'ecommreelsInfo',
            [
                'url' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest'),
                'apiBase' => rest_url('wp-reels/v1/'),
                'createStoryNonce' => wp_create_nonce('ecommreels_action'),
                'currentStoryUrl' => home_url('/wp-admin/admin.php?page=my-reels&story='),
                'isProActive' => $is_pro_active,
                'isLicenseActive' => $is_pro_license_active,
                'license_page' => home_url() . '/wp-admin/admin.php?page=ecomm-reels-license'
            ]
        );

        wp_localize_script(
            'ecommreels-ecomm-reels-build-script',
            'reelswp_media_api',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('reelswp_nonce'),
                'action'   => 'reelswp_upload_cropped_image',
            ]
        );
    }

    /**
     * Renders the shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function ecommreels_shortcode($atts)
    {
        $atts = shortcode_atts(['id' => ''], $atts, 'ecommreels-reel');
        $id = intval($atts['id']);

        if ($id <= 0) {
            return '<p>' . esc_html__('Invalid reel ID.', 'ecomm-reels') . '</p>';
        }

        return '<div class="ecommreels-short-code" id="ecommreels-short-code-' . esc_attr($id) . '" data-id="' . esc_attr($id) . '"></div>';
    }

    /**
     * Renders the reelswp-reel shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function reelswp_reel_shortcode($atts)
    {
        $atts = shortcode_atts(['id' => ''], $atts, 'reelswp-reel');
        $id = intval($atts['id']);

        if ($id <= 0) {
            return '<p>' . esc_html__('Invalid reel ID.', 'ecomm-reels') . '</p>';
        }

        return '<div class="reelswp-reel-short-code" id="reelswp-reel-short-code-' . esc_attr($id) . '" data-id="' . esc_attr($id) . '"></div>';
    }

    /**
     * Renders the reelswp-group shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function reelswp_group_shortcode($atts)
    {
        $atts = shortcode_atts(['id' => ''], $atts, 'reelswp-group');
        $id = intval($atts['id']);

        if ($id <= 0) {
            return '<p>' . esc_html__('Invalid group ID.', 'ecomm-reels') . '</p>';
        }

        return '<div class="reelswp-group-short-code" id="reelswp-group-short-code-' . esc_attr($id) . '" data-id="' . esc_attr($id) . '"></div>';
    }


    public function reelswp_upload_cropped_image()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'reelswp_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $uploaded_file = $_FILES['file'];
        $upload_overrides = ['test_form' => false];
        $move_file = wp_handle_upload($uploaded_file, $upload_overrides);

        if ($move_file && !isset($move_file['error'])) {
            wp_send_json_success(['url' => $move_file['url']]);
        } else {
            wp_send_json_error($move_file['error']);
        }
    }
}

new Ecommreels_Story_Admin();
