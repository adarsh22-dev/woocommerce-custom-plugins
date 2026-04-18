<?php
/*
Plugin Name: CISAI WCFM Vendor Tag Manager (POST Only)
Description: Vendor Tag Manager with Create, Edit, Delete – Name, Slug, Description. Fully AJAX & POST, 414 Safe
Version: 1.1
Author: CISAI
Text Domain: cisai-tag-manager
Domain Path: /languages
*/

if (!defined('ABSPATH')) exit;

/* ================= UI ================= */
add_action('after_wcfm_products_manage_general', function() {

    if(!is_user_logged_in()) return;

    $tag_taxonomy = 'product_tag';
    ?>
    <div class="page_collapsible products_manage_vendor_product_tag simple variable external grouped booking">
        <label class="wcfmfa fa-tag"></label> Vendor Tags
    </div>

    <div class="wcfm-container simple variable external grouped booking">
        <div class="wcfm-content">

            <h3><?php _e('🏷 Manage Tags', 'cisai-tag-manager'); ?></h3>

            <div id="cisai-tag-form" data-tax="<?php echo esc_attr($tag_taxonomy); ?>">

                <div class="cisai-grid">
                    <input type="text" class="cisai-tag-name" placeholder="<?php _e('Tag Name', 'cisai-tag-manager'); ?>">
                    <input type="text" class="cisai-tag-slug" placeholder="<?php _e('Slug', 'cisai-tag-manager'); ?>">
                    <textarea class="cisai-tag-desc" placeholder="<?php _e('Description', 'cisai-tag-manager'); ?>"></textarea>
                </div>

                <button type="button" class="cisai-save-tag"><?php _e('Create Tag', 'cisai-tag-manager'); ?></button>
                <button type="button" class="cisai-cancel-tag" style="display:none;"><?php _e('Cancel', 'cisai-tag-manager'); ?></button>
                <div class="cisai-tag-msg"></div>

            </div>

            <h4><?php _e('Existing Tags', 'cisai-tag-manager'); ?></h4>
            <div class="cisai-tag-list"></div>

        </div>
    </div>

    <style>
        .cisai-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        .cisai-grid textarea{grid-column:1/-1}
        .cisai-tag-row{display:flex;justify-content:space-between;padding:8px;border-bottom:1px solid #ddd}
        .cisai-loading { opacity: 0.6; }
    </style>

    <script>
    jQuery(function($){

        const ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';
        const nonce = '<?php echo wp_create_nonce("cisai_tag_nonce"); ?>';
        const box = $('#cisai-tag-form');
        const taxonomy = box.data('tax');

        /* ================= LOAD ================= */
        function loadTags(){
            $.post(ajaxurl,{
                action:'cisai_fetch_tags',
                nonce:nonce,
                taxonomy:taxonomy
            }, function(res){
                if(res.success){
                    let list=$('.cisai-tag-list');
                    list.html('');
                    res.data.forEach(t=>{
                        list.append(`
                        <div class="cisai-tag-row">
                            <span>${t.name}</span>
                            <div>
                                <button class="cisai-edit-tag" data-id="${t.id}">Edit</button>
                                <button class="cisai-delete-tag" data-id="${t.id}" style="color:red;margin-left:6px;">Delete</button>
                            </div>
                        </div>
                        `);
                    });
                } else {
                    console.error('Load error:', res.data);
                }
            }).fail(function() {
                console.error('AJAX load failed');
            });
        }
        loadTags();

        /* ================= SAVE ================= */
        $('.cisai-save-tag').on('click',function(){
            const btn = $(this);
            const msg = $('.cisai-tag-msg');
            const form = box.find('input,textarea');
            btn.prop('disabled', true).text('<?php _e('Saving...', 'cisai-tag-manager'); ?>').addClass('cisai-loading');
            box.addClass('cisai-loading');

            $.post(ajaxurl,{
                action:'cisai_save_tag',
                nonce:nonce,
                taxonomy:taxonomy,
                term_id:box.data('edit')||'',
                name:box.find('.cisai-tag-name').val(),
                slug:box.find('.cisai-tag-slug').val(),
                desc:box.find('.cisai-tag-desc').val()
            },function(res){
                if(res.success){
                    msg.text('<?php _e('Saved Successfully', 'cisai-tag-manager'); ?>').css('color','green');
                    form.val('');
                    box.data('edit','');
                    $('.cisai-cancel-tag').hide();
                    btn.text('<?php _e('Create Tag', 'cisai-tag-manager'); ?>');
                    loadTags();
                }else{
                    msg.text(res.data || '<?php _e('Save Failed', 'cisai-tag-manager'); ?>').css('color','red');
                }
                btn.prop('disabled', false).removeClass('cisai-loading');
                box.removeClass('cisai-loading');
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error('Save error:', textStatus, errorThrown);
                msg.text('<?php _e('Network Error', 'cisai-tag-manager'); ?>').css('color','red');
                btn.prop('disabled', false).removeClass('cisai-loading');
                box.removeClass('cisai-loading');
            });
        });

        /* ================= EDIT ================= */
        $(document).on('click','.cisai-edit-tag',function(){
            const btn = $(this);
            const id = btn.data('id');
            btn.prop('disabled', true).text('<?php _e('Loading...', 'cisai-tag-manager'); ?>');

            $.post(ajaxurl,{
                action:'cisai_get_tag',
                nonce:nonce,
                term_id:id,
                taxonomy:taxonomy
            },function(res){
                if(res.success){
                    let t=res.data;
                    box.find('.cisai-tag-name').val(t.name);
                    box.find('.cisai-tag-slug').val(t.slug);
                    box.find('.cisai-tag-desc').val(t.desc);

                    box.data('edit',t.id);
                    $('.cisai-save-tag').text('<?php _e('Update Tag', 'cisai-tag-manager'); ?>');
                    $('.cisai-cancel-tag').show();
                }else{
                    $('.cisai-tag-msg').text(res.data || '<?php _e('Load Failed', 'cisai-tag-manager'); ?>').css('color','red');
                }
                btn.prop('disabled', false).text('Edit');
            }).fail(function() {
                console.error('Edit load failed');
                btn.prop('disabled', false).text('Edit');
            });
        });

        /* ================= DELETE ================= */
        $(document).on('click','.cisai-delete-tag',function(){

            if(!confirm('<?php _e('Delete this tag permanently? This will unassign it from all products.', 'cisai-tag-manager'); ?>')) return;

            const btn = $(this);
            const id = btn.data('id');
            btn.prop('disabled', true).text('<?php _e('Deleting...', 'cisai-tag-manager'); ?>');

            $.post(ajaxurl,{
                action:'cisai_delete_tag',
                nonce:nonce,
                term_id:id,
                taxonomy:taxonomy
            },function(res){
                if(res.success){
                    $('.cisai-tag-msg').text('<?php _e('Tag Deleted', 'cisai-tag-manager'); ?>').css('color','green');
                    loadTags();
                }else{
                    $('.cisai-tag-msg').text(res.data || '<?php _e('Delete Failed', 'cisai-tag-manager'); ?>').css('color','red');
                }
                btn.prop('disabled', false).text('Delete');
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error('Delete error:', textStatus, errorThrown);
                $('.cisai-tag-msg').text('<?php _e('Network Error', 'cisai-tag-manager'); ?>').css('color','red');
                btn.prop('disabled', false).text('Delete');
            });
        });

        /* ================= CANCEL ================= */
        $('.cisai-cancel-tag').on('click',function(){
            box.find('input,textarea').val('');
            box.data('edit','');
            $(this).hide();
            $('.cisai-save-tag').text('<?php _e('Create Tag', 'cisai-tag-manager'); ?>');
        });

    });
    </script>

<?php
});

/* ================= AJAX HANDLERS ================= */

/* FETCH */
add_action('wp_ajax_cisai_fetch_tags', function(){
    if(!is_user_logged_in() || !wp_verify_nonce($_POST['nonce'],'cisai_tag_nonce') || !current_user_can('edit_products'))
        wp_send_json_error(__('Security failed', 'cisai-tag-manager'));

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
add_action('wp_ajax_cisai_get_tag', function(){
    if(!is_user_logged_in() || !wp_verify_nonce($_POST['nonce'],'cisai_tag_nonce') || !current_user_can('edit_products'))
        wp_send_json_error();

    $id = intval($_POST['term_id']);
    $tax = sanitize_text_field($_POST['taxonomy']);
    $term = get_term($id,$tax);

    if(is_wp_error($term)) wp_send_json_error(__('Term not found', 'cisai-tag-manager'));

    $uid = get_current_user_id();
    $owner = get_term_meta($id,'_vendor_id',true);
    if($owner != $uid) wp_send_json_error(__('Permission denied', 'cisai-tag-manager'));

    wp_send_json_success([
        'id'=>$term->term_id,
        'name'=>$term->name,
        'slug'=>$term->slug,
        'desc'=>$term->description
    ]);
});

/* SAVE */
add_action('wp_ajax_cisai_save_tag', function(){
    if(!is_user_logged_in() || !wp_verify_nonce($_POST['nonce'],'cisai_tag_nonce') || !current_user_can('edit_products'))
        wp_send_json_error(__('Security failed', 'cisai-tag-manager'));

    $uid = get_current_user_id();
    $tax = sanitize_text_field($_POST['taxonomy']);

    $name = sanitize_text_field($_POST['name']);
    if(empty($name)) wp_send_json_error(__('Tag name required', 'cisai-tag-manager'));

    $args = [
        'name'=>$name,
        'slug'=>sanitize_title($_POST['slug']),
        'description'=>wp_kses_post($_POST['desc']) // Allow limited HTML
    ];

    // Auto-generate slug if empty
    if(empty($args['slug'])) $args['slug'] = sanitize_title($args['name']);

    $term_id = intval($_POST['term_id']);

    try {
        if($term_id){
            // Check ownership for update
            $owner = get_term_meta($term_id,'_vendor_id',true);
            if($owner != $uid) wp_send_json_error(__('Permission denied', 'cisai-tag-manager'));
            $result = wp_update_term($term_id,$tax,$args);
            if(is_wp_error($result)) throw new Exception($result->get_error_message());
        }else{
            $result = wp_insert_term($args['name'],$tax,$args);
            if(is_wp_error($result)) throw new Exception($result->get_error_message());
            $term_id = $result['term_id'];
            update_term_meta($term_id,'_vendor_id',$uid);
        }

        wp_send_json_success();
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
});

/* DELETE */
add_action('wp_ajax_cisai_delete_tag', function(){
    if(!is_user_logged_in() || !wp_verify_nonce($_POST['nonce'],'cisai_tag_nonce') || !current_user_can('edit_products'))
        wp_send_json_error(__('Security failed', 'cisai-tag-manager'));

    $uid = get_current_user_id();
    $tax = sanitize_text_field($_POST['taxonomy']);
    $term_id = intval($_POST['term_id']);

    // Verify term exists
    $term = get_term($term_id, $tax);
    if(is_wp_error($term)) wp_send_json_error(__('Term not found', 'cisai-tag-manager'));

    $owner = get_term_meta($term_id,'_vendor_id',true);
    if($owner != $uid) wp_send_json_error(__('Permission denied', 'cisai-tag-manager'));

    $result = wp_delete_term($term_id,$tax);
    if(is_wp_error($result)) wp_send_json_error($result->get_error_message());

    wp_send_json_success();
});