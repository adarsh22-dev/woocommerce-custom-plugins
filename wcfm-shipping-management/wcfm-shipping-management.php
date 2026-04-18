<?php
/**
 * Plugin Name: CISAI WCFM Orders Quick Actions (Single File + Invoice Barcode)
 * Description: Vendor dashboard "Orders Quick Actions" popup. Send email/message, add tracking, update status, generate barcode, invoice generation (preview + PDF) with clean prices, auto tax & fees breakdown. Works with WCFM Free.
 * Version: 1.9.6
 * Author: Adarsh Singh
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Helper: Clean formatted price string (strip HTML and decode entities)
 * Returns plain text like "₹299.00" or "INR ₹299.00"
 */
function cisai_wcfm_oqa_clean_price_string( $amount ) {
    $amount = floatval( $amount );

    // Try common WOOCS functions / global
    try {
        if ( function_exists( 'woocs_get_price' ) ) {
            $res = @woocs_get_price( $amount );
            if ( $res ) {
                $clean = wp_strip_all_tags( $res );
                $clean = html_entity_decode( $clean, ENT_QUOTES, 'UTF-8' );
                $clean = trim( preg_replace( '/\s+/', ' ', $clean ) );
                return $clean;
            }
        }
        if ( isset( $GLOBALS['WOOCS'] ) && is_object( $GLOBALS['WOOCS'] ) ) {
            if ( method_exists( $GLOBALS['WOOCS'], 'woocs_get_price' ) ) {
                $res = @call_user_func( array( $GLOBALS['WOOCS'], 'woocs_get_price' ), $amount );
                if ( $res ) {
                    $clean = wp_strip_all_tags( $res );
                    $clean = html_entity_decode( $clean, ENT_QUOTES, 'UTF-8' );
                    $clean = trim( preg_replace( '/\s+/', ' ', $clean ) );
                    return $clean;
                }
            }
            if ( method_exists( $GLOBALS['WOOCS'], 'price_format' ) ) {
                $res = @call_user_func( array( $GLOBALS['WOOCS'], 'price_format' ), $amount );
                if ( $res ) {
                    $clean = wp_strip_all_tags( $res );
                    $clean = html_entity_decode( $clean, ENT_QUOTES, 'UTF-8' );
                    $clean = trim( preg_replace( '/\s+/', ' ', $clean ) );
                    return $clean;
                }
            }
            if ( method_exists( $GLOBALS['WOOCS'], 'get_price' ) ) {
                $res = @call_user_func( array( $GLOBALS['WOOCS'], 'get_price' ), $amount );
                if ( $res ) {
                    $clean = wp_strip_all_tags( $res );
                    $clean = html_entity_decode( $clean, ENT_QUOTES, 'UTF-8' );
                    $clean = trim( preg_replace( '/\s+/', ' ', $clean ) );
                    return $clean;
                }
            }
        }
    } catch ( Exception $e ) {
        // ignore
    }

    // Fallback to wc_price() and strip HTML
    if ( function_exists( 'wc_price' ) ) {
        $res = wc_price( $amount );
        if ( $res ) {
            $clean = wp_strip_all_tags( $res );
            $clean = html_entity_decode( $clean, ENT_QUOTES, 'UTF-8' );
            $clean = trim( preg_replace( '/\s+/', ' ', $clean ) );
            return $clean;
        }
    }

    // Last fallback: number with currency code
    $currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '';
    return trim( number_format( $amount, 2 ) . ( $currency ? ' ' . $currency : '' ) );
}

/* --------------------------
 * Enqueue Styles & JS (single file plugin)
 * -------------------------*/
add_action( 'wp_enqueue_scripts', function() {
    wp_register_style( 'cisai_wcfm_oqa_css', false );
    wp_enqueue_style( 'cisai_wcfm_oqa_css' );

    $css = '
    .cisai-oqa-btn{ display:inline-block;padding:8px 12px;background:#1c2b36;color:#fff;border-radius:6px;cursor:pointer;margin-left:8px;border:none;font-weight:300;font-size: 15px;}
    .cisai-oqa-modal{ position:fixed;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.45);display:none;align-items:flex-start;justify-content:center;padding:40px;z-index:99999;overflow:auto;}
    .cisai-oqa-panel{ background:#fff;border-radius:8px;max-width:none;width:100%;padding:18px;box-shadow:0 8px 30px rgba(0,0,0,0.2); position:relative; }
    .cisai-oqa-close{ position:absolute;right:12px;top:10px;cursor:pointer;font-size:18px;color:#333; }
    .cisai-oqa-table{ width:100%; border-collapse:collapse; margin-top:12px; font-size:13px; }
    .cisai-oqa-table th,.cisai-oqa-table td{ border:1px solid #eee;padding:8px;text-align:left; vertical-align:top;}
    .cisai-oqa-actions button{ margin:4px 6px 0 0; padding:6px 8px; font-size:13px; cursor:pointer; }
    .cisai-oqa-panel h3{ margin:0 0 8px 0; }
    .cisai-oqa-filter-row{ display:flex; gap:8px; align-items:center; margin-bottom:8px; flex-wrap:wrap; }
    .cisai-oqa-input{ padding:6px; border:1px solid #ccc; border-radius:4px; }
    .cisai-oqa-primary{ background:#0b74d1;color:#fff;border:0;padding:7px 12px;border-radius:4px; cursor:pointer; }
    .cisai-oqa-note{ font-size:13px; color:#666; margin-top:8px; }
    .cisai-oqa-download{ background:#28a745; color:#fff; border:0; padding:6px 12px; border-radius:4px; cursor:pointer; margin:0px 0px 0 0; font-size:13px; }
    #cisai_oqa_barcode_canvas{ max-width:100%; margin:10px 0; }
    .cisai-oqa-preview-btn{ background:#ffc107; color:#000; margin:0px; }
    .cisai-oqa-invoice { background:#fff; padding:12px; border-radius:6px; border:1px solid #eee; }
    .cisai-oqa-invoice h2{ margin:0 0 8px 0; }
    @media print { body * { visibility: hidden; } #cisai_oqa_invoice_preview { visibility: visible; position: absolute; left: 0; top: 0; width: 100%; } }
    ';
    wp_add_inline_style( 'cisai_wcfm_oqa_css', $css );

    wp_register_script( 'cisai_wcfm_oqa_js', false );
    wp_enqueue_script( 'cisai_wcfm_oqa_js' );

    $vars = array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'cisai_wcfm_oqa_nonce' ),
        'site_url' => home_url( '/' ),
    );
    wp_localize_script( 'cisai_wcfm_oqa_js', 'cisai_wcfm_oqa_vars', $vars );

    // Footer inline JS and libs
    add_action( 'wp_print_footer_scripts', function() { ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
(function(){
    /* Helpers */
    function isOrdersPage() {
        var href = (window.location.href || '').toLowerCase();
        if (href.indexOf('/orders') !== -1 || href.indexOf('wcfm/orders') !== -1) return true;
        var candidates = document.querySelectorAll('h1, h2, .wcfm_page_title, .wcfm-page-title, .page-title, .wcfm-submenu-title');
        for (var i=0;i<candidates.length;i++){
            if ((candidates[i].textContent||'').toLowerCase().includes('orders')) return true;
        }
        return false;
    }
    function insertQuickButton(){
        if (document.querySelector('.cisai-oqa-btn-inserted')) return;
        if (!isOrdersPage()) return;
        var header = document.querySelector('.wcfm_page_title')
                  || document.querySelector('.wcfm-page-title')
                  || document.querySelector('.page-title')
                  || document.querySelector('h2')
                  || document.querySelector('h1');
        if (!header) header = document.querySelector('.wcfm-container, .wcfm-wrap, .wcfm-page-content, .content, .container');
        if (!header) return;
        var container = document.createElement('div');
        container.className = 'cisai-oqa-btn-inserted';
        container.style.display = 'inline-block';
        container.style.marginLeft = '8px';
        var btn = document.createElement('button');
        btn.className = 'cisai-oqa-btn';
        btn.innerText = 'Orders Quick Actions';
        btn.addEventListener('click', openModal);
        container.appendChild(btn);
        try { header.appendChild(container); } catch(e) { document.body.insertBefore(container, document.body.firstChild); }
    }

    var modal = null;
    function buildModal(){
        if (modal) return modal;
        modal = document.createElement('div');
        modal.className = 'cisai-oqa-modal';
        modal.innerHTML = '<div class="cisai-oqa-panel" role="dialog" aria-modal="true">'
            + '<span class="cisai-oqa-close" title="Close">✕</span>'
            + '<h3>Fresh Orders - Quick Actions</h3>'
            + '<div class="cisai-oqa-filter-row">'
            + 'Filter: <select id="cisai_oqa_filter" class="cisai-oqa-input"><option value="all">All Fresh</option><option value="pending">Pending</option><option value="processing">Processing</option><option value="on-hold">On Hold</option><option value="completed">Completed</option></select>'
            + 'Search: <input id="cisai_oqa_search" class="cisai-oqa-input" placeholder="Order ID / customer / phone / email" style="width:320px" />'
            + '<button id="cisai_oqa_refresh" class="cisai-oqa-primary">Refresh</button>'
            + '</div>'
            + '<div id="cisai_oqa_content"><em>Loading...</em></div>'
            + '</div>';
        document.body.appendChild(modal);
        modal.querySelector('.cisai-oqa-close').addEventListener('click', closeModal);
        modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });
        modal.querySelector('#cisai_oqa_refresh').addEventListener('click', loadOrders);
        modal.querySelector('#cisai_oqa_search').addEventListener('input', function(){ clearTimeout(window.cisai_oqa_input_timer); window.cisai_oqa_input_timer = setTimeout(loadOrders, 450); });
        modal.querySelector('#cisai_oqa_filter').addEventListener('change', loadOrders);
        return modal;
    }
    function openModal(){ buildModal(); modal.style.display = 'flex'; loadOrders(); }
    function closeModal(){ if(modal) modal.style.display='none'; }
    function loadOrders(){
        var content = document.getElementById('cisai_oqa_content');
        if (!content) return;
        content.innerHTML = '<em>Loading orders...</em>';
        var filter = document.getElementById('cisai_oqa_filter').value;
        var search = document.getElementById('cisai_oqa_search').value || '';
        var data = new URLSearchParams();
        data.append('action','cisai_wcfm_oqa_fetch_orders');
        data.append('nonce', cisai_wcfm_oqa_vars.nonce);
        data.append('filter', filter);
        data.append('search', search);
        fetch(cisai_wcfm_oqa_vars.ajax_url,{
            method:'POST',credentials:'same-origin',
            headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
            body: data.toString()
        }).then(r=>r.json()).then(renderOrders).catch(e=>{content.innerHTML='<div class="cisai-oqa-note">Error loading orders: ' + (e.message || e) + '</div>';});
    }
    function renderOrders(resp){
        var content=document.getElementById('cisai_oqa_content'); if(!content) return;
        if(!resp || !resp.success || !resp.data || !resp.data.length){ content.innerHTML='<div class="cisai-oqa-note">No orders</div>'; return; }
        var html='<table class="cisai-oqa-table"><thead><tr>'
            + '<th>Order</th><th>Customer</th><th>Phone / Email</th><th>Amount</th><th>Status</th><th>Tracking</th><th>Actions</th>'
            + '</tr></thead><tbody>';
        resp.data.forEach(o=>{
            html+='<tr data-order-id="'+o.id+'">'
                + '<td>#'+o.id+'<br><small>'+escapeHtml(o.date)+'</small></td>'
                + '<td>'+escapeHtml(o.customer_name)+'<br><small>'+escapeHtml(o.billing_address||'')+'</small></td>'
                + '<td>'+escapeHtml(o.phone||'')+'<br><small>'+escapeHtml(o.email||'')+'</small></td>'
                + '<td>' + escapeHtml(o.total_display||o.total) + '</td>'
                + '<td><strong>'+escapeHtml(o.status_name)+'</strong></td>'
                + '<td>'+(o.tracking?escapeHtml(o.tracking):'<em>—</em>')+'</td>'
                + '<td class="cisai-oqa-actions">'
                + '<button class="cisai-oqa-email" data-id="'+o.id+'">Email</button>'
                + '<button class="cisai-oqa-sms" data-id="'+o.id+'">Message</button>'
                + '<button class="cisai-oqa-track" data-id="'+o.id+'">'+(o.tracking?'Update Tracking':'Add Tracking')+'</button>'
                + '<button class="cisai-oqa-status" data-id="'+o.id+'" data-new="processing">Processing</button>'
                + '<button class="cisai-oqa-status" data-id="'+o.id+'" data-new="completed">Complete</button>'
                + '<button class="cisai-oqa-status" data-id="'+o.id+'" data-new="refunded">Refund</button>'
                + '<button class="cisai-oqa-status" data-id="'+o.id+'" data-new="cancelled">Cancel</button>'
                + '<button class="cisai-oqa-barcode" data-id="'+o.id+'">Barcode</button>'
                + '<button class="cisai-oqa-invoice-btn" data-id="'+o.id+'">Invoice</button>'
                + '</td></tr>';
        });
        html+='</tbody></table><div id="cisai_oqa_details" style="margin-top:12px"></div>';
        content.innerHTML=html;
        document.querySelectorAll('.cisai-oqa-email').forEach(btn=>btn.addEventListener('click',emailHandler));
        document.querySelectorAll('.cisai-oqa-sms').forEach(btn=>btn.addEventListener('click',smsHandler));
        document.querySelectorAll('.cisai-oqa-track').forEach(btn=>btn.addEventListener('click',trackHandler));
        document.querySelectorAll('.cisai-oqa-status').forEach(btn=>btn.addEventListener('click',statusHandler));
        document.querySelectorAll('.cisai-oqa-barcode').forEach(btn=>btn.addEventListener('click',barcodeHandler));
        document.querySelectorAll('.cisai-oqa-invoice-btn').forEach(btn=>btn.addEventListener('click',invoiceHandler));
    }
    function escapeHtml(str){ if(!str && str!==0) return ''; return String(str).replace(/[&<>"'`=\/]/g, s=>'&#'+s.charCodeAt(0)+';'); }

    /* Email */
    function emailHandler(){
        var id=this.getAttribute('data-id');
        var d=document.getElementById('cisai_oqa_details');
        d.innerHTML='<h4>Send Email for Order #'+id+'</h4>'
            +'<input id="cisai_oqa_email_subject" placeholder="Subject" class="cisai-oqa-input" style="width:100%;margin-bottom:6px" />'
            +'<textarea id="cisai_oqa_email_body" placeholder="Message" class="cisai-oqa-input" style="width:100%;height:120px"></textarea>'
            +'<br><label><input type="checkbox" id="cisai_oqa_include_tracking" /> Include tracking number (if present)</label>'
            +'<br><button id="cisai_oqa_send_email" class="cisai-oqa-primary" data-id="'+id+'" style="margin-top:6px">Send Email</button>';
        document.getElementById('cisai_oqa_send_email').addEventListener('click',function(){
            var btn=this; var subject=document.getElementById('cisai_oqa_email_subject').value;
            var body=document.getElementById('cisai_oqa_email_body').value; var includeTracking = document.getElementById('cisai_oqa_include_tracking').checked;
            btn.disabled=true;
            var data=new URLSearchParams();
            data.append('action','cisai_wcfm_oqa_send_email'); data.append('nonce',cisai_wcfm_oqa_vars.nonce);
            data.append('order_id',this.getAttribute('data-id')); data.append('subject',subject); data.append('body',body);
            data.append('include_tracking', includeTracking ? '1' : '0');
            fetch(cisai_wcfm_oqa_vars.ajax_url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:data.toString()})
            .then(r=>r.json()).then(r=>{ alert(r.success?'Email sent/queued':'Error: '+(r.data||'unknown')); btn.disabled=false;}).catch(e=>{alert('Network error: ' + (e.message || e));btn.disabled=false;});
        });
    }

    /* SMS / Message */
    function smsHandler(){
        var id=this.getAttribute('data-id'); var d=document.getElementById('cisai_oqa_details');
        d.innerHTML='<h4>Send Message for Order #'+id+'</h4>'
            +'<textarea id="cisai_oqa_sms_body" class="cisai-oqa-input" placeholder="Message (will be saved as order note)" style="width:100%;height:120px"></textarea>'
            +'<br><label><input type="checkbox" id="cisai_oqa_sms_visible" checked /> Visible to customer</label>'
            +'<br><button id="cisai_oqa_send_sms" class="cisai-oqa-primary" data-id="'+id+'" style="margin-top:6px">Send Message</button>';
        document.getElementById('cisai_oqa_send_sms').addEventListener('click',function(){
            var btn=this; var body=document.getElementById('cisai_oqa_sms_body').value; var visible = document.getElementById('cisai_oqa_sms_visible').checked;
            btn.disabled=true;
            var data=new URLSearchParams(); data.append('action','cisai_wcfm_oqa_send_message'); data.append('nonce',cisai_wcfm_oqa_vars.nonce);
            data.append('order_id',this.getAttribute('data-id')); data.append('body',body); data.append('visible', visible ? '1' : '0');
            fetch(cisai_wcfm_oqa_vars.ajax_url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:data.toString()})
            .then(r=>r.json()).then(r=>{ alert(r.success?'Message saved as note':'Error: '+(r.data||'unknown')); btn.disabled=false;}).catch(e=>{alert('Network error: ' + (e.message || e));btn.disabled=false;});
        });
    }

    /* Tracking */
    function trackHandler(){
        var id=this.getAttribute('data-id'); var d=document.getElementById('cisai_oqa_details');
        d.innerHTML='<h4>Tracking for Order #'+id+'</h4>'
            +'<input id="cisai_oqa_tracking" class="cisai-oqa-input" placeholder="Enter tracking number or generate" style="width:60%;margin-right:6px" />'
            +'<button id="cisai_oqa_gen_track" class="cisai-oqa-input" style="padding:6px 10px">Generate</button>'
            +'<button id="cisai_oqa_save_track" class="cisai-oqa-primary" style="margin-left:6px">Save Tracking</button>'
            +'<div class="cisai-oqa-note">After saving, order note added & email sent.</div>';
        document.getElementById('cisai_oqa_gen_track').addEventListener('click',()=>{document.getElementById('cisai_oqa_tracking').value='TRK'+Date.now().toString().slice(-8)+Math.floor(Math.random()*900+100);});
        document.getElementById('cisai_oqa_save_track').addEventListener('click',function(){
            var btn=this; var tracking=document.getElementById('cisai_oqa_tracking').value;
            if(!tracking){alert('Enter tracking number');return;} btn.disabled=true;
            var data=new URLSearchParams(); data.append('action','cisai_wcfm_oqa_add_tracking'); data.append('nonce',cisai_wcfm_oqa_vars.nonce);
            data.append('order_id',id); data.append('tracking',tracking);
            fetch(cisai_wcfm_oqa_vars.ajax_url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:data.toString()})
            .then(r=>r.json()).then(r=>{ alert(r.success?'Tracking saved':'Error: '+(r.data||'unknown')); btn.disabled=false; loadOrders();}).catch(e=>{alert('Network error: ' + (e.message || e));btn.disabled=false;});
        });
    }

    /* Status */
    function statusHandler(){
        var id=this.getAttribute('data-id'); var newStatus=this.getAttribute('data-new');
        if(!confirm('Change order #'+id+' status to '+newStatus+'?')) return;
        var data=new URLSearchParams(); data.append('action','cisai_wcfm_oqa_update_status'); data.append('nonce',cisai_wcfm_oqa_vars.nonce);
        data.append('order_id',id); data.append('status',newStatus);
        fetch(cisai_wcfm_oqa_vars.ajax_url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:data.toString()})
        .then(r=>r.json()).then(r=>{ alert(r.success?'Status updated':'Error: '+(r.data||'unknown')); loadOrders(); }).catch(e=>{alert('Network error: ' + (e.message || e));});
    }

    /* Row barcode (small) */
    function barcodeHandler() {
        var id = this.getAttribute('data-id');
        var d = document.getElementById('cisai_oqa_details');
        d.innerHTML = '<h4>Barcode for Order #' + id + '</h4>'
            + '<input id="cisai_oqa_barcode_text" class="cisai-oqa-input" placeholder="Custom barcode text (default: order ID)" value="' + id + '" style="width:100%;margin-bottom:6px" />'
            + '<button id="cisai_oqa_gen_barcode" class="cisai-oqa-primary" style="margin-bottom:10px">Generate Barcode</button>'
            + '<canvas id="cisai_oqa_barcode_canvas"></canvas>'
            + '<button id="cisai_oqa_download_barcode" class="cisai-oqa-download">Download PNG</button>'
            + '<div class="cisai-oqa-note">Barcode in Code 128 format. Enter custom text above and click Generate.</div>';
        var input = document.getElementById('cisai_oqa_barcode_text');
        var genBtn = document.getElementById('cisai_oqa_gen_barcode');
        var canvas = document.getElementById('cisai_oqa_barcode_canvas');
        var downloadBtn = document.getElementById('cisai_oqa_download_barcode');
        function generateBarcode() {
            var text = input.value.trim();
            if (!text) { alert('Enter barcode text'); return; }
            JsBarcode(canvas, text, { format: 'CODE128', width: 2, height: 60, displayValue: true, fontSize: 14 });
        }
        genBtn.addEventListener('click', generateBarcode);
        input.addEventListener('input', function() { clearTimeout(window.cisai_oqa_barcode_timer); window.cisai_oqa_barcode_timer = setTimeout(generateBarcode, 400); });
        downloadBtn.addEventListener('click', function() {
            var link = document.createElement('a'); link.download = 'barcode_' + (input.value.trim() || id) + '.png'; link.href = canvas.toDataURL(); link.click();
        });
        generateBarcode();
    }

    /* Invoice (preview, PDF, print) - includes barcode in preview */
    function invoiceHandler() {
        var id = this.getAttribute('data-id');
        var d = document.getElementById('cisai_oqa_details');
        d.innerHTML = '<h4>Invoice for Order #' + id + '</h4>'
            + '<div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:8px;">'
            + '<button id="cisai_oqa_preview_invoice" class="cisai-oqa-primary">Preview Invoice</button>'
            + '<button id="cisai_oqa_download_invoice" class="cisai-oqa-download">Download PDF</button>'
            + '<button id="cisai_oqa_print_invoice" class="cisai-oqa-preview-btn">Print</button>'
            + '<label style="margin-left:8px">Invoice #: <input id="cisai_oqa_invoice_number" class="cisai-oqa-input" style="width:160px" placeholder="Auto or enter custom" /></label>'
            + '</div>'
            + '<div id="cisai_oqa_invoice_preview" class="cisai-oqa-invoice" style="display:none;"></div>';

        document.getElementById('cisai_oqa_preview_invoice').addEventListener('click', function(){ fetchInvoiceAndRender(id, false); });
        document.getElementById('cisai_oqa_download_invoice').addEventListener('click', function(){ fetchInvoiceAndRender(id, true); });
        document.getElementById('cisai_oqa_print_invoice').addEventListener('click', function(){ fetchInvoiceAndRender(id, 'print'); });
    }

    function fetchInvoiceAndRender(orderId, action){
        var data=new URLSearchParams(); data.append('action','cisai_wcfm_oqa_get_invoice'); data.append('nonce',cisai_wcfm_oqa_vars.nonce);
        data.append('order_id', orderId);
        fetch(cisai_wcfm_oqa_vars.ajax_url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:data.toString()})
        .then(r=>r.json()).then(function(resp){
            if(!resp || !resp.success){ alert('Could not fetch order details'); return; }
            var o = resp.data;
            var invNumEl = document.getElementById('cisai_oqa_invoice_number');
            var invoiceNumber = (invNumEl && invNumEl.value.trim()) || ('INV-' + (o.id) + '-' + (new Date().getFullYear()));
            var html = buildInvoiceHtml(o, invoiceNumber);
            var preview = document.getElementById('cisai_oqa_invoice_preview');
            preview.innerHTML = html;
            preview.style.display = 'block';

            // Generate barcode inside the invoice preview using invoiceNumber
            try {
                // create an SVG placeholder -> use JsBarcode to render
                var svgEl = preview.querySelector('#cisai_oqa_invoice_barcode_svg');
                if (svgEl && typeof JsBarcode === 'function') {
                    JsBarcode(svgEl, invoiceNumber, { format: 'CODE128', width:1.8, height:60, displayValue:true, fontSize:14 });
                }
            } catch(e) {
                console.warn('Barcode generation failed', e);
            }

            if(action === false) { preview.scrollIntoView({behavior:'smooth'}); return; }

            // Convert to canvas -> PDF or print
            html2canvas(preview, { scale: 2, useCORS:true }).then(function(canvas){
                var imgData = canvas.toDataURL('image/png');
                var pdf = new jspdf.jsPDF('p', 'mm', 'a4');
                var pageWidth = pdf.internal.pageSize.getWidth();
                var imgProps = pdf.getImageProperties(imgData);
                var pdfWidth = pageWidth - 20; 
                var pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
                pdf.addImage(imgData, 'PNG', 10, 10, pdfWidth, pdfHeight);
                if(action === 'print') {
                    var blob = pdf.output('bloburl');
                    window.open(blob, '_blank');
                } else {
                    pdf.save('invoice_' + invoiceNumber + '.pdf');
                }
            }).catch(function(err){ alert('Could not generate PDF: ' + (err && err.message ? err.message : err)); });
        }).catch(function(e){ alert('Network error: ' + (e.message || e)); });
    }

    function buildInvoiceHtml(o, invoiceNumber){
        var lines = [];
        lines.push('<div style="font-family:Arial, Helvetica, sans-serif; color:#333">');
        lines.push('<div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">');
        lines.push('<div><h2 style="margin:0 0 4px 0;">Invoice</h2><div>Invoice #: <strong>' + escapeHtml(invoiceNumber) + '</strong></div><div>Order #: <strong>#' + escapeHtml(String(o.id)) + '</strong></div><div>Date: ' + escapeHtml(o.date) + '</div></div>');
        lines.push('<div style="text-align:right;">' + (o.store_name ? '<strong>' + escapeHtml(o.store_name) + '</strong><br/>' : '') + (escapeHtml(o.store_addr).replace(/\n/g,"<br/>") ? escapeHtml(o.store_addr).replace(/\n/g,"<br/>") + '<br/>' : '') + '<div style="margin-top:6px;"><svg id="cisai_oqa_invoice_barcode_svg"></svg></div></div>');
        lines.push('</div>');
        lines.push('<hr style="margin:12px 0;border:none;border-top:1px solid #eee" />');
        lines.push('<div style="display:flex; gap:12px;">');
        lines.push('<div style="flex:1;"><strong>Bill To:</strong><br/>' + escapeHtml(o.billing.name || '') + '<br/>' + (escapeHtml(o.billing.address || '').replace(/\n/g,"<br/>")) + '<br/>' + escapeHtml(o.billing.email || '') + '<br/>' + escapeHtml(o.billing.phone || '') + '</div>');
        lines.push('<div style="flex:1;"><strong>Ship To:</strong><br/>' + escapeHtml(o.shipping.name || '') + '<br/>' + (escapeHtml(o.shipping.address || '').replace(/\n/g,"<br/>")) + '</div>');
        lines.push('</div>');
        lines.push('<table style="width:100%; border-collapse:collapse; margin-top:12px;"><thead><tr><th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Item</th><th style="text-align:right;border-bottom:1px solid #ddd;padding:8px;">Qty</th><th style="text-align:right;border-bottom:1px solid #ddd;padding:8px;">Price</th><th style="text-align:right;border-bottom:1px solid #ddd;padding:8px;">Total</th></tr></thead><tbody>');
        o.items.forEach(function(it){
            var price = it.price_display || it.price || it.total_display || it.total;
            var total = it.total_display || it.total;
            lines.push('<tr><td style="padding:8px;border-bottom:1px dashed #eee;">' + escapeHtml(it.name) + (it.sku ? '<br><small>SKU: '+escapeHtml(it.sku)+'</small>' : '') + '</td><td style="text-align:right;padding:8px;border-bottom:1px dashed #eee;">' + escapeHtml(String(it.qty)) + '</td><td style="text-align:right;padding:8px;border-bottom:1px dashed #eee;">' + escapeHtml(price) + '</td><td style="text-align:right;padding:8px;border-bottom:1px dashed #eee;">' + escapeHtml(total) + '</td></tr>');
        });
        lines.push('</tbody></table>');

        // Totals block with fees, taxes, discounts
        lines.push('<div style="display:flex; justify-content:flex-end; margin-top:12px;">');
        lines.push('<table style="width:-webkit-fill-available;border-collapse:collapse;"><tbody>');
        lines.push('<tr><td style="padding:8px;border-top:1px solid #eee;">Subtotal</td><td style="text-align:right;padding:8px;border-top:1px solid #eee;">' + escapeHtml(o.subtotal_display) + '</td></tr>');

        // Fees (order item fees)
        if ( o.fees && o.fees.length ) {
            o.fees.forEach(function(f){
                lines.push('<tr><td style="padding:8px;">' + escapeHtml(f.name) + '</td><td style="text-align:right;padding:8px;">' + escapeHtml(f.amount_display) + '</td></tr>');
            });
        }

        // Shipping
        if ( o.shipping_total_display ) {
            lines.push('<tr><td style="padding:8px;">Shipping</td><td style="text-align:right;padding:8px;">' + escapeHtml(o.shipping_total_display) + '</td></tr>');
        }

        // Discounts / coupons
        if ( o.discount_display && parseFloat(String(o.discount_raw || 0)) != 0 ) {
            lines.push('<tr><td style="padding:8px;">Discount</td><td style="text-align:right;padding:8px;">-' + escapeHtml(o.discount_display) + '</td></tr>');
        }

        // Taxes: show breakdown
        if ( o.taxes && o.taxes.length ) {
            o.taxes.forEach(function(t){
                lines.push('<tr><td style="padding:8px;">' + escapeHtml(t.label) + '</td><td style="text-align:right;padding:8px;">' + escapeHtml(t.amount_display) + '</td></tr>');
            });
        }

        lines.push('<tr><td style="padding:10px;border-top:1px solid #ddd;"><strong>Total</strong></td><td style="text-align:right;padding:10px;border-top:1px solid #ddd;"><strong>' + escapeHtml(o.total_display) + '</strong></td></tr>');
        lines.push('</tbody></table>');
        lines.push('</div>');

        if(o.tracking) {
            lines.push('<div style="margin-top:12px;">Tracking: <strong>' + escapeHtml(o.tracking) + '</strong></div>');
        }
        lines.push('<div style="margin-top:18px;font-size:12px;color:#666;">This is a system generated invoice.</div>');
        lines.push('</div>');
        return lines.join('');
    }

    function tryInsert(){ try{ insertQuickButton(); }catch(e){console.error(e);} }
    document.addEventListener('DOMContentLoaded',tryInsert);
    setTimeout(tryInsert,800); setTimeout(tryInsert,2200); setTimeout(tryInsert,4200);
})();
</script>
    <?php }, 100 );
});

/* --------------------------
 * AJAX: fetch orders (vendor only)
 * -------------------------*/
add_action( 'wp_ajax_cisai_wcfm_oqa_fetch_orders', 'cisai_wcfm_oqa_fetch_orders' );
add_action( 'wp_ajax_nopriv_cisai_wcfm_oqa_fetch_orders', 'cisai_wcfm_oqa_fetch_orders' );
function cisai_wcfm_oqa_fetch_orders() {
    while ( ob_get_level() ) ob_end_clean();
    if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'cisai_wcfm_oqa_nonce' ) ) {
        wp_send_json_error( 'invalid_nonce' );
    }
    $filter = sanitize_text_field( wp_unslash( $_POST['filter'] ?? 'all' ) );
    $search = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
    $fresh_statuses = array( 'pending', 'on-hold', 'processing' );
    if ( $filter === 'completed' ) {
        $statuses = array( 'completed' );
    } elseif ( $filter && $filter !== 'all' ) {
        $statuses = array( $filter );
    } else {
        $statuses = $fresh_statuses;
    }
    $limit = ( ! empty( $search ) ? 100 : 40 );
    $args  = array(
        'limit'   => $limit,
        'orderby' => 'date',
        'order'   => 'DESC',
        'status'  => $statuses,
    );
    $orders = wc_get_orders( $args );
    $out    = array();
    $current_user = wp_get_current_user();
    $vendor_id = $current_user->ID ?: 0;
    foreach ( $orders as $order ) {
        try {
            if ( ! $order ) continue;
            $vendor_found = false;
            foreach ( $order->get_items() as $item ) {
                $item_id = is_object( $item ) && method_exists( $item, 'get_id' ) ? $item->get_id() : ( isset( $item['id'] ) ? $item['id'] : 0 );
                $item_vendor = '';
                if ( $item_id ) {
                    $item_vendor = wc_get_order_item_meta( $item_id, '_vendor_id', true );
                    if ( empty( $item_vendor ) ) $item_vendor = wc_get_order_item_meta( $item_id, 'vendor_id', true );
                }
                if ( $item_vendor && intval( $item_vendor ) === intval( $vendor_id ) ) {
                    $vendor_found = true;
                    break;
                }
            }
            if ( ! $vendor_found && ! in_array( 'administrator', (array) $current_user->roles ) ) continue;
            $order_id = $order->get_id();
            $status = $order->get_status();
            $status_name = wc_get_order_status_name( $status );
            $date = $order->get_date_created() ? $order->get_date_created()->date_i18n( 'Y-m-d H:i' ) : '';
            $customer_name = $order->get_formatted_billing_full_name() ?: trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
            $billing_addr = trim( $order->get_billing_address_1() . ' ' . $order->get_billing_city() . ' ' . $order->get_billing_country() );
            $phone = $order->get_billing_phone();
            $email = $order->get_billing_email();
            $total_display = cisai_wcfm_oqa_clean_price_string( (float) $order->get_total() );
            $tracking = get_post_meta( $order_id, '_tracking_number', true ) ?: get_post_meta( $order_id, 'tracking_number', true ) ?: '';
            if ( ! empty( $search ) ) {
                $needle = strtolower( $search );
                $hay = strtolower( $order_id . ' ' . $customer_name . ' ' . ( $phone ?: '' ) . ' ' . ( $email ?: '' ) );
                if ( strpos( $hay, $needle ) === false ) continue;
            }
            $out[] = array(
                'id' => $order_id,
                'date' => $date,
                'customer_name' => $customer_name,
                'billing_address' => $billing_addr,
                'phone' => $phone,
                'email' => $email,
                'total' => (float) $order->get_total(),
                'total_display' => $total_display,
                'status' => $status,
                'status_name' => $status_name,
                'tracking' => $tracking,
            );
        } catch ( Exception $e ) {
            continue;
        }
    }
    wp_send_json_success( $out );
}

/* --------------------------
 * AJAX: send email
 * -------------------------*/
add_action( 'wp_ajax_cisai_wcfm_oqa_send_email', 'cisai_wcfm_oqa_send_email' );
function cisai_wcfm_oqa_send_email() {
    while ( ob_get_level() ) ob_end_clean();
    if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'cisai_wcfm_oqa_nonce' ) ) {
        wp_send_json_error( 'invalid_nonce' );
    }
    $order_id = intval( $_POST['order_id'] ?? 0 );
    if ( ! $order_id ) wp_send_json_error( 'no_order' );
    $subject = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) );
    $body_raw = wp_unslash( $_POST['body'] ?? '' );
    $body = wp_kses_post( $body_raw );
    $include_tracking = ( ! empty( $_POST['include_tracking'] ) && $_POST['include_tracking'] == '1' );
    $order = wc_get_order( $order_id );
    if ( ! $order ) wp_send_json_error( 'invalid_order' );
    // permission
    $current_user = wp_get_current_user();
    $vendor_id = $current_user->ID;
    $vendor_ok = false;
    foreach ( $order->get_items() as $item ) {
        $item_id = is_object( $item ) && method_exists( $item, 'get_id' ) ? $item->get_id() : ( isset( $item['id'] ) ? $item['id'] : 0 );
        $item_vendor = '';
        if ( $item_id ) {
            $item_vendor = wc_get_order_item_meta( $item_id, '_vendor_id', true );
            if ( empty( $item_vendor ) ) $item_vendor = wc_get_order_item_meta( $item_id, 'vendor_id', true );
        }
        if ( $item_vendor && intval( $item_vendor ) === intval( $vendor_id ) ) { $vendor_ok = true; break; }
    }
    if ( ! $vendor_ok && ! in_array( 'administrator', (array) $current_user->roles ) ) wp_send_json_error( 'no_permission' );
    $to = $order->get_billing_email();
    if ( ! $to ) wp_send_json_error( 'no_email' );
    $headers = array( 'Content-Type: text/html; charset=UTF-8' );
    $from = get_option( 'admin_email' );
    $headers[] = 'From: ' . get_bloginfo( 'name' ) . ' <' . $from . '>';
    $full_body = '<p>Dear ' . esc_html( $order->get_billing_first_name() ) . ',</p>';
    if ( $include_tracking ) {
        $tracking = get_post_meta( $order_id, '_tracking_number', true ) ?: get_post_meta( $order_id, 'tracking_number', true ) ?: '';
        if ( $tracking ) {
            $full_body .= '<p>Your tracking number: <strong>' . esc_html( $tracking ) . '</strong></p>';
        }
    }
    $full_body .= wp_kses_post( $body );
    $full_body .= '<p>Regards,<br>' . esc_html( get_bloginfo( 'name' ) ) . '</p>';
    $sent = wp_mail( $to, $subject, $full_body, $headers );
    if ( $sent ) {
        $note = sprintf( 'Quick action email sent by %s: %s', $current_user->display_name ?: $current_user->user_login, wp_strip_all_tags( $subject ) );
        $order->add_order_note( $note );
        wp_send_json_success( 'sent' );
    }
    wp_send_json_error( 'failed' );
}

/* --------------------------
 * AJAX: send message (order note)
 * -------------------------*/
add_action( 'wp_ajax_cisai_wcfm_oqa_send_message', 'cisai_wcfm_oqa_send_message' );
function cisai_wcfm_oqa_send_message() {
    while ( ob_get_level() ) ob_end_clean();
    if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'cisai_wcfm_oqa_nonce' ) ) {
        wp_send_json_error( 'invalid_nonce' );
    }
    $order_id = intval( $_POST['order_id'] ?? 0 );
    if ( ! $order_id ) wp_send_json_error( 'no_order' );
    $body = sanitize_textarea_field( wp_unslash( $_POST['body'] ?? '' ) );
    $visible = ( ! empty( $_POST['visible'] ) && $_POST['visible'] == '1' );
    $order = wc_get_order( $order_id );
    if ( ! $order ) wp_send_json_error( 'invalid_order' );
    $current_user = wp_get_current_user();
    $vendor_id = $current_user->ID;
    $vendor_ok = false;
    foreach ( $order->get_items() as $item ) {
        $item_id = is_object( $item ) && method_exists( $item, 'get_id' ) ? $item->get_id() : ( isset( $item['id'] ) ? $item['id'] : 0 );
        $item_vendor = '';
        if ( $item_id ) {
            $item_vendor = wc_get_order_item_meta( $item_id, '_vendor_id', true );
            if ( empty( $item_vendor ) ) $item_vendor = wc_get_order_item_meta( $item_id, 'vendor_id', true );
        }
        if ( $item_vendor && intval( $item_vendor ) === intval( $vendor_id ) ) { $vendor_ok = true; break; }
    }
    if ( ! $vendor_ok && ! in_array( 'administrator', (array) $current_user->roles ) ) wp_send_json_error( 'no_permission' );
    $note_priv = sprintf( 'Quick action message from %s: %s', $current_user->display_name ?: $current_user->user_login, wp_strip_all_tags( $body ) );
    $order->add_order_note( $note_priv, true ); // visible to customer
    $order->add_order_note( sprintf( 'Quick action message (private copy) from %s', $current_user->display_name ?: $current_user->user_login ), false );
    wp_send_json_success( 'saved' );
}

/* --------------------------
 * AJAX: add tracking
 * -------------------------*/
add_action( 'wp_ajax_cisai_wcfm_oqa_add_tracking', 'cisai_wcfm_oqa_add_tracking' );
function cisai_wcfm_oqa_add_tracking() {
    while ( ob_get_level() ) ob_end_clean();
    if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'cisai_wcfm_oqa_nonce' ) ) {
        wp_send_json_error( 'invalid_nonce' );
    }
    $order_id = intval( $_POST['order_id'] ?? 0 );
    $tracking = sanitize_text_field( wp_unslash( $_POST['tracking'] ?? '' ) );
    if ( ! $order_id ) wp_send_json_error( 'no_order' );
    if ( empty( $tracking ) ) wp_send_json_error( 'no_tracking' );
    $order = wc_get_order( $order_id );
    if ( ! $order ) wp_send_json_error( 'invalid_order' );
    $current_user = wp_get_current_user();
    $vendor_id = $current_user->ID;
    $vendor_ok = false;
    foreach ( $order->get_items() as $item ) {
        $item_id = is_object( $item ) && method_exists( $item, 'get_id' ) ? $item->get_id() : ( isset( $item['id'] ) ? $item['id'] : 0 );
        $item_vendor = '';
        if ( $item_id ) {
            $item_vendor = wc_get_order_item_meta( $item_id, '_vendor_id', true );
            if ( empty( $item_vendor ) ) $item_vendor = wc_get_order_item_meta( $item_id, 'vendor_id', true );
        }
        if ( $item_vendor && intval( $item_vendor ) === intval( $vendor_id ) ) { $vendor_ok = true; break; }
    }
    if ( ! $vendor_ok && ! in_array( 'administrator', (array) $current_user->roles ) ) wp_send_json_error( 'no_permission' );
    update_post_meta( $order_id, '_tracking_number', $tracking );
    update_post_meta( $order_id, 'tracking_number', $tracking );
    $order->add_order_note( sprintf( 'Tracking number added/updated: %s (by %s)', $tracking, $current_user->display_name ?: $current_user->user_login ) );
    $to = $order->get_billing_email();
    if ( $to ) {
        $subject = sprintf( 'Tracking for your order #%d', $order_id );
        $body = sprintf( 'Hello %s,<br><br>Your order #%d tracking number: <strong>%s</strong><br><br>Regards,<br>%s', esc_html( $order->get_billing_first_name() ), $order_id, esc_html( $tracking ), esc_html( get_bloginfo( 'name' ) ) );
        wp_mail( $to, $subject, $body, array( 'Content-Type' => 'text/html; charset=UTF-8' ) );
    }
    wp_send_json_success( 'tracking_saved' );
}

/* --------------------------
 * AJAX: update status
 * -------------------------*/
add_action( 'wp_ajax_cisai_wcfm_oqa_update_status', 'cisai_wcfm_oqa_update_status' );
function cisai_wcfm_oqa_update_status() {
    while ( ob_get_level() ) ob_end_clean();
    if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'cisai_wcfm_oqa_nonce' ) ) {
        wp_send_json_error( 'invalid_nonce' );
    }
    $order_id = intval( $_POST['order_id'] ?? 0 );
    $status = sanitize_text_field( wp_unslash( $_POST['status'] ?? '' ) );
    if ( ! $order_id ) wp_send_json_error( 'no_order' );
    if ( empty( $status ) ) wp_send_json_error( 'no_status' );
    $order = wc_get_order( $order_id );
    if ( ! $order ) wp_send_json_error( 'invalid_order' );
    $current_user = wp_get_current_user();
    $vendor_id = $current_user->ID;
    $vendor_ok = false;
    foreach ( $order->get_items() as $item ) {
        $item_id = is_object( $item ) && method_exists( $item, 'get_id' ) ? $item->get_id() : ( isset( $item['id'] ) ? $item['id'] : 0 );
        $item_vendor = '';
        if ( $item_id ) {
            $item_vendor = wc_get_order_item_meta( $item_id, '_vendor_id', true );
            if ( empty( $item_vendor ) ) $item_vendor = wc_get_order_item_meta( $item_id, 'vendor_id', true );
        }
        if ( $item_vendor && intval( $item_vendor ) === intval( $vendor_id ) ) { $vendor_ok = true; break; }
    }
    if ( ! $vendor_ok && ! in_array( 'administrator', (array) $current_user->roles ) ) wp_send_json_error( 'no_permission' );
    $allowed = array( 'processing', 'completed', 'refunded', 'cancelled', 'on-hold', 'pending' );
    if ( ! in_array( $status, $allowed, true ) ) wp_send_json_error( 'invalid_status' );
    try {
        $order->update_status( $status, sprintf( 'Status changed via Orders Quick Actions by %s', $current_user->display_name ?: $current_user->user_login ) );
        wp_send_json_success( 'status_updated' );
    } catch ( Exception $e ) {
        wp_send_json_error( 'error' );
    }
}

/* --------------------------
 * AJAX: get invoice data (cleaned + taxes + fees + discounts)
 * -------------------------*/
add_action( 'wp_ajax_cisai_wcfm_oqa_get_invoice', 'cisai_wcfm_oqa_get_invoice' );
function cisai_wcfm_oqa_get_invoice() {
    while ( ob_get_level() ) ob_end_clean();
    if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'cisai_wcfm_oqa_nonce' ) ) {
        wp_send_json_error( 'invalid_nonce' );
    }
    $order_id = intval( $_POST['order_id'] ?? 0 );
    if ( ! $order_id ) wp_send_json_error( 'no_order' );
    $order = wc_get_order( $order_id );
    if ( ! $order ) wp_send_json_error( 'invalid_order' );

    // permission: vendor only sees their orders (unless admin)
    $current_user = wp_get_current_user();
    $vendor_id = $current_user->ID;
    $vendor_ok = false;
    foreach ( $order->get_items() as $item ) {
        $item_id = is_object( $item ) && method_exists( $item, 'get_id' ) ? $item->get_id() : ( isset( $item['id'] ) ? $item['id'] : 0 );
        $item_vendor = '';
        if ( $item_id ) {
            $item_vendor = wc_get_order_item_meta( $item_id, '_vendor_id', true );
            if ( empty( $item_vendor ) ) $item_vendor = wc_get_order_item_meta( $item_id, 'vendor_id', true );
        }
        if ( $item_vendor && intval( $item_vendor ) === intval( $vendor_id ) ) { $vendor_ok = true; break; }
    }
    if ( ! $vendor_ok && ! in_array( 'administrator', (array) $current_user->roles ) ) wp_send_json_error( 'no_permission' );

    // Items
    $items = array();
    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        $sku = $product ? $product->get_sku() : '';
        $qty = (int) $item->get_quantity();
        $line_total_raw = (float) $item->get_total();
        $unit_price_raw = $qty ? ( $line_total_raw / $qty ) : 0.0;
        $price_display = cisai_wcfm_oqa_clean_price_string( $unit_price_raw );
        $total_display = cisai_wcfm_oqa_clean_price_string( $line_total_raw );
        $items[] = array(
            'name' => $item->get_name(),
            'sku' => $sku,
            'qty' => $qty,
            'price' => $unit_price_raw,
            'price_display' => $price_display,
            'total' => $line_total_raw,
            'total_display' => $total_display,
        );
    }

    // Fees (order item fees)
    $fees = array();
    foreach ( $order->get_items( 'fee' ) as $fee_item ) {
        $fname = $fee_item->get_name();
        $famount_raw = (float) $fee_item->get_total();
        $fees[] = array(
            'name' => $fname,
            'amount' => $famount_raw,
            'amount_display' => cisai_wcfm_oqa_clean_price_string( $famount_raw ),
        );
    }

    // Taxes breakdown
    $taxes = array();
    $tax_totals = $order->get_tax_totals();
    if ( is_array( $tax_totals ) ) {
        foreach ( $tax_totals as $code => $tax ) {
            // $tax is WC_Order_Item_Tax or array-like
            $label = is_object( $tax ) && isset( $tax->label ) ? $tax->label : ( is_array($tax) && isset($tax['label']) ? $tax['label'] : (string)$code );
            $amount_raw = is_object( $tax ) && isset( $tax->amount ) ? (float)$tax->amount : ( isset($tax['amount']) ? (float)$tax['amount'] : 0.0 );
            $taxes[] = array(
                'code' => $code,
                'label' => $label,
                'amount_raw' => $amount_raw,
                'amount_display' => cisai_wcfm_oqa_clean_price_string( $amount_raw ),
            );
        }
    }

    // Discounts (coupons)
    $discount_raw = (float) $order->get_discount_total();
    $discount_display = $discount_raw ? cisai_wcfm_oqa_clean_price_string( $discount_raw ) : '';

    $billing = array(
        'name'    => $order->get_formatted_billing_full_name(),
        'address' => trim( $order->get_billing_address_1() . "\n" . $order->get_billing_city() . "\n" . $order->get_billing_country() ),
        'email'   => $order->get_billing_email(),
        'phone'   => $order->get_billing_phone(),
    );
    $shipping = array(
        'name'    => $order->get_formatted_shipping_full_name(),
        'address' => trim( $order->get_shipping_address_1() . "\n" . $order->get_shipping_city() . "\n" . $order->get_shipping_country() ),
    );

    $subtotal_raw = (float) $order->get_subtotal();
    $shipping_total_raw = (float) $order->get_shipping_total();
    $tax_total_raw = (float) $order->get_total_tax();
    $total_raw = (float) $order->get_total();

    $subtotal_display = cisai_wcfm_oqa_clean_price_string( $subtotal_raw );
    $shipping_total_display = $shipping_total_raw ? cisai_wcfm_oqa_clean_price_string( $shipping_total_raw ) : '';
    $tax_total_display = $tax_total_raw ? cisai_wcfm_oqa_clean_price_string( $tax_total_raw ) : '';
    $total_display = cisai_wcfm_oqa_clean_price_string( $total_raw );

    $store_name = get_bloginfo( 'name' );
    $store_addr = get_option( 'woocommerce_store_address' ) . "\n" . get_option( 'woocommerce_store_city' );

    $data = array(
        'id' => $order_id,
        'date' => $order->get_date_created() ? $order->get_date_created()->date_i18n( 'Y-m-d H:i' ) : '',
        'items' => $items,
        'fees' => $fees,
        'taxes' => $taxes,
        'billing' => $billing,
        'shipping' => $shipping,
        'subtotal' => $subtotal_raw,
        'subtotal_display' => $subtotal_display,
        'shipping_total' => $shipping_total_raw,
        'shipping_total_display' => $shipping_total_display,
        'tax_total' => $tax_total_raw,
        'tax_total_display' => $tax_total_display,
        'discount_raw' => $discount_raw,
        'discount_display' => $discount_display,
        'total' => $total_raw,
        'total_display' => $total_display,
        'tracking' => get_post_meta( $order_id, '_tracking_number', true ) ?: get_post_meta( $order_id, 'tracking_number', true ) ?: '',
        'store_name' => $store_name,
        'store_addr' => $store_addr,
    );

    wp_send_json_success( $data );
}

/* --------------------------
 * End plugin
 * -------------------------*/
