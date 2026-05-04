<?php

function sputnik_enqueue_admin_styles() {
    wp_enqueue_style(
        'sputnik-admin',
        SPUTNIK_URL . 'assets/css/admin.css',
        [],
        SPUTNIK_VERSION
    );
}

add_action('admin_enqueue_scripts', function() {
    if (isset($_GET['page']) && strpos($_GET['page'], 'sputnik') === 0) {
        sputnik_enqueue_admin_styles();
    }
});
