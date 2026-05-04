<?php

/**
 * AI Client - Abstracted interface for AI model providers
 * 
 * Supported providers:
 * - anthropic (Claude) - RECOMMENDED for block generation
 * - openai (GPT models)
 * - google (Gemini)
 * 
 * Set SPUTNIK_AI_PROVIDER environment variable to switch providers.
 * Defaults to 'anthropic'
 */

function sputnik_ai_chat($messages, $allowed_blocks) {
    
    $provider = getenv('SPUTNIK_AI_PROVIDER') ?: 'anthropic';
    
    switch ($provider) {
        case 'anthropic':
            return sputnik_ai_anthropic($messages, $allowed_blocks);
        case 'openai':
            return sputnik_ai_openai($messages, $allowed_blocks);
        case 'google':
            return sputnik_ai_google($messages, $allowed_blocks);
        default:
            return [
                'error' => "Unknown AI provider: {$provider}. Set SPUTNIK_AI_PROVIDER to 'anthropic', 'openai', or 'google'."
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
    
    $api_key = getenv('ANTHROPIC_API_KEY');
    if (!$api_key) {
        return ['error' => 'ANTHROPIC_API_KEY environment variable not set'];
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
    
    $api_key = getenv('OPENAI_API_KEY');
    if (!$api_key) {
        return ['error' => 'OPENAI_API_KEY environment variable not set'];
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
    
    $api_key = getenv('GOOGLE_API_KEY');
    if (!$api_key) {
        return ['error' => 'GOOGLE_API_KEY environment variable not set'];
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