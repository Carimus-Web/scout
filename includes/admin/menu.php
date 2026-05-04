<?php

add_action('admin_menu', function () {
    $icon_url = SPUTNIK_URL . 'admin-icon.svg';
    
    add_menu_page(
        'Sputnik',
        'Sputnik',
        'edit_posts',
        'sputnik',
        'sputnik_render_page',
        $icon_url,
        3
    );

    add_submenu_page(
        'sputnik',
        'Sputnik Settings',
        'Settings',
        'manage_options',
        'sputnik-settings',
        'sputnik_render_settings_page'
    );
});