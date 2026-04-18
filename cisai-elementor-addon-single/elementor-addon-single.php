<?php
/**
 * Plugin Name: CISAI Elementor Categories Book - Single File (Adarsh)
 * Description: Elementor widget: Category grid that opens a modern glossy book. Left page = parent image/title. Right page = child categories as image cards. Close by button or clicking outside.
 * Version: 1.0
 * Author: Adarsh
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* --------------------------
 * Ensure Elementor loaded
 * -------------------------*/
add_action( 'plugins_loaded', function() {
    if ( ! did_action( 'elementor/loaded' ) ) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning"><p><strong>Elementor Categories Book</strong> requires Elementor to be installed & activated.</p></div>';
        });
        return;
    }
});

/* --------------------------
 * AJAX handler: return child categories (JSON html)
 * -------------------------*/
add_action( 'wp_ajax_ecb_get_children', 'ecb_get_children' );
add_action( 'wp_ajax_nopriv_ecb_get_children', 'ecb_get_children' );

function ecb_get_children() {
    // expect taxonomy and parent_id
    $taxonomy = isset($_REQUEST['taxonomy']) ? sanitize_text_field($_REQUEST['taxonomy']) : 'product_cat';
    $parent = isset($_REQUEST['parent']) ? intval($_REQUEST['parent']) : 0;
    $hide_empty = isset($_REQUEST['hide_empty']) && $_REQUEST['hide_empty'] == '1' ? true : false;

    if ( $parent <= 0 ) {
        wp_send_json_error('invalid parent');
    }

    $children = get_terms([
        'taxonomy' => $taxonomy,
        'parent' => $parent,
        'hide_empty' => $hide_empty,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);

    if ( is_wp_error($children) ) {
        wp_send_json_error('error');
    }

    // Build HTML for right page: grid cards with image + title
    ob_start();
    if ( empty($children) ) {
        echo '<div class="ecb-no-children">No sub-categories found.</div>';
    } else {
        echo '<div class="ecb-children-grid">';
        foreach ( $children as $c ) {
            $child_link = get_term_link( $c );
            $child_name = esc_html( $c->name );
            $child_count = intval( $c->count );

            // try to get thumbnail (product_cat)
            $child_img = '';
            $thumb_id = get_term_meta( $c->term_id, 'thumbnail_id', true );
            if ( $thumb_id ) {
                $img = wp_get_attachment_image_src( $thumb_id, 'medium' );
                if ( ! empty( $img[0] ) ) $child_img = esc_url( $img[0] );
            }

            if ( empty( $child_img ) ) {
                // Try other common meta
                $maybe_meta = get_term_meta( $c->term_id, 'image', true );
                if ( ! empty($maybe_meta) ) $child_img = esc_url($maybe_meta);
            }

            if ( empty( $child_img ) ) {
                // fallback to provided placeholder local path (tooling will convert to URL)
                $child_img = '/mnt/data/38ec9318-c929-4540-a767-fee8ef2c204a.png';
            }

            ?>
            <a class="ecb-child-card" href="<?php echo esc_url( $child_link ); ?>">
                <div class="ecb-child-thumb-wrap">
                    <img class="ecb-child-thumb" src="<?php echo $child_img; ?>" alt="<?php echo esc_attr($child_name); ?>" loading="lazy" />
                </div>
                <div class="ecb-child-title"><?php echo $child_name; ?> <?php if($child_count) echo '<span class="ecb-child-count">(' . $child_count . ')</span>'; ?></div>
            </a>
            <?php
        }
        echo '</div>';
    }
    $html = ob_get_clean();

    wp_send_json_success( [ 'html' => $html ] );
}

/* --------------------------
 * Register Elementor widget
 * -------------------------*/
add_action( 'elementor/widgets/register', function( $widgets_manager ) {

    class Widget_Categories_Book_Adarsh extends \Elementor\Widget_Base {

        public function get_name() { return 'categories_book_adarsh'; }
        public function get_title() { return 'Categories Book — Adarsh'; }
        public function get_icon() { return 'eicon-gallery-justified'; }
        public function get_categories() { return [ 'basic' ]; }

        protected function register_controls() {
            $this->start_controls_section( 'section_content', [
                'label' => __( 'Content', 'elementor-cats-book' ),
            ] );

            $this->add_control( 'taxonomy', [
                'label' => 'Taxonomy',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'product_cat' => 'WooCommerce Product Categories (recommended)',
                    'category' => 'Blog Categories',
                ],
                'default' => 'product_cat',
            ] );

            $this->add_control( 'hide_empty', [
                'label' => 'Hide empty',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'return_value' => '1',
                'default' => '',
            ] );

            $this->add_control( 'max', [
                'label' => 'Max parent categories (0 = all)',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 0,
                'step' => 1,
                'default' => 0,
            ] );

            $this->end_controls_section();
        }

        protected function render() {
            $s = $this->get_settings_for_display();
            $taxonomy = ! empty( $s['taxonomy'] ) ? $s['taxonomy'] : 'product_cat';
            $hide_empty = ! empty( $s['hide_empty'] );
            $max = intval( $s['max'] );

            // placeholder (local path will be converted by your tooling)
            $placeholder_local = '/mnt/data/38ec9318-c929-4540-a767-fee8ef2c204a.png';

            $args = [
                'taxonomy' => $taxonomy,
                'parent' => 0,
                'hide_empty' => $hide_empty,
                'orderby' => 'name',
                'order' => 'ASC',
            ];
            if ( $max > 0 ) $args['number'] = $max;

            $parents = get_terms( $args );

            if ( empty($parents) || is_wp_error($parents) ) {
                echo '<div class="ecb-no-cats">' . esc_html__( 'No categories found.', 'elementor-cats-book' ) . '</div>';
                return;
            }

            // Inline CSS & JS for single-file plugin
            ?>
            <style>
                .ecb-parent-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
                @media (min-width: 320px) and (max-width: 767px) {.ecb-parents-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 18px;
    margin: 0;
    padding: 0;
    list-style: none;
}
                }
                .ecb-overlay{
    display:flex;
    align-items:center;
    justify-content:center;
}
@media (max-width:640px){
   .ecb-book{width:95%;height:auto;max-height:92vh;overflow-y:auto;}
}
@media (max-width:640px){
   .ecb-children-grid{grid-template-columns:repeat(2,1fr)!important;}
}

            /* Grid for parent categories */
            .ecb-parents-grid{display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin:0; padding:0; list-style:none;}
            .ecb-parent-card{background:#fff;border-radius:10px;overflow:hidden;border:1px solid rgba(12,12,12,0.06);cursor:pointer;position:relative;transition:transform .18s ease, box-shadow .18s ease;}
            .ecb-parent-card:hover{transform:translateY(-6px);box-shadow:0 14px 30px rgba(20,24,40,0.06);}
            .ecb-parent-thumb{width:100%;height:150px;object-fit:cover;display:block;}
            .ecb-parent-body{padding:10px 12px;}
            .ecb-parent-title{font-weight:700;color:#111;margin:0 0 4px;display:block;text-decoration:none;}
            .ecb-parent-count{font-size:13px;color:#666;margin-left:6px;font-weight:600;}

            /* Book overlay: modern glossy magazine style (C) */
            .ecb-overlay{position:fixed;left:0;top:0;right:0;bottom:0;display:none;align-items:center;justify-content:center;z-index:99999;background:rgba(8,12,20,0.46);backdrop-filter: blur(3px);}
            .ecb-book{margin: auto !important;width:900px;max-width:calc(100% - 48px);height:520px;max-height:calc(100% - 80px);transform-style:preserve-3d;perspective:1200px;position:relative;}
            .ecb-pages{margin-top: 25%;width:100%;height:100%;position:relative;transform-origin:left center;transition:transform .7s cubic-bezier(.2,.9,.25,1);transform: rotateY(-90deg);} /* closed */
            .ecb-book.open .ecb-pages{transform: rotateY(0deg);} /* opened */

            .ecb-left,.ecb-right{width:50%;height:100%;position:absolute;top:0;overflow:hidden;box-shadow:0 6px 30px rgba(10,14,28,0.06);border-radius:8px;}
            .ecb-left{left:0;background:linear-gradient(180deg,#ffffff,#fbfbfd);border-right:1px solid rgba(0,0,0,0.04);display:flex;flex-direction:column;align-items:center;justify-content:flex-start;padding:24px;}
            .ecb-right{right:0;background:linear-gradient(180deg,#ffffff,#f7f8fb);display:flex;flex-direction:column;padding:18px 18px 24px;overflow:auto;}

            .ecb-left .ecb-parent-large-thumb{width:100%;height:280px;object-fit:cover;border-radius:6px;margin-bottom:12px;box-shadow:0 8px 30px rgba(15,20,40,0.06);}
            .ecb-left .ecb-large-title{font-size:20px;font-weight:800;margin:0;color:#111;}
            .ecb-left .ecb-large-count{color:#777;font-weight:600;margin-top:6px;}

            /* Right page children grid (3) */
            .ecb-children-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;}
            .ecb-child-card{ gap: 5px !important;display:flex;flex-direction:row;align-items:center;text-decoration:none;background:#fff;border-radius:8px;padding:8px;min-height:60px;border:1px solid rgba(12,12,12,0.04);transition:transform .12s ease;}
            .ecb-child-card:hover{transform:translateY(-6px);}
            .ecb-child-thumb-wrap{object-fit: contain;width:30%;height:50px;overflow:hidden;border-radius:6px;margin-bottom:8px;}
            .ecb-child-thumb{width:100%;height:100%;object-fit:cover;display:block;}
            .ecb-child-title{font-weight:700;font-size:14px;color:#111;text-align:center;}
            .ecb-child-count{color:#666;font-size:13px;margin-left:6px;font-weight:600;}

            /* controls */
            .ecb-close-btn{position:absolute;right:8px;top:8px;background:linear-gradient(180deg,#fff,#f3f6ff);border-radius:999px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:101;color:#222;border:1px solid rgba(0,0,0,0.06);box-shadow:0 6px 20px rgba(10,15,40,0.06);}
            .ecb-close-icon{font-weight:700;}

            /* Responsive */
            @media (max-width: 1024px){
                .ecb-book{height:84vh; max-height:840px;}
                .ecb-left .ecb-parent-large-thumb{height:40vh;}
            }
            @media (max-width: 640px){
                .ecb-book{width:100%;height:100%;max-width:100%;max-height:100%;border-radius:0;}
                .ecb-left,.ecb-right{width:100%;height:50%;position:relative;border-radius:0;}
                .ecb-pages{transform-origin:center center;}
                .ecb-child-card{min-height:84px;}
                .ecb-children-grid{grid-template-columns:repeat(2,1fr);}
            }

            /* small polish */
            .ecb-loading{display:flex;align-items:center;justify-content:center;padding:24px;font-weight:600;color:#666;}
            </style>

            <div class="ecb-wrapper">
                <ul class="ecb-parents-grid">
                <?php foreach ( $parents as $p ):
                    $term_link = get_term_link( $p );
                    $name = esc_html( $p->name );
                    $count = intval( $p->count );

                    // thumbnail for product_cat
                    $img_url = '';
                    if ( $taxonomy === 'product_cat' ) {
                        $thumb_id = get_term_meta( $p->term_id, 'thumbnail_id', true );
                        if ( $thumb_id ) {
                            $img = wp_get_attachment_image_src( $thumb_id, 'medium' );
                            if ( ! empty( $img[0] ) ) $img_url = $img[0];
                        }
                    }
                    if ( empty( $img_url ) ) {
                        $maybe_meta = get_term_meta( $p->term_id, 'image', true );
                        if ( ! empty( $maybe_meta ) ) $img_url = $maybe_meta;
                    }
                    if ( empty( $img_url ) ) {
                        $img_url = $placeholder_local;
                    }
                ?>
                    <li class="ecb-parent-card" data-term-id="<?php echo intval($p->term_id); ?>" data-term-name="<?php echo esc_attr($p->name); ?>" data-term-count="<?php echo intval($p->count); ?>" data-term-link="<?php echo esc_url($term_link); ?>" data-taxonomy="<?php echo esc_attr($taxonomy); ?>" data-hide-empty="<?php echo $hide_empty ? '1' : '0'; ?>" data-img="<?php echo esc_url($img_url); ?>">
                        <img class="ecb-parent-thumb" src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy" />
                        <div class="ecb-parent-body">
                            <span class="ecb-parent-title"><?php echo $name; ?></span>
                            <?php if ( $count ) : ?><span class="ecb-parent-count">(<?php echo $count; ?>)</span><?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
                </ul>
            </div>

            <!-- Book overlay -->
            <div class="ecb-overlay" id="ecb-overlay" aria-hidden="true">
                <div class="ecb-book" role="dialog" aria-modal="true" aria-labelledby="ecb-book-title">
                    <div class="ecb-close-btn" id="ecb-close" title="Close">
                        <span class="ecb-close-icon">&times;</span>
                    </div>

                    <div class="ecb-pages" id="ecb-pages">
                        <div class="ecb-left" id="ecb-left-page">
                            <!-- left content (parent image + title) inserted via JS -->
                        </div>
                        <div class="ecb-right" id="ecb-right-page">
                            <!-- right content (children grid) inserted via AJAX -->
                            <div class="ecb-loading">Loading...</div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            (function($){
                $(function(){
                    var $overlay = $('#ecb-overlay'),
                        $book = $('.ecb-book'),
                        $pages = $('#ecb-pages'),
                        $left = $('#ecb-left-page'),
                        $right = $('#ecb-right-page'),
                        openClass = 'open';

                    function openBook(meta){
                        // populate left
                        var parentImg = meta.img;
                        var title = meta.name;
                        var count = meta.count || '';
                        var link = meta.link || '#';
                        $left.html(
                            '<img class="ecb-parent-large-thumb" src="'+parentImg+'" alt="'+title+'" />' +
                            '<h2 class="ecb-large-title" id="ecb-book-title"><a href="'+link+'" style="color:inherit;text-decoration:none;">'+title+'</a></h2>' +
                            (count ? '<div class="ecb-large-count">('+count+')</div>' : '')
                        );

                        // show overlay and animate open
                        $overlay.fadeIn(180, function(){
                            $book.addClass(openClass);
                        });

                        // fetch children via AJAX
                        $right.html('<div class="ecb-loading">Loading...</div>');
                        $.ajax({
                            url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                            method: 'POST',
                            data: {
                                action: 'ecb_get_children',
                                taxonomy: meta.taxonomy,
                                parent: meta.term_id,
                                hide_empty: meta.hide_empty
                            },
                            success: function(res){
                                if ( res.success ) {
                                    $right.html(res.data.html);
                                } else {
                                    $right.html('<div class="ecb-loading">No sub-categories found.</div>');
                                }
                            },
                            error: function(){
                                $right.html('<div class="ecb-loading">Error loading sub-categories.</div>');
                            }
                        });
                    }

                    function closeBook(){
                        $book.removeClass(openClass);
                        // small timeout to allow close animation
                        setTimeout(function(){
                            $overlay.fadeOut(160);
                            $left.empty();
                            $right.empty();
                        }, 320);
                    }

                    // Open on parent card click
                    $('.ecb-parent-card').on('click', function(e){
                        e.preventDefault();
                        var $t = $(this);
                        var meta = {
                            term_id: $t.data('term-id'),
                            name: $t.data('term-name'),
                            count: $t.data('term-count'),
                            link: $t.data('term-link'),
                            taxonomy: $t.data('taxonomy'),
                            hide_empty: $t.data('hide-empty'),
                            img: $t.data('img')
                        };
                        openBook(meta);
                    });

                    // Close by button
                    $('#ecb-close').on('click', function(e){
                        e.preventDefault();
                        closeBook();
                    });

                    // Close by clicking outside the book
                    $overlay.on('click', function(e){
                        if ( $(e.target).is('#ecb-overlay') ) {
                            closeBook();
                        }
                    });

                    // Esc key closes
                    $(document).on('keyup', function(e){
                        if ( e.key === 'Escape' ) closeBook();
                    });

                    // prevent clicks inside book from closing
                    $book.on('click', function(e){ e.stopPropagation(); });

                    // Touch improvements: swipe down to close on small screens
                    var startY = null;
                    $book.on('touchstart', function(e){ startY = e.originalEvent.touches[0].clientY; });
                    $book.on('touchend', function(e){
                        if (!startY) return;
                        var endY = (e.originalEvent.changedTouches && e.originalEvent.changedTouches[0]) ? e.originalEvent.changedTouches[0].clientY : null;
                        if (endY && (endY - startY) > 120) closeBook();
                        startY = null;
                    });

                });
            })(jQuery);
            </script>
            <?php
        }
    }

    $widgets_manager->register( new Widget_Categories_Book_Adarsh() );
});
