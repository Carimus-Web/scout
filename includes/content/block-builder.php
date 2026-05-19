<?php

require_once SCOUT_PATH . 'includes/media/placeholder.php';
require_once SCOUT_PATH . 'includes/blocks/allowed.php';

/**
 * Clean WYSIWYG content by removing all HTML tags
 * WYSIWYG fields will store plain text
 * The render template will use wpautop() to format the output
 */
function scout_clean_wysiwyg_content($content) {
    if (!is_string($content)) {
        return $content;
    }
    
    // Strip all HTML tags - just store the plain text
    // The render template will handle formatting with wpautop()
    $content = strip_tags($content);
    
    return trim($content);
}

/**
 * Get all field keys for a specific block type from ACF JSON definitions
 * 
 * @param string $block_type Block type without 'carimus/' prefix (e.g., 'highlights')
 * @return array Map of field names to ACF field keys
 */
function scout_get_all_field_keys_for_block($block_type) {
    static $all_keys_cache = [];
    
    if (isset($all_keys_cache[$block_type])) {
        return $all_keys_cache[$block_type];
    }
    
    $field_keys = [];
    $theme_dir = get_stylesheet_directory();
    $acf_json_dir = $theme_dir . '/acf-json';
    
    if (!is_dir($acf_json_dir)) {
        return $field_keys;
    }
    
    $json_files = glob($acf_json_dir . '/*.json');
    
    foreach ($json_files as $file) {
        $json_content = file_get_contents($file);
        $field_group = json_decode($json_content, true);
        
        if (!$field_group || !is_array($field_group)) {
            continue;
        }
        
        // Check if this field group is for our block (look in location conditions)
        $is_for_block = false;
        if (!empty($field_group['location']) && is_array($field_group['location'])) {
            foreach ($field_group['location'] as $condition_group) {
                if (!is_array($condition_group)) continue;
                foreach ($condition_group as $condition) {
                    if (!is_array($condition)) continue;
                    if (($condition['param'] ?? '') === 'block') {
                        $block_value = $condition['value'] ?? '';
                        if (strpos($block_value, $block_type) !== false || strpos($block_value, 'carimus/' . $block_type) !== false) {
                            $is_for_block = true;
                            break 2;
                        }
                    }
                }
            }
        }
        
        if (!$is_for_block) {
            continue;
        }
        
        // Extract all field keys and their sub-field keys
        if (!empty($field_group['fields']) && is_array($field_group['fields'])) {
            foreach ($field_group['fields'] as $field) {
                $field_name = $field['name'] ?? '';
                $field_key = $field['key'] ?? '';
                
                if ($field_name && $field_key) {
                    $field_keys[$field_name] = $field_key;
                }
                
                // Also get sub-field keys (for groups, repeaters, etc.)
                if (!empty($field['sub_fields']) && is_array($field['sub_fields'])) {
                    foreach ($field['sub_fields'] as $sub_field) {
                        $sub_field_name = $sub_field['name'] ?? '';
                        $sub_field_key = $sub_field['key'] ?? '';
                        
                        if ($sub_field_name && $sub_field_key) {
                            $field_keys[$sub_field_name] = $sub_field_key;
                        }
                    }
                }
            }
        }
        
        break; // Found the field group, no need to continue
    }
    
    $all_keys_cache[$block_type] = $field_keys;
    return $field_keys;
}



function scout_build_blocks($layout, $postType = null) {

    $blocks = [];
    $totalBlocks = count($layout);
    $blockIndex = 0;
    
    // Get allowed blocks to check field types for WYSIWYG fields
    $allowed_blocks = [];
    $field_types_map = [];
    $field_keys_map = [];  // Map field names to ACF field keys
    if ($postType) {
        $allowed_blocks = scout_get_allowed_blocks($postType);
        // Build map of block names to field types and keys
        foreach ($allowed_blocks as $block) {
            if (!empty($block['acf']['fields'])) {
                foreach ($block['acf']['fields'] as $field) {
                    $field_name = $field['name'] ?? $field['key'] ?? '';
                    $field_type = $field['type'] ?? '';
                    $field_key = $field['key'] ?? '';
                    if ($field_name) {
                        $field_types_map[$block['name']][$field_name] = $field_type;
                        $field_keys_map[$block['name']][$field_name] = $field_key;
                        
                        // Map ALL sub-fields (repeaters, groups, etc.)
                        if (!empty($field['sub_fields'])) {
                            foreach ($field['sub_fields'] as $sub_field) {
                                $sub_field_name = $sub_field['name'] ?? '';
                                $sub_field_key = $sub_field['key'] ?? '';
                                if ($sub_field_name) {
                                    // For repeater sub-fields, use __notation
                                    if ($field_type === 'repeater') {
                                        $field_keys_map[$block['name']][$field_name . '__' . $sub_field_name] = $sub_field_key;
                                    } 
                                    // For group sub-fields (like padding sub-fields), map them directly
                                    else {
                                        $field_keys_map[$block['name']][$sub_field_name] = $sub_field_key;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    $totalBlocks = count($layout);
    
    foreach ($layout as $blockIndex => $block) {

        $fields = $block['fields'];
        $block_type = str_replace('carimus/', '', $block['block']);
        
        // Build block data in FLAT format
        $acf_fields = [];
        
        // Process all fields
        foreach ($fields as $field_name => $field_value) {
            $field_type = $field_types_map[$block['block']][$field_name] ?? '';
            $field_key = $field_keys_map[$block['block']][$field_name] ?? '';
            
            // FALLBACK: If field type wasn't mapped, try to detect it from data structure
            if (!$field_type && is_array($field_value) && !empty($field_value)) {
                if (isset($field_value[0]) && is_array($field_value[0])) {
                    $field_type = 'repeater';
                }
            }
            
            // Handle repeater fields - store in FLAT format
            if ($field_type === 'repeater') {
                if (!is_array($field_value)) {
                    if (is_string($field_value)) {
                        $decoded = json_decode($field_value, true);
                        $field_value = is_array($decoded) ? $decoded : [];
                    } else {
                        $field_value = [];
                    }
                }
                
                // Store the row count
                $acf_fields[$field_name] = count($field_value);
                
                // Store field key reference
                if ($field_key) {
                    $acf_fields['_' . $field_name] = $field_key;
                }
                
                // Flatten each row: highlights_0_eyebrow, highlights_0_headline, etc.
                foreach ($field_value as $row_index => $row) {
                    if (is_array($row)) {
                        foreach ($row as $sub_field_name => $sub_field_value) {
                            // Clean WYSIWYG content
                            if (is_string($sub_field_value) && (strpos($sub_field_value, '<') !== false || strpos($sub_field_value, 'u003c') !== false)) {
                                $sub_field_value = scout_clean_wysiwyg_content($sub_field_value);
                            }
                            
                            // Store flat key: field_name_index_subfield
                            $flat_key = $field_name . '_' . $row_index . '_' . $sub_field_name;
                            $acf_fields[$flat_key] = $sub_field_value;
                        }
                    }
                }
            } 
            // For WYSIWYG and similar rich text fields, clean the content
            elseif (in_array($field_type, ['wysiwyg', 'richtext', 'html'])) {
                if (is_string($field_value)) {
                    $field_value = scout_clean_wysiwyg_content($field_value);
                }
                $acf_fields[$field_name] = $field_value;
            }
            // For all other fields
            else {
                $acf_fields[$field_name] = $field_value;
            }
        }
        
        // Handle image field
        if (isset($block['image'])) {
            if (is_numeric($block['image']) && $block['image'] > 0) {
                $image_array = scout_attachment_id_to_acf_image($block['image']);
                if ($image_array) {
                    $acf_fields['image'] = $image_array;
                }
            } elseif (!empty($block['image'])) {
                $random_id = scout_get_placeholder_image();
                $image_array = scout_attachment_id_to_acf_image($random_id);
                if ($image_array) {
                    $acf_fields['image'] = $image_array;
                }
            }
        }

        // Determine bottom padding: none for last block, lg for all others
        $isLastBlock = ($blockIndex === $totalBlocks - 1);
        $bottomPadding = $isLastBlock ? 'none' : 'lg';

        // Add padding in NESTED format for render templates (they call get_field('padding'))
        // The structure matches ACF's nested format
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
        
        $acf_fields['padding'] = $padding_nested;
        
        // Add settings field as empty
        $acf_fields['settings'] = '';
        $acf_fields['id'] = '';
        $acf_fields['z-index'] = '';
        
        // Get all field keys and add them with underscore
        $field_keys_for_block = scout_get_all_field_keys_for_block($block_type);
        foreach ($field_keys_for_block as $fname => $fkey) {
            if (!isset($acf_fields['_' . $fname])) {
                $acf_fields['_' . $fname] = $fkey;
            }
        }

        $blocks[] = [
            'blockName' => $block['block'],
            'attrs' => array_merge([
                'name' => $block['block'],
                'mode' => 'auto'
            ], ['data' => $acf_fields]),
            'innerBlocks' => []
        ];
    }

    $serialized = serialize_blocks($blocks);
    return $serialized;
}