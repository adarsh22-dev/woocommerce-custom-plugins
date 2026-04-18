<?php
/**
 * Dynamic Category Manager
 * Add, edit, delete categories from admin panel
 */

if (!defined('ABSPATH')) exit;

class Exchange_Pro_Category_Manager {
    
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
        add_action('wp_ajax_exchange_pro_add_category', array($this, 'ajax_add_category'));
        add_action('wp_ajax_exchange_pro_edit_category', array($this, 'ajax_edit_category'));
        add_action('wp_ajax_exchange_pro_delete_category', array($this, 'ajax_delete_category'));
        add_action('wp_ajax_exchange_pro_get_categories_list', array($this, 'ajax_get_categories'));
    }
    
    public function render() {
        $categories = $this->db->get_categories('');
        
        // Available Font Awesome icons
        $available_icons = array(
            'mobile-alt' => 'Mobile Phone',
            'laptop' => 'Laptop',
            'tablet-alt' => 'Tablet',
            'print' => 'Printer',
            'camera' => 'Camera',
            'desktop' => 'Desktop',
            'tv' => 'Television',
            'headphones' => 'Headphones',
            'keyboard' => 'Keyboard',
            'mouse' => 'Mouse',
            'gamepad' => 'Gaming Console',
            'watch' => 'Smartwatch',
            'car' => 'Vehicle',
            'bicycle' => 'Bicycle',
            'home' => 'Home Appliance',
            'blender' => 'Kitchen Appliance',
            'tools' => 'Tools',
            'plug' => 'Electronics',
            'bolt' => 'Power Tools',
            'book' => 'Books',
        );
        ?>
        <div class="wrap exchange-pro-admin">
            <h1 class="wp-heading-inline"><?php _e('Category Management', 'exchange-pro'); ?></h1>
            <button class="page-title-action" id="add-category-btn">
                <i class="fas fa-plus"></i> <?php _e('Add Category', 'exchange-pro'); ?>
            </button>
            
            <p class="description" style="margin-top: 15px;">
                <?php _e('Manage device categories. Categories added here will automatically appear in the frontend exchange popup.', 'exchange-pro'); ?>
            </p>
            
            <div class="card mt-4">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 60px;"><?php _e('Icon', 'exchange-pro'); ?></th>
                            <th><?php _e('Category Name', 'exchange-pro'); ?></th>
                            <th><?php _e('Slug', 'exchange-pro'); ?></th>
                            <th><?php _e('Status', 'exchange-pro'); ?></th>
                            <th><?php _e('Brands', 'exchange-pro'); ?></th>
                            <th><?php _e('Actions', 'exchange-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="categories-table-body">
                        <?php foreach ($categories as $cat): 
                            $brands_count = $this->db->wpdb->get_var($this->db->wpdb->prepare(
                                "SELECT COUNT(*) FROM {$this->db->brands_table} WHERE category_id = %d",
                                $cat->id
                            ));
                        ?>
                        <tr data-category-id="<?php echo $cat->id; ?>">
                            <td style="text-align: center;">
                                <i class="fas fa-<?php echo esc_attr($cat->icon); ?>" style="font-size: 24px; color: #0073aa;"></i>
                            </td>
                            <td><strong><?php echo esc_html($cat->name); ?></strong></td>
                            <td><code><?php echo esc_html($cat->slug); ?></code></td>
                            <td>
                                <?php if ($cat->status === 'active'): ?>
                                    <span class="badge bg-success" style="background: #46b450; color: white; padding: 3px 8px; border-radius: 3px;">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary" style="background: #999; color: white; padding: 3px 8px; border-radius: 3px;">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $brands_count; ?> brands</td>
                            <td>
                                <button class="button button-small edit-category-btn" 
                                        data-id="<?php echo $cat->id; ?>"
                                        data-name="<?php echo esc_attr($cat->name); ?>"
                                        data-icon="<?php echo esc_attr($cat->icon); ?>"
                                        data-status="<?php echo esc_attr($cat->status); ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="button button-small delete-category-btn" 
                                        data-id="<?php echo $cat->id; ?>"
                                        style="color: #d63638;">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Add Category Modal -->
        <div class="modal fade" id="addCategoryModal" tabindex="-1" style="background: #00000000 !important;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php _e('Add Category', 'exchange-pro'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding: 5% !important;">
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Category Name', 'exchange-pro'); ?> *</label>
                            <input type="text" class="form-control" id="add-category-name" placeholder="e.g., Mobile, Laptop">
                            <small class="text-muted"><?php _e('This will appear in the frontend', 'exchange-pro'); ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Icon', 'exchange-pro'); ?> *</label>
                            <select class="form-control" id="add-category-icon">
                                <option value="">Select icon...</option>
                                <?php foreach ($available_icons as $icon => $label): ?>
                                    <option value="<?php echo esc_attr($icon); ?>">
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted"><?php _e('Icon to display in frontend', 'exchange-pro'); ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Status', 'exchange-pro'); ?></label>
                            <select class="form-control" id="add-category-status">
                                <option value="active"><?php _e('Active', 'exchange-pro'); ?></option>
                                <option value="inactive"><?php _e('Inactive', 'exchange-pro'); ?></option>
                            </select>
                            <small class="text-muted"><?php _e('Only active categories appear in frontend', 'exchange-pro'); ?></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel', 'exchange-pro'); ?></button>
                        <button type="button" class="btn btn-primary" id="save-category-btn"><?php _e('Save Category', 'exchange-pro'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Edit Category Modal -->
        <div class="modal fade" id="editCategoryModal" tabindex="-1" style="background: #00000000 !important;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php _e('Edit Category', 'exchange-pro'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding: 5% !important;">
                        <input type="hidden" id="edit-category-id">
                        
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Category Name', 'exchange-pro'); ?> *</label>
                            <input type="text" class="form-control" id="edit-category-name">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Icon', 'exchange-pro'); ?> *</label>
                            <select class="form-control" id="edit-category-icon">
                                <option value="">Select icon...</option>
                                <?php foreach ($available_icons as $icon => $label): ?>
                                    <option value="<?php echo esc_attr($icon); ?>">
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Status', 'exchange-pro'); ?></label>
                            <select class="form-control" id="edit-category-status">
                                <option value="active"><?php _e('Active', 'exchange-pro'); ?></option>
                                <option value="inactive"><?php _e('Inactive', 'exchange-pro'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel', 'exchange-pro'); ?></button>
                        <button type="button" class="btn btn-primary" id="update-category-btn"><?php _e('Update Category', 'exchange-pro'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            
            // Add category
            $('#add-category-btn').on('click', function() {
                $('#add-category-name').val('');
                $('#add-category-icon').val('');
                $('#add-category-status').val('active');
                $('#addCategoryModal').modal('show');
            });
            
            $('#save-category-btn').on('click', function() {
                let name = $('#add-category-name').val().trim();
                let icon = $('#add-category-icon').val();
                let status = $('#add-category-status').val();
                
                if (!name || !icon) {
                    alert('Please fill all required fields');
                    return;
                }
                
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');
                
                $.post(ajaxurl, {
                    action: 'exchange_pro_add_category',
                    nonce: exchangeProAdmin.nonce,
                    name: name,
                    icon: icon,
                    status: status
                }, function(response) {
                    if (response.success) {
                        $('#addCategoryModal').modal('hide');
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error adding category');
                    }
                }).always(function() {
                    $('#save-category-btn').prop('disabled', false).html('Save Category');
                });
            });
            
            // Edit category
            $(document).on('click', '.edit-category-btn', function() {
                let id = $(this).data('id');
                let name = $(this).data('name');
                let icon = $(this).data('icon');
                let status = $(this).data('status');
                
                $('#edit-category-id').val(id);
                $('#edit-category-name').val(name);
                $('#edit-category-icon').val(icon);
                $('#edit-category-status').val(status);
                
                $('#editCategoryModal').modal('show');
            });
            
            $('#update-category-btn').on('click', function() {
                let id = $('#edit-category-id').val();
                let name = $('#edit-category-name').val().trim();
                let icon = $('#edit-category-icon').val();
                let status = $('#edit-category-status').val();
                
                if (!name || !icon) {
                    alert('Please fill all required fields');
                    return;
                }
                
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Updating...');
                
                $.post(ajaxurl, {
                    action: 'exchange_pro_edit_category',
                    nonce: exchangeProAdmin.nonce,
                    id: id,
                    name: name,
                    icon: icon,
                    status: status
                }, function(response) {
                    if (response.success) {
                        $('#editCategoryModal').modal('hide');
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error updating category');
                    }
                }).always(function() {
                    $('#update-category-btn').prop('disabled', false).html('Update Category');
                });
            });
            
            // Delete category
            $(document).on('click', '.delete-category-btn', function() {
                let id = $(this).data('id');
                
                if (!confirm('Are you sure? This will also delete all brands, models, and variants under this category!')) {
                    return;
                }
                
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                
                $.post(ajaxurl, {
                    action: 'exchange_pro_delete_category',
                    nonce: exchangeProAdmin.nonce,
                    id: id
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error deleting category');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function ajax_add_category() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $name = sanitize_text_field($_POST['name']);
        $icon = sanitize_text_field($_POST['icon']);
        $status = sanitize_text_field($_POST['status']);
        
        if (empty($name) || empty($icon)) {
            wp_send_json_error(array('message' => 'Name and icon are required'));
        }
        
        $result = $this->db->insert_category(array(
            'name' => $name,
            'slug' => sanitize_title($name),
            'icon' => $icon,
            'status' => $status
        ));
        
        if ($result) {
            wp_send_json_success(array('message' => 'Category added successfully'));
        } else {
            wp_send_json_error(array('message' => 'Error adding category'));
        }
    }
    
    public function ajax_edit_category() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $id = intval($_POST['id']);
        $name = sanitize_text_field($_POST['name']);
        $icon = sanitize_text_field($_POST['icon']);
        $status = sanitize_text_field($_POST['status']);
        
        if (empty($name) || empty($icon)) {
            wp_send_json_error(array('message' => 'Name and icon are required'));
        }
        
        $result = $this->db->update_category($id, array(
            'name' => $name,
            'slug' => sanitize_title($name),
            'icon' => $icon,
            'status' => $status
        ));
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Category updated successfully'));
        } else {
            wp_send_json_error(array('message' => 'Error updating category'));
        }
    }
    
    public function ajax_delete_category() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $id = intval($_POST['id']);
        
        // Delete all related data
        global $wpdb;
        
        // Get all brands under this category
        $brands = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$this->db->brands_table} WHERE category_id = %d",
            $id
        ));
        
        foreach ($brands as $brand_id) {
            // Get all models under this brand
            $models = $wpdb->get_col($wpdb->prepare(
                "SELECT id FROM {$this->db->models_table} WHERE brand_id = %d",
                $brand_id
            ));
            
            foreach ($models as $model_id) {
                // Get all variants under this model
                $variants = $wpdb->get_col($wpdb->prepare(
                    "SELECT id FROM {$this->db->variants_table} WHERE model_id = %d",
                    $model_id
                ));
                
                foreach ($variants as $variant_id) {
                    // Delete pricing
                    $wpdb->delete($this->db->pricing_table, array('variant_id' => $variant_id));
                }
                
                // Delete variants
                $wpdb->delete($this->db->variants_table, array('model_id' => $model_id));
            }
            
            // Delete models
            $wpdb->delete($this->db->models_table, array('brand_id' => $brand_id));
        }
        
        // Delete brands
        $wpdb->delete($this->db->brands_table, array('category_id' => $id));
        
        // Delete category
        $result = $this->db->delete_category($id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Category deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Error deleting category'));
        }
    }
    
    public function ajax_get_categories() {
        check_ajax_referer('exchange_pro_admin_nonce', 'nonce');
        
        $categories = $this->db->get_categories('active');
        wp_send_json_success(array('categories' => $categories));
    }
}
