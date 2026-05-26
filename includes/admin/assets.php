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
    $selected_types = get_option('scout_post_types', []);
    $types = get_post_types(['public' => true], 'objects');

    // If no types are selected, use all public types
    if (empty($selected_types)) {
        $types_to_use = $types;
    } else {
        // Filter to only selected types
        $types_to_use = [];
        foreach ($selected_types as $type_name) {
            if (isset($types[$type_name])) {
                $types_to_use[$type_name] = $types[$type_name];
            }
        }
    }

    $output = [];

    foreach ($types_to_use as $type) {
        $output[] = [
            'label' => $type->label,
            'value' => $type->name
        ];
    }

    return $output;
}