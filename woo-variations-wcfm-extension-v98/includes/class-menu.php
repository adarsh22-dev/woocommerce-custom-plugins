
<?php
add_filter('wcfm_menus', function ($menus) {
    if (!wcfm_is_vendor()) return $menus;
    $menus['woo_variations'] = [
        'label' => 'My Variations',
        'url' => wcfm_get_endpoint_url('woo-variations'),
        'icon' => 'cube',
        'priority' => 45
    ];
    return $menus;
});
add_filter('wcfm_query_vars', function ($vars) {
    $vars['woo-variations'] = 'woo-variations';
    return $vars;
});
