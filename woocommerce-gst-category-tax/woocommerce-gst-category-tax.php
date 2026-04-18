<?php
/*
Plugin Name: CISAI WooCommerce GST Category Tax
Description: Assign GST tax classes to WooCommerce product categories.
Version: 1.0
Author: Adarsh Singh
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add tax class field to category edit page
add_action('product_cat_add_form_fields', 'gst_category_tax_add_field');
add_action('product_cat_edit_form_fields', 'gst_category_tax_edit_field', 10, 2);

function gst_category_tax_add_field() {
    ?>
    <div class="form-field">
        <label for="gst_tax_class"><?php _e('GST Tax Class', 'woocommerce-gst-category-tax'); ?></label>
        <select name="gst_tax_class" id="gst_tax_class" class="postform">
            <option value=""><?php _e('Select a tax class', 'woocommerce-gst-category-tax'); ?></option>
            <?php
            $tax_classes = WC_Tax::get_tax_classes();
            foreach ($tax_classes as $class) {
                echo '<option value="' . esc_attr($class) . '">' . esc_html(wc_clean($class)) . '</option>';
            }
            ?>
        </select>
        <p class="description"><?php _e('Select the GST tax class for this category.', 'woocommerce-gst-category-tax'); ?></p>
    </div>
    <?php
}

function gst_category_tax_edit_field($term) {
    $tax_class = get_term_meta($term->term_id, 'gst_tax_class', true);
    ?>
    <tr class="form-field">
        <th><label for="gst_tax_class"><?php _e('GST Tax Class', 'woocommerce-gst-category-tax'); ?></label></th>
        <td>
            <select name="gst_tax_class" id="gst_tax_class" class="postform">
                <option value=""><?php _e('Select a tax class', 'woocommerce-gst-category-tax'); ?></option>
                <?php
                $tax_classes = WC_Tax::get_tax_classes();
                foreach ($tax_classes as $class) {
                    $selected = selected($tax_class, $class, false);
                    echo '<option value="' . esc_attr($class) . '" ' . $selected . '>' . esc_html(wc_clean($class)) . '</option>';
                }
                ?>
            </td>
        </tr>
    <?php
}

// Save tax class metadata
add_action('created_product_cat', 'gst_category_tax_save_field');
add_action('edited_product_cat', 'gst_category_tax_save_field');

function gst_category_tax_save_field($term_id) {
    if (isset($_POST['gst_tax_class'])) {
        update_term_meta($term_id, 'gst_tax_class', sanitize_text_field($_POST['gst_tax_class']));
    } else {
        delete_term_meta($term_id, 'gst_tax_class');
    }
}

// Assign tax class to products based on category
add_filter('woocommerce_product_get_tax_class', 'gst_category_tax_assign_to_products', 10, 2);

function gst_category_tax_assign_to_products($tax_class, $product) {
    $categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'ids'));
    if (!empty($categories)) {
        foreach ($categories as $category_id) {
            $category_tax_class = get_term_meta($category_id, 'gst_tax_class', true);
            if (!empty($category_tax_class)) {
                return $category_tax_class; // Return the first matching category's tax class
            }
        }
    }
    return $tax_class; // Fallback to product’s existing tax class
}
