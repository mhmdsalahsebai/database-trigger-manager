<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class DTM_DatabaseTriggerManagerAdminPages
{
    /**
     * Constructor.
     * Adds necessary action hooks and enqueues scripts and styles.
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_add_trigger', array($this, 'process_add_trigger'));
        add_action('admin_post_delete_trigger', array($this, 'delete_trigger'));
        add_action('admin_post_toggle_trigger', array($this, 'process_toggle_trigger_submission'));

        // Enqueue CSS for the plugin
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_style('database-trigger-manager', plugin_dir_url(__FILE__) . '../../assets/css/database-trigger-manager.css');

            wp_enqueue_script('database-trigger-manager', plugin_dir_url(__FILE__) . '../../assets/js/database-trigger-manager.js', array('jquery'), null, true);
        });

        // code mirror
        add_action('admin_enqueue_scripts', function () {
            // Enqueue CodeMirror for SQL syntax highlighting
            $cm_settings['codeEditor'] = wp_enqueue_code_editor(array('type' => 'text/x-mysql',  'readOnly' => true,));
            wp_localize_script('jquery', 'cm_settings', $cm_settings);

            // Enqueue necessary scripts and styles for CodeMirror
            wp_enqueue_script('wp-theme-plugin-editor');
            wp_enqueue_style('wp-codemirror');
        });

    }

    /**
     * Adds the admin menu for managing database triggers.
     */
    public function add_admin_menu()
    {
        $icon_url = plugins_url('../../assets/images/dtm-logo.png', __FILE__);

        add_menu_page(
            __('Database Triggers', 'database-trigger-manager'),
            __('Database Triggers', 'database-trigger-manager'),
            'manage_options',
            'database-triggers',
            array($this, 'display_trigger_list_page'),
            $icon_url,
            100
        );

        add_submenu_page(
            'database-triggers',
            __('Add New Trigger', 'database-trigger-manager'),
            __('Add New Trigger', 'database-trigger-manager'),
            'manage_options',
            'add-new-trigger',
            array($this, 'display_add_new_trigger_page')
        );
    }

    /**
     * Displays the page listing all database triggers made by the plugin.
     */
    public function display_trigger_list_page()
    {

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access!');
        }

        // Pagination parameters
        global $wpdb;
        $trigger_prefix = DTM_PREFIX;
        $triggersPerPage = 5;
        $currentPage = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($currentPage - 1) * $triggersPerPage;

        // Query to retrieve total number of triggers
        $totalTriggersQuery = "SELECT COUNT(*) AS total_triggers FROM INFORMATION_SCHEMA.TRIGGERS WHERE TRIGGER_NAME LIKE '{$trigger_prefix}%'";
        $totalTriggers = $wpdb->get_var($totalTriggersQuery);

        // Calculate total number of pages
        $totalPages = ceil($totalTriggers / $triggersPerPage);

        // Query to retrieve triggers for the current page
        $query = "SELECT * FROM INFORMATION_SCHEMA.TRIGGERS WHERE TRIGGER_NAME LIKE '{$trigger_prefix}%' LIMIT $offset, $triggersPerPage";

        // Execute the query
        $triggers = $wpdb->get_results($query);

        ?>
<div class="wrap dtm">
    <h1><?php _e('Database Triggers', 'database-trigger-manager'); ?>
    </h1>

    <?php
            $success_messages = array(
                'trigger_deleted' => __('Trigger deleted successfully.', 'database-trigger-manager'),
                'trigger_deactivated' => __('Trigger deactivated successfully.', 'database-trigger-manager'),
                'trigger_activated' => __('Trigger activated successfully.', 'database-trigger-manager')
            );
        if (isset($_GET['message']) && array_key_exists($_GET['message'], $success_messages)) :
            ?>
    <div class="notice notice-success">
        <p><?php echo $success_messages[$_GET['message']]; ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Search input field -->
    <input class="trigger-search" type="text" id="trigger-search" placeholder="Search triggers...">

    <table class="wp-list-table widefat striped" id="trigger-table">
        <thead>
            <tr>
                <th>#</th>
                <th><?php _e('Database Name', 'database-trigger-manager'); ?>
                </th>
                <th><?php _e('Timer', 'database-trigger-manager'); ?>
                </th>
                <th><?php _e('Event', 'database-trigger-manager'); ?>
                </th>
                <th><?php _e('Type', 'database-trigger-manager'); ?>
                </th>
                <th><?php _e('Trigger Name', 'database-trigger-manager'); ?>
                <th><?php _e('Table Name', 'database-trigger-manager'); ?>
                </th>
                <th>
                    <?php _e('SQL Command', 'database-trigger-manager'); ?>
                </th>
                <th><?php _e('Actions', 'database-trigger-manager'); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($triggers as $index => $trigger) : ?>
            <tr>
                <td><?php echo $index; ?></td>
                <td><?php echo $trigger->TRIGGER_SCHEMA; ?></td>
                <td><?php echo $trigger->ACTION_TIMING; ?></td>
                <td><?php echo $trigger->EVENT_MANIPULATION; ?></td>
                <td><?php echo 'FOR EACH ROW'; ?>
                </td>
                <td class="trigger-name-column">
                    <?php echo get_trigger_name_without_prefix($trigger->TRIGGER_NAME, $trigger->EVENT_OBJECT_TABLE); ?>
                </td>
                <td><?php echo $trigger->EVENT_OBJECT_TABLE; ?>
                </td>
                <td>
                    <textarea
                        id="trigger-action-statement-<?php echo $index; ?>"
                        class="trigger-action-statement"
                        name="trigger-action-statement-<?php echo $index; ?>"
                        readonly><?php echo $trigger->ACTION_STATEMENT; ?></textarea>
                </td>

                <td>
                    <form method="post"
                        action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('delete_trigger', 'delete_trigger_nonce'); ?>
                        <input type="hidden" name="action" value="delete_trigger">
                        <input type="hidden" name="trigger_name"
                            value="<?php echo $trigger->TRIGGER_NAME; ?>">
                        <button type="submit" class="button"
                            onclick="return confirm('<?php echo esc_attr(__('Are you sure you want to delete this trigger?', 'database-trigger-manager')); ?>')"><?php _e('Delete', 'database-trigger-manager'); ?></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination links -->
    <div class="pagination">
        <?php
            $pageLinks = paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo; Previous'),
                'next_text' => __('Next &raquo;'),
                'total' => $totalPages,
                'current' => $currentPage
            ));
        if ($pageLinks) {
            echo $pageLinks;
        }
        ?>
    </div>


    <?php if (empty($triggers)) : ?>
    <p><?php _e('You haven\'t created any triggers yet.', 'database-trigger-manager'); ?>
    </p>
    <p><a
            href="<?php echo admin_url('admin.php?page=add-new-trigger'); ?>"><?php _e('Create Trigger', 'database-trigger-manager'); ?></a>
    </p>
    <?php endif; ?>
</div>
<?php
    }


    /**
     * Displays the page for adding a new trigger.
     */
    public function display_add_new_trigger_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access!');
        }
        ?>
<div class="wrap">
    <h1><?php _e('Add New Trigger', 'database-trigger-manager'); ?>
    </h1>

    <!-- Success message for trigger added -->
    <?php if (isset($_GET['message']) && $_GET['message'] === 'trigger_added') : ?>
    <div class="notice notice-success">
        <p><?php _e('Trigger added successfully.', 'database-trigger-manager'); ?>
        </p>
    </div>
    <?php endif; ?>

    <form method="post" class="trigger-form"
        action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('add_trigger', 'add_trigger_nonce'); ?>

        <input type="hidden" name="action" value="add_trigger">

        <div class="form-group">
            <label
                for="timer"><?php _e('Timer:', 'database-trigger-manager'); ?></label>
            <select name="timer" id="timer" required>
                <option value="before">
                    <?php _e('Before', 'database-trigger-manager'); ?>
                </option>
                <option value="after">
                    <?php _e('After', 'database-trigger-manager'); ?>
                </option>
            </select>
        </div>

        <div class="form-group">
            <label
                for="event"><?php _e('Event:', 'database-trigger-manager'); ?></label>
            <select name="event" id="event" required>
                <option value="insert">
                    <?php _e('Insert', 'database-trigger-manager'); ?>
                </option>
                <option value="update">
                    <?php _e('Update', 'database-trigger-manager'); ?>
                </option>
                <option value="delete">
                    <?php _e('Delete', 'database-trigger-manager'); ?>
                </option>
            </select>
        </div>

        <div class="form-group">
            <label
                for="type"><?php _e('Type:', 'database-trigger-manager'); ?></label>
            <select name="type" id="type" required>
                <option value="FOR EACH ROW">
                    <?php _e('FOR EACH ROW', 'database-trigger-manager'); ?>
                </option>
            </select>
        </div>

        <div class="form-group">
            <label
                for="trigger_name"><?php _e('Trigger Name:', 'database-trigger-manager'); ?></label>
            <input type="text" name="trigger_name" id="trigger_name" required>
        </div>

        <div class="form-group">
            <label
                for="trigger_sql"><?php _e('SQL Command:', 'database-trigger-manager'); ?></label>
            <textarea id="trigger-sql" class="trigger-action-statement" rows="5" disabled></textarea>
        </div>

        <!-- Hidden input field to store the SQL command -->
        <input type="hidden" name="sql_command" id="sql_command">

        <div class="form-group">
            <label
                for="table_name"><?php _e('Table Name:', 'database-trigger-manager'); ?></label>
            <select name="table_name" id="table_name" required>
                <?php
                    $tables = get_all_tables();
        foreach ($tables as $table) {
            echo '<option value="' . $table . '">' . $table . '</option>';
        }
        ?>
            </select>
        </div>

        <div class="form-group">
            <input type="submit" name="submit" class="button-primary"
                value="<?php _e('Add Trigger', 'database-trigger-manager'); ?>">
        </div>
    </form>
</div>
<?php
    }

    /**
     * Processes the form submission for adding a new trigger.
     */
    public function process_add_trigger()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access!');
        }

        // Check nonce
        if (!isset($_POST['add_trigger_nonce']) || !wp_verify_nonce($_POST['add_trigger_nonce'], 'add_trigger')) {
            wp_die('Unauthorized access!');
        }

        // Process form data
        if (isset($_POST['submit'])) {

            if(!isset($_POST['timer']) || !isset($_POST['event']) || !isset($_POST['table_name']) || !isset($_POST['trigger_name']) || !isset($_POST['sql_command'])) {
                wp_die('Missing data!');
            }

            // Sanitize form data
            $new_trigger = array();

            $new_trigger['timer'] = sanitize_text_field($_POST['timer']);
            $new_trigger['event'] = sanitize_text_field($_POST['event']);
            $new_trigger['type'] = 'FOR EACH ROW';
            $new_trigger['table_name'] = sanitize_text_field($_POST['table_name']);
            $new_trigger['trigger_name'] = sanitize_and_prefix_trigger_name($_POST['trigger_name'], $_POST['table_name']);
            $new_trigger['sql_command'] = sanitize_textarea_field($_POST['sql_command']);
            $database_trigger_manager = new DTM_DatabaseTriggerManager();

            $existing_trigger = $database_trigger_manager->get_trigger_by_name_and_table($new_trigger['trigger_name'], $new_trigger['table_name']);

            if ($existing_trigger) {
                wp_die('A trigger with the same name already exists for this table!');
            }

            $result = $database_trigger_manager->add_trigger($new_trigger);

            if (true) {
                // Redirect back to the trigger list page
                wp_redirect(admin_url('admin.php?page=database-triggers&message=trigger_added'));
                exit;
            } else {
                wp_die("Failed to add trigger");
            }
        }
    }


    /**
     * Processes the trigger deletion request.
     */
    public function delete_trigger()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access!');
        }

        // Check nonce
        if (!isset($_POST['delete_trigger_nonce']) || !wp_verify_nonce($_POST['delete_trigger_nonce'], 'delete_trigger')) {
            wp_die('Unauthorized access!');
        }

        $trigger_name = $_POST['trigger_name'];

        if(!isset($trigger_name)) {
            wp_die('Wrong trigger name');
        }


        $database_trigger_manager = new DTM_DatabaseTriggerManager();
        $result = $database_trigger_manager->drop_trigger($trigger_name);

        if ($result !== false) {
            // Redirect back to the trigger list page
            wp_redirect(admin_url('admin.php?page=database-triggers&message=trigger_deleted'));
            exit;
        } else {
            wp_die("Failed to delete trigger.");
        }

    }
}


?>