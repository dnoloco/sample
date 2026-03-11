<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */

class MyPCO_Deactivator {

    /**
     * Plugin deactivation tasks.
     *
     * Performs cleanup tasks when the plugin is deactivated.
     * Note: This does NOT delete user data or credentials - that's handled by uninstall.
     */
    public static function deactivate() {
        // Clear all PCO-related caches
        self::clear_caches();

        // Clear any scheduled cron jobs
        self::clear_scheduled_tasks();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Log deactivation
        error_log('MyPCO Online: Plugin deactivated');
    }

    /**
     * Clear all PCO-related caches and transients.
     */
    private static function clear_caches() {
        global $wpdb;

        // Clear all transients starting with 'pco_' or 'mypco_'
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_pco_%' 
             OR option_name LIKE '_transient_timeout_pco_%'
             OR option_name LIKE '_transient_mypco_%' 
             OR option_name LIKE '_transient_timeout_mypco_%'"
        );

        if ($deleted) {
            error_log("MyPCO Online: Cleared {$deleted} cached items");
        }

        // Clear any object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }

    /**
     * Clear any scheduled WordPress cron tasks.
     */
    private static function clear_scheduled_tasks() {
        // Clear any scheduled tasks for cache cleanup
        $timestamp = wp_next_scheduled('mypco_daily_cache_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'mypco_daily_cache_cleanup');
        }

        // Clear any scheduled tasks for data sync
        $timestamp = wp_next_scheduled('mypco_hourly_sync');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'mypco_hourly_sync');
        }

        error_log('MyPCO Online: Cleared scheduled tasks');
    }

    /**
     * Clear any temporary files or uploads.
     */
    private static function clear_temporary_files() {
        $upload_dir = wp_upload_dir();
        $mypco_temp = $upload_dir['basedir'] . '/mypco-temp/';

        if (is_dir($mypco_temp)) {
            // Remove temporary files
            $files = glob($mypco_temp . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            @rmdir($mypco_temp);
        }
    }

    /**
     * Note: Data preservation on deactivation
     *
     * The following are intentionally NOT deleted during deactivation:
     * - API credentials (encrypted in wp_options)
     * - Database tables (signups, registrations, message logs)
     * - Module settings and preferences
     * - License information
     *
     * These are only removed during complete uninstall via uninstall.php
     * This allows users to deactivate temporarily without losing data.
     */
}