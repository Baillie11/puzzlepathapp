<?php
defined('ABSPATH') or die('No script kiddies please!');

// The admin menu for this page is now registered in the main plugin file.

/**
 * Display the main page for managing events.
 */
function puzzlepath_events_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pp_events';

    // Handle form submissions for adding/editing events
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['puzzlepath_event_nonce'])) {
        if (!wp_verify_nonce($_POST['puzzlepath_event_nonce'], 'puzzlepath_save_event')) {
            wp_die('Security check failed.');
        }

        $id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $title = sanitize_text_field($_POST['title']);
        $location = sanitize_text_field($_POST['location']);
        $price = floatval($_POST['price']);
        $seats = intval($_POST['seats']);
        $hosting_type = in_array($_POST['hosting_type'], ['hosted', 'self_hosted']) ? $_POST['hosting_type'] : 'hosted';
        $event_date = ($hosting_type === 'hosted' && !empty($_POST['event_date'])) ? sanitize_text_field($_POST['event_date']) : null;
        
        // New fields for hunt integration
        $hunt_code = !empty($_POST['hunt_code']) ? strtoupper(sanitize_text_field($_POST['hunt_code'])) : null;
        $hunt_name = !empty($_POST['hunt_name']) ? sanitize_text_field($_POST['hunt_name']) : null;

        $data = [
            'title' => $title,
            'location' => $location,
            'price' => $price,
            'seats' => $seats,
            'hosting_type' => $hosting_type,
            'event_date' => $event_date,
            'hunt_code' => $hunt_code,
            'hunt_name' => $hunt_name,
        ];

        if ($id > 0) {
            $wpdb->update($table_name, $data, ['id' => $id]);
        } else {
            $wpdb->insert($table_name, $data);
        }
        
        // Redirect to avoid form resubmission
        wp_redirect(admin_url('admin.php?page=puzzlepath-events&message=1'));
        exit;
    }

    // Handle event deletion
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['event_id'])) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'puzzlepath_delete_event_' . $_GET['event_id'])) {
            wp_die('Security check failed.');
        }
        $id = intval($_GET['event_id']);
        $wpdb->delete($table_name, ['id' => $id]);
        wp_redirect(admin_url('admin.php?page=puzzlepath-events&message=2'));
        exit;
    }

    $edit_event = null;
    if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['event_id'])) {
        $edit_event = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['event_id'])));
    }
    ?>
    <div class="wrap">
        <h1>Events</h1>

        <?php if (isset($_GET['message'])): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo $_GET['message'] == 1 ? 'Event saved successfully.' : 'Event deleted successfully.'; ?></p>
            </div>
        <?php endif; ?>

        <h2><?php echo $edit_event ? 'Edit Event' : 'Add New Event'; ?></h2>
        <form method="post" action="">
            <input type="hidden" name="event_id" value="<?php echo $edit_event ? esc_attr($edit_event->id) : ''; ?>">
            <?php wp_nonce_field('puzzlepath_save_event', 'puzzlepath_event_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="title">Title</label></th>
                    <td><input type="text" name="title" id="title" value="<?php echo $edit_event ? esc_attr($edit_event->title) : ''; ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="hosting_type">Hosting Type</label></th>
                    <td>
                        <select name="hosting_type" id="hosting_type">
                            <option value="hosted" <?php selected($edit_event ? $edit_event->hosting_type : '', 'hosted'); ?>>Hosted</option>
                            <option value="self_hosted" <?php selected($edit_event ? $edit_event->hosting_type : '', 'self_hosted'); ?>>Self Hosted (App)</option>
                        </select>
                    </td>
                </tr>
                <tr id="event_date_row">
                    <th scope="row"><label for="event_date">Event Date</label></th>
                    <td><input type="datetime-local" name="event_date" id="event_date" value="<?php echo $edit_event && $edit_event->event_date ? date('Y-m-d\TH:i', strtotime($edit_event->event_date)) : ''; ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="location">Location</label></th>
                    <td><input type="text" name="location" id="location" value="<?php echo $edit_event ? esc_attr($edit_event->location) : ''; ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="price">Price</label></th>
                    <td><input type="number" step="0.01" name="price" id="price" value="<?php echo $edit_event ? esc_attr($edit_event->price) : ''; ?>" class="small-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="seats">Seats</label></th>
                    <td><input type="number" name="seats" id="seats" value="<?php echo $edit_event ? esc_attr($edit_event->seats) : ''; ?>" class="small-text" required></td>
                </tr>
                
                <!-- NEW HUNT INTEGRATION FIELDS -->
                <tr class="hunt-fields" id="hunt_code_row">
                    <th scope="row"><label for="hunt_code">Hunt Code</label></th>
                    <td>
                        <input type="text" name="hunt_code" id="hunt_code" value="<?php echo $edit_event ? esc_attr($edit_event->hunt_code) : ''; ?>" class="small-text" maxlength="10" pattern="[A-Z]+" style="text-transform: uppercase;">
                        <p class="description">2-3 letter code (e.g., BB for Broadbeach, EP for Emerald Park). Will be used in booking codes.</p>
                    </td>
                </tr>
                <tr class="hunt-fields" id="hunt_name_row">
                    <th scope="row"><label for="hunt_name">Hunt Name</label></th>
                    <td>
                        <input type="text" name="hunt_name" id="hunt_name" value="<?php echo $edit_event ? esc_attr($edit_event->hunt_name) : ''; ?>" class="regular-text">
                        <p class="description">Full name of the quest (e.g., "Broadbeach Quest", "Emerald Lakes Explorer's Quest")</p>
                    </td>
                </tr>
            </table>
            
            <div class="hunt-integration-info" style="background: #f0f8ff; border: 1px solid #0073aa; border-radius: 5px; padding: 15px; margin: 20px 0;">
                <h3 style="margin-top: 0;">🎯 Hunt Integration</h3>
                <p><strong>For unified quest app integration:</strong></p>
                <ul>
                    <li>Add a <strong>Hunt Code</strong> (e.g., BB, EP, GC) to link this event to a specific quest</li>
                    <li>Booking codes will be generated as: <code>[HUNT_CODE]-[DATE]-[NUMBER]</code></li>
                    <li>Leave empty for events that don't use the quest app</li>
                </ul>
                
                <h4>Available Hunt Codes:</h4>
                <div style="background: white; padding: 10px; border-radius: 3px;">
                    <?php
                    // Show existing hunt codes
                    $existing_codes = $wpdb->get_results("SELECT DISTINCT hunt_code, hunt_name FROM $table_name WHERE hunt_code IS NOT NULL ORDER BY hunt_code");
                    if ($existing_codes) {
                        foreach ($existing_codes as $code) {
                            echo '<span style="background: #28a745; color: white; padding: 3px 8px; border-radius: 3px; margin: 2px; display: inline-block;">';
                            echo esc_html($code->hunt_code) . ': ' . esc_html($code->hunt_name);
                            echo '</span> ';
                        }
                    } else {
                        echo '<em>No hunt codes configured yet</em>';
                    }
                    ?>
                </div>
            </div>
            
            <?php submit_button($edit_event ? 'Update Event' : 'Add Event'); ?>
        </form>

        <hr/>
        
        <h2>All Events</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Hosting Type</th>
                    <th>Event Date</th>
                    <th>Location</th>
                    <th>Price</th>
                    <th>Seats Left</th>
                    <th>Hunt Code</th>
                    <th>Quest App</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $events = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
                foreach ($events as $event) {
                    echo '<tr>';
                    echo '<td>' . esc_html($event->title) . '</td>';
                    echo '<td>' . ($event->hosting_type === 'hosted' ? 'Hosted' : 'Self Hosted (App)') . '</td>';
                    echo '<td>' . ($event->event_date ? date('F j, Y, g:i a', strtotime($event->event_date)) : 'N/A') . '</td>';
                    echo '<td>' . esc_html($event->location) . '</td>';
                    echo '<td>$' . number_format($event->price, 2) . '</td>';
                    echo '<td>' . esc_html($event->seats) . '</td>';
                    echo '<td>';
                    if ($event->hunt_code) {
                        echo '<span style="background: #0073aa; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8em;">';
                        echo esc_html($event->hunt_code);
                        echo '</span>';
                    } else {
                        echo '<span style="color: #666;">N/A</span>';
                    }
                    echo '</td>';
                    echo '<td>';
                    if ($event->hunt_code && $event->hosting_type === 'self_hosted') {
                        $unified_app_url = get_option('puzzlepath_unified_app_url', '');
                        if ($unified_app_url) {
                            echo '<a href="' . esc_url($unified_app_url) . '" target="_blank" style="color: #28a745;">✓ Linked</a>';
                        } else {
                            echo '<span style="color: #d63638;">⚠ Setup Required</span>';
                        }
                    } else {
                        echo '<span style="color: #666;">-</span>';
                    }
                    echo '</td>';
                    echo '<td>';
                    echo '<a href="' . admin_url('admin.php?page=puzzlepath-events&action=edit&event_id=' . $event->id) . '">Edit</a> | ';
                    $delete_nonce = wp_create_nonce('puzzlepath_delete_event_' . $event->id);
                    echo '<a href="' . admin_url('admin.php?page=puzzlepath-events&action=delete&event_id=' . $event->id . '&_wpnonce=' . $delete_nonce) . '" onclick="return confirm(\'Are you sure you want to delete this event?\')">Delete</a>';
                    echo '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
        
        <!-- Quick Setup Guide -->
        <div style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-top: 20px;">
            <h3>🚀 Quick Setup Guide</h3>
            <p>To set up quest integration:</p>
            <ol>
                <li><strong>Create or Edit an Event</strong>: Add a Hunt Code (e.g., "BB" for Broadbeach)</li>
                <li><strong>Set Hosting Type</strong>: Choose "Self Hosted (App)" for quest events</li>
                <li><strong>Configure Unified App URL</strong>: Go to <a href="<?php echo admin_url('admin.php?page=puzzlepath-unified-settings'); ?>">Unified App Settings</a></li>
                <li><strong>Test Integration</strong>: Make a booking and check if the quest link appears</li>
            </ol>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        function toggleEventDate() {
            if ($('#hosting_type').val() === 'hosted') {
                $('#event_date_row').show();
            } else {
                $('#event_date_row').hide();
            }
        }
        
        function toggleHuntFields() {
            if ($('#hosting_type').val() === 'self_hosted') {
                $('.hunt-fields').show();
                $('.hunt-integration-info').show();
            } else {
                $('.hunt-fields').hide();
                $('.hunt-integration-info').hide();
            }
        }
        
        function initializeForm() {
            toggleEventDate();
            toggleHuntFields();
        }
        
        initializeForm();
        $('#hosting_type').on('change', function() {
            toggleEventDate();
            toggleHuntFields();
        });
        
        // Auto-uppercase hunt code
        $('#hunt_code').on('input', function() {
            this.value = this.value.toUpperCase();
        });
    });
    </script>
    
    <style>
    .hunt-fields {
        background: #f0f8ff;
    }
    .hunt-fields th {
        background: #e6f3ff;
    }
    .hunt-integration-info {
        display: none;
    }
    </style>
    <?php
}
