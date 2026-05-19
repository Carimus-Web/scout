<?php

require_once SCOUT_PATH . 'includes/ai/client.php';
require_once SCOUT_PATH . 'includes/admin/settings-config.php';
require_once SCOUT_PATH . 'includes/blocks/allowed.php';
require_once SCOUT_PATH . 'includes/blocks/validator.php';
require_once SCOUT_PATH . 'includes/content/post-creator.php';

function scout_chat_handler($request) {
    try {
        // Check API configuration first
        $provider = scout_get_ai_provider();

        if (empty($api_key)) {
            return [
                'error' => 'No AI provider configured. Go to Scout → Settings to add your API key.'
            ];
        }

        // Get parameters from JSON body
        $params = $request->get_json_params();
        
        $messages = isset($params['messages']) ? $params['messages'] : null;
        $postType = isset($params['postType']) ? $params['postType'] : null;

        if (empty($messages) || empty($postType)) {
            return [
                'error' => 'Missing required parameters: messages and postType'
            ];
        }

        $allowed_blocks = scout_get_allowed_blocks($postType);

        if (empty($allowed_blocks)) {
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

        $ai = scout_ai_chat($messages, $allowed_blocks);

        // Check for errors from AI provider
        if (!empty($ai['error'])) {
            return [
                'error' => $ai['error']
            ];
        }

        // If layout exists → create post
        if (!empty($ai['layout'])) {

            if (!scout_validate_layout($ai['layout'], $allowed_blocks)) {
                return [
                    'reply' => [
                        'role' => 'assistant',
                        'content' => 'Block validation failed. The AI generated invalid blocks or fields. Try refining your request.'
                    ],
                    'complete' => false
                ];
            }

            $post_id = scout_create_post($postType, $ai['layout']);

            if (is_wp_error($post_id)) {
                return [
                    'error' => 'Failed to create post: ' . $post_id->get_error_message()
                ];
            }
            
            $edit_url = get_edit_post_link($post_id, 'raw');
            $post_url = get_permalink($post_id);
            
            // Fallback: construct edit URL manually if get_edit_post_link returns null
            if (!$edit_url) {
                $edit_url = admin_url('post.php?post=' . $post_id . '&action=edit');
            }
            
            // If post_url is null, construct it as preview
            if (!$post_url) {
                $post_url = add_query_arg('preview', 'true', get_home_url() . '?p=' . $post_id);
            }

            return [
                'reply' => [
                    'role' => 'assistant',
                    'content' => 'Page draft created successfully! Redirecting to editor...'
                ],
                'complete' => true,
                'edit_url' => $edit_url,
                'post_url' => $post_url
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

/**
 * Handler for creating a page with blocks
 * Called when frontend needs to create a draft page from layout JSON
 */
function scout_create_page_handler($request) {
    try {
        $params = $request->get_json_params();
        $layout = isset($params['layout']) ? $params['layout'] : null;
        $postType = isset($params['postType']) ? $params['postType'] : null;
        $post_id = isset($params['post_id']) ? intval($params['post_id']) : null;

        if (empty($layout) || empty($postType)) {
            return [
                'error' => 'Missing required parameters: layout and postType'
            ];
        }

        if ($post_id) {
            // Update existing post
            $result = scout_update_post($post_id, $layout);
        } else {
            // Create new post
            $result = scout_create_post($postType, $layout);
        }

        if (is_wp_error($result)) {
            return [
                'error' => 'Failed to create/update post: ' . $result->get_error_message()
            ];
        }

        $post_id = $result;
        
        // Get the URLs - these can be null if post doesn't exist
        $edit_url = get_edit_post_link($post_id, 'raw');
        $post_url = get_permalink($post_id);
        
        // Fallback: construct edit URL manually if get_edit_post_link returns null
        if (!$edit_url) {
            $edit_url = admin_url('post.php?post=' . $post_id . '&action=edit');
        }
        
        // If post_url is null, construct it as preview
        if (!$post_url) {
            $post_url = add_query_arg('preview', 'true', get_home_url() . '?p=' . $post_id);
        }
        
        return [
            'success' => true,
            'post_id' => $post_id,
            'edit_url' => $edit_url,
            'post_url' => $post_url
        ];
    } catch (Exception $e) {
        error_log('Create Page Error: ' . $e->getMessage());
        return [
            'error' => 'Error creating page: ' . $e->getMessage()
        ];
    }
}