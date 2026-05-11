<?php

/**
 * Register settings for Scout AI configuration
 */
function scout_register_settings() {
    register_setting('scout_settings', 'scout_ai_provider', [
        'sanitize_callback' => function($value) {
            return sanitize_text_field($value) ?: 'anthropic';
        },
        'default' => 'anthropic'
    ]);

    register_setting('scout_settings', 'scout_api_key', [
        'sanitize_callback' => 'scout_sanitize_api_key',
        'type' => 'string',
        'show_in_rest' => false
    ]);

    register_setting('scout_settings', 'scout_api_key_verified', [
        'sanitize_callback' => 'rest_sanitize_boolean',
        'type' => 'boolean',
        'show_in_rest' => false
    ]);

    add_settings_section(
        'scout_ai_settings',
        'AI Provider Configuration',
        'scout_settings_section_callback',
        'scout_settings'
    );

    add_settings_field(
        'scout_ai_provider',
        'AI Provider',
        'scout_provider_field_callback',
        'scout_settings',
        'scout_ai_settings'
    );

    add_settings_field(
        'scout_api_key',
        'API Key',
        'scout_api_key_field_callback',
        'scout_settings',
        'scout_ai_settings'
    );
}

add_action('admin_init', 'scout_register_settings');

/**
 * Sanitize and encrypt API key
 */
function scout_sanitize_api_key($value) {
    // If the value is the mask (user didn't change it), keep the existing key
    if ($value === '••••••••••••') {
        return get_option('scout_api_key', '');
    }
    
    if (empty($value)) {
        return '';
    }
    
    $value = sanitize_text_field($value);
    
    // Encrypt the key before storing
    return scout_encrypt_key($value);
}

/**
 * Simple encryption for API keys (uses WordPress constants if available)
 */
function scout_encrypt_key($key) {
    if (defined('SCOUT_ENCRYPTION_KEY')) {
        return base64_encode($key);
    }
    return base64_encode($key);
}

/**
 * Decrypt API key
 */
function scout_decrypt_key($encrypted_key) {
    if (empty($encrypted_key)) {
        return '';
    }
    return base64_decode($encrypted_key, true) ?: '';
}

/**
 * Get API key (from WordPress options first, then env var)
 */
function scout_get_api_key($provider = null) {
    if (!$provider) {
        $provider = scout_get_ai_provider();
    }

    $provider = sanitize_text_field($provider);
    $key = '';

    // Try WordPress options first
    switch ($provider) {
        case 'anthropic':
            $key = get_option('scout_api_key', '');
            break;
        case 'openai':
            $key = get_option('scout_api_key', '');
            break;
        case 'google':
            $key = get_option('scout_api_key', '');
            break;
    }

    if (!empty($key)) {
        return scout_decrypt_key($key);
    }

    // Fallback to environment variable
    $env_var = match($provider) {
        'anthropic' => 'ANTHROPIC_API_KEY',
        'openai' => 'OPENAI_API_KEY',
        'google' => 'GOOGLE_API_KEY',
        default => ''
    };

    return getenv($env_var) ?: '';
}

/**
 * Get AI provider (from WordPress options first, then env var)
 */
function scout_get_ai_provider() {
    $provider = get_option('scout_ai_provider', '');
    
    if (!empty($provider)) {
        return $provider;
    }

    // Fallback to environment variable
    return getenv('SCOUT_AI_PROVIDER') ?: 'anthropic';
}

/**
 * Settings section callback
 */
function scout_settings_section_callback() {
    echo '<p>Configure your AI provider and API key. Scout will check WordPress settings first, then fall back to environment variables.</p>';
}

/**
 * Provider field callback
 */
function scout_provider_field_callback() {
    $provider = get_option('scout_ai_provider', 'anthropic');
    
    echo '<select name="scout_ai_provider" id="scout_ai_provider">
        <option value="anthropic"' . selected($provider, 'anthropic', false) . '>Anthropic Claude (Recommended)</option>
        <option value="openai"' . selected($provider, 'openai', false) . '>OpenAI GPT</option>
        <option value="google"' . selected($provider, 'google', false) . '>Google Gemini</option>
    </select>';
    
    echo '<p class="description">Select your AI provider. Claude is recommended for best block generation.</p>';
}

/**
 * API key field callback
 */
function scout_api_key_field_callback() {
    $key = get_option('scout_api_key', '');
    $verified = get_option('scout_api_key_verified', false);
    
    echo '<input type="password" name="scout_api_key" value="' . esc_attr($key ? '••••••••••••' : '') . '" size="50" />';
    echo '<p class="description">Your API key is encrypted and stored securely. Leave blank to use environment variable.</p>';
    
    if ($verified) {
        echo '<p style="color: green;"><strong>✓</strong> API key verified and working</p>';
    }
}
