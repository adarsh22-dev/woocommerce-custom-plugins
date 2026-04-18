<?php if (!defined('ABSPATH')) exit;

// Handle toggle
if (isset($_POST['toggle_policy'])) {
    check_admin_referer('wooai_toggle_policy');
    
    global $wpdb;
    $table = $wpdb->prefix . 'wooai_policies';
    
    $policy_id = intval($_POST['policy_id']);
    $is_active = intval($_POST['is_active']);
    
    $wpdb->update($table, 
        array('is_active' => $is_active),
        array('id' => $policy_id)
    );
    
    echo '<div class="notice notice-success is-dismissible"><p>✅ Policy updated!</p></div>';
}

// Handle edit save
if (isset($_POST['save_policy'])) {
    check_admin_referer('wooai_save_policy');
    
    global $wpdb;
    $table = $wpdb->prefix . 'wooai_policies';
    
    $wpdb->update($table, 
        array(
            'summary' => sanitize_textarea_field($_POST['summary']),
            'url' => esc_url_raw($_POST['url'])
        ),
        array('id' => intval($_POST['policy_id']))
    );
    
    echo '<div class="notice notice-success is-dismissible"><p>✅ Policy saved!</p></div>';
}

// Define icons for each policy type
$icon_map = array(
    'return' => '↩️',
    'shipping' => '📦',
    'privacy' => '🔒',
    'terms' => '📄',
    'refund' => '💰',
    'warranty' => '🛡️',
    'cookie' => '🍪',
    'disclaimer' => '⚠️',
    'payment' => '💳',
    'cancellation' => '🚫',
    'exchange' => '🔄',
    'delivery' => '🚚',
    'security' => '🔐',
    'faq' => '❓',
    'contact' => '📞',
    'about' => '🏢',
    'support' => '💬',
    'loyalty' => '⭐',
    'trackorder' => '📍',
    'giftcard' => '🎁',
    'wholesale' => '🏭',
    'affiliate' => '🤝',
    'sizeguide' => '📏',
    'care' => '🧼',
    'sustainability' => '🌱',
);

// Get existing policies
global $wpdb;
$table = $wpdb->prefix . 'wooai_policies';
$policies = $wpdb->get_results("SELECT * FROM $table ORDER BY id", ARRAY_A);

$editing = null;
if (isset($_GET['edit'])) {
    $editing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($_GET['edit'])), ARRAY_A);
}
?>

<div class="wooai-wrap">
    <h1>📋 Policies Manager</h1>
    <p class="description" style="margin-bottom: 20px;">Enable/disable policies to show in chatbot. Click Edit to customize content.</p>
    
    <?php if (count($policies) < 10): ?>
    <div class="notice notice-warning">
        <p><strong>⚠️ Missing Policies!</strong> You have <?php echo count($policies); ?> policies but should have 25. Please <strong>deactivate and reactivate</strong> the plugin to create all policies.</p>
    </div>
    <?php endif; ?>
    
    <div class="wooai-card">
        <h2>All Policies (<?php echo count($policies); ?>)</h2>
        <p style="color: #6B7280; margin: 0 0 20px 0;">Toggle to enable/disable policies in the chatbot</p>
        
        <?php if (empty($policies)): ?>
            <div style="text-align: center; padding: 60px 20px; color: #6B7280; background: #F9FAFB; border-radius: 8px;">
                <div style="font-size: 48px; margin-bottom: 16px;">📋</div>
                <h3 style="margin: 0 0 8px 0; color: #374151;">No Policies Found</h3>
                <p style="margin: 0;">Please deactivate and reactivate the plugin to create all 25 policies.</p>
            </div>
        <?php else: ?>
        
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th width="5%">Icon</th>
                    <th width="25%">Policy</th>
                    <th width="40%">Summary</th>
                    <th width="15%">Status</th>
                    <th width="15%">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($policies as $policy): 
                $icon = isset($icon_map[$policy['type']]) ? $icon_map[$policy['type']] : '📋';
                ?>
                <tr>
                    <td style="font-size: 24px;"><?php echo $icon; ?></td>
                    <td><strong><?php echo esc_html($policy['title']); ?></strong></td>
                    <td>
                        <div style="max-height: 40px; overflow: hidden; color: #6B7280;">
                            <?php 
                            $summary = esc_html($policy['summary']);
                            echo strlen($summary) > 80 ? substr($summary, 0, 80) . '...' : $summary;
                            ?>
                        </div>
                    </td>
                    <td>
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field('wooai_toggle_policy'); ?>
                            <input type="hidden" name="policy_id" value="<?php echo $policy['id']; ?>">
                            <input type="hidden" name="is_active" value="<?php echo $policy['is_active'] ? 0 : 1; ?>">
                            <button type="submit" name="toggle_policy" class="button button-small" style="<?php echo $policy['is_active'] ? 'background: #10B981; color: white; border-color: #10B981;' : ''; ?>">
                                <?php echo $policy['is_active'] ? '✓ Enabled' : '○ Disabled'; ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <a href="?page=wooai-policies&edit=<?php echo $policy['id']; ?>" class="button button-small">
                            ✏️ Edit
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    
    <?php if ($editing): ?>
    <div class="wooai-card" style="margin-top: 20px;">
        <h2>✏️ Edit: <?php echo esc_html($editing['title']); ?></h2>
        <form method="post">
            <?php wp_nonce_field('wooai_save_policy'); ?>
            <input type="hidden" name="policy_id" value="<?php echo $editing['id']; ?>">
            
            <table class="form-table">
                <tr>
                    <th><label>Summary *</label></th>
                    <td>
                        <textarea name="summary" rows="5" class="large-text" required><?php echo esc_textarea($editing['summary']); ?></textarea>
                        <p class="description">Brief summary that AI will use when answering questions</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label>Page URL</label></th>
                    <td>
                        <input type="url" name="url" value="<?php echo esc_url($editing['url']); ?>" class="large-text" placeholder="https://yoursite.com/policy-page">
                        <p class="description">Optional: Link to full policy page</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" name="save_policy" class="button button-primary button-large">💾 Save Policy</button>
                <a href="?page=wooai-policies" class="button button-large">Cancel</a>
            </p>
        </form>
    </div>
    <?php endif; ?>
    
    <div class="wooai-card" style="margin-top: 20px; background: #F0F9FF; border-left: 4px solid #3B82F6;">
        <h3 style="margin: 0 0 12px 0; color: #1E40AF;">💡 How It Works</h3>
        <ol style="margin: 0; padding-left: 20px; color: #1F2937;">
            <li><strong>Enable/Disable:</strong> Click the status button to toggle policies in the chatbot</li>
            <li><strong>Edit Content:</strong> Click "Edit" to customize the summary and add page URL</li>
            <li><strong>AI Usage:</strong> When enabled, AI will use these policies to answer customer questions</li>
            <li><strong>Missing Policies?</strong> Deactivate and reactivate the plugin to create all 25</li>
        </ol>
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
.wooai-card h2 {
    margin: 0 0 20px 0;
}
.form-table th {
    width: 150px;
    padding: 15px 10px 15px 0;
    vertical-align: top;
}
</style>
