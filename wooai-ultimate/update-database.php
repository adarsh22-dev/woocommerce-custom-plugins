<?php
/**
 * WooAI Database Updater
 * Run this file ONCE to update Order History to Order Tracking
 * 
 * HOW TO USE:
 * 1. Upload this file to: wp-content/plugins/wooai-ultimate/
 * 2. Visit: yoursite.com/wp-content/plugins/wooai-ultimate/update-database.php
 * 3. You'll see success message
 * 4. Delete this file after running
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('You must be an administrator to run this script.');
}

global $wpdb;
$table = $wpdb->prefix . 'wooai_actions';

// Update Order History to Order Tracking
$updated = $wpdb->update(
    $table,
    array(
        'label' => 'Order Tracking',
        'icon' => '🚚',
        'action_type' => 'ordertracking'
    ),
    array('action_type' => 'orders'),
    array('%s', '%s', '%s'),
    array('%s')
);

if ($updated !== false) {
    echo '<h1>✅ Database Updated Successfully!</h1>';
    echo '<p><strong>Updated:</strong> ' . $updated . ' row(s)</p>';
    echo '<p>Order History has been changed to Order Tracking</p>';
    echo '<p><strong>Action type:</strong> orders → ordertracking</p>';
    echo '<p><strong>Icon:</strong> 📦 → 🚚</p>';
    echo '<hr>';
    echo '<p><strong>⚠️ IMPORTANT:</strong> Delete this file now for security!</p>';
    echo '<p><a href="' . admin_url('admin.php?page=wooai-actions') . '" style="display:inline-block;padding:10px 20px;background:#0073aa;color:white;text-decoration:none;border-radius:3px;">Go to Quick Actions</a></p>';
} else {
    echo '<h1>❌ Update Failed</h1>';
    echo '<p>Error: ' . $wpdb->last_error . '</p>';
}

// Show current actions
echo '<hr><h2>Current Quick Actions:</h2>';
$actions = $wpdb->get_results("SELECT * FROM $table ORDER BY sort_order");
echo '<table border="1" cellpadding="10" style="border-collapse:collapse;">';
echo '<tr><th>ID</th><th>Label</th><th>Icon</th><th>Action Type</th><th>Active</th><th>Sort Order</th></tr>';
foreach ($actions as $action) {
    echo '<tr>';
    echo '<td>' . $action->id . '</td>';
    echo '<td>' . $action->label . '</td>';
    echo '<td>' . $action->icon . '</td>';
    echo '<td><strong>' . $action->action_type . '</strong></td>';
    echo '<td>' . ($action->is_active ? '✅ Yes' : '❌ No') . '</td>';
    echo '<td>' . $action->sort_order . '</td>';
    echo '</tr>';
}
echo '</table>';
?>
