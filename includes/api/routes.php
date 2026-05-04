<?php

require_once SPUTNIK_PATH . 'includes/api/chat-controller.php';

add_action('rest_api_init', function () {
    register_rest_route('sputnik/v1', '/chat', [
        'methods' => 'POST',
        'callback' => 'sputnik_chat_handler',
    ]);
});