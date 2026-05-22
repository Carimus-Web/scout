<?php

/**
 * Simple GitHub-based plugin update checker for Scout
 * Checks the GitHub releases API for new versions
 */

function scout_check_for_updates() {
    $github_repo = 'Carimus-Web/scout';
    $current_version = SCOUT_VERSION;
    
    // Check GitHub API for latest release
    $response = wp_remote_get("https://api.github.com/repos/{$github_repo}/releases/latest", [
        'timeout' => 10,
        'sslverify' => true
    ]);
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $release = json_decode(wp_remote_retrieve_body($response), true);
    
    if (!$release || empty($release['tag_name'])) {
        return false;
    }
    
    $remote_version = ltrim($release['tag_name'], 'v');
    
    // Compare versions
    if (version_compare($remote_version, $current_version, '>')) {
        // New version available
        $update = new stdClass();
        $update->slug = 'scout';
        $update->new_version = $remote_version;
        $update->url = $release['html_url'];
        $update->package = $release['zipball_url'];
        $update->tested = '6.5';
        $update->requires = '6.0';
        $update->requires_php = '7.4';
        
        return $update;
    }
    
    return false;
}

/**
 * Debug function to check update status
 * Returns version comparison info for debugging
 */
function scout_debug_update_status() {
    $github_repo = 'Carimus-Web/scout';
    $current_version = SCOUT_VERSION;
    
    // Get latest release info
    $response = wp_remote_get("https://api.github.com/repos/{$github_repo}/releases/latest", [
        'timeout' => 10,
        'sslverify' => true
    ]);
    
    if (is_wp_error($response)) {
        return ['error' => 'Failed to fetch from GitHub: ' . $response->get_error_message()];
    }
    
    $release = json_decode(wp_remote_retrieve_body($response), true);
    
    if (!$release) {
        return ['error' => 'Invalid GitHub API response'];
    }
    
    $remote_version = ltrim($release['tag_name'] ?? '', 'v');
    
    return [
        'current_version' => $current_version,
        'remote_version' => $remote_version,
        'remote_tag' => $release['tag_name'] ?? 'not found',
        'release_url' => $release['html_url'] ?? 'not found',
        'is_prerelease' => $release['prerelease'] ?? false,
        'is_draft' => $release['draft'] ?? false,
        'version_comparison' => version_compare($remote_version, $current_version, '>') ? 'Update available' : 'No update needed',
        'transient_cache' => get_transient('scout_update_check'),
    ];
}

/**
 * Register the update with WordPress
 */
function scout_register_plugin_update($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }
    
    // Correct plugin file path: scout/scout.php
    $plugin_file = 'scout/scout.php';
    
    // Only check if Scout is in the checked list
    if (!isset($transient->checked[$plugin_file])) {
        return $transient;
    }
    
    // Check for updates (use transient to cache for 12 hours)
    $cache_key = 'scout_update_check';
    $update = get_transient($cache_key);
    
    if (false === $update) {
        $update = scout_check_for_updates();
        set_transient($cache_key, $update ?: 'no-update', 12 * HOUR_IN_SECONDS);
    }
    
    if ($update && $update !== 'no-update') {
        $transient->response[$plugin_file] = $update;
    }
    
    return $transient;
}

/**
 * Rename the extracted plugin folder to match the plugin slug
 * GitHub zipball extracts as "Carimus-Web-scout-<hash>" but WordPress expects "scout"
 */
function scout_upgrade_source_selection($source, $remote_source, $upgrader) {
    global $wp_filesystem;
    
    // Check if this is a Scout plugin update
    if (isset($upgrader->skin->plugin)) {
        if (strpos($upgrader->skin->plugin, 'scout') === false) {
            return $source;
        }
    }
    
    // If source is already named "scout", no need to rename
    if (basename($source) === 'scout') {
        return $source;
    }
    
    // Rename the folder to "scout"
    $new_source = trailingslashit($remote_source) . 'scout';
    
    if ($wp_filesystem->move($source, $new_source)) {
        return $new_source;
    }
    
    return $source;
}

add_filter('upgrader_source_selection', 'scout_upgrade_source_selection', 10, 3);
add_filter('site_transient_update_plugins', 'scout_register_plugin_update');