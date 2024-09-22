<?php
/*
Plugin Name: Shift Assignment Plugin
Description: A plugin to assign shifts to jobsearch candidates.
Version: 1.1
Author: Asad Irshad
*/

// Add the shortcode for displaying assigned shifts
function display_assigned_shifts() {
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        return 'Please log in to see your assigned shifts.';
    }

    // Fetch assigned shifts from the database
    global $wpdb;
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}assigned_shifts WHERE candidate_id = %d", 
        $user_id
    ));

    if (empty($results)) {
        return 'No shift assigned.';
    }

    $output = '<h3>Your Assigned Shifts</h3><table><tr><th>Shift Name</th><th>Date</th><th>Time</th><th>Total Hours</th><th>Hourly Rate</th><th>Total Payout</th><th>Currency</th></tr>';
    
    foreach ($results as $shift) {
        $output .= "<tr><td>{$shift->shift_name}</td><td>{$shift->shift_date}</td><td>{$shift->shift_time}</td><td>{$shift->total_hours}</td><td>{$shift->hourly_rate}</td><td>{$shift->total_payout}</td><td>{$shift->currency}</td></tr>";
    }
    $output .= '</table>';

    return $output;
}
add_shortcode('assigned_shifts', 'display_assigned_shifts');

// Add the admin menu for shift assignment
function shift_assignment_menu() {
    add_menu_page(
        'Shift Assignment',      // Page title
        'Shift Assignment',      // Menu title
        'manage_options',        // Capability required to view the page
        'shift-assignment',      // Menu slug
        'shift_assignment_page', // Function to display the page content
        'dashicons-calendar',    // Optional icon for the menu
        6                        // Position in the menu
    );
}
add_action('admin_menu', 'shift_assignment_menu');

// Admin page callback function
function shift_assignment_page() {
    ?>
    <h2>Assign Shifts to Candidates</h2>
    <form method="post" action="">
        <label for="candidate">Select Candidate:</label>
        <select name="candidate" id="candidate">
            <?php
            $users = get_users(array('role' => 'jobsearch_candidate'));
            foreach ($users as $user) {
                echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
            }
            ?>
        </select><br>

        <label for="shift_name">Shift Name:</label>
        <input type="text" name="shift_name" id="shift_name" required><br>

        <label for="shift_date">Shift Date:</label>
        <input type="date" name="shift_date" id="shift_date" required><br>

        <label for="shift_time">Shift Time:</label>
        <input type="time" name="shift_time" id="shift_time" required><br>

        <label for="hourly_rate">Hourly Rate:</label>
        <input type="number" name="hourly_rate" id="hourly_rate" step="0.01" required><br>

        <label for="currency">Currency:</label>
        <select name="currency" id="currency">
            <option value="USD">$ (Dollar)</option>
            <option value="GBP">£ (Pound)</option>
        </select><br>

        <input type="submit" name="assign_shift" value="Assign Shift">
    </form>
    <?php

    // Handle Shift Assignment Submission
    if (isset($_POST['assign_shift'])) {
        $candidate_id = intval($_POST['candidate']);
        $shift_name = sanitize_text_field($_POST['shift_name']);
        $shift_date = sanitize_text_field($_POST['shift_date']);
        $shift_time = sanitize_text_field($_POST['shift_time']);
        $hourly_rate = floatval($_POST['hourly_rate']);
        $currency = sanitize_text_field($_POST['currency']);
        
        // Calculate total hours and total payout
        $total_hours = 8; // Example: static value or calculate based on time input
        $total_payout = $total_hours * $hourly_rate;

        // Insert into database
        global $wpdb;
        $wpdb->insert(
            "{$wpdb->prefix}assigned_shifts",
            array(
                'candidate_id' => $candidate_id,
                'shift_name' => $shift_name,
                'shift_date' => $shift_date,
                'shift_time' => $shift_time,
                'total_hours' => $total_hours,
                'hourly_rate' => $hourly_rate,
                'total_payout' => $total_payout,
                'currency' => $currency
            )
        );

        echo '<p>Shift assigned successfully!</p>';
    }
}

// Create the table on plugin activation
function shift_assignment_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'assigned_shifts';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        candidate_id bigint(20) NOT NULL,
        shift_name varchar(100) NOT NULL,
        shift_date date NOT NULL,
        shift_time time NOT NULL,
        total_hours float NOT NULL,
        hourly_rate float NOT NULL,
        total_payout float NOT NULL,
        currency varchar(3) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'shift_assignment_install');
