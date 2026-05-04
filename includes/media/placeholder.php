<?php

function sputnik_get_placeholder_image() {

    $images = get_posts([
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'orderby' => 'rand',
        'numberposts' => 1
    ]);

    return $images[0]->ID ?? null;
}