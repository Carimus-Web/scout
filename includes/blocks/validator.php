<?php

/**
 * Strictly validate that generated layout uses ONLY allowed blocks and valid fields.
 * No exceptions. No invented blocks. No invented fields.
 */
function scout_validate_layout($layout, $allowed_blocks) {

    if (!is_array($layout) || empty($layout)) {
        return false;
    }

    // Build map of allowed blocks and their valid fields
    $allowed_map = [];
    $allowed_block_names = [];

    foreach ($allowed_blocks as $block) {
        $allowed_block_names[] = $block['name'];
        $valid_fields = [];
        
        // Extract field names from ACF config
        if (!empty($block['acf']['fields']) && is_array($block['acf']['fields'])) {
            foreach ($block['acf']['fields'] as $field) {
                $field_name = $field['name'] ?? $field['key'] ?? '';
                if ($field_name) {
                    $valid_fields[] = $field_name;
                }
            }
        }
        
        $allowed_map[$block['name']] = $valid_fields;
    }

    // Validate each block in the layout
    foreach ($layout as $block) {
        
        // Check block exists in allowed list
        if (!in_array($block['block'], $allowed_block_names, true)) {
            error_log("Scout validation failed: Unknown block '{$block['block']}'");
            return false;
        }

        // Validate all fields are allowed for this block
        if (!empty($block['fields']) && is_array($block['fields'])) {
            foreach ($block['fields'] as $key => $val) {
                if (!in_array($key, $allowed_map[$block['block']], true)) {
                    error_log("Scout validation failed: Invalid field '{$key}' for block '{$block['block']}'");
                    return false;
                }
            }
        }
    }

    return true;
}