<?php

function sputnik_enqueue_admin_styles() {
    // Enqueue Tailwind CSS via CDN
    wp_enqueue_style(
        'tailwindcss',
        'https://cdn.tailwindcss.com',
        [],
        null
    );

    // Enqueue Google Fonts (Poppins)
    wp_enqueue_style(
        'poppins-font',
        'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap',
        [],
        null
    );

    // Enqueue custom Sputnik styles
    wp_enqueue_style(
        'sputnik-admin',
        SPUTNIK_URL . 'assets/css/admin.css',
        ['tailwindcss', 'poppins-font'],
        SPUTNIK_VERSION
    );
}

add_action('admin_enqueue_scripts', function() {
    if (isset($_GET['page']) && strpos($_GET['page'], 'sputnik') === 0) {
        sputnik_enqueue_admin_styles();
    }
});
