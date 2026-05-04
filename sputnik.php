<?php
/**
 * Plugin Name: Sputnik
 */

define('SPUTNIK_PATH', plugin_dir_path(__FILE__));
define('SPUTNIK_URL', plugin_dir_url(__FILE__));

// Admin
require_once SPUTNIK_PATH . 'includes/admin/menu.php';
require_once SPUTNIK_PATH . 'includes/admin/assets.php';
require_once SPUTNIK_PATH . 'includes/admin/page.php';

// API
require_once SPUTNIK_PATH . 'includes/api/routes.php';