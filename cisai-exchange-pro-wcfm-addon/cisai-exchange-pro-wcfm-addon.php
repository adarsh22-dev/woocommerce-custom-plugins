<?php
/*
Plugin Name: CISAI Exchange Pro - WCFM Vendor Fields
Description: Shows the same Exchange Configuration fields (Enable Exchange, Max Cap, Allowed Categories, Pricing Source, Custom Pricing Rows) in WCFM vendor product edit. Saves to the same meta keys used by Exchange Pro.
Version: 1.2.3
Author: CISAI
Text Domain: exchange-pro
*/

if ( ! defined('ABSPATH') ) exit;

final class CISAI_ExchangePro_WCFM_Addon {

	const VERSION = '1.2.3';

	private static $instance = null;

	public static function instance() {
		if ( self::$instance === null ) self::$instance = new self();
		return self::$instance;
	}

	private function __construct() {
		add_action('plugins_loaded', [ $this, 'boot' ], 99);
		add_action('init', [ $this, 'boot' ], 99);
	}

	private $booted = false;

	public function boot() {

		if ( $this->booted ) return;

		// Need WCFM vendor environment + your Exchange Pro plugin classes.
		if ( ! $this->has_wcfm() ) return;
		if ( ! $this->has_exchange_pro() ) return;

		$this->booted = true;

		// Render fields in WCFM product manage.
		add_action('after_wcfm_products_manage_general', [ $this, 'render_exchange_panel_in_wcfm' ], 80);

		// Save fields.
		add_action('after_wcfm_products_manage_meta_save', [ $this, 'save_exchange_meta_from_wcfm' ], 50, 2);

		// Assets for WCFM dashboard.
		add_action('wp_enqueue_scripts', [ $this, 'enqueue_assets' ], 50);

		// Show status in WCFM product list.
		add_filter('wcfm_products_additonal_data_hidden', '__return_false');
		add_filter('wcfm_products_additional_info_column_label', function() { return __('Exchange', 'exchange-pro'); });
		add_filter('wcfm_products_additonal_data', [ $this, 'wcfm_products_list_badge' ], 10, 2);

		// Optional: WP Admin Products list column.
		add_filter('manage_edit-product_columns', [ $this, 'admin_products_column' ], 30);
		add_action('manage_product_posts_custom_column', [ $this, 'admin_products_column_render' ], 30, 2);
	}

	private function has_wcfm() {
		return ( function_exists('wcfm_is_vendor') || defined('WCFM_VERSION') || class_exists('WCFM') );
	}

	private function has_exchange_pro() {
		// Your plugin defines these in includes/.
		return class_exists('Exchange_Pro_Database');
	}

	private function is_wcfm_context() {

		// If helper exists, use it.
		if ( function_exists('is_wcfm_page') ) {
			return (bool) is_wcfm_page();
		}

		// Fallback on URL pattern.
		$uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
		if ( strpos($uri, 'wcfm') !== false ) return true;
		if ( strpos($uri, 'products-manage') !== false ) return true;
		if ( strpos($uri, 'products') !== false && strpos($uri, 'dashboard') !== false ) return true;

		return false;
	}

	private function current_product_id() {

		// Query var (pretty endpoints)
		if ( function_exists('get_query_var') ) {
			$qv = get_query_var('wcfm-products-manage');
			if ( $qv ) return intval($qv);
		}

		global $wp, $wp_query;
		if ( isset($wp) && isset($wp->query_vars['wcfm-products-manage']) && $wp->query_vars['wcfm-products-manage'] ) {
			return intval($wp->query_vars['wcfm-products-manage']);
		}
		if ( isset($wp_query) && isset($wp_query->query_vars['wcfm-products-manage']) && $wp_query->query_vars['wcfm-products-manage'] ) {
			return intval($wp_query->query_vars['wcfm-products-manage']);
		}

		// Query string
		if ( isset($_GET['wcfm-products-manage']) ) return intval($_GET['wcfm-products-manage']);
		if ( isset($_GET['product_id']) ) return intval($_GET['product_id']);

		// Common hidden fields
		if ( isset($_REQUEST['pro_id']) ) return intval($_REQUEST['pro_id']);
		if ( isset($_REQUEST['product']) ) return intval($_REQUEST['product']);

		// URL parse fallback
		$uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
		if ( preg_match('~products-manage/(\d+)~', $uri, $m) ) return intval($m[1]);

		return 0;
	}

	public function enqueue_assets() {

		if ( ! is_user_logged_in() ) return;
		if ( function_exists('wcfm_is_vendor') && ! wcfm_is_vendor() && ! current_user_can('manage_woocommerce') ) return;
		if ( ! $this->is_wcfm_context() ) return;

		wp_enqueue_style('cisai-expro-wcfm', plugins_url('assets/css/wcfm-exchange-pro.css', __FILE__), [], self::VERSION);
		wp_enqueue_script('cisai-expro-wcfm', plugins_url('assets/js/wcfm-exchange-pro.js', __FILE__), ['jquery'], self::VERSION, true);

		wp_localize_script('cisai-expro-wcfm', 'cisaiExProWcfm', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce'    => wp_create_nonce('exchange_pro_nonce'),
			'product_id' => $this->current_product_id(),
			'strings'  => [
				'loading' => __('Loading...', 'exchange-pro'),
				'select_brand' => __('Select brand...', 'exchange-pro'),
				'select_model' => __('Select model...', 'exchange-pro'),
				'select_variant' => __('Select variant...', 'exchange-pro'),
			]
		]);
	}

	public function render_exchange_panel_in_wcfm() {

		if ( ! is_user_logged_in() ) return;

		$product_id = $this->current_product_id();

		?>
		<div class="page_collapsible products_manage_vendor_exchange simple variable external grouped booking">
			<label class="wcfmfa fa-sync-alt"></label>
			<?php esc_html_e('Exchange Pro (Device Exchange)', 'exchange-pro'); ?>
		</div>

		<div class="wcfm-container simple variable external grouped booking">
			<div class="wcfm-content">

				<?php if ( ! $product_id ) : ?>
					<div class="cisai-expro-notice">
						<?php esc_html_e('Save the product once, then reopen Edit Product to configure Exchange Pro settings.', 'exchange-pro'); ?>
					</div>
				<?php else : ?>

					<?php
					$enabled = (get_post_meta($product_id, '_exchange_pro_enable', true) === 'yes') ? 'yes' : 'no';
					$max_cap = get_post_meta($product_id, '_exchange_pro_max_cap', true);
					$allowed = get_post_meta($product_id, '_exchange_pro_allowed_categories', true);
					if ( ! is_array($allowed) ) $allowed = [];

					$pricing_source = get_post_meta($product_id, '_exchange_pro_pricing_source', true);
					if ( empty($pricing_source) ) {
						$legacy_custom = get_post_meta($product_id, '_exchange_pro_custom_pricing', true);
						$pricing_source = ($legacy_custom === 'yes') ? 'custom' : 'global';
					}
					if ( $pricing_source !== 'custom' ) $pricing_source = 'global';

					$rows = get_post_meta($product_id, '_exchange_pro_pricing_data', true);
					if ( ! is_array($rows) ) $rows = [];

					$db = Exchange_Pro_Database::get_instance();
					$categories = $db ? $db->get_categories('') : [];
					?>

					<div class="cisai-expro-wrap" data-product-id="<?php echo esc_attr($product_id); ?>">

						<h3 class="cisai-expro-title"><?php esc_html_e('Exchange Configuration', 'exchange-pro'); ?></h3>

						<div class="cisai-expro-field">
							<label class="cisai-expro-checkbox">
								<input type="checkbox" name="cisai_expro_enable" value="yes" <?php checked($enabled, 'yes'); ?>>
								<strong><?php esc_html_e('Enable Exchange', 'exchange-pro'); ?></strong>
								<span class="cisai-expro-sub"><?php esc_html_e('Allow exchange for this product', 'exchange-pro'); ?></span>
							</label>
						</div>

						<div class="cisai-expro-settings" style="<?php echo ($enabled === 'yes') ? '' : 'display:none;'; ?>">

							<div class="cisai-expro-field">
								<label><strong><?php esc_html_e('Max Exchange Cap (%)', 'exchange-pro'); ?></strong></label>
								<input type="number" name="cisai_expro_max_cap" value="<?php echo esc_attr($max_cap); ?>" min="0" max="100" step="1" placeholder="e.g. 50">
							</div>

							<div class="cisai-expro-field">
								<label><strong><?php esc_html_e('Allowed Device Categories', 'exchange-pro'); ?></strong></label>
								<div class="cisai-expro-help"><?php esc_html_e('Select which device categories customers can exchange for this product', 'exchange-pro'); ?></div>
								<select name="cisai_expro_allowed_categories[]" class="cisai-expro-multi" multiple="multiple">
									<?php if (is_array($categories)) : foreach ($categories as $cat) : ?>
										<option value="<?php echo (int)$cat->id; ?>" <?php selected( in_array((int)$cat->id, array_map('intval',$allowed), true) ); ?>>
											<?php echo esc_html($cat->name); ?>
										</option>
									<?php endforeach; endif; ?>
								</select>
								<div class="cisai-expro-help"><?php esc_html_e('Hold Ctrl/Cmd to select multiple categories.', 'exchange-pro'); ?></div>
							</div>

							<div class="cisai-expro-field">
								<label><strong><?php esc_html_e('Exchange Pricing Source', 'exchange-pro'); ?></strong></label>
								<div class="cisai-expro-help"><?php esc_html_e("Choose where the popup loads devices/prices from. Global = Devices & Pricing Management. Custom = this product's custom rows only.", 'exchange-pro'); ?></div>

								<label class="cisai-expro-radio">
									<input type="radio" name="cisai_expro_pricing_source" value="global" <?php checked($pricing_source, 'global'); ?>>
									<?php esc_html_e('Use Global Device Pricing', 'exchange-pro'); ?>
								</label>

								<label class="cisai-expro-radio">
									<input type="radio" name="cisai_expro_pricing_source" value="custom" <?php checked($pricing_source, 'custom'); ?>>
									<?php esc_html_e('Use Custom Pricing for this Product', 'exchange-pro'); ?>
								</label>
							</div>

							<div class="cisai-expro-custom" style="<?php echo ($pricing_source === 'custom') ? '' : 'display:none;'; ?>">

								<h4><?php esc_html_e('Custom Exchange Pricing', 'exchange-pro'); ?></h4>
								<div class="cisai-expro-help">
									<?php esc_html_e('Set custom exchange values for specific devices. These will override the global pricing matrix.', 'exchange-pro'); ?>
								</div>

								<button type="button" class="button cisai-expro-add-row">+ <?php esc_html_e('Add Device Pricing', 'exchange-pro'); ?></button>

								<div class="cisai-expro-tip">
									<?php esc_html_e('Tip: pick a Variant to lock this row to an exact device. When custom pricing is enabled, ONLY the variants you add here will be eligible for exchange for this product.', 'exchange-pro'); ?>
								</div>

								<div class="cisai-expro-rows" data-next-index="<?php echo esc_attr(count($rows)); ?>">
									<?php
									if ( ! empty($rows) ) {
										foreach ($rows as $i => $row) {
											$this->render_row($i, $row, $categories);
										}
									}
									?>
								</div>

								<template id="cisai-expro-row-template">
									<?php $this->render_row('__INDEX__', [], $categories, true); ?>
								</template>

							</div>

						</div>

					</div>

				<?php endif; ?>

			</div>
		</div>
		<?php
	}

	private function render_row($index, $row, $categories, $is_template = false) {

		$name_idx = $is_template ? '${idx}' : (string) $index;

		$cat_id = intval($row['category_id'] ?? 0);
		$brand_id = intval($row['brand_id'] ?? 0);
		$model_id = intval($row['model_id'] ?? 0);
		$variant_id = intval($row['variant_id'] ?? 0);
		$device_name = (string) ($row['device_name'] ?? '');

		$excellent = $row['excellent'] ?? '';
		$good = $row['good'] ?? '';
		$fair = $row['fair'] ?? '';
		$poor = $row['poor'] ?? '';

		?>
		<div class="cisai-expro-row">

			<div class="cisai-expro-row-grid">
				<div>
					<label><?php esc_html_e('Category', 'exchange-pro'); ?></label>
					<select class="cisai-expro-category" name="cisai_expro_pricing_data[<?php echo $name_idx; ?>][category_id]">
						<option value=""><?php esc_html_e('Select category...', 'exchange-pro'); ?></option>
						<?php if (is_array($categories)) : foreach ($categories as $cat) : ?>
							<option value="<?php echo (int)$cat->id; ?>" <?php selected($cat_id, (int)$cat->id); ?>>
								<?php echo esc_html($cat->name); ?>
							</option>
						<?php endforeach; endif; ?>
					</select>
				</div>

				<div>
					<label><?php esc_html_e('Brand', 'exchange-pro'); ?></label>
					<select class="cisai-expro-brand" name="cisai_expro_pricing_data[<?php echo $name_idx; ?>][brand_id]" data-selected="<?php echo esc_attr($brand_id ?: ''); ?>">
						<option value=""><?php esc_html_e('Select brand...', 'exchange-pro'); ?></option>
					</select>
				</div>

				<div>
					<label><?php esc_html_e('Model', 'exchange-pro'); ?></label>
					<select class="cisai-expro-model" name="cisai_expro_pricing_data[<?php echo $name_idx; ?>][model_id]" data-selected="<?php echo esc_attr($model_id ?: ''); ?>">
						<option value=""><?php esc_html_e('Select model...', 'exchange-pro'); ?></option>
					</select>
				</div>

				<div>
					<label><?php esc_html_e('Variant', 'exchange-pro'); ?></label>
					<select class="cisai-expro-variant" name="cisai_expro_pricing_data[<?php echo $name_idx; ?>][variant_id]" data-selected="<?php echo esc_attr($variant_id ?: ''); ?>">
						<option value=""><?php esc_html_e('Select variant...', 'exchange-pro'); ?></option>
					</select>
				</div>
			</div>

			<input type="hidden" class="cisai-expro-device-name" name="cisai_expro_pricing_data[<?php echo $name_idx; ?>][device_name]" value="<?php echo esc_attr($device_name); ?>">

			<div class="cisai-expro-row-grid prices">
				<div>
					<label><?php esc_html_e('Excellent', 'exchange-pro'); ?></label>
					<input type="number" name="cisai_expro_pricing_data[<?php echo $name_idx; ?>][excellent]" value="<?php echo esc_attr($excellent); ?>" step="0.01" min="0">
				</div>
				<div>
					<label><?php esc_html_e('Good', 'exchange-pro'); ?></label>
					<input type="number" name="cisai_expro_pricing_data[<?php echo $name_idx; ?>][good]" value="<?php echo esc_attr($good); ?>" step="0.01" min="0">
				</div>
				<div>
					<label><?php esc_html_e('Fair', 'exchange-pro'); ?></label>
					<input type="number" name="cisai_expro_pricing_data[<?php echo $name_idx; ?>][fair]" value="<?php echo esc_attr($fair); ?>" step="0.01" min="0">
				</div>
				<div>
					<label><?php esc_html_e('Poor', 'exchange-pro'); ?></label>
					<input type="number" name="cisai_expro_pricing_data[<?php echo $name_idx; ?>][poor]" value="<?php echo esc_attr($poor); ?>" step="0.01" min="0">
				</div>
			</div>

			<div class="cisai-expro-row-actions">
				<button type="button" class="button cisai-expro-remove-row"><?php esc_html_e('Remove', 'exchange-pro'); ?></button>
			</div>

		</div>
		<?php
	}

	public function save_exchange_meta_from_wcfm( $product_id, $form_data ) {

		// Require vendor/admin
		if ( function_exists('wcfm_is_vendor') && ! wcfm_is_vendor() && ! current_user_can('manage_woocommerce') ) return;

		// WCFM may not include custom meta keys (especially those starting with "_") in $form_data.
		// So we read from request and fall back to $form_data.
		$raw = [];

		if ( isset($_POST['wcfm_products_manage_form']) ) {
			if ( is_array($_POST['wcfm_products_manage_form']) ) {
				$raw = $_POST['wcfm_products_manage_form'];
			} elseif ( is_string($_POST['wcfm_products_manage_form']) ) {
				parse_str($_POST['wcfm_products_manage_form'], $raw);
			}
		}

		// Merge with $form_data (in case WCFM provided some fields there)
		if ( is_array($form_data) ) {
			$raw = array_merge($raw, $form_data);
		}

		// Enabled checkbox (checked => value "yes")
		$enabled = (isset($raw['cisai_expro_enable']) && $raw['cisai_expro_enable'] === 'yes') ? 'yes' : 'no';
		update_post_meta($product_id, '_exchange_pro_enable', $enabled);

		// Max cap
		if ( isset($raw['cisai_expro_max_cap']) ) {
			$cap = sanitize_text_field($raw['cisai_expro_max_cap']);
			update_post_meta($product_id, '_exchange_pro_max_cap', $cap);
		}

		// Allowed categories
		if ( isset($raw['cisai_expro_allowed_categories']) ) {
			$vals = $raw['cisai_expro_allowed_categories'];
			if ( ! is_array($vals) ) $vals = [ $vals ];
			$vals = array_values(array_filter(array_map('intval', $vals)));
			update_post_meta($product_id, '_exchange_pro_allowed_categories', $vals);
		} else {
			update_post_meta($product_id, '_exchange_pro_allowed_categories', []);
		}

		// Pricing source
		$src = isset($raw['cisai_expro_pricing_source']) ? sanitize_text_field($raw['cisai_expro_pricing_source']) : 'global';
		if ( $src !== 'custom' ) $src = 'global';
		update_post_meta($product_id, '_exchange_pro_pricing_source', $src);
		update_post_meta($product_id, '_exchange_pro_custom_pricing', ($src === 'custom') ? 'yes' : 'no');

		// Custom rows
		if ( isset($raw['cisai_expro_pricing_data']) && is_array($raw['cisai_expro_pricing_data']) ) {
			$clean = [];
			foreach ($raw['cisai_expro_pricing_data'] as $row) {
				if ( ! is_array($row) ) continue;

				$variant_id = intval($row['variant_id'] ?? 0);
				if ( ! $variant_id ) continue; // require variant lock

				$clean[] = [
					'category_id' => intval($row['category_id'] ?? 0),
					'brand_id'    => intval($row['brand_id'] ?? 0),
					'model_id'    => intval($row['model_id'] ?? 0),
					'variant_id'  => $variant_id,
					'device_name' => sanitize_text_field($row['device_name'] ?? ''),
					'excellent'   => $this->sanitize_money($row['excellent'] ?? ''),
					'good'        => $this->sanitize_money($row['good'] ?? ''),
					'fair'        => $this->sanitize_money($row['fair'] ?? ''),
					'poor'        => $this->sanitize_money($row['poor'] ?? ''),
				];
			}
			update_post_meta($product_id, '_exchange_pro_pricing_data', $clean);
		} else {
			// If vendor removed all rows
			if ( get_post_meta($product_id, '_exchange_pro_pricing_source', true) === 'custom' ) {
				update_post_meta($product_id, '_exchange_pro_pricing_data', []);
			}
		}
	}

	private function sanitize_money($v) {
		if ( $v === '' || $v === null ) return '';
		$v = preg_replace('/[^0-9\.\-]/', '', (string)$v);
		return (string) max(0, (float)$v);
	}

	public function wcfm_products_list_badge($data, $product_id) {
		$enabled = (get_post_meta($product_id, '_exchange_pro_enable', true) === 'yes');
		if ( ! $enabled ) return '<span style="color:#999;">Disabled</span>';

		$src = get_post_meta($product_id, '_exchange_pro_pricing_source', true);
		if ( empty($src) ) {
			$legacy = get_post_meta($product_id, '_exchange_pro_custom_pricing', true);
			$src = ($legacy === 'yes') ? 'custom' : 'global';
		}

		if ( $src === 'custom' ) {
			$rows = get_post_meta($product_id, '_exchange_pro_pricing_data', true);
			$cnt = is_array($rows) ? count($rows) : 0;
			return '<span style="color:green;font-weight:600;">Enabled</span> <span style="color:#555;">(Custom · '.intval($cnt).')</span>';
		}

		return '<span style="color:green;font-weight:600;">Enabled</span> <span style="color:#555;">(Global)</span>';
	}

	public function admin_products_column($cols) {
		$cols['cisai_expro'] = __('Exchange', 'exchange-pro');
		return $cols;
	}

	public function admin_products_column_render($col, $post_id) {
		if ( $col !== 'cisai_expro' ) return;
		echo wp_kses_post( $this->wcfm_products_list_badge('', $post_id) );
	}
}

CISAI_ExchangePro_WCFM_Addon::instance();
