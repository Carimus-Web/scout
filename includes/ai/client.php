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
 * 1. WordPress Settings (Sputnik > Settings)
 * 2. Environment variables (SPUTNIK_AI_PROVIDER, ANTHROPIC_API_KEY, etc)
 */

function sputnik_ai_chat($messages, $allowed_blocks) {
    
    $provider = sputnik_get_ai_provider();
    
    switch ($provider) {
        case 'anthropic':
            return sputnik_ai_anthropic($messages, $allowed_blocks);
        case 'openai':
            return sputnik_ai_openai($messages, $allowed_blocks);
        case 'google':
            return sputnik_ai_google($messages, $allowed_blocks);
        default:
            return [
                'error' => "Unknown AI provider: {$provider}. Configure in Sputnik Settings."
            ];
    }
}

/**
 * Anthropic Claude API
 * RECOMMENDED - Best for structured JSON generation with constraints
 */
function sputnik_ai_anthropic($messages, $allowed_blocks) {
    
    require_once SPUTNIK_PATH . 'includes/ai/prompts.php';
    
    $prompt = sputnik_build_prompt($messages, $allowed_blocks);
    
    $api_key = sputnik_get_api_key('anthropic');
    if (!$api_key) {
        return ['error' => 'Anthropic API key not configured. Set it in Sputnik Settings.'];
    }
    
    $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
        'headers' => [
            'x-api-key' => $api_key,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json'
        ],
        'body' => json_encode([
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 2048,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt[count($prompt) - 1]['content']
                ]
            ],
            'system' => $prompt[0]['content']
        ])
    ]);
    
    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message()];
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $content = $body['content'][0]['text'] ?? '';
    
    $decoded = json_decode($content, true);
    if ($decoded) return $decoded;
    
    return ['message' => $content];
}

/**
 * OpenAI GPT API
 */
function sputnik_ai_openai($messages, $allowed_blocks) {
    
    require_once SPUTNIK_PATH . 'includes/ai/prompts.php';
    
    $prompt = sputnik_build_prompt($messages, $allowed_blocks);
    
    $api_key = sputnik_get_api_key('openai');
    if (!$api_key) {
        return ['error' => 'OpenAI API key not configured. Set it in Sputnik Settings.'];
    }
    
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode([
            'model' => 'gpt-4-turbo',
            'messages' => $prompt,
            'temperature' => 0.7
        ])
    ]);
    
    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message()];
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $content = $body['choices'][0]['message']['content'] ?? '';
    
    $decoded = json_decode($content, true);
    if ($decoded) return $decoded;
    
    return ['message' => $content];
}

/**
 * Google Gemini API
 */
function sputnik_ai_google($messages, $allowed_blocks) {
    
    require_once SPUTNIK_PATH . 'includes/ai/prompts.php';
    
    $prompt = sputnik_build_prompt($messages, $allowed_blocks);
    
    $api_key = sputnik_get_api_key('google');
    if (!$api_key) {
        return ['error' => 'Google API key not configured. Set it in Sputnik Settings.'];
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
            ]
        ])
    ]);
    
    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message()];
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $content = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
    
    $decoded = json_decode($content, true);
    if ($decoded) return $decoded;
    
    return ['message' => $content];
}