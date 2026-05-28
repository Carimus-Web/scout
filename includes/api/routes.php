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
                
                global $wp_version;
                $provider = scout_get_ai_provider();
                
                return [
                    'status' => 'API is working',
                    'received' => $params,
                    'provider' => $provider,
                    'has_key' => !empty(scout_get_api_key($provider)),
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
    

});