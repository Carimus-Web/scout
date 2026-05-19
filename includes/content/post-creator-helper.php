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
        error_log('scout_get_repeater_field_keys: acf-json folder not found');
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
                    error_log('scout_get_repeater_field_keys: ' . $block_name . '.' . $field['name'] . ' = ' . $field['key']);
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
        error_log('scout_discover_repeater_fields: acf-json folder not found at: ' . $acf_json_dir);
        return $repeater_map;
    }
    
    error_log('scout_discover_repeater_fields: Scanning ' . $acf_json_dir);
    
    // Scan all JSON files in the acf-json folder
    $json_files = glob($acf_json_dir . '/*.json');
    
    foreach ($json_files as $file) {
        $json_content = file_get_contents($file);
        $field_group = json_decode($json_content, true);
        
        if (!$field_group || !is_array($field_group)) {
            error_log('scout_discover_repeater_fields: Failed to decode JSON file: ' . basename($file));
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
            error_log('scout_discover_repeater_fields: Found repeaters for ' . $block_name . ': ' . implode(', ', $repeater_fields));
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
 * Register repeater field values in post meta for ACF block editing
 * This ensures that when ACF tries to load the block for editing, it can find the field values
 * 
 * Dynamically discovers repeater fields from theme's acf-json folder
 */
function scout_register_repeater_fields($post_id, $layout) {
    if (!function_exists('update_field')) {
        error_log('scout_register_repeater_fields: ACF function update_field not available');
        return;
    }

    // Dynamically discover repeater fields from ACF JSON files
    $repeater_fields = scout_discover_repeater_fields();
    
    error_log('scout_register_repeater_fields: Discovered repeater fields: ' . json_encode($repeater_fields));
    
    if (empty($repeater_fields)) {
        error_log('scout_register_repeater_fields: No repeater fields discovered');
        return;
    }

    // Load ACF field groups to get field keys for repeaters
    $field_keys_map = scout_get_repeater_field_keys();

    foreach ($layout as $block) {
        // Extract block type (e.g., 'carimus/highlights' -> 'highlights')
        $block_type = str_replace('carimus/', '', $block['block']);
        
        error_log('scout_register_repeater_fields: Processing block: ' . $block_type);
        
        if (isset($repeater_fields[$block_type])) {
            error_log('scout_register_repeater_fields: Block ' . $block_type . ' has repeater fields: ' . implode(', ', $repeater_fields[$block_type]));
            
            foreach ($repeater_fields[$block_type] as $field_name) {
                if (isset($block['fields'][$field_name]) && is_array($block['fields'][$field_name])) {
                    error_log('scout_register_repeater_fields: Setting ' . $field_name . ' with ' . count($block['fields'][$field_name]) . ' rows');
                    
                    // Get the field key for this repeater
                    $field_key = $field_keys_map[$block_type][$field_name] ?? null;
                    
                    if ($field_key) {
                        // Delete old meta to ensure clean state
                        delete_post_meta($post_id, $field_name);
                        delete_post_meta($post_id, '_' . $field_name);
                        
                        // Set the field key reference
                        update_post_meta($post_id, '_' . $field_name, $field_key);
                        
                        // Store the row count
                        update_post_meta($post_id, $field_name, count($block['fields'][$field_name]));
                        
                        // Store each row with proper meta keys
                        foreach ($block['fields'][$field_name] as $row_index => $row) {
                            foreach ($row as $sub_field_name => $sub_field_value) {
                                $meta_key = $field_name . '_' . $row_index . '_' . $sub_field_name;
                                update_post_meta($post_id, $meta_key, $sub_field_value);
                            }
                        }
                        
                        error_log("scout_register_repeater_fields: Registered {$field_name} (key: {$field_key}) for block {$block_type} on post {$post_id}");
                    } else {
                        error_log('scout_register_repeater_fields: Field key not found for ' . $field_name . ' in block ' . $block_type);
                    }
                } else {
                    error_log('scout_register_repeater_fields: Field ' . $field_name . ' not found or not array in block ' . $block_type);
                }
            }
        } else {
            error_log('scout_register_repeater_fields: No repeater fields for block type: ' . $block_type);
        }
    }
}

