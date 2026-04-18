<?php
/*
Plugin Name: CISAI WCFM 360° Product Viewer - Final Version
Description: Upload multiple images for 360° rotation. Backend preview, frontend button with popup. Works with WCFM Free + Pods.
Version: 3.0 - Final
Author: Adarsh Singh
*/

if (!defined('ABSPATH')) exit;

/* ============================================================
 * 1. BACKEND - Add Custom Field in WCFM Product Edit
 * ============================================================ */
add_action('after_wcfm_products_manage_general', function($product_id) {
    $images = get_post_meta($product_id, 'product_360_view_images', true);
    if (!is_array($images)) $images = [];
    ?>
    
    <div class="page_collapsible products_manage_360_images simple variable external grouped booking">
        <label class="wcfmfa fa-sync-alt"></label> Product 360° View Images
    </div>

    <div class="wcfm-container simple variable external grouped booking">
        <div class="wcfm-content">
            
            <!-- Header -->
            <div style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:#fff;padding:20px;border-radius:8px;margin-bottom:20px;">
                <h3 style="margin:0 0 10px 0;font-size:20px;">🔄 360° Product Rotation</h3>
                <p style="margin:0;font-size:14px;opacity:0.9;">Upload 12-36 images taken from different angles for interactive 360° view</p>
            </div>

            <!-- Instructions -->
            <div style="background:#E8F5E9;border-left:4px solid #4CAF50;padding:15px;margin-bottom:20px;border-radius:4px;">
                <strong style="color:#2E7D32;font-size:15px;">📸 How to Capture 360° Images:</strong>
                <ul style="margin:10px 0 0 20px;line-height:1.8;color:#333;">
                    <li>Place product on turntable or rotate manually</li>
                    <li>Take 12-36 photos at equal intervals (every 10-30 degrees)</li>
                    <li>Keep camera position, lighting & zoom consistent</li>
                    <li>Upload images in the order they were taken</li>
                </ul>
            </div>

            <!-- Upload Section --><div class="admin-dashboard">
            <p class="wcfm_title"><strong>Upload Images or Paste URLs</strong></p>

            <div id="product-360-images-wrapper">
                <?php if (empty($images)): ?>
                    <div class="product-360-image-row" style="display:flex;align-items:center;gap:10px;margin-bottom:10px;background:#f9f9f9;padding:10px;border-radius:6px;">
                        <input type="url" name="product_360_view_images[]" value="" placeholder="Paste Image URL or click Upload" style="flex:1;padding:8px;border:1px solid #ddd;border-radius:4px;">
                        <button type="button" class="button product-360-upload-image" style="background:#667eea;color:#fff;">📤 Upload</button>
                        <button type="button" class="button product-360-remove-image" style="background:#dc3545;color:#fff;">Remove</button>
                    </div>
                <?php else: ?>
                    <?php foreach ($images as $url): ?>
                        <div class="product-360-image-row" style="display:flex;align-items:center;gap:10px;margin-bottom:10px;background:#f9f9f9;padding:10px;border-radius:6px;">
                            <input type="url" name="product_360_view_images[]" value="<?php echo esc_attr($url); ?>" placeholder="Paste Image URL or click Upload" style="flex:1;padding:8px;border:1px solid #ddd;border-radius:4px;">
                            <div class="uoload-remove-mobile-version">
                            <button type="button" class="button product-360-upload-image" style="background:#667eea;color:#fff;">📤 Upload</button>
                            <button type="button" class="button product-360-remove-image" style="background:#dc3545;color:#fff;">Remove</button>
                    </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" id="product-360-add-image" class="button" style="margin-top:10px;background:#4CAF50;color:#fff;padding:10px 20px;border-radius:6px;font-weight:600;">
                ➕ Add Another Image
            </button>

            <div class="display-desktop-mobile">
            <!-- Thumbnail Preview -->
            <?php if (!empty($images)): ?>
            <div style="margin-top:20px;">
                <h4 style="margin:0 0 10px 0;color:#333;">📁 Uploaded Images (<?php echo count($images); ?> frames)</h4>
                <div class="product-360-preview" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:12px;">
                    <?php foreach ($images as $index => $url): ?>
                        <div style="position:relative;border:2px solid #ddd;border-radius:8px;overflow:hidden;background:#fff;">
                            <img src="<?php echo esc_url($url); ?>" style="width:100%;height:100px;object-fit:cover;display:block;">
                            <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.8);color:#fff;text-align:center;padding:4px;font-size:11px;font-weight:600;">
                                Frame #<?php echo $index + 1; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Live Preview Canvas - BACKEND ONLY -->
            <?php if (!empty($images)): ?>
            <div style="margin-top:25px;background:#dddddd;padding:20px;border-radius:8px;">
                <h4 style="color:#000;margin:0 0 12px 0;">🎬 Live Preview - Test Your 360° Rotation (Backend Only)</h4>
                <div style="position:relative;background:#000;border-radius:8px;overflow:hidden;height:400px;">
                    <canvas id="product-360-preview-canvas" style="width:100%;height:100%;cursor:grab;"></canvas>
                    
                    <div style="position:absolute;top:15px;left:15px;background:rgba(0,0,0,0.8);color:#fff;padding:8px 15px;border-radius:20px;font-size:13px;">
                        🖱️ Drag to rotate
                    </div>
                    
                    <div style="position:absolute;top:15px;right:15px;">
                        <button type="button" id="product-360-auto-rotate" style="background:rgba(67,97,238,0.9);color:#fff;border:none;padding:8px 20px;border-radius:20px;cursor:pointer;font-weight:600;font-size:13px;">▶ Auto Rotate</button>
                    </div>
                    
                    <div style="position:absolute;bottom:60px;right:15px;display:flex;flex-direction:column;gap:10px;">
                        <button type="button" id="product-360-zoom-in" style="background:rgba(60 87 215);border:none;width:45px;height:45px;border-radius:50%;cursor:pointer;font-size:20px;box-shadow:0 2px 10px rgba(0,0,0,0.3);line-height: 1px;padding: 1px;">+</button>
                        <button type="button" id="product-360-zoom-out" style="background:rgba(60 87 215);border:none;width:45px;height:45px;border-radius:50%;cursor:pointer;font-size:20px;box-shadow:0 2px 10px rgba(0,0,0,0.3);line-height: 1px;padding: 1px;">−</button>
                    </div>
                    
                    <div style="position:absolute;bottom:15px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.8);color:#fff;padding:8px 20px;border-radius:25px;font-size:14px;font-weight:600;">
                        Frame <span id="product-360-current-frame">1</span> / <span id="product-360-total-frames"><?php echo count($images); ?></span>
                    </div>
                    
                    <div style="position:absolute;bottom:0;left:0;right:0;height:4px;background:rgba(255,255,255,0.2);">
                        <div id="product-360-progress" style="height:100%;background:#667eea;width:0%;transition:width 0.1s;"></div>
                    </div>
                </div>
                
                <input type="range" id="product-360-frame-slider" min="0" max="<?php echo count($images) - 1; ?>" value="0" style="width:100%;margin-top:15px;display:none !important;">
            </div>
            <?php endif; ?>
            </div>

            <!-- Save Reminder -->
            <div style="background:#fff3cd;border:1px solid #ffc107;padding:15px;border-radius:8px;margin-top:20px;text-align:center;">
                <p style="margin:0;color:#856404;font-weight:600;">⚠️ Click the "Submit" button at the bottom of the page to save all changes!</p>
            </div>
        </div>
    </div>
            </div>

    <script>
    jQuery(document).ready(function($){
        let frame;

        // Add new row
        $('#product-360-add-image').on('click', function(e){
            e.preventDefault();
            let row = `
            <div class="product-360-image-row" style="display:flex;align-items:center;gap:10px;margin-bottom:10px;background:#f9f9f9;padding:10px;border-radius:6px;">
                <input type="url" name="product_360_view_images[]" placeholder="Paste Image URL or click Upload" style="flex:1;padding:8px;border:1px solid #ddd;border-radius:4px;">
                <button type="button" class="button product-360-upload-image" style="background:#667eea;color:#fff;">📤 Upload</button>
                <button type="button" class="button product-360-remove-image" style="background:#dc3545;color:#fff;">Remove</button>
            </div>`;
            $('#product-360-images-wrapper').append(row);
        });

        // Remove image
        $(document).on('click', '.product-360-remove-image', function(){
            $(this).closest('.product-360-image-row').remove();
        });

        // Upload image via Media Library
        $(document).on('click', '.product-360-upload-image', function(e){
            e.preventDefault();
            let input = $(this).closest('.product-360-image-row').find('input');

            if (frame) frame.close();

            frame = wp.media({
                title: 'Select or Upload 360° Image',
                button: { text: 'Use this image' },
                library: { type: ['image'] },
                multiple: false
            });

            frame.on('select', function(){
                let attachment = frame.state().get('selection').first().toJSON();
                input.val(attachment.url);
            });

            frame.open();
        });

        // Canvas Preview
        const canvas = document.getElementById('product-360-preview-canvas');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            const images = [];
            let currentFrame = 0;
            let isDragging = false;
            let startX = 0;
            let zoom = 1;
            let isAutoRotating = false;
            let autoInterval;
            const imgCache = [];

            // Get image URLs
            $('input[name="product_360_view_images[]"]').each(function() {
                const url = $(this).val().trim();
                if (url) images.push(url);
            });

            // Load images
            images.forEach((url, i) => {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.src = url;
                img.onload = function() {
                    imgCache[i] = img;
                    if (i === 0) renderFrame(0);
                };
            });

            function renderFrame(index) {
                if (!imgCache[index]) return;
                
                canvas.width = canvas.offsetWidth;
                canvas.height = canvas.offsetHeight;
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                const img = imgCache[index];
                const scale = Math.min(canvas.width / img.width, canvas.height / img.height) * zoom;
                const x = (canvas.width - img.width * scale) / 2;
                const y = (canvas.height - img.height * scale) / 2;
                
                ctx.drawImage(img, x, y, img.width * scale, img.height * scale);
                
                $('#product-360-current-frame').text(index + 1);
                $('#product-360-progress').css('width', ((index + 1) / images.length * 100) + '%');
                $('#product-360-frame-slider').val(index);
            }

            // Mouse drag
            canvas.addEventListener('mousedown', e => {
                isDragging = true;
                startX = e.clientX;
                canvas.style.cursor = 'grabbing';
                stopAuto();
            });

            canvas.addEventListener('mousemove', e => {
                if (!isDragging) return;
                const delta = e.clientX - startX;
                if (Math.abs(delta) > 3) {
                    currentFrame = (currentFrame + (delta > 0 ? -1 : 1) + images.length) % images.length;
                    renderFrame(currentFrame);
                    startX = e.clientX;
                }
            });

            canvas.addEventListener('mouseup', () => {
                isDragging = false;
                canvas.style.cursor = 'grab';
            });

            canvas.addEventListener('mouseleave', () => {
                isDragging = false;
                canvas.style.cursor = 'grab';
            });

            // Touch events
            let touchStartX = 0;
            canvas.addEventListener('touchstart', e => {
                touchStartX = e.touches[0].clientX;
                stopAuto();
            });

            canvas.addEventListener('touchmove', e => {
                const delta = e.touches[0].clientX - touchStartX;
                if (Math.abs(delta) > 3) {
                    currentFrame = (currentFrame + (delta > 0 ? -1 : 1) + images.length) % images.length;
                    renderFrame(currentFrame);
                    touchStartX = e.touches[0].clientX;
                }
            });

            // Auto rotate
            function startAuto() {
                isAutoRotating = true;
                $('#product-360-auto-rotate').html('⏸ Stop').css('background', 'rgba(220,53,69,0.9)');
                autoInterval = setInterval(() => {
                    currentFrame = (currentFrame + 1) % images.length;
                    renderFrame(currentFrame);
                }, 100);
            }

            function stopAuto() {
                isAutoRotating = false;
                $('#product-360-auto-rotate').html('▶ Auto Rotate').css('background', 'rgba(67,97,238,0.9)');
                if (autoInterval) clearInterval(autoInterval);
            }

            $('#product-360-auto-rotate').on('click', function() {
                if (isAutoRotating) stopAuto();
                else startAuto();
            });

            // Zoom
            $('#product-360-zoom-in').on('click', function() {
                zoom = Math.min(3, zoom + 0.2);
                renderFrame(currentFrame);
            });

            $('#product-360-zoom-out').on('click', function() {
                zoom = Math.max(0.5, zoom - 0.2);
                renderFrame(currentFrame);
            });

            // Slider
            $('#product-360-frame-slider').on('input', function() {
                currentFrame = parseInt($(this).val());
                renderFrame(currentFrame);
                stopAuto();
            });

            // Window resize
            $(window).on('resize', () => renderFrame(currentFrame));
        }
    });
    </script>
    <?php
});

/* ============================================================
 * 2. SAVE - Using same method as manufacturer images plugin
 * ============================================================ */
add_action('after_wcfm_products_manage_meta_save', function($product_id, $data){
    if (isset($data['product_360_view_images'])) {
        $urls = array_filter(array_map('esc_url_raw', (array)$data['product_360_view_images']));
        update_post_meta($product_id, 'product_360_view_images', $urls);
    } else {
        delete_post_meta($product_id, 'product_360_view_images');
    }
}, 10, 2);

/* ============================================================
 * 3. FRONTEND SHORTCODE - Button with Popup Modal
 * ============================================================ */
add_shortcode('product_360_viewer', function($atts){
    global $post;
    
    $atts = shortcode_atts([
        'product_id' => $post ? $post->ID : 0,
        'button_text' => 'View 360° Product Rotation',
        'button_style' => 'gradient' // gradient, blue, green, red, orange
    ], $atts);
    
    $product_id = intval($atts['product_id']);
    $images = get_post_meta($product_id, 'product_360_view_images', true);
    
    if (empty($images) || !is_array($images)) {
        return '';
    }

    $modal_id = 'modal-360-' . $product_id . '-' . rand(1000, 9999);
    $images_json = json_encode(array_values($images));
    
    // Button styles
    $button_styles = [
        'gradient' => 'background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);',
        'blue' => 'background:#2196F3;',
        'green' => 'background:#4CAF50;',
        'red' => 'background:#f44336;',
        'orange' => 'background:#ff9800;'
    ];
    
    $button_bg = isset($button_styles[$atts['button_style']]) ? $button_styles[$atts['button_style']] : $button_styles['gradient'];

    ob_start(); ?>

    <!-- 360° Button -->
    <div class="product-360-button-wrapper" style="margin:20px 0;">
        <button type="button" id="view360" class="open-360-btn-<?php echo esc_attr($modal_id); ?>" style="<?php echo $button_bg; ?>color:#fff;border:none;padding:15px 35px;font-size:17px;font-weight:700;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:12px;box-shadow:0 4px 20px rgba(102,126,234,0.4);transition:all 0.3s;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 6v6l4 2"/>
            </svg>
            <?php echo esc_html($atts['button_text']); ?>
        </button>
    </div>

    <!-- 360° Modal Popup -->
    <div id="<?php echo esc_attr($modal_id); ?>" class="product-360-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0 0 0 / 41%);z-index:999999;align-items:center;justify-content:center;">
        
        <!-- Close Button -->
        <button class="close-360-modal-<?php echo esc_attr($modal_id); ?>" style="position:absolute;top:70px;right:12.5vw;background:#dc3545;color:#fff;border:none;width:55px;height:55px;border-radius:50%;font-size:32px;cursor:pointer;z-index:10;box-shadow:0 4px 20px rgba(220,53,69,0.6);transition:all 0.3s;line-height:1;font-weight:300;padding: 1px;">
            ×
        </button>

        <!-- Modal Content -->
        <div class="modal-360-content" style="width:92%;max-width:1400px;height:92%;">
            <h2 style="color:#ffffff00;text-align:center;margin-bottom:25px;font-size:30px;font-weight:700;">🔄 Interactive 360° Product View</h2>
            
            <div class="canvas-height" style="position:relative;height:calc(100% - 120px);background:#000;border-radius:12px;overflow:hidden;box-shadow:0 10px 50px rgba(0,0,0,0.7);">
                <canvas class="modal-360-canvas" style="width:100%;height:100%;cursor:grab;"></canvas>
                
                <!-- Controls Overlay -->
                <div class="auto-rotate" style="position:absolute;top:20px;left:20px;right:20px;display:flex;justify-content:space-between;align-items:center;z-index:10;flex-wrap:wrap;gap:10px;">
                    <div style="background:rgba(0,0,0,0.85);color:#ffffff00;padding:12px 20px;border-radius:25px;font-size:15px;display:flex;align-items:center;gap:10px;display: none !important;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M2 12h20"/>
                        </svg>
                        <span>Drag or swipe to rotate</span>
                    </div>
                    <button class="modal-360-auto" style="background:rgba(67,97,238,0.9);color:#fff;border:none;padding:12px 16px;border-radius:25px;cursor:pointer;font-weight:600;font-size:15px;transition:all 0.3s;">
                        ▶ Auto Rotate
                    </button>
                </div>

                <!-- Zoom Controls -->
                <div Class="align-button" style="position:absolute;bottom:90px;right:20px;display:flex;flex-direction:column;gap:12px;z-index:10;">
                    <button class="modal-360-zoom-in" style="background:rgba(60 87 215);border:none;width:40px;height:40px;border-radius:50%;cursor:pointer;font-size:26px;box-shadow:0 4px 15px rgba(0,0,0,0.3);transition:all 0.3s;font-weight:300; line-height: 1px;padding: 1px;">+</button>
                    <button class="modal-360-zoom-reset" style="background:rgba(60 87 215);border:none;width:40px;height:40px;border-radius:50%;cursor:pointer;font-size:12px;font-weight:bold;box-shadow:0 4px 15px rgba(0,0,0,0.3);transition:all 0.3s;line-height: 0px !important;padding: 1px;">100%</button>
                    <button class="modal-360-zoom-out" style="background:rgba(60 87 215);border:none;width:40px;height:40px;border-radius:50%;cursor:pointer;font-size:26px;box-shadow:0 4px 15px rgba(0,0,0,0.3);transition:all 0.3s;font-weight:300;line-height: 1px;padding: 1px;">−</button>
                </div>

                <!-- Frame Counter -->
                <!--<div style="position:absolute;bottom:20px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.0);color:#fff;padding:12px 28px;border-radius:30px;font-size:17px;font-weight:600;z-index:10;box-shadow:0 4px 15px rgba(0,0,0,0);">
                    Frame <span class="modal-current-frame">1</span> / <span class="modal-total-frames"><?php echo count($images); ?></span>
                </div>-->

                <!-- Progress Bar -->
                <div style="position:absolute;bottom:0;left:0;right:0;height:5px;background:rgba(255,255,255,0.2);z-index:10;">
                    <div class="modal-360-progress" style="height:100%;background:linear-gradient(90deg, #667eea 0%, #764ba2 100%);width:0%;transition:width 0.1s;"></div>
                </div>
            </div>

            <!-- Frame Slider -->
            <input type="range" class="modal-360-slider" min="0" max="<?php echo count($images) - 1; ?>" value="0" style="display: none;width:100%;margin-top:18px;height:8px;border-radius:5px;outline:none;cursor:pointer;">

            <!-- Instructions -->
            <div style="text-align:center;margin-top:15px;color:#fff;font-size:14px;opacity:0.85;">
                <p style="display: none !important;margin:0;">💡 <strong>Tip:</strong> Drag to rotate • Pinch to zoom • Use slider for precision • Press ESC to close</p>
            </div>
        </div>
    </div>

    <style>
    .open-360-btn-<?php echo esc_attr($modal_id); ?>:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 25px rgba(102,126,234,0.6);
    }
    
    .close-360-modal-<?php echo esc_attr($modal_id); ?>:hover {
        transform: scale(1.1) rotate(90deg);
        background: #c82333;
    }
    
    .modal-360-auto:hover,
    .modal-360-zoom-in:hover,
    .modal-360-zoom-out:hover,
    .modal-360-zoom-reset:hover {
        transform: scale(1.1);
    }
    
    .modal-360-canvas {
        cursor: grab;
    }
    
    .modal-360-canvas:active {
        cursor: grabbing;
    }
    
    .product-360-modal {
        animation: fadeInModal 0.3s ease-out;
    }
    
    @keyframes fadeInModal {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @media (max-width: 768px) {
        .modal-360-content {
            width: 96% !important;
            height: 96% !important;
        }
        .modal-360-content h2 {
            font-size: 22px !important;
            margin-bottom: 15px !important;
        }
        .modal-360-zoom-in,
        .modal-360-zoom-out,
        .modal-360-zoom-reset {
            width: 46px !important;
            height: 46px !important;
            font-size: 22px !important;
        }
        .close-360-modal-<?php echo esc_attr($modal_id); ?> {
            top: 100px !important;
            width: 50px !important;
            height: 50px !important;
            font-size: 28px !important;
        }
    }
   
    </style>

    <script>
    (function() {
        const openBtn = document.querySelector('.open-360-btn-<?php echo esc_js($modal_id); ?>');
        const modal = document.getElementById('<?php echo esc_js($modal_id); ?>');
        const closeBtn = modal.querySelector('.close-360-modal-<?php echo esc_js($modal_id); ?>');
        const canvas = modal.querySelector('.modal-360-canvas');
        const ctx = canvas.getContext('2d');
        const autoBtn = modal.querySelector('.modal-360-auto');
        const zoomInBtn = modal.querySelector('.modal-360-zoom-in');
        const zoomOutBtn = modal.querySelector('.modal-360-zoom-out');
        const zoomResetBtn = modal.querySelector('.modal-360-zoom-reset');
        const currentFrameEl = modal.querySelector('.modal-current-frame');
        const progressBar = modal.querySelector('.modal-360-progress');
        const slider = modal.querySelector('.modal-360-slider');
        
        const images = <?php echo $images_json; ?>;
        const imageCache = [];
        let currentFrame = 0;
        let isDragging = false;
        let startX = 0;
        let zoom = 1;
        let isAutoRotating = false;
        let autoInterval;
        const sensitivity = 3;
        
        // Load images
        images.forEach((url, i) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.src = url;
            img.onload = () => imageCache[i] = img;
        });
        
        function renderFrame(index) {
            if (!imageCache[index]) return;
            
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            const img = imageCache[index];
            const scale = Math.min(canvas.width / img.width, canvas.height / img.height) * zoom;
            const x = (canvas.width - img.width * scale) / 2;
            const y = (canvas.height - img.height * scale) / 2;
            
            ctx.drawImage(img, x, y, img.width * scale, img.height * scale);
            
            currentFrameEl.textContent = index + 1;
            progressBar.style.width = ((index + 1) / images.length * 100) + '%';
            slider.value = index;
            zoomResetBtn.textContent = Math.round(zoom * 100) + '%';
        }
        
        // Open modal
        openBtn.addEventListener('click', () => {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            setTimeout(() => renderFrame(0), 100);
        });
        
        // Close modal
        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            stopAuto();
            zoom = 1;
            currentFrame = 0;
        }
        
        closeBtn.addEventListener('click', closeModal);
        
        // Close on background click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
        
        // Close on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                closeModal();
            }
        });
        
        // Drag to rotate
        canvas.addEventListener('mousedown', e => {
            isDragging = true;
            startX = e.clientX;
            canvas.style.cursor = 'grabbing';
            stopAuto();
        });
        
        canvas.addEventListener('mousemove', e => {
            if (!isDragging) return;
            const delta = e.clientX - startX;
            if (Math.abs(delta) > sensitivity) {
                currentFrame = (currentFrame + (delta > 0 ? -1 : 1) + images.length) % images.length;
                renderFrame(currentFrame);
                startX = e.clientX;
            }
        });
        
        canvas.addEventListener('mouseup', () => {
            isDragging = false;
            canvas.style.cursor = 'grab';
        });
        
        canvas.addEventListener('mouseleave', () => {
            isDragging = false;
            canvas.style.cursor = 'grab';
        });
        
        // Touch events
        let touchStartX = 0;
        canvas.addEventListener('touchstart', e => {
            touchStartX = e.touches[0].clientX;
            stopAuto();
        });
        
        canvas.addEventListener('touchmove', e => {
            const delta = e.touches[0].clientX - touchStartX;
            if (Math.abs(delta) > sensitivity) {
                currentFrame = (currentFrame + (delta > 0 ? -1 : 1) + images.length) % images.length;
                renderFrame(currentFrame);
                touchStartX = e.touches[0].clientX;
            }
        });
        
        // Auto rotate
        function startAuto() {
            isAutoRotating = true;
            autoBtn.innerHTML = '⏸ Stop';
            autoBtn.style.background = 'rgba(220,53,69,0.9)';
            autoInterval = setInterval(() => {
                currentFrame = (currentFrame + 1) % images.length;
                renderFrame(currentFrame);
            }, 100);
        }
        
        function stopAuto() {
            isAutoRotating = false;
            autoBtn.innerHTML = '▶ Auto Rotate';
            autoBtn.style.background = 'rgba(67,97,238,0.9)';
            if (autoInterval) clearInterval(autoInterval);
        }
        
        autoBtn.addEventListener('click', () => {
            if (isAutoRotating) stopAuto();
            else startAuto();
        });
        
        // Zoom controls
        zoomInBtn.addEventListener('click', () => {
            zoom = Math.min(3, zoom + 0.2);
            renderFrame(currentFrame);
        });
        
        zoomOutBtn.addEventListener('click', () => {
            zoom = Math.max(0.5, zoom - 0.2);
            renderFrame(currentFrame);
        });
        
        zoomResetBtn.addEventListener('click', () => {
            zoom = 1;
            renderFrame(currentFrame);
        });
        
        // Slider
        slider.addEventListener('input', () => {
            currentFrame = parseInt(slider.value);
            renderFrame(currentFrame);
            stopAuto();
        });
        
        // Resize handler
        window.addEventListener('resize', () => {
            if (modal.style.display === 'flex') {
                renderFrame(currentFrame);
            }
        });
    })();
    </script>

    <?php

    return ob_get_clean();
});

/* ============================================================
 * 4. SHORTCODE USAGE EXAMPLES
 * ============================================================ 
 * 
 * Basic usage:
 * [product_360_viewer]
 * 
 * With custom button text:
 * [product_360_viewer button_text="See Product in 360°"]
 * 
 * With different button style:
 * [product_360_viewer button_style="blue"]
 * [product_360_viewer button_style="green"]
 * [product_360_viewer button_style="red"]
 * [product_360_viewer button_style="orange"]
 * 
 * Combine options:
 * [product_360_viewer button_text="View All Angles" button_style="green"]
 * 
 * For specific product:
 * [product_360_viewer product_id="123"]
 * 
 * ============================================================ */