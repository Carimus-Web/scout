<?php

/**
 * Get allowed blocks for a post type by querying the WordPress block registry
 * and reading their metadata from block.json files.
 */
function sputnik_get_allowed_blocks($postType) {
    
    $blocks = get_block_types();
    $allowed_blocks = [];
    
    // Filter to only Carimus blocks
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
    
    // Apply theme's allowed_blocks filter to respect post-type restrictions
    $theme_allowed = apply_filters('allowed_block_types_all', true, (object)['post' => (object)['post_type' => $postType]]);
    
    if (is_array($theme_allowed) && !empty($theme_allowed)) {
        $allowed_blocks = array_filter($allowed_blocks, function($block) use ($theme_allowed) {
            return in_array($block['name'], $theme_allowed);
        });
    }
    
    return $allowed_blocks;
}