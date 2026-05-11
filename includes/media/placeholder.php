<?php

/**
 * Get a single random image from media library for fallback
 */
function scout_get_placeholder_image() {

    $images = get_posts([
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'orderby' => 'rand',
        'numberposts' => 1
    ]);

    return $images[0]->ID ?? null;
}

/**
 * Get up to N images from media library with full metadata
 * Returns array of images with ID, URL, alt text
 * 
 * @param int $limit Number of images to retrieve (default: 20)
 * @return array Array of image objects with id, url, alt_text, title
 */
function scout_get_media_library_images($limit = 20) {
    
    $images = get_posts([
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'numberposts' => $limit,
        'orderby' => 'date',
        'order' => 'DESC'
    ]);

    $formatted_images = [];

    foreach ($images as $image) {
        $image_url = wp_get_attachment_url($image->ID);
        $alt_text = get_post_meta($image->ID, '_wp_attachment_image_alt', true);
        $title = $image->post_title ?: basename($image_url);

        $formatted_images[] = [
            'id' => $image->ID,
            'url' => $image_url,
            'alt_text' => $alt_text ?: $title,
            'title' => $title
        ];
    }

    return $formatted_images;
}

/**
 * Convert an attachment ID to ACF image array format
 * This is what Carimus blocks expect from ACF
 * Includes full image metadata that ACF provides
 * 
 * @param int $attachment_id
 * @return array|null Array with ID, url, alt, width, height keys or null if invalid
 */
function scout_attachment_id_to_acf_image($attachment_id) {
    if (!$attachment_id || !is_numeric($attachment_id)) {
        return null;
    }
    
    $image_url = wp_get_attachment_url($attachment_id);
    if (!$image_url) {
        return null;
    }
    
    $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
    
    // Get image dimensions
    $image_meta = wp_get_attachment_metadata($attachment_id);
    $width = $image_meta['width'] ?? 0;
    $height = $image_meta['height'] ?? 0;
    
    // Build standard ACF image array
    // This matches what ACF normally returns for image fields
    $result = [
        'ID' => (int)$attachment_id,
        'id' => (int)$attachment_id,
        'url' => $image_url,
        'title' => get_the_title($attachment_id) ?: '',
        'alt' => $alt_text ?: '',
        'caption' => wp_get_attachment_caption($attachment_id) ?: '',
        'width' => $width,
        'height' => $height
    ];
    
    return $result;
}