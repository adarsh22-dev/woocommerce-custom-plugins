<?php
/**
 * Plugin Name: CISAI Recently Viewed Products
 * Description: Displays the last 4 recently viewed WooCommerce products in a clean 4-column grid. Elementor shortcode supported. Hides rating & category.
 * Version: 1.1
 * Author: CISAI
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class CISAI_Recently_Viewed_Products {

    public function __construct() {
        add_shortcode( 'recently_viewed_products', array( $this, 'render_shortcode' ) );
        add_action( 'wp_head', array( $this, 'add_styles' ) );
    }

    public function render_shortcode() {

        $viewed_products = ! empty( $_COOKIE['woocommerce_recently_viewed'] )
            ? array_reverse( array_filter( array_map( 'absint', explode( '|', $_COOKIE['woocommerce_recently_viewed'] ) ) ) )
            : array();

        if ( empty( $viewed_products ) ) {
            return '<p class="cisai-rvp-empty"></p>';
        }

        $viewed_products = array_slice( $viewed_products, 0, 4 );

        ob_start();
        echo '<div class="cisai-rvp-wrapper">';
        echo '<h2 class="cisai-rvp-title">Recently Viewed Products</h2>';
        echo do_shortcode('[products ids="' . implode(',', $viewed_products) . '" columns="4"]');
        echo '</div>';

        return ob_get_clean();
    }

    public function add_styles() {
        echo '
        <style>
            .cisai-rvp-wrapper {
                margin: 40px 0;
            }
            .cisai-rvp-title {
                font-size: 26px;
                font-weight: 700;
                margin-bottom: 18px;
                text-align: left;
            }
            @media (max-width: 1024px) {
                .products.columns-4 li.product {
                    width: 48% !important;
                }
            }
            @media (max-width: 600px) {
                .products.columns-4 li.product {
                    width: 100% !important;
                }
            }
            .cisai-rvp-empty {
                font-size: 18px;
                opacity: 0.7;
                text-align: center;
                margin: 40px 0;
            }

            /* ❌ Hide Star Rating */
            .products li.product .star-rating {
                display: none !important;
            }

            /* ❌ Hide Product Category (astra theme uses .ast-woo-product-category) */
            .ast-woo-product-category {
                display: none !important;
            }
        </style>
        ';
    }
}

new CISAI_Recently_Viewed_Products();
