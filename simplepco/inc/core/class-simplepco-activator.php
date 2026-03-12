<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */

class SimplePCO_Activator {

    /**
     * Plugin activation tasks.
     *
     * Sets up default options, creates database tables, and performs
     * initial setup tasks when the plugin is activated.
     */
    public static function activate() {
        // Set default options
        self::set_default_options();

        // Create database tables
        self::create_tables();

        // Generate webhook secret for signups
        self::generate_webhook_secret();

        // Clear all PCO caches
        self::clear_caches();

        // Check PHP requirements
        self::check_requirements();

        // Flag rewrite rules for flush on next page load, after CPTs/taxonomies
        // have been registered on the 'init' hook.
        set_transient('simplepco_flush_rewrite_rules', true);

        // Log activation
        error_log('SimplePCO: Plugin activated successfully');
    }

    /**
     * Set default plugin options.
     */
    private static function set_default_options() {
        // Enable free modules by default
        add_option('simplepco_module_calendar_enabled', true);
        add_option('simplepco_module_groups_enabled', true);

        // Freemium modules
        add_option('simplepco_module_series_enabled', true);

        // Premium modules disabled by default (enable after license validation)
        add_option('simplepco_module_services_enabled', true);
        add_option('simplepco_module_contacts_enabled', true);
        add_option('simplepco_module_signups_enabled', true);

        // Set plugin version
        add_option('simplepco_version', SIMPLEPCO_VERSION);

        // Set default timezone if not set
        if (!get_option('timezone_string')) {
            update_option('timezone_string', 'America/Chicago');
        }

        // License options (placeholder for future licensing system)
        add_option('simplepco_license_services', '');
        add_option('simplepco_license_series', '');
    }

    /**
     * Create database tables for signups, series, and messages.
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table for signup events
        $table_signups = $wpdb->prefix . 'simplepco_signups';
        $sql_signups = "CREATE TABLE IF NOT EXISTS $table_signups (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_id varchar(255) NOT NULL,
            event_name varchar(255) NOT NULL,
            event_date date NOT NULL,
            google_form_id varchar(255) DEFAULT NULL,
            google_form_url text DEFAULT NULL,
            max_attendees int DEFAULT 0,
            payment_required tinyint(1) DEFAULT 0,
            payment_amount decimal(10,2) DEFAULT 0.00,
            payment_description text DEFAULT NULL,
            allow_partial_payment tinyint(1) DEFAULT 0,
            minimum_payment decimal(10,2) DEFAULT 0.00,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY event_id (event_id),
            KEY event_date (event_date),
            KEY is_active (is_active)
        ) $charset_collate;";

        // Table for registrations
        $table_registrations = $wpdb->prefix . 'simplepco_registrations';
        $sql_registrations = "CREATE TABLE IF NOT EXISTS $table_registrations (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            signup_id mediumint(9) NOT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) DEFAULT NULL,
            registration_date datetime DEFAULT CURRENT_TIMESTAMP,
            form_data text DEFAULT NULL,
            payment_status varchar(50) DEFAULT 'pending',
            payment_amount decimal(10,2) DEFAULT 0.00,
            payment_date datetime DEFAULT NULL,
            stripe_payment_id varchar(255) DEFAULT NULL,
            notes text DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY signup_id (signup_id),
            KEY email (email),
            KEY payment_status (payment_status),
            KEY registration_date (registration_date)
        ) $charset_collate;";

        // Table for Clearstream message logs
        $table_clearstream = $wpdb->prefix . 'simplepco_clearstream_log';
        $sql_clearstream = "CREATE TABLE IF NOT EXISTS $table_clearstream (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            sender_name varchar(100) DEFAULT NULL,
            sender_id mediumint(9) DEFAULT NULL,
            recipient_count int DEFAULT 0,
            recipient_names text DEFAULT NULL,
            message_body text NOT NULL,
            status varchar(20) DEFAULT 'sent',
            scheduled_at datetime DEFAULT NULL,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY sender_id (sender_id),
            KEY sent_at (sent_at),
            KEY status (status)
        ) $charset_collate;";

        // Execute table creation
        dbDelta($sql_signups);
        dbDelta($sql_registrations);
        dbDelta($sql_clearstream);
    }

    /**
     * Generate a secure webhook secret for Google Forms integration.
     */
    private static function generate_webhook_secret() {
        if (!get_option('simplepco_webhook_secret')) {
            $secret = wp_generate_password(32, false);
            add_option('simplepco_webhook_secret', $secret);
        }
    }

    /**
     * Clear all PCO-related caches.
     */
    private static function clear_caches() {
        global $wpdb;

        // Clear all transients starting with 'pco_' or 'simplepco_'
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_pco_%' 
             OR option_name LIKE '_transient_timeout_pco_%'
             OR option_name LIKE '_transient_simplepco_%' 
             OR option_name LIKE '_transient_timeout_simplepco_%'"
        );
    }

    /**
     * Check if server meets plugin requirements.
     */
    private static function check_requirements() {
        $errors = [];

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $errors[] = 'PHP 7.4 or higher is required. Current version: ' . PHP_VERSION;
        }

        // Check WordPress version
        global $wp_version;
        if (version_compare($wp_version, '5.0', '<')) {
            $errors[] = 'WordPress 5.0 or higher is required. Current version: ' . $wp_version;
        }

        // Check required PHP extensions
        if (!extension_loaded('openssl')) {
            $errors[] = 'OpenSSL extension is required for credential encryption';
        }

        if (!extension_loaded('curl')) {
            $errors[] = 'cURL extension is required for API communication';
        }

        if (!extension_loaded('json')) {
            $errors[] = 'JSON extension is required';
        }

        // Check if WordPress salts are defined
        if (!defined('AUTH_KEY') || !defined('SECURE_AUTH_SALT')) {
            $errors[] = 'WordPress security salts (AUTH_KEY, SECURE_AUTH_SALT) must be defined in wp-config.php';
        }

        // Log errors
        if (!empty($errors)) {
            error_log('SimplePCO Activation Warnings:');
            foreach ($errors as $error) {
                error_log('  - ' . $error);
            }
        }

        // Store requirement check results
        update_option('simplepco_requirements_check', [
            'passed' => empty($errors),
            'errors' => $errors,
            'checked_at' => current_time('mysql')
        ]);
    }

    /**
     * Migrate old data if upgrading from previous version.
     */
    private static function maybe_migrate_data() {
        $current_version = get_option('simplepco_version');

        // First time activation
        if (!$current_version) {
            return;
        }

        // Perform version-specific migrations
        if (version_compare($current_version, '2.0.0', '<')) {
            // Migrate from 1.x to 2.0
            self::migrate_from_v1();
        }

        // Update version
        update_option('simplepco_version', SIMPLEPCO_VERSION);
    }

    /**
     * Migrate data from version 1.x to 2.0.
     */
    private static function migrate_from_v1() {
        // Placeholder for any data migrations needed
        // Example: rename old options, update database schema, etc.
        error_log('SimplePCO: Migrating from version 1.x to 2.0');
    }
}
