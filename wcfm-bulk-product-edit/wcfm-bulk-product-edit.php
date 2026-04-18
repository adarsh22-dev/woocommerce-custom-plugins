<?php
/**
 * Plugin Name: CISAI WCFM Bulk Edit Inline Products
 * Description: Bulk edit WooCommerce products inline in WCFM. Supports Name, SKU, Price, Sale Price, Stock, Categories, Tags, Short & Long Description.
 * Version: 1.1
 * Author: Adarsh Singh
 */

if(!defined('ABSPATH')) exit;

/* -------------------------
 * Add Bulk Edit Button
 * ------------------------- */
add_action('wcfm_products_quick_actions', function(){
    if(!(current_user_can('administrator') || (function_exists('wcfm_is_vendor') && wcfm_is_vendor()))) return;
    ?>
    <a href="#" id="wcfm_bulk_inline_btn" class="button" style="margin-left:8px;background:#1c2b36;color:#fff;padding: 8px 12px;font-size:15px;font-weight:300;">
        <i class="fa fa-edit"></i> Bulk Edit
    </a>
    <?php
});

/* -------------------------
 * Modal HTML
 * ------------------------- */
add_action('wp_footer', function(){
    if(!(current_user_can('administrator') || (function_exists('wcfm_is_vendor') && wcfm_is_vendor()))) return;
    ?>
    <div id="wcfm_bulk_inline_modal" style="display:none;position:fixed;left:0;top:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:99999;overflow:auto;">
        <div style="background:#fff;padding:20px;margin:20px auto;border-radius:8px;max-width:95%;min-width:700px;box-shadow:0 8px 20px rgba(0,0,0,.4);">
            <button id="wcfm_bulk_inline_close" style="float:right;font-size:28px;background:#0b1115;border:none;cursor:pointer;padding: 0px 8px;border-radius: 30px;">&times;</button>
            <h2>Bulk Edit Products</h2>
            <div id="wcfm_bulk_inline_table_wrapper">
                <p>Loading products...</p>
            </div>
            <button id="wcfm_bulk_inline_save" class="button button-primary" style="margin-top:15px;">Save Changes</button>
            <div id="wcfm_bulk_inline_status" style="margin-top:10px;font-weight:600;"></div>
        </div>
    </div>

    <script>
    (function($){
        $(document).ready(function(){
            $('#wcfm_bulk_inline_btn').on('click', function(e){
                e.preventDefault();
                $('#wcfm_bulk_inline_modal').fadeIn(150);
                $('#wcfm_bulk_inline_status').text('');
                loadProducts();
            });

            $('#wcfm_bulk_inline_close').on('click', function(){ $('#wcfm_bulk_inline_modal').fadeOut(150); });

            function loadProducts(){
                $('#wcfm_bulk_inline_table_wrapper').html('Loading...');
                $.ajax({
                    url:'<?php echo admin_url("admin-ajax.php"); ?>',
                    method:'POST',
                    data:{ action:'wcfm_bulk_inline_load' },
                    success:function(resp){
                        $('#wcfm_bulk_inline_table_wrapper').html(resp);
                    }
                });
            }

            $('#wcfm_bulk_inline_save').on('click', function(){
                var data = [];
                $('#wcfm_bulk_table tr').each(function(){
                    var row = $(this);
                    var id = row.data('id');
                    if(!id) return;
                    data.push({
                        id: id,
                        name: row.find('.bulk_name').val(),
                        price: row.find('.bulk_price').val(),
                        sale_price: row.find('.bulk_sale_price').val(), // Sale Price
                        sku: row.find('.bulk_sku').val(),
                        stock: row.find('.bulk_stock').val(),
                        short_desc: row.find('.bulk_short_desc').val(),
                        desc: row.find('.bulk_desc').val(),
                        categories: row.find('.bulk_categories').val(),
                        tags: row.find('.bulk_tags').val()
                    });
                });

                $('#wcfm_bulk_inline_status').text('Saving...');
                $.ajax({
                    url:'<?php echo admin_url("admin-ajax.php"); ?>',
                    method:'POST',
                    data:{ action:'wcfm_bulk_inline_save', products:data },
                    success:function(resp){
                        $('#wcfm_bulk_inline_status').text(resp.data.message);
                        loadProducts();
                    }
                });
            });
        });
    })(jQuery);
    </script>
    <?php
});

/* -------------------------
 * Load Products AJAX
 * ------------------------- */
add_action('wp_ajax_wcfm_bulk_inline_load', function(){
    $user_id = get_current_user_id();
    $args = [
        'post_type'=>'product',
        'posts_per_page'=>200,
        'post_status'=>'publish',
    ];
    if(function_exists('wcfm_is_vendor') && wcfm_is_vendor()){
        $args['author'] = $user_id;
    }
    $products = get_posts($args);
    if(!$products){
        echo '<p>No products found.</p>';
        wp_die();
    }

    echo '<table id="wcfm_bulk_table" border="1" cellpadding="5" cellspacing="0" style="width:100%;border-collapse:collapse;">';
    echo '<tr><th>ID</th><th>Name</th><th>Regular Price</th><th>Sale Price</th><th>SKU</th><th>Stock</th><th>Short Description</th><th>Description</th><th>Categories</th><th>Tags</th></tr>';
    foreach($products as $p){
        $prod = wc_get_product($p->ID);
        $cats = wp_get_post_terms($p->ID,'product_cat',array('fields'=>'names'));
        $tags = wp_get_post_terms($p->ID,'product_tag',array('fields'=>'names'));
        echo '<tr data-id="'.$p->ID.'">';
        echo '<td>'.$p->ID.'</td>';
        echo '<td><input type="text" class="bulk_name" value="'.esc_attr($prod->get_name()).'"></td>';
        echo '<td><input type="text" class="bulk_price" value="'.esc_attr($prod->get_regular_price()).'"></td>';
        echo '<td><input type="text" class="bulk_sale_price" value="'.esc_attr($prod->get_sale_price()).'"></td>'; // Sale Price
        echo '<td><input type="text" class="bulk_sku" value="'.esc_attr($prod->get_sku()).'"></td>';
        echo '<td><input type="text" class="bulk_stock" value="'.esc_attr($prod->get_stock_quantity()).'"></td>';
        echo '<td><textarea class="bulk_short_desc">'.esc_textarea($prod->get_short_description()).'</textarea></td>';
        echo '<td><textarea class="bulk_desc">'.esc_textarea($prod->get_description()).'</textarea></td>';
        echo '<td><input type="text" class="bulk_categories" value="'.esc_attr(implode(', ',$cats)).'"></td>';
        echo '<td><input type="text" class="bulk_tags" value="'.esc_attr(implode(', ',$tags)).'"></td>';
        echo '</tr>';
    }
    echo '</table>';
    wp_die();
});

/* -------------------------
 * Save Products AJAX
 * ------------------------- */
add_action('wp_ajax_wcfm_bulk_inline_save', function(){
    $products = $_POST['products'] ?? [];
    $success=0; $failed=0; $errors=[];
    foreach($products as $p){
        $id = intval($p['id'] ?? 0);
        if(!$id) continue;
        $product = wc_get_product($id);
        if(!$product){ $failed++; continue; }

        try{
            if(isset($p['name'])) $product->set_name(sanitize_text_field($p['name']));
            if(isset($p['price'])) $product->set_regular_price(sanitize_text_field($p['price']));
            if(isset($p['sale_price'])) $product->set_sale_price(sanitize_text_field($p['sale_price'])); // Sale Price
            if(isset($p['sku'])) $product->set_sku(sanitize_text_field($p['sku']));
            if(isset($p['stock'])){ $product->set_manage_stock(true); $product->set_stock_quantity(intval($p['stock'])); }
            if(isset($p['short_desc'])) $product->set_short_description(sanitize_textarea_field($p['short_desc']));
            if(isset($p['desc'])) $product->set_description(sanitize_textarea_field($p['desc']));

            if(isset($p['categories'])){
                $cats = array_map('trim', explode(',', $p['categories']));
                $cat_ids=[];
                foreach($cats as $c){
                    $term = get_term_by('name',$c,'product_cat');
                    if($term) $cat_ids[]=$term->term_id;
                }
                if($cat_ids) $product->set_category_ids($cat_ids);
            }

            if(isset($p['tags'])){
                $tags = array_map('trim',explode(',',$p['tags']));
                wp_set_post_terms($id,$tags,'product_tag');
            }

            // WCFM vendor metas
            $vendor_id = get_current_user_id();
            update_post_meta($id,'_wcfm_product_vendor',$vendor_id);
            update_post_meta($id,'_vendor_id',$vendor_id);

            $product->save();
            $success++;
        }catch(Exception $e){
            $failed++;
            $errors[]="Product ID $id: ".$e->getMessage();
        }
    }

    $message = "Updated: $success, Failed: $failed";
    if($errors) $message .= "\n".implode("\n",$errors);

    wp_send_json_success(['message'=>$message]);
});
