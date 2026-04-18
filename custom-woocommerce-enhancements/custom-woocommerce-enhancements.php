<?php
/*
 * Plugin Name: CISAI Custom WooCommerce Enhancements
 * Plugin URI: https://example.com
 * Description: Custom enhancements for WooCommerce including login page styling, product description shortcodes, image collage, cache management, and more.
 * Version: 1.0.0
 * Author: Adarsh VinodKumar Singh
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: custom-woocommerce-enhancements
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Enqueue Parent Theme Styles
if (!function_exists('chld_thm_cfg_locale_css')):
    function chld_thm_cfg_locale_css($uri) {
        if (empty($uri) && is_rtl() && file_exists(get_template_directory() . '/rtl.css')) {
            $uri = get_template_directory_uri() . '/rtl.css';
        }
        return $uri;
    }
endif;
add_filter('locale_stylesheet_uri', 'chld_thm_cfg_locale_css');

if (!function_exists('chld_thm_cfg_parent_css')):
    function chld_thm_cfg_parent_css() {
        wp_enqueue_style('chld_thm_cfg_parent', trailingslashit(get_template_directory_uri()) . 'style.css', array());
    }
endif;
add_action('wp_enqueue_scripts', 'chld_thm_cfg_parent_css', 10);

if (!function_exists('child_theme_configurator_css')):
    function child_theme_configurator_css() {
        wp_enqueue_style('chld_thm_cfg_separate', trailingslashit(get_stylesheet_directory_uri()) . 'ctc-style.css', array('chld_thm_cfg_parent'));
    }
endif;
add_action('wp_enqueue_scripts', 'child_theme_configurator_css', 10);

// Custom Login Video Background
function custom_login_video_background() {
    ?>
    <style>
        body.login {
            margin: 0;
            padding: 0;
            background: transparent !important;
        }
        #login {
            position: relative;
            z-index: 2;
        }
        #login-video-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 1;
            object-fit: cover;
            opacity: 1;
            pointer-events: none;
            transition: opacity 0.4s ease-in-out;
        }
        .wp-login-lost-password {
            color: #fff !important;
            font-weight: 600 !important;
        }
        .login #backtoblog a {
            text-decoration: none;
            color: #fff !important;
            font-weight: 600 !important;
        }
        .login form {
            margin: 24px 0;
            padding: 26px 24px;
            font-weight: 400;
            overflow: hidden;
            background: #ffffff38 !important;
            border: 1px solid #fff !important;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
            border-width: 0px 1px 0px 1px !important;
        }
        .login h1 {
            text-align: center;
            background: #ffffff38 !important;
            border: 1px solid #fff !important;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
            border-width: 1px 1px 0px 1px !important;
        }
        .login h1 a {
            margin: 0 auto 0px !important;
        }
        .login form {
            margin: 0px 0 !important;
        }
        .login h1 {
            text-align: center;
            padding-top: 24px !important;
        }
        .login label {
            font-size: 14px;
            line-height: 1.5;
            display: inline-block;
            margin-bottom: 3px;
            color: #fff !important;
            font-weight: 600 !important;
        }
        .login #nav {
            margin: 0px 0 0 !important;
            background: #ffffff38 !important;
            border: 1px solid #fff !important;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
            border-width: 0px 1px 0px 1px !important;
            text-align: center !important;
        }
        #backtoblog {
            margin: 0px 0 !important;
            word-wrap: break-word;
            background: #ffffff38 !important;
            padding-bottom: 20px !important;
            border-radius: 0px 0px 10px 10px !important;
            border: 1px solid #fff !important;
            border-width: 0px 1px 1px 1px !important;
            text-align: center !important;
        }
        .login h1 {
            text-align: center !important;
            border-radius: 10px 10px 0px 0px !important;
        }
        .button-primary:hover {
            background: #000 !important;
            border-color: #fff !important;
            color: #fff;
        }
        .wp-core-ui .button-primary {
            background: #75c32c !important;
            border-color: #fff !important;
            color: #fff;
            text-decoration: none;
            text-shadow: none;
        }
        .login form .forgetmenot {
            font-weight: 400;
            float: left;
            margin-bottom: 0;
            width: 100% !important;
            text-align: center !important;
        }
        #wp-submit {
            width: 100% !important;
        }
        @media (max-width: 768px) {
            #login {
                padding: 50% 0 0 !important;
            }
        }
        #login {
            width: 320px;
            padding: 10% !important;
            margin: auto;
        }
        @media only screen and (min-device-width: 320px) and (max-device-width: 480px) {
            #login-video-bg {
                position: fixed;
                top: 0;
                left: 0;
                width: 130vw;
                height: 130vh;
                z-index: 1;
                object-fit: cover;
                opacity: 1;
                pointer-events: none;
                transition: opacity 0.4s ease-in-out;
            }
            .login h1 a {
                background-image: url(http://10.31.1.84/wp-content/uploads/2025/10/imgi_1_imgi_1_Xtronic-dark-1-removebg-preview-768x252-1.png) !important;
                background-size: 84px !important;
                background-position: center top !important;
                background-repeat: no-repeat !important;
                width: 84px !important;
                height: 30px !important;
            }
            #login {
                width: 320px;
                padding: 50% 0px 50px 0px !important;
                margin: auto;
            }
        }
    </style>
    <video autoplay muted playsinline loop id="login-video-bg">
        <source src="http://10.31.1.84/wp-content/uploads/2025/10/6009484_Caucasian_Young_3840x2160.mp4" type="video/mp4">
    </video>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const video = document.getElementById('login-video-bg');
            if (video) {
                video.addEventListener('ended', () => {
                    video.currentTime = 0;
                    video.play();
                });
            }
        });
    </script>
    <?php
}
add_action('login_enqueue_scripts', 'custom_login_video_background');

// Custom Login Logo
function custom_login_logo() {
    ?>
    <style>
        .login h1 a {
            background-image: url('http://10.31.1.84/wp-content/uploads/2025/10/imgi_1_imgi_1_Xtronic-dark-1-removebg-preview-768x252-1.png') !important;
            background-size: 100% !important;
            background-position: center top !important;
            background-repeat: no-repeat !important;
            width: 100% !important;
            height: 100px !important;
            text-indent: -9999px !important;
            overflow: hidden !important;
            background-color: #ffffff00 !important;
            border-radius: 10px !important;
        }
    </style>
    <?php
}
add_action('login_enqueue_scripts', 'custom_login_logo');

// Disable Inspect Element for Non-Admins
function disable_inspect_scripts() {
    if (!current_user_can('manage_options')) {
        ?>
        <script>
            document.addEventListener('contextmenu', event => event.preventDefault());
            document.addEventListener('keydown', function (event) {
                if (event.keyCode === 123 || 
                    (event.ctrlKey && event.shiftKey && event.keyCode === 73) ||
                    (event.ctrlKey && event.shiftKey && event.keyCode === 74) ||
                    (event.ctrlKey && event.keyCode === 85) ||
                    (event.ctrlKey && event.keyCode === 83) ||
                    (event.ctrlKey && event.shiftKey && event.keyCode === 67)) {
                    event.preventDefault();
                    alert('Inspecting is disabled on this site.');
                }
            });
        </script>
        <?php
    }
}
add_action('wp_head', 'disable_inspect_scripts');

// Custom Product Description Shortcode
function custom_product_description_shortcode() {
    global $product;

    if (!is_a($product, 'WC_Product')) {
        return '';
    }

    $description = $product->get_description();
    $description = preg_replace('/<img[^>]+>/i', '', $description);

    ob_start();
    ?>
    <div class="custom-product-description">
        <div class="short-desc">
            <?php echo wp_kses_post(wp_trim_words($description, 20, '...')); ?>
        </div>
        <div class="full-desc" style="display: none;">
            <?php echo wp_kses_post($description); ?>
        </div>
        <a href="#" class="toggle-desc">Read more</a>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll('.custom-product-description .toggle-desc').forEach(function(toggle) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const container = this.closest('.custom-product-description');
                    const fullDesc = container.querySelector('.full-desc');
                    const shortDesc = container.querySelector('.short-desc');
                    const isVisible = fullDesc.style.display === 'block';
                    fullDesc.style.display = isVisible ? 'none' : 'block';
                    shortDesc.style.display = isVisible ? 'block' : 'none';
                    this.textContent = isVisible ? 'Read more' : 'Read less';
                });
            });
        });
    </script>
    <style>
        .custom-product-description .toggle-desc {
            color: #0073aa;
            text-decoration: underline;
            cursor: pointer;
            display: inline-block;
            margin-top: 10px;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('product_description', 'custom_product_description_shortcode');

// Product Description Image Collage Shortcode
function product_description_image_collage_shortcode() {
    global $product;

    if (!is_a($product, 'WC_Product')) {
        return '';
    }

    $description = $product->get_description();
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $description);
    $imageTags = $doc->getElementsByTagName('img');

    $imagesHtml = '';
    foreach ($imageTags as $img) {
        $src = $img->getAttribute('src');
        $alt = $img->getAttribute('alt');
        if (!empty($src)) {
            $imagesHtml .= '<div class="swiper-slide"><img src="' . esc_url($src) . '" alt="' . esc_attr($alt) . '"></div>';
        }
    }

    if (empty($imagesHtml)) {
        return '';
    }

    ob_start();
    ?>
    <div class="product-description-collage">
        <?php echo str_replace('swiper-slide', 'desc-image', $imagesHtml); ?>
    </div>
    <div class="product-description-swiper swiper">
        <div class="swiper-wrapper">
            <?php echo $imagesHtml; ?>
        </div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
        <div class="swiper-pagination"></div>
    </div>
    <style>
        .product-description-collage {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .product-description-collage .desc-image {
            flex: 1 1 calc(33.333% - 10px);
            max-width: calc(33.333% - 10px);
            box-sizing: border-box;
        }
        .product-description-collage img {
            width: 100%;
            height: auto;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .product-description-swiper {
            display: none;
            margin-top: 20px;
            width: 100%;
        }
        .product-description-swiper img {
            width: 100%;
            height: auto;
            object-fit: cover;
            border-radius: 6px;
        }
        @media (max-width: 480px) {
            .product-description-collage {
                display: none;
            }
            .product-description-swiper {
                display: block;
            }
        }
        
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function initSwiperIfNeeded() {
                const isMobile = window.innerWidth <= 480;
                const swiperContainer = document.querySelector('.product-description-swiper');
                if (isMobile && swiperContainer && swiperContainer.offsetHeight > 0) {
                    new Swiper('.product-description-swiper', {
                        loop: true,
                        slidesPerView: 1,
                        spaceBetween: 10,
                        pagination: { el: '.swiper-pagination', clickable: true },
                        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
                    });
                } else {
                    setTimeout(initSwiperIfNeeded, 100);
                }
            }
            initSwiperIfNeeded();
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('product_description_images', 'product_description_image_collage_shortcode');

// Replace WooCommerce Short Description with Toggle
add_filter('woocommerce_short_description', 'custom_short_description_with_toggle');
function custom_short_description_with_toggle($description) {
    $trimmed = wp_trim_words($description, 12, '...');
    ob_start();
    ?>
    <div class="woocommerce-product-details__short-description custom-short-description-wrapper">
        <div class="short-description-preview"><?php echo wp_kses_post($trimmed); ?></div>
        <div class="short-description-full" style="display:none;"><?php echo wp_kses_post($description); ?></div>
        <a href="javascript:void(0);" class="toggle-short-description">Read More</a>
    </div>
    <?php
    return ob_get_clean();
}

add_action('wp_footer', 'custom_short_description_toggle_script');
function custom_short_description_toggle_script() {
    if (is_product()) {
        ?>
        <style>
            .custom-short-description-wrapper { margin-top: 15px; }
            .toggle-short-description { display: inline-block; margin-top: 10px; color: #05b895 !important; cursor: pointer; font-weight: bold; text-decoration: underline; }
            @media (max-width: 768px) { .custom-short-description-wrapper { font-size: 14px; } }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const toggleLink = document.querySelector('.toggle-short-description');
                const preview = document.querySelector('.short-description-preview');
                const full = document.querySelector('.short-description-full');
                if (toggleLink && preview && full) {
                    toggleLink.addEventListener('click', function () {
                        const isExpanded = full.style.display === 'block';
                        full.style.display = isExpanded ? 'none' : 'block';
                        preview.style.display = isExpanded ? 'block' : 'none';
                        toggleLink.textContent = isExpanded ? 'Read More' : 'Read Less';
                    });
                }
            });
        </script>
        <?php
    }
}

// Remove Images from Product Description Tab
add_filter('woocommerce_product_tabs', 'custom_remove_images_from_description_tab');
function custom_remove_images_from_description_tab($tabs) {
    if (isset($tabs['description']['callback'])) {
        $tabs['description']['callback'] = 'custom_description_tab_without_images';
    }
    return $tabs;
}
function custom_description_tab_without_images() {
    global $post;
    if (!$post) return;
    $content = $post->post_content;
    $content = preg_replace('/<img[^>]*>/i', '', $content);
    $content = apply_filters('the_content', $content);
    echo '<h3>' . esc_html__('Product description', 'woocommerce') . '</h3>';
    echo $content;
}

// Cache Management
add_action('save_post', 'auto_flush_cache_on_save');
function auto_flush_cache_on_save($post_id) {
    auto_flush_all_caches();
}
add_action('wp_login', 'auto_flush_cache_on_login');
add_action('wp_logout', 'auto_flush_cache_on_logout');
function auto_flush_cache_on_login() {
    auto_flush_all_caches();
}
function auto_flush_cache_on_logout() {
    auto_flush_all_caches();
}
if (!wp_next_scheduled('auto_hourly_cache_flush')) {
    wp_schedule_event(time(), 'hourly', 'auto_hourly_cache_flush');
}
add_action('auto_hourly_cache_flush', 'auto_flush_all_caches');
function auto_flush_all_caches() {
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();
    }
    if (function_exists('w3tc_flush_all')) {
        w3tc_flush_all();
    }
    if (function_exists('autoptimize_clear_cache')) {
        autoptimize_clear_cache();
    }
    global $wpdb;
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_%'");
}

// Image Optimization
function custom_resize_image_sizes() {
    update_option('large_size_w', 1024);
    update_option('large_size_h', 768);
    update_option('medium_size_w', 512);
    update_option('medium_size_h', 384);
}
add_action('after_setup_theme', 'custom_resize_image_sizes');

function compress_image_on_upload($metadata) {
    if (!class_exists('Imagick')) {
        return $metadata;
    }
    $upload_dir = wp_upload_dir();
    $image_path = trailingslashit($upload_dir['basedir']) . $metadata['file'];
    if (!file_exists($image_path)) {
        return $metadata;
    }
    try {
        $imagick = new Imagick($image_path);
        if ($imagick->getImageMimeType() === 'image/jpeg') {
            $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
            $imagick->setImageCompressionQuality(75);
            $imagick->stripImage();
            $imagick->writeImage($image_path);
        }
        $imagick->clear();
        $imagick->destroy();
    } catch (Exception $e) {
        // Skip on error
    }
    return $metadata;
}
add_filter('wp_generate_attachment_metadata', 'compress_image_on_upload');

function add_lazy_loading_to_images($content) {
    if (strpos($content, 'loading=') === false) {
        $content = preg_replace('/<img(.*?)>/', '<img loading="lazy"$1>', $content);
    }
    return $content;
}
add_filter('the_content', 'add_lazy_loading_to_images');

// Block Localhost and Local URLs
function block_localhost_and_local_urls() {
    $current_url = $_SERVER['REQUEST_URI'];
    if (strpos($current_url, 'localhost') !== false || strpos($current_url, 'local') !== false) {
        wp_redirect(home_url());
        exit;
    }
}
add_action('template_redirect', 'block_localhost_and_local_urls');

// Custom WooCommerce Login & Register Shortcodes
add_shortcode('custom_wc_login', function() {
    if (is_user_logged_in()) {
        return '<p>You are already logged in. <a href="' . wc_get_page_permalink('myaccount') . '">Go to My Account</a></p>';
    }
    ob_start();
    ?>
    <div class="woocommerce">
        <h2><?php esc_html_e('Login', 'woocommerce'); ?></h2>
        <?php woocommerce_login_form(); ?>
        <p class="switch-link">
            <?php esc_html_e("Don't have an account?", "woocommerce"); ?>
            <a href="<?php echo esc_url(site_url('/register/')); ?>"><?php esc_html_e('Register here', 'woocommerce'); ?></a>
        </p>
    </div>
    <?php
    return ob_get_clean();
});

add_shortcode('custom_wc_register', function() {
    if (is_user_logged_in()) {
        return '<p>You are already registered and logged in. <a href="' . wc_get_page_permalink('myaccount') . '">Go to My Account</a></p>';
    }
    ob_start();
    ?>
    <div class="woocommerce">
        <h2><?php esc_html_e('Register', 'woocommerce'); ?></h2>
        <form method="post" class="woocommerce-form woocommerce-form-register register">
            <?php do_action('woocommerce_register_form_start'); ?>
            <?php if ('no' === get_option('woocommerce_registration_generate_username')) : ?>
                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="reg_username"><?php esc_html_e('Username', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
                    <input type="text" class="woocommerce-Input input-text" name="username" id="reg_username" autocomplete="username"
                        value="<?php if (!empty($_POST['username'])) echo esc_attr(wp_unslash($_POST['username'])); ?>" />
                </p>
            <?php endif; ?>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="reg_email"><?php esc_html_e('Email address', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
                <input type="email" class="woocommerce-Input input-text" name="email" id="reg_email" autocomplete="email"
                    value="<?php if (!empty($_POST['email'])) echo esc_attr(wp_unslash($_POST['email'])); ?>" />
            </p>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="reg_password"><?php esc_html_e('Password', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
                <input type="password" class="woocommerce-Input input-text" name="password" id="reg_password" autocomplete="new-password" />
            </p>
            <?php do_action('woocommerce_register_form'); ?>
            <p class="woocommerce-form-row form-row">
                <?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
                <button type="submit" class="woocommerce-Button button" name="register"
                    value="<?php esc_attr_e('Register', 'woocommerce'); ?>"><?php esc_html_e('Register', 'woocommerce'); ?></button>
            </p>
            <?php do_action('woocommerce_register_form_end'); ?>
        </form>
        <p class="switch-link">
            <?php esc_html_e('Already have an account?', 'woocommerce'); ?>
            <a href="<?php echo esc_url(site_url('/log-in/')); ?>"><?php esc_html_e('Login here', 'woocommerce'); ?></a>
        </p>
    </div>
    <?php
    return ob_get_clean();
});

add_filter('woocommerce_login_redirect', function($redirect, $user) {
    return wc_get_page_permalink('myaccount');
}, 10, 2);

add_filter('woocommerce_registration_redirect', function($redirect) {
    return wc_get_page_permalink('myaccount');
});

add_action('wp_logout', function() {
    wp_safe_redirect(site_url('/log-in/'));
    exit;
});

// My Account Slider Assets
function mychild_myaccount_slider_assets() {
    if (function_exists('is_account_page') && is_account_page()) {
        wp_enqueue_style('swiper', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css', [], '10.0');
        wp_enqueue_script('swiper', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js', [], '10.0', true);
        if (!wp_style_is('font-awesome', 'enqueued')) {
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css', [], '6.5.2');
        }
        $init = <<<JS
            document.addEventListener('DOMContentLoaded', function(){
                new Swiper('.myacc-swiper', {
                    slidesPerView: 'auto',
                    spaceBetween: 10,
                    freeMode: true,
                    mousewheel: true,
                    navigation: {
                        nextEl: '.myacc-swiper-button-next',
                        prevEl: '.myacc-swiper-button-prev'
                    }
                });
            });
        JS;
        wp_add_inline_script('swiper', $init);
    }
}
add_action('wp_enqueue_scripts', 'mychild_myaccount_slider_assets');

// Enable Debugging
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
    define('WP_DEBUG_LOG', true);
}

// Hide Tables and Titles in Product Description
add_action('wp_head', function() {
    echo '<style>.woocommerce-product-details__short-description table, .product-description table, #tab-description table, .woocommerce-product-details__short-description h1, .woocommerce-product-details__short-description h2, .woocommerce-product-details__short-description h3 { display: none !important; }</style>';
});

// Table Data Shortcode
function display_table_data_shortcode($atts) {
    global $post;
    $atts = shortcode_atts(array('post_id' => $post->ID), $atts, 'table_data');
    $post_id = $atts['post_id'];
    if (!$post_id) {
        error_log('No product ID available for shortcode [table_data].');
        return '';
    }
    $description = get_post_field('post_content', $post_id);
    if (empty($description)) {
        error_log('No product description found for post ID: ' . $post_id);
        return '';
    }
    $parsed_data = parseTableDataWithTitle($description);
    $title = $parsed_data['title'];
    $data = $parsed_data['data'];
    if (empty($data)) {
        error_log('No table data parsed from description for post ID: ' . $post_id);
        return '';
    }
    $chunk_size = 10;
    $chunks = array_chunk($data, $chunk_size, true);
    $output = '';
    if (!empty($title)) {
        $output .= '<h3 style="margin: 10px 0; padding: 5px; background: #f1f1f1;">' . esc_html($title) . '</h3>';
    }
    foreach ($chunks as $index => $chunk) {
        $output .= '<table class="product-specs-table" style="width: 100%; border-collapse: collapse; border: 1px solid #ddd; margin: 10px 0;">';
        $output .= '<thead><tr><th style="border: 1px solid #ddd; padding: 8px; background: #f9f9f9; width: 30%;">Specification</th><th style="border: 1px solid #ddd; padding: 8px; background: #f9f9f9;">Details</th></tr></thead>';
        $output .= '<tbody>';
        foreach ($chunk as $key => $value) {
            $output .= '<tr>';
            $output .= '<td style="border: 1px solid #ddd; padding: 8px; font-weight: bold;">' . esc_html($key) . '</td>';
            $output .= '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($value) . '</td>';
            $output .= '</tr>';
        }
        $output .= '</tbody></table>';
        if ($index < count($chunks) - 1) {
            $output .= '<div style="margin: 20px 0;"></div>';
        }
    }
    return $output;
}
add_shortcode('table_data', 'display_table_data_shortcode');

function parseTableDataWithTitle($description) {
    $data = array();
    $title = '';
    $description = preg_replace('/[\r\n]+/', '', $description);
    if (preg_match('/<(h[1-3])[^>]*>(.*?)<\/h[1-3]>/si', $description, $title_match)) {
        $title = trim(strip_tags($title_match[0]));
        $description = preg_replace('/<(h[1-3])[^>]*>.*?<\/h[1-3]>/si', '', $description, 1);
    }
    if (preg_match_all('/<tr[^>]*>.*?<td[^>]*>(.*?)<\/td>.*?<td[^>]*>(.*?)<\/td>.*?<\/tr>/si', $description, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $key = trim(strip_tags($match[1]));
            $value = trim(strip_tags($match[2]));
            if (!empty($key) && $key !== 'Specification' && $key !== 'Details') {
                $data[$key] = $value;
            }
        }
    } else {
        error_log('No valid <tr><td> tags found in description.');
    }
    return array('title' => $title, 'data' => $data);
}

add_filter('woocommerce_product_tabs', 'add_specs_tab');
function add_specs_tab($tabs) {
    global $post;
    $description = get_post_field('post_content', $post->ID);
    $parsed_data = parseTableDataWithTitle($description);
    $data = $parsed_data['data'];
    if (!empty($data)) {
        $tabs['specs'] = array(
            'title'    => __('Specifications', 'woocommerce'),
            'priority' => 50,
            'callback' => 'specs_tab_content'
        );
    }
    return $tabs;
}
function specs_tab_content() {
    echo do_shortcode('[table_data]');
}

// Rename My Account Tabs
add_filter('woocommerce_account_menu_items', 'custom_my_account_tabs');
function custom_my_account_tabs($menu_links) {
    $menu_links['payment-methods'] = 'Saved Payment Methods';
    $menu_links['edit-address'] = 'Manage Addresses';
    return $menu_links;
}

// WCFM Vendor Notifications Shortcode
function cisai_wcfm_vendor_notifications() {
    if (!is_user_logged_in()) {
        return '<p>Please login to view your notifications.</p>';
    }
    $user_id = get_current_user_id();
    if (!wcfm_is_vendor($user_id)) {
        return '<p>This page is only for vendors.</p>';
    }
    global $wpdb;
    $table = $wpdb->prefix . 'wcfm_messages';
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE author_id = %d OR to_user = %d ORDER BY message_id DESC LIMIT 20",
        $user_id, $user_id
    ));
    if (empty($results)) {
        return '<p>No notifications yet.</p>';
    }
    $output = '<div class="wcfm-vendor-notifications">';
    $output .= '<h3>Vendor Notifications</h3><ul>';
    foreach ($results as $note) {
        $output .= '<li><strong>' . esc_html($note->message) . '</strong> ';
        $output .= '<em>(' . esc_html($note->created) . ')</em></li>';
    }
    $output .= '</ul></div>';
    return $output;
}
add_shortcode('vendor_notifications', 'cisai_wcfm_vendor_notifications');

// Track and Display Recently Viewed Products
add_action('wp', 'track_product_view');
function track_product_view() {
    if (!is_product()) {
        return;
    }
    global $post;
    $product_id = $post->ID;
    $viewed_products = !empty($_COOKIE['woocommerce_recently_viewed']) ? (array) explode('|', $_COOKIE['woocommerce_recently_viewed']) : array();
    $viewed_products = array_filter(array_map('absint', $viewed_products));
    if (!in_array($product_id, $viewed_products, true)) {
        $viewed_products[] = $product_id;
    }
    if (count($viewed_products) > 15) {
        array_shift($viewed_products);
    }
    wc_setcookie('woocommerce_recently_viewed', implode('|', $viewed_products), time() + WEEK_IN_SECONDS);
}

add_shortcode('recently_viewed_products', 'recently_viewed_products_shortcode');
function recently_viewed_products_shortcode($atts) {
    $atts = shortcode_atts(array(
        'per_page' => '4',
        'columns'  => '4',
    ), $atts, 'recently_viewed_products');
    $viewed_products = !empty($_COOKIE['woocommerce_recently_viewed']) ? (array) explode('|', $_COOKIE['woocommerce_recently_viewed']) : array();
    $viewed_products = array_reverse(array_filter(array_map('absint', $viewed_products)));
    if (empty($viewed_products)) {
        return '<div class="recently-viewed-section"><p>No products viewed yet!</p></div>';
    }
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => absint($atts['per_page']),
        'post__in'       => $viewed_products,
        'orderby'        => 'post__in',
    );
    $products_query = new WP_Query($args);
    ob_start();
    if ($products_query->have_posts()) {
        echo '<div class="recently-viewed-section">';
        echo '<h2>Recently Viewed Products</h2>';
        echo '<div class="woocommerce columns-' . esc_attr($atts['columns']) . '">';
        echo '<ul class="products">';
        while ($products_query->have_posts()) {
            $products_query->the_post();
            wc_get_template_part('content', 'product');
        }
        echo '</ul>';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="recently-viewed-section"><p>No products viewed yet!</p></div>';
    }
    wp_reset_postdata();
    return ob_get_clean();
}

add_action('woocommerce_after_single_product_summary', 'display_recently_viewed_products', 15);
function display_recently_viewed_products() {
    if (!empty($_COOKIE['woocommerce_recently_viewed'])) {
        echo do_shortcode('[recently_viewed_products per_page="4" columns="4"]');
    }
}

add_action('wp_enqueue_scripts', 'recently_viewed_products_styles');
function recently_viewed_products_styles() {
    $custom_css = "
        .recently-viewed-section { margin-top: 2em; padding: 1em 0; }
        .recently-viewed-section h2 { text-align: center; font-size: 1.5em; margin-bottom: 1em; color: #333; }
        .recently-viewed-section .products { list-style: none; margin: 0; padding: 0; }
        .recently-viewed-section .products li.product { text-align: center; }
        .recently-viewed-section p { text-align: center; color: #777; font-size: 1em; }
    ";
    wp_add_inline_style('woocommerce-general', $custom_css);
}
?>