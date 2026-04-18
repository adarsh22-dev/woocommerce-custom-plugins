
<?php
$vendor = get_current_user_id();
$products = get_posts(['post_type'=>'product','author'=>$vendor,'posts_per_page'=>-1]);

$stock_value = $variations = $low = $out = 0;
$chart = [];

foreach ($products as $p) {
 $product = wc_get_product($p->ID);
 if (!$product || !$product->is_type('variable')) continue;
 foreach ($product->get_children() as $vid) {
   $v = wc_get_product($vid);
   $qty = (int)$v->get_stock_quantity();
   $price = (float)$v->get_price();
   $variations++;
   $stock_value += $qty * $price;
   if ($qty == 0) $out++;
   if ($qty > 0 && $qty <= 5) $low++;
   $chart[$v->get_sku()] = $qty;
 }
}
arsort($chart);
$chart = array_slice($chart,0,10,true);
?>

<div class="wcfm-container">
<div class="wcfm-content">

<h2>WooVariations Pro</h2>

<div class="wv-top">
  <div class="wv-cards">
    <div class="wv-card green"><span>Stock Value</span><strong id="wvStock">₹<?php echo number_format($stock_value); ?></strong><span>Total inventory worth</span></div>
    <div class="wv-card blue"><span>Variations</span><strong id="wvVar"><?php echo $variations; ?></strong><span>Total active SKUs</span></div>
    <div class="wv-card yellow"><span>Low Stock</span><strong id="wvLow"><?php echo $low; ?></strong><span>Items under 5 units</span></div>
    <div class="wv-card red"><span>Out of Stock</span><strong id="wvOut"><?php echo $out; ?></strong><span>Immediate attention</span></div>
  </div>

  <div class="wv-chart-box">
    <h3>Top 10 Inventory Items</h3>
    <p>Stock Units by SKU</p>
    <div class="wv-chart-wrapper">
      <canvas id="wvChart"></canvas>
    </div>
  </div>
</div>

<table class="wcfm-table">
<thead>
<tr>
  <th>Product</th>
  <th>SKU</th>
  <th>Price</th>
  <th>Stock</th>
  <th>Status</th>
  <th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($products as $p):
$product = wc_get_product($p->ID);
if (!$product || !$product->is_type('variable')) continue;
foreach ($product->get_children() as $vid):
$v = wc_get_product($vid);
?>
<tr data-vid="<?php echo $vid; ?>">
<td><?php echo esc_html($product->get_name()); ?></td>
<td><input class="wcfm-text sku" value="<?php echo esc_attr($v->get_sku()); ?>"></td>
<td><input class="wcfm-text price" value="<?php echo esc_attr($v->get_price()); ?>"></td>
<td><input class="wcfm-text stock" value="<?php echo esc_attr($v->get_stock_quantity()); ?>"></td>
<td class="wv-status"><span class="wv-badge pending">Not Saved</span></td>
<td><button class="wcfm_submit_button save">Save</button></td>
</tr>
<?php endforeach; endforeach; ?>
</tbody>
</table>

<script>
window.wvChartData = <?php echo json_encode($chart); ?>;
</script>

</div>
</div>
