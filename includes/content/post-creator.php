<?php

require_once SPUTNIK_PATH . 'includes/content/block-builder.php';
require_once SPUTNIK_PATH . 'includes/media/placeholder.php';

function sputnik_create_post($postType, $layout) {
    error_log('sputnik_create_post called with postType: ' . $postType);

    $post_id = wp_insert_post([
        'post_type' => $postType,
        'post_status' => 'draft',
        'post_title' => 'AI Generated Page'
    ]);

    // Check if post creation failed
    if (is_wp_error($post_id)) {
        error_log('wp_insert_post failed: ' . $post_id->get_error_message());
        return $post_id;
    }

    error_log('Post created with ID: ' . $post_id);

    $content = sputnik_build_blocks($layout);
    error_log('Blocks built, content length: ' . strlen($content));
    error_log('Full serialized blocks: ' . $content);

    $updated = wp_update_post([
        'ID' => $post_id,
        'post_content' => $content
    ]);

    if (is_wp_error($updated)) {
        error_log('wp_update_post failed: ' . $updated->get_error_message());
        return $updated;
    }

    error_log('Post content updated successfully');
    return $post_id;
}