<?php

function sputnik_build_blocks($layout) {

    $blocks = [];
    $totalBlocks = count($layout);
    $blockIndex = 0;

    foreach ($layout as $block) {

        $fields = $block['fields'];

        if (!empty($block['image'])) {
            $fields['image'] = sputnik_get_placeholder_image();
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