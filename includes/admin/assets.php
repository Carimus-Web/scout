<?php

add_action('admin_enqueue_scripts', function ($hook) {

    if ($hook !== 'toplevel_page_scout') return;

    // Pre-load template HTML files
    $templates = [];
    $template_names = [
        'main-layout',
        'preview-idle',
        'preview-loading',
        'preview-error',
        'preview-iframe'
    ];

    $template_dir = SCOUT_PATH . 'assets/html';
    foreach ($template_names as $name) {
        $file = $template_dir . '/' . $name . '.html';
        if (file_exists($file)) {
            $templates[$name] = file_get_contents($file);
        }
    }

    // Inject config and templates into window
    echo '<script>';
    echo 'window.SCOUT = ' . json_encode([
        'api' => '/wp-json/scout/v1/chat',
        'postTypes' => scout_get_post_types(),
        'settingsUrl' => admin_url('admin.php?page=scout-settings')
    ]) . ';';
    echo 'window.SCOUT_TEMPLATE_PATH = ' . json_encode(SCOUT_URL . 'assets/html') . ';';
    echo 'window.SCOUT_TEMPLATES = ' . json_encode($templates) . ';';
    echo '</script>';

    wp_enqueue_script(
        'scout-app',
        SCOUT_URL . 'assets/js/app.js',
        [],
        '1.0',
        true
    );

    // Add module type attribute to the script tag
    add_filter('script_loader_tag', function ($tag, $handle) {
        if ($handle === 'scout-app') {
            return str_replace(' src=', ' type="module" src=', $tag);
        }
        return $tag;
    }, 10, 2);
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