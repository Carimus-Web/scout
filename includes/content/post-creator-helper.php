<?php

/**
 * Registers block field data in post meta for ACF retrieval
 * 
 * ACF render templates call get_field() which reads from post meta.
 * This function stores field data in post meta in the flat format that ACF expects,
 * so get_field() can retrieve and convert it to nested format for templates.
 * 
 * Block attributes store data in flat format (for the ACF block editor).
 * Post meta also stores data in flat format (for get_field() to read).
 * get_field() automatically converts flat format to nested for templates.
 * 
 * @param int $post_id Post ID
 * @param array $layout Block layout array
 */
function scout_register_repeater_fields($post_id, $layout) {
    require_once SCOUT_PATH . 'includes/blocks/allowed.php';
    
    $field_keys_cache = [];
    
    // Build a cache of field keys for all blocks
    $theme_dir = get_stylesheet_directory();
    $acf_json_dir = $theme_dir . '/acf-json';
    
    if (is_dir($acf_json_dir)) {
        $json_files = glob($acf_json_dir . '/*.json');
        foreach ($json_files as $file) {
            $json_content = file_get_contents($file);
            $field_group = json_decode($json_content, true);
            if ($field_group && is_array($field_group) && !empty($field_group['fields'])) {
                foreach ($field_group['fields'] as $field) {
                    if (!empty($field['name']) && !empty($field['key'])) {
                        $field_keys_cache[$field['name']] = $field['key'];
                    }
                    if (!empty($field['sub_fields'])) {
                        foreach ($field['sub_fields'] as $sub_field) {
                            if (!empty($sub_field['name']) && !empty($sub_field['key'])) {
                                $field_keys_cache[$sub_field['name']] = $sub_field['key'];
                            }
                        }
                    }
                }
            }
        }
    }
    
    // Process each block and store field data in post meta in FLAT format
    foreach ($layout as $block) {
        if (!isset($block['fields']) || !is_array($block['fields'])) {
            continue;
        }
        
        foreach ($block['fields'] as $field_name => $field_value) {
            // Check if this is a repeater (array of arrays)
            if (is_array($field_value) && !empty($field_value) && isset($field_value[0]) && is_array($field_value[0])) {
                // Store repeater in flat format for ACF
                // Format: highlights_0_eyebrow, highlights_0_headline, highlights_1_eyebrow, etc.
                
                // Store row count
                update_post_meta($post_id, $field_name, count($field_value));
                
                // Store field key
                if (isset($field_keys_cache[$field_name])) {
                    update_post_meta($post_id, '_' . $field_name, $field_keys_cache[$field_name]);
                }
                
                // Flatten and store each row
                foreach ($field_value as $row_index => $row) {
                    if (is_array($row)) {
                        foreach ($row as $sub_field_name => $sub_field_value) {
                            $flat_key = $field_name . '_' . $row_index . '_' . $sub_field_name;
                            update_post_meta($post_id, $flat_key, $sub_field_value);
                            
                            // Also store the sub-field key reference for ACF
                            if (isset($field_keys_cache[$sub_field_name])) {
                                update_post_meta($post_id, '_' . $flat_key, $field_keys_cache[$sub_field_name]);
                            }
                        }
                    }
                }
            } else {
                // Non-repeater fields - store as-is
                update_post_meta($post_id, $field_name, $field_value);
                if (isset($field_keys_cache[$field_name])) {
                    update_post_meta($post_id, '_' . $field_name, $field_keys_cache[$field_name]);
                }
            }
        }
    }
}



