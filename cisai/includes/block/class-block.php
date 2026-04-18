<?php

if (!defined('ABSPATH')) {
    exit;
}

class Ecommreels_Block
{
    public function __construct()
    {
        add_action('init', [$this, 'register_block']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_block_editor_assets']);
    }

    public function register_block()
    {
        register_block_type(ECOMMREELS_PATH . 'src/block');
    }

    public function enqueue_block_editor_assets()
    {
        $screen = get_current_screen();
        if ($screen && $screen->is_block_editor()) {
            wp_enqueue_script(
                'ecomm-reels-block-editor',
                ECOMMREELS_ASSETS . 'build/block.js',
                ['wp-blocks', 'wp-i18n', 'wp-element', 'wp-block-editor', 'wp-components'],
                WP_REELS_VER,
                true
            );

            // Pass data to the block editor script
            wp_localize_script(
                'ecomm-reels-block-editor',
                'ecommreelsInfo',
                [
                    'url' => esc_url_raw(rest_url()),
                    'nonce' => wp_create_nonce('wp_rest'),
                    'apiBase' => rest_url('wp-reels/v1/'),
                ]
            );
        }
    }
}

new Ecommreels_Block();
