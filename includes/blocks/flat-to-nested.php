<?php

/**
 * Convert flat ACF block data to nested arrays for render templates
 * 
 * ACF Pro stores repeater data flat in block attributes:
 * - highlights_0_eyebrow: "value"
 * - highlights_1_eyebrow: "value"
 * - highlights: 2 (count)
 * 
 * This function converts it to nested format that render templates expect:
 * - highlights: [{eyebrow: "value"}, {eyebrow: "value"}]
 * 
 * @param array $block_data The flat block data from $block['data']
 * @param string $field_name The repeater field name to convert
 * @return array Nested array of repeater rows
 */
function scout_flat_to_nested_repeater($block_data, $field_name) {
    if (!is_array($block_data)) {
        return [];
    }
    
    // Get the row count
    $row_count = $block_data[$field_name] ?? 0;
    if (!is_numeric($row_count) || $row_count == 0) {
        return [];
    }
    
    $nested_rows = [];
    
    // Reconstruct each row from flat keys
    for ($row_index = 0; $row_index < $row_count; $row_index++) {
        $row = [];
        
        // Find all sub-field values for this row
        $prefix = $field_name . '_' . $row_index . '_';
        foreach ($block_data as $key => $value) {
            if (strpos($key, $prefix) === 0 && strpos($key, '_' . $prefix) === false) {
                // Extract sub-field name
                $sub_field_name = substr($key, strlen($prefix));
                $row[$sub_field_name] = $value;
            }
        }
        
        if (!empty($row)) {
            $nested_rows[] = $row;
        }
    }
    
    return $nested_rows;
}
