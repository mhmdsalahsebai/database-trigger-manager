<?php

// If uninstall is not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

require_once(plugin_dir_path(__FILE__) . 'src/classes/database-trigger-manager.php');

$trigger_manager = new DTM_DatabaseTriggerManager();

// Drop all registered triggers
$trigger_manager->drop_registered_triggers();
