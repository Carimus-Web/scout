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
    if ($postType) {
        $allowed_blocks = scout_get_allowed_blocks($postType);
        // Build map of block names to field types
        foreach ($allowed_blocks as $block) {
            if (!empty($block['acf']['fields'])) {
                foreach ($block['acf']['fields'] as $field) {
                    $field_name = $field['name'] ?? $field['key'] ?? '';
                    $field_type = $field['type'] ?? '';
                    if ($field_name) {
                        $field_types_map[$block['name']][$field_name] = $field_type;
                    }
                }
            }
        }
    }

    $totalBlocks = count($layout);
    
    foreach ($layout as $blockIndex => $block) {

        $fields = $block['fields'];
        
        // Clean WYSIWYG fields by removing wrapping tags
        // WYSIWYG fields should contain inner content; the render template uses wpautop() to format
        if (!empty($field_types_map[$block['block']])) {
            foreach ($fields as $field_name => &$field_value) {
                $field_type = $field_types_map[$block['block']][$field_name] ?? '';
                // For WYSIWYG and similar rich text fields, clean wrapping tags
                if (in_array($field_type, ['wysiwyg', 'richtext', 'html'])) {
                    if (is_string($field_value)) {
                        // Remove wrapping <p> tags so the render template can properly format
                        $field_value = scout_clean_wysiwyg_content($field_value);
                    }
                }
            }
            unset($field_value);
        }

        // Handle image field
        // Claude returns image IDs - convert to full ACF image array
        // This is what theme's get_field('image') will return
        if (isset($block['image'])) {
            if (is_numeric($block['image']) && $block['image'] > 0) {
                // Convert attachment ID to full ACF image array
                $image_array = scout_attachment_id_to_acf_image($block['image']);
                if ($image_array) {
                    $fields['image'] = $image_array;
                }
            } elseif (!empty($block['image'])) {
                // Fallback: pick random image if image is just true
                $random_id = scout_get_placeholder_image();
                $image_array = scout_attachment_id_to_acf_image($random_id);
                if ($image_array) {
                    $fields['image'] = $image_array;
                }
            }
        }

        // Determine bottom padding: none for last block, lg for all others
        $isLastBlock = ($blockIndex === $totalBlocks - 1);
        $bottomPadding = $isLastBlock ? 'none' : 'lg';

        // Add padding settings to every section
        // Top padding is always 'none', bottom padding depends on block position
        // These are top-level fields that the render template accesses via get_field('padding')
        $fields['padding'] = [
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

        // Add settings field if needed by the block
        $fields['settings'] = [
            'padding' => $fields['padding']
        ];

        $blocks[] = [
            'blockName' => $block['block'],
            'attrs' => ['data' => $fields],
            'innerBlocks' => []
        ];
    }

    $serialized = serialize_blocks($blocks);
    return $serialized;
}