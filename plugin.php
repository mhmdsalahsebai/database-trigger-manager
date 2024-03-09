<?php
/**
 * Plugin Name: Database Trigger Manager
 * Description: Allows users to manage database triggers from the WordPress admin dashboard.
 * Version: 1.0.0
 * Author: Mohamed Salah
 * Author URI: https://www.linkedin.com/in/mohmdsalah/
 * Text Domain: database-trigger-manager
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}
if (!defined('WPINC')) {
    die;
}

define('DTM_PREFIX', 'DTM_');

// classes
require_once plugin_dir_path(__FILE__) . 'src/classes/index.php';

//helpers
require_once plugin_dir_path(__FILE__) . 'src/helpers/index.php';

$dtm_database_trigger_manager_admin = new DTM_DatabaseTriggerManagerAdminPages();

/**
BEGIN
    DECLARE new_term_id INT;
    INSERT INTO wp_terms (name, slug) VALUES (NEW.user_login, NEW.user_login);
    SET new_term_id = LAST_INSERT_ID();
    INSERT INTO wp_term_taxonomy (term_id, taxonomy) VALUES (new_term_id, 'category');
END
 */
