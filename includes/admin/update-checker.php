<?php

/**
 * Simple GitHub-based plugin update checker for Scout
 * Checks the GitHub releases API for new versions
 */

function scout_check_for_updates() {
    $github_repo = 'carimus/scout';
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
 * Register the update with WordPress
 */
function scout_register_plugin_update($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }
    
    $plugin_file = plugin_basename(SCOUT_PATH . '../scout.php');
    
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

add_filter('site_transient_update_plugins', 'scout_register_plugin_update');
