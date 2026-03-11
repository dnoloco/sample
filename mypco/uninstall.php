<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * This file is called when a user chooses to DELETE the plugin (not just deactivate).
 * All plugin data, tables, and options are removed.
 *
 * @link       https://example.com
 * @since      2.0.0
 * @package    MyPCO_Online
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Delete all plugin options from wp_options table.
 */
function mypco_delete_options() {
    global $wpdb;

    // Delete all options starting with 'mypco_'
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE 'mypco_%'"
    );

    // Specific encrypted credentials
    delete_option('mypco_pco_client_id');
    delete_option('mypco_pco_secret_key');
    delete_option('mypco_clearstream_api_token');
    delete_option('mypco_clearstream_message_header');

    // Module settings
    delete_option('mypco_module_calendar_enabled');
    delete_option('mypco_module_groups_enabled');
    delete_option('mypco_module_services_enabled');
    delete_option('mypco_module_contacts_enabled');
    delete_option('mypco_module_signups_enabled');
    delete_option('mypco_module_series_enabled');

    // License options
    delete_option('mypco_license_services');
    delete_option('mypco_license_series');

    // Other options
    delete_option('mypco_version');
    delete_option('mypco_webhook_secret');
    delete_option('mypco_requirements_check');
}

/**
 * Delete all transients and cached data.
 */
function mypco_delete_transients() {
    global $wpdb;

    // Delete all PCO-related transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_pco_%' 
         OR option_name LIKE '_transient_timeout_pco_%'
         OR option_name LIKE '_transient_mypco_%' 
         OR option_name LIKE '_transient_timeout_mypco_%'"
    );
}

/**
 * Drop all custom database tables.
 */
function mypco_drop_tables() {
    global $wpdb;

    $tables = [
        $wpdb->prefix . 'mypco_signups',
        $wpdb->prefix . 'mypco_registrations',
        $wpdb->prefix . 'mypco_clearstream_log',
        $wpdb->prefix . 'mypco_messages',
        $wpdb->prefix . 'mypco_speakers',
        $wpdb->prefix . 'mypco_series',
        $wpdb->prefix . 'mypco_topics'
    ];

    foreach ($tables as $table) {
        $result = $wpdb->query("DROP TABLE IF EXISTS {$table}");
        if ($result === false) {
            error_log("MyPCO Uninstall: Failed to drop table {$table}");
        } else {
            error_log("MyPCO Uninstall: Successfully dropped table {$table}");
        }
    }
}

/**
 * Delete uploaded files and temporary data.
 */
function mypco_delete_uploads() {
    $upload_dir = wp_upload_dir();
    $mypco_dir = $upload_dir['basedir'] . '/mypco-temp/';

    if (is_dir($mypco_dir)) {
        // Recursively delete directory
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($mypco_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            @$todo($fileinfo->getRealPath());
        }

        @rmdir($mypco_dir);
    }
}

/**
 * Clear any scheduled cron tasks.
 */
function mypco_clear_cron_tasks() {
    wp_clear_scheduled_hook('mypco_daily_cache_cleanup');
    wp_clear_scheduled_hook('mypco_hourly_sync');
}

/**
 * Log uninstall for debugging (optional).
 */
function mypco_log_uninstall() {
    error_log('MyPCO Online: Plugin completely uninstalled');
}

// Execute uninstall procedures
// Comment out any of these if you want to preserve certain data
mypco_delete_transients();
mypco_delete_options();
mypco_drop_tables();
mypco_delete_uploads();
mypco_clear_cron_tasks();
mypco_log_uninstall();

// Flush rewrite rules
flush_rewrite_rules();