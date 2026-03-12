<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * This file is called when a user chooses to DELETE the plugin (not just deactivate).
 * All plugin data, tables, and options are removed.
 *
 * @link       https://example.com
 * @since      2.0.0
 * @package    SimplePCO_Online
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Delete all plugin options from wp_options table.
 */
function simplepco_delete_options() {
    global $wpdb;

    // Delete all options starting with 'simplepco_'
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE 'simplepco_%'"
    );

    // Specific encrypted credentials
    delete_option('simplepco_pco_client_id');
    delete_option('simplepco_pco_secret_key');
    delete_option('simplepco_clearstream_api_token');
    delete_option('simplepco_clearstream_message_header');

    // Module settings
    delete_option('simplepco_module_calendar_enabled');
    delete_option('simplepco_module_groups_enabled');
    delete_option('simplepco_module_services_enabled');
    delete_option('simplepco_module_contacts_enabled');
    delete_option('simplepco_module_signups_enabled');
    delete_option('simplepco_module_series_enabled');

    // License options
    delete_option('simplepco_license_services');
    delete_option('simplepco_license_series');

    // Other options
    delete_option('simplepco_version');
    delete_option('simplepco_webhook_secret');
    delete_option('simplepco_requirements_check');
}

/**
 * Delete all transients and cached data.
 */
function simplepco_delete_transients() {
    global $wpdb;

    // Delete all PCO-related transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_pco_%' 
         OR option_name LIKE '_transient_timeout_pco_%'
         OR option_name LIKE '_transient_simplepco_%' 
         OR option_name LIKE '_transient_timeout_simplepco_%'"
    );
}

/**
 * Drop all custom database tables.
 */
function simplepco_drop_tables() {
    global $wpdb;

    $tables = [
        $wpdb->prefix . 'simplepco_signups',
        $wpdb->prefix . 'simplepco_registrations',
        $wpdb->prefix . 'simplepco_clearstream_log',
        $wpdb->prefix . 'simplepco_messages',
        $wpdb->prefix . 'simplepco_speakers',
        $wpdb->prefix . 'simplepco_series',
        $wpdb->prefix . 'simplepco_topics'
    ];

    foreach ($tables as $table) {
        $result = $wpdb->query("DROP TABLE IF EXISTS {$table}");
        if ($result === false) {
            error_log("SimplePCO Uninstall: Failed to drop table {$table}");
        } else {
            error_log("SimplePCO Uninstall: Successfully dropped table {$table}");
        }
    }
}

/**
 * Delete uploaded files and temporary data.
 */
function simplepco_delete_uploads() {
    $upload_dir = wp_upload_dir();
    $simplepco_dir = $upload_dir['basedir'] . '/simplepco-temp/';

    if (is_dir($simplepco_dir)) {
        // Recursively delete directory
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($simplepco_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            @$todo($fileinfo->getRealPath());
        }

        @rmdir($simplepco_dir);
    }
}

/**
 * Clear any scheduled cron tasks.
 */
function simplepco_clear_cron_tasks() {
    wp_clear_scheduled_hook('simplepco_daily_cache_cleanup');
    wp_clear_scheduled_hook('simplepco_hourly_sync');
}

/**
 * Log uninstall for debugging (optional).
 */
function simplepco_log_uninstall() {
    error_log('SimplePCO Online: Plugin completely uninstalled');
}

// Execute uninstall procedures
// Comment out any of these if you want to preserve certain data
simplepco_delete_transients();
simplepco_delete_options();
simplepco_drop_tables();
simplepco_delete_uploads();
simplepco_clear_cron_tasks();
simplepco_log_uninstall();

// Flush rewrite rules
flush_rewrite_rules();