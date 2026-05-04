<?php

function sputnik_validate_layout($layout, $allowed_blocks) {

    $allowed_map = [];

    foreach ($allowed_blocks as $b) {
        $allowed_map[$b['name']] = $b['fields'];
    }

    foreach ($layout as $block) {

        if (!isset($allowed_map[$block['block']])) {
            return false;
        }

        foreach ($block['fields'] as $key => $val) {
            if (!in_array($key, $allowed_map[$block['block']])) {
                return false;
            }
        }
    }

    return true;
}