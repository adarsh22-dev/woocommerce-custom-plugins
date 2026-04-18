<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Plugin Elementor Init.
 *
 * The main class that initiates and runs the elementor widgets.
 *
 * @since 1.0.0
 */
final class ReelsWP_Elementor_Init
{

    /**
     * Instance
     *
     * @since 1.0.0
     *
     * @access private
     * @static
     *
     * @var ReelsWP_Elementor_Init The single instance of the class.
     */
    private static $_instance = null;

    /**
     * Instance
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @since 1.0.0
     *
     * @access public
     * @static
     *
     * @return ReelsWP_Elementor_Init An instance of the class.
     */
    public static function instance()
    {

        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function __construct()
    {
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
    }

    /**
     * Register Widgets
     *
     * Register new Elementor widgets.
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
     */
    public function register_widgets($widgets_manager)
    {
        require_once ECOMMREELS_PATH . 'includes/elementor/widgets/class-reels-wp-widget.php';

        $widgets_manager->register(new \Reels_WP_Widget());

        // Enqueue editor-specific CSS for the widget icon and styling
        wp_enqueue_style(
            'ecommreels-elementor-editor-css',
            ECOMMREELS_ASSETS . 'assets/admin/css/editor.css',
            [],
            WP_REELS_VER
        );
    }
}

ReelsWP_Elementor_Init::instance();
