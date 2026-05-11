<?php

add_action('admin_enqueue_scripts', function ($hook) {

    if ($hook !== 'toplevel_page_scout') return;

    wp_enqueue_script(
        'scout-app',
        SCOUT_URL . 'assets/js/app.js',
        [],
        '1.0',
        true
    );

    wp_localize_script('scout-app', 'SCOUT', [
        'api' => '/wp-json/scout/v1/chat',
        'postTypes' => scout_get_post_types(),
        'settingsUrl' => admin_url('admin.php?page=scout-settings')
    ]);
});

function scout_get_post_types() {
    $types = get_post_types(['public' => true], 'objects');

    $output = [];

    foreach ($types as $type) {
        $output[] = [
            'label' => $type->label,
            'value' => $type->name
        ];
    }

    return $output;
}