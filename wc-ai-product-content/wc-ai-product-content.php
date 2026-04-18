<?php
/**
 * Plugin Name: CISAI WooCommerce AI Product Content + Auto-Correction
 * Description: Generate and auto-correct WooCommerce product titles/descriptions with OpenAI.
 * Version: 1.0.0
 * Author: CISAI
 */

if (!defined('ABSPATH')) exit;

class WC_AI_Product_Content {
    const OPT_KEY = 'wc_ai_pc_settings';
    const NONCE_ACTION = 'wc_ai_pc_nonce_action';
    const NONCE_NAME = 'wc_ai_pc_nonce';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);

        add_action('add_meta_boxes', [$this, 'add_product_metabox']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        add_action('wp_ajax_wc_ai_pc_generate', [$this, 'ajax_generate']);
        add_action('wp_ajax_wc_ai_pc_correct', [$this, 'ajax_correct']);

        add_action('save_post_product', [$this, 'maybe_autocorrect_on_save'], 20, 3);
    }

    public function get_settings(): array {
        $defaults = [
            'api_key' => '',
            'model' => 'gpt-5.2',
            'temperature' => 0.4,
            'max_output_tokens' => 800,
            'brand_name' => '',
            'tone' => 'clear, persuasive, not hypey',
            'language' => 'English',
            'autocorrect_on_save' => 0,
            'write_seo' => 1,
            'disclaimer' => 1,
        ];
        $saved = get_option(self::OPT_KEY, []);
        return wp_parse_args(is_array($saved) ? $saved : [], $defaults);
    }

    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            'AI Product Content',
            'AI Product Content',
            'manage_woocommerce',
            'wc-ai-product-content',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('wc_ai_pc_group', self::OPT_KEY, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_settings'],
            'default' => [],
        ]);
    }

    public function sanitize_settings($in) {
        $in = is_array($in) ? $in : [];
        $out = [];
        $out['api_key'] = isset($in['api_key']) ? sanitize_text_field($in['api_key']) : '';
        $out['model'] = isset($in['model']) ? sanitize_text_field($in['model']) : 'gpt-5.2';
        $out['temperature'] = isset($in['temperature']) ? floatval($in['temperature']) : 0.4;
        $out['max_output_tokens'] = isset($in['max_output_tokens']) ? intval($in['max_output_tokens']) : 800;
        $out['brand_name'] = isset($in['brand_name']) ? sanitize_text_field($in['brand_name']) : '';
        $out['tone'] = isset($in['tone']) ? sanitize_text_field($in['tone']) : 'clear, persuasive, not hypey';
        $out['language'] = isset($in['language']) ? sanitize_text_field($in['language']) : 'English';
        $out['autocorrect_on_save'] = !empty($in['autocorrect_on_save']) ? 1 : 0;
        $out['write_seo'] = !empty($in['write_seo']) ? 1 : 0;
        $out['disclaimer'] = !empty($in['disclaimer']) ? 1 : 0;
        return $out;
    }

    public function render_settings_page() {
        if (!current_user_can('manage_woocommerce')) return;
        $s = $this->get_settings();
        ?>
        <div class="wrap">
            <h1>AI Product Content Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wc_ai_pc_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="wc_ai_pc_api_key">OpenAI API Key</label></th>
                        <td>
                            <input id="wc_ai_pc_api_key" type="password" class="regular-text"
                                name="<?php echo esc_attr(self::OPT_KEY); ?>[api_key]"
                                value="<?php echo esc_attr($s['api_key']); ?>" autocomplete="off" />
                            <p class="description">Keep this secret. Stored in WP options.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wc_ai_pc_model">Model</label></th>
                        <td>
                            <input id="wc_ai_pc_model" type="text" class="regular-text"
                                name="<?php echo esc_attr(self::OPT_KEY); ?>[model]"
                                value="<?php echo esc_attr($s['model']); ?>" />
                            <p class="description">Example: gpt-5.2</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wc_ai_pc_temp">Temperature</label></th>
                        <td>
                            <input id="wc_ai_pc_temp" type="number" step="0.1" min="0" max="1"
                                name="<?php echo esc_attr(self::OPT_KEY); ?>[temperature]"
                                value="<?php echo esc_attr($s['temperature']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wc_ai_pc_tokens">Max Output Tokens</label></th>
                        <td>
                            <input id="wc_ai_pc_tokens" type="number" step="50" min="200" max="4000"
                                name="<?php echo esc_attr(self::OPT_KEY); ?>[max_output_tokens]"
                                value="<?php echo esc_attr($s['max_output_tokens']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wc_ai_pc_brand">Brand Name (optional)</label></th>
                        <td>
                            <input id="wc_ai_pc_brand" type="text" class="regular-text"
                                name="<?php echo esc_attr(self::OPT_KEY); ?>[brand_name]"
                                value="<?php echo esc_attr($s['brand_name']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wc_ai_pc_tone">Tone</label></th>
                        <td>
                            <input id="wc_ai_pc_tone" type="text" class="regular-text"
                                name="<?php echo esc_attr(self::OPT_KEY); ?>[tone]"
                                value="<?php echo esc_attr($s['tone']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wc_ai_pc_lang">Language</label></th>
                        <td>
                            <input id="wc_ai_pc_lang" type="text" class="regular-text"
                                name="<?php echo esc_attr(self::OPT_KEY); ?>[language]"
                                value="<?php echo esc_attr($s['language']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Options</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(self::OPT_KEY); ?>[autocorrect_on_save]" value="1"
                                    <?php checked($s['autocorrect_on_save'], 1); ?> />
                                Auto-correct description on product save (only if fields are non-empty)
                            </label>
                            <br />
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(self::OPT_KEY); ?>[write_seo]" value="1"
                                    <?php checked($s['write_seo'], 1); ?> />
                                Generate SEO focus keyword + meta description
                            </label>
                            <br />
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(self::OPT_KEY); ?>[disclaimer]" value="1"
                                    <?php checked($s['disclaimer'], 1); ?> />
                                Add internal “AI-generated” marker (stored in post meta, not shown to customers)
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }

    public function add_product_metabox() {
        add_meta_box(
            'wc_ai_pc_box',
            'AI: Content + Auto-Correction',
            [$this, 'render_metabox'],
            'product',
            'side',
            'high'
        );
    }

    public function render_metabox($post) {
        if (!current_user_can('edit_post', $post->ID)) return;
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $sku = '';
        if (function_exists('wc_get_product')) {
            $p = wc_get_product($post->ID);
            if ($p) $sku = $p->get_sku();
        }
        $brand = get_post_meta($post->ID, '_wc_ai_brand', true);
        ?>
        <p style="margin-top:0;">
            <strong>Fast workflow:</strong><br>
            1) Fill key details (features, materials, size, etc.)<br>
            2) Click Generate → Apply
        </p>

        <label for="wc_ai_pc_notes"><strong>Product Details / Notes</strong></label>
        <textarea id="wc_ai_pc_notes" style="width:100%;min-height:120px;"
            placeholder="Example: Material, capacity, use-case, target audience, warranty, colors, what's in the box..."></textarea>

        <p style="margin:10px 0 0;">
            <label><strong>SKU (optional)</strong></label><br>
            <input id="wc_ai_pc_sku" type="text" style="width:100%;" value="<?php echo esc_attr($sku); ?>" placeholder="Auto-detected or type" />
        </p>

        <p style="margin:10px 0 0;">
            <label><strong>Brand (optional)</strong></label><br>
            <input id="wc_ai_pc_brand" type="text" style="width:100%;" value="<?php echo esc_attr($brand); ?>" placeholder="Brand name" />
        </p>

        <p style="margin:10px 0;">
            <button type="button" class="button button-primary" id="wc_ai_pc_btn_generate">Generate</button>
            <button type="button" class="button" id="wc_ai_pc_btn_correct">Auto-correct</button>
        </p>

        <div id="wc_ai_pc_status" style="font-size:12px;line-height:1.4;"></div>

        <hr/>

        <p style="margin:0;"><strong>Apply results:</strong></p>
        <p style="margin:8px 0 0;">
            <button type="button" class="button" id="wc_ai_pc_apply_title">Apply Title</button>
            <button type="button" class="button" id="wc_ai_pc_apply_short">Apply Short</button>
        </p>
        <p style="margin:8px 0 0;">
            <button type="button" class="button" id="wc_ai_pc_apply_long">Apply Long</button>
        </p>
        <p style="margin:8px 0 0;">
            <button type="button" class="button" id="wc_ai_pc_apply_seo">Apply SEO</button>
        </p>

        <input type="hidden" id="wc_ai_pc_last_json" value="" />
        <?php
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'product') return;

        wp_enqueue_script(
            'wc-ai-pc-admin',
            plugins_url('wc-ai-pc-admin.js', __FILE__),
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('wc-ai-pc-admin', 'WCAIPC', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'postId' => get_the_ID(),
        ]);
    }

    private function openai_call(string $input, array $settings): array {
        $api_key = $settings['api_key'] ?? '';
        if (!$api_key) {
            return ['ok' => false, 'error' => 'Missing API key in WooCommerce → AI Product Content settings.'];
        }

        $body = [
            'model' => $settings['model'] ?? 'gpt-5.2',
            'input' => $input,
            'temperature' => floatval($settings['temperature'] ?? 0.4),
            'max_output_tokens' => intval($settings['max_output_tokens'] ?? 800),
        ];

        $resp = wp_remote_post('https://api.openai.com/v1/responses', [
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => wp_json_encode($body),
        ]);

        if (is_wp_error($resp)) {
            return ['ok' => false, 'error' => $resp->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($resp);
        $raw  = wp_remote_retrieve_body($resp);
        $json = json_decode($raw, true);

        if ($code < 200 || $code >= 300) {
            $msg = 'OpenAI API error';
            if (is_array($json) && isset($json['error']['message'])) $msg = $json['error']['message'];
            return ['ok' => false, 'error' => $msg, 'status' => $code];
        }

        $text = '';
        if (is_array($json) && !empty($json['output']) && is_array($json['output'])) {
            foreach ($json['output'] as $outItem) {
                if (!empty($outItem['content']) && is_array($outItem['content'])) {
                    foreach ($outItem['content'] as $c) {
                        if (($c['type'] ?? '') === 'output_text' && isset($c['text'])) {
                            $text .= $c['text'];
                        }
                    }
                }
            }
        }

        if (!$text) {
            return ['ok' => false, 'error' => 'No output_text returned from model.'];
        }

        return ['ok' => true, 'text' => $text, 'raw' => $json];
    }

    private function build_generation_prompt(int $product_id, string $notes, string $sku, string $brand_override): string {
        $s = $this->get_settings();
        $product = wc_get_product($product_id);
        $existing_title = $product ? $product->get_name() : '';
        $existing_short = $product ? $product->get_short_description() : '';
        $existing_long  = $product ? $product->get_description() : '';

        $brand = trim($brand_override) ?: trim($s['brand_name'] ?? '');
        if ($brand_override) update_post_meta($product_id, '_wc_ai_brand', $brand_override);

        $seo_on = !empty($s['write_seo']);

        $schema = [
            "title" => "string",
            "short_description" => "string (plain text, 2-4 lines, no markdown)",
            "long_description_html" => "string (HTML allowed: <p>, <ul>, <li>, <strong>)",
            "bullet_points" => ["string", "string", "string", "string", "string"],
            "seo_focus_keyword" => $seo_on ? "string" : "",
            "seo_meta_description" => $seo_on ? "string (<=155 chars)" : "",
        ];

        $tone = $s['tone'] ?? 'clear, persuasive, not hypey';
        $lang = $s['language'] ?? 'English';

        return
"You're writing a WooCommerce product page.\n".
"Language: {$lang}\n".
"Tone: {$tone}\n".
($brand ? "Brand: {$brand}\n" : "").
($sku ? "SKU: {$sku}\n" : "").
"Hard rules:\n".
"- No fake claims, no medical claims, no 'best in the world'.\n".
"- Keep it scannable and conversion-focused.\n".
"- If details are missing, do NOT invent them; write around unknowns.\n".
"- Return ONLY valid JSON, nothing else.\n".
"\n".
"Existing content (use only if helpful):\n".
"Title: {$existing_title}\n".
"Short: {$existing_short}\n".
"Long: {$existing_long}\n".
"\n".
"Notes / specs from user:\n{$notes}\n".
"\n".
"Return JSON with this shape:\n".wp_json_encode($schema, JSON_PRETTY_PRINT);
    }

    private function build_correction_prompt(int $product_id): string {
        $s = $this->get_settings();
        $product = wc_get_product($product_id);
        $title = $product ? $product->get_name() : '';
        $short = $product ? $product->get_short_description() : '';
        $long  = $product ? $product->get_description() : '';

        $tone = $s['tone'] ?? 'clear, persuasive, not hypey';
        $lang = $s['language'] ?? 'English';

        $schema = [
            "title" => "string (only if it needs correction; otherwise same)",
            "short_description" => "string (corrected, plain text)",
            "long_description_html" => "string (corrected HTML, keep meaning, keep structure)",
        ];

        return
"Fix grammar, clarity, and phrasing for a WooCommerce product page.\n".
"Language: {$lang}\n".
"Tone: {$tone}\n".
"Rules:\n".
"- Keep meaning the same.\n".
"- Do not add new specs/claims.\n".
"- Return ONLY valid JSON.\n".
"\n".
"Current:\n".
"Title: {$title}\n".
"Short: {$short}\n".
"Long: {$long}\n".
"\n".
"Return JSON with this shape:\n".wp_json_encode($schema, JSON_PRETTY_PRINT);
    }

    private function decode_json_safely(string $text): array {
        $text = trim($text);
        $text = preg_replace('/^```(json)?/i', '', $text);
        $text = preg_replace('/```$/', '', $text);
        $text = trim($text);

        $data = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return ['ok' => false, 'error' => 'Model did not return valid JSON. Raw output: ' . mb_substr($text, 0, 4000)];
        }
        return ['ok' => true, 'data' => $data];
    }

    public function ajax_generate() {
        if (!current_user_can('edit_products')) wp_send_json_error(['message' => 'No permission'], 403);
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);
        $notes   = sanitize_textarea_field($_POST['notes'] ?? '');
        $sku     = sanitize_text_field($_POST['sku'] ?? '');
        $brand   = sanitize_text_field($_POST['brand'] ?? '');

        if (!$post_id) wp_send_json_error(['message' => 'Missing post_id'], 400);

        $settings = $this->get_settings();
        $prompt = $this->build_generation_prompt($post_id, $notes, $sku, $brand);

        $api = $this->openai_call($prompt, $settings);
        if (!$api['ok']) wp_send_json_error(['message' => $api['error'] ?? 'API error'], $api['status'] ?? 500);

        $decoded = $this->decode_json_safely($api['text']);
        if (!$decoded['ok']) wp_send_json_error(['message' => $decoded['error']], 422);

        if (!empty($settings['disclaimer'])) {
            update_post_meta($post_id, '_wc_ai_generated_at', time());
        }

        wp_send_json_success(['data' => $decoded['data']]);
    }

    public function ajax_correct() {
        if (!current_user_can('edit_products')) wp_send_json_error(['message' => 'No permission'], 403);
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) wp_send_json_error(['message' => 'Missing post_id'], 400);

        $settings = $this->get_settings();
        $prompt = $this->build_correction_prompt($post_id);

        $api = $this->openai_call($prompt, $settings);
        if (!$api['ok']) wp_send_json_error(['message' => $api['error'] ?? 'API error'], $api['status'] ?? 500);

        $decoded = $this->decode_json_safely($api['text']);
        if (!$decoded['ok']) wp_send_json_error(['message' => $decoded['error']], 422);

        if (!empty($settings['disclaimer'])) {
            update_post_meta($post_id, '_wc_ai_corrected_at', time());
        }

        wp_send_json_success(['data' => $decoded['data']]);
    }

    public function maybe_autocorrect_on_save($post_id, $post, $update) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $s = $this->get_settings();
        if (empty($s['autocorrect_on_save'])) return;

        if (get_transient('wc_ai_pc_lock_' . $post_id)) return;

        $product = wc_get_product($post_id);
        if (!$product) return;

        $short = $product->get_short_description();
        $long  = $product->get_description();

        if (!trim(wp_strip_all_tags($short)) && !trim(wp_strip_all_tags($long))) return;

        set_transient('wc_ai_pc_lock_' . $post_id, 1, 60);

        $prompt = $this->build_correction_prompt($post_id);
        $api = $this->openai_call($prompt, $s);
        if ($api['ok']) {
            $decoded = $this->decode_json_safely($api['text']);
            if ($decoded['ok']) {
                $d = $decoded['data'];
                if (isset($d['short_description'])) {
                    $product->set_short_description(wp_kses_post($d['short_description']));
                }
                if (isset($d['long_description_html'])) {
                    $product->set_description(wp_kses_post($d['long_description_html']));
                }
                if (isset($d['title']) && trim($d['title'])) {
                    $product->set_name(sanitize_text_field($d['title']));
                }
                $product->save();
            }
        }

        delete_transient('wc_ai_pc_lock_' . $post_id);
    }
}

new WC_AI_Product_Content();
