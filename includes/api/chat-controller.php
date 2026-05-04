<?php

require_once SPUTNIK_PATH . 'includes/ai/client.php';
require_once SPUTNIK_PATH . 'includes/blocks/allowed.php';
require_once SPUTNIK_PATH . 'includes/blocks/validator.php';
require_once SPUTNIK_PATH . 'includes/content/post-creator.php';

function sputnik_chat_handler($request) {

    $messages = $request->get_param('messages');
    $postType = $request->get_param('postType');

    $allowed_blocks = sputnik_get_allowed_blocks($postType);

    $ai = sputnik_ai_chat($messages, $allowed_blocks);

    // If layout exists → create post
    if (!empty($ai['layout'])) {

        if (!sputnik_validate_layout($ai['layout'], $allowed_blocks)) {
            return [
                'reply' => [
                    'role' => 'assistant',
                    'content' => 'Something went wrong with block validation. Try again.'
                ],
                'complete' => false
            ];
        }

        $post_id = sputnik_create_post($postType, $ai['layout']);

        return [
            'reply' => [
                'role' => 'assistant',
                'content' => 'Page created. Redirecting...'
            ],
            'complete' => true,
            'edit_url' => get_edit_post_link($post_id, 'raw')
        ];
    }

    return [
        'reply' => [
            'role' => 'assistant',
            'content' => $ai['message']
        ],
        'complete' => false
    ];
}