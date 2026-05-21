<?php

/**
 * Build a comprehensive prompt for the AI that enforces strict constraints.
 * The AI acts as a CONTENT WRITER only, not a designer.
 * It must use ONLY the allowed blocks, no exceptions.
 */
function scout_build_prompt($messages, $allowed_blocks) {
    
    // Get available media library images
    require_once SCOUT_PATH . 'includes/media/placeholder.php';
    $media_images = scout_get_media_library_images(20);
    
    // Identify which blocks support images by checking for image-type fields
    $blocks_with_images = [];
    foreach ($allowed_blocks as $block) {
        $has_image_field = false;
        if (!empty($block['acf']) && !empty($block['acf']['fields'])) {
            foreach ($block['acf']['fields'] as $field) {
                $field_type = $field['type'] ?? '';
                if (in_array($field_type, ['image', 'gallery', 'relationship'])) {
                    $has_image_field = true;
                    break;
                }
            }
        }
        if ($has_image_field) {
            $blocks_with_images[] = $block['name'];
        }
    }
    
    // Format block metadata for the AI to understand
    $blocks_description = "AVAILABLE BLOCKS (and ONLY these):\n\n";
    
    foreach ($allowed_blocks as $block) {
        $is_image_block = in_array($block['name'], $blocks_with_images);
        $blocks_description .= "### {$block['name']} - {$block['title']}" . ($is_image_block ? " [SUPPORTS IMAGES]" : "") . "\n";
        $blocks_description .= "Description: {$block['description']}\n";
        
        // Include ACF field information if available
        if (!empty($block['acf']) && !empty($block['acf']['fields'])) {
            $blocks_description .= "Fields:\n";
            foreach ($block['acf']['fields'] as $field) {
                $field_name = $field['name'] ?? $field['key'] ?? '';
                $field_label = $field['label'] ?? $field_name;
                $field_type = $field['type'] ?? '';
                $blocks_description .= "  - {$field_label} ({$field_name}): {$field_type}\n";
            }
        }
        // Fallback: Extract field names from example data (for auto mode ACF blocks)
        else if (!empty($block['example']['attributes']['data']) && is_array($block['example']['attributes']['data'])) {
            $blocks_description .= "Fields:\n";
            foreach (array_keys($block['example']['attributes']['data']) as $field_name) {
                $blocks_description .= "  - {$field_name}\n";
            }
        }
        
        // Include example data if available
        if (!empty($block['example']['attributes']['data'])) {
            $blocks_description .= "Example: " . json_encode($block['example']['attributes']['data']) . "\n";
        }
        
        $blocks_description .= "\n";
    }
    
    // Format available images for the AI
    $images_description = "AVAILABLE MEDIA LIBRARY IMAGES:\n\n";
    foreach ($media_images as $img) {
        $images_description .= "- ID {$img['id']}: {$img['title']} (Alt: {$img['alt_text']})\n";
    }

    return [
        [
            "role" => "system",
            "content" => "You are a CONTENT WRITER ONLY. Your job is to create first drafts of page content using ONLY the provided blocks.\n\n" .
            "CRITICAL CONSTRAINTS:\n" .
            "1. Use ONLY the blocks listed below - do NOT invent new blocks or sections\n" .
            "2. Use ONLY the field names exactly as specified for each block\n" .
            "3. Generate realistic, relevant content for each field based on the user's input\n" .
            "4. Do NOT try to design the page layout - that's already defined by the blocks\n" .
            "5. Do NOT suggest removing or changing blocks - work with what's available\n" .
            "6. If you don't have enough information, ask clarifying questions\n" .
            "7. Do NOT include emojis in any field values - keep content professional and text-only\n" .
            "8. For WYSIWYG and richtext fields: DO NOT include any HTML markup (no <p>, <b>, <i>, etc.) - only plain text and line breaks\n" .
            "9. When you have enough information and are ready to create the page, return ONLY valid JSON\n\n" .
            "IMAGE HANDLING:\n" .
            "- For blocks marked [SUPPORTS IMAGES], pick an image ID from the available media library that matches the content context\n" .
            "- Use the image ID (not URL) in the JSON response\n" .
            "- Pick images intelligently: e.g., for vehicle-related content, pick images with vehicles; for tech content, pick tech-related images\n" .
            "- If no image is appropriate for a block, use image ID null or omit it\n\n" .
            "JSON FORMAT (return ONLY when ready to build):\n" .
            "{\n" .
            "  \"layout\": [\n" .
            "    {\n" .
            "      \"block\": \"carimus/block-name\",\n" .
            "      \"fields\": { \"field_name\": \"value\", \"another_field\": \"value\" },\n" .
            "      \"image\": IMAGE_ID_OR_NULL\n" .
            "    }\n" .
            "  ]\n" .
            "}\n\n" .
            $blocks_description . "\n" .
            $images_description
        ],
        [
            "role" => "user",
            "content" => json_encode($messages)
        ]
    ];
}