<?php

// All includes handled in main sputnik.php file

// Ensure blocks are initialized for REST API context
add_action('rest_api_init', function () {
    // Force block editor initialization in REST context
    do_action('enqueue_block_editor_assets');
});

add_action('wp_loaded', function () {
    // Register REST routes after WordPress has fully loaded
    // This ensures blocks from themes/plugins are registered first
    register_rest_route('sputnik/v1', '/test', [
        'methods' => 'POST',
        'callback' => function($request) {
            try {
                $params = $request->get_json_params();
                
                // Check what blocks are available
                $blocks_function_exists = function_exists('get_block_types');
                
                // Use new discovery function that scans theme directory
                $all_discovered_blocks = sputnik_discover_blocks_from_theme();
                $carimus_blocks = array_filter($all_discovered_blocks, function($b) {
                    return strpos($b['name'], 'carimus/') === 0;
                });
                
                // Also try WordPress registry
                $wp_blocks = [];
                if ($blocks_function_exists) {
                    $wp_blocks = get_block_types();
                }
                
                // Debug: check WP version and theme
                global $wp_version;
                
                // Debug API key storage
                $provider = sputnik_get_ai_provider();
                $stored_key = get_option('sputnik_api_key', '');
                $retrieved_key = sputnik_get_api_key($provider);
                $key_length = strlen($retrieved_key);
                
                // Log for debugging
                error_log('Sputnik Test - Discovered blocks: ' . count($all_discovered_blocks) . ', Carimus blocks: ' . count($carimus_blocks));
                error_log('Sputnik Test - Provider: ' . $provider . ', Key stored: ' . (!empty($stored_key) ? 'YES' : 'NO') . ', Key retrieved length: ' . $key_length);
                
                return [
                    'status' => 'API is working',
                    'received' => $params,
                    'provider' => sputnik_get_ai_provider(),
                    'has_key' => !empty(sputnik_get_api_key(sputnik_get_ai_provider())),
                    'api_key_debug' => [
                        'provider' => $provider,
                        'stored_in_options' => !empty($stored_key),
                        'retrieved_key_length' => $key_length,
                        'first_10_chars' => substr($retrieved_key, 0, 10),
                    ],
                    'wp_version' => $wp_version ?? 'unknown',
                    'current_theme' => wp_get_theme()->get('Name') ?? 'none',
                    'blocks_api_exists' => $blocks_function_exists,
                    'blocks_from_registry' => $wp_blocks,
                    'blocks_discovered_from_theme' => $all_discovered_blocks,
                    'carimus_blocks_found' => array_values($carimus_blocks),
                    'discovered_blocks_count' => count($all_discovered_blocks),
                    'carimus_blocks_count' => count($carimus_blocks)
                ];
            } catch (Throwable $e) {
                return ['error' => $e->getMessage()];
            }
        },
        'permission_callback' => '__return_true',
    ]);
    
    register_rest_route('sputnik/v1', '/chat', [
        'methods' => 'POST',
        'callback' => function($request) {
            try {
                return sputnik_chat_handler($request);
            } catch (Throwable $e) {
                error_log('Sputnik Chat Error: ' . $e->getMessage());
                return [
                    'error' => $e->getMessage()
                ];
            }
        },
        'permission_callback' => '__return_true',
    ]);
    
    register_rest_route('sputnik/v1', '/create-page', [
        'methods' => 'POST',
        'callback' => function($request) {
            try {
                return sputnik_create_page_handler($request);
            } catch (Throwable $e) {
                error_log('Sputnik Create Page Error: ' . $e->getMessage());
                return [
                    'error' => $e->getMessage()
                ];
            }
        },
        'permission_callback' => '__return_true',
    ]);
});