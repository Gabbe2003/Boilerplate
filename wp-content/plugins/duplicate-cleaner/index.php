<?php
/**
 * Plugin Name: Duplicate Cleaner – Dashboard
 * Description: Loader file that boots the Duplicate Cleaner Dashboard plugin (admin dashboard, bulk deletion, and 301 redirects to base slug). Keep the core prevention plugin active to block new numbered slugs.
 * Version: 1.1.0
 * Author: Your Name
 * License: GPL-2.0+
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Text Domain: gt-dcd
 */

if (!defined('ABSPATH')) { exit; }

// Define plugin constants
if (!defined('GT_DCD_VERSION')) define('GT_DCD_VERSION', '1.1.0');
if (!defined('GT_DCD_FILE'))    define('GT_DCD_FILE', __FILE__);
if (!defined('GT_DCD_DIR'))     define('GT_DCD_DIR', plugin_dir_path(__FILE__));
if (!defined('GT_DCD_URL'))     define('GT_DCD_URL', plugin_dir_url(__FILE__));

// Optionally: check for minimum WP version / PHP version here
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function(){
        if (!current_user_can('activate_plugins')) return;
        echo '<div class="notice notice-error"><p><strong>Duplicate Cleaner – Dashboard</strong> requires PHP 7.4 or higher.</p></div>';
    });
    return;
}

// Load the actual implementation (kept in a separate file for clarity)
$impl = GT_DCD_DIR . 'duplicate-cleaner-dashboard.php';
if (file_exists($impl)) {
    require_once $impl;
} else {
    // Show an admin notice if the implementation file is missing
    add_action('admin_notices', function(){
        if (!current_user_can('activate_plugins')) return;
        echo '<div class="notice notice-error"><p><strong>Duplicate Cleaner – Dashboard</strong> is missing <code>duplicate-cleaner-dashboard.php</code>. Please upload that file to <code>' . esc_html(GT_DCD_DIR) . '</code>.</p></div>';
    });
}

// Optional: gently remind admins to keep the prevention plugin active
add_action('admin_init', function(){
    if (!current_user_can('activate_plugins')) return;
    if (!class_exists('GT_No_Numbered_Duplicates_Plugin')) {
        add_action('admin_notices', function(){
        });
    }
});
