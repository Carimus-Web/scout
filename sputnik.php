<?php
/**
 * Plugin Name: Sputnik
 * Description: AI-powered content draft generator for WordPress using Carimus Backbone theme blocks
 * Version: 1.0.0
 * Author: Carimus
 * Author URI: https://carimus.com
 * Plugin URI: https://github.com/carimus/sputnik
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sputnik
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

define('SPUTNIK_VERSION', '1.0.0');
define('SPUTNIK_PATH', plugin_dir_path(__FILE__));
define('SPUTNIK_URL', plugin_dir_url(__FILE__));

// Admin
require_once SPUTNIK_PATH . 'includes/admin/menu.php';
require_once SPUTNIK_PATH . 'includes/admin/assets.php';
require_once SPUTNIK_PATH . 'includes/admin/page.php';
require_once SPUTNIK_PATH . 'includes/admin/settings-config.php';
require_once SPUTNIK_PATH . 'includes/admin/settings-page.php';

// API
require_once SPUTNIK_PATH . 'includes/api/routes.php';