<?php
/**
 * Plugin Name: CISAI WCFM Bulk Product Import (CSV/Excel)
 * Description: Upload multiple products at once using CSV or Excel. Supports standard WooCommerce fields + WCFM meta. When an incoming row appears to duplicate an existing product (same SKU OR same name+price OR identical name+price+description), the plugin will either update the existing product or create a new draft copy depending on the chosen option in the modal.
 * Version: 1.2
 * Author: Your Name
 * Text Domain: wcfm-bulk-product-import
 */

if (! defined('ABSPATH')) exit;

/* -------------------------
 * Activation dependency check
 * ------------------------- */
register_activation_hook(__FILE__, function() {
    if (!class_exists('WooCommerce') || !function_exists('wcfm_get_endpoint_url')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('This plugin requires WooCommerce and WCFM (Frontend Manager) to be active.', 'wcfm-bulk-product-import'));
    }
});

/* -------------------------
 * Add button to WCFM products action bar
 * ------------------------- */
add_action('wcfm_products_quick_actions', function() {
    if ( ! ( current_user_can('administrator') || (function_exists('wcfm_is_vendor') && wcfm_is_vendor()) ) ) return;
    ?>
    <a href="#" id="wcfm_bulk_csv_open_btn" class="button" style="margin-left:8px;background:#1c2b36;color:#fff;border-radius:3px;padding: 8px 12px;font-size:15px;font-weight:300;">
        <i class="fa fa-upload"></i>&nbsp;Bulk CSV Import
    </a>
    <?php
});

/* -------------------------
 * Modal HTML (front-end UI)
 * ------------------------- */
add_action('wp_footer', function() {
    if ( ! ( current_user_can('administrator') || (function_exists('wcfm_is_vendor') && wcfm_is_vendor()) ) ) return;
    $nonce = wp_create_nonce('wcfm_bulk_csv_import_nonce');
    ?>
    <div id="wcfm_bulk_csv_modal" style="display:none;">
        <div class="wcfm-bulk-csv-modal-inner">
            <button id="wcfm_bulk_csv_close">&times;</button>
            <h2><?php _e('Bulk CSV Import', 'wcfm-bulk-product-import'); ?></h2>
            <p><?php _e('Upload CSV/Excel with WooCommerce product fields (ID, SKU, Name, Price, Stock, Categories, Images, Meta, etc.). When a duplicate is detected you can choose whether to update the existing product or create a new draft copy.', 'wcfm-bulk-product-import'); ?></p>

            <input type="file" id="wcfm_csv_file" accept=".csv,.xls,.xlsx" />

            <div style="margin-top:12px;">
                <label style="font-weight:600;"><?php _e('If duplicate product found:', 'wcfm-bulk-product-import'); ?></label><br>
                <select id="wcfm_duplicate_action" style="width:100%;padding:7px;border-radius:4px;">
                    <option value="copy" selected><?php _e('Duplicate → New draft copy', 'wcfm-bulk-product-import'); ?></option>
                    <option value="update"><?php _e('Update existing product', 'wcfm-bulk-product-import'); ?></option>
                </select>
                <small style="display:block;margin-top:6px;color:#666;"><?php _e('Default: create a draft copy. If "Update" is chosen, the importer will replace title, description, short description, images (replace), categories (replace), SKU (if not colliding), and stock.', 'wcfm-bulk-product-import'); ?></small>
            </div>

            <div style="margin-top:10px;">
                <button id="wcfm_csv_upload_btn" class="button button-primary"><?php _e('Upload & Import', 'wcfm-bulk-product-import'); ?></button>
                <span id="wcfm_csv_status" style="margin-left:10px;font-weight:600;"></span>
            </div>
            <div id="wcfm_csv_result" style="margin-top:12px;white-space:pre-wrap;"></div>
        </div>
    </div>

    <style>
    #wcfm_bulk_csv_modal{position:fixed;left:0;top:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:999999;display:flex;align-items:flex-start;padding-top:6%;justify-content:center;}
    .wcfm-bulk-csv-modal-inner{background:#fff;padding:22px;border-radius:8px;max-width:720px;width:94%;box-shadow:0 8px 30px rgba(0,0,0,.25);position:relative;}
    #wcfm_bulk_csv_close{position:absolute;right:12px;top:8px;border:0;background:#0b1115;color:#fff;font-size:26px;cursor:pointer;padding: 0px 8px;border-radius: 30px !important;}
    </style>

    <script type="text/javascript">
    (function($){
        $(document).ready(function(){
            $('#wcfm_bulk_csv_open_btn').on('click', function(e){
                e.preventDefault();
                $('#wcfm_csv_status').text('');
                $('#wcfm_csv_result').html('');
                $('#wcfm_csv_file').val('');
                $('#wcfm_bulk_csv_modal').fadeIn(150);
            });
            $('#wcfm_bulk_csv_close').on('click', function(){ $('#wcfm_bulk_csv_modal').fadeOut(150); });
            $('#wcfm_bulk_csv_modal').on('click', function(e){ if(e.target === this) $(this).fadeOut(150); });

            $('#wcfm_csv_upload_btn').on('click', function(e){
                e.preventDefault();
                var fileInput = $('#wcfm_csv_file')[0];
                if (!fileInput.files || !fileInput.files[0]) {
                    alert('<?php echo esc_js(__('Please choose a CSV/Excel file first.', 'wcfm-bulk-product-import')); ?>');
                    return;
                }
                var fd = new FormData();
                fd.append('file', fileInput.files[0]);
                fd.append('action', 'wcfm_bulk_csv_import_ajax');
                fd.append('nonce', '<?php echo $nonce; ?>');
                fd.append('duplicate_action', $('#wcfm_duplicate_action').val());

                $('#wcfm_csv_status').text('<?php echo esc_js(__('Uploading...', 'wcfm-bulk-product-import')); ?>');

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: fd,
                    contentType: false,
                    processData: false,
                    timeout: 0,
                    success: function(resp){
                        var msg = '';
                        if(resp.success) {
                            msg = resp.data.message;
                            $('#wcfm_csv_status').text('<?php echo esc_js(__('Done', 'wcfm-bulk-product-import')); ?>');
                        } else {
                            msg = resp.data ? resp.data.message : 'Import failed';
                            $('#wcfm_csv_status').text('<?php echo esc_js(__('Error', 'wcfm-bulk-product-import')); ?>');
                        }
                        $('#wcfm_csv_result').html('<pre>'+ $('<div/>').text(msg).html() +'</pre>');
                    },
                    error: function(xhr){ $('#wcfm_csv_status').text('Error'); $('#wcfm_csv_result').html('<pre>'+xhr.responseText+'</pre>'); }
                });
            });
        });
    })(jQuery);
    </script>
    <?php
});

/* -------------------------
 * Helper: find duplicate product
 * Returns product ID if a duplicate is found (by SKU OR name+price OR name+price+description), false otherwise
 * ------------------------- */
function cisai_find_duplicate_product($sku, $name, $regular_price, $description) {
    // 1) Check SKU (fast)
    if (!empty($sku)) {
        $sku = trim($sku);
        $existing_by_sku = wc_get_product_id_by_sku($sku);
        if ($existing_by_sku) return intval($existing_by_sku);
    }

    // 2) Check by name + regular price
    if ($name !== '' && $regular_price !== '') {
        $name_trim = trim($name);
        $price_val = trim($regular_price);

        global $wpdb;
        $sql = $wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_regular_price' AND pm.meta_value = %s
             WHERE p.post_type = 'product' AND p.post_title = %s
             LIMIT 1",
            $price_val,
            $name_trim
        );
        $found_id = $wpdb->get_var($sql);
        if ($found_id) {
            return intval($found_id);
        }
    }

    // 3) Check by exact name + price + description (optional deeper check)
    if ($name !== '' && $regular_price !== '' && $description !== '') {
        global $wpdb;
        $sql2 = $wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_regular_price' AND pm.meta_value = %s
             WHERE p.post_type = 'product' AND p.post_title = %s AND p.post_content = %s
             LIMIT 1",
            trim($regular_price),
            trim($name),
            trim($description)
        );
        $found_id2 = $wpdb->get_var($sql2);
        if ($found_id2) return intval($found_id2);
    }

    return false;
}

/* -------------------------
 * AJAX handler: import CSV
 * ------------------------- */
add_action('wp_ajax_wcfm_bulk_csv_import_ajax', function() {
    if (! (current_user_can('administrator') || (function_exists('wcfm_is_vendor') && wcfm_is_vendor())) ) {
        wp_send_json_error(['message' => __('Access denied.', 'wcfm-bulk-product-import')]);
    }
    $nonce = $_POST['nonce'] ?? '';
    if (! wp_verify_nonce($nonce, 'wcfm_bulk_csv_import_nonce') ) {
        wp_send_json_error(['message' => __('Security check failed.', 'wcfm-bulk-product-import')]);
    }

    if ( empty($_FILES['file']['tmp_name']) ) {
        wp_send_json_error(['message' => __('File missing.', 'wcfm-bulk-product-import')]);
    }

    // normalize duplicate action (copy | update)
    $duplicate_action = sanitize_text_field($_POST['duplicate_action'] ?? 'copy');
    if (!in_array($duplicate_action, array('copy','update'), true)) $duplicate_action = 'copy';

    $file = $_FILES['file']['tmp_name'];
    $ext  = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

    // read CSV (Excel currently not implemented)
    $rows = [];
    if (strtolower($ext) === 'csv') {
        if (($handle = fopen($file, 'r')) !== false) {
            // attempt to detect BOM/encoding and normalize - basic approach
            while (($data = fgetcsv($handle, 0, ",")) !== false) {
                // skip empty lines
                $allEmpty = true;
                foreach ($data as $c) { if (trim($c) !== '') { $allEmpty = false; break; } }
                if ($allEmpty) continue;
                $rows[] = $data;
            }
            fclose($handle);
        }
    } elseif (in_array(strtolower($ext), ['xls','xlsx'])) {
        // Minimal Excel support message (user can implement PhpSpreadsheet if desired)
        wp_send_json_error(['message'=>'Excel import not implemented in this package. Please use CSV.']);
    } else {
        wp_send_json_error(['message'=>'Invalid file type. Only CSV supported currently.']);
    }

    if (count($rows) < 2) wp_send_json_error(['message'=>'CSV must have header + data rows.']);

    $header = array_map('trim', $rows[0]);
    $data_rows = array_slice($rows, 1);

    $success = 0; $failed = 0; $errors = []; $created_ids = [];

    $current_user = get_current_user_id();

    // helper: sideload media
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );

    foreach ($data_rows as $index => $row) {
        if (count($row) < count($header)) $row = array_pad($row, count($header), '');
        $row_assoc = @array_combine($header, $row);
        if (!is_array($row_assoc)) {
            $failed++;
            $errors[] = "Row ".($index+2).": header/data mismatch.";
            continue;
        }

        // Normalize common fields (case-insensitive)
        $get = function($keys) use ($row_assoc) {
            foreach ((array)$keys as $k) {
                if (isset($row_assoc[$k]) && trim($row_assoc[$k]) !== '') return trim($row_assoc[$k]);
            }
            return '';
        };

        $id_in = intval($get(['ID','Id','id']));
        $sku_in = $get(['SKU','sku','Sku']);
        $name_in = $get(['Name','name','Title','title']);
        $regular_price_in = $get(['Regular price','Regular Price','regular_price','regular price','Price','price']);
        $sale_price_in = $get(['Sale price','Sale Price','sale_price','sale price']);
        $stock_in = $get(['Stock','stock','Stock quantity','stock_quantity']);
        $desc_in = $get(['Description','description','Long description','long_description']);
        $short_desc_in = $get(['Short description','short description','Short Description','short_description']);
        $categories_in = $get(['Categories','categories','Category','category']);
        $images_in = $get(['Images','images','Image','image']);

        try {
            // Determine duplicate logic
            $duplicate_found_id = cisai_find_duplicate_product($sku_in, $name_in, $regular_price_in, $desc_in);

            // If duplicate found -> follow user preference: update OR create draft copy
            if ($duplicate_found_id) {
                if ($duplicate_action === 'update') {
                    // We'll treat this row as an update: load product object and let update logic below handle it.
                    $product = wc_get_product($duplicate_found_id);
                    if ($product) {
                        $is_update = true;
                    } else {
                        // fallback to create copy if product load failed
                        $is_update = false;
                    }
                } else {
                    // Duplicate action = copy -> create a NEW DRAFT copy and continue to next row
                    $new_product = new WC_Product_Simple();
                    $copy_name = $name_in ? $name_in . ' (Copy)' : 'Product Copy';
                    $new_product->set_name(sanitize_text_field($copy_name));
                    if ($regular_price_in !== '') $new_product->set_regular_price(floatval(preg_replace('/[^\d.\-]/', '', $regular_price_in)));
                    if ($sale_price_in !== '') $new_product->set_sale_price(floatval(preg_replace('/[^\d.\-]/', '', $sale_price_in)));
                    if ($sku_in !== '') {
                        $base_sku = sanitize_text_field($sku_in);
                        $unique_sku = $base_sku;
                        if (wc_get_product_id_by_sku($unique_sku)) {
                            $unique_sku = $base_sku . '-copy-' . time();
                        }
                        $new_product->set_sku($unique_sku);
                    }
                    if ($desc_in !== '') $new_product->set_description(sanitize_textarea_field($desc_in));
                    if ($short_desc_in !== '') $new_product->set_short_description(sanitize_textarea_field($short_desc_in));
                    if ($stock_in !== '') { $new_product->set_manage_stock(true); $new_product->set_stock_quantity(intval($stock_in)); }
                    $new_product->set_status('draft');

                    // categories
                    if ($categories_in !== '') {
                        $cats = array_map('trim', explode(',', $categories_in));
                        $cat_ids = [];
                        foreach ($cats as $c) {
                            if ($c === '') continue;
                            $term = get_term_by('slug', sanitize_title($c), 'product_cat');
                            if (!$term) $term = get_term_by('name', $c, 'product_cat');
                            if ($term && !is_wp_error($term)) $cat_ids[] = intval($term->term_id);
                        }
                        if (!empty($cat_ids)) $new_product->set_category_ids($cat_ids);
                    }

                    $new_id = $new_product->save();

                    // images: sideload to new product
                    if ($images_in !== '') {
                        $imgs = array_filter(array_map('trim', explode(',', $images_in)));
                        $attach_ids = [];
                        foreach ($imgs as $img_url) {
                            if (!filter_var($img_url, FILTER_VALIDATE_URL)) continue;
                            $tmp_file = download_url($img_url);
                            if (is_wp_error($tmp_file)) continue;
                            $file = array(
                                'name' => basename( parse_url($img_url, PHP_URL_PATH) ),
                                'tmp_name' => $tmp_file
                            );
                            $attach_id = media_handle_sideload($file, $new_id);
                            if (is_wp_error($attach_id)) {
                                @unlink($file['tmp_name']);
                                continue;
                            }
                            $attach_ids[] = $attach_id;
                        }
                        if (!empty($attach_ids)) {
                            set_post_thumbnail($new_id, $attach_ids[0]);
                            if (count($attach_ids) > 1) {
                                update_post_meta($new_id, '_product_image_gallery', implode(',', $attach_ids));
                            }
                        }
                    }

                    // WCFM vendor assignment
                    update_post_meta($new_id, '_wcfm_product_vendor', $current_user);
                    update_post_meta($new_id, '_vendor_id', $current_user);

                    // Save any Meta: columns present in CSV as post meta
                    foreach ($row_assoc as $key => $val) {
                        if ($val === '') continue;
                        if (strpos($key, 'Meta:') === 0) {
                            $meta_key = trim(str_replace('Meta:', '', $key));
                            if ($meta_key !== '') update_post_meta($new_id, $meta_key, $val);
                        }
                    }

                    $created_ids[] = $new_id;
                    $success++;
                    continue; // move to next row
                }
            } else {
                // Not a duplicate by our detection rules
                $product = null;
                $is_update = false;
            }

            // If not duplicate, proceed with original behavior: update existing if ID present otherwise create new published product
            // Note: if duplicate_action was 'update' and duplicate found, $product and $is_update are set above.
            if (!isset($is_update)) $is_update = false;

            if (!$is_update) {
                // Detect update candidate by ID or SKU (non-duplicate cases)
                if ($id_in > 0 && get_post_type($id_in) === 'product') {
                    $product = wc_get_product($id_in);
                    $is_update = (bool)$product;
                } else if ($sku_in !== '') {
                    $by_sku = wc_get_product_id_by_sku($sku_in);
                    if ($by_sku) {
                        $product = wc_get_product($by_sku);
                        $is_update = (bool)$product;
                    }
                }
            }

            if ($is_update && $product) {
                // update existing product - chosen update behavior: replace title, descriptions, images (replace), categories (replace), SKU (if possible), stock
                if ($name_in !== '') $product->set_name(sanitize_text_field($name_in));
                if ($regular_price_in !== '') $product->set_regular_price(floatval(preg_replace('/[^\d.\-]/', '', $regular_price_in)));
                if ($sale_price_in !== '') $product->set_sale_price(floatval(preg_replace('/[^\d.\-]/', '', $sale_price_in)));
                if ($sku_in !== '') {
                    $existing_id_for_sku = wc_get_product_id_by_sku($sku_in);
                    if (!$existing_id_for_sku || intval($existing_id_for_sku) === intval($product->get_id())) {
                        $product->set_sku(sanitize_text_field($sku_in));
                    } else {
                        // avoid collision - append suffix
                        $product->set_sku(sanitize_text_field($sku_in) . '-import-' . time());
                    }
                }
                if ($stock_in !== '') { $product->set_manage_stock(true); $product->set_stock_quantity(intval($stock_in)); }
                if ($desc_in !== '') $product->set_description(sanitize_textarea_field($desc_in));
                if ($short_desc_in !== '') $product->set_short_description(sanitize_textarea_field($short_desc_in));

                // categories - REPLACE
                if ($categories_in !== '') {
                    $cats = array_map('trim', explode(',', $categories_in));
                    $cat_ids = [];
                    foreach ($cats as $c) {
                        if ($c === '') continue;
                        $term = get_term_by('slug', sanitize_title($c), 'product_cat');
                        if (!$term) $term = get_term_by('name', $c, 'product_cat');
                        if ($term && !is_wp_error($term)) $cat_ids[] = intval($term->term_id);
                    }
                    if (!empty($cat_ids)) $product->set_category_ids($cat_ids);
                }

                // images: if provided we REPLACE existing images with provided ones
                if ($images_in !== '') {
                    $imgs = array_filter(array_map('trim', explode(',', $images_in)));
                    $attach_ids = [];
                    foreach ($imgs as $img_url) {
                        if (!filter_var($img_url, FILTER_VALIDATE_URL)) continue;
                        $tmp_file = download_url($img_url);
                        if (is_wp_error($tmp_file)) continue;
                        $file = array(
                            'name' => basename( parse_url($img_url, PHP_URL_PATH) ),
                            'tmp_name' => $tmp_file
                        );
                        $attach_id = media_handle_sideload($file, $product->get_id());
                        if (is_wp_error($attach_id)) { @unlink($file['tmp_name']); continue; }
                        $attach_ids[] = $attach_id;
                    }
                    if (!empty($attach_ids)) {
                        set_post_thumbnail($product->get_id(), $attach_ids[0]);
                        update_post_meta($product->get_id(), '_product_image_gallery', implode(',', $attach_ids));
                    }
                }

                // Update WCFM vendor assignment to uploader (optional)
                update_post_meta($product->get_id(), '_wcfm_product_vendor', $current_user);
                update_post_meta($product->get_id(), '_vendor_id', $current_user);

                // Meta fields from CSV
                foreach ($row_assoc as $key=>$val) {
                    if ($val === '') continue;
                    if (strpos($key, 'Meta:') === 0) {
                        $meta_key = trim(str_replace('Meta:', '', $key));
                        if ($meta_key !== '') update_post_meta($product->get_id(), $meta_key, $val);
                    }
                }

                $product->save();
                $success++;
            } else {
                // create new product (publish)
                $new_product = new WC_Product_Simple();
                if ($name_in !== '') $new_product->set_name(sanitize_text_field($name_in));
                if ($regular_price_in !== '') $new_product->set_regular_price(floatval(preg_replace('/[^\d.\-]/', '', $regular_price_in)));
                if ($sale_price_in !== '') $new_product->set_sale_price(floatval(preg_replace('/[^\d.\-]/', '', $sale_price_in)));
                if ($sku_in !== '') {
                    $unique_sku = sanitize_text_field($sku_in);
                    if (wc_get_product_id_by_sku($unique_sku)) {
                        // make unique by appending timestamp
                        $unique_sku = $unique_sku . '-import-' . time();
                    }
                    $new_product->set_sku($unique_sku);
                }
                if ($stock_in !== '') { $new_product->set_manage_stock(true); $new_product->set_stock_quantity(intval($stock_in)); }
                if ($desc_in !== '') $new_product->set_description(sanitize_textarea_field($desc_in));
                if ($short_desc_in !== '') $new_product->set_short_description(sanitize_textarea_field($short_desc_in));
                if ($categories_in !== '') {
                    $cats = array_map('trim', explode(',', $categories_in));
                    $cat_ids = [];
                    foreach ($cats as $c) {
                        if ($c === '') continue;
                        $term = get_term_by('slug', sanitize_title($c), 'product_cat');
                        if (!$term) $term = get_term_by('name', $c, 'product_cat');
                        if ($term && !is_wp_error($term)) $cat_ids[] = intval($term->term_id);
                    }
                    if (!empty($cat_ids)) $new_product->set_category_ids($cat_ids);
                }

                // status: publish (same as original behavior)
                $new_product->set_status('publish');

                $new_id = $new_product->save();

                // images: sideload to new product
                if ($images_in !== '') {
                    $imgs = array_filter(array_map('trim', explode(',', $images_in)));
                    $attach_ids = [];
                    foreach ($imgs as $img_url) {
                        if (!filter_var($img_url, FILTER_VALIDATE_URL)) continue;
                        $tmp_file = download_url($img_url);
                        if (is_wp_error($tmp_file)) continue;
                        $file = array(
                            'name' => basename( parse_url($img_url, PHP_URL_PATH) ),
                            'tmp_name' => $tmp_file
                        );
                        $attach_id = media_handle_sideload($file, $new_id);
                        if (is_wp_error($attach_id)) { @unlink($file['tmp_name']); continue; }
                        $attach_ids[] = $attach_id;
                    }
                    if (!empty($attach_ids)) {
                        set_post_thumbnail($new_id, $attach_ids[0]);
                        if (count($attach_ids) > 1) update_post_meta($new_id, '_product_image_gallery', implode(',', $attach_ids));
                    }
                }

                // WCFM vendor assignment
                update_post_meta($new_id, '_wcfm_product_vendor', $current_user);
                update_post_meta($new_id, '_vendor_id', $current_user);

                // Meta fields from CSV
                foreach ($row_assoc as $key=>$val) {
                    if ($val === '') continue;
                    if (strpos($key, 'Meta:') === 0) {
                        $meta_key = trim(str_replace('Meta:', '', $key));
                        if ($meta_key !== '') update_post_meta($new_id, $meta_key, $val);
                    }
                }

                $created_ids[] = $new_id;
                $success++;
            }

        } catch (Exception $e) {
            $failed++;
            $errors[] = "Row ".($index+2).": ".$e->getMessage();
        }
    } // foreach row

    $message = sprintf(__('Imported/Updated: %d, Failed: %d', 'wcfm-bulk-product-import'), $success, $failed);
    if (!empty($errors)) {
        $message .= "\n\n" . __('Errors:', 'wcfm-bulk-product-import') . "\n" . implode("\n", $errors);
    }
    if (!empty($created_ids)) {
        $message .= "\n\n" . __('Created IDs:', 'wcfm-bulk-product-import') . ' ' . implode(',', $created_ids);
    }

    wp_send_json_success(['message' => $message, 'created' => $created_ids]);
});
