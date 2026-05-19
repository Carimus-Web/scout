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

function scout_build_blocks($layout, $postType = null) {

    $blocks = [];
    $totalBlocks = count($layout);
    $blockIndex = 0;
    
    // LOGGING: Check what we're receiving
    error_log('scout_build_blocks called with ' . count($layout) . ' blocks');
    foreach ($layout as $idx => $block) {
        if (!empty($block['fields'])) {
            foreach ($block['fields'] as $fname => $fval) {
                if (is_string($fval) && strlen($fval) < 500) {
                    if (strpos($fval, 'u003c') !== false || strpos($fval, '&lt;') !== false) {
                        error_log("Block {$idx}, field {$fname} contains escaped HTML before: " . substr($fval, 0, 100));
                    }
                }
            }
        }
    }
    
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
                        
                        // Also map sub-fields for repeaters
                        if ($field_type === 'repeater' && !empty($field['sub_fields'])) {
                            foreach ($field['sub_fields'] as $sub_field) {
                                $sub_field_name = $sub_field['name'] ?? '';
                                $sub_field_key = $sub_field['key'] ?? '';
                                if ($sub_field_name) {
                                    $field_keys_map[$block['name']][$field_name . '__' . $sub_field_name] = $sub_field_key;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    $totalBlocks = count($layout);
    
    // Debug logging for repeater fields
    error_log('scout_build_blocks: Processing ' . count($layout) . ' blocks');
    foreach ($layout as $b) {
        if ($b['block'] === 'carimus/what-we-do' || $b['block'] === 'carimus/highlights' || $b['block'] === 'carimus/copy-with-columns') {
            error_log('scout_build_blocks: Block ' . $b['block'] . ' fields: ' . json_encode(array_keys($b['fields'] ?? [])));
            foreach ($b['fields'] ?? [] as $fname => $fval) {
                if (is_array($fval)) {
                    error_log('  - ' . $fname . ' is array with ' . count($fval) . ' items');
                } else {
                    error_log('  - ' . $fname . ' is ' . gettype($fval));
                }
            }
        }
    }
    
    foreach ($layout as $blockIndex => $block) {

        $fields = $block['fields'];
        $block_type = str_replace('carimus/', '', $block['block']);
        
        // Build block data in ACF Pro's flat format (matching manual block creation)
        $acf_fields = [];
        
        // Process all fields
        foreach ($fields as $field_name => $field_value) {
            $field_type = $field_types_map[$block['block']][$field_name] ?? '';
            $field_key = $field_keys_map[$block['block']][$field_name] ?? '';
            
            // Handle repeater fields - store in flat format like ACF Pro does
            if ($field_type === 'repeater') {
                if (!is_array($field_value)) {
                    if (is_string($field_value)) {
                        $decoded = json_decode($field_value, true);
                        $field_value = is_array($decoded) ? $decoded : [];
                    } else {
                        $field_value = [];
                    }
                }
                
                // Store row count
                $acf_fields[$field_name] = count($field_value);
                
                // Store field key with underscore prefix
                if ($field_key) {
                    $acf_fields['_' . $field_name] = $field_key;
                }
                
                // Flatten repeater rows to ACF Pro format: field_name_0_subfield
                foreach ($field_value as $row_index => $row) {
                    if (is_array($row)) {
                        foreach ($row as $sub_field_name => $sub_field_value) {
                            // Get the ACF sub-field key
                            $sub_field_key = $field_keys_map[$block['block']][$field_name . '__' . $sub_field_name] ?? '';
                            
                            // Clean WYSIWYG-like sub-fields
                            if (is_string($sub_field_value) && (strpos($sub_field_value, '<') !== false || strpos($sub_field_value, 'u003c') !== false)) {
                                $sub_field_value = scout_clean_wysiwyg_content($sub_field_value);
                            }
                            
                            // Store value with flat key
                            $flat_key = $field_name . '_' . $row_index . '_' . $sub_field_name;
                            $acf_fields[$flat_key] = $sub_field_value;
                            
                            // Store field key with underscore prefix
                            if ($sub_field_key) {
                                $acf_fields['_' . $flat_key] = $sub_field_key;
                            }
                        }
                    }
                }
            } 
            // For WYSIWYG and similar rich text fields (non-repeater), clean wrapping tags
            elseif (in_array($field_type, ['wysiwyg', 'richtext', 'html'])) {
                if (is_string($field_value)) {
                    $field_value = scout_clean_wysiwyg_content($field_value);
                }
                $acf_fields[$field_name] = $field_value;
                if ($field_key) {
                    $acf_fields['_' . $field_name] = $field_key;
                }
            }
            // For all other fields
            else {
                $acf_fields[$field_name] = $field_value;
                if ($field_key) {
                    $acf_fields['_' . $field_name] = $field_key;
                }
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

        // Add padding as nested structure (for render template compatibility)
        $acf_fields['padding'] = [
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
        
        // Add settings field
        $acf_fields['settings'] = [
            'padding' => $acf_fields['padding'],
            'id' => '',
            'z-index' => 'auto'
        ];

        $blocks[] = [
            'blockName' => $block['block'],
            'attrs' => [
                'name' => $block['block'],
                'mode' => 'auto',
                'data' => $acf_fields
            ],
            'innerBlocks' => []
        ];
    }

    $serialized = serialize_blocks($blocks);
    return $serialized;
}