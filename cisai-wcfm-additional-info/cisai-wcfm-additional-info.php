<?php
/**
 * Plugin Name: CISAI WCFM Additional Details
 * Description: Adds a single "Additional Details" TinyMCE visual editor with full table, image, media & preview popup to WCFM product edit page. Saves via AJAX and includes shortcode.
 * Version: 2.6
 * Author: Adarsh Singh
 */

if (!defined('ABSPATH')) exit;

/* ============================================================
 * 1️⃣ Add Additional Details Editor to WCFM Product Edit
 * ============================================================ */
add_action('after_wcfm_products_manage_general', function($product_id) {
    $meta_key = 'additional_details';
    $label = 'Additional Details';
    $saved_data = get_post_meta($product_id, '_' . $meta_key, true);
    ?>

    <div class="page_collapsible products_manage_<?php echo esc_attr($meta_key); ?> simple variable external grouped">
        <label class="wcfmfa fa-file-alt"></label> <?php echo esc_html($label); ?><span></span>
    </div>

    <div class="wcfm-container simple variable external grouped">
        <div class="wcfm-content">
            <textarea id="<?php echo esc_attr($meta_key); ?>_editor" name="<?php echo esc_attr($meta_key); ?>" style="width:100%; height:400px;"><?php echo esc_textarea($saved_data); ?></textarea>

            <div style="margin-top:10px;">
                <button id="save_<?php echo esc_attr($meta_key); ?>" class="wcfm_submit_button">💾 Save <?php echo esc_html($label); ?></button>
                <button id="preview_<?php echo esc_attr($meta_key); ?>" class="wcfm_submit_button" style="background:#05b895; color:#fff;">👁 Preview</button>
            </div>

            <p id="<?php echo esc_attr($meta_key); ?>_status" style="margin-top:5px;"></p>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal_<?php echo esc_attr($meta_key); ?>" class="wcfm-preview-modal" style="display:none;">
        <div class="wcfm-preview-overlay"></div>
        <div class="wcfm-preview-content">
            <div class="wcfm-preview-header">
                <h3>Preview — <?php echo esc_html($label); ?></h3>
                <button class="close-preview">✖</button>
            </div>
            <div class="wcfm-preview-body"></div>
        </div>
    </div>

    <style>
        /* Modal Styles */
        .wcfm-preview-modal {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 999999;
        }
        .wcfm-preview-overlay {
            background: rgba(0,0,0,0.5);
            width: 100%; height: 100%;
            position: absolute; top: 0; left: 0;
        }
        .wcfm-preview-content {
            background: #fff;
            width: 80%;
            max-width: 900px;
            margin: 60px auto;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            position: relative;
        }
        .wcfm-preview-header h3 {
        color: #fff !important;
        }
        .wcfm-preview-header {
            background: #05b895;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .wcfm-preview-body {
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
        }
        .close-preview {
            display: none !important;
            background: none;
            border: none;
            color: #fff;
            font-size: 18px;
            cursor: pointer;
        }
        #<?php echo esc_attr($meta_key); ?>_editor { border: 1px solid #ccc; border-radius: 6px; }
        .mce-container { z-index: 999999 !important; }
        div#mceu_289-body {
    border: 1px solid !important;
}
div#mceu_308 {
    border: 1px solid !important;
}
div.mce-panel {
    border: 1px solid !important;
    background: #fff;
}
    </style>

    <script>
    jQuery(document).ready(function($) {
        const key = '<?php echo esc_js($meta_key); ?>';

        // ✅ Initialize TinyMCE safely
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: '#' + key + '_editor',
                height: 450,
                menubar: 'file edit view insert format table tools help',
                branding: false,
                plugins: [
                    'advlist autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | styleselect | bold italic underline forecolor backcolor | ' +
                         'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | ' +
                         'link image media | table | removeformat | code',
                toolbar_mode: 'floating',
                contextmenu: 'inserttable | cell row column deletetable',
                table_default_attributes: { border: '1' },
                table_default_styles: { width: '100%', borderCollapse: 'collapse' },
                table_toolbar: 'tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol',
                content_style: 'body { font-family: Arial, sans-serif; font-size:15px; }',
                relative_urls: false,
                remove_script_host: false,
                convert_urls: true,
                setup: function(editor) {
                    editor.on('change', function() {
                        editor.save();
                    });
                }
            });
        }

        // ✅ AJAX Save Handler
        $('#save_' + key).on('click', function(e) {
            e.preventDefault();
            var content = tinymce.get(key + '_editor') ? tinymce.get(key + '_editor').getContent() : $('#' + key + '_editor').val();
            $('#' + key + '_status').text('Saving...');
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'save_wcfm_additional_details',
                    product_id: '<?php echo esc_js($product_id); ?>',
                    content: content,
                    security: '<?php echo wp_create_nonce('save_wcfm_additional_details_nonce'); ?>'
                },
                success: function(response) {
                    $('#' + key + '_status').text(response.data.message).css('color', 'green');
                },
                error: function() {
                    $('#' + key + '_status').text('Error saving content.').css('color', 'red');
                }
            });
        });

        // 👁 Preview Popup
        $('#preview_' + key).on('click', function(e) {
            e.preventDefault();
            const content = tinymce.get(key + '_editor') ? tinymce.get(key + '_editor').getContent() : $('#' + key + '_editor').val();
            if (!content.trim()) {
                alert('No content to preview!');
                return;
            }
            $('#previewModal_' + key + ' .wcfm-preview-body').html(content);
            $('#previewModal_' + key).fadeIn(200);
        });

        // ❌ Close Popup
        $(document).on('click', '.close-preview, .wcfm-preview-overlay', function() {
            $('.wcfm-preview-modal').fadeOut(200);
        });
    });
    </script>

    <?php
});

/* ============================================================
 * 2️⃣ AJAX Save Handler
 * ============================================================ */
add_action('wp_ajax_save_wcfm_additional_details', function() {
    check_ajax_referer('save_wcfm_additional_details_nonce', 'security');
    $product_id = intval($_POST['product_id']);
    $content = wp_kses_post($_POST['content']);
    update_post_meta($product_id, '_additional_details', $content);
    wp_send_json_success(['message' => '✅ Additional Details saved successfully!']);
});

/* ============================================================
 * 3️⃣ Load TinyMCE + Table Plugin Without Errors
 * ============================================================ */
add_action('wp_footer', function() {
    if (function_exists('is_wcfm_page') && is_wcfm_page()) {
        ?>
        <script src="<?php echo includes_url('js/tinymce/tinymce.min.js'); ?>"></script>
        <script>
        if (typeof tinymce !== 'undefined' && tinymce.PluginManager) {
            try {
                tinymce.PluginManager.load('table', '<?php echo includes_url('js/tinymce/plugins/table/plugin.min.js'); ?>');
            } catch (e) {
                console.warn('TinyMCE table plugin already loaded.');
            }
        }

        // ✅ Hide "Failed to load plugin" notices if any occur
        setInterval(() => {
            jQuery('.mce-notification-inner:contains("Failed to load plugin")').closest('.mce-notification').fadeOut(300);
        }, 800);
        </script>
        <?php
    }
});

/* ============================================================
 * 4️⃣ Shortcode: [product_additional_details id="123"]
 * ============================================================ */
add_shortcode('product_additional_details', function($atts) {
    $atts = shortcode_atts(['id' => get_the_ID()], $atts, 'product_additional_details');
    $content = get_post_meta($atts['id'], '_additional_details', true);
    if (empty(trim($content))) return '';
    return '<div class="product-additional-details" style="margin:30px 0;">' . wp_kses_post($content) . '</div>';
});
