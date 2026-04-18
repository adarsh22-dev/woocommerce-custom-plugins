<?php
/*
 * Plugin Name:       Cisai Instagram – Instagram Stories & Reels for WooCommerce
 * Description:       Complete Instagram-style stories and reels solution with shoppable videos, UGC content, story groups, advanced analytics, text overlays, interactive buttons - All features unlocked!
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Cisai
 * Author URI:        https://cisai.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cisai-instagram
 * Domain Path:       /languages
 */

if (! defined('ABSPATH')) exit;

define('ECOMMREELS_FILE', __FILE__);
define('ECOMMREELS_PATH', plugin_dir_path(__FILE__));
define('ECOMMREELS_ASSETS', plugins_url('/', __FILE__));
define('ECOMMREELS_FILE_PREFIX', 'class-');
define('WP_REELS_VER', '1.0.0');

// 1) Composer autoloader (optional, guarded)
$autoload = ECOMMREELS_PATH . 'vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// 2) Manual requires (keep these if your classes are NOT namespaced/PSR-4)
require_once ECOMMREELS_PATH . 'includes/' . ECOMMREELS_FILE_PREFIX . 'main.php';
// require_once ECOMMREELS_PATH . 'includes/class-renderer.php';
require_once ECOMMREELS_PATH . 'includes/api/ReelsWP_Router.php';
// require_once ECOMMREELS_PATH . 'includes/class-api.php';
require_once ECOMMREELS_PATH . 'includes/class-db.php';
require_once ECOMMREELS_PATH . 'includes/class-upgrader.php';

require_once ECOMMREELS_PATH . 'includes/class-feedback.php';
require_once ECOMMREELS_PATH . 'includes/block/class-block.php';
// 3) Activation: fresh installs create schema
register_activation_hook(ECOMMREELS_FILE, ['WP_Reels_DB', 'activate']);

// 4) i18n
// add_action('init', function () {
//     load_plugin_textdomain('reels-wp', false, dirname(plugin_basename(ECOMMREELS_FILE)) . '/languages');
// }, 1);

// Bootstrap the router so it can hook into rest_api_init:
add_action('plugins_loaded', function () {
    if (class_exists('ReelsWP_Router')) {
        ReelsWP_Router::init();
    }
}, 9);

// 5) Upgrades/migrations run on load if version changed
add_action('plugins_loaded', function () {
    if (defined('WP_INSTALLING') && WP_INSTALLING) return;
    WP_Reels_Upgrader::maybe_upgrade();
}, 5);

// 6) If class-api.php needs bootstrapping, do it here (example)
// add_action('plugins_loaded', ['WP_Reels_API', 'init'], 9);

// 7) Elementor integration
add_action('plugins_loaded', function () {
    if (did_action('elementor/loaded')) {
        require_once ECOMMREELS_PATH . 'includes/elementor/class-elementor-init.php';
    }
});
