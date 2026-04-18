
<?php
add_action('wp_ajax_wv_save', function () {

    check_ajax_referer('wv_nonce','nonce');

    $vid   = intval($_POST['vid']);
    $price = wc_format_decimal($_POST['price']);
    $stock = intval($_POST['stock']);
    $sku   = sanitize_text_field($_POST['sku']);

    $v = wc_get_product($vid);
    if (!$v) wp_send_json_error('Invalid');

    $parent = get_post($v->get_parent_id());
    if ((int)$parent->post_author !== get_current_user_id())
        wp_send_json_error('Denied');

    if ($sku !== '') $v->set_sku($sku);
    $v->set_regular_price($price);
    $v->set_stock_quantity($stock);
    $v->save();

    // Recalculate stats
    $vendor = get_current_user_id();
    $products = get_posts(['post_type'=>'product','author'=>$vendor,'posts_per_page'=>-1]);

    $stock_value = $variations = $low = $out = 0;
    $chart = [];

    foreach ($products as $p) {
        $product = wc_get_product($p->ID);
        if (!$product || !$product->is_type('variable')) continue;
        foreach ($product->get_children() as $cid) {
            $cv = wc_get_product($cid);
            $qty = (int)$cv->get_stock_quantity();
            $pr  = (float)$cv->get_price();
            $variations++;
            $stock_value += $qty * $pr;
            if ($qty == 0) $out++;
            if ($qty > 0 && $qty <= 5) $low++;
            $chart[$cv->get_sku()] = $qty;
        }
    }

    arsort($chart);
    $chart = array_slice($chart,0,10,true);

    wp_send_json_success([
        'message' => 'Variation updated successfully',
        'cards' => [
            'stock_value' => number_format($stock_value),
            'variations'  => $variations,
            'low'         => $low,
            'out'         => $out
        ],
        'chart' => $chart
    ]);
});
