<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Retrieves all tables from the database.
 *
 * This function retrieves all tables from the WordPress database using $wpdb->get_results()
 * and caches the result for improved performance.
 *
 * @return array Array of table names.
 */
function get_all_tables()
{
    global $wpdb;

    // Generate a unique cache key
    $cache_key = DTM_PREFIX . 'all_tables';

    // Attempt to retrieve table names from the cache
    $table_names = wp_cache_get($cache_key, 'tables');

    // If table names are not found in the cache, fetch them from the database
    if ($table_names === false) {
        $tables = $wpdb->get_results("SHOW TABLES");
        $table_names = array();
        foreach ($tables as $table) {
            $table_names[] = $table->{'Tables_in_' . DB_NAME};
        }

        // Cache the table names for future use
        wp_cache_set($cache_key, $table_names, 'tables');
    }

    return $table_names;
}

/**
 * Render a select element with options for database tables.
 */
function render_table_select()
{
    $tables = get_all_tables();
    echo '<select name="table_name" id="table_name" required>';
    foreach ($tables as $table) {
        echo '<option value="' . esc_attr($table) . '">' . esc_html($table) . '</option>';
    }
    echo '</select>';
}

/**
 * Get the trigger name without the prefix.
 *
 * @param string $trigger_name The full trigger name.
 * @param string $table_name   The name of the table associated with the trigger.
 * @return string Trigger name without the prefix.
 */
function get_trigger_name_without_prefix($trigger_name, $table_name)
{
    $prefix_length = strlen(DTM_PREFIX . $table_name . '_');
    return substr($trigger_name, $prefix_length);
}

/**
 * Get the trigger name with the prefix.
 *
 * @param string $trigger_name The trigger name.
 * @param string $table_name   The name of the table associated with the trigger.
 * @return string Trigger name with the prefix.
 */
function get_trigger_name_with_prefix($trigger_name, $table_name)
{
    return DTM_PREFIX . $table_name . '_' . $trigger_name;
}


/**
 * Sanitizes a string to create a valid MySQL trigger name.
 *
 * This function removes any characters from the input string that are not alphanumeric,
 * underscore, or dollar sign, and ensures that the resulting string is a valid MySQL
 * identifier. If the sanitized string begins with a digit, an underscore is prepended
 * to ensure validity.
 *
 * @param string $string The input string to be sanitized.
 * @return string The sanitized trigger name.
 */
function sanitize_trigger_name($string)
{
    // Remove any non-alphanumeric characters except underscore and dollar sign
    $sanitized = preg_replace('/[^a-zA-Z0-9_$]/', '', $string);

    // If the string begins with a digit, prepend an underscore
    if (preg_match('/^\d/', $sanitized)) {
        $sanitized = '_' . $sanitized;
    }

    return $sanitized;
}


/**
 * Sanitizes a trigger name by removing invalid characters and ensuring it's a valid MySQL identifier.
 *
 * This function takes a user input trigger name, sanitizes it using the
 * sanitize_text_field() function to remove any potentially harmful characters,
 * and then further sanitizes it to create a valid MySQL trigger name using the
 * sanitize_trigger_name() function.
 *
 * @param string $trigger_name The user input trigger name to be sanitized.
 * @param string $table_name The name of the database table associated with the trigger.
 * @return string The sanitized trigger name with the prefix.
 */
function sanitize_and_prefix_trigger_name($trigger_name, $table_name)
{
    $sanitized_trigger_name = sanitize_trigger_name(sanitize_text_field($trigger_name));

    $sanitized_table_name = sanitize_text_field($table_name);

    $prefixed_sanitized_trigger_name = get_trigger_name_with_prefix($sanitized_trigger_name, $sanitized_table_name);

    return $prefixed_sanitized_trigger_name;
}
