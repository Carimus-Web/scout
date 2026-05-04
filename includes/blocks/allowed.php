<?php

function sputnik_get_allowed_blocks($postType) {

    return [
        [
            'name' => 'acf/hero',
            'fields' => ['headline', 'copy', 'image']
        ],
        [
            'name' => 'acf/features',
            'fields' => ['items']
        ],
        [
            'name' => 'acf/cta',
            'fields' => ['headline', 'button_text']
        ]
    ];
}