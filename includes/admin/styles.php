<?php

function sputnik_enqueue_admin_styles() {
    // Enqueue Tailwind CSS via CDN (it's a JavaScript file that generates CSS)
    wp_enqueue_script(
        'tailwindcss',
        'https://cdn.tailwindcss.com',
        [],
        null,
        false  // Load in head, not footer
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
        ['poppins-font'],
        SPUTNIK_VERSION
    );
}

add_action('admin_enqueue_scripts', function() {
    if (isset($_GET['page']) && strpos($_GET['page'], 'sputnik') === 0) {
        sputnik_enqueue_admin_styles();
    }
});
