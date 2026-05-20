<?php

/**
 * AI Client - Abstracted interface for AI model providers
 * 
 * Supported providers:
 * - anthropic (Claude) - RECOMMENDED for block generation
 * - openai (GPT models)
 * - google (Gemini)
 * 
 * Configuration sources (checked in order):
 * 1. WordPress Settings (Scout > Settings)
 * 2. Environment variables (SCOUT_AI_PROVIDER, ANTHROPIC_API_KEY, etc)
 */

function scout_ai_chat($messages, $allowed_blocks) {
    
    $provider = scout_get_ai_provider();
    
    switch ($provider) {
        case 'anthropic':
            return scout_ai_anthropic($messages, $allowed_blocks);
        case 'openai':
            return scout_ai_openai($messages, $allowed_blocks);
        case 'google':
            return scout_ai_google($messages, $allowed_blocks);
        default:
            return [
                'error' => "Unknown AI provider: {$provider}. Configure in Scout Settings."
            ];
    }
}

/**
 * Anthropic Claude API
 * RECOMMENDED - Best for structured JSON generation with constraints
 */
function scout_ai_anthropic($messages, $allowed_blocks) {
    
    require_once SCOUT_PATH . 'includes/ai/prompts.php';
    
    $prompt = scout_build_prompt($messages, $allowed_blocks);
    
    $system_content = $prompt[0]['content'];
    $user_content = $prompt[count($prompt) - 1]['content'];
    
    $api_key = scout_get_api_key('anthropic');
    if (!$api_key) {
        return ['error' => 'Anthropic API key not configured. Set it in Scout Settings.'];
    }
    
    $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
        'headers' => [
            'x-api-key' => $api_key,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json'
        ],
        'body' => json_encode([
            'model' => 'claude-sonnet-4-6',
            'max_tokens' => 4096,  // Increased from 2048 to handle full page layouts
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $user_content
                ]
            ],
            'system' => $system_content
        ]),
        'timeout' => 60
    ]);
    
    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message()];
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $error_message = $body['error']['message'] ?? 'Unknown error from Anthropic API';
        return ['error' => "Anthropic API Error ({$status_code}): {$error_message}"];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!$body || !isset($body['content'][0]['text'])) {
        return ['error' => 'Invalid response format from Anthropic API'];
    }

    $content = $body['content'][0]['text'];

    $decoded = json_decode($content, true);
    if ($decoded) {
        // Decode WYSIWYG fields that may have encoded HTML entities
        if (!empty($decoded['layout'])) {
            $decoded['layout'] = scout_unescape_wysiwyg_fields($decoded['layout'], $allowed_blocks);
        }
        return $decoded;
    }

    return ['message' => $content];
}

/**
 * OpenAI GPT API
 */
function scout_ai_openai($messages, $allowed_blocks) {
    
    require_once SCOUT_PATH . 'includes/ai/prompts.php';
    
    $prompt = scout_build_prompt($messages, $allowed_blocks);
    
    $api_key = scout_get_api_key('openai');
    if (!$api_key) {
        return ['error' => 'OpenAI API key not configured. Set it in Scout Settings.'];
    }
    
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode([
            'model' => 'gpt-4-turbo',
            'messages' => $prompt,
            'temperature' => 0.7,
            'max_tokens' => 4096  // Allow full page layout responses
        ]),
        'timeout' => 60
    ]);
    
    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message()];
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $content = $body['choices'][0]['message']['content'] ?? '';
    
    $decoded = json_decode($content, true);
    if ($decoded) {
        // Unescape WYSIWYG fields that may have been escaped by REST API
        if (!empty($decoded['layout'])) {
            $decoded['layout'] = scout_unescape_wysiwyg_fields($decoded['layout'], $allowed_blocks);
        }
        return $decoded;
    }
    
    return ['message' => $content];
}

/**
 * Google Gemini API
 */
function scout_ai_google($messages, $allowed_blocks) {
    
    require_once SCOUT_PATH . 'includes/ai/prompts.php';
    
    $prompt = scout_build_prompt($messages, $allowed_blocks);
    
    $api_key = scout_get_api_key('google');
    if (!$api_key) {
        return ['error' => 'Google API key not configured. Set it in Scout Settings.'];
    }
    
    $response = wp_remote_post('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key=' . $api_key, [
        'headers' => [
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode([
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $prompt[count($prompt) - 1]['content']
                        ]
                    ]
                ]
            ],
            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => $prompt[0]['content']
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => 4096
            ]
        ]),
        'timeout' => 60
    ]);
    
    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message()];
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $content = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
    
    $decoded = json_decode($content, true);
    if ($decoded) {
        // Unescape WYSIWYG fields that may have been escaped by REST API
        if (!empty($decoded['layout'])) {
            $decoded['layout'] = scout_unescape_wysiwyg_fields($decoded['layout'], $allowed_blocks);
        }
        return $decoded;
    }
    
    return ['message' => $content];
}

/**
 * Decode HTML entities in WYSIWYG fields that may have been escaped or encoded improperly
 * Handles both unicode escape sequences (\u003c) and literal entity strings (u003c)
 * 
 * @param array $layout The layout array from AI response
 * @param array $allowed_blocks The allowed blocks configuration
 * @return array The layout with decoded WYSIWYG content
 */
function scout_unescape_wysiwyg_fields($layout, $allowed_blocks) {
    if (!is_array($layout)) {
        return $layout;
    }

    foreach ($layout as $block_index => $block) {
        if (!is_array($block) || empty($block['block'])) {
            continue;
        }

        $block_type = str_replace('carimus/', '', $block['block']);
        
        // Get field configuration for this block type
        if (!isset($allowed_blocks[$block_type])) {
            continue;
        }

        $block_config = $allowed_blocks[$block_type];
        
        // Check each field in the block
        foreach ($block as $field_name => $field_value) {
            if (!is_string($field_value)) {
                continue;
            }

            // Decode HTML entities in string fields
            $field_value = scout_decode_html_entities($field_value);
            $layout[$block_index][$field_name] = $field_value;
        }
    }

    return $layout;
}

/**
 * Decode improperly encoded HTML entities
 * Converts u003c/u003e to </> and handles other unicode escape sequences
 * 
 * @param string $content The content to decode
 * @return string The decoded content
 */
function scout_decode_html_entities($content) {
    if (!is_string($content)) {
        return $content;
    }

    // Replace literal u003c with <
    $content = str_replace('u003c', '<', $content);
    // Replace literal u003e with >
    $content = str_replace('u003e', '>', $content);
    // Replace literal u0026 with &
    $content = str_replace('u0026', '&', $content);
    // Replace literal u0022 with "
    $content = str_replace('u0022', '"', $content);
    // Replace literal u0027 with '
    $content = str_replace('u0027', "'", $content);
    // Replace literal \u00xx sequences with their unicode equivalents
    $content = preg_replace_callback('/\\\\u([0-9a-f]{4})/i', function($matches) {
        return chr(hexdec($matches[1]));
    }, $content);

    return $content;
}