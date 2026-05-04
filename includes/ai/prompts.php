<?php

/**
 * Build a comprehensive prompt for the AI that enforces strict constraints.
 * The AI acts as a CONTENT WRITER only, not a designer.
 * It must use ONLY the allowed blocks, no exceptions.
 */
function sputnik_build_prompt($messages, $allowed_blocks) {
    
    // Format block metadata for the AI to understand
    $blocks_description = "AVAILABLE BLOCKS (and ONLY these):\n\n";
    
    foreach ($allowed_blocks as $block) {
        $blocks_description .= "### {$block['name']} - {$block['title']}\n";
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
        
        // Include example data if available
        if (!empty($block['example']['attributes']['data'])) {
            $blocks_description .= "Example: " . json_encode($block['example']['attributes']['data']) . "\n";
        }
        
        $blocks_description .= "\n";
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
            "7. When you have enough information and are ready to create the page, return ONLY valid JSON\n\n" .
            "JSON FORMAT (return ONLY when ready to build):\n" .
            "{\n" .
            "  \"layout\": [\n" .
            "    {\n" .
            "      \"block\": \"carimus/block-name\",\n" .
            "      \"fields\": { \"field_name\": \"value\", \"another_field\": \"value\" },\n" .
            "      \"image\": false\n" .
            "    }\n" .
            "  ]\n" .
            "}\n\n" .
            $blocks_description
        ],
        [
            "role" => "user",
            "content" => json_encode($messages)
        ]
    ];
}