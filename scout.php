<?php
/**
 * Plugin Name: Scout
 * Description: AI-powered content draft generator for WordPress using Carimus Backbone theme blocks
 * Version: 1.0.2
 * Author: Carimus
 * Author URI: https://carimus.com
 * Plugin URI: https://github.com/carimus/scout
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: scout
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

define('SCOUT_VERSION', '1.0.2');
define('SCOUT_PATH', plugin_dir_path(__FILE__));
define('SCOUT_URL', plugin_dir_url(__FILE__));

// Admin
require_once SCOUT_PATH . 'includes/admin/menu.php';
require_once SCOUT_PATH . 'includes/admin/styles.php';
require_once SCOUT_PATH . 'includes/admin/assets.php';
require_once SCOUT_PATH . 'includes/admin/page.php';
require_once SCOUT_PATH . 'includes/admin/settings-config.php';
require_once SCOUT_PATH . 'includes/admin/settings-page.php';
require_once SCOUT_PATH . 'includes/admin/update-checker.php';

// API
require_once SCOUT_PATH . 'includes/api/chat-controller.php';
require_once SCOUT_PATH . 'includes/api/routes.php';