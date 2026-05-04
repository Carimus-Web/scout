<?php

require_once SPUTNIK_PATH . 'includes/ai/client.php';
require_once SPUTNIK_PATH . 'includes/blocks/allowed.php';
require_once SPUTNIK_PATH . 'includes/blocks/validator.php';
require_once SPUTNIK_PATH . 'includes/content/post-creator.php';

function sputnik_chat_handler($request) {

    $messages = $request->get_param('messages');
    $postType = $request->get_param('postType');

    if (empty($messages) || empty($postType)) {
        return [
            'error' => 'Missing required parameters: messages and postType'
        ];
    }

    $allowed_blocks = sputnik_get_allowed_blocks($postType);

    if (empty($allowed_blocks)) {
        return [
            'error' => "No allowed blocks found for post type '{$postType}'. Check your theme configuration."
        ];
    }

    $ai = sputnik_ai_chat($messages, $allowed_blocks);

    // Check for errors from AI provider
    if (!empty($ai['error'])) {
        return [
            'error' => $ai['error']
        ];
    }

    // If layout exists → create post
    if (!empty($ai['layout'])) {

        if (!sputnik_validate_layout($ai['layout'], $allowed_blocks)) {
            return [
                'reply' => [
                    'role' => 'assistant',
                    'content' => 'Block validation failed. The AI generated invalid blocks or fields. Try refining your request.'
                ],
                'complete' => false
            ];
        }

        $post_id = sputnik_create_post($postType, $ai['layout']);

        if (is_wp_error($post_id)) {
            return [
                'error' => 'Failed to create post: ' . $post_id->get_error_message()
            ];
        }

        return [
            'reply' => [
                'role' => 'assistant',
                'content' => 'Page draft created successfully! Redirecting to editor...'
            ],
            'complete' => true,
            'edit_url' => get_edit_post_link($post_id, 'raw')
        ];
    }

    // Return AI message (clarifying question or other response)
    return [
        'reply' => [
            'role' => 'assistant',
            'content' => $ai['message'] ?? 'Unable to process your request. Please try again.'
        ],
        'complete' => false
    ];
}