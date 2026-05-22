<?php

// All includes handled in main scout.php file

// Register REST routes on rest_api_init hook (required by WordPress)
add_action('rest_api_init', function () {
    // Force block editor initialization in REST context
    do_action('enqueue_block_editor_assets');
    
    // Register test endpoint
    register_rest_route('scout/v1', '/test', [
        'methods' => 'POST',
        'callback' => function($request) {
            try {
                $params = $request->get_json_params();
                
                // Check what blocks are available
                $blocks_function_exists = function_exists('get_block_types');
                
                // Use new discovery function that scans theme directory
                $all_discovered_blocks = scout_discover_blocks_from_theme();
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
                $provider = scout_get_ai_provider();
                $stored_key = get_option('scout_api_key', '');
                $retrieved_key = scout_get_api_key($provider);
                $key_length = strlen($retrieved_key);
                
                // Log for debugging
                error_log('Scout Test - Discovered blocks: ' . count($all_discovered_blocks) . ', Carimus blocks: ' . count($carimus_blocks));
                error_log('Scout Test - Provider: ' . $provider . ', Key stored: ' . (!empty($stored_key) ? 'YES' : 'NO') . ', Key retrieved length: ' . $key_length);
                
                return [
                    'status' => 'API is working',
                    'received' => $params,
                    'provider' => scout_get_ai_provider(),
                    'has_key' => !empty(scout_get_api_key(scout_get_ai_provider())),
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
    
    register_rest_route('scout/v1', '/chat', [
        'methods' => 'POST',
        'callback' => function($request) {
            try {
                return scout_chat_handler($request);
            } catch (Throwable $e) {
                error_log('Scout Chat Error: ' . $e->getMessage());
                return [
                    'error' => $e->getMessage()
                ];
            }
        },
        'permission_callback' => '__return_true',
    ]);
    
    register_rest_route('scout/v1', '/create-page', [
        'methods' => 'POST',
        'callback' => function($request) {
            try {
                return scout_create_page_handler($request);
            } catch (Throwable $e) {
                error_log('Scout Create Page Error: ' . $e->getMessage());
                return [
                    'error' => $e->getMessage()
                ];
            }
        },
        'permission_callback' => '__return_true',
    ]);
    
    register_rest_route('scout/v1', '/check-update', [
        'methods' => 'GET',
        'callback' => function($request) {
            // Clear transient cache to force fresh check
            delete_transient('scout_update_check');
            
            // Get debug info
            require_once SCOUT_PATH . 'includes/admin/update-checker.php';
            return scout_debug_update_status();
        },
        'permission_callback' => '__return_true',
    ]);
});