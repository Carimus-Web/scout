<?php

require_once SPUTNIK_PATH . 'includes/ai/client.php';
require_once SPUTNIK_PATH . 'includes/admin/settings-config.php';
require_once SPUTNIK_PATH . 'includes/blocks/allowed.php';
require_once SPUTNIK_PATH . 'includes/blocks/validator.php';
require_once SPUTNIK_PATH . 'includes/content/post-creator.php';

function sputnik_chat_handler($request) {
    try {
        // Log the request for debugging
        error_log('Sputnik Chat Handler Called');
        
        // Check API configuration first
        error_log('Getting AI provider...');
        $provider = sputnik_get_ai_provider();
        error_log('Provider: ' . $provider);
        
        $api_key = sputnik_get_api_key($provider);
        error_log('API Key: ' . (empty($api_key) ? 'empty' : 'set'));

        if (empty($api_key)) {
            return [
                'error' => 'No AI provider configured. Go to Sputnik → Settings to add your API key.'
            ];
        }

        // Get parameters from JSON body
        error_log('Getting JSON params...');
        $params = $request->get_json_params();
        error_log('Params received: ' . json_encode($params));
        
        $messages = isset($params['messages']) ? $params['messages'] : null;
        $postType = isset($params['postType']) ? $params['postType'] : null;

        if (empty($messages) || empty($postType)) {
            error_log('Missing parameters - Messages: ' . (empty($messages) ? 'empty' : 'set') . ', PostType: ' . (empty($postType) ? 'empty' : $postType));
            return [
                'error' => 'Missing required parameters: messages and postType'
            ];
        }

        $allowed_blocks = sputnik_get_allowed_blocks($postType);

        if (empty($allowed_blocks)) {
            error_log('No blocks found. get_block_types exists: ' . (function_exists('get_block_types') ? 'yes' : 'no'));
            
            // If blocks function doesn't exist, blocks haven't loaded yet
            if (!function_exists('get_block_types')) {
                return [
                    'error' => 'WordPress blocks API not available. Make sure you have a compatible WordPress version and the Carimus Backbone theme is active.'
                ];
            }
            
            return [
                'error' => "No allowed blocks found for post type '{$postType}'. Make sure the Carimus Backbone theme is active and has registered blocks."
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
    } catch (Exception $e) {
        return [
            'error' => 'Error processing request: ' . $e->getMessage()
        ];
    }
}