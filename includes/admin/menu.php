<?php

add_action('admin_menu', function () {
    // Base64-encoded SVG icon for proper WordPress menu alignment
    // Icon renders at standard 16x16 and 24x24 sizes with other WordPress admin icons
    $icon_url = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMThweCIgdmlld0JveD0iMCAtOTYwIDk2MCA5NjAiIHdpZHRoPSIyNHB4IiBmaWxsPSIjZTNlM2UzIj48cGF0aCBkPSJNNTYwLTMydi04MHExMTcgMCAxOTguNS04MS41VDg0MC0zOTJoODBxMCA3NS0yOC41IDE0MC41dC03NyAxMTRxLTQ4LjUgNDguNS0xMTQgNzdUNTYwLTMyWm0wLTE2MHYtODBxNTAgMCA4NS0zNXQzNS04NWg4MHEwIDgzLTU4LjUgMTQxLjVUNTYwLTE5MlpNMjIyLTU3cS0xNSAwLTMwLTZ0LTI3LTE3TDIzLTIyMnEtMTEtMTItMTctMjd0LTYtMzBxMC0xNiA2LTMwLjVUMjMtMzM1bDEyNy0xMjdxMjMtMjMgNTctMjMuNXQ1NyAyMi41bDUwIDUwIDI4LTI4LTUwLTUwcS0yMy0yMy0yMy01NnQyMy01Nmw1Ny01N3EyMy0yMyA1Ni41LTIzdDU2LjUgMjNsNTAgNTAgMjgtMjgtNTAtNTBxLTIzLTIzLTIzLTU2LjV0MjMtNTYuNWwxMjctMTI3cTEyLTEyIDI3LTE4dDMwLTZxMTUgMCAyOS41IDZ0MjYuNSAxOGwxNDIgMTQycTEyIDExIDE3LjUgMjUuNVQ4OTUtNzMwcTAgMTUtNS41IDMwVDg3Mi02NzNMNzQ1LTU0NnEtMjMgMjMtNTYuNSAyM1Q2MzItNTQ2bC01MC01MC0yOCAyOCA1MCA1MHEyMyAyMyAyMi41IDU2LjVUNjAzLTQwNWwtNTYgNTZxLTIzIDIzLTU2LjUgMjNUNDM0LTM0OWwtNTAtNTAtMjggMjggNTAgNTBxMjMgMjMgMjIuNSA1N1Q0MDUtMjA3TDI3OC04MHEtMTEgMTEtMjUuNSAxN1QyMjItNTdabTAtNzkgNDItNDItMTQyLTE0Mi00MiA0MiAxNDIgMTQyWm04NS04NSA0Mi00Mi0xNDItMTQyLTQyIDQyIDE0MiAxNDJabTE4NC0xODQgNTYtNTYtMTQyLTE0Mi01NiA1NiAxNDIgMTQyWm0xOTgtMTk4IDQyLTQyLTE0Mi0xNDItNDIgNDIgMTQyIDE0MlptODUtODUgNDItNDItMTQyLTE0Mi00MiA0MiAxNDIgMTQyWk00NDgtNTA0WiIvPjwvc3ZnPg==';
    
    add_menu_page(
        'Scout',
        'Scout',
        'edit_posts',
        'scout',
        'scout_render_page',
        $icon_url,
        3
    );

    add_submenu_page(
        'scout',
        'Scout Settings',
        'Settings',
        'manage_options',
        'scout-settings',
        'scout_render_settings_page'
    );
});