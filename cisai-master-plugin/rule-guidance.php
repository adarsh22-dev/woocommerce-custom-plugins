<?php
/**
 * Plugin Name: CISAI WCFM Vendor Dashboard Guide (Bootstrap Tabs Popup)
 * Description: Shows detailed guide for multiple vendor sections with Bootstrap tabbed interface, images, and animations (WCFM Free).
 * Version: 1.4.1
 * Author: Adarsh Singh
 */
if ( ! defined( 'ABSPATH' ) ) {
exit;
}
/**
 * Add item to WCFM menus (keeps your original behavior)
 */
add_filter( 'wcfm_menus', function( $menus ) {
$menus['vendor_guide'] = array(
'label' => __( 'Vendor Guide', 'cisai-wcfm' ),
'url' => '#vendor-guide',
'icon' => 'info',
'priority' => 99,
);
return $menus;
} );
/**
 * Enqueue assets only for vendors on frontend
 */
add_action( 'wp_enqueue_scripts', function() {
// If WCFM isn't present or user isn't vendor — bail early
if ( ! function_exists( 'wcfm_is_vendor' ) || ! wcfm_is_vendor() ) {
return;
}
// Use official jsDelivr Bootstrap 5.3 css/js and Bootstrap Icons
// We avoid integrity attributes to prevent mismatches that cause 403 errors.
wp_enqueue_style( 'cisai-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', array(), '5.3.3' );
wp_enqueue_style( 'cisai-bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css', array(), '1.11.3' );
// We'll need jQuery for some of the existing animation JS (WordPress provides it)
wp_enqueue_script( 'jquery' );
// Bootstrap bundle includes Popper, no dependency on jQuery
wp_enqueue_script( 'cisai-bootstrap-bundle', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', array(), '5.3.3', true );
// Add a small inline style and script group (keeps markup readable in footer but enqueues assets correctly)
$custom_css = "
/* CISAI Vendor Guide styles */
.cisai-vendor-guide .tab-intro { font-style: italic; color: #666; margin-bottom: 20px; font-size: 16px; }
.cisai-vendor-guide .sub-section { opacity: 0; transform: translateX(0px); transition: all .6s cubic-bezier(.25,.46,.45,.94); }
.cisai-vendor-guide .sub-section.visible { opacity: 1; transform: translateX(0); }
.cisai-vendor-guide .nav-tabs .nav-link.active { border-color: #dee2e6 #dee2e6 #dee2e6; color: #000000ff; }
.cisai-vendor-guide h4 { color: #2271b1; margin-bottom: 10px; }
.cisai-vendor-guide .guide-common-section h4 { color: #2271b1; }
  ul#guideTabNav {justify-content: space-around;}
  button:hover{background-color: #ffffff;}
@media (min-width: 576px) {
.cisai-vendor-guide .modal-dialog { max-width: 100%; margin: 30px; }
}
";
wp_add_inline_style( 'cisai-bootstrap', $custom_css );
$custom_js = "
( function( $ ) {
'use strict';
// Modal open trigger: link with href '#vendor-guide'
$( document ).on( 'click', 'a[href=\"#vendor-guide\"]', function( e ) {
e.preventDefault();
var modalEl = document.getElementById( 'wcfmVendorGuidePopup' );
if ( ! modalEl ) return;
var modal = new bootstrap.Modal( modalEl );
modal.show();
} );
// Smooth animations for sub-sections inside modal
function animateOnScroll() {
$( '#wcfmVendorGuidePopup .sub-section' ).each( function() {
var elem = $( this );
if ( elem.hasClass( 'visible' ) ) return;
// check relative position inside modal viewport
var modalBody = $( '#wcfmVendorGuidePopup .modal-body' );
if ( ! modalBody.length ) return;
var elemTop = elem.position().top; // position relative to modal-body
var visibleHeight = modalBody.innerHeight();
// show when element is within bottom 80% of modal body
if ( elemTop < visibleHeight * 0.85 ) {
setTimeout( function() { elem.addClass( 'visible' ); }, Math.random() * 250 );
}
} );
}
// When switching tabs, reset and animate
$( '#guideTabNav' ).on( 'shown.bs.tab', 'button[data-bs-toggle=\"tab\"]', function() {
var target = $( this ).attr( 'data-bs-target' );
$( target ).find( '.sub-section' ).removeClass( 'visible' ).css( 'transform', 'translateX(0px)' );
// small delay before animating to allow content to settle
setTimeout( animateOnScroll, 120 );
} );
// When modal shown, animate initial visible sections
document.addEventListener( 'shown.bs.modal', function( ev ) {
if ( ev.target && ev.target.id === 'wcfmVendorGuidePopup' ) {
animateOnScroll();
}
} );
// Bind scroll inside modal body
$( document ).on( 'scroll', '#wcfmVendorGuidePopup .modal-body', animateOnScroll );
} )( jQuery );
";
wp_add_inline_script( 'cisai-bootstrap-bundle', $custom_js );
} );
/**
 * Output modal HTML in footer for vendors only.
 */
add_action( 'wp_footer', function() {
if ( ! function_exists( 'wcfm_is_vendor' ) || ! wcfm_is_vendor() ) {
return;
}
$asset_base = plugin_dir_url( __FILE__ );
?>
<!-- Vendor Guide Modal -->
<div id="wcfmVendorGuidePopup" class="modal fade cisai-vendor-guide" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-fullscreen-sm-down">
<div class="modal-content">
  <div class="modal-header">
<h2 class="modal-title"><?php echo esc_html__( 'WCFM Vendor Dashboard Guide', 'cisai-wcfm' ); ?></h2>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'cisai-wcfm' ); ?>"></button>
  </div>
  <div class="modal-body">
<p class="lead"><?php echo esc_html__( 'Comprehensive guide to manage your store effectively across key sections.', 'cisai-wcfm' ); ?></p>
<!-- Tabs -->
<ul class="nav nav-tabs" id="guideTabNav" role="tablist" style="margin: 0 0 1.5em 0em !important;">
  <li class="nav-item" role="presentation">
<button class="nav-link active" id="tab-products-tab" data-bs-toggle="tab" data-bs-target="#tab-products" type="button" role="tab"><?php esc_html_e( 'Products', 'cisai-wcfm' ); ?></button>
  </li>
  <li class="nav-item" role="presentation">
<button class="nav-link" id="tab-orders-tab" data-bs-toggle="tab" data-bs-target="#tab-orders" type="button" role="tab"><?php esc_html_e( 'Orders', 'cisai-wcfm' ); ?></button>
  </li>
  <li class="nav-item" role="presentation">
<button class="nav-link" id="tab-payments-tab" data-bs-toggle="tab" data-bs-target="#tab-payments" type="button" role="tab"><?php esc_html_e( 'Payments', 'cisai-wcfm' ); ?></button>
  </li>
  <li class="nav-item" role="presentation">
<button class="nav-link" id="tab-coupons-tab" data-bs-toggle="tab" data-bs-target="#tab-coupons" type="button" role="tab"><?php esc_html_e( 'Coupons', 'cisai-wcfm' ); ?></button>
  </li>
  <li class="nav-item" role="presentation">
<button class="nav-link" id="tab-refunds-tab" data-bs-toggle="tab" data-bs-target="#tab-refunds" type="button" role="tab"><?php esc_html_e( 'Refunds', 'cisai-wcfm' ); ?></button>
  </li>
  <li class="nav-item" role="presentation">
<button class="nav-link" id="tab-reports-tab" data-bs-toggle="tab" data-bs-target="#tab-reports" type="button" role="tab"><?php esc_html_e( 'Reports', 'cisai-wcfm' ); ?></button>
  </li>
</ul>
<div class="tab-content mt-3" id="guideTabContent">
  <!-- Products Tab -->
  <div class="tab-pane fade show active" id="tab-products" role="tabpanel" aria-labelledby="tab-products-tab">
<p class="tab-intro"><?php esc_html_e( 'Manage your product catalog: add, edit, and track inventory.', 'cisai-wcfm' ); ?></p>
<div class="row sub-section mb-4">
  <div class="col-md-6">
<img src="<?php echo esc_url( $asset_base . 'assets/images/products-1.png' ); ?>" alt="<?php esc_attr_e( 'Product List', 'cisai-wcfm' ); ?>" class="img-fluid rounded border">
<p class="mt-2"><?php esc_html_e( 'Browse and filter your products easily.', 'cisai-wcfm' ); ?></p>
  </div>
  <div class="col-md-6">
<h4><?php esc_html_e( 'Product Listing & Filters', 'cisai-wcfm' ); ?></h4>
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'View all products with status, stock, and price.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Filter by category, status, or type.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Search for specific items quickly.', 'cisai-wcfm' ); ?></li>
</ul>
  </div>
</div>
<!-- Add more sub-sections as in your original file, but example only for brevity -->
<div class="row sub-section mb-4 flex-md-row-reverse">
  <div class="col-md-6">
<img src="<?php echo esc_url( $asset_base . 'http://10.31.1.84/wp-content/uploads/2025/12/Screenshot-from-2025-12-03-15-58-58-1.png' ); ?>" alt="<?php esc_attr_e( 'Add Product Form', 'cisai-wcfm' ); ?>" class="img-fluid rounded border">
  </div>
  <div class="col-md-6">
<h4><?php esc_html_e( 'Add/Edit Products', 'cisai-wcfm' ); ?></h4>
<p><?php esc_html_e( 'Create new products or update existing ones with images and details.', 'cisai-wcfm' ); ?></p>
  </div>
</div>
<div class="sub-section mb-4">
<h4><?php esc_html_e( 'Products Listing', 'cisai-wcfm' ); ?></h4>
<p><?php esc_html_e( 'To view products that have been created, go to Products in WCFM Dashboard left menu. At the top of this screen you can view the standard filter and search area. A list of products appears in order of date made.', 'cisai-wcfm' ); ?></p>
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Filter by status using the status links at the top', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Filter by category', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Filter by the product types', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Search', 'cisai-wcfm' ); ?></li>
</ul>
<p><?php esc_html_e( 'At the far right of each product are actions you can perform on the row:', 'cisai-wcfm' ); ?></p>
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'View', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Edit', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Duplicate', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Featured Mark', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Delete', 'cisai-wcfm' ); ?></li>
</ul>
<p><?php esc_html_e( 'You also have some useful options at top right of the screen:', 'cisai-wcfm' ); ?></p>
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Add New Product', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Product Export', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Product Import', 'cisai-wcfm' ); ?></li>
</ul>
<img src="<?php echo esc_url( $asset_base . 'assets/images/products-3.png' ); ?>" alt="<?php esc_attr_e( 'Products Additional Image', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
</div>
  </div>
  <!-- Orders Tab -->
  <div class="tab-pane fade" id="tab-orders" role="tabpanel" aria-labelledby="tab-orders-tab">
<p class="tab-intro"><?php esc_html_e( 'Handle customer orders: view, update, and process shipments.', 'cisai-wcfm' ); ?></p>
<div class="row sub-section mb-4">
  <div class="col-md-6">
<img src="<?php echo esc_url( $asset_base . 'assets/images/orders-1.png' ); ?>" alt="<?php esc_attr_e( 'Orders List', 'cisai-wcfm' ); ?>" class="img-fluid rounded border">
<p class="mt-2"><?php esc_html_e( 'Comprehensive order overview.', 'cisai-wcfm' ); ?></p>
  </div>
  <div class="col-md-6">
<h4><?php esc_html_e( 'Order Columns & Details', 'cisai-wcfm' ); ?></h4>
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Order ID, customer, products, and quantities.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Billing/shipping addresses with locations.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Gross sales, earnings, and dates.', 'cisai-wcfm' ); ?></li>
</ul>
  </div>
</div>
<div class="sub-section mb-4">
<h4><?php esc_html_e( 'Orders Listing', 'cisai-wcfm' ); ?></h4>
<p><?php esc_html_e( 'To view orders that have been placed, go to Orders in WCFM Dashboard left menu. At the top of this screen you can view the standard filter and search area. A list of orders appears in order of date made:', 'cisai-wcfm' ); ?></p>
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Filter by status using the status links at the top', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Filter by date', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Search', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'WooCommerce Sequential Order Number search also supported', 'cisai-wcfm' ); ?></li>
</ul>
<p><?php esc_html_e( 'At the far right of each order are actions you can perform on the row:', 'cisai-wcfm' ); ?></p>
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'View', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Mark as Complete (only for Admin)', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Mark as Shipped (only for Vendors)', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Download PDF Invoice (Install WooCommerce PDF Invoices & Packing Slips to avail this feature)', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Delete', 'cisai-wcfm' ); ?></li>
</ul>
<p><?php esc_html_e( 'You also have some useful options at top right of the screen:', 'cisai-wcfm' ); ?></p>
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'WP admin Order dashboard (only for Admin)', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Screen Manager (only for Admin) – you can easily manage listing columns', 'cisai-wcfm' ); ?></li>
</ul>
<img src="<?php echo esc_url( $asset_base . 'assets/images/orders-2.png' ); ?>" alt="<?php esc_attr_e( 'Orders Additional Image 1', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<img src="<?php echo esc_url( $asset_base . 'assets/images/orders-3.png' ); ?>" alt="<?php esc_attr_e( 'Orders Additional Image 2', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
</div>
  </div>
  <!-- Payments Tab -->
  <div class="tab-pane fade" id="tab-payments" role="tabpanel" aria-labelledby="tab-payments-tab">
<p class="tab-intro"><?php esc_html_e( 'Monitor earnings, commissions, and withdrawal requests.', 'cisai-wcfm' ); ?></p>
<div class="row sub-section mb-4">
  <div class="col-md-6">
<img src="<?php echo esc_url( $asset_base . 'assets/images/payments-1.png' ); ?>" alt="<?php esc_attr_e( 'Earnings Overview', 'cisai-wcfm' ); ?>" class="img-fluid rounded border">
<p class="mt-2"><?php esc_html_e( 'Total earnings and commission breakdown.', 'cisai-wcfm' ); ?></p>
  </div>
  <div class="col-md-6">
<h4><?php esc_html_e( 'Earnings & Commissions', 'cisai-wcfm' ); ?></h4>
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'View gross, net, and commission amounts.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Filter by date range or payment status.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Track pending and approved payments.', 'cisai-wcfm' ); ?></li>
</ul>
  </div>
</div>
<div class="sub-section mb-4">
<h4><?php esc_html_e( 'Payment Overview', 'cisai-wcfm' ); ?></h4>
<p><?php esc_html_e( 'Vendor payment gateways are most essential component for vendor commission payout. Vendors are allowed to withdrawal their commission via these payment options.', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Vendor payment options –', 'cisai-wcfm' ); ?></p>
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'PayPal', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Stripe', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Stripe Split Pay', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Skrill', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Bank Transfer', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Cash Pay', 'cisai-wcfm' ); ?></li>
</ul>
<p><?php esc_html_e( 'You may setup this from WCFM Admin Setting -> Payment Setting Tab', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Admin may enable any of these payment options, and Vendor may set any of these as their preferred withdrawal payment method.', 'cisai-wcfm' ); ?></p>
<h5><?php esc_html_e( 'Electronic Payment Methods', 'cisai-wcfm' ); ?></h5>
<p><?php esc_html_e( 'Electronic payment options are allowed you to transfer payment from Admin account to vendors’ account via online transfer (using API)', 'cisai-wcfm' ); ?></p>
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'PayPal', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Stripe', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Stripe Split Pay', 'cisai-wcfm' ); ?></li>
</ul>
<h6><?php esc_html_e( 'PayPal', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'Enable PayPal from payment list and hence you will see PayPal configuration fields –', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/payments-2.png' ); ?>" alt="<?php esc_attr_e( 'Payments Additional Image 1', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'You may enable “Test Mode” for testing purpose.', 'cisai-wcfm' ); ?></p>
<h6><?php esc_html_e( 'PayPal Account Setup', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'Read this know how you may get PayPal client ID and Secret Key – https://www.appinvoice.com/en/s/documentation/how-to-get-paypal-client-id-and-secret-key', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/payments-3.png' ); ?>" alt="<?php esc_attr_e( 'Payments Additional Image 2', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<h6><?php esc_html_e( 'Vendor PayPal Connect', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'Vendors will have PayPal account connect option at their Store Manager -> Setting -> Payment -> Preferred Payment Method', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/payments-4.png' ); ?>" alt="<?php esc_attr_e( 'Payments Additional Image 3', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<h6><?php esc_html_e( 'Stripe', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'Enable Stripe from payment list and hence you will see Stripe configuration fields –', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/payments-5.png' ); ?>" alt="<?php esc_attr_e( 'Payments Additional Image 4', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'You may enable “Test Mode” for testing purpose.', 'cisai-wcfm' ); ?></p>
<h6><?php esc_html_e( 'Stripe Account Setup', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'Sign up or Log in. At first, you have to visit stripe.com and sign up. Follow their verification process. If you already have a Stripe Account, just sign in.', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/payments-6.png' ); ?>" alt="<?php esc_attr_e( 'Payments Additional Image 5', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'Generate (Test and Live) API Keys. You should land on the Home tab after you log in to Stripe. If not, navigate to the Home tab of the Stripe logged-in interface to find the Secret and the Publishable Keys. The keys will appear based on what you select from the toggle button above. For example, if you activate the Test mode button, then the keys will show up for Test mode only. To view the live mode keys, simply turn off the toggle button for Test mode.', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/payments-7.png' ); ?>" alt="<?php esc_attr_e( 'Payments Additional Image 6', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'At the WCFM interface. Copy and paste the keys to the credential fields. If you are testing, use the Test credentials. For live transactions, use the live credentials.', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Connect Your Marketplace and Get the Client ID. Now that you have already entered the API keys and the only thing required is the Client ID for live transaction or Test Client ID for Testing.', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Click the settings icon from the top right hand side of the Stripe logged in interface, just beside the Profile icon. Click on the Connect link under the Product Settings section. From the Connect settings page, choose the Onboarding options link. Click on the oAuth tab, and inside that, you’ll find the Stripe Client ID. This is the credential you have to put under the Client ID of the WCFM Payment Settings section. Also, copy the redirect URLs from the Payment Settings in WCFM, and add those to the Stripe interface under the Integration section (You have to set two URLs here – WCFM Dashboard page setting URL and for Setup Widget https://yourdomain.com?store-setup=yes&step=payment)', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/payments-8.png' ); ?>" alt="<?php esc_attr_e( 'Payments Additional Image 7', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'Know more about this from here – https://www.appypie.com/faqs/how-to-get-live-publishable-key-live-secret-key-and-client-id-from-stripe', 'cisai-wcfm' ); ?></p>
<h6><?php esc_html_e( 'Vendor Stripe Connect', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'Vendors will have Stripe account connect option at their Store Manager -> Setting -> Payment -> Preferred Payment Method', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/payments-9.png' ); ?>" alt="<?php esc_attr_e( 'Payments Additional Image 8', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'On click “Connect with Stripe” button vendor will redirect to Stripe site to connect their account with Admin’s stripe account –', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'After successful connect vendor will redirect back to their dashboard payment tab and will see connect information.', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Vendors may disconnect their account anytime from here as well.', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/payments-10.png' ); ?>" alt="<?php esc_attr_e( 'Payments Additional Image 9', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<h6><?php esc_html_e( 'Stripe Split Pay', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'Stripe Split Pay is payment option using which you may pay vendors instantly, as soon as customer pay for the order. Total order amount divided into parts – vendor commissions and admin fee. Vendor commissions go into vendor stripe account and rest in admin’s stripe account.', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Enable Stripe Split Pay from payment list and hence you will see Stripe configuration fields –', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'You may enable “Test Mode” for testing purpose.', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Stripe has different type of charges/fees for Split payments –', 'cisai-wcfm' ); ?></p>
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Direct Charges', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Destination Charges', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Transfer Changes', 'cisai-wcfm' ); ?></li>
</ul>
<p><?php esc_html_e( 'Know more about charges from here – https://stripe.com/docs/connect/charges', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'When you are using this payment method you do not require any other Stripe plugin for WooCommerce. It will work as Stripe payment gateway.', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'This will work as Stripe payment method at WC Checkout.', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'On successful payment order status change into “Processing”.', 'cisai-wcfm' ); ?></p>
<h6><?php esc_html_e( 'Stripe Split Pay Account Setup', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'Sign up or Log in. At first, you have to visit stripe.com and sign up. Follow their verification process. If you already have a Stripe Account, just sign in.', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Generate (Test and Live) API Keys. You should land on the Home tab after you log in to Stripe. If not, navigate to the Home tab of the Stripe logged-in interface to find the Secret and the Publishable Keys. The keys will appear based on what you select from the toggle button above. For example, if you activate the Test mode button, then the keys will show up for Test mode only. To view the live mode keys, simply turn off the toggle button for Test mode.', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'At the WCFM interface. Copy and paste the keys to the credential fields. If you are testing, use the Test credentials. For live transactions, use the live credentials.', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Connect Your Marketplace and Get the Client ID. Now that you have already entered the API keys and the only thing required is the Client ID for live transaction or Test Client ID for Testing.', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Click the settings icon from the top right hand side of the Stripe logged in interface, just beside the Profile icon. Click on the Connect link under the Product Settings section. From the Connect settings page, choose the Onboarding options link. Click on the oAuth tab, and inside that, you’ll find the Stripe Client ID. This is the credential you have to put under the Client ID of the WCFM Payment Settings section. Also, copy the redirect URLs from the Payment Settings in WCFM, and add those to the Stripe interface under the Redirects section (You have to set two URLs here – WCFM Dashboard page setting URL and for Setup Widget https://yourdomain.com?store-setup=yes&step=payment)', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Know more about this from here – https://www.appypie.com/faqs/how-to-get-live-publishable-key-live-secret-key-and-client-id-from-stripe', 'cisai-wcfm' ); ?></p>
<h6><?php esc_html_e( 'Vendor Account Connect', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'Vendors will have Stripe account connect option at their Store Manager -> Setting -> Payment -> Preferred Payment Method', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'On click “Connect with Stripe” button vendor will redirect to Stripe site to connect their account with Admin’s stripe account –', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'After successful connect vendor will redirect back to their dashboard payment tab and will see connect information.', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Vendors may disconnect their account anytime from here as well.', 'cisai-wcfm' ); ?></p>
<h6><?php esc_html_e( 'Payfast', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'To have Payfast payment gateway for your store you will need the followings along with WCFM Marketplace and WooCommerce:', 'cisai-wcfm' ); ?></p>
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'WCFM – Frontend Manager', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'WooCommerce Payfast gateway', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'WCFM-payfast addon ( from Git)', 'cisai-wcfm' ); ?></li>
</ul>
<h6><?php esc_html_e( 'Payfast- Admin setup', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'Admin has to enable the payfast gateway from their own dashboard, they can do the same from here: Goto WCFM admin Dashboard-> Settings -> Payment settings-> Check Payfast option.', 'cisai-wcfm' ); ?></p>
<h6><?php esc_html_e( 'Payfast- Vendor Setup', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'Vendor’s will have to enter the merchant id of their Payfast gateway and select the preferred payment system as Payfast as shown below:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/payfast-vendor-setup.png' ); ?>" alt="<?php esc_attr_e( 'Payfast Vendor Setup', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'The above section can be navigated from here: Goto WCFM Vendor dashboard -> Settings -> Payment. Once this is done, the configuration of Payfast gateway is completed and vendors can receive payment using Payfast.', 'cisai-wcfm' ); ?></p>
<h6><?php esc_html_e( 'Paystack', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'To have Paystack payment gateway for your store you will need the followings along with WCFM Marketplace and WooCommerce:', 'cisai-wcfm' ); ?></p>
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'WCFM – Frontend Manager', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Paystack WooCommerce Payment Gateway', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'WCFM Paystack Addon ( Github link)', 'cisai-wcfm' ); ?></li>
</ul>
<h6><?php esc_html_e( 'Paystack- Admin setup', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'Admin has to enable the Paystack gateway from their own dashboard, they can do the same from here: Goto WCFM admin Dashboard-> Settings -> Payment settings-> Check Paystack option as shown below.', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/paystack-admin-setup.png' ); ?>" alt="<?php esc_attr_e( 'Paystack Admin Setup', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'You will also have to place the Paystack Test secret key and public key as shown in the image above, check how to generate those keys from here', 'cisai-wcfm' ); ?></p>
<h6><?php esc_html_e( 'Paystack- Vendor Setup', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'In addition to admin, vendors will also have to configure their payment system accordingly, the vendors can do so from – WCFM Vendor Dashboard -> Settings -> Payments as shown below:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/paystack-vendor-setup.png' ); ?>" alt="<?php esc_attr_e( 'Paystack Vendor Setup', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'They will need to fill-up the necessary details and this will ensure that the amount gets paid through Paystack as required.', 'cisai-wcfm' ); ?></p>
<h6><?php esc_html_e( 'MangoPay', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'For MangoPay payment gateway in your store you will need the followings along with WCFM Marketplace and WooCommerce:', 'cisai-wcfm' ); ?></p>
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'WCFM – Frontend Manager', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'MANGOPAY WooCommerce', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'WCFM MangoPay Addon( Gitlab Link)', 'cisai-wcfm' ); ?></li>
</ul>
<h6><?php esc_html_e( 'Mangopay- Admin settings', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'Admin has to enable the MangoPay option for his/her store and that can be done from payment settings as shown below from Admin Dashboard.', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/mangopay-admin-settings.png' ); ?>" alt="<?php esc_attr_e( 'MangoPay Admin Settings', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'This will allow the vendors to setup MangoPay settings from their dashboard to accept payments.', 'cisai-wcfm' ); ?></p>
<h6><?php esc_html_e( 'MangoPay- Vendor Settings', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'The vendors will also have to configure their Mangopay settings by entering details with few identity proves as shown below. This can be seen under payments option in settings menu of WCFM Vendor dashboard. Here’s a screenshot of the same along with details:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/mangopay-vendor-settings.png' ); ?>" alt="<?php esc_attr_e( 'MangoPay Vendor Settings', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<h5><?php esc_html_e( 'Manual Payment Methods', 'cisai-wcfm' ); ?></h5>
<p><?php esc_html_e( 'Manual payment methods are for transact payment manually from Admin account to vendors’ account. Here vendors may insert their account details for receiving payment but admin has to pay commission amount manually in vendors’ account.', 'cisai-wcfm' ); ?></p>
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Bank Transfer', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Skrill', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Cash Pay', 'cisai-wcfm' ); ?></li>
</ul>
</div>
  </div>
  <!-- Coupons Tab -->
  <div class="tab-pane fade" id="tab-coupons" role="tabpanel" aria-labelledby="tab-coupons-tab">
<p class="tab-intro"><?php esc_html_e( 'Create and manage discount coupons to boost sales.', 'cisai-wcfm' ); ?></p>
<div class="sub-section mb-4">
<h4><?php esc_html_e( 'Adding/Editing Coupon', 'cisai-wcfm' ); ?></h4>
<p><?php esc_html_e( 'Adding or Editing coupons is easily managed by the users using WCFM, in this section we will discuss the procedure to add/edit coupons both by admin and by vendor.', 'cisai-wcfm' ); ?></p>
<h5><?php esc_html_e( 'By Admin', 'cisai-wcfm' ); ?></h5>
<p><?php esc_html_e( 'Admin can add coupon from here: Goto WCFM Admin Dashboard -> Coupon -> Add New ( see screenshot)', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/coupons-1.png' ); ?>" alt="<?php esc_attr_e( 'Admin Add New Coupon', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'Similarly, for editing the present coupons, you can click on the existing coupon ( from the list) or click on the edit option under “Action” column', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Clicking on Add/edit option will redirect you to Edit/Add coupon page as shown below:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/coupons-2.png' ); ?>" alt="<?php esc_attr_e( 'Coupon Edit/Add Page', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<h6><?php esc_html_e( 'General settings', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'These include the basic settings of coupon and following screenshot along with description shows the options available:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/coupons-3.png' ); ?>" alt="<?php esc_attr_e( 'Coupon General Settings', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'a. Code', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Admin can set here the name of the code.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'b.Description', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Here admin can provide the description of the coupon', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'c.Discount Type', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Admin can select the type of discount from the drop-down provided here, by default admin can choose if it will be fixed cart or fixed product or percentage discount as shown below:', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'd. Coupon amount', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Here, one can apply the value of the coupon which is to be declared.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'e. Coupon Expiry date', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'You can set the expiry date of the coupon from here.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'f. Allow free shipping', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Check this option if the coupon grants free shipping, ensure that “Free shipping method” must be enabled and configured as required.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'g. Store', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Here the admin can set the store for which the coupon will be applicable.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'h. Show on store', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Check this option if you want to display the coupon in your store page. To display the coupon, you will need to add Vendor store coupon widget under Vendor store sidebar as shown below in the screenshot:', 'cisai-wcfm' ); ?></li>
</ul>
<img src="<?php echo esc_url( $asset_base . 'assets/images/coupons-4.png' ); ?>" alt="<?php esc_attr_e( 'Vendor Store Coupon Widget', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<h6><?php esc_html_e( 'Restriction settings', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'These settings will allow the admin to configure rules for the published coupons globally ( i,e throughout the site). Here’s a screenshot of the section and the details of the options provided.', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/coupons-5.png' ); ?>" alt="<?php esc_attr_e( 'Coupon Restriction Settings', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'a. Minimum spend', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'This option allows you to set the minimum subtotal needed to use the coupon. Note that the amount = Cart total +tax', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'b. Maximum spend', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'This allows you to set the maximum subtotal allowed when using the coupon.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'c. Individual use only', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Check the box if you don’t want this coupon to be used with other coupons.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'd. Exclude sale items', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Check the box if you don’t want this coupon to apply to products on sale.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'e. Products', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'You can set here the products on which the coupon can be applied.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'f. Exclude product', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'You can set the products that the coupon code will not be applied to.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'g. Product categories', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'You can select the product categories that the coupon will be applied to, or that need to be in the cart in order for the coupon to be applied.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'h. Exclude categories', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'You can set the product categories that the coupon will not be applied to, or that cannot be in the cart in order for the coupon to be applied.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'i.Email restrictions', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Here you can set a list of Email addresses that can use a coupon, verified against customer’s billing email. Use a comma to separate the emails.', 'cisai-wcfm' ); ?></li>
</ul>
<h6><?php esc_html_e( 'Limit', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'This tab will allow the admin to add usage limits against the coupons. Here’s a screen-grab of the functions available under this tab along with descriptions, for better understanding:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/coupons-6.png' ); ?>" alt="<?php esc_attr_e( 'Coupon Limit Settings', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'a. Usage limit per coupon', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Admin can set here the number of times this coupon can be used before it becomes invalid.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'b. Limit usage to X items', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'This will allow the admin to set the number of items against which coupon can be applied to before being invalid.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'c. Usage Limit per user', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Set the number of times a coupon can be used by each customer before being invalid for that customer.', 'cisai-wcfm' ); ?></li>
</ul>
<h5><?php esc_html_e( 'By Vendor', 'cisai-wcfm' ); ?></h5>
<p><?php esc_html_e( 'WCFM also allows the vendors to configure coupon for their store. In addition to the global coupons which are declared by the admin, the vendors can specifically create coupons for their store if the capability is given ( as shown above).', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Similar to Admin, the vendors can add their coupon from here: Goto WCFM Vendor Dashboard-> Coupons-> Add new ( as shown in the screenshot below)', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/coupons-7.png' ); ?>" alt="<?php esc_attr_e( 'Vendor Add New Coupon', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'In the same manner, for editing the present coupons, you can click on the existing coupon ( from the list) or click on the edit option under “Action” column.', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Clicking on Add/edit option will redirect you to Edit/Add coupon page as shown below:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/coupons-8.png' ); ?>" alt="<?php esc_attr_e( 'Vendor Coupon Edit/Add Page', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'Similar to Admin settings, let’s discuss the feasibility provided to the vendors for Coupons by WCFM.', 'cisai-wcfm' ); ?></p>
<h6><?php esc_html_e( 'General Settings', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'These include the basic settings provided to the vendor for configuring coupons for their stores, here’s a screenshot of the options available along with brief description of each:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/coupons-9.png' ); ?>" alt="<?php esc_attr_e( 'Vendor Coupon General Settings', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'a. Code', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Vendors can provide the name of the coupon for their store.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'b. Description', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Vendors can enter the description of the coupon here.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'c. Discount Type', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Vendors can select the type of discount from the drop-down option provided here. Note that unlike admin, vendors have 2 discount types options which can be declared for their store, namely- percentage discount and fixed product discount.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'd. Coupon amount', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Here, vendors can apply the value of the coupon which is to be declared.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'e. Coupon Expiry date', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Vendors can set expiry date of the coupon from here.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'f. Allow free shipping', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Check this option if the coupon grants free shipping, ensure that “Free shipping method” must be enabled and configured as required.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'g. Show on store', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Checking this option will allow the vendors to display the coupon in their store page. It has to be ensured that the vendor store coupon widget is set properly in the sidebar as discussed in admin section.', 'cisai-wcfm' ); ?></li>
</ul>
<h6><?php esc_html_e( 'Restriction settings', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'Similar to admin, these settings will allow the vendors to configure rules for the published coupons for their store. Here’s a screenshot of the section and the details of the options provided.', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/coupons-10.png' ); ?>" alt="<?php esc_attr_e( 'Vendor Coupon Restriction Settings', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'a. Minimum spend', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'This option allows the vendor to set the minimum subtotal needed to use the coupon in their store. Note that the amount = Cart total +tax', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'b. Maximum spend', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'This allows the vendor to set the maximum subtotal allowed when using the coupon.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'c. Individual use only', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Checking this box will ensure that this coupon is not used with other coupons declared by the vendor.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'd. Exclude sale items', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Vendors can check the box if they don’t want this coupon to be applied against the products on sale in their store', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'e. Products', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Vendors can set here the products of their store on which the coupon can be applied.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'f. Exclude product', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Vendors can set the products of their store upon which the coupon cannot be applied.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'g. Product categories', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Vendors can select the product categories of their store upon which the coupon can be applied, or that need to be in the cart in order for the coupon to be applied.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'h. Exclude categories', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Vendors can set the product categories of their store upon which the coupon will not be applied, or that cannot be in the cart in order for the coupon to be applied.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'i. Email restrictions', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Here the vendors can set a list of Email addresses that can use a coupon, verified against customer’s billing email. Use a comma to separate the emails.', 'cisai-wcfm' ); ?></li>
</ul>
<h6><?php esc_html_e( 'Limit', 'cisai-wcfm' ); ?></h6>
<p><?php esc_html_e( 'This tab will allow the vendor(s) to add usage limits against the declared coupons. Here’s a screengrab of the functions available under this tab along with descriptions, for better understanding:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/coupons-11.png' ); ?>" alt="<?php esc_attr_e( 'Vendor Coupon Limit Settings', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'a. Usage limit per coupon', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Vendor(s) can set here the number of times this coupon can be used before it becomes invalid.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'b. Limit usage to X items', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'This will allow the vendors to set the number of items against which coupon can be applied to before being invalid.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'c. Usage Limit per user', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Set the number of times a coupon can be used by each customer before being invalid for that customer.', 'cisai-wcfm' ); ?></li>
</ul>
<h4><?php esc_html_e( 'Deleting coupons ( For admin and Vendors)', 'cisai-wcfm' ); ?></h4>
<p><?php esc_html_e( 'Both Admin can Vendors can delete the coupons from their respective Dashboard from Coupon Listing page: WCFM Admin/Vendor Dashboard -> Coupons by click on the “Delete” icon under the actions column as shown below:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/coupons-12.png' ); ?>" alt="<?php esc_attr_e( 'Coupon Delete Action', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<img src="<?php echo esc_url( $asset_base . 'assets/images/coupons-13.png' ); ?>" alt="<?php esc_attr_e( 'Coupons Additional Image 1', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<img src="<?php echo esc_url( $asset_base . 'assets/images/coupons-14.png' ); ?>" alt="<?php esc_attr_e( 'Coupons Additional Image 2', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<img src="<?php echo esc_url( $asset_base . 'assets/images/coupons-15.png' ); ?>" alt="<?php esc_attr_e( 'Coupons Additional Image 3', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<img src="<?php echo esc_url( $asset_base . 'assets/images/coupons-16.png' ); ?>" alt="<?php esc_attr_e( 'Coupons Additional Image 4', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
</div>
  </div>
  <!-- Refunds Tab -->
  <div class="tab-pane fade" id="tab-refunds" role="tabpanel" aria-labelledby="tab-refunds-tab">
<p class="tab-intro"><?php esc_html_e( 'Process refunds efficiently for customer satisfaction.', 'cisai-wcfm' ); ?></p>
<div class="sub-section mb-4">
<h4><?php esc_html_e( 'Refund Claim by Vendors', 'cisai-wcfm' ); ?></h4>
<p><?php esc_html_e( 'Vendors can apply for a refund for specific order from their order tabs here: WCFM Vendor Dashboard -> Orders -> Order Item list.', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Upon clicking on the refund icon ( placed under the actions column) the vendor will be prompted to fill in the details of the claim like this:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/refunds-1.png' ); ?>" alt="<?php esc_attr_e( 'Vendor Refund Claim Popup', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'While the Product name is pre-filled in the above pop-up shown, the vendor has to fill in remaining details for claiming the refund such as:', 'cisai-wcfm' ); ?></p>
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'Refund Request', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Here the vendor can select is he/she will apply for a full refund or partial refund from the drop-down. Incase of partial refund, the vendor has to fill in the refund amount which is to be claimed.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'Refund request reason', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'Here the vendor can provide specific reason for claiming a refund against an order.', 'cisai-wcfm' ); ?></li>
</ul>
<p><?php esc_html_e( 'Once the request has been placed and approved by admin ( either automatically or manually), the refund gets reflected in the order list as shown here ( for partial refund):', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/refunds-2.png' ); ?>" alt="<?php esc_attr_e( 'Partial Refund in Order List', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'Note that if the vendor had already requested or withdrew commission before, then they will not be able to ask for a refund.', 'cisai-wcfm' ); ?></p>
<h4><?php esc_html_e( 'Refund claim by customers', 'cisai-wcfm' ); ?></h4>
<p><?php esc_html_e( 'Customer will be able to claim a refund only if Admin checks the “Refund by customer” option from here: WCFM Admin Dashboard –> Settings -> Refund Settings.', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'If customer refund is allowed, then the customers can see refund options from their Order Dashboard, as shown here:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/refunds-3.png' ); ?>" alt="<?php esc_attr_e( 'Customer Refund Options', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'Similar to that of vendors, the customer will be prompted to fill-up few fields and enter the details of refund request as here:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/refunds-4.png' ); ?>" alt="<?php esc_attr_e( 'Customer Refund Details Form', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'The customer can ask for a Full refund or partial return and submit a request which will be processed by site admin.', 'cisai-wcfm' ); ?></p>
<h4><?php esc_html_e( 'Request Approval', 'cisai-wcfm' ); ?></h4>
<p><?php esc_html_e( 'WCFM allows the admin to approve the requests either manually or automatically. This can be setup accordingly from here: WCFM Admin Dashboard -> Settings -> Refund Settings', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Upon checking the Request Auto approve button, all refund requests will be disbursed automatically.', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Incase the admin doesn’t approve auto-approve then all refund requests will have to be approved manually and the request can be seen here : WCFM Admin Dashboard -> Refund', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/refunds-5.png' ); ?>" alt="<?php esc_attr_e( 'Admin Refund Requests List', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'The admin can select the required refund requests from the above list and can either Approve or Reject the request from the buttons provided at the bottom.', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/refunds-6.png' ); ?>" alt="<?php esc_attr_e( 'Refunds Additional Image 1', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<img src="<?php echo esc_url( $asset_base . 'assets/images/refunds-7.png' ); ?>" alt="<?php esc_attr_e( 'Refunds Additional Image 2', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<img src="<?php echo esc_url( $asset_base . 'assets/images/refunds-8.png' ); ?>" alt="<?php esc_attr_e( 'Refunds Additional Image 3', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<img src="<?php echo esc_url( $asset_base . 'assets/images/refunds-9.png' ); ?>" alt="<?php esc_attr_e( 'Refunds Additional Image 4', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<img src="<?php echo esc_url( $asset_base . 'assets/images/refunds-10.png' ); ?>" alt="<?php esc_attr_e( 'Refunds Additional Image 5', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<img src="<?php echo esc_url( $asset_base . 'assets/images/refunds-11.png' ); ?>" alt="<?php esc_attr_e( 'Refunds Additional Image 6', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<img src="<?php echo esc_url( $asset_base . 'assets/images/refunds-12.png' ); ?>" alt="<?php esc_attr_e( 'Refunds Additional Image 7', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
</div>
  </div>
  <!-- Reports Tab -->
  <div class="tab-pane fade" id="tab-reports" role="tabpanel" aria-labelledby="tab-reports-tab">
<p class="tab-intro"><?php esc_html_e( 'Analyze sales, performance, and trends with insightful reports.', 'cisai-wcfm' ); ?></p>
<div class="sub-section mb-4">
<h4><?php esc_html_e( 'Options', 'cisai-wcfm' ); ?></h4>
<p><?php esc_html_e( 'Both Admin and Vendors can see the reports of their store from here: Goto WCFM Admin/vendor Dashboard -> Reports, following if the glimpse of the same:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/reports-1.png' ); ?>" alt="<?php esc_attr_e( 'Report Parameters for Admin', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'Let’s now discuss the options available in the Report Dashboard individually to have a better insight:', 'cisai-wcfm' ); ?></p>
<h5><?php esc_html_e( '3.1.Sales by Date (for both Admin and Vendor)', 'cisai-wcfm' ); ?></h5>
<p><?php esc_html_e( 'This will allow the vendors and admin to check the sales figure for a particular range of date such as Year, Month, week and even custom selected range of date.', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/reports-2.png' ); ?>" alt="<?php esc_attr_e( 'Sales by Date Report', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'Note that Sale by date option is available for admin and vendors, only difference being, the admin can see the entire sale of the store and vendors can see the sale of only their store.', 'cisai-wcfm' ); ?></p>
<h5><?php esc_html_e( '3.2.Sale by Product ( both Admin and vendor)', 'cisai-wcfm' ); ?></h5>
<p><?php esc_html_e( 'Similar to Sale by date, both admin and vendors are able to get a vivid analysis of the products being sold. Only difference being- the admin can check this for any products in the store, whereas the vendor can only search for the sale which is being sold exclusively in their store.', 'cisai-wcfm' ); ?></p>
<p><?php esc_html_e( 'Here’s an outlook of the report section ( of a vendor) for better understanding:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/reports-3.png' ); ?>" alt="<?php esc_attr_e( 'Sale by Product Vendor Report', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'Here’s a briefing of the options available as shown in the screenshot above:', 'cisai-wcfm' ); ?></p>
<ul class="list-unstyled">
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'Time range', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'From here one can select the time span within which the report needs to be generated.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'Product Search', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'The product search option allows one to enter the name of the product whose sales report has to be generated. One will have to select the product from the drop-down option needed.', 'cisai-wcfm' ); ?></li>
  <li><i class="bi bi-check-circle text-primary"></i> <strong><?php esc_html_e( 'Top Sellers', 'cisai-wcfm' ); ?>:</strong> <?php esc_html_e( 'This section will enlist all the top selling products, clicking on which shows the sales figure of the selected product for a span of time.', 'cisai-wcfm' ); ?></li>
</ul>
<img src="<?php echo esc_url( $asset_base . 'assets/images/reports-4.png' ); ?>" alt="<?php esc_attr_e( 'Top Sellers List', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<img src="<?php echo esc_url( $asset_base . 'assets/images/reports-5.png' ); ?>" alt="<?php esc_attr_e( 'Selected Product Sales Report', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'Top Freebies', 'cisai-wcfm' ); ?>: <?php esc_html_e( 'Similar to top sellers option this section will enlist all the top freebie products as shown in the following screenshot:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/reports-6.png' ); ?>" alt="<?php esc_attr_e( 'Top Freebies', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<p><?php esc_html_e( 'Top Earners', 'cisai-wcfm' ); ?>: <?php esc_html_e( 'Using this option one will get the list of top sold products, and check the sales report generated for a selected span of time.', 'cisai-wcfm' ); ?></p>
<h5><?php esc_html_e( '3.3.Sale by store ( Only admin)', 'cisai-wcfm' ); ?></h5>
<p><?php esc_html_e( 'This option is only available for admin/store-owner as they can select any vendor store from the drop-down option and check the sales report of that particular vendor store. Here’s a screenshot of the options available:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/reports-7.png' ); ?>" alt="<?php esc_attr_e( 'Sale by Store Admin', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<h5><?php esc_html_e( '3.4.Low in stock ( both admin and vendor)', 'cisai-wcfm' ); ?></h5>
<p><?php esc_html_e( 'Both admin and vendor can get the list of products which are low in stock from here. Apart from getting a glimpse of the inventory, you can also edit/view/delete the products from this list. Here’s again a screenshot showing a demo listing:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/reports-8.png' ); ?>" alt="<?php esc_attr_e( 'Low in Stock List', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<h5><?php esc_html_e( '3.5.Out of stock ( both admin and vendor)', 'cisai-wcfm' ); ?></h5>
<p><?php esc_html_e( 'Similar to the “Low in Stock” feature, this option allows the admin and the vendors to get a glance of the stock which is out of stock. Quite understandably, vendor will be able to see the stock of their own store, and the admin can see the status of the stock for the entire online store ( i.e for every vendor). Here’s a glimpse of the same:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/reports-9.png' ); ?>" alt="<?php esc_attr_e( 'Out of Stock List', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<h5><?php esc_html_e( '3.6.Coupons by date ( only Admin)', 'cisai-wcfm' ); ?></h5>
<p><?php esc_html_e( 'Admin can check the number of coupons and the amount of discount which is used in the store for a selected span of time. Additionally, there is also filter options available using which one can see the reports of any specific coupon. It also lists the coupons which are most popular and which offered maximum discounts Here is a screen-grab of the same:', 'cisai-wcfm' ); ?></p>
<img src="<?php echo esc_url( $asset_base . 'assets/images/reports-10.png' ); ?>" alt="<?php esc_attr_e( 'Coupons by Date Admin Report', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
<img src="<?php echo esc_url( $asset_base . 'assets/images/reports-11.png' ); ?>" alt="<?php esc_attr_e( 'Reports Additional Image 1', 'cisai-wcfm' ); ?>" class="img-fluid rounded border mb-2">
</div>
  </div>
</div>
<div class="guide-common-section animate-slide mt-4 pt-3 border-top">
  <h4><?php esc_html_e( 'Why Use This Dashboard?', 'cisai-wcfm' ); ?></h4>
  <ul class="list-unstyled">
<li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Streamline operations across all sections', 'cisai-wcfm' ); ?></li>
<li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Real-time data for informed decisions', 'cisai-wcfm' ); ?></li>
<li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Boost sales and customer satisfaction', 'cisai-wcfm' ); ?></li>
<li><i class="bi bi-check-circle text-primary"></i> <?php esc_html_e( 'Easy exports for compliance and analysis', 'cisai-wcfm' ); ?></li>
  </ul>
</div>
  </div>
</div>
  </div>
</div>
<!-- End Modal -->
<?php
} );