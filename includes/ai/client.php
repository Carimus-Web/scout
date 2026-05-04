<?php

require_once SPUTNIK_PATH . 'includes/ai/prompts.php';

function sputnik_ai_chat($messages, $allowed_blocks) {

    $prompt = sputnik_build_prompt($messages, $allowed_blocks);

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . getenv('OPENAI_API_KEY'),
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode([
            'model' => 'gpt-5',
            'messages' => $prompt
        ])
    ]);

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $content = $body['choices'][0]['message']['content'] ?? '';

    $decoded = json_decode($content, true);

    if ($decoded) return $decoded;

    return ['message' => $content];
}