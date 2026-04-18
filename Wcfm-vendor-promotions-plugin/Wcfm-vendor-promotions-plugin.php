<?php
/**
 * Plugin Name: CISAI WCFM Vendor Promotions Elementor (Enhanced)
 * Description: Elementor widget + shortcode to display WCFM vendor coupons with slider, vendor filter, styling controls, glass/gradient, GSAP animation and Copy (C) button.
 * Version: 2.1
 * Author: Adarsh Singh
 */

if (!defined('ABSPATH')) exit;

/* -------------------------
 * Enqueue required scripts/styles (Swiper + GSAP optionally)
 * ------------------------*/
add_action('wp_enqueue_scripts', function() {
    // Swiper (slider)
    wp_enqueue_style('wvp-swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], null);
    wp_enqueue_script('wvp-swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], null, true);

    // GSAP (we'll only use it if enabled; safe to include)
    wp_enqueue_script('wvp-gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', [], null, true);
});

/* -------------------------
 * Helper: fetch coupons authored by vendor IDs
 * ------------------------*/
function wvp_get_vendor_coupons_raw($args = []) {
    $defaults = ['vendor_ids' => [], 'max' => -1, 'sort' => 'latest'];
    $opts = wp_parse_args($args, $defaults);

    $query_args = [
        'post_type' => 'shop_coupon',
        'post_status' => 'publish',
        'posts_per_page' => $opts['max'],
    ];
    if (!empty($opts['vendor_ids'])) $query_args['author__in'] = $opts['vendor_ids'];

    $posts = get_posts($query_args);
    if (!$posts) return [];

    $coupons = [];
    foreach ($posts as $p) {
        $id = $p->ID;
        $couponObj = null;
        if (class_exists('WC_Coupon')) {
            try { $couponObj = new WC_Coupon($id); } catch (Exception $e) { $couponObj = null; }
        }

        $code = $p->post_title;
        $desc = $p->post_excerpt ?: ($couponObj ? $couponObj->get_description() : '');
        $vendor_id = $p->post_author;

        // vendor name detection (WCFM common meta keys)
        $vendor_name = get_user_meta($vendor_id, 'store_name', true);
        if (!$vendor_name) $vendor_name = get_user_meta($vendor_id, 'wcfmmp_store_name', true);
        if (!$vendor_name) $vendor_name = get_userdata($vendor_id) ? get_userdata($vendor_id)->display_name : 'Vendor';

        $banner_id = get_user_meta($vendor_id, '_wcfm_store_banner', true);
        $banner = $banner_id ? wp_get_attachment_image_url($banner_id, 'large') : get_avatar_url($vendor_id);

        $discount_type_raw = $couponObj ? $couponObj->get_discount_type() : get_post_meta($id, 'discount_type', true);
        $discount_type = 'N/A';
        if ($discount_type_raw) {
            if (stripos($discount_type_raw, 'percent') !== false) $discount_type = 'percentage';
            elseif (in_array($discount_type_raw, ['fixed_cart','fixed_product'], true)) $discount_type = 'fixed';
            else $discount_type = $discount_type_raw;
        }

        $amount_num = $couponObj ? (float)$couponObj->get_amount() : (float)get_post_meta($id, 'coupon_amount', true);
        $amount_formatted = '';
        if (function_exists('wc_price') && $amount_num !== '') {
            $amount_formatted = wp_strip_all_tags(wc_price($amount_num));
        } else {
            $amount_formatted = $amount_num !== '' ? (string)$amount_num : '';
        }

        $expiry_raw = '';
        if ($couponObj) {
            $date_expires = $couponObj->get_date_expires();
            if ($date_expires && is_a($date_expires, 'DateTime')) $expiry_raw = $date_expires->format('Y-m-d');
        }
        if (!$expiry_raw) {
            $meta_e = get_post_meta($id, 'date_expires', true);
            if ($meta_e && is_numeric($meta_e)) $expiry_raw = date('Y-m-d', intval($meta_e));
            elseif ($meta_e) $expiry_raw = $meta_e;
        }

        $free_shipping = $couponObj ? (bool)$couponObj->get_free_shipping() : (bool)get_post_meta($id, 'free_shipping', true);

        $coupons[] = [
            'id' => $id,
            'code' => $code,
            'desc' => $desc,
            'vendor_id' => $vendor_id,
            'vendor_name' => $vendor_name,
            'banner' => $banner,
            'discount_type' => $discount_type,
            'amount_num' => $amount_num,
            'amount_formatted' => $amount_formatted,
            'expiry' => $expiry_raw,
            'free_shipping' => $free_shipping,
            'post_date' => $p->post_date,
        ];
    }

    // Sorting
    if ($opts['sort'] === 'highest') {
        usort($coupons, function($a,$b){ return $b['amount_num'] <=> $a['amount_num']; });
    } elseif ($opts['sort'] === 'expiry') {
        usort($coupons, function($a,$b){
            $ea = $a['expiry'] ? strtotime($a['expiry']) : PHP_INT_MAX;
            $eb = $b['expiry'] ? strtotime($b['expiry']) : PHP_INT_MAX;
            return $ea <=> $eb;
        });
    } else { // latest
        usort($coupons, function($a,$b){ return strtotime($b['post_date']) <=> strtotime($a['post_date']); });
    }

    return $coupons;
}

/* -------------------------
 * Shortcode fallback
 * ------------------------*/
add_shortcode('wcfm_vendor_promotions', function($atts){
    // simple shortcode wrapper that outputs the widget HTML (server defaults)
    $atts = shortcode_atts(['sort'=>'latest','vendor'=>'all','per_page'=>12,'title'=>'Promotions & Discounts'], $atts, 'wcfm_vendor_promotions');

    $vendor_ids = [];
    if ($atts['vendor'] !== 'all' && !empty($atts['vendor'])) {
        if (strpos($atts['vendor'], ',') !== false) {
            $parts = array_map('trim', explode(',', $atts['vendor']));
            foreach ($parts as $p) if (is_numeric($p)) $vendor_ids[] = intval($p);
        } else {
            if (is_numeric($atts['vendor'])) $vendor_ids[] = intval($atts['vendor']);
            else {
                $u = get_user_by('login', $atts['vendor']);
                if ($u) $vendor_ids[] = $u->ID;
            }
        }
    } else {
        $v = get_users(['role__in'=>['wcfm_vendor','vendor'],'fields'=>'ID']);
        $vendor_ids = $v ? wp_list_pluck($v,'ID') : [];
    }

    $coupons = wvp_get_vendor_coupons_raw(['vendor_ids'=>$vendor_ids,'max'=>-1,'sort'=>$atts['sort']]);
    if (empty($coupons)) return '<p>No promotions found.</p>';

    // Simple HTML output: we'll reuse widget renderer to avoid duplication — but for shortcode keep simple responsive grid
    ob_start();
    echo '<div class="wcfmvp-shortcode-wrapper">';
    foreach ($coupons as $c) {
        echo '<div class="wcfmvp-shortcard" style="border:1px double #eaeaea;padding:12px;margin-bottom:12px">';
        echo '<div style="font-weight:700">'.esc_html($c['code']).'</div>';
        echo '<div>'.esc_html($c['desc']).'</div>';
        echo '<div><strong>Discount Type:</strong> '.esc_html(ucfirst($c['discount_type'])).'</div>';
        echo '<div><strong>Expiry:</strong> '.esc_html($c['expiry']).'</div>';
        echo '<div><strong>Free Shipping:</strong> '.($c['free_shipping']? 'Yes' : 'No').'</div>';
        echo '<div><strong>Coupon Amount:</strong> '.esc_html($c['amount_formatted']).'</div>';
        echo '<div><strong>Vendor:</strong> '.esc_html($c['vendor_name']).'</div>';
        echo '<div style="margin-top:8px"><button class="wcfmvp-short-copy" data-code="'.esc_attr($c['code']).'">Copy (C)</button></div>';
        echo '</div>';
    }
    echo '</div>';
    ?>
    <script>
    (function(){
        // delegated handler for shortcode copy buttons (covers dynamic/cloned UI too)
        document.addEventListener('click', function(e){
            var btn = e.target.closest && e.target.closest('.wcfmvp-short-copy');
            if (!btn) return;
            var code = btn.getAttribute('data-code') || '';
            if (!code) return;
            // attempt navigator.clipboard then fallback
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(code).then(function(){
                    var prev = btn.innerText;
                    btn.innerText = 'Copied!';
                    setTimeout(function(){ btn.innerText = prev; }, 1200);
                }).catch(function(){
                    // fallback
                    fallbackCopy(code, btn);
                });
            } else {
                fallbackCopy(code, btn);
            }
        });

        function fallbackCopy(text, btn) {
            var ta = document.createElement('textarea');
            ta.value = text;
            // avoid scrolling to bottom
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try {
                var ok = document.execCommand('copy');
                if (ok) {
                    var prev = btn.innerText;
                    btn.innerText = 'Copied!';
                    setTimeout(function(){ btn.innerText = prev; }, 1200);
                } else {
                    alert('Unable to copy to clipboard.');
                }
            } catch (ex) {
                alert('Copy failed: ' + ex);
            }
            document.body.removeChild(ta);
        }
    })();
    </script>
    <?php
    return ob_get_clean();
});

/* -------------------------
 * Elementor widget registration
 * ------------------------*/
add_action('elementor/elements/categories_registered', function($elements_manager){
    if (method_exists($elements_manager, 'add_category')) {
        $elements_manager->add_category('wcfm-addons', ['title'=>'WCFM Addons','icon'=>'fa fa-store']);
    }
});

// Use widgets_registered to maximize compatibility
add_action('elementor/widgets/widgets_registered', function($widgets_manager){
    if (!class_exists('\Elementor\Widget_Base')) return;

    if (!class_exists('WCFMVP_Elementor_Widget')) {
        class WCFMVP_Elementor_Widget extends \Elementor\Widget_Base {

            public function get_name(){ return 'wcfmvp_promos'; }
            public function get_title(){ return 'WCFM Vendor Promotions'; }
            public function get_icon(){ return 'eicon-carousel'; }
            public function get_categories(){ return ['wcfm-addons']; }

            protected function register_controls() {
                // Content Section
                $this->start_controls_section('content_section', ['label' => 'Content']);

                $this->add_control('title', [
                    'label' => 'Title',
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'default' => 'Promotions & Discounts',
                ]);

                $this->add_control('sort', [
                    'label' => 'Sort By',
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'latest' => 'Latest',
                        'highest' => 'Highest discount',
                        'expiry' => 'Expiry soon'
                    ],
                    'default' => 'latest'
                ]);

                $this->add_control('vendor_filter', [
                    'label' => 'Show vendor filter',
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'label_on' => 'Yes',
                    'label_off' => 'No',
                    'return_value' => 'yes',
                    'default' => 'yes'
                ]);

                $this->add_control('slider_enable', [
                    'label' => 'Enable slider',
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'return_value' => 'yes',
                    'default' => 'yes'
                ]);

                $this->add_control('per_slide', [
                    'label' => 'Slides per view (desktop)',
                    'type' => \Elementor\Controls_Manager::NUMBER,
                    'default' => 4,
                    'min' => 1,
                    'max' => 8
                ]);

                $this->add_control('max_items', [
                    'label' => 'Max items (0 = all)',
                    'type' => \Elementor\Controls_Manager::NUMBER,
                    'default' => 0
                ]);

                $this->add_control('show_image', [
                    'label' => 'Show vendor image/banner',
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'return_value' => 'yes',
                    'default' => ''
                ]);

                $this->end_controls_section();

                // Style Section
                $this->start_controls_section('style_section', ['label' => 'Style', 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);

                $this->add_control('card_bg_color', [
                    'label' => 'Card background color',
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#ffffff'
                ]);

                $this->add_control('use_gradient', [
                    'label' => 'Use gradient background for cards',
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'return_value' => 'yes',
                    'default' => ''
                ]);

                $this->add_control('gradient_from', [
                    'label' => 'Gradient color from',
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#1e293b'
                ]);

                $this->add_control('gradient_to', [
                    'label' => 'Gradient color to',
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#05b895'
                ]);

                $this->add_control('glass_effect', [
                    'label' => 'Glass effect (backdrop blur)',
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'return_value' => 'yes',
                    'default' => ''
                ]);

                $this->add_control('border_color', [
                    'label' => 'Card border color',
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#ffffff'
                ]);

                $this->add_control('border_width', [
                    'label' => 'Card border width (px)',
                    'type' => \Elementor\Controls_Manager::NUMBER,
                    'default' => 1,
                    'min' => 0,
                    'max' => 10
                ]);

                $this->add_control('card_padding', [
                    'label' => 'Card padding (px)',
                    'type' => \Elementor\Controls_Manager::NUMBER,
                    'default' => 12,
                    'min' => 0,
                    'max' => 60
                ]);

                $this->add_control('gap', [
                    'label' => 'Gap between cards (px)',
                    'type' => \Elementor\Controls_Manager::NUMBER,
                    'default' => 18,
                    'min' => 0,
                    'max' => 60
                ]);

                $this->add_control('font_color', [
                    'label' => 'Font color',
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#111111'
                ]);

                $this->add_control('button_bg', [
                    'label' => 'Button background',
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#0071e3'
                ]);

                $this->add_control('button_hover', [
                    'label' => 'Button hover background',
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#005bb5'
                ]);

                $this->add_control('gsap_anim', [
                    'label' => 'Enable GSAP hover animation',
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'return_value' => 'yes',
                    'default' => ''
                ]);

                $this->end_controls_section();
            }

            protected function render() {
                $s = $this->get_settings_for_display();

                // vendors
                $vendors = get_users(['role__in' => ['wcfm_vendor','vendor'], 'fields' => ['ID','display_name']]);
                $vendor_ids = $vendors ? wp_list_pluck($vendors, 'ID') : [];

                $max = intval($s['max_items']) === 0 ? -1 : intval($s['max_items']);
                $coupons = wvp_get_vendor_coupons_raw(['vendor_ids' => $vendor_ids, 'max' => $max, 'sort' => $s['sort']]);

                // vendor list for filter
                $vendor_list = [];
                foreach ($coupons as $c) if (!isset($vendor_list[$c['vendor_id']])) $vendor_list[$c['vendor_id']] = $c['vendor_name'];

                // unique id for scoped CSS/JS
                $uniq = 'wvp_' . substr(md5(uniqid('', true)), 0, 6);
                $card_bg = esc_attr($s['card_bg_color']);
                $use_grad = ($s['use_gradient'] === 'yes');
                $grad_from = esc_attr($s['gradient_from']);
                $grad_to = esc_attr($s['gradient_to']);
                $glass = ($s['glass_effect'] === 'yes');
                $border_color = esc_attr($s['border_color']);
                $border_width = intval($s['border_width']);
                $card_padding = intval($s['card_padding']);
                $gap = intval($s['gap']);
                $font_color = esc_attr($s['font_color']);
                $btn_bg = esc_attr($s['button_bg']);
                $btn_hover = esc_attr($s['button_hover']);
                $perSlide = max(1, intval($s['per_slide'] ?: 4));
                $slider_on = ($s['slider_enable'] === 'yes');
                $show_image = ($s['show_image'] === 'yes');
                $use_gsap = ($s['gsap_anim'] === 'yes');

                // print Swiper/GSAP CSS only once is handled by enqueueing earlier
                // Output widget HTML
                echo "<div class='wcfmvp-widget {$uniq}' data-per-slide='{$perSlide}' data-slider='".($slider_on?'1':'0')."' style='--wvp-gap:{$gap}px'>";

                // header controls (filter/search/sort)
                echo "<div class='wcfmvp-controls' style='margin-bottom:12px'>";
                echo "<div style='display:flex;gap:8px;align-items:center'>";
                echo "<select class='wcfmvp-sort' aria-label='Sort promotions'><option value='latest'".($s['sort']=='latest'?' selected':'').">Latest</option><option value='highest'".($s['sort']=='highest'?' selected':'').">Highest discount</option><option value='expiry'".($s['sort']=='expiry'?' selected':'').">Expiry soon</option></select>";

                if ($s['vendor_filter'] === 'yes') {
                    echo "<select class='wcfmvp-vendor-select' aria-label='Filter by vendor' style='margin-left:8px'><option value='all'>All vendors</option>";
                    foreach ($vendor_list as $vid => $vname) echo "<option value='".esc_attr($vid)."'>".esc_html($vname)."</option>";
                    echo "</select>";
                    echo "<input class='wcfmvp-vendor-search' placeholder='Search vendor...' style='margin-left:8px;padding:6px' />";
                }
                echo "</div>";
                echo "</div>"; // controls

                // grid / swiper container
                // We'll output slides as swiper slides if slider_on else plain grid
                echo "<div class='wcfmvp-container {$uniq}-container' style='--wvp-card-padding:{$card_padding}px'>";
                if ($slider_on) {
                    echo "<div class='swiper wcfmvp-swiper-{$uniq}'><div class='swiper-wrapper' style='gap:{$gap}px'>";
                    foreach ($coupons as $c) {
                        $banner = esc_url($c['banner']);
                        $code = esc_html($c['code']);
                        $desc = esc_html($c['desc']);
                        $discount_type = esc_html(ucfirst($c['discount_type']));
                        $expiry = esc_html($c['expiry'] ?: 'No expiry');
                        $free_ship = $c['free_shipping'] ? 'Yes' : 'No';
                        $amount = esc_html($c['amount_formatted']);
                        $vendor_name = esc_html($c['vendor_name']);
                        $vendor_id = intval($c['vendor_id']);

                        // card style: use CSS variables
                        $card_style = "padding:{$card_padding}px;border:{$border_width}px solid {$border_color};border-radius:12px;";

                        if ($use_grad) {
                            $card_bg_css = "background:linear-gradient(135deg, {$grad_from}, {$grad_to});";
                        } else {
                            $card_bg_css = "background:{$card_bg};";
                        }
                        if ($glass) {
                            $card_bg_css .= "backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);background-color:rgba(255,255,255,0.06);";
                        }

                        echo "<div class='swiper-slide wcfmvp-slide-item' data-vendor-id='".esc_attr($vendor_id)."'>";
                        echo "<div class='wcfmvp-card' style='{$card_style} {$card_bg_css} color:{$font_color}'>";
                        if ($show_image) {
                            echo "<div class='wcfmvp-media' style='width:100%;height:140px;overflow:hidden;border-radius:8px;margin-bottom:10px'><img src='{$banner}' style='width:100%;height:100%;object-fit:cover' loading='lazy' alt=''></div>";
                        }
                        echo "<div class='wcfmvp-code' style='font-weight:700;color:{$btn_bg};margin-bottom:8px'>".esc_html($code)."</div>";
                        echo "<div class='wcfmvp-desc' style='margin-bottom:6px'><strong>Description:</strong> {$desc}</div>";
                        echo "<div class='wcfmvp-item' style='margin-bottom:6px'><strong>Discount Type:</strong> {$discount_type}</div>";
                        echo "<div class='wcfmvp-item' style='margin-bottom:6px'><strong>Coupon expiry date:</strong> {$expiry}</div>";
                        echo "<div class='wcfmvp-item' style='margin-bottom:6px'><strong>Allow free shipping:</strong> {$free_ship}</div>";
                        echo "<div class='wcfmvp-item' style='margin-bottom:8px'><strong>Coupon Amount:</strong> {$amount}</div>";
                        echo "<div class='wcfmvp-item' style='margin-bottom:10px'><strong>Vendor:</strong> {$vendor_name}</div>";
                        // Buttons: Visit Store + Copy (C)
                        echo "<div style='display:flex;gap:8px'>";
                        echo "<a class='wcfmvp-visit' href='".esc_url(get_author_posts_url($vendor_id))."' style='flex:1;text-align:center;padding:8px;border-radius:8px;background:{$btn_bg};color:#fff;text-decoration:none'>Visit Store</a>";
                        echo "<button class='wcfmvp-copy-btn' data-code='".esc_attr($c['code'])."' style='flex:1;padding:8px;border-radius:8px;background:#fff;border:1px solid {$btn_bg};color:{$btn_bg};font-weight:700;cursor:pointer'>Copy (C)</button>";
                        echo "</div>";

                        echo "</div>"; // card
                        echo "</div>"; // slide
                    }
                    echo "</div>"; // swiper-wrapper
                    echo "<div class='wcfmvp-pagination-{$uniq}'></div>";
                    echo "<div class='wcfmvp-button-prev-{$uniq}'></div>";
                    echo "<div class='wcfmvp-button-next-{$uniq}'></div>";
                    echo "</div>"; // swiper
                } else {
                    // grid mode
                    echo "<div class='wcfmvp-grid' style='display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:{$gap}px'>";
                    foreach ($coupons as $c) {
                        $banner = esc_url($c['banner']);
                        $code = esc_html($c['code']);
                        $desc = esc_html($c['desc']);
                        $discount_type = esc_html(ucfirst($c['discount_type']));
                        $expiry = esc_html($c['expiry'] ?: 'No expiry');
                        $free_ship = $c['free_shipping'] ? 'Yes' : 'No';
                        $amount = esc_html($c['amount_formatted']);
                        $vendor_name = esc_html($c['vendor_name']);
                        $vendor_id = intval($c['vendor_id']);

                        $card_style = "padding:{$card_padding}px;border:{$border_width}px solid {$border_color};border-radius:12px;";

                        if ($use_grad) $card_bg_css = "background:linear-gradient(135deg, {$grad_from}, {$grad_to});";
                        else $card_bg_css = "background:{$card_bg};";
                        if ($glass) $card_bg_css .= "backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);background-color:rgba(255,255,255,0.06);";

                        echo "<div class='wcfmvp-card' style='{$card_style} {$card_bg_css};color:{$font_color}'>";
                        if ($show_image) echo "<div class='wcfmvp-media' style='width:100%;height:140px;overflow:hidden;border-radius:8px;margin-bottom:10px'><img src='{$banner}' style='width:100%;height:100%;object-fit:cover' loading='lazy' alt=''></div>";
                        echo "<div class='wcfmvp-code' style='font-weight:700;color:{$btn_bg};margin-bottom:8px'>".esc_html($code)."</div>";
                        echo "<div class='wcfmvp-desc' style='margin-bottom:6px'><strong>Description:</strong> {$desc}</div>";
                        echo "<div class='wcfmvp-item' style='margin-bottom:6px'><strong>Coupon expiry date:</strong> {$expiry}</div>";
                        echo "<div class='wcfmvp-item' style='margin-bottom:6px'><strong>Coupon Amount:</strong> {$amount}</div>";
                        echo "<div style='display:flex;gap:8px;margin-top:10px'><a class='wcfmvp-visit' href='".esc_url(get_author_posts_url($vendor_id))."' style='flex:1;text-align:center;padding:8px;border-radius:8px;background:{$btn_bg};color:#fff;text-decoration:none'>Visit Store</a><button class='wcfmvp-copy-btn' data-code='".esc_attr($c['code'])."' style='flex:1;padding:8px;border-radius:8px;background:#fff;border:1px double {$btn_bg};color:{$btn_bg};font-weight:700;cursor:pointer'>Copy (C)</button></div>";
                        echo "</div>";
                    }
                    echo "</div>"; // grid
                }

                echo "</div>"; // container

                // Scoped CSS for this widget instance
                ?>
                <style>
                .<?php echo $uniq; ?> .wcfmvp-card { transition: transform .25s ease, box-shadow .25s ease; }
                .<?php echo $uniq; ?> .wcfmvp-copy-btn { transition: background .15s ease, color .15s ease; }
                .<?php echo $uniq; ?> .wcfmvp-copy-btn.copied { background: <?php echo $btn_bg; ?>; color: #fff !important; }
                </style>
                <script>
                (function(){
                    // Wait for DOM
                    document.addEventListener('DOMContentLoaded', function(){
                        var sliderOn = <?php echo ($slider_on ? 'true' : 'false'); ?>;
                        var perSlide = <?php echo $perSlide; ?>;
                        var uniq = '<?php echo $uniq; ?>';
                        var swiperInstance = null;

                        function initSwiper() {
                            if (!sliderOn) return;
                            // initialize with container class specific to uniq
                            var selector = '.wcfmvp-swiper-<?php echo $uniq; ?>';
                            if (typeof Swiper !== 'undefined') {
                                swiperInstance = new Swiper(selector, {
                                    slidesPerView: perSlide,
                                    spaceBetween: <?php echo $gap; ?>,
                                    pagination: { el: '.wcfmvp-pagination-<?php echo $uniq; ?>', clickable: true },
                                    navigation: { nextEl: '.wcfmvp-button-next-<?php echo $uniq; ?>', prevEl: '.wcfmvp-button-prev-<?php echo $uniq; ?>' },
                                    breakpoints: {
                                        0: { slidesPerView: 1 },
                                        600: { slidesPerView: Math.max(1, Math.min(2, perSlide)) },
                                        900: { slidesPerView: Math.max(1, Math.min(3, perSlide)) },
                                        1200: { slidesPerView: perSlide }
                                    }
                                });
                            } else {
                                // if Swiper not loaded yet, try load by polling (rare)
                                var t = setInterval(function(){
                                    if (typeof Swiper !== 'undefined') {
                                        clearInterval(t);
                                        initSwiper();
                                    }
                                }, 200);
                            }
                        }

                        initSwiper();

                        // Delegated copy behavior (works with clones & dynamically added slides)
                        var widgetRoot = document.querySelector('.' + uniq);
                        if (widgetRoot) {
                            widgetRoot.addEventListener('click', function(e){
                                var btn = e.target.closest('.wcfmvp-copy-btn');
                                if (!btn) return;
                                var code = btn.getAttribute('data-code') || '';
                                if (!code) return;

                                // try navigator.clipboard first
                                if (navigator.clipboard && navigator.clipboard.writeText) {
                                    navigator.clipboard.writeText(code).then(function(){
                                        showCopiedState(btn);
                                    }).catch(function(){
                                        fallbackCopy(code, btn);
                                    });
                                } else {
                                    fallbackCopy(code, btn);
                                }
                            });
                        }

                        function showCopiedState(btn) {
                            var prev = btn.innerText;
                            btn.classList.add('copied');
                            btn.innerText = 'Copied!';
                            setTimeout(function(){ btn.classList.remove('copied'); btn.innerText = prev; }, 1200);
                        }

                        function fallbackCopy(text, btn) {
                            var ta = document.createElement('textarea');
                            ta.value = text;
                            ta.style.position = 'fixed';
                            ta.style.left = '-9999px';
                            document.body.appendChild(ta);
                            ta.focus();
                            ta.select();
                            try {
                                var ok = document.execCommand('copy');
                                if (ok) {
                                    showCopiedState(btn);
                                } else {
                                    alert('Unable to copy to clipboard.');
                                }
                            } catch (ex) {
                                alert('Copy failed: ' + ex);
                            }
                            document.body.removeChild(ta);
                        }

                        // Vendor filter + search
                        var vendorSelect = document.querySelector('.<?php echo $uniq; ?> .wcfmvp-vendor-select');
                        var vendorSearch = document.querySelector('.<?php echo $uniq; ?> .wcfmvp-vendor-search');
                        function applyFilter() {
                            var sel = vendorSelect ? vendorSelect.value : 'all';
                            var term = vendorSearch ? vendorSearch.value.toLowerCase() : '';
                            document.querySelectorAll('.<?php echo $uniq; ?> .wcfmvp-slide-item').forEach(function(sl){
                                var vid = sl.getAttribute('data-vendor-id');
                                var text = sl.textContent.toLowerCase();
                                var okVendor = (sel === 'all') || (vid === sel);
                                var okSearch = !term || (text.indexOf(term) !== -1);
                                sl.style.display = (okVendor && okSearch) ? '' : 'none';
                            });
                            if (swiperInstance) swiperInstance.update();
                        }
                        if (vendorSelect) vendorSelect.addEventListener('change', applyFilter);
                        if (vendorSearch) vendorSearch.addEventListener('input', applyFilter);

                        // Sort control (server reload approach — keeps server-side sorting correct)
                        var sortSelect = document.querySelector('.<?php echo $uniq; ?> .wcfmvp-sort');
                        if (sortSelect) {
                            sortSelect.addEventListener('change', function(){
                                var val = this.value;
                                // reload page with param wvp_sort
                                var url = new URL(window.location.href);
                                url.searchParams.set('wvp_sort', val);
                                window.location.href = url.toString();
                            });
                        }

                        <?php if ($use_gsap) : ?>
                        // GSAP hover animation
                        if (typeof gsap !== 'undefined') {
                            document.querySelectorAll('.<?php echo $uniq; ?> .wcfmvp-card').forEach(function(card){
                                card.addEventListener('mouseenter', function(){
                                    gsap.to(card, { scale: 1.03, boxShadow: '0 10px 30px rgba(0,0,0,0.15)', duration: 0.35 });
                                });
                                card.addEventListener('mouseleave', function(){
                                    gsap.to(card, { scale: 1, boxShadow: '0 4px 15px rgba(0,0,0,0.08)', duration: 0.35 });
                                });
                            });
                        }
                        <?php endif; ?>
                    }); // DOMContentLoaded
                })();
                </script>
                <?php
            } // end render
        } // end widget class
    } // end if class exists

    // register widget
    $widgets_manager->register( new WCFMVP_Elementor_Widget() );
});

/* -------------------------
 * Server-side handling for ?wvp_sort param (simple)
 * If wvp_sort is present we'll allow our coupon fetch to read it via $_GET in future enhancements.
 * ------------------------*/
add_action('init', function(){
    if (!empty($_GET['wvp_sort'])) {
        // currently we reload page on sort change; widgets/shortcode re-render when page loads so no persistent storage needed
        // noop placeholder
    }
});

/* -------------------------
 * End plugin
 * ------------------------*/
