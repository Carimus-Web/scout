<?php

add_action('admin_enqueue_scripts', function ($hook) {

    if ($hook !== 'toplevel_page_sputnik') return;

    wp_enqueue_script(
        'sputnik-app',
        SPUTNIK_URL . 'assets/js/app.js',
        [],
        '1.0',
        true
    );

    wp_localize_script('sputnik-app', 'SPUTNIK', [
        'api' => '/wp-json/sputnik/v1/chat',
        'postTypes' => sputnik_get_post_types(),
        'settingsUrl' => admin_url('admin.php?page=sputnik-settings')
    ]);
});

function sputnik_get_post_types() {
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