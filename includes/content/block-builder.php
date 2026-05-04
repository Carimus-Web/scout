<?php

function sputnik_build_blocks($layout) {

    $blocks = [];

    foreach ($layout as $block) {

        $fields = $block['fields'];

        if (!empty($block['image'])) {
            $fields['image'] = sputnik_get_placeholder_image();
        }

        $blocks[] = [
            'blockName' => $block['block'],
            'attrs' => ['data' => $fields],
            'innerBlocks' => []
        ];
    }

    return serialize_blocks($blocks);
}