
<?php
add_action('wcfm_load_views', function ($endpoint) {
    if ($endpoint !== 'woo-variations') return;
    include __DIR__ . '/views/dashboard.php';
});
