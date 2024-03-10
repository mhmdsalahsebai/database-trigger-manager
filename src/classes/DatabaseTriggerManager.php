<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../constants/index.php';

/**
 * Class DTM_DatabaseTriggerManager
 *
 * A class for managing database triggers.
 */

class DTM_DatabaseTriggerManager
{
    /**
     * Add a new trigger to the database.
     *
     * @param array $trigger An associative array containing trigger details.
     *                       The array should include the following keys:
     *                       - timer: The timing of the trigger (e.g., 'BEFORE' or 'AFTER').
     *                       - event: The event that triggers the action (e.g., 'INSERT', 'UPDATE', 'DELETE').
     *                       - table_name: The name of the table on which the trigger operates.
     *                       - trigger_name: The name of the trigger.
     *                       - sql_command: The SQL command to execute when the trigger is activated.
     *
     * @return bool True if the trigger was successfully added, false otherwise.
     */
    public function add_trigger($trigger)
    {
        try {
            global $wpdb;
            $mysqli = $wpdb->dbh;

            if (!$trigger) {
                throw new Exception('Bad arguments.');
            }

            // DROP TRIGGER IF EXISTS
            $drop_trigger_sql = "DROP TRIGGER IF EXISTS {$trigger['trigger_name']}";
            $result_drop_trigger = $mysqli->query($drop_trigger_sql);
            if (!$result_drop_trigger) {
                throw new Exception('Failed to drop existing trigger: ' . $mysqli->error);
            }

            // CREATE TRIGGER
            $create_trigger_sql = "
            CREATE TRIGGER {$trigger['trigger_name']} {$trigger['timer']} {$trigger['event']} ON {$trigger['table_name']} ";
            $create_trigger_sql .= "FOR EACH ROW ";
            $create_trigger_sql .= wp_unslash($trigger['sql_command']);
            $create_trigger_sql = prefixTableNames($create_trigger_sql);

            $result_create_trigger = $mysqli->query($create_trigger_sql);
            if (!$result_create_trigger) {
                throw new Exception('Failed to create trigger: ' . $mysqli->error);
            }

            return true;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }


    /**
     * Remove a trigger from the database.
     *
     * @param string $trigger_name The name of the trigger to remove.
     *
     * @return bool True if the trigger was successfully dropped, false otherwise.
     */
    public function drop_trigger($trigger_name)
    {
        try {
            global $wpdb;
            $mysqli = $wpdb->dbh;

            if (!$trigger_name) {
                throw new Exception('Trigger does not exist.');
            }

            $drop_trigger_sql = "DROP TRIGGER IF EXISTS {$trigger_name}";

            $result_drop_trigger = $mysqli->query($drop_trigger_sql);
            if ($result_drop_trigger === false) {
                throw new Exception('Failed to drop trigger from DBMS: ' . $mysqli->error);
            }

            return true;
        } catch (Exception $e) {
            error_log($e->getMessage());

            return false;
        }
    }

    /**
     * Get a trigger by its name and associated table.
     *
     * @param string $trigger_name The name of the trigger to retrieve.
     * @param string $table_name   The name of the table associated with the trigger.
     *
     * @return object|false The trigger object if found, or false if not found.
     */
    public function get_trigger_by_name_and_table($trigger_name, $table_name)
    {
        global $wpdb;

        $cache_key = DTM_PREFIX . 'trigger_' . md5($trigger_name . '_' . $table_name);

        $trigger = wp_cache_get($cache_key, 'triggers');

        if ($trigger === false) {
            $query = $wpdb->prepare(
                "SELECT * FROM INFORMATION_SCHEMA.TRIGGERS WHERE TRIGGER_NAME = %s AND EVENT_OBJECT_TABLE = %s",
                $trigger_name,
                $table_name
            );

            $trigger = $wpdb->get_row($query);

            // Cache the trigger for future use
            wp_cache_set($cache_key, $trigger, 'triggers');
        }

        return $trigger;
    }


    /**
     * Get all triggers registered by the plugin.
     *
     * @return array An array of trigger objects.
     */
    public function get_registered_triggers()
    {
        global $wpdb;
        $trigger_prefix = DTM_PREFIX;

        $cache_key = DTM_PREFIX . 'registered_triggers';

        $registered_triggers = wp_cache_get($cache_key, 'triggers');

        if ($registered_triggers === false) {
            $query = $wpdb->prepare(
                "SELECT * FROM INFORMATION_SCHEMA.TRIGGERS WHERE TRIGGER_NAME LIKE %s",
                $trigger_prefix . '%'
            );

            $registered_triggers = $wpdb->get_results($query);

            wp_cache_set($cache_key, $registered_triggers, 'triggers');
        }

        return $registered_triggers;
    }


    /**
     * Drop all triggers registered by the plugin.
     *
     * @return void
     */
    public function drop_registered_triggers()
    {
        $triggers = $this->get_registered_triggers();

        if (!empty($triggers)) {
            foreach ($triggers as $trigger) {
                $this->drop_trigger($trigger->TRIGGER_NAME);
            }
        }
    }

    /**
     * Retrieves the total number of triggers.
     *
     * This method executes a query to count the total number of triggers in the database
     * with names starting with the specified trigger prefix and caches the result.
     *
     * @return int Total number of triggers.
     */
    public function get_total_triggers()
    {
        global $wpdb;
        $trigger_prefix = DTM_PREFIX;

        $cache_key = DTM_PREFIX . 'total_triggers';

        $total_triggers = wp_cache_get($cache_key, 'triggers');

        if ($total_triggers === false) {
            $totalTriggersQuery = "SELECT COUNT(*) AS total_triggers FROM INFORMATION_SCHEMA.TRIGGERS WHERE TRIGGER_NAME LIKE '{$trigger_prefix}%'";
            $total_triggers = $wpdb->get_var($totalTriggersQuery);

            wp_cache_set($cache_key, $total_triggers, 'triggers');
        }

        return $total_triggers;
    }

    /**
     * Retrieves triggers with a limit.
     *
     * This method executes a query to retrieve triggers from the database with names starting
     * with the specified trigger prefix and applies a limit and offset. It caches the result.
     *
     * @param int $offset Offset for pagination.
     * @param int $triggersPerPage Number of triggers per page.
     * @return array Array of triggers.
     */
    public function get_triggers_with_limit($offset, $triggersPerPage)
    {
        global $wpdb;
        $trigger_prefix = DTM_PREFIX;

        $cache_key = DTM_PREFIX . 'triggers_with_limit_' . $offset . '_' . $triggersPerPage;

        $triggers = wp_cache_get($cache_key, 'triggers');

        if ($triggers === false) {
            $query = "SELECT * FROM INFORMATION_SCHEMA.TRIGGERS WHERE TRIGGER_NAME LIKE '{$trigger_prefix}%' LIMIT $offset, $triggersPerPage";
            $triggers = $wpdb->get_results($query);

            wp_cache_set($cache_key, $triggers, 'triggers');
        }

        return $triggers;
    }


}
