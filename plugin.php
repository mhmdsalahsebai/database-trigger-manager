<?php
/**
 * Plugin Name: Database Trigger Manager
 * Description: Allows users to manage database triggers from the WordPress admin dashboard.
 * Version: 1.0.0
 * Author: Mohamed Salah
 * Author URI: https://www.linkedin.com/in/mohmdsalah/
 * Text Domain: database-trigger-manager
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WPINC')) {
    die;
}

// constants
require_once plugin_dir_path(__FILE__) . 'src/constants/index.php';

// classes
require_once plugin_dir_path(__FILE__) . 'src/classes/index.php';

//helpers
require_once plugin_dir_path(__FILE__) . 'src/helpers/index.php';

$dtm_database_trigger_manager_admin = new DTM_DatabaseTriggerManagerAdminPages();
