<?php
/*
Plugin Name: CISAI WCFM Manufacturer Images 2025
Description: Vendors can upload multiple manufacturer images via WCFM (Free). On the frontend, images are divided into groups of 3 — each group forms its own full-width slider row.
Version: 6.0
Author: Adarsh Singh
*/

if (!defined('ABSPATH')) exit;

/* ============================================================
 * 1. Add Custom Field in WCFM Product Edit
 * ============================================================ */
add_action('after_wcfm_products_manage_general', function($product_id) {
    $images = get_post_meta($product_id, 'from_the_manufacturer_images', true);
    if (!is_array($images)) $images = [];
    ?>
    <div class="page_collapsible products_manage_pods_image simple variable external">
        <label class="wcfmfa fa-images"></label> From the Manufacturer Images
    </div>

    <div class="wcfm-container simple variable external">
        <div class="wcfm-content">
            <p class="wcfm_title"><strong>Upload or paste image URLs</strong></p>

            <div id="cisai-images-wrapper">
                <?php foreach ($images as $url): ?>
                    <div class="cisai-image-row" style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                        <input type="url" name="from_the_manufacturer_images[]" value="<?php echo esc_attr($url); ?>" placeholder="Paste Image URL or upload" style="flex:1;padding:6px;">
                        <button type="button" class="button cisai-upload-image">Upload</button>
                        <button type="button" class="button cisai-remove-image" style="background:#dc3545;color:#fff;">Remove</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" id="cisai-add-image" class="button" style="margin-top:10px;">+ Add Another Image</button>

            <div class="cisai-preview" style="display:flex;flex-wrap:wrap;gap:10px;margin-top:10px;">
                <?php foreach ($images as $url): ?>
                    <img src="<?php echo esc_url($url); ?>" style="width:120px;height:auto;border:1px solid #ddd;border-radius:6px;">
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($){
        let frame;

        // Add new row
        $('#cisai-add-image').on('click', function(e){
            e.preventDefault();
            let row = `
            <div class="cisai-image-row" style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                <input type="url" name="from_the_manufacturer_images[]" placeholder="Paste Image URL or upload" style="flex:1;padding:6px;">
                <button type="button" class="button cisai-upload-image">Upload</button>
                <button type="button" class="button cisai-remove-image" style="background:#dc3545;color:#fff;">Remove</button>
            </div>`;
            $('#cisai-images-wrapper').append(row);
        });

        // Remove image
        $(document).on('click', '.cisai-remove-image', function(){
            $(this).closest('.cisai-image-row').remove();
        });

        // Upload image via Media Library
        $(document).on('click', '.cisai-upload-image', function(e){
            e.preventDefault();
            let input = $(this).closest('.cisai-image-row').find('input');

            if (frame) frame.close();

            frame = wp.media({
                title: 'Select or Upload Image',
                button: { text: 'Use this image' },
                library: { type: ['image'] },
                multiple: false
            });

            frame.on('select', function(){
                let attachment = frame.state().get('selection').first().toJSON();
                input.val(attachment.url);
                $('.cisai-preview').append('<img src="'+attachment.url+'" style="width:120px;height:auto;border:1px solid #ddd;border-radius:6px;">');
            });

            frame.open();
        });
    });
    </script>
    <?php
});

/* ============================================================
 * 2. Save Images
 * ============================================================ */
add_action('after_wcfm_products_manage_meta_save', function($product_id, $data){
    if (isset($data['from_the_manufacturer_images'])) {
        $urls = array_filter(array_map('esc_url_raw', (array)$data['from_the_manufacturer_images']));
        update_post_meta($product_id, 'from_the_manufacturer_images', $urls);
    } else {
        delete_post_meta($product_id, 'from_the_manufacturer_images');
    }
}, 10, 2);

/* ============================================================
 * 3. Frontend Slider Grouped Every 3 Images
 * ============================================================ */
add_shortcode('manufacturer_images', function($atts){
    global $post;
    $images = get_post_meta($post->ID, 'from_the_manufacturer_images', true);
    if (empty($images)) return '';

    // Split images into groups of 3
    $groups = array_chunk($images, 3);

    ob_start(); ?>

    <style>
        .cisai-slide {
    cursor: pointer;
    border-radius: 0px !important;
    overflow: hidden;
    background: #fff;
    display: flex;
    justify-content: center;
    height: 500px !important;
    object-fit:cover !important;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
}
    .cisai-slider-row {
        position: relative;
        overflow: hidden;
        width: 100%;
        max-width: 1200px;
        margin: 0px auto;
        border-radius: 0px;
    }
    .cisai-slider-track {
        display: flex;
        transition: transform 0.6s ease;
    }
    .cisai-slide {
        min-width: 100%;
        box-sizing: border-box;
    }
    .cisai-slide img {
        width: 100%;
        height: auto;
        display: block;
        border-radius: 0px;
    }
    .cisai-prev, .cisai-next {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0,0,0,0.6);
        color: #fff;
        border: none;
        padding: 8px 14px;
        border-radius: 50%;
        cursor: pointer;
        z-index: 2;
    }
    .cisai-prev { left: 10px; }
    .cisai-next { right: 10px; }
    @media (max-width:768px){
        .cisai-prev, .cisai-next { padding: 8px 10px; }
    }
    button.cisai-prev:hover {
    background-color: #05b895 !important;
}
button.cisai-next:hover {
    background-color: #05b895 !important
}
    @media (min-width: 300px) and (max-width: 767px) {
    .cisai-slide {
    justify-content: center;
    height: 200px!important;
    object-fit: cover !important;
}
    }
    </style>

    <?php foreach ($groups as $i => $group): 
        $slider_id = 'cisai-slider-' . ($i + 1) . '-' . rand(1000,9999);
    ?>
    <div id="<?php echo esc_attr($slider_id); ?>" class="cisai-slider-row">
        <div class="cisai-slider-track">
            <?php foreach ($group as $url): ?>
                <div class="cisai-slide"><img src="<?php echo esc_url($url); ?>"></div>
            <?php endforeach; ?>
        </div>
        <button class="cisai-prev">‹</button>
        <button class="cisai-next">›</button>
    </div>

    <script>
    (function(){
        const slider = document.querySelector('#<?php echo esc_js($slider_id); ?>');
        const track = slider.querySelector('.cisai-slider-track');
        const slides = slider.querySelectorAll('.cisai-slide');
        const prevBtn = slider.querySelector('.cisai-prev');
        const nextBtn = slider.querySelector('.cisai-next');
        let index = 0;
        let startX = 0, endX = 0;

        function updateSlider(){
            track.style.transform = 'translateX(' + (-index * 100) + '%)';
        }

        nextBtn.addEventListener('click', ()=> {
            index = (index + 1) % slides.length;
            updateSlider();
        });
        prevBtn.addEventListener('click', ()=> {
            index = (index - 1 + slides.length) % slides.length;
            updateSlider();
        });

        // Swipe
        track.addEventListener('touchstart', e=>{ startX = e.touches[0].clientX; });
        track.addEventListener('touchmove', e=>{ endX = e.touches[0].clientX; });
        track.addEventListener('touchend', ()=> {
            if(startX - endX > 50){ index = (index + 1) % slides.length; }
            else if(endX - startX > 50){ index = (index - 1 + slides.length) % slides.length; }
            updateSlider();
        });

        // Auto slide every 5s
        setInterval(()=> {
            index = (index + 1) % slides.length;
            updateSlider();
        }, 5000);
    })();
    </script>
    <?php endforeach;

    return ob_get_clean();
});
