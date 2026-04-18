<?php
/*
Plugin Name: CISAI WCFM Manufacturer Videos 2025
Description: Adds a responsive product video gallery (main video with side thumbnails) for WCFM Free. Supports YouTube, Vimeo, and MP4 uploads with shortcode [manufacturer_videos].
Version: 5.1
Author: Adarsh Singh
*/

if (!defined('ABSPATH')) exit;

/* ============================================================
 * 1. Add Fields in WCFM Product Edit
 * ============================================================ */
add_action('after_wcfm_products_manage_general', function($product_id) {
    $videos = get_post_meta($product_id, 'from_the_manufacturer_videos', true);
    if (!is_array($videos)) $videos = [];
    ?>
    <div class="page_collapsible products_manage_pods_video simple variable external">
        <label class="wcfmfa fa-video"></label> From the Manufacturer Videos
    </div>

    <div class="wcfm-container simple variable external">
        <div class="wcfm-content">
            <p class="wcfm_title"><strong>Upload or paste video URLs (YouTube, Vimeo, or MP4)</strong></p>

            <div id="cisai-videos-wrapper">
                <?php foreach ($videos as $url): ?>
                    <div class="cisai-video-row" style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                        <input type="url" name="from_the_manufacturer_videos[]" value="<?php echo esc_attr($url); ?>" placeholder="Paste Video URL or upload" style="flex:1;padding:6px;">
                        <button type="button" class="button cisai-upload-video">Upload</button>
                        <button type="button" class="button cisai-remove-video" style="background:#dc3545;color:#fff;">Remove</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" id="cisai-add-video" class="button" style="margin-top:10px;">+ Add Another Video</button>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($){
        let frame;

        // Add new video row
        $('#cisai-add-video').on('click', function(e){
            e.preventDefault();
            let row = `
            <div class="cisai-video-row" style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                <input type="url" name="from_the_manufacturer_videos[]" placeholder="Paste Video URL or upload" style="flex:1;padding:6px;">
                <button type="button" class="button cisai-upload-video">Upload</button>
                <button type="button" class="button cisai-remove-video" style="background:#dc3545;color:#fff;">Remove</button>
            </div>`;
            $('#cisai-videos-wrapper').append(row);
        });

        // Remove row
        $(document).on('click', '.cisai-remove-video', function(){
            $(this).closest('.cisai-video-row').remove();
        });

        // Upload video
        $(document).on('click', '.cisai-upload-video', function(e){
            e.preventDefault();
            let input = $(this).closest('.cisai-video-row').find('input');
            if (frame) frame.close();

            frame = wp.media({
                title: 'Select or Upload Video',
                button: { text: 'Use this video' },
                library: { type: ['video'] },
                multiple: false
            });

            frame.on('select', function(){
                let attachment = frame.state().get('selection').first().toJSON();
                input.val(attachment.url);
            });

            frame.open();
        });
    });
    </script>
    <?php
});

/* ============================================================
 * 2. Save Meta
 * ============================================================ */
add_action('after_wcfm_products_manage_meta_save', function($product_id, $data){
    if (isset($data['from_the_manufacturer_videos'])) {
        $urls = array_filter(array_map('esc_url_raw', (array)$data['from_the_manufacturer_videos']));
        update_post_meta($product_id, 'from_the_manufacturer_videos', $urls);
    } else {
        delete_post_meta($product_id, 'from_the_manufacturer_videos');
    }
}, 10, 2);

/* ============================================================
 * 3. Generate Embed/Thumbnail
 * ============================================================ */
function cisai_generate_video_embed($url, $main = false) {
    if (empty($url)) return '';

    // YouTube
    if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([A-Za-z0-9_-]{11})/', $url, $m)) {
        $id = $m[1];
        return $main
            ? '<iframe src="https://www.youtube.com/embed/'.$id.'?modestbranding=1&rel=0&controls=1" frameborder="0" allowfullscreen style="width:100%;aspect-ratio:16/9;border-radius:10px;"></iframe>'
            : '<img src="https://img.youtube.com/vi/'.$id.'/mqdefault.jpg" style="width:100%;border-radius:6px;cursor:pointer;">';
    }

    // Vimeo
    elseif (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
        $id = $m[1];
        return $main
            ? '<iframe src="https://player.vimeo.com/video/'.$id.'" frameborder="0" allowfullscreen style="width:100%;aspect-ratio:16/9;border-radius:10px;"></iframe>'
            : '<img src="https://vumbnail.com/'.$id.'.jpg" style="width:100%;border-radius:6px;cursor:pointer;">';
    }

    // MP4
    elseif (preg_match('/\.(mp4|webm|ogg)$/i', $url)) {
        return $main
            ? '<video src="'.esc_url($url).'" playsinline controls controlslist="nodownload noplaybackrate nofullscreen" disablePictureInPicture style="width:100%;aspect-ratio:16/9;border-radius:10px;"></video>'
            : '<video src="'.esc_url($url).'" muted playsinline style="width:100%;border-radius:6px;cursor:pointer;"></video>';
    }

    return '';
}

/* ============================================================
 * 4. Frontend Display (Main Player + Thumbnails)
 * ============================================================ */
add_shortcode('manufacturer_videos', function($atts){
    global $post;
    if (empty($post->ID)) return '';
    $videos = get_post_meta($post->ID, 'from_the_manufacturer_videos', true);
    if (empty($videos)) return '';

    $first = $videos[0];
    ob_start();
    ?>
    <div class="cisai-video-gallery">
        <div class="main-video" id="cisai-main-video">
            <?php echo cisai_generate_video_embed($first, true); ?>
        </div>
        <div class="video-thumbnails">
            <?php foreach ($videos as $url): ?>
                <div class="thumb-item" data-video="<?php echo esc_url($url); ?>">
                    <?php echo cisai_generate_video_embed($url, false); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <style>
    .cisai-video-gallery {
        display: flex;
        gap: 20px;
        align-items: flex-start;
        flex-wrap: wrap;
    }
    .cisai-video-gallery .main-video {
        flex: 1;
        min-width: 65%;
    }
    .video-thumbnails {
        width: 30%;
        max-height: 420px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .video-thumbnails::-webkit-scrollbar { width: 6px; }
    .video-thumbnails::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
    .thumb-item:hover { opacity: 0.8; }
    @media (max-width: 768px) {
        .cisai-video-gallery { flex-direction: column; }
        .main-video, .video-thumbnails { width: 100%; }
        .video-thumbnails { flex-direction: row; overflow-x: auto; max-height: unset; }
        .video-thumbnails .thumb-item { flex: 0 0 40%; }
    }
    </style>

    <script>
    jQuery(document).ready(function($){
        $(".thumb-item").on("click", function(){
            var videoUrl = $(this).data("video");
            var embed = "";

            if (videoUrl.includes("youtube.com") || videoUrl.includes("youtu.be")) {
                var match = videoUrl.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([A-Za-z0-9_-]{11})/);
                if (match) embed = `<iframe src="https://www.youtube.com/embed/${match[1]}?autoplay=1&modestbranding=1&rel=0&controls=1" frameborder="0" allow="autoplay; fullscreen" allowfullscreen style="width:100%;aspect-ratio:16/9;border-radius:10px;"></iframe>`;
            } 
            else if (videoUrl.includes("vimeo.com")) {
                var match = videoUrl.match(/vimeo\.com\/(\d+)/);
                if (match) embed = `<iframe src="https://player.vimeo.com/video/${match[1]}?autoplay=1" frameborder="0" allow="autoplay; fullscreen" allowfullscreen style="width:100%;aspect-ratio:16/9;border-radius:10px;"></iframe>`;
            } 
            else if (videoUrl.match(/\.(mp4|webm|ogg)$/i)) {
                embed = `<video src="${videoUrl}" playsinline autoplay controls controlslist="nodownload noplaybackrate nofullscreen" disablePictureInPicture style="width:100%;aspect-ratio:16/9;border-radius:10px;"></video>`;
            }

            $("#cisai-main-video").html(embed);
        });
    });
    </script>
    <?php
    return ob_get_clean();
});
