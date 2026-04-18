<?php
/*
 Plugin Name: CISAI WCFM Manufacturer Plugin
 Description: Integrates custom video, image, and additional fields for WCFM product management in a single file.
 Version: 1.0
 Author: Adarsh VinodKumar Singh
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue inline scripts (since no separate JS files)
function wcfm_manufacturer_enqueue_scripts($hook = '') {
    if ($hook && $hook != 'post.php' && $hook != 'post-new.php') {
        return;
    }
    wp_enqueue_media();
    wp_add_inline_script('wcfm-manufacturer-admin', '
        jQuery(document).ready(function($) {
            // Basic admin JS (enhance as needed)
            $(".wcfmv-add-video, .wcfm-add-media").on("click", function() {
                var $this = $(this);
                var uploader = wp.media({
                    title: "Select or Upload Media",
                    button: { text: "Select" },
                    multiple: true
                }).on("select", function() {
                    var attachments = uploader.state().get("selection").toJSON();
                    var $list = $this.prev(".wcfmv-videos-list, .wcfm-images-list");
                    $.each(attachments, function(i, attachment) {
                        if (attachment.type === "video" || attachment.type === "image") {
                            $list.append(\'<div class="wcfmv-video-item wcfm-image-item"><input type="hidden" name="wcfmv_video_sections[0][videos][]" value="\' + attachment.id + \'"><\' + (attachment.type === "video" ? "video" : "img") + \' src="\' + attachment.url + \'" width="100" controls><button type="button" class="wcfmv-remove-video wcfm-remove-image">Remove</button></div>\');
                        }
                    });
                }).open();
            });
        });
    ', 'after');
    wp_add_inline_script('wcfm-slider-js', '
        jQuery(document).ready(function($) {
            // Basic slider JS (enhance for full functionality)
            $(".wcfmv-slider-next, .wcfm-slider-next").on("click", function() {
                var $slider = $(this).siblings(".wcfmv-slider, .wcfm-slider");
                $slider.css("transform", "translateX(-100%)");
            });
            $(".wcfmv-slider-prev, .wcfm-slider-prev").on("click", function() {
                var $slider = $(this).siblings(".wcfmv-slider, .wcfm-slider");
                $slider.css("transform", "translateX(0)");
            });
        });
    ', 'after');
}
add_action('admin_enqueue_scripts', 'wcfm_manufacturer_enqueue_scripts');
add_action('wp_enqueue_scripts', 'wcfm_manufacturer_enqueue_scripts');

// Add custom fields to WCFM product management form
function wcfm_manufacturer_product_fields($fields) {
    $product_id = get_the_ID();
    $technical_details = get_post_meta($product_id, 'technical_details', true) ?: '';
    $additional_information = get_post_meta($product_id, 'additional_information', true) ?: '';

    // Video and Image Sections
    $video_sections = get_post_meta($product_id, 'wcfmv_video_sections', true) ?: array();
    $image_sections = get_post_meta($product_id, 'wcfm_manufacturer_sections', true) ?: array();
    $show_heading = get_post_meta($product_id, 'wcfm_show_heading', true) !== 'no';
    $custom_heading = get_post_meta($product_id, 'wcfm_custom_heading', true) ?: 'From the Manufacturer';

    // Custom Fields
    $fields['technical_details'] = array(
        'label' => 'Technical Details',
        'type' => 'wpeditor',
        'class' => 'wcfm-wpeditor wcfm_custom_field',
        'label_class' => 'wcfm_title',
        'value' => $technical_details,
        'desc' => 'Enter technical details here.',
    );

    $fields['additional_information'] = array(
        'label' => 'Additional Information',
        'type' => 'wpeditor',
        'class' => 'wcfm-wpeditor wcfm_custom_field',
        'label_class' => 'wcfm_title',
        'value' => $additional_information,
        'desc' => 'Enter additional information here.',
    );

    // Video Sections
    $fields['wcfmv_show_heading'] = array(
        'label' => 'Show Section Heading',
        'type' => 'checkbox',
        'class' => 'wcfm-checkbox wcfm_custom_field',
        'label_class' => 'wcfm_title checkbox_title',
        'value' => $show_heading ? 'yes' : '',
        'dfvalue' => 'yes',
        'desc' => 'Check to show the heading above all sections.',
    );

    $fields['wcfmv_custom_heading'] = array(
        'label' => 'Custom Heading Text',
        'type' => 'text',
        'class' => 'wcfm-text wcfm_custom_field',
        'label_class' => 'wcfm_title',
        'value' => $custom_heading,
        'placeholder' => 'From the Manufacturer',
        'desc' => 'Enter custom text for the heading (optional).',
    );

    $fields['wcfmv_video_sections'] = array(
        'label' => 'From the Manufacturer Video Sections',
        'type' => 'multiinput',
        'class' => 'wcfm-text wcfm_custom_field wcfmv_video_sections',
        'label_class' => 'wcfm_title',
        'value' => $video_sections,
        'options' => array(
            'visible' => array('label' => 'Enable', 'type' => 'checkbox', 'class' => 'wcfm-checkbox', 'value' => 'yes', 'dfvalue' => 'yes'),
            'mode' => array('label' => 'Display Mode', 'type' => 'radio', 'options' => array('grid' => 'Grid (3 per row)', 'slider' => 'Slider/Banner'), 'default' => 'grid'),
            'videos' => array('label' => 'Videos', 'type' => 'multiinput', 'options' => array('video' => array('type' => 'upload', 'class' => 'wcfm-uploader', 'prid' => $product_id, 'mime_types' => 'video/mp4'))),
        ),
        'desc' => 'Add multiple sections with videos (up to 5MB) for the manufacturer.',
    );

    // Image Sections
    $fields['wcfm_manufacturer_sections'] = array(
        'label' => 'From the Manufacturer Image Sections',
        'type' => 'multiinput',
        'class' => 'wcfm-text wcfm_custom_field wcfm_manufacturer_sections',
        'label_class' => 'wcfm_title',
        'value' => $image_sections,
        'options' => array(
            'visible' => array('label' => 'Enable', 'type' => 'checkbox', 'class' => 'wcfm-checkbox', 'value' => 'yes', 'dfvalue' => 'yes'),
            'mode' => array('label' => 'Display Mode', 'type' => 'radio', 'options' => array('grid' => 'Grid (3 per row)', 'slider' => 'Slider/Banner'), 'default' => 'grid'),
            'images' => array('label' => 'Images or Videos', 'type' => 'multiinput', 'options' => array('image' => array('type' => 'upload', 'class' => 'wcfm-uploader', 'prid' => $product_id, 'mime_types' => 'image/*,video/mp4'))),
        ),
        'desc' => 'Add multiple sections with images or videos (up to 30MB) for the manufacturer.',
    );

    return $fields;
}
add_filter('wcfm_products_manage_fields', 'wcfm_manufacturer_product_fields', 100);

// Save custom fields for WCFM
function wcfm_manufacturer_product_meta_save($product_id, $wcfm_settings, $wcfm_vendor_id) {
    // Custom Fields
    if (isset($_POST['technical_details'])) {
        update_post_meta($product_id, 'technical_details', wp_kses_post($_POST['technical_details']));
    }
    if (isset($_POST['additional_information'])) {
        update_post_meta($product_id, 'additional_information', wp_kses_post($_POST['additional_information']));
    }

    // Video Sections
    $show_heading = isset($_POST['wcfmv_show_heading']) && $_POST['wcfmv_show_heading'] === 'yes' ? 'yes' : 'no';
    update_post_meta($product_id, 'wcfm_show_heading', $show_heading);

    $custom_heading = isset($_POST['wcfmv_custom_heading']) ? sanitize_text_field($_POST['wcfmv_custom_heading']) : 'From the Manufacturer';
    update_post_meta($product_id, 'wcfm_custom_heading', $custom_heading);

    $video_sections = isset($_POST['wcfmv_video_sections']) ? $_POST['wcfmv_video_sections'] : array();
    $saved_video_sections = array();
    foreach ($video_sections as $index => $section) {
        $videos = isset($section['videos']) ? array_filter(array_column($section['videos'], 'video')) : array();
        $videos = array_filter($videos, function($id) {
            return get_post($id) && get_post($id)->post_type === 'attachment' && !wp_attachment_is_image($id);
        });
        if (!empty($videos)) {
            $saved_video_sections[] = array(
                'visible' => isset($section['visible']) && $section['visible'] === 'yes' ? 'yes' : 'no',
                'mode' => isset($section['mode']) ? sanitize_text_field($section['mode']) : 'grid',
                'videos' => $videos,
            );
        }
    }
    update_post_meta($product_id, 'wcfmv_video_sections', $saved_video_sections);

    // Image Sections
    $image_sections = isset($_POST['wcfm_manufacturer_sections']) ? $_POST['wcfm_manufacturer_sections'] : array();
    $saved_image_sections = array();
    foreach ($image_sections as $index => $section) {
        $images = isset($section['images']) ? array_filter(array_column($section['images'], 'image')) : array();
        if (!empty($images)) {
            $saved_image_sections[] = array(
                'visible' => isset($section['visible']) && $section['visible'] === 'yes' ? 'yes' : 'no',
                'mode' => isset($section['mode']) ? sanitize_text_field($section['mode']) : 'grid',
                'images' => $images,
            );
        }
    }
    update_post_meta($product_id, 'wcfm_manufacturer_sections', $saved_image_sections);
}
add_action('wcfm_product_meta_save', 'wcfm_manufacturer_product_meta_save', 100, 3);

// Shortcode for display
function wcfm_manufacturer_shortcode($atts) {
    $atts = shortcode_atts(array('product_id' => get_the_ID()), $atts, 'from_manufacturer');
    $product_id = intval($atts['product_id']);
    $video_sections = get_post_meta($product_id, 'wcfmv_video_sections', true);
    $image_sections = get_post_meta($product_id, 'wcfm_manufacturer_sections', true);
    $show_heading = get_post_meta($product_id, 'wcfm_show_heading', true) !== 'no';
    $custom_heading = get_post_meta($product_id, 'wcfm_custom_heading', true) ?: 'From the Manufacturer';

    if (empty($video_sections) && empty($image_sections)) {
        return '';
    }

    ob_start();
    if ($show_heading) {
        echo '<h2>' . esc_html($custom_heading) . '</h2>';
    }
    ?>
    <div class="wcfm-manufacturer-sections">
        <?php
        // Video Sections
        if (!empty($video_sections) && is_array($video_sections)) {
            foreach ($video_sections as $section) {
                if ($section['visible'] !== 'yes' || empty($section['videos'])) {
                    continue;
                }
                ?>
                <div class="wcfmv-section-content" data-mode="<?php echo esc_attr($section['mode']); ?>">
                    <?php if ($section['mode'] === 'grid'): ?>
                        <div class="wcfmv-videos-grid">
                            <?php foreach ($section['videos'] as $video_id): ?>
                                <div class="wcfmv-video-item">
                                    <?php
                                    $video = get_post($video_id);
                                    if ($video && $video->post_type === 'attachment' && !wp_attachment_is_image($video_id)) {
                                        $video_url = wp_get_attachment_url($video_id);
                                        ?>
                                        <div class="wcfmv-video-wrapper">
                                            <video class="wcfmv-video" preload="metadata" controlslist="nodownload">
                                                <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                                                Your browser does not support the video tag.
                                            </video>
                                            <div class="wcfmv-play-button"></div>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="wcfmv-slider-container">
                            <div class="wcfmv-slider">
                                <?php foreach ($section['videos'] as $video_id): ?>
                                    <div class="wcfmv-slide">
                                        <?php
                                        $video = get_post($video_id);
                                        if ($video && $video->post_type === 'attachment' && !wp_attachment_is_image($video_id)) {
                                            $video_url = wp_get_attachment_url($video_id);
                                            ?>
                                            <div class="wcfmv-video-wrapper">
                                                <video class="wcfmv-video" preload="metadata" controlslist="nodownload">
                                                    <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                                                    Your browser does not support the video tag.
                                                </video>
                                                <div class="wcfmv-play-button"></div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="wcfmv-slider-prev">‹</button>
                            <button class="wcfmv-slider-next">›</button>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
            }
        }

        // Image Sections
        if (!empty($image_sections) && is_array($image_sections)) {
            foreach ($image_sections as $section) {
                if ($section['visible'] !== 'yes' || empty($section['images'])) {
                    continue;
                }
                ?>
                <div class="wcfm-section-content" data-mode="<?php echo esc_attr($section['mode']); ?>">
                    <?php if ($section['mode'] === 'grid'): ?>
                        <div class="wcfm-images-grid">
                            <?php foreach ($section['images'] as $media_id): ?>
                                <div class="wcfm-image-item">
                                    <?php
                                    $media = get_post($media_id);
                                    if ($media && $media->post_type === 'attachment') {
                                        if (wp_attachment_is_image($media_id)) {
                                            echo wp_get_attachment_image($media_id, 'medium');
                                        } else {
                                            echo '<video width="300" height="auto" controls><source src="' . wp_get_attachment_url($media_id) . '" type="video/mp4">Your browser does not support the video tag.</video>';
                                        }
                                    }
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="wcfm-slider-container">
                            <div class="wcfm-slider">
                                <?php foreach ($section['images'] as $media_id): ?>
                                    <div class="wcfm-slide">
                                        <?php
                                        $media = get_post($media_id);
                                        if ($media && $media->post_type === 'attachment') {
                                            if (wp_attachment_is_image($media_id)) {
                                                echo wp_get_attachment_image($media_id, 'large', false, array('class' => 'wcfm-slide-img'));
                                            } else {
                                                echo '<video class="wcfm-slide-video" controls><source src="' . wp_get_attachment_url($media_id) . '" type="video/mp4">Your browser does not support the video tag.</video>';
                                            }
                                        }
                                        ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="wcfm-slider-prev">‹</button>
                            <button class="wcfm-slider-next">›</button>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
            }
        }
        ?>
    </div>
    <?php
    // Add CSS
    ?>
    <style>
        .wcfm-manufacturer-sections { width: 100%; }
        .wcfmv-videos-grid, .wcfm-images-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .wcfmv-video-wrapper { position: relative; width: 100%; padding-top: 56.25%; }
        .wcfmv-video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; background: #000; }
        .wcfmv-play-button { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 60px; height: 60px; background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23ffffff"><path d="M8 5v14l11-7z"/></svg>') no-repeat center; background-size: 60px 60px; cursor: pointer; opacity: 0.8; }
        .wcfmv-play-button:hover { opacity: 1; }
        .wcfm-slider-container { position: relative; max-width: 100%; margin: 20px 0; overflow: hidden; }
        .wcfm-slider { display: flex; transition: transform 0.3s ease; }
        .wcfm-slide { min-width: 100%; }
        .wcfm-slide-img, .wcfm-slide-video { width: 100%; height: auto; display: block; }
        .wcfm-slider-prev, .wcfm-slider-next { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; border: none; font-size: 2em; padding: 10px; cursor: pointer; }
        .wcfm-slider-prev { left: 10px; }
        .wcfm-slider-next { right: 10px; }
        @media (max-width: 768px) { .wcfmv-videos-grid, .wcfm-images-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 480px) { .wcfmv-videos-grid, .wcfm-images-grid { grid-template-columns: 1fr; } }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('from_manufacturer', 'wcfm_manufacturer_shortcode');
