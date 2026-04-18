<?php
/*
Plugin Name: CISAI WCFM Vendor Category Manager (Dynamic + Delete)
Description: Full Vendor Category Manager with Dynamic WooCommerce GST, Dynamic WCFM Commission, Edit, Update & Delete.
Version: 8.0
Author: CISAI
*/

if (!defined('ABSPATH')) exit;

/* ================= UI ================= */
add_action('after_wcfm_products_manage_general', function() {

if(!is_user_logged_in()) return;

/* ✅ Dynamic GST from Woo */
$tax_classes = WC_Tax::get_tax_classes();

/* ✅ Dynamic Commission from WCFM */
$wcfm_options = get_option('wcfm_marketplace_options', []);
$commission_types = $wcfm_options['commission_types'] ?? [
    'by_global_rule' => 'By Global Rule',
    'percent'        => 'Percent',
    'fixed'          => 'Fixed',
    'percent_fixed'  => 'Percent + Fixed',
];
?>

<div class="page_collapsible products_manage_vendor_product_cat simple variable external grouped booking">
<label class="wcfmfa fa-folder-open"></label> Vendor Categories
</div>

<div class="wcfm-container simple variable external grouped booking">
<div class="wcfm-content">

<h3>📂 Manage Categories</h3>

<div id="vendor-term-form-product_cat">

<div class="wcfm-form-grid">

<input type="text" class="term_name" placeholder="Category Name">
<input type="text" class="term_slug" placeholder="Slug">

<select class="term_parent">
<option value="">Parent Category</option>
<?php
$all_cats = get_terms(['taxonomy'=>'product_cat','hide_empty'=>false]);
foreach($all_cats as $c){
echo '<option value="'.$c->term_id.'">'.$c->name.'</option>';
}
?>
</select>

<input type="text" class="term_thumbnail" placeholder="Thumbnail ID">

<select class="term_display_type">
<option value="">Display Type</option>
<option value="products">Products</option>
<option value="subcategories">Subcategories</option>
<option value="both">Both</option>
</select>

<select class="term_gst_tax_class">
<option value="">GST / Tax Class</option>
<option value="standard">Standard</option>
<?php foreach($tax_classes as $tax){ ?>
<option value="<?php echo esc_attr($tax); ?>"><?php echo esc_html($tax); ?></option>
<?php } ?>
</select>

<select class="term_commission_mode">
<option value="">Commission Mode</option>
<?php foreach ($commission_types as $key => $label) { ?>
<option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
<?php } ?>
</select>

<textarea class="term_desc" placeholder="Description"></textarea>

</div>

<button type="button" class="createCategoryBtn">Create Category</button>
<button type="button" class="cancelEditBtn" style="display:none;">Cancel</button>

<div class="wcfmTermMsg"></div>

</div>

<h4>Existing Categories</h4>
<div class="wcfm-terms-list"></div>

</div>
</div>

<style>
.wcfm-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
textarea{grid-column:1/-1}
.wcfm-term-item{display:flex;justify-content:space-between;align-items:center;padding:8px;border-bottom:1px solid #ddd}
.wcfm-term-item button{padding:4px 10px}
</style>

<script>
jQuery(function($){

const ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';
const nonce   = '<?php echo wp_create_nonce("wcfm_vendor_cat"); ?>';
const box     = $('#vendor-term-form-product_cat');

/* ================= LOAD ================= */
function loadCats(){
$.post(ajaxurl,{action:'wcfm_fetch_vendor_categories',nonce:nonce},function(res){
if(res.success){
let list=$('.wcfm-terms-list');
list.html('');
res.data.forEach(t=>{
list.append(`
<div class="wcfm-term-item">
    <span>${t.name}</span>
    <div>
        <button type="button" class="editBtn" data-id="${t.id}">Edit</button>
        <button type="button" class="deleteBtn" data-id="${t.id}" style="margin-left:8px;color:red;">Delete</button>
    </div>
</div>
`);
});
}
});
}
loadCats();
setInterval(loadCats,4000);

/* ================= CREATE + UPDATE ================= */
$('.createCategoryBtn').on('click',function(){

let data={
action:'wcfm_create_vendor_category',
nonce:nonce,
term_id:box.data('edit')||'',
name:box.find('.term_name').val(),
slug:box.find('.term_slug').val(),
parent:box.find('.term_parent').val(),
desc:box.find('.term_desc').val(),
thumbnail:box.find('.term_thumbnail').val(),
gst:box.find('.term_gst_tax_class').val(),
display_type:box.find('.term_display_type').val(),
commission:box.find('.term_commission_mode').val()
};

$.post(ajaxurl,data,function(res){
if(res.success){
$('.wcfmTermMsg').text('Saved Successfully').css('color','green');
box.find('input,select,textarea').val('');
box.data('edit','');
$('.cancelEditBtn').hide();
$('.createCategoryBtn').text('Create Category');
loadCats();
}else{
$('.wcfmTermMsg').text(res.data).css('color','red');
}
});
});

/* ================= EDIT ================= */
$(document).on('click','.editBtn',function(){
let id=$(this).data('id');

$.post(ajaxurl,{
action:'wcfm_get_vendor_category',
nonce:nonce,
term_id:id
},function(res){
if(res.success){
let t=res.data;

box.find('.term_name').val(t.name);
box.find('.term_slug').val(t.slug);
box.find('.term_parent').val(t.parent);
box.find('.term_desc').val(t.desc);
box.find('.term_thumbnail').val(t.thumbnail);
box.find('.term_gst_tax_class').val(t.gst);
box.find('.term_display_type').val(t.display_type);
box.find('.term_commission_mode').val(t.commission);

box.data('edit',id);
$('.createCategoryBtn').text('Update Category');
$('.cancelEditBtn').show();
}
});
});

/* ================= DELETE ================= */
$(document).on('click','.deleteBtn',function(){

let id = $(this).data('id');

if(!confirm('Delete this category permanently?')) return;

$.post(ajaxurl,{
action:'wcfm_delete_vendor_category',
nonce:nonce,
term_id:id
},function(res){
if(res.success){
$('.wcfmTermMsg').text('Category Deleted').css('color','green');
loadCats();
}else{
$('.wcfmTermMsg').text(res.data).css('color','red');
}
});
});

/* ================= CANCEL ================= */
$('.cancelEditBtn').on('click',function(){
box.find('input,select,textarea').val('');
box.data('edit','');
$(this).hide();
$('.createCategoryBtn').text('Create Category');
});

});
</script>
<?php
});

/* ================= FETCH ================= */
add_action('wp_ajax_wcfm_fetch_vendor_categories',function(){

if(!is_user_logged_in()||!wp_verify_nonce($_POST['nonce'],'wcfm_vendor_cat'))
wp_send_json_error('Security failed');

$uid=get_current_user_id();

$terms=get_terms([
'taxonomy'=>'product_cat',
'hide_empty'=>false,
'meta_query'=>[['key'=>'_vendor_id','value'=>$uid]]
]);

$data=[];
foreach($terms as $t){
$data[]=['id'=>$t->term_id,'name'=>$t->name];
}
wp_send_json_success($data);
});

/* ================= GET SINGLE ================= */
add_action('wp_ajax_wcfm_get_vendor_category',function(){

if(!is_user_logged_in()||!wp_verify_nonce($_POST['nonce'],'wcfm_vendor_cat'))
wp_send_json_error();

$id=intval($_POST['term_id']);
$term=get_term($id,'product_cat');

wp_send_json_success([
'name'=>$term->name,
'slug'=>$term->slug,
'parent'=>$term->parent,
'desc'=>$term->description,
'thumbnail'=>get_term_meta($id,'thumbnail_id',true),
'display_type'=>get_term_meta($id,'display_type',true),
'gst'=>get_term_meta($id,'gst_tax_class',true),
'commission'=>get_term_meta($id,'wcfm_commission_mode',true)
]);
});

/* ================= CREATE + UPDATE ================= */
add_action('wp_ajax_wcfm_create_vendor_category',function(){

if(!is_user_logged_in()||!wp_verify_nonce($_POST['nonce'],'wcfm_vendor_cat'))
wp_send_json_error();

$uid=get_current_user_id();

$args=[
'name'=>sanitize_text_field($_POST['name']),
'slug'=>sanitize_title($_POST['slug']),
'parent'=>intval($_POST['parent']),
'description'=>sanitize_textarea_field($_POST['desc'])
];

$term_id=intval($_POST['term_id']);

if($term_id){
wp_update_term($term_id,'product_cat',$args);
}else{
$term=wp_insert_term($args['name'],'product_cat',$args);
$term_id=$term['term_id'];
update_term_meta($term_id,'_vendor_id',$uid);
}

update_term_meta($term_id,'thumbnail_id',sanitize_text_field($_POST['thumbnail']));
update_term_meta($term_id,'display_type',sanitize_text_field($_POST['display_type']));
update_term_meta($term_id,'gst_tax_class',sanitize_text_field($_POST['gst']));
update_term_meta($term_id,'wcfm_commission_mode',sanitize_text_field($_POST['commission']));

wp_send_json_success();
});

/* ================= DELETE ================= */
add_action('wp_ajax_wcfm_delete_vendor_category',function(){

if(!is_user_logged_in()||!wp_verify_nonce($_POST['nonce'],'wcfm_vendor_cat'))
wp_send_json_error('Security failed');

$uid=get_current_user_id();
$term_id=intval($_POST['term_id']);

$owner=get_term_meta($term_id,'_vendor_id',true);
if($owner!=$uid)
wp_send_json_error('Permission denied');

wp_delete_term($term_id,'product_cat');

wp_send_json_success();
});
