<?php

/**
 * Extract repeater field keys from ACF JSON files
 * Returns a map of block names to repeater field names to ACF field keys
 * 
 * @return array Map like: ['highlights' => ['highlights' => 'field_699d51ac08929']]
 */
function scout_get_repeater_field_keys() {
    static $field_keys_map = null;
    
    if ($field_keys_map !== null) {
        return $field_keys_map;
    }
    
    $field_keys_map = [];
    
    $theme_dir = get_stylesheet_directory();
    $acf_json_dir = $theme_dir . '/acf-json';
    
    if (!is_dir($acf_json_dir)) {
        return $field_keys_map;
    }
    
    $json_files = glob($acf_json_dir . '/*.json');
    
    foreach ($json_files as $file) {
        $json_content = file_get_contents($file);
        $field_group = json_decode($json_content, true);
        
        if (!$field_group || !is_array($field_group)) {
            continue;
        }
        
        $block_name = scout_get_block_from_field_group($field_group);
        if (!$block_name) {
            continue;
        }
        
        $block_name = str_replace('carimus/', '', $block_name);
        
        // Extract repeater fields with their keys
        if (!empty($field_group['fields']) && is_array($field_group['fields'])) {
            foreach ($field_group['fields'] as $field) {
                if (is_array($field) && ($field['type'] ?? '') === 'repeater' && !empty($field['name'])) {
                    if (!isset($field_keys_map[$block_name])) {
                        $field_keys_map[$block_name] = [];
                    }
                    $field_keys_map[$block_name][$field['name']] = $field['key'];
                }
            }
        }
    }
    
    return $field_keys_map;
}

/**
 * Scans the theme's acf-json folder and builds a dynamic map of blocks to repeater fields
 * 
 * @return array Map of block names (without 'carimus/' prefix) to array of repeater field names
 * Example: ['highlights' => ['highlights'], 'what-we-do' => ['slides']]
 */
function scout_discover_repeater_fields() {
    static $repeater_map = null;
    
    // Cache the result so we don't scan on every call
    if ($repeater_map !== null) {
        return $repeater_map;
    }
    
    $repeater_map = [];
    
    // Try to find acf-json folder in the active theme
    $theme_dir = get_stylesheet_directory();
    $acf_json_dir = $theme_dir . '/acf-json';
    
    if (!is_dir($acf_json_dir)) {
        return $repeater_map;
    }
    
    // Scan all JSON files in the acf-json folder
    $json_files = glob($acf_json_dir . '/*.json');
    
    foreach ($json_files as $file) {
        $json_content = file_get_contents($file);
        $field_group = json_decode($json_content, true);
        
        if (!$field_group || !is_array($field_group)) {
            continue;
        }
        
        // Check if this field group is for a block
        $block_name = scout_get_block_from_field_group($field_group);
        
        if (!$block_name) {
            continue;
        }
        
        // Clean up block name (remove 'carimus/' prefix if present)
        $block_name = str_replace('carimus/', '', $block_name);
        
        // Find all repeater fields in this group
        $repeater_fields = scout_extract_repeater_fields($field_group);
        
        if (!empty($repeater_fields)) {
            $repeater_map[$block_name] = $repeater_fields;
        }
    }
    
    return $repeater_map;
}

/**
 * Extract the block name from a field group if it's associated with a block
 * 
 * @param array $field_group ACF field group array from JSON
 * @return string|null Block name (e.g., 'carimus/highlights') or null if not a block field group
 */
function scout_get_block_from_field_group($field_group) {
    if (empty($field_group['location']) || !is_array($field_group['location'])) {
        return null;
    }
    
    // location is an array of arrays - look for block conditions
    foreach ($field_group['location'] as $condition_group) {
        if (!is_array($condition_group)) {
            continue;
        }
        
        foreach ($condition_group as $condition) {
            if (!is_array($condition)) {
                continue;
            }
            
            // Look for: param = 'block', value = 'carimus/...'
            if (($condition['param'] ?? '') === 'block' && !empty($condition['value'])) {
                return $condition['value'];
            }
        }
    }
    
    return null;
}

/**
 * Extract all repeater field names from a field group
 * 
 * @param array $field_group ACF field group array from JSON
 * @return array List of repeater field names
 */
function scout_extract_repeater_fields($field_group) {
    $repeater_fields = [];
    
    if (empty($field_group['fields']) || !is_array($field_group['fields'])) {
        return $repeater_fields;
    }
    
    foreach ($field_group['fields'] as $field) {
        if (!is_array($field)) {
            continue;
        }
        
        // Check if this field is a repeater
        if (($field['type'] ?? '') === 'repeater' && !empty($field['name'])) {
            $repeater_fields[] = $field['name'];
        }
    }
    
    return $repeater_fields;
}

/**
 * Register block data in post meta for ACF editor and render templates
 * 
 * Block attributes store data in flat format (highlights_0_eyebrow, etc.)
 * Post meta stores data in formats that ACF and render templates expect
 */
function scout_register_repeater_fields($post_id, $layout) {
    foreach ($layout as $blockIndex => $block) {
        $block_type = str_replace('carimus/', '', $block['block']);
        $isLastBlock = ($blockIndex === count($layout) - 1);
        $bottomPadding = $isLastBlock ? 'none' : 'lg';
        
        // Store padding in nested format for render templates
        // Render templates call get_field('padding') and ACF looks in post meta
        $padding_nested = [
            'top' => [
                'desktop' => 'none',
                'desktop_custom' => '',
                'tablet' => 'none',
                'tablet_custom' => '',
                'mobile' => 'none',
                'mobile_custom' => ''
            ],
            'bottom' => [
                'desktop' => $bottomPadding,
                'desktop_custom' => '',
                'tablet' => $bottomPadding,
                'tablet_custom' => '',
                'mobile' => $bottomPadding,
                'mobile_custom' => ''
            ]
        ];
        
        update_post_meta($post_id, 'padding', $padding_nested);
    }
}



