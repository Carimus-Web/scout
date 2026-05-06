<?php

require_once SPUTNIK_PATH . 'includes/media/placeholder.php';

function sputnik_build_blocks($layout) {

    $blocks = [];
    $totalBlocks = count($layout);
    $blockIndex = 0;

    foreach ($layout as $block) {

        $fields = $block['fields'];

        // Handle image field
        // Claude returns image IDs - convert to full ACF image array
        // This is what theme's get_field('image') will return
        if (isset($block['image'])) {
            error_log('DEBUG: Block ' . $blockIndex . ' image value: ' . json_encode($block['image']) . ' (type: ' . gettype($block['image']) . ')');
            
            if (is_numeric($block['image']) && $block['image'] > 0) {
                // Convert attachment ID to full ACF image array
                $image_array = sputnik_attachment_id_to_acf_image($block['image']);
                if ($image_array) {
                    error_log('DEBUG: Image array converted successfully, checking keys: ' . json_encode(array_keys($image_array)));
                    error_log('DEBUG: Full image array: ' . json_encode($image_array));
                    $fields['image'] = $image_array;
                    error_log('DEBUG: Stored in $fields[image], re-checking: ' . json_encode($fields['image']));
                    error_log('DEBUG: $fields[image] has ID key? ' . (isset($fields['image']['ID']) ? 'YES' : 'NO'));
                } else {
                    error_log('DEBUG: sputnik_attachment_id_to_acf_image returned null for ID: ' . $block['image']);
                }
            } elseif (!empty($block['image'])) {
                // Fallback: pick random image if image is just true
                $random_id = sputnik_get_placeholder_image();
                $image_array = sputnik_attachment_id_to_acf_image($random_id);
                if ($image_array) {
                    $fields['image'] = $image_array;
                    error_log('DEBUG: Using fallback image, converted ID ' . $random_id . ' to array');
                }
            }
        }

        // Add ACF padding settings to every section
        // All sections get 'lg' bottom padding, except the last section which gets 'none'
        $isLastBlock = ($blockIndex === $totalBlocks - 1);
        $bottomPadding = $isLastBlock ? 'none' : 'lg';

        error_log('Block ' . $blockIndex . ' padding setting: ' . $bottomPadding . ' (isLast: ' . ($isLastBlock ? 'yes' : 'no') . ')');

        $fields['settings'] = [
            'padding' => [
                'bottom' => [
                    'desktop' => $bottomPadding,
                    'desktop_custom' => '',
                    'tablet' => $bottomPadding,
                    'tablet_custom' => '',
                    'mobile' => $bottomPadding,
                    'mobile_custom' => ''
                ]
            ]
        ];

        error_log('Block ' . $blockIndex . ' fields BEFORE image processing: ' . json_encode($fields));

        $blocks[] = [
            'blockName' => $block['block'],
            'attrs' => ['data' => $fields],
            'innerBlocks' => []
        ];
        
        error_log('Block ' . $blockIndex . ' - After adding to $blocks array');
        error_log('Block ' . $blockIndex . ' image field final check: ' . (isset($fields['image']) ? json_encode($fields['image']) : 'NOT SET'));
        if (isset($fields['image']) && is_array($fields['image'])) {
            error_log('Block ' . $blockIndex . ' image array has ID key? ' . (isset($fields['image']['ID']) ? 'YES: ' . $fields['image']['ID'] : 'NO'));
            error_log('Block ' . $blockIndex . ' image array has url key? ' . (isset($fields['image']['url']) ? 'YES' : 'NO'));
        }

        $blockIndex++;
    }

    error_log('DEBUG: About to serialize ' . count($blocks) . ' blocks');
    if (!empty($blocks) && isset($blocks[0]['attrs']['data']['image'])) {
        error_log('DEBUG: First block image data before serialization: ' . json_encode($blocks[0]['attrs']['data']['image']));
    }

    $serialized = serialize_blocks($blocks);
    
    error_log('DEBUG: Serialized blocks length: ' . strlen($serialized));
    
    return $serialized;
}