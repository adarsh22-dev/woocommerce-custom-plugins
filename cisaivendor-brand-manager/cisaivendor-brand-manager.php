<?php
/*
Plugin Name: CISAI WCFM Vendor Brand Manager (POST Only)
Description: Vendor Brand Manager with Create, Edit, Delete – Fully AJAX & POST, 414 Safe
Version: 3.2
Author: CISAI
*/

if (!defined('ABSPATH')) exit;

/* ================= UI ================= */
add_action('after_wcfm_products_manage_general', function() {

    if(!is_user_logged_in()) return;

    $brand_taxonomy = taxonomy_exists('product_brand') ? 'product_brand' : 'pwb-brand';
    ?>
    <div class="page_collapsible products_manage_vendor_product_brand simple variable external grouped booking">
        <label class="wcfmfa fa-tags"></label> Vendor Brands
    </div>

    <div class="wcfm-container simple variable external grouped booking">
        <div class="wcfm-content">

            <h3><?php _e('🏷 Manage Brands', 'cisai-brand-manager'); ?></h3>

            <div id="cisai-brand-form" data-tax="<?php echo esc_attr($brand_taxonomy); ?>">

                <div class="cisai-grid">
                    <input type="text" class="cisai-brand-name" placeholder="<?php _e('Brand Name', 'cisai-brand-manager'); ?>">
                    <input type="text" class="cisai-brand-slug" placeholder="<?php _e('Slug', 'cisai-brand-manager'); ?>">
                    <select class="cisai-brand-parent">
                        <option value=""><?php _e('Parent Brand', 'cisai-brand-manager'); ?></option>
                        <?php
                        $all_brands = get_terms(['taxonomy'=>$brand_taxonomy,'hide_empty'=>false]);
                        foreach($all_brands as $b){
                            echo '<option value="'.$b->term_id.'">'.$b->name.'</option>';
                        }
                        ?>
                    </select>
                    <input type="text" class="cisai-brand-thumb" placeholder="<?php _e('Thumbnail ID', 'cisai-brand-manager'); ?>">
                    <textarea class="cisai-brand-desc" placeholder="<?php _e('Description', 'cisai-brand-manager'); ?>"></textarea>
                </div>

                <button type="button" class="cisai-save-brand"><?php _e('Create Brand', 'cisai-brand-manager'); ?></button>
                <button type="button" class="cisai-cancel-brand" style="display:none;"><?php _e('Cancel', 'cisai-brand-manager'); ?></button>
                <div class="cisai-brand-msg"></div>

            </div>

            <h4><?php _e('Existing Brands', 'cisai-brand-manager'); ?></h4>
            <div class="cisai-brand-list"></div>

        </div>
    </div>

    <style>
        .cisai-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        .cisai-grid textarea{grid-column:1/-1}
        .cisai-brand-row{display:flex;justify-content:space-between;padding:8px;border-bottom:1px solid #ddd}
        .cisai-loading { opacity: 0.6; }
    </style>

    <script>
    jQuery(function($){

        const ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';
        const nonce = '<?php echo wp_create_nonce("cisai_brand_nonce"); ?>';
        const box = $('#cisai-brand-form');
        const taxonomy = box.data('tax');

        /* ================= LOAD ================= */
        function loadBrands(){
            $.post(ajaxurl,{
                action:'cisai_fetch_brands',
                nonce:nonce,
                taxonomy:taxonomy
            }, function(res){
                if(res.success){
                    let list=$('.cisai-brand-list');
                    list.html('');
                    res.data.forEach(t=>{
                        list.append(`
                        <div class="cisai-brand-row">
                            <span>${t.name}</span>
                            <div>
                                <button class="cisai-edit-brand" data-id="${t.id}">Edit</button>
                                <button class="cisai-delete-brand" data-id="${t.id}" style="color:red;margin-left:6px;">Delete</button>
                            </div>
                        </div>
                        `);
                    });
                }
            });
        }
        loadBrands();

        /* ================= SAVE ================= */
        $('.cisai-save-brand').on('click',function(){
            const btn = $(this);
            const msg = $('.cisai-brand-msg');
            const form = box.find('input,select,textarea');
            btn.prop('disabled', true).text('<?php _e('Saving...', 'cisai-brand-manager'); ?>').addClass('cisai-loading');
            box.addClass('cisai-loading');

            $.post(ajaxurl,{
                action:'cisai_save_brand',
                nonce:nonce,
                taxonomy:taxonomy,
                term_id:box.data('edit')||'',
                name:box.find('.cisai-brand-name').val(),
                slug:box.find('.cisai-brand-slug').val(),
                parent:box.find('.cisai-brand-parent').val(),
                desc:box.find('.cisai-brand-desc').val(),
                thumbnail:box.find('.cisai-brand-thumb').val()
            },function(res){
                if(res.success){
                    msg.text('<?php _e('Saved Successfully', 'cisai-brand-manager'); ?>').css('color','green');
                    form.val('');
                    box.data('edit','');
                    $('.cisai-cancel-brand').hide();
                    btn.text('<?php _e('Create Brand', 'cisai-brand-manager'); ?>');
                    loadBrands();
                }else{
                    msg.text(res.data || '<?php _e('Save Failed', 'cisai-brand-manager'); ?>').css('color','red');
                }
                btn.prop('disabled', false).removeClass('cisai-loading');
                box.removeClass('cisai-loading');
            });
        });

        /* ================= EDIT ================= */
        $(document).on('click','.cisai-edit-brand',function(){
            const btn = $(this);
            const id = btn.data('id');
            btn.prop('disabled', true).text('<?php _e('Loading...', 'cisai-brand-manager'); ?>');

            $.post(ajaxurl,{
                action:'cisai_get_brand',
                nonce:nonce,
                term_id:id,
                taxonomy:taxonomy
            },function(res){
                if(res.success){
                    let t=res.data;
                    box.find('.cisai-brand-name').val(t.name);
                    box.find('.cisai-brand-slug').val(t.slug);
                    box.find('.cisai-brand-parent').val(t.parent);
                    box.find('.cisai-brand-desc').val(t.desc);
                    box.find('.cisai-brand-thumb').val(t.thumbnail);

                    // Disable self as parent
                    box.find('.cisai-brand-parent option').prop('disabled', false);
                    if(t.id) box.find(`.cisai-brand-parent option[value="${t.id}"]`).prop('disabled', true);

                    box.data('edit',t.id);
                    $('.cisai-save-brand').text('<?php _e('Update Brand', 'cisai-brand-manager'); ?>');
                    $('.cisai-cancel-brand').show();
                }else{
                    $('.cisai-brand-msg').text(res.data || '<?php _e('Load Failed', 'cisai-brand-manager'); ?>').css('color','red');
                }
                btn.prop('disabled', false).text('Edit');
            });
        });

        /* ================= DELETE ================= */
        $(document).on('click','.cisai-delete-brand',function(){

            if(!confirm('<?php _e('Delete this brand permanently?', 'cisai-brand-manager'); ?>')) return;

            const btn = $(this);
            const id = btn.data('id');
            btn.prop('disabled', true).text('<?php _e('Deleting...', 'cisai-brand-manager'); ?>');

            $.post(ajaxurl,{
                action:'cisai_delete_brand',
                nonce:nonce,
                term_id:id,
                taxonomy:taxonomy
            },function(res){
                if(res.success){
                    $('.cisai-brand-msg').text('<?php _e('Brand Deleted', 'cisai-brand-manager'); ?>').css('color','green');
                    loadBrands();
                }else{
                    $('.cisai-brand-msg').text(res.data || '<?php _e('Delete Failed', 'cisai-brand-manager'); ?>').css('color','red');
                }
                btn.prop('disabled', false).text('Delete');
            });
        });

        /* ================= CANCEL ================= */
        $('.cisai-cancel-brand').on('click',function(){
            box.find('input,select,textarea').val('');
            box.data('edit','');
            $(this).hide();
            $('.cisai-save-brand').text('<?php _e('Create Brand', 'cisai-brand-manager'); ?>');
            // Re-enable all parent options
            box.find('.cisai-brand-parent option').prop('disabled', false);
        });

    });
    </script>

<?php
});

/* ================= AJAX HANDLERS ================= */

/* FETCH */
add_action('wp_ajax_cisai_fetch_brands', function(){
    if(!is_user_logged_in() || !wp_verify_nonce($_POST['nonce'],'cisai_brand_nonce') || !current_user_can('manage_woocommerce'))
        wp_send_json_error(__('Security failed', 'cisai-brand-manager'));

    $uid = get_current_user_id();
    $tax = sanitize_text_field($_POST['taxonomy']);

    $terms = get_terms([
        'taxonomy' => $tax,
        'hide_empty' => false,
        'meta_query' => [['key'=>'_vendor_id','value'=>$uid]],
        'number' => 50 // Limit for performance; add pagination if needed
    ]);

    $data = [];
    foreach($terms as $t){
        $data[] = ['id'=>$t->term_id,'name'=>$t->name];
    }
    wp_send_json_success($data);
});

/* GET */
add_action('wp_ajax_cisai_get_brand', function(){
    if(!is_user_logged_in() || !wp_verify_nonce($_POST['nonce'],'cisai_brand_nonce') || !current_user_can('manage_woocommerce'))
        wp_send_json_error();

    $id = intval($_POST['term_id']);
    $tax = sanitize_text_field($_POST['taxonomy']);
    $term = get_term($id,$tax);

    if(is_wp_error($term)) wp_send_json_error(__('Term not found', 'cisai-brand-manager'));

    $uid = get_current_user_id();
    $owner = get_term_meta($id,'_vendor_id',true);
    if($owner != $uid) wp_send_json_error(__('Permission denied', 'cisai-brand-manager'));

    wp_send_json_success([
        'id'=>$term->term_id,
        'name'=>$term->name,
        'slug'=>$term->slug,
        'parent'=>$term->parent,
        'desc'=>$term->description,
        'thumbnail'=>get_term_meta($id,'thumbnail_id',true)
    ]);
});

/* SAVE */
add_action('wp_ajax_cisai_save_brand', function(){
    if(!is_user_logged_in() || !wp_verify_nonce($_POST['nonce'],'cisai_brand_nonce') || !current_user_can('manage_woocommerce'))
        wp_send_json_error(__('Security failed', 'cisai-brand-manager'));

    $uid = get_current_user_id();
    $tax = sanitize_text_field($_POST['taxonomy']);

    $name = sanitize_text_field($_POST['name']);
    if(empty($name)) wp_send_json_error(__('Brand name required', 'cisai-brand-manager'));

    $args = [
        'name'=>$name,
        'slug'=>sanitize_title($_POST['slug']),
        'parent'=>intval($_POST['parent']),
        'description'=>wp_kses_post($_POST['desc']) // Allow limited HTML
    ];

    // Auto-generate slug if empty
    if(empty($args['slug'])) $args['slug'] = sanitize_title($args['name']);

    $term_id = intval($_POST['term_id']);

    try {
        if($term_id){
            // Check ownership for update
            $owner = get_term_meta($term_id,'_vendor_id',true);
            if($owner != $uid) wp_send_json_error(__('Permission denied', 'cisai-brand-manager'));
            $result = wp_update_term($term_id,$tax,$args);
            if(is_wp_error($result)) throw new Exception($result->get_error_message());
        }else{
            $result = wp_insert_term($args['name'],$tax,$args);
            if(is_wp_error($result)) throw new Exception($result->get_error_message());
            $term_id = $result['term_id'];
            update_term_meta($term_id,'_vendor_id',$uid);
        }

        // Validate and set thumbnail
        $thumb_id = intval($_POST['thumbnail']);
        if($thumb_id && !get_post($thumb_id)) {
            wp_send_json_error(__('Invalid thumbnail ID', 'cisai-brand-manager'));
        }
        update_term_meta($term_id,'thumbnail_id',$thumb_id);

        wp_send_json_success();
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
});

/* DELETE */
add_action('wp_ajax_cisai_delete_brand', function(){
    if(!is_user_logged_in() || !wp_verify_nonce($_POST['nonce'],'cisai_brand_nonce') || !current_user_can('manage_woocommerce'))
        wp_send_json_error(__('Security failed', 'cisai-brand-manager'));

    $uid = get_current_user_id();
    $tax = sanitize_text_field($_POST['taxonomy']);
    $term_id = intval($_POST['term_id']);

    $owner = get_term_meta($term_id,'_vendor_id',true);
    if($owner != $uid) wp_send_json_error(__('Permission denied', 'cisai-brand-manager'));

    $result = wp_delete_term($term_id,$tax);
    if(is_wp_error($result)) wp_send_json_error($result->get_error_message());

    wp_send_json_success();
});