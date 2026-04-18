<?php if (!defined('ABSPATH')) exit;

// Handle status update
if (isset($_POST['update_callback_status'])) {
    check_admin_referer('wooai_callback_update');
    
    global $wpdb;
    $table = $wpdb->prefix . 'wooai_callbacks';
    
    $wpdb->update(
        $table,
        array('status' => sanitize_text_field($_POST['status'])),
        array('id' => intval($_POST['callback_id']))
    );
    
    echo '<div class="notice notice-success"><p>Callback status updated!</p></div>';
}

// Get callbacks
global $wpdb;
$callbacks_table = $wpdb->prefix . 'wooai_callbacks';

// Get filter
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';

$where = '1=1';
if ($status_filter !== 'all') {
    $where = $wpdb->prepare('status = %s', $status_filter);
}

$callbacks = $wpdb->get_results("SELECT * FROM {$callbacks_table} WHERE {$where} ORDER BY created_at DESC", ARRAY_A);
?>

<div class="wooai-wrap">
    <h1>📞 Callback Requests</h1>
    
    <!-- Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <?php
        $total = count($callbacks);
        $pending = count(array_filter($callbacks, function($c) { return $c['status'] == 'pending'; }));
        $completed = count(array_filter($callbacks, function($c) { return $c['status'] == 'completed'; }));
        ?>
        
        <div class="wooai-card" style="padding: 20px;">
            <h3 style="margin: 0 0 10px 0; color: #6B7280; font-size: 14px;">Total Requests</h3>
            <div style="font-size: 32px; font-weight: 700; color: #1F2937;"><?php echo $total; ?></div>
        </div>
        
        <div class="wooai-card" style="padding: 20px;">
            <h3 style="margin: 0 0 10px 0; color: #6B7280; font-size: 14px;">Pending</h3>
            <div style="font-size: 32px; font-weight: 700; color: #F59E0B;"><?php echo $pending; ?></div>
        </div>
        
        <div class="wooai-card" style="padding: 20px;">
            <h3 style="margin: 0 0 10px 0; color: #6B7280; font-size: 14px;">Completed</h3>
            <div style="font-size: 32px; font-weight: 700; color: #10B981;"><?php echo $completed; ?></div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="wooai-card" style="margin-bottom: 20px; padding: 15px;">
        <div style="display: flex; gap: 10px; align-items: center;">
            <span style="font-weight: 600;">Filter:</span>
            <a href="?page=wooai-callbacks&status=all" class="button <?php echo $status_filter == 'all' ? 'button-primary' : ''; ?>">
                All (<?php echo $total; ?>)
            </a>
            <a href="?page=wooai-callbacks&status=pending" class="button <?php echo $status_filter == 'pending' ? 'button-primary' : ''; ?>">
                Pending (<?php echo $pending; ?>)
            </a>
            <a href="?page=wooai-callbacks&status=in_progress" class="button <?php echo $status_filter == 'in_progress' ? 'button-primary' : ''; ?>">
                In Progress
            </a>
            <a href="?page=wooai-callbacks&status=completed" class="button <?php echo $status_filter == 'completed' ? 'button-primary' : ''; ?>">
                Completed (<?php echo $completed; ?>)
            </a>
        </div>
    </div>
    
    <!-- Callbacks List -->
    <div class="wooai-card">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="20%">Name</th>
                    <th width="15%">Phone</th>
                    <th width="15%">Email</th>
                    <th width="20%">Message</th>
                    <th width="10%">Status</th>
                    <th width="12%">Date</th>
                    <th width="8%">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($callbacks)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #6B7280;">
                            No callback requests found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($callbacks as $callback): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($callback['name']); ?></strong>
                            <?php if ($callback['user_id']): ?>
                                <br><small style="color: #6B7280;">User #<?php echo $callback['user_id']; ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="tel:<?php echo esc_attr($callback['phone']); ?>" style="color: #7C3AED; text-decoration: none;">
                                📞 <?php echo esc_html($callback['phone']); ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($callback['email']): ?>
                                <a href="mailto:<?php echo esc_attr($callback['email']); ?>" style="color: #7C3AED; text-decoration: none;">
                                    <?php echo esc_html($callback['email']); ?>
                                </a>
                            <?php else: ?>
                                <span style="color: #9CA3AF;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($callback['message']): ?>
                                <div style="max-height: 60px; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo esc_html(substr($callback['message'], 0, 100)); ?>
                                    <?php if (strlen($callback['message']) > 100): ?>...<?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #9CA3AF;">No message</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $status_colors = array(
                                'pending' => array('bg' => '#FEF3C7', 'text' => '#F59E0B', 'label' => 'Pending'),
                                'in_progress' => array('bg' => '#DBEAFE', 'text' => '#3B82F6', 'label' => 'In Progress'),
                                'completed' => array('bg' => '#D1FAE5', 'text' => '#10B981', 'label' => 'Completed'),
                                'cancelled' => array('bg' => '#FEE2E2', 'text' => '#EF4444', 'label' => 'Cancelled')
                            );
                            $status_info = $status_colors[$callback['status']] ?? $status_colors['pending'];
                            ?>
                            <span style="display: inline-block; padding: 4px 8px; background: <?php echo $status_info['bg']; ?>; color: <?php echo $status_info['text']; ?>; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                <?php echo $status_info['label']; ?>
                            </span>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($callback['created_at'])); ?>
                            <br>
                            <small style="color: #6B7280;"><?php echo date('H:i', strtotime($callback['created_at'])); ?></small>
                        </td>
                        <td>
                            <button type="button" class="button button-small" onclick="updateCallbackStatus(<?php echo $callback['id']; ?>)">
                                Update
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Status Update Modal -->
<div id="status-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; padding:30px; border-radius:12px; max-width:500px; width:90%;">
        <h2>Update Callback Status</h2>
        <form method="post" id="status-form">
            <?php wp_nonce_field('wooai_callback_update'); ?>
            <input type="hidden" name="callback_id" id="callback-id">
            
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="regular-text" required>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" name="update_callback_status" class="button button-primary">Update Status</button>
                <button type="button" class="button" onclick="closeStatusModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function updateCallbackStatus(callbackId) {
    document.getElementById('callback-id').value = callbackId;
    document.getElementById('status-modal').style.display = 'flex';
}

function closeStatusModal() {
    document.getElementById('status-modal').style.display = 'none';
}

// Close modal on outside click
document.getElementById('status-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeStatusModal();
    }
});
</script>

<style>
.wooai-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: #374151;
}
</style>
