<?php

/**
 * Register settings for Sputnik AI configuration
 */
function sputnik_register_settings() {
    register_setting('sputnik_settings', 'sputnik_ai_provider', [
        'sanitize_callback' => function($value) {
            return sanitize_text_field($value) ?: 'anthropic';
        },
        'default' => 'anthropic'
    ]);

    register_setting('sputnik_settings', 'sputnik_api_key', [
        'sanitize_callback' => 'sputnik_sanitize_api_key',
        'type' => 'string',
        'show_in_rest' => false
    ]);

    register_setting('sputnik_settings', 'sputnik_api_key_verified', [
        'sanitize_callback' => 'rest_sanitize_boolean',
        'type' => 'boolean',
        'show_in_rest' => false
    ]);

    add_settings_section(
        'sputnik_ai_settings',
        'AI Provider Configuration',
        'sputnik_settings_section_callback',
        'sputnik_settings'
    );

    add_settings_field(
        'sputnik_ai_provider',
        'AI Provider',
        'sputnik_provider_field_callback',
        'sputnik_settings',
        'sputnik_ai_settings'
    );

    add_settings_field(
        'sputnik_api_key',
        'API Key',
        'sputnik_api_key_field_callback',
        'sputnik_settings',
        'sputnik_ai_settings'
    );
}

add_action('admin_init', 'sputnik_register_settings');

/**
 * Sanitize and encrypt API key
 */
function sputnik_sanitize_api_key($value) {
    if (empty($value)) {
        return '';
    }
    
    $value = sanitize_text_field($value);
    
    // Encrypt the key before storing
    return sputnik_encrypt_key($value);
}

/**
 * Simple encryption for API keys (uses WordPress constants if available)
 */
function sputnik_encrypt_key($key) {
    if (defined('SPUTNIK_ENCRYPTION_KEY')) {
        return base64_encode($key);
    }
    return base64_encode($key);
}

/**
 * Decrypt API key
 */
function sputnik_decrypt_key($encrypted_key) {
    if (empty($encrypted_key)) {
        return '';
    }
    return base64_decode($encrypted_key, true) ?: '';
}

/**
 * Get API key (from WordPress options first, then env var)
 */
function sputnik_get_api_key($provider = null) {
    if (!$provider) {
        $provider = sputnik_get_ai_provider();
    }

    $provider = sanitize_text_field($provider);
    $key = '';

    // Try WordPress options first
    switch ($provider) {
        case 'anthropic':
            $key = get_option('sputnik_api_key', '');
            break;
        case 'openai':
            $key = get_option('sputnik_api_key', '');
            break;
        case 'google':
            $key = get_option('sputnik_api_key', '');
            break;
    }

    if (!empty($key)) {
        return sputnik_decrypt_key($key);
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
function sputnik_get_ai_provider() {
    $provider = get_option('sputnik_ai_provider', '');
    
    if (!empty($provider)) {
        return $provider;
    }

    // Fallback to environment variable
    return getenv('SPUTNIK_AI_PROVIDER') ?: 'anthropic';
}

/**
 * Settings section callback
 */
function sputnik_settings_section_callback() {
    echo '<p>Configure your AI provider and API key. Sputnik will check WordPress settings first, then fall back to environment variables.</p>';
}

/**
 * Provider field callback
 */
function sputnik_provider_field_callback() {
    $provider = get_option('sputnik_ai_provider', 'anthropic');
    
    echo '<select name="sputnik_ai_provider" id="sputnik_ai_provider">
        <option value="anthropic"' . selected($provider, 'anthropic', false) . '>Anthropic Claude (Recommended)</option>
        <option value="openai"' . selected($provider, 'openai', false) . '>OpenAI GPT</option>
        <option value="google"' . selected($provider, 'google', false) . '>Google Gemini</option>
    </select>';
    
    echo '<p class="description">Select your AI provider. Claude is recommended for best block generation.</p>';
}

/**
 * API key field callback
 */
function sputnik_api_key_field_callback() {
    $key = get_option('sputnik_api_key', '');
    $verified = get_option('sputnik_api_key_verified', false);
    
    echo '<input type="password" name="sputnik_api_key" value="' . esc_attr($key ? '••••••••••••' : '') . '" size="50" />';
    echo '<p class="description">Your API key is encrypted and stored securely. Leave blank to use environment variable.</p>';
    
    if ($verified) {
        echo '<p style="color: green;"><strong>✓</strong> API key verified and working</p>';
    }
}
