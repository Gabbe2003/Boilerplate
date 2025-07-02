<?php
/**
 * Plugin Name: Headless Post Views
 * Description: Tracks and displays post views for headless WordPress setup.
 * Version: 1.0
 * Author: Your Name
 */

 // Load rest Page
 require_once plugin_dir_path(__FILE__) . 'rest.php';

// === Load Dashboard Page ===
require_once plugin_dir_path(__FILE__) . 'admin/dashboard-page.php';

// === Register Meta Field for Views ===
// This ensures post_views is available in REST/GraphQL and stored as integer
add_action('init', function () {
    register_post_meta('post', 'post_views', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'integer',
        'default' => 0,
    ]);
});

// === Add 'Views' Column to Posts List ===
// Adds a new column to the /wp-admin/edit.php table
add_filter('manage_post_posts_columns', function ($columns) {
    $columns['post_views'] = 'Views';
    return $columns;
});

// Fill the new column with post_views value
add_action('manage_post_posts_custom_column', function ($column, $post_id) {
    if ($column === 'post_views') {
        $views = (int) get_post_meta($post_id, 'post_views', true);
        echo $views;
    }
}, 10, 2);

// Make 'Views' column sortable
add_filter('manage_edit-post_sortable_columns', function ($columns) {
    $columns['post_views'] = 'post_views';
    return $columns;
});

// Hook sorting logic for views
add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query()) return;

    if ($query->get('orderby') === 'post_views') {
        $query->set('meta_key', 'post_views');
        $query->set('orderby', 'meta_value_num');
    }
});

// === Add Dashboard Page to Admin Menu ===
// This creates the "Post Views" item in the main Dashboard menu
add_action('admin_menu', function () {
    add_dashboard_page(
        'Post Views Dashboard',  // Page title
        'Post Views',            // Menu title
        'edit_posts',            // Capability
        'post-views-dashboard',  // Menu slug
        'hpv_render_dashboard_page' // Callback function (in dashboard-page.php)
    );
});

// === Plugin Activation: Create log table ===
register_activation_hook(__FILE__, 'hpv_create_views_log_table');

function hpv_create_views_log_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'post_view_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            view_date DATETIME NOT NULL,
            PRIMARY KEY (id),
            INDEX post_date_idx (post_id, view_date)
        ) $charset_collate;
    ";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function hpv_get_views_this_week() {
    global $wpdb;

    $start_of_week = date('Y-m-d 00:00:00', strtotime('monday this week'));
    $end_of_week   = date('Y-m-d 23:59:59', strtotime('sunday this week'));

    $table = $wpdb->prefix . 'post_view_logs';

    $results = $wpdb->get_results(
        $wpdb->prepare("
            SELECT DATE(view_date) as view_day, COUNT(*) as total
            FROM $table
            WHERE view_date BETWEEN %s AND %s
            GROUP BY view_day
            ORDER BY view_day ASC
        ", $start_of_week, $end_of_week),
        ARRAY_A
    );

    // Ensure all days in the week are represented (even if 0)
    $views_by_day = [];
    for ($i = 0; $i < 7; $i++) {
        $day = date('Y-m-d', strtotime("monday this week +{$i} days"));
        $views_by_day[$day] = 0;
    }

    foreach ($results as $row) {
        $views_by_day[$row['view_day']] = (int) $row['total'];
    }

    return $views_by_day;
}


function hpv_get_views_by_week($reference_date = null) {
    global $wpdb;

    $reference_date = $reference_date ?: current_time('Y-m-d');

    $start_of_week = date('Y-m-d 00:00:00', strtotime('monday this week', strtotime($reference_date)));
    $end_of_week   = date('Y-m-d 23:59:59', strtotime('sunday this week', strtotime($reference_date)));

    $table = $wpdb->prefix . 'post_view_logs';

    $results = $wpdb->get_results(
        $wpdb->prepare("
            SELECT DATE(view_date) as view_day, COUNT(*) as total
            FROM $table
            WHERE view_date BETWEEN %s AND %s
            GROUP BY view_day
            ORDER BY view_day ASC
        ", $start_of_week, $end_of_week),
        ARRAY_A
    );

    // Fill missing days with 0
    $views_by_day = [];
    for ($i = 0; $i < 7; $i++) {
        $day = date('Y-m-d', strtotime("monday this week +{$i} days", strtotime($reference_date)));
        $views_by_day[$day] = 0;
    }

    foreach ($results as $row) {
        $views_by_day[$row['view_day']] = (int) $row['total'];
    }

    return $views_by_day;
}

// === Get total views (all time)
function hpv_get_total_views_all_time() {
    global $wpdb;
    $table = $wpdb->prefix . 'post_view_logs';

    return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
}

// === Get top posts for a given week
function hpv_get_top_posts_by_week($reference_date = null, $limit = 5) {
    global $wpdb;

    $reference_date = $reference_date ?: current_time('Y-m-d');
    $start_of_week = date('Y-m-d 00:00:00', strtotime('monday this week', strtotime($reference_date)));
    $end_of_week   = date('Y-m-d 23:59:59', strtotime('sunday this week', strtotime($reference_date)));

    $table = $wpdb->prefix . 'post_view_logs';

    $results = $wpdb->get_results($wpdb->prepare("
        SELECT post_id, COUNT(*) as views
        FROM $table
        WHERE view_date BETWEEN %s AND %s
        GROUP BY post_id
        ORDER BY views DESC
        LIMIT %d
    ", $start_of_week, $end_of_week, $limit), ARRAY_A);

    return $results;
}


function hpv_get_top_viewed_posts($period = 'day', $limit = 6) {
    global $wpdb;

    $now = current_time('mysql');
    $table = $wpdb->prefix . 'post_view_logs';

    // Set start date based on period
    switch ($period) {
        case 'day':
            $start = date('Y-m-d 00:00:00', strtotime('today'));
            break;
        case 'week':
            $start = date('Y-m-d 00:00:00', strtotime('monday this week'));
            break;
        case 'month':
            $start = date('Y-m-01 00:00:00');
            break;
        default:
            return []; // Invalid
    }

    // Get most viewed post IDs
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT post_id, COUNT(*) as views
        FROM $table
        WHERE view_date BETWEEN %s AND %s
        GROUP BY post_id
        ORDER BY views DESC
        LIMIT %d
    ", $start, $now, $limit), ARRAY_A);

    if (!$results) return [];

    // Fetch actual posts
    $post_ids = wp_list_pluck($results, 'post_id');

    $posts = get_posts([
        'post__in' => $post_ids,
        'orderby' => 'post__in',
        'post_type' => 'post',
        'post_status' => 'publish',
        'numberposts' => $limit,
    ]);

    return $posts;
}
