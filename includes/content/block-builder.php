<?php

require_once SCOUT_PATH . 'includes/media/placeholder.php';

function scout_build_blocks($layout) {

    $blocks = [];
    $totalBlocks = count($layout);
    $blockIndex = 0;

    foreach ($layout as $block) {

        $fields = $block['fields'];

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

        // Add ACF padding settings to every section
        // The "settings" field is a clone of another field group
        // We need to provide the cloned field's structure exactly
        
        $fields['settings'] = [
            'padding' => [
                'top' => [
                    'desktop' => 'none',
                    'desktop_custom' => '',
                    'tablet' => 'none',
                    'tablet_custom' => '',
                    'mobile' => 'none',
                    'mobile_custom' => ''
                ],
                'bottom' => [
                    'desktop' => 'lg',
                    'desktop_custom' => '',
                    'tablet' => 'lg',
                    'tablet_custom' => '',
                    'mobile' => 'lg',
                    'mobile_custom' => ''
                ]
            ]
        ];

        $blocks[] = [
            'blockName' => $block['block'],
            'attrs' => ['data' => $fields],
            'innerBlocks' => []
        ];

        $blockIndex++;
    }

    $serialized = serialize_blocks($blocks);
    return $serialized;
}