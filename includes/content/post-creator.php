<?php

require_once SPUTNIK_PATH . 'includes/content/block-builder.php';
require_once SPUTNIK_PATH . 'includes/media/placeholder.php';

function sputnik_create_post($postType, $layout) {

    $post_id = wp_insert_post([
        'post_type' => $postType,
        'post_status' => 'draft',
        'post_title' => 'AI Generated Page'
    ]);

    $content = sputnik_build_blocks($layout);

    wp_update_post([
        'ID' => $post_id,
        'post_content' => $content
    ]);

    return $post_id;
}