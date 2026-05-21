<?php

/**
 * Discover blocks by scanning theme directory for block.json files
 * Falls back to WordPress Block Registry if available
 */
function scout_get_allowed_blocks($postType) {
    
    $allowed_blocks = [];
    
    // Try WordPress Block Registry first
    if (function_exists('get_block_types')) {
        $blocks = get_block_types();
        foreach ($blocks as $block_name) {
            if (strpos($block_name, 'carimus/') === 0) {
                $block_type = get_block_type($block_name);
                if ($block_type) {
                    $allowed_blocks[] = [
                        'name' => $block_name,
                        'title' => $block_type->title ?? '',
                        'description' => $block_type->description ?? '',
                        'category' => $block_type->category ?? '',
                        'icon' => $block_type->icon ?? '',
                        'attributes' => $block_type->attributes ?? [],
                        'acf' => $block_type->acf ?? [],
                        'example' => $block_type->example ?? []
                    ];
                }
            }
        }
    }
    
    // If WordPress Registry empty, scan theme for block.json files
    if (empty($allowed_blocks)) {
        $allowed_blocks = scout_discover_blocks_from_theme();
    }
    
    // Apply theme's allowed_blocks filter to respect post-type restrictions
    // Create a post object similar to what WordPress expects
    $post_obj = (object)[];
    $post_obj->post_type = $postType;
    
    $theme_allowed = apply_filters('allowed_block_types_all', true, $post_obj);
    
    if (is_array($theme_allowed) && !empty($theme_allowed)) {
        $allowed_blocks = array_filter($allowed_blocks, function($block) use ($theme_allowed) {
            return in_array($block['name'], $theme_allowed);
        });
        
        // FALLBACK: If filtering resulted in no blocks for posts, use all discovered blocks
        // This handles the case where theme restricts 'post' but we want to show blocks for AI
        if (empty($allowed_blocks) && $postType === 'post') {
            $allowed_blocks = scout_discover_blocks_from_theme();
        }
    }
    
    return array_values($allowed_blocks);
}

/**
 * Discover Carimus blocks by scanning theme blocks directory
 * Looks for block.json files and parses their metadata
 */
function scout_discover_blocks_from_theme() {
    $blocks = [];
    $theme = wp_get_theme();
    $theme_dir = $theme->get_stylesheet_directory();
    
    // Common block directory patterns
    $block_dirs = [
        $theme_dir . '/blocks',
        $theme_dir . '/src/blocks',
        $theme_dir . '/inc/blocks',
        $theme_dir . '/templates/blocks',  // Carimus Backbone stores blocks here
    ];
    
    foreach ($block_dirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        
        // Recursively scan for block.json files
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if ($file->getBasename() === 'block.json') {
                $block_data = json_decode(file_get_contents($file->getPathname()), true);
                
                if ($block_data && isset($block_data['name'])) {
                    // Only include Carimus blocks
                    if (strpos($block_data['name'], 'carimus/') === 0) {
                        $blocks[] = [
                            'name' => $block_data['name'],
                            'title' => $block_data['title'] ?? '',
                            'description' => $block_data['description'] ?? '',
                            'category' => $block_data['category'] ?? '',
                            'icon' => $block_data['icon'] ?? '',
                            'attributes' => $block_data['attributes'] ?? [],
                            'acf' => $block_data['acf'] ?? [],
                            'example' => $block_data['example'] ?? []
                        ];
                    }
                }
            }
        }
    }
    
    return $blocks;
}