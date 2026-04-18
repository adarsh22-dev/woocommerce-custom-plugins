<?php if (!defined('ABSPATH')) exit; ?>
<div class="wooai-wrap">
    <h1>Plugin Settings</h1>
    <form id="settings-form">
        <div class="wooai-card">
            <h2>General Configuration</h2>
            <div class="form-group">
                <label>Greeting Message</label>
                <textarea name="greeting" rows="3" style="width:100%"><?php echo esc_attr(get_option('wooai_greeting')); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Widget Color</label>
                <input type="color" name="color" value="<?php echo esc_attr(get_option('wooai_color')); ?>" />
            </div>
        </div>
        
        <div class="wooai-card">
            <h2>AI Intelligence</h2>
            <div class="ai-providers">
                <label><input type="radio" name="provider" value="gemini" <?php checked(get_option('wooai_ai_provider'), 'gemini'); ?>> Google Gemini</label>
                <label><input type="radio" name="provider" value="openai" <?php checked(get_option('wooai_ai_provider'), 'openai'); ?>> OpenAI</label>
                <label><input type="radio" name="provider" value="claude" <?php checked(get_option('wooai_ai_provider'), 'claude'); ?>> Claude</label>
            </div>
            
            <div class="form-group">
                <label>Google Gemini API Key</label>
                <input type="password" name="gemini_key" value="<?php echo esc_attr(get_option('wooai_gemini_key')); ?>" style="width:100%" />
            </div>
            
            <div class="form-group">
                <label>OpenAI API Key</label>
                <input type="password" name="openai_key" value="<?php echo esc_attr(get_option('wooai_openai_key')); ?>" style="width:100%" />
            </div>
            
            <div class="form-group">
                <label>Claude API Key</label>
                <input type="password" name="claude_key" value="<?php echo esc_attr(get_option('wooai_claude_key')); ?>" style="width:100%" />
            </div>
        </div>
        
        <button type="submit" class="button button-primary button-large">Save Changes</button>
    </form>
</div>
<script>
jQuery(function($){
    $('#settings-form').submit(function(e){
        e.preventDefault();
        $.post(wooaiAdmin.ajax_url, {
            action: 'wooai_save_settings',
            nonce: wooaiAdmin.nonce,
            greeting: $('[name="greeting"]').val(),
            color: $('[name="color"]').val(),
            provider: $('[name="provider"]:checked').val(),
            gemini_key: $('[name="gemini_key"]').val(),
            openai_key: $('[name="openai_key"]').val(),
            claude_key: $('[name="claude_key"]').val()
        }, function(r){
            if(r.success) alert('Settings saved successfully!');
        });
    });
});
</script>
