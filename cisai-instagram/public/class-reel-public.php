<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Story Public
 */
class Ecommreels_Story_Public
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'ecommreels_public_scripts'));
    }

    public function ecommreels_public_scripts()
    {
        // Always register scripts and styles so they are available for dependencies.
        wp_register_style(
            'ecommreels-fonts',
            ECOMMREELS_ASSETS . 'public/global.css',
            [],
            WP_REELS_VER
        );

        if (!class_exists('ReelsWPPro_Main')) {
            wp_register_script(
                'ecommreels-ecomm-reels-build-script',
                ECOMMREELS_ASSETS . 'build/index.js',
                ['react', 'react-jsx-runtime', 'wp-dom-ready', 'wp-element', 'wp-i18n'],
                WP_REELS_VER,
                true
            );
        }

        wp_localize_script('ecommreels-ecomm-reels-build-script', 'ecommreelsInfo', [
            'apiBase' => rest_url('wp-reels/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'url' => esc_url_raw(rest_url())
        ]);

        // For content outside of our custom Elementor widget (like classic editor, Gutenberg, or Elementor's generic Shortcode widget),
        // we still need to check the post content.
        if (is_singular() || is_page() || is_archive()) {
            global $post;

            if (empty($post->post_content)) {
                // No content to check. Our custom widget is handled by Elementor's dependency system.
                return;
            }

            $shortcodes = ['ecommreels-reel', 'reelswp-group', 'reelswp-reel'];
            $has_any_shortcode = false;
            foreach ($shortcodes as $shortcode) {
                if (has_shortcode($post->post_content, $shortcode)) {
                    $has_any_shortcode = true;
                    break;
                }
            }

            if ($has_any_shortcode) {
                wp_enqueue_style('ecommreels-fonts');
                if (wp_script_is('ecommreels-ecomm-reels-build-script', 'registered')) {
                    wp_enqueue_script('ecommreels-ecomm-reels-build-script');
                }
            }
        }
    }
}

new Ecommreels_Story_Public();
