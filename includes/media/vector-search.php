<?php

/**
 * Vector Search Integration with AWS Bedrock Knowledge Base
 * Provides semantic image search capabilities
 */

/**
 * Check if Vector DB is properly configured
 * 
 * @return bool True if all required settings are configured
 */
function scout_is_vector_db_configured() {
    $enabled = get_option('scout_vector_db_enabled', false);
    $kb_id = get_option('scout_bedrock_knowledge_base_id', '');
    $region = get_option('scout_bedrock_region', '');
    $model = get_option('scout_bedrock_model', '');
    
    return $enabled && !empty($kb_id) && !empty($region) && !empty($model);
}

/**
 * Generate embedding for text content using Bedrock
 * 
 * @param string $text The text to generate embedding for
 * @return array|null Embedding vector or null on error
 */
function scout_generate_bedrock_embedding($text) {
    try {
        $model = get_option('scout_bedrock_model', 'amazon.titan-embed-text-v1');
        $region = get_option('scout_bedrock_region', 'us-east-1');
        
        // Initialize Bedrock client
        require_once SCOUT_PATH . 'includes/ai/client.php';
        
        $client = scout_get_bedrock_client($region);
        if (!$client) {
            error_log('Scout: Failed to initialize Bedrock client for embedding');
            return null;
        }
        
        // Call Bedrock embeddings API
        $response = $client->invokeModel([
            'modelId' => $model,
            'contentType' => 'application/json',
            'body' => json_encode([
                'inputText' => substr($text, 0, 500)
            ])
        ]);
        
        $result = json_decode($response['body'], true);
        
        if (!isset($result['embedding'])) {
            error_log('Scout: Invalid embedding response from Bedrock');
            return null;
        }
        
        return $result['embedding'];
        
    } catch (Exception $e) {
        error_log('Scout: Bedrock embedding error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Query Bedrock Knowledge Base for semantically similar images
 * 
 * @param string $content The content to find similar images for
 * @param int $limit Maximum number of images to return
 * @return array Array of image data or empty array on error
 */
function scout_query_knowledge_base_for_images($content, $limit = 20) {
    try {
        // Generate embedding for the content
        $embedding = scout_generate_bedrock_embedding($content);
        if (!$embedding) {
            return [];
        }
        
        $kb_id = get_option('scout_bedrock_knowledge_base_id', '');
        $region = get_option('scout_bedrock_region', 'us-east-1');
        
        if (empty($kb_id)) {
            error_log('Scout: Knowledge Base ID not configured');
            return [];
        }
        
        error_log('Scout: Knowledge Base ID: ' . substr($kb_id, 0, 20) . '... (region: ' . $region . ')');
        
        // Use Bedrock Agents Runtime to query the knowledge base
        require_once SCOUT_PATH . 'includes/ai/client.php';
        
        $client = scout_get_bedrock_agents_client($region);
        if (!$client) {
            error_log('Scout: Failed to initialize Bedrock Agents client');
            return [];
        }
        
        $response = $client->retrieve([
            'knowledgeBaseId' => $kb_id,
            'retrievalConfiguration' => [
                'vectorSearchConfiguration' => [
                    'numberOfResults' => $limit,
                    'overrideSearchType' => 'SEMANTIC'
                ]
            ],
            'retrievalQuery' => [
                'text' => substr($content, 0, 300) // Log first 300 chars
            ]
        ]);
        
        $results = [];
        if (!empty($response['retrievalResults'])) {
            foreach ($response['retrievalResults'] as $result) {
                // Extract image metadata from retrieval results
                $metadata = $result['metadata'] ?? [];
                $image_id = $metadata['image_id'] ?? null;
                $score = $result['score'] ?? 0;
                
                if ($image_id) {
                    $results[] = [
                        'image_id' => intval($image_id),
                        'relevance_score' => $score,
                        'source' => $result['source'] ?? 'unknown'
                    ];
                }
            }
        }
        
        return $results;
        
    } catch (Exception $e) {
        error_log('Scout: Knowledge Base query error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get semantically relevant images from Knowledge Base query results
 * Falls back to random selection if Vector DB is not configured
 * 
 * @param string $content The content to find images for
 * @param int $limit Maximum number of images to return
 * @return array Array of image objects with id, url, alt_text, title, from_vector_db flag
 */
function scout_get_semantic_images($content, $limit = 20) {
    // Check if Vector DB is configured
    if (!scout_is_vector_db_configured()) {
        $images = scout_get_media_library_images($limit);
        // Mark images as NOT from Vector DB
        foreach ($images as &$img) {
            $img['from_vector_db'] = false;
        }
        return $images;
    }
    
    try {
        // Query knowledge base for relevant images
        $kb_results = scout_query_knowledge_base_for_images($content, $limit);
        
        if (empty($kb_results)) {
            $images = scout_get_media_library_images($limit);
            foreach ($images as &$img) {
                $img['from_vector_db'] = false;
            }
            return $images;
        }
        
        // Convert results to full image data
        $formatted_images = [];
        foreach ($kb_results as $result) {
            $image_id = $result['image_id'];
            $image_url = wp_get_attachment_url($image_id);
            
            if ($image_url) {
                $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                $title = get_the_title($image_id) ?: basename($image_url);
                
                $img_data = [
                    'id' => $image_id,
                    'url' => $image_url,
                    'alt_text' => $alt_text ?: $title,
                    'title' => $title,
                    'relevance_score' => $result['relevance_score'],
                    'from_vector_db' => true
                ];
                
                $formatted_images[] = $img_data;
            }
        }
        
        return $formatted_images;
        
    } catch (Exception $e) {
        error_log('Scout: Semantic image search error: ' . $e->getMessage());
        $images = scout_get_media_library_images($limit);
        foreach ($images as &$img) {
            $img['from_vector_db'] = false;
        }
        return $images;
    }
}
