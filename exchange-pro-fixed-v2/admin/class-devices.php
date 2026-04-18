<?php
if (!defined('ABSPATH')) exit;

class Exchange_Pro_Devices {
    private static $instance = null;
    private $db;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db = Exchange_Pro_Database::get_instance();
        
        // AJAX handlers
        add_action('wp_ajax_exchange_pro_add_brand', array($this, 'ajax_add_brand'));
        add_action('wp_ajax_exchange_pro_add_model', array($this, 'ajax_add_model'));
        add_action('wp_ajax_exchange_pro_add_variant', array($this, 'ajax_add_variant'));
        add_action('wp_ajax_exchange_pro_set_pricing', array($this, 'ajax_set_pricing'));
        add_action('wp_ajax_exchange_pro_get_category_data', array($this, 'ajax_get_category_data'));

        // Delete handlers
        add_action('wp_ajax_exchange_pro_delete_brand', array($this, 'ajax_delete_brand'));
        add_action('wp_ajax_exchange_pro_delete_model', array($this, 'ajax_delete_model'));
        add_action('wp_ajax_exchange_pro_delete_variant', array($this, 'ajax_delete_variant'));

        // Update + restore handlers (Phase 2)
        add_action('wp_ajax_exchange_pro_update_brand', array($this, 'ajax_update_brand'));
        add_action('wp_ajax_exchange_pro_update_model', array($this, 'ajax_update_model'));
        add_action('wp_ajax_exchange_pro_update_variant', array($this, 'ajax_update_variant'));

        add_action('wp_ajax_exchange_pro_restore_brand', array($this, 'ajax_restore_brand'));
        add_action('wp_ajax_exchange_pro_restore_model', array($this, 'ajax_restore_model'));
        add_action('wp_ajax_exchange_pro_restore_variant', array($this, 'ajax_restore_variant'));
    }
    
    public function render() {
        $categories = $this->db->get_categories('');
        ?>
        <div class="wrap exchange-pro-admin">
            <h1><?php _e('Devices & Pricing Management', 'exchange-pro'); ?></h1>
            
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header" style="background: #0073aa; color: white;">
                            <h5 class="mb-0"><?php _e('Categories', 'exchange-pro'); ?></h5>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach ($categories as $cat): ?>
                                <a href="#" class="list-group-item list-group-item-action category-selector" 
                                   data-category-id="<?php echo $cat->id; ?>"
                                   data-category-name="<?php echo esc_attr($cat->name); ?>">
                                    <i class="fas fa-<?php echo esc_attr($cat->icon); ?>"></i> 
                                    <?php echo esc_html($cat->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div id="category-content">
                        <div class="card">
                            <div class="card-body text-center p-5">
                                <i class="fas fa-arrow-left" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                                <h4><?php _e('Select a category to manage devices', 'exchange-pro'); ?></h4>
                                <p class="text-muted"><?php _e('Click on a category from the left panel', 'exchange-pro'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Add Brand Modal -->
        <div class="modal fade" id="addBrandModal" tabindex="-1" style="background: #00000000 !important;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php _e('Add Brand', 'exchange-pro'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding: 5% !important;">
                        <input type="hidden" id="brand-category-id">
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Brand Name', 'exchange-pro'); ?></label>
                            <input type="text" class="form-control" id="brand-name" placeholder="e.g., Apple">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel', 'exchange-pro'); ?></button>
                        <button type="button" class="btn btn-primary" id="save-brand-btn"><?php _e('Save Brand', 'exchange-pro'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Brand Modal (Phase 2) -->
        <div class="modal fade" id="editBrandModal" tabindex="-1" style="background: #00000000 !important;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php _e('Edit Brand', 'exchange-pro'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding: 5% !important;">
                        <input type="hidden" id="edit-brand-id">
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Brand Name', 'exchange-pro'); ?></label>
                            <input type="text" class="form-control" id="edit-brand-name" placeholder="e.g., Apple">
                            <div class="form-text"><?php _e('Inactive brands remain visible in admin only and are hidden from customers.', 'exchange-pro'); ?></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel', 'exchange-pro'); ?></button>
                        <button type="button" class="btn btn-primary" id="update-brand-btn"><?php _e('Update Brand', 'exchange-pro'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Add Model Modal -->
        <div class="modal fade" id="addModelModal" tabindex="-1" style="background: #00000000 !important;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php _e('Add Model', 'exchange-pro'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding: 5% !important;">
                        <input type="hidden" id="model-brand-id">
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Model Name', 'exchange-pro'); ?></label>
                            <input type="text" class="form-control" id="model-name" placeholder="e.g., iPhone 13">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel', 'exchange-pro'); ?></button>
                        <button type="button" class="btn btn-primary" id="save-model-btn"><?php _e('Save Model', 'exchange-pro'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Model Modal (Phase 2) -->
        <div class="modal fade" id="editModelModal" tabindex="-1" style="background: #00000000 !important;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php _e('Edit Model', 'exchange-pro'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding: 5% !important;">
                        <input type="hidden" id="edit-model-id">
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Model Name', 'exchange-pro'); ?></label>
                            <input type="text" class="form-control" id="edit-model-name" placeholder="e.g., iPhone 13">
                            <div class="form-text"><?php _e('Inactive models are hidden from customers.', 'exchange-pro'); ?></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel', 'exchange-pro'); ?></button>
                        <button type="button" class="btn btn-primary" id="update-model-btn"><?php _e('Update Model', 'exchange-pro'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Add Variant Modal -->
        <div class="modal fade" id="addVariantModal" tabindex="-1" style="background: #00000000 !important;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php _e('Add Variant', 'exchange-pro'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding: 5% !important;">
                        <input type="hidden" id="variant-model-id">
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Variant Name', 'exchange-pro'); ?></label>
                            <input type="text" class="form-control" id="variant-name" placeholder="e.g., 128GB">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel', 'exchange-pro'); ?></button>
                        <button type="button" class="btn btn-primary" id="save-variant-btn"><?php _e('Save Variant', 'exchange-pro'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Variant Modal (Phase 2) -->
        <div class="modal fade" id="editVariantModal" tabindex="-1" style="background: #00000000 !important;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php _e('Edit Variant', 'exchange-pro'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding: 5% !important;">
                        <input type="hidden" id="edit-variant-id">
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Variant Name', 'exchange-pro'); ?></label>
                            <input type="text" class="form-control" id="edit-variant-name" placeholder="e.g., 128GB">
                            <div class="form-text"><?php _e('Prices are edited via Set Price.', 'exchange-pro'); ?></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel', 'exchange-pro'); ?></button>
                        <button type="button" class="btn btn-primary" id="update-variant-btn"><?php _e('Update Variant', 'exchange-pro'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Set Pricing Modal -->
        <div class="modal fade" id="setPricingModal" tabindex="-1" style="background: #00000000 !important;">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php _e('Set Pricing', 'exchange-pro'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding: 5% !important;">
                        <input type="hidden" id="pricing-variant-id">
                        <p><strong id="pricing-variant-name"></strong></p>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php _e('Excellent Condition', 'exchange-pro'); ?></label>
                                <input type="number" class="form-control" id="price-excellent" placeholder="0" step="1">
                                <small class="text-muted"><?php _e('No scratches / Brand new', 'exchange-pro'); ?></small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php _e('Good Condition', 'exchange-pro'); ?></label>
                                <input type="number" class="form-control" id="price-good" placeholder="0" step="1">
                                <small class="text-muted"><?php _e('Minor wear / Functional', 'exchange-pro'); ?></small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php _e('Fair Condition', 'exchange-pro'); ?></label>
                                <input type="number" class="form-control" id="price-fair" placeholder="0" step="1">
                                <small class="text-muted"><?php _e('Display/Body issues', 'exchange-pro'); ?></small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php _e('Poor Condition', 'exchange-pro'); ?></label>
                                <input type="number" class="form-control" id="price-poor" placeholder="0" step="1">
                                <small class="text-muted"><?php _e('Major issues', 'exchange-pro'); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel', 'exchange-pro'); ?></button>
                        <button type="button" class="btn btn-primary" id="save-pricing-btn"><?php _e('Save Pricing', 'exchange-pro'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            let selectedCategoryId = null;
            let selectedCategoryName = '';
            
            // Category selection
            $('.category-selector').on('click', function(e) {
                e.preventDefault();
                $('.category-selector').removeClass('active');
                $(this).addClass('active');
                
                selectedCategoryId = $(this).data('category-id');
                selectedCategoryName = $(this).data('category-name');
                
                loadCategoryData(selectedCategoryId);
            });
            
            // Load category data
            function loadCategoryData(categoryId) {
                $('#category-content').html('<div class="text-center p-5"><div class="spinner-border"></div></div>');
                
                $.post(ajaxurl, {
                    action: 'exchange_pro_get_category_data',
                    nonce: exchangeProAdmin.nonce,
                    category_id: categoryId
                }, function(response) {
                    if (response.success) {
                        renderCategoryContent(response.data);
                    }
                });
            }
            
            // Render category content
            function renderCategoryContent(data) {
                let html = '<div class="card"><div class="card-header d-flex justify-content-between align-items-center">';
                html += '<h5 class="mb-0">' + selectedCategoryName + '</h5>';
                html += '<button class="btn btn-primary btn-sm" id="add-brand-btn"><i class="fas fa-plus"></i> Add Brand</button>';
                html += '</div><div class="card-body">';
                
                if (data.brands.length === 0) {
                    html += '<p class="text-muted">No brands added yet. Click "Add Brand" to get started.</p>';
                } else {
                    $.each(data.brands, function(i, brand) {
                        html += '<div class="brand-section mb-4">';
                        html += '<div class="d-flex justify-content-between align-items-center mb-2">';
	                        var bBadge = (brand.status && brand.status !== 'active') ? ' <span class="badge bg-secondary ms-2">Inactive</span>' : '';
	                        html += '<h6 class="mb-0"><strong>' + brand.name + '</strong>' + bBadge + '</h6>';
                        html += '<div class="d-flex gap-2">';
                        html += '<button class="btn btn-sm btn-primary add-model-btn" data-brand-id="' + brand.id + '">Add Model</button>';
	                        html += '<button class="btn btn-sm btn-outline-secondary edit-brand-btn" data-brand-id="' + brand.id + '" data-brand-name="' + brand.name + '">Edit</button>';
	                        if (brand.status && brand.status !== 'active') {
	                            html += '<button class="btn btn-sm btn-success restore-brand-btn" data-brand-id="' + brand.id + '">Restore</button>';
	                        } else {
	                            html += '<button class="btn btn-sm btn-outline-danger delete-brand-btn" data-brand-id="' + brand.id + '" title="Deactivate brand"><i class="fas fa-trash"></i></button>';
	                        }
                        html += '</div>';
                        html += '</div>';
                        
                        if (brand.models.length === 0) {
                            html += '<p class="text-muted ms-3">No models added</p>';
                        } else {
                            html += '<div class="ms-3">';
                            $.each(brand.models, function(j, model) {
                                html += '<div class="model-section mb-3 p-3" style="background: #f8f9fa; border-radius: 8px;">';
                                html += '<div class="d-flex justify-content-between align-items-center mb-2">';
	                                var mBadge = (model.status && model.status !== 'active') ? ' <span class="badge bg-secondary ms-2">Inactive</span>' : '';
	                                html += '<strong>' + model.name + '</strong>' + mBadge;
                                html += '<div class="d-flex gap-2">';
                                html += '<button class="btn btn-sm btn-primary add-variant-btn" data-model-id="' + model.id + '">Add Variant</button>';
	                                html += '<button class="btn btn-sm btn-outline-secondary edit-model-btn" data-model-id="' + model.id + '" data-model-name="' + model.name + '">Edit</button>';
	                                if (model.status && model.status !== 'active') {
	                                    html += '<button class="btn btn-sm btn-success restore-model-btn" data-model-id="' + model.id + '">Restore</button>';
	                                } else {
	                                    html += '<button class="btn btn-sm btn-outline-danger delete-model-btn" data-model-id="' + model.id + '" title="Deactivate model"><i class="fas fa-trash"></i></button>';
	                                }
                                html += '</div>';
                                html += '</div>';
                                
                                if (model.variants.length === 0) {
                                    html += '<p class="text-muted ms-3">No variants added</p>';
                                } else {
                                    html += '<div class="ms-3"><table class="table table-sm"><thead><tr><th>Variant</th><th>Excellent</th><th>Good</th><th>Fair</th><th>Poor</th><th>Action</th></tr></thead><tbody>';
                                    $.each(model.variants, function(k, variant) {
                                        html += '<tr>';
	                                        var vBadge = (variant.status && variant.status !== 'active') ? ' <span class="badge bg-secondary ms-2">Inactive</span>' : '';
	                                        html += '<td>' + variant.name + vBadge + '</td>';
                                        html += '<td>' + (variant.pricing.excellent || '-') + '</td>';
                                        html += '<td>' + (variant.pricing.good || '-') + '</td>';
                                        html += '<td>' + (variant.pricing.fair || '-') + '</td>';
                                        html += '<td>' + (variant.pricing.poor || '-') + '</td>';
	                                        html += '<td class="d-flex gap-2">';
	                                        html += '<button class="btn btn-sm btn-warning set-pricing-btn" data-variant-id="' + variant.id + '" data-variant-name="' + brand.name + ' ' + model.name + ' ' + variant.name + '">Set Price</button>';
	                                        html += '<button class="btn btn-sm btn-outline-secondary edit-variant-btn" data-variant-id="' + variant.id + '" data-variant-name="' + variant.name + '">Edit</button>';
	                                        if (variant.status && variant.status !== 'active') {
	                                            html += '<button class="btn btn-sm btn-success restore-variant-btn" data-variant-id="' + variant.id + '">Restore</button>';
	                                        } else {
	                                            html += '<button class="btn btn-sm btn-outline-danger delete-variant-btn" data-variant-id="' + variant.id + '" title="Deactivate variant"><i class="fas fa-trash"></i></button>';
	                                        }
                                        html += '</td>';
                                        html += '</tr>';
                                    });
                                    html += '</tbody></table></div>';
                                }
                                
                                html += '</div>';
                            });
                            html += '</div>';
                        }
                        
                        html += '</div><hr>';
                    });
                }
                
                html += '</div></div>';
                $('#category-content').html(html);
            }
            
            // Add brand
            $(document).on('click', '#add-brand-btn', function() {
                $('#brand-category-id').val(selectedCategoryId);
                $('#brand-name').val('');
                $('#addBrandModal').modal('show');
            });

            // Delete brand/model/variant
            function doDelete(action, idKey, idVal) {
                if (!confirm(exchangeProAdmin.strings.confirm_delete || 'Are you sure?')) return;
                $.post(ajaxurl, {
                    action: action,
                    nonce: exchangeProAdmin.nonce,
                    [idKey]: idVal
                }, function(resp) {
                    if (resp && resp.success) {
                        loadCategoryData(selectedCategoryId);
                    } else {
                        alert((resp && resp.data && resp.data.message) ? resp.data.message : (exchangeProAdmin.strings.error || 'Error'));
                    }
                });
            }

            $(document).on('click', '.delete-brand-btn', function() {
                doDelete('exchange_pro_delete_brand', 'brand_id', $(this).data('brand-id'));
            });

            $(document).on('click', '.delete-model-btn', function() {
                doDelete('exchange_pro_delete_model', 'model_id', $(this).data('model-id'));
            });

            $(document).on('click', '.delete-variant-btn', function() {
                doDelete('exchange_pro_delete_variant', 'variant_id', $(this).data('variant-id'));
            });

            function doRestore(action, idKey, idVal) {
                $.post(ajaxurl, {
                    action: action,
                    nonce: exchangeProAdmin.nonce,
                    [idKey]: idVal
                }, function(resp) {
                    if (resp && resp.success) {
                        loadCategoryData(selectedCategoryId);
                    } else {
                        alert((resp && resp.data && resp.data.message) ? resp.data.message : (exchangeProAdmin.strings.error || 'Error'));
                    }
                });
            }

            $(document).on('click', '.restore-brand-btn', function() {
                doRestore('exchange_pro_restore_brand', 'brand_id', $(this).data('brand-id'));
            });
            $(document).on('click', '.restore-model-btn', function() {
                doRestore('exchange_pro_restore_model', 'model_id', $(this).data('model-id'));
            });
            $(document).on('click', '.restore-variant-btn', function() {
                doRestore('exchange_pro_restore_variant', 'variant_id', $(this).data('variant-id'));
            });

            // Edit brand/model/variant
            $(document).on('click', '.edit-brand-btn', function() {
                $('#edit-brand-id').val($(this).data('brand-id'));
                $('#edit-brand-name').val($(this).data('brand-name'));
                $('#editBrandModal').modal('show');
            });
            $('#update-brand-btn').on('click', function() {
                const id = parseInt($('#edit-brand-id').val(), 10);
                const name = ($('#edit-brand-name').val() || '').trim();
                if (!id || !name) return alert('Please enter brand name');
                $.post(ajaxurl, { action: 'exchange_pro_update_brand', nonce: exchangeProAdmin.nonce, brand_id: id, name: name }, function(resp){
                    if (resp && resp.success) {
                        $('#editBrandModal').modal('hide');
                        loadCategoryData(selectedCategoryId);
                    } else {
                        alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Error');
                    }
                });
            });

            $(document).on('click', '.edit-model-btn', function() {
                $('#edit-model-id').val($(this).data('model-id'));
                $('#edit-model-name').val($(this).data('model-name'));
                $('#editModelModal').modal('show');
            });
            $('#update-model-btn').on('click', function() {
                const id = parseInt($('#edit-model-id').val(), 10);
                const name = ($('#edit-model-name').val() || '').trim();
                if (!id || !name) return alert('Please enter model name');
                $.post(ajaxurl, { action: 'exchange_pro_update_model', nonce: exchangeProAdmin.nonce, model_id: id, name: name }, function(resp){
                    if (resp && resp.success) {
                        $('#editModelModal').modal('hide');
                        loadCategoryData(selectedCategoryId);
                    } else {
                        alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Error');
                    }
                });
            });

            $(document).on('click', '.edit-variant-btn', function() {
                $('#edit-variant-id').val($(this).data('variant-id'));
                $('#edit-variant-name').val($(this).data('variant-name'));
                $('#editVariantModal').modal('show');
            });
            $('#update-variant-btn').on('click', function() {
                const id = parseInt($('#edit-variant-id').val(), 10);
                const name = ($('#edit-variant-name').val() || '').trim();
                if (!id || !name) return alert('Please enter variant name');
                $.post(ajaxurl, { action: 'exchange_pro_update_variant', nonce: exchangeProAdmin.nonce, variant_id: id, name: name }, function(resp){
                    if (resp && resp.success) {
                        $('#editVariantModal').modal('hide');
                        loadCategoryData(selectedCategoryId);
                    } else {
                        alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Error');
                    }
                });
            });
            
            $('#save-brand-btn').on('click', function() {
                let name = $('#brand-name').val().trim();
                if (!name) {
                    alert('Please enter brand name');
                    return;
                }
                
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                
                $.post(ajaxurl, {
                    action: 'exchange_pro_add_brand',
                    nonce: exchangeProAdmin.nonce,
                    category_id: selectedCategoryId,
                    name: name
                }, function(response) {
                    if (response.success) {
                        $('#addBrandModal').modal('hide');
                        loadCategoryData(selectedCategoryId);
                    } else {
                        alert('Error adding brand');
                    }
                }).always(function() {
                    $('#save-brand-btn').prop('disabled', false).html('Save Brand');
                });
            });
            
            // Add model
            $(document).on('click', '.add-model-btn', function() {
                let brandId = $(this).data('brand-id');
                $('#model-brand-id').val(brandId);
                $('#model-name').val('');
                $('#addModelModal').modal('show');
            });
            
            $('#save-model-btn').on('click', function() {
                let name = $('#model-name').val().trim();
                let brandId = $('#model-brand-id').val();
                
                if (!name) {
                    alert('Please enter model name');
                    return;
                }
                
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                
                $.post(ajaxurl, {
                    action: 'exchange_pro_add_model',
                    nonce: exchangeProAdmin.nonce,
                    brand_id: brandId,
                    name: name
                }, function(response) {
                    if (response.success) {
                        $('#addModelModal').modal('hide');
                        loadCategoryData(selectedCategoryId);
                    } else {
                        alert('Error adding model');
                    }
                }).always(function() {
                    $('#save-model-btn').prop('disabled', false).html('Save Model');
                });
            });
            
            // Add variant
            $(document).on('click', '.add-variant-btn', function() {
                let modelId = $(this).data('model-id');
                $('#variant-model-id').val(modelId);
                $('#variant-name').val('');
                $('#addVariantModal').modal('show');
            });
            
            $('#save-variant-btn').on('click', function() {
                let name = $('#variant-name').val().trim();
                let modelId = $('#variant-model-id').val();
                
                if (!name) {
                    alert('Please enter variant name');
                    return;
                }
                
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                
                $.post(ajaxurl, {
                    action: 'exchange_pro_add_variant',
                    nonce: exchangeProAdmin.nonce,
                    model_id: modelId,
                    name: name
                }, function(response) {
                    if (response.success) {
                        $('#addVariantModal').modal('hide');
                        loadCategoryData(selectedCategoryId);
                    } else {
                        alert('Error adding variant');
                    }
                }).always(function() {
                    $('#save-variant-btn').prop('disabled', false).html('Save Variant');
                });
            });
            
            // Set pricing
            $(document).on('click', '.set-pricing-btn', function() {
                let variantId = $(this).data('variant-id');
                let variantName = $(this).data('variant-name');
                
                $('#pricing-variant-id').val(variantId);
                $('#pricing-variant-name').text(variantName);
                
                // Load existing pricing
                // TODO: Fetch and populate existing prices
                
                $('#setPricingModal').modal('show');
            });
            
            $('#save-pricing-btn').on('click', function() {
                let variantId = $('#pricing-variant-id').val();
                let prices = {
                    excellent: $('#price-excellent').val(),
                    good: $('#price-good').val(),
                    fair: $('#price-fair').val(),
                    poor: $('#price-poor').val()
                };
                
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                
                $.post(ajaxurl, {
                    action: 'exchange_pro_set_pricing',
                    nonce: exchangeProAdmin.nonce,
                    variant_id: variantId,
                    prices: prices
                }, function(response) {
                    if (response.success) {
                        $('#setPricingModal').modal('hide');
                        loadCategoryData(selectedCategoryId);
                    } else {
                        alert('Error setting pricing');
                    }
                }).always(function() {
                    $('#save-pricing-btn').prop('disabled', false).html('Save Pricing');
                });
            });
        });
        </script>
        <?php
    }
    
    public function ajax_add_brand() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        
        $category_id = intval($_POST['category_id']);
        $name = sanitize_text_field($_POST['name']);
        
        if (!$name) {
            wp_send_json_error();
        }
        
        $result = $this->db->insert_brand(array(
            'category_id' => $category_id,
            'name' => $name,
            'slug' => sanitize_title($name),
            'status' => 'active'
        ));
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
    
    public function ajax_add_model() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        
        $brand_id = intval($_POST['brand_id']);
        $name = sanitize_text_field($_POST['name']);
        
        if (!$name) {
            wp_send_json_error();
        }
        
        $result = $this->db->insert_model(array(
            'brand_id' => $brand_id,
            'name' => $name,
            'slug' => sanitize_title($name),
            'status' => 'active'
        ));
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
    
    public function ajax_add_variant() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        
        $model_id = intval($_POST['model_id']);
        $name = sanitize_text_field($_POST['name']);
        
        if (!$name) {
            wp_send_json_error();
        }
        
        $result = $this->db->insert_variant(array(
            'model_id' => $model_id,
            'name' => $name,
            'slug' => sanitize_title($name),
            'status' => 'active'
        ));
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
    
    public function ajax_set_pricing() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        
        $variant_id = intval($_POST['variant_id']);
        $prices = $_POST['prices'];
        
        foreach ($prices as $condition => $price) {
            if ($price) {
                $this->db->upsert_pricing($variant_id, $condition, floatval($price));
            }
        }
        
        wp_send_json_success();
    }
    
    public function ajax_get_category_data() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        
        $category_id = intval($_POST['category_id']);
        // Admin sees active + inactive (admin-only visibility)
        $brands = $this->db->get_brands($category_id, null);
        
        $data = array('brands' => array());
        
        foreach ($brands as $brand) {
            $brand_data = array(
                'id' => $brand->id,
                'name' => $brand->name,
                'status' => isset($brand->status) ? $brand->status : 'active',
                'models' => array()
            );
            
            $models = $this->db->get_models($brand->id, null);
            foreach ($models as $model) {
                $model_data = array(
                    'id' => $model->id,
                    'name' => $model->name,
                    'status' => isset($model->status) ? $model->status : 'active',
                    'variants' => array()
                );
                
                $variants = $this->db->get_variants($model->id, null);
                foreach ($variants as $variant) {
                    $pricing = $this->db->get_pricing($variant->id);
                    $prices = array();
                    foreach ($pricing as $p) {
                        $prices[$p->condition_type] = $p->price;
                    }
                    
                    $model_data['variants'][] = array(
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'status' => isset($variant->status) ? $variant->status : 'active',
                        'pricing' => $prices
                    );
                }
                
                $brand_data['models'][] = $model_data;
            }
            
            $data['brands'][] = $brand_data;
        }
        
        wp_send_json_success($data);
    }

    public function ajax_delete_brand() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'exchange-pro')));
        }
        $brand_id = isset($_POST['brand_id']) ? intval($_POST['brand_id']) : 0;
        if (!$brand_id) {
            wp_send_json_error(array('message' => __('Invalid brand', 'exchange-pro')));
        }

        // Soft delete: mark inactive (admin-only visibility)
        $this->db->update_brand($brand_id, array('status' => 'inactive'));
        $this->db->insert_exchange_log(array(
            'exchange_id' => null,
            'order_id' => null,
            'action' => 'brand_deactivated',
            'old_value' => maybe_serialize(array('brand_id' => $brand_id)),
            'new_value' => maybe_serialize(array('status' => 'inactive')),
            'admin_user' => get_current_user_id()
        ));

        // Cascade: models + variants inactive
        $models = $this->db->get_models($brand_id, null);
        foreach ($models as $m) {
            $this->db->update_model($m->id, array('status' => 'inactive'));
            $variants = $this->db->get_variants($m->id, null);
            foreach ($variants as $v) {
                $this->db->update_variant($v->id, array('status' => 'inactive'));
            }
        }

        wp_send_json_success();
    }

    public function ajax_delete_model() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'exchange-pro')));
        }
        $model_id = isset($_POST['model_id']) ? intval($_POST['model_id']) : 0;
        if (!$model_id) {
            wp_send_json_error(array('message' => __('Invalid model', 'exchange-pro')));
        }

        // Soft delete: mark inactive
        $this->db->update_model($model_id, array('status' => 'inactive'));
        $this->db->insert_exchange_log(array(
            'exchange_id' => null,
            'order_id' => null,
            'action' => 'model_deactivated',
            'old_value' => maybe_serialize(array('model_id' => $model_id)),
            'new_value' => maybe_serialize(array('status' => 'inactive')),
            'admin_user' => get_current_user_id()
        ));
        $variants = $this->db->get_variants($model_id, null);
        foreach ($variants as $v) {
            $this->db->update_variant($v->id, array('status' => 'inactive'));
        }
        wp_send_json_success();
    }

    public function ajax_delete_variant() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'exchange-pro')));
        }
        $variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : 0;
        if (!$variant_id) {
            wp_send_json_error(array('message' => __('Invalid variant', 'exchange-pro')));
        }
        // Soft delete: mark inactive
        $this->db->update_variant($variant_id, array('status' => 'inactive'));
        $this->db->insert_exchange_log(array(
            'exchange_id' => null,
            'order_id' => null,
            'action' => 'variant_deactivated',
            'old_value' => maybe_serialize(array('variant_id' => $variant_id)),
            'new_value' => maybe_serialize(array('status' => 'inactive')),
            'admin_user' => get_current_user_id()
        ));
        wp_send_json_success();
    }

    // Phase 2: update name
    public function ajax_update_brand() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'exchange-pro')));
        }
        $brand_id = isset($_POST['brand_id']) ? intval($_POST['brand_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        if (!$brand_id || !$name) {
            wp_send_json_error(array('message' => __('Invalid data', 'exchange-pro')));
        }
        $ok = $this->db->update_brand($brand_id, array('name' => $name, 'slug' => sanitize_title($name)));
        if ($ok !== false) {
            $this->db->insert_exchange_log(array(
                'exchange_id' => null,
                'order_id' => null,
                'action' => 'brand_updated',
                'old_value' => maybe_serialize(array('brand_id' => $brand_id)),
                'new_value' => maybe_serialize(array('name' => $name)),
                'admin_user' => get_current_user_id()
            ));
            wp_send_json_success();
        }
        wp_send_json_error(array('message' => __('Failed to update brand', 'exchange-pro')));
    }

    public function ajax_update_model() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'exchange-pro')));
        }
        $model_id = isset($_POST['model_id']) ? intval($_POST['model_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        if (!$model_id || !$name) {
            wp_send_json_error(array('message' => __('Invalid data', 'exchange-pro')));
        }
        $ok = $this->db->update_model($model_id, array('name' => $name, 'slug' => sanitize_title($name)));
        if ($ok !== false) {
            $this->db->insert_exchange_log(array(
                'exchange_id' => null,
                'order_id' => null,
                'action' => 'model_updated',
                'old_value' => maybe_serialize(array('model_id' => $model_id)),
                'new_value' => maybe_serialize(array('name' => $name)),
                'admin_user' => get_current_user_id()
            ));
            wp_send_json_success();
        }
        wp_send_json_error(array('message' => __('Failed to update model', 'exchange-pro')));
    }

    public function ajax_update_variant() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'exchange-pro')));
        }
        $variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        if (!$variant_id || !$name) {
            wp_send_json_error(array('message' => __('Invalid data', 'exchange-pro')));
        }
        $ok = $this->db->update_variant($variant_id, array('name' => $name, 'slug' => sanitize_title($name)));
        if ($ok !== false) {
            $this->db->insert_exchange_log(array(
                'exchange_id' => null,
                'order_id' => null,
                'action' => 'variant_updated',
                'old_value' => maybe_serialize(array('variant_id' => $variant_id)),
                'new_value' => maybe_serialize(array('name' => $name)),
                'admin_user' => get_current_user_id()
            ));
            wp_send_json_success();
        }
        wp_send_json_error(array('message' => __('Failed to update variant', 'exchange-pro')));
    }

    // Phase 2: restore
    public function ajax_restore_brand() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'exchange-pro')));
        }
        $brand_id = isset($_POST['brand_id']) ? intval($_POST['brand_id']) : 0;
        if (!$brand_id) {
            wp_send_json_error(array('message' => __('Invalid brand', 'exchange-pro')));
        }
        $this->db->update_brand($brand_id, array('status' => 'active'));
        $this->db->insert_exchange_log(array(
            'exchange_id' => null,
            'order_id' => null,
            'action' => 'brand_restored',
            'old_value' => maybe_serialize(array('brand_id' => $brand_id)),
            'new_value' => maybe_serialize(array('status' => 'active')),
            'admin_user' => get_current_user_id()
        ));
        wp_send_json_success();
    }

    public function ajax_restore_model() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'exchange-pro')));
        }
        $model_id = isset($_POST['model_id']) ? intval($_POST['model_id']) : 0;
        if (!$model_id) {
            wp_send_json_error(array('message' => __('Invalid model', 'exchange-pro')));
        }
        $this->db->update_model($model_id, array('status' => 'active'));
        $this->db->insert_exchange_log(array(
            'exchange_id' => null,
            'order_id' => null,
            'action' => 'model_restored',
            'old_value' => maybe_serialize(array('model_id' => $model_id)),
            'new_value' => maybe_serialize(array('status' => 'active')),
            'admin_user' => get_current_user_id()
        ));
        wp_send_json_success();
    }

    public function ajax_restore_variant() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'exchange-pro')));
        }
        $variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : 0;
        if (!$variant_id) {
            wp_send_json_error(array('message' => __('Invalid variant', 'exchange-pro')));
        }
        $this->db->update_variant($variant_id, array('status' => 'active'));
        $this->db->insert_exchange_log(array(
            'exchange_id' => null,
            'order_id' => null,
            'action' => 'variant_restored',
            'old_value' => maybe_serialize(array('variant_id' => $variant_id)),
            'new_value' => maybe_serialize(array('status' => 'active')),
            'admin_user' => get_current_user_id()
        ));
        wp_send_json_success();
    }
}
