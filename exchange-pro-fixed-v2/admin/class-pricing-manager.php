<?php
if (!defined('ABSPATH')) exit;

class Exchange_Pro_Pricing_Manager {
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
    }
    
    public function render() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Permission denied', 'exchange-pro'));
        }

        $cat_filter = isset($_GET['category_id']) ? absint($_GET['category_id']) : 0;
        $categories = $this->db->get_categories();

        // AUTO: derive the full matrix from the canonical device tables.
        $matrix = $this->db->get_pricing_matrix($cat_filter);

        ?>
        <div class="wrap">
            <h1><?php _e('Pricing Matrix', 'exchange-pro'); ?></h1>
            <p><?php _e('Manage condition-based pricing for all device variants.', 'exchange-pro'); ?></p>

            <div class="card" style="max-width: 1400px; padding: 18px;">
                <form method="get" style="display:flex; gap:12px; align-items:center;">
                    <input type="hidden" name="page" value="exchange-pricing" />
                    <label for="exchange_pro_category" style="font-weight:600;"><?php _e('Category', 'exchange-pro'); ?></label>
                    <select id="exchange_pro_category" name="category_id" style="min-width:240px;">
                        <option value="0"><?php _e('All categories', 'exchange-pro'); ?></option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo esc_attr($c->id); ?>" <?php selected($cat_filter, $c->id); ?>>
                                <?php echo esc_html($c->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="button button-primary" type="submit"><?php _e('Filter', 'exchange-pro'); ?></button>

                    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=exchange-devices')); ?>" style="margin-left:auto;">
                        <?php _e('Go to Devices & Pricing Management', 'exchange-pro'); ?>
                    </a>
                </form>

                <hr />

                <table class="widefat striped" style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th><?php _e('Category', 'exchange-pro'); ?></th>
                            <th><?php _e('Brand', 'exchange-pro'); ?></th>
                            <th><?php _e('Model', 'exchange-pro'); ?></th>
                            <th><?php _e('Variant', 'exchange-pro'); ?></th>
                            <th><?php _e('Excellent', 'exchange-pro'); ?></th>
                            <th><?php _e('Good', 'exchange-pro'); ?></th>
                            <th><?php _e('Fair', 'exchange-pro'); ?></th>
                            <th><?php _e('Poor', 'exchange-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($matrix)): ?>
                            <tr>
                                <td colspan="8">
                                    <?php _e('No pricing data found. Add devices/models/variants and set prices in Devices & Pricing Management.', 'exchange-pro'); ?>
                                </td>
                            </tr>
                        <?php else: foreach ($matrix as $row): ?>
                            <tr>
                                <td><?php echo esc_html($row->category_name); ?></td>
                                <td><?php echo esc_html($row->brand_name); ?></td>
                                <td><?php echo esc_html($row->model_name); ?></td>
                                <td><?php echo esc_html($row->variant_name); ?></td>
                                <td><?php echo esc_html($row->excellent_price); ?></td>
                                <td><?php echo esc_html($row->good_price); ?></td>
                                <td><?php echo esc_html($row->fair_price); ?></td>
                                <td><?php echo esc_html($row->poor_price); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>

                <p class="description" style="margin-top:10px;">
                    <?php _e('AUTO mode: This page is a real-time view of the canonical pricing data from Devices & Pricing Management. Edit prices there to update this matrix.', 'exchange-pro'); ?>
                </p>
            </div>
        </div>
        <?php
    }
}
