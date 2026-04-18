<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use Elementor\Controls_Manager;
use Elementor\Plugin;
use Elementor\Widget_Base;

/**
 * Elementor Reels WP Widget.
 *
 * Elementor widget for displaying a group of reels.
 *
 * @since 1.0.0
 */
class Reels_WP_Widget extends Widget_Base
{

    /**
     * Get widget name.
     *
     * Retrieve Reels WP widget name.
     *
     * @since 1.0.0
     * @access public
     * @return string Widget name.
     */
    public function get_name()
    {
        return 'reelswp';
    }

    /**
     * Get widget title.
     *
     * Retrieve Reels WP widget title.
     *
     * @since 1.0.0
     * @access public
     * @return string Widget title.
     */
    public function get_title()
    {
        return esc_html__('ReelsWP', 'ecomm-reels');
    }

    /**
     * Get widget icon.
     *
     * Retrieve Reels WP widget icon.
     *
     * @since 1.0.0
     * @access public
     * @return string Widget icon.
     */
    public function get_icon()
    {
        return 'reelswp-icon';
    }

    /**
     * Get widget categories.
     *
     * Retrieve the list of categories the Reels WP widget belongs to.
     *
     * @since 1.0.0
     * @access public
     * @return array Widget categories.
     */
    public function get_categories()
    {
        return ['general'];
    }

    /**
     * Get widget script dependencies.
     *
     * Retrieve the list of script handles the widget depends on.
     *
     * @since 1.0.0
     * @access public
     * @return array Widget script handles.
     */
    public function get_script_depends()
    {
        return ['ecommreels-ecomm-reels-build-script'];
    }

    /**
     * Get widget style dependencies.
     *
     * Retrieve the list of style handles the widget depends on.
     *
     * @since 1.0.0
     * @access public
     * @return array Widget style handles.
     */
    public function get_style_depends()
    {
        return ['ecommreels-fonts'];
    }

    /**
     * Register Reels WP widget controls.
     *
     * Add input fields to allow the user to customize the widget settings.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function _register_controls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'ecomm-reels'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'group_id',
            [
                'label' => esc_html__('Reel Group', 'ecomm-reels'),
                'type' => Controls_Manager::SELECT2,
                'options' => $this->get_groups(),
                'default' => '',
                'select2options' => [
                    'placeholder' => esc_html__('-- Select a Group --', 'ecomm-reels'),
                    'allowClear' => true,
                ],
            ]
        );

        $this->add_control(
            'preview_notice',
            [
                'type' => Controls_Manager::NOTICE,
                'heading' => esc_html__('Live Preview not available', 'ecomm-reels'),
                'content' => esc_html__('The selected reel group will be visible on the live page. Please save and view the page to see the final result.', 'ecomm-reels'),
                'show_icon' => true,
                'notice_type' => 'info',
                'condition' => [
                    'group_id!' => '',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render Reels WP widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $group_id = $settings['group_id'];

        if (empty($group_id)) {
            if (Plugin::$instance->editor->is_edit_mode()) {
                echo '<div style="text-align: center; padding: 20px; background: #f1f1f1;">' .
                    esc_html__('Please select a group to display.', 'ecomm-reels') .
                    '</div>';
            }
            return;
        }

        echo do_shortcode('[reelswp-group id="' . esc_attr($group_id) . '"]');
    }

    private function get_groups()
    {
        if (!current_user_can('manage_options')) {
            return ['' => esc_html__('You do not have permission to view groups.', 'ecomm-reels')];
        }

        $request = new \WP_REST_Request('GET', '/wp-reels/v1/groups');
        $request->set_param('per_page', '999');
        $response = rest_do_request($request);

        if ($response->is_error()) {
            return ['' => esc_html__('Error loading groups.', 'ecomm-reels')];
        }

        $data = $response->get_data();

        if (empty($data)) {
            return ['' => esc_html__('No groups found.', 'ecomm-reels')];
        }

        $options = [];
        foreach ($data as $group) {
            if (isset($group['id']) && isset($group['group_name'])) {
                $options[$group['id']] = $group['group_name'];
            }
        }

        return  $options;
    }
}
