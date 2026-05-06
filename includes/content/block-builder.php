<?php

require_once SPUTNIK_PATH . 'includes/media/placeholder.php';

function sputnik_build_blocks($layout) {

    $blocks = [];
    $totalBlocks = count($layout);
    $blockIndex = 0;

    foreach ($layout as $block) {

        $fields = $block['fields'];

        // Handle image field
        // Claude returns image IDs - store the ID directly
        // Let ACF's get_field() handle the conversion to full image array
        if (isset($block['image'])) {
            error_log('DEBUG: Block ' . $blockIndex . ' image value: ' . json_encode($block['image']) . ' (type: ' . gettype($block['image']) . ')');
            
            if (is_numeric($block['image']) && $block['image'] > 0) {
                // Store just the attachment ID
                // ACF will automatically convert this to full image array when get_field() is called
                $fields['image'] = (int)$block['image'];
                error_log('DEBUG: Stored image ID: ' . $fields['image']);
            } elseif (!empty($block['image'])) {
                // Fallback: pick random image if image is just true
                $random_id = sputnik_get_placeholder_image();
                $fields['image'] = $random_id;
                error_log('DEBUG: Using fallback image ID: ' . $random_id);
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

        error_log('Block settings structure: ' . json_encode($fields['settings']));

        $blocks[] = [
            'blockName' => $block['block'],
            'attrs' => ['data' => $fields],
            'innerBlocks' => []
        ];

        $blockIndex++;
    }

    return serialize_blocks($blocks);
}