<?php

add_action('admin_menu', function () {
    add_menu_page(
        'Sputnik',
        'Sputnik',
        'edit_posts',
        'sputnik',
        'sputnik_render_page',
        'dashicons-admin-site-alt3',
        3
    );
});