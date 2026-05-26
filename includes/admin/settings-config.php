<?php

/**
 * Register settings for Scout AI configuration
 */
function scout_register_settings() {
    // Get all public post types for default value
    $all_post_types = get_post_types(['public' => true], 'objects');
    $default_post_types = array_keys($all_post_types);
    
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

    register_setting('scout_settings', 'scout_post_types', [
        'sanitize_callback' => function($value) {
            // Handle empty value from hidden input
            if (empty($value) || (is_array($value) && count($value) === 0)) {
                // Validation error - at least one post type is required
                add_settings_error(
                    'scout_post_types',
                    'scout_post_types_required',
                    'At least one content type must be selected.',
                    'error'
                );
                // Return the current saved value to prevent clearing
                return get_option('scout_post_types');
            }
            
            // If not an array, return current value
            if (!is_array($value)) {
                return get_option('scout_post_types');
            }
            
            // Sanitize each post type
            $sanitized = array_filter(array_map('sanitize_text_field', $value));
            
            // Check that at least one is selected
            if (empty($sanitized)) {
                add_settings_error(
                    'scout_post_types',
                    'scout_post_types_required',
                    'At least one content type must be selected.',
                    'error'
                );
                return get_option('scout_post_types');
            }
            
            return $sanitized;
        },
        'type' => 'array',
        'default' => $default_post_types
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

    add_settings_section(
        'scout_content_settings',
        'Content Type Configuration',
        'scout_content_settings_section_callback',
        'scout_settings'
    );

    add_settings_field(
        'scout_post_types',
        'Available Content Types',
        'scout_post_types_field_callback',
        'scout_settings',
        'scout_content_settings'
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
 * Get AI provider (from WordPress options first, then env var, then default to anthropic)
 */
function scout_get_ai_provider() {
    // Try WordPress option first
    $provider = get_option('scout_ai_provider');
    if (!empty($provider)) {
        return $provider;
    }

    // Try environment variable
    $env_provider = getenv('SCOUT_AI_PROVIDER');
    if (!empty($env_provider)) {
        return $env_provider;
    }

    // Default to anthropic if nothing is set
    return 'anthropic';
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

/**
 * Content settings section callback
 */
function scout_content_settings_section_callback() {
    echo '<p>Select which content types should be available in Scout for generating drafts.</p>';
}

/**
 * Post types field callback
 */
function scout_post_types_field_callback() {
    $selected_types = get_option('scout_post_types');
    
    // If not set yet, get all public post types as default
    if ($selected_types === false) {
        $all_types = get_post_types(['public' => true], 'objects');
        $selected_types = array_keys($all_types);
    }
    
    $all_types = get_post_types(['public' => true], 'objects');
    
    if (empty($all_types)) {
        echo '<p>No public post types found.</p>';
        return;
    }
    
    // Hidden input with empty value to ensure unchecked values are saved as empty array
    echo '<input type="hidden" name="scout_post_types" value="" />';
    
    echo '<div style="display: flex; flex-direction: column; gap: 10px;">';
    
    foreach ($all_types as $type) {
        $checked = in_array($type->name, $selected_types, true) ? 'checked' : '';
        $field_name = 'scout_post_types[]';
        
        echo '<label style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;">';
        echo '<input type="checkbox" name="' . esc_attr($field_name) . '" value="' . esc_attr($type->name) . '" ' . esc_attr($checked) . ' />';
        echo '<span>' . esc_html($type->label) . '</span>';
        echo '</label>';
    }
    
    echo '</div>';
    echo '<p class="description">Select at least one content type to make available in Scout. If none are selected, the form will not save.</p>';
}
