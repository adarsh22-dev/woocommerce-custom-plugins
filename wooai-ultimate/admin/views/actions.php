<?php if (!defined('ABSPATH')) exit;

// Handle image upload
if (isset($_FILES['icon_image']) && $_FILES['icon_image']['size'] > 0) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    $attachment_id = media_handle_upload('icon_image', 0);
    if (!is_wp_error($attachment_id)) {
        $_POST['icon_url'] = wp_get_attachment_url($attachment_id);
    }
}

// Handle add/edit
if (isset($_POST['save_action'])) {
    check_admin_referer('wooai_action_save');
    
    global $wpdb;
    $table = $wpdb->prefix . 'wooai_actions';
    
    $icon_url = isset($_POST['icon_url']) ? esc_url_raw($_POST['icon_url']) : '';
    
    $data = array(
        'label' => sanitize_text_field($_POST['label']),
        'icon' => $icon_url,
        'action_type' => sanitize_text_field($_POST['action_type']),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'is_custom' => isset($_POST['is_custom']) ? 1 : 0,
        'sort_order' => intval($_POST['sort_order'])
    );
    
    if (!empty($_POST['action_id'])) {
        $wpdb->update($table, $data, array('id' => intval($_POST['action_id'])));
        echo '<div class="notice notice-success"><p>✅ Action updated!</p></div>';
    } else {
        $wpdb->insert($table, $data);
        echo '<div class="notice notice-success"><p>✅ Action added!</p></div>';
    }
}

// Handle delete
if (isset($_GET['delete']) && check_admin_referer('wooai_delete_action_' . $_GET['delete'])) {
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'wooai_actions', array('id' => intval($_GET['delete'])));
    echo '<div class="notice notice-success"><p>✅ Action deleted!</p></div>';
}

global $wpdb;
$actions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wooai_actions ORDER BY sort_order", ARRAY_A);

$editing = null;
if (isset($_GET['edit'])) {
    $editing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wooai_actions WHERE id = %d", intval($_GET['edit'])), ARRAY_A);
}
?>

<div class="wooai-wrap">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1>⚡ Quick Actions Manager</h1>
        <button class="button button-primary" onclick="document.getElementById('action-form').style.display='block'">+ Add Action</button>
    </div>
    
    <div class="notice notice-info">
        <p><strong>📸 Upload PNG/JPG icons!</strong> Use 128x128px or 256x256px images for best results.</p>
    </div>
    
    <!-- Add/Edit Form -->
    <div id="action-form" class="wooai-card" style="<?php echo $editing ? 'display:block;' : 'display:none;'; ?>">
        <h2><?php echo $editing ? '✏️ Edit' : '➕ Add'; ?> Quick Action</h2>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('wooai_action_save'); ?>
            <input type="hidden" name="action_id" value="<?php echo $editing ? $editing['id'] : ''; ?>">
            <?php if ($editing && $editing['icon']): ?>
            <input type="hidden" name="icon_url" value="<?php echo esc_url($editing['icon']); ?>">
            <?php endif; ?>
            
            <table class="form-table">
                <tr>
                    <th><label>Label *</label></th>
                    <td>
                        <input type="text" name="label" value="<?php echo $editing ? esc_attr($editing['label']) : ''; ?>" 
                               class="regular-text" required placeholder="Bestselling">
                    </td>
                </tr>
                
                <tr>
                    <th><label>Icon Image *</label></th>
                    <td>
                        <?php if ($editing && $editing['icon']): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="<?php echo esc_url($editing['icon']); ?>" style="width: 64px; height: 64px; object-fit: contain; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                            <p class="description">Current icon</p>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="icon_image" accept="image/png,image/jpeg,image/jpg" <?php echo $editing ? '' : 'required'; ?>>
                        <p class="description">Upload PNG or JPG image (recommended: 128x128px or 256x256px)</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label>Action Type *</label></th>
                    <td>
                        <select name="action_type" class="regular-text" required>
                            <option value="">Select...</option>
                            <option value="bestselling" <?php echo ($editing && $editing['action_type'] == 'bestselling') ? 'selected' : ''; ?>>Bestselling</option>
                            <option value="recommended" <?php echo ($editing && $editing['action_type'] == 'recommended') ? 'selected' : ''; ?>>Recommended</option>
                            <option value="new_arrivals" <?php echo ($editing && $editing['action_type'] == 'new_arrivals') ? 'selected' : ''; ?>>New Arrivals</option>
                            <option value="offers" <?php echo ($editing && $editing['action_type'] == 'offers') ? 'selected' : ''; ?>>Offers</option>
                            <option value="search" <?php echo ($editing && $editing['action_type'] == 'search') ? 'selected' : ''; ?>>Search Product</option>
                            <option value="policies" <?php echo ($editing && $editing['action_type'] == 'policies') ? 'selected' : ''; ?>>Policies</option>
                            <option value="account" <?php echo ($editing && $editing['action_type'] == 'account') ? 'selected' : ''; ?>>My Account</option>
                            <option value="ordertracking" <?php echo ($editing && $editing['action_type'] == 'ordertracking') ? 'selected' : ''; ?>>Order Tracking</option>
                            <option value="callback" <?php echo ($editing && $editing['action_type'] == 'callback') ? 'selected' : ''; ?>>Callback</option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th><label>Sort Order</label></th>
                    <td>
                        <input type="number" name="sort_order" value="<?php echo $editing ? $editing['sort_order'] : '0'; ?>" 
                               class="small-text" min="0">
                    </td>
                </tr>
                
                <tr>
                    <th><label>Active</label></th>
                    <td>
                        <input type="checkbox" name="is_active" value="1" <?php echo (!$editing || $editing['is_active']) ? 'checked' : ''; ?>>
                        Show in chat widget
                    </td>
                </tr>
                
                <tr>
                    <th><label>Custom</label></th>
                    <td>
                        <input type="checkbox" name="is_custom" value="1" <?php echo ($editing && $editing['is_custom']) ? 'checked' : ''; ?>>
                        Custom action (can be deleted)
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" name="save_action" class="button button-primary button-large">
                    <?php echo $editing ? '💾 Update' : '➕ Add'; ?>
                </button>
                <a href="?page=wooai-actions" class="button button-large">Cancel</a>
            </p>
        </form>
    </div>
    
    <!-- Actions List -->
    <div class="wooai-card">
        <h2>All Quick Actions (<?php echo count($actions); ?>)</h2>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th width="10%">Icon</th>
                    <th width="20%">Label</th>
                    <th width="20%">Type</th>
                    <th width="10%">Order</th>
                    <th width="10%">Status</th>
                    <th width="10%">Custom</th>
                    <th width="20%">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($actions as $action): ?>
                <tr>
                    <td>
                        <img src="<?php echo esc_url($action['icon']); ?>" style="width: 40px; height: 40px; object-fit: contain;">
                    </td>
                    <td><strong><?php echo esc_html($action['label']); ?></strong></td>
                    <td><code><?php echo esc_html($action['action_type']); ?></code></td>
                    <td><?php echo $action['sort_order']; ?></td>
                    <td>
                        <?php if ($action['is_active']): ?>
                            <span style="color:#10B981; font-weight:600;">✓ Active</span>
                        <?php else: ?>
                            <span style="color:#6B7280;">○ Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $action['is_custom'] ? '<span style="color:#7C3AED;">✓ Custom</span>' : '—'; ?></td>
                    <td>
                        <a href="?page=wooai-actions&edit=<?php echo $action['id']; ?>" class="button button-small">Edit</a>
                        <?php if ($action['is_custom']): ?>
                        <a href="?page=wooai-actions&delete=<?php echo $action['id']; ?>&_wpnonce=<?php echo wp_create_nonce('wooai_delete_action_' . $action['id']); ?>" 
                           class="button button-small" onclick="return confirm('Delete?')">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.wooai-card {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}
.form-table th {
    width: 150px;
    padding: 15px 10px 15px 0;
}
</style>
