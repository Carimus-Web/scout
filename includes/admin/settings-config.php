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

    // Vector Database settings
    register_setting('scout_settings', 'scout_vector_db_enabled', [
        'sanitize_callback' => 'rest_sanitize_boolean',
        'type' => 'boolean',
        'default' => false
    ]);

    register_setting('scout_settings', 'scout_vector_db_provider', [
        'sanitize_callback' => function($value) {
            return sanitize_text_field($value) ?: 'bedrock';
        },
        'default' => 'bedrock'
    ]);

    register_setting('scout_settings', 'scout_bedrock_region', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'us-east-1'
    ]);

    register_setting('scout_settings', 'scout_bedrock_model', [
        'sanitize_callback' => function($value) {
            return sanitize_text_field($value) ?: 'amazon.titan-embed-text-v1';
        },
        'default' => 'amazon.titan-embed-text-v1'
    ]);

    register_setting('scout_settings', 'scout_vector_db_endpoint', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ]);

    register_setting('scout_settings', 'scout_bedrock_knowledge_base_id', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ]);

    register_setting('scout_settings', 'scout_vector_db_verified', [
        'sanitize_callback' => 'rest_sanitize_boolean',
        'type' => 'boolean',
        'show_in_rest' => false
    ]);

    register_setting('scout_settings', 'scout_bedrock_api_key', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
        'show_in_rest' => false
    ]);

    add_settings_section(
        'scout_vector_db_settings',
        'Vector Database Configuration',
        'scout_vector_db_settings_section_callback',
        'scout_settings'
    );

    add_settings_field(
        'scout_vector_db_enabled',
        'Enable Vector DB for Image Search',
        'scout_vector_db_enabled_field_callback',
        'scout_settings',
        'scout_vector_db_settings'
    );

    add_settings_field(
        'scout_bedrock_region',
        'AWS Region',
        'scout_bedrock_region_field_callback',
        'scout_settings',
        'scout_vector_db_settings'
    );

    add_settings_field(
        'scout_bedrock_model',
        'Bedrock Embedding Model',
        'scout_bedrock_model_field_callback',
        'scout_settings',
        'scout_vector_db_settings'
    );

    add_settings_field(
        'scout_vector_db_endpoint',
        'Vector DB Endpoint',
        'scout_vector_db_endpoint_field_callback',
        'scout_settings',
        'scout_vector_db_settings'
    );

    add_settings_field(
        'scout_bedrock_knowledge_base_id',
        'Bedrock Knowledge Base ID',
        'scout_bedrock_knowledge_base_id_field_callback',
        'scout_settings',
        'scout_vector_db_settings'
    );

    add_settings_field(
        'scout_bedrock_api_key',
        'Bedrock API Key',
        'scout_bedrock_api_key_field_callback',
        'scout_settings',
        'scout_vector_db_settings'
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
 * Check if Vector DB is enabled (from WordPress options first, then env var)
 */
function scout_is_vector_db_enabled() {
    // Try WordPress option first
    $enabled = get_option('scout_vector_db_enabled', false);
    if ($enabled) {
        return true;
    }

    // Try environment variable
    return strtolower(getenv('SCOUT_VECTOR_DB_ENABLED')) === 'true';
}

/**
 * Get Bedrock region (from WordPress options first, then env var)
 */
function scout_get_bedrock_region() {
    // Try WordPress option first
    $region = get_option('scout_bedrock_region');
    if (!empty($region)) {
        return $region;
    }

    // Try environment variable
    $env_region = getenv('SCOUT_BEDROCK_REGION');
    if (!empty($env_region)) {
        return $env_region;
    }

    // Default to us-east-1
    return 'us-east-1';
}

/**
 * Get Bedrock embedding model (from WordPress options first, then env var)
 */
function scout_get_bedrock_model() {
    // Try WordPress option first
    $model = get_option('scout_bedrock_model');
    if (!empty($model)) {
        return $model;
    }

    // Try environment variable
    $env_model = getenv('SCOUT_BEDROCK_MODEL');
    if (!empty($env_model)) {
        return $env_model;
    }

    // Default to Titan
    return 'amazon.titan-embed-text-v1';
}

/**
 * Get Vector DB endpoint (from WordPress options first, then env var)
 */
function scout_get_vector_db_endpoint() {
    // Try WordPress option first
    $endpoint = get_option('scout_vector_db_endpoint');
    if (!empty($endpoint)) {
        return $endpoint;
    }

    // Try environment variable
    $env_endpoint = getenv('SCOUT_VECTOR_DB_ENDPOINT');
    if (!empty($env_endpoint)) {
        return $env_endpoint;
    }

    // Return empty - endpoint may not be needed if using Bedrock only
    return '';
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

/**
 * Vector database settings section callback
 */
function scout_vector_db_settings_section_callback() {
    $enabled = get_option('scout_vector_db_enabled', false);
    echo '<p>';
    echo 'Optionally integrate with AWS Bedrock vector database for semantic image selection. ';
    echo 'If not configured, Scout will randomly select from the 50 most recent uploads.';
    echo '</p>';
    
    if (!$enabled) {
        echo '<p style="color: #666; font-style: italic;">Vector DB is currently disabled. Enable below to configure connection.</p>';
    }
}

/**
 * Vector DB enabled toggle callback
 */
function scout_vector_db_enabled_field_callback() {
    $enabled = get_option('scout_vector_db_enabled', false);
    
    echo '<label style="display: flex; align-items: center; gap: 8px;">';
    echo '<input type="checkbox" name="scout_vector_db_enabled" value="1" ' . checked($enabled, 1, false) . ' />';
    echo '<span>Enable Vector Database integration</span>';
    echo '</label>';
    echo '<p class="description">When enabled, Scout will use semantic similarity to select images from your media library. Requires AWS Bedrock API access.</p>';
}

/**
 * AWS Region field callback
 */
function scout_bedrock_region_field_callback() {
    $region = get_option('scout_bedrock_region', 'us-east-1');
    
    echo '<input type="text" name="scout_bedrock_region" value="' . esc_attr($region) . '" placeholder="us-east-1" />';
    echo '<p class="description">AWS region for Bedrock embeddings. Example: us-east-1, us-west-2, eu-west-1</p>';
}

/**
 * Bedrock Model field callback
 */
function scout_bedrock_model_field_callback() {
    $model = get_option('scout_bedrock_model', 'amazon.titan-embed-text-v1');
    
    echo '<select name="scout_bedrock_model">';
    echo '<option value="amazon.titan-embed-text-v1" ' . selected($model, 'amazon.titan-embed-text-v1', false) . '>Amazon Titan Text Embeddings v1 (Recommended)</option>';
    echo '<option value="cohere.embed-english-v3" ' . selected($model, 'cohere.embed-english-v3', false) . '>Cohere Embed English v3</option>';
    echo '</select>';
    echo '<p class="description">Choose the embedding model for semantic image search. Both models are available on AWS Bedrock.</p>';
}

/**
 * Vector DB endpoint field callback
 */
function scout_vector_db_endpoint_field_callback() {
    $endpoint = get_option('scout_vector_db_endpoint', '');
    
    echo '<input type="text" name="scout_vector_db_endpoint" value="' . esc_attr($endpoint) . '" placeholder="postgresql://user:pass@host:5432/scout_vectors" size="60" />';
    echo '<p class="description">PostgreSQL connection string with pgvector extension. Format: postgresql://user:password@host:port/database. Leave blank if using AWS credentials from environment.</p>';
}

function scout_bedrock_knowledge_base_id_field_callback() {
    $kb_id = get_option('scout_bedrock_knowledge_base_id', '');
    
    echo '<input type="text" name="scout_bedrock_knowledge_base_id" value="' . esc_attr($kb_id) . '" placeholder="XXXXXXXXXXXXXXXXXXXXX" size="60" />';
    echo '<p class="description">Bedrock Knowledge Base ID (found in AWS Console → Bedrock → Knowledge Bases). This is used to retrieve semantically relevant images from your OpenSearch knowledge base.</p>';
}

/**
 * Bedrock API Key field callback
 */
function scout_bedrock_api_key_field_callback() {
    $api_key = get_option('scout_bedrock_api_key', '');
    
    echo '<input type="password" name="scout_bedrock_api_key" value="' . esc_attr($api_key) . '" placeholder="••••••••••••" size="60" />';
    echo '<p class="description">Bedrock API Key for authentication. Generate this in AWS Console → Bedrock → API Keys. This is simpler and more secure than IAM credentials. Leave blank to use IAM credentials or environment variables.</p>';
}
