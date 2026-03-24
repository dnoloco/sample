<?php
// includes/class-simplepco-signups.php

class SimplePCO_Signups {

    private $model;
    private $table_name;
    private $registrations_table;

    public function __construct(simplepco_api_model $model) {
        global $wpdb;
        $this->model = $model;
        $this->table_name = $wpdb->prefix . 'simplepco_signups';
        $this->registrations_table = $wpdb->prefix . 'simplepco_registrations';
    }

    /**
     * Create the signups database table
     */
    public static function create_tables() {
        global $wpdb;

        $signups_table = $wpdb->prefix . 'simplepco_signups';
        $registrations_table = $wpdb->prefix . 'simplepco_registrations';

        $charset_collate = $wpdb->get_charset_collate();

        // Signups table
        $sql_signups = "CREATE TABLE IF NOT EXISTS `{$signups_table}` (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_id VARCHAR(100) NOT NULL,
            event_name VARCHAR(255) NOT NULL,
            event_date DATETIME NOT NULL,
            google_form_id VARCHAR(255),
            google_form_url TEXT,
            max_attendees INT DEFAULT 0,
            payment_required TINYINT DEFAULT 0,
            payment_amount DECIMAL(10,2) DEFAULT 0,
            payment_description TEXT,
            allow_partial_payment TINYINT DEFAULT 0,
            minimum_payment DECIMAL(10,2) DEFAULT 0,
            is_active TINYINT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY event_id (event_id),
            KEY event_date (event_date),
            KEY is_active (is_active)
        ) $charset_collate;";

        // Registrations table
        $sql_registrations = "CREATE TABLE IF NOT EXISTS `{$registrations_table}` (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            signup_id BIGINT UNSIGNED NOT NULL,
            form_response_id VARCHAR(100),
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            email VARCHAR(255),
            phone VARCHAR(50),
            registration_date DATETIME NOT NULL,
            form_data JSON,
            payment_status VARCHAR(20) DEFAULT 'pending',
            payment_amount DECIMAL(10,2) DEFAULT 0,
            amount_paid DECIMAL(10,2) DEFAULT 0,
            stripe_payment_intent_id VARCHAR(255),
            is_waitlist TINYINT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY signup_id (signup_id),
            KEY email (email),
            KEY payment_status (payment_status),
            KEY is_waitlist (is_waitlist)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_signups);
        dbDelta($sql_registrations);

        // Add new columns if they don't exist (for existing installations)
        self::maybe_add_partial_payment_columns();
    }

    /**
     * Add partial payment columns to existing tables
     */
    private static function maybe_add_partial_payment_columns() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'simplepco_signups';

        // Check if columns exist
        $columns = $wpdb->get_results("SHOW COLUMNS FROM `{$table_name}` LIKE 'allow_partial_payment'");

        if (empty($columns)) {
            $wpdb->query("ALTER TABLE `{$table_name}` 
                ADD COLUMN `allow_partial_payment` TINYINT DEFAULT 0 AFTER `payment_description`,
                ADD COLUMN `minimum_payment` DECIMAL(10,2) DEFAULT 0 AFTER `allow_partial_payment`");
        }
    }

    /**
     * Get upcoming PCO calendar events
     */
    public function get_simplepco_events($count = 50) {
        $params = [
            'filter' => 'future',
            'per_page' => $count,
            'include' => 'event'
        ];

        $transient_key = 'simplepco_events_for_signups_' . $count;
        $response = $this->model->get_data_with_caching('calendar', '/v2/event_instances', $params, $transient_key);

        if (isset($response['error'])) {
            return ['error' => $response['error']];
        }

        $instances = $response['data'] ?? [];
        $included = $response['included'] ?? [];

        // Build event map
        $event_map = [];
        foreach ($included as $item) {
            if ($item['type'] === 'Event') {
                $event_map[$item['id']] = $item['attributes'];
            }
        }

        $events = [];
        foreach ($instances as $instance) {
            $event_id = $instance['relationships']['event']['data']['id'] ?? null;
            $parent_event = $event_map[$event_id] ?? null;

            if ($parent_event) {
                $events[] = [
                    'instance_id' => $instance['id'],
                    'event_id' => $event_id,
                    'name' => $parent_event['name'] ?? 'Unnamed Event',
                    'starts_at' => $instance['attributes']['starts_at'],
                    'location' => $instance['attributes']['location'] ?? '',
                ];
            }
        }

        return $events;
    }

    /**
     * Create or update a signup
     */
    public function save_signup($data) {
        global $wpdb;

        $signup_data = [
            'event_id' => sanitize_text_field($data['event_id']),
            'event_name' => sanitize_text_field($data['event_name']),
            'event_date' => sanitize_text_field($data['event_date']),
            'google_form_id' => sanitize_text_field($data['google_form_id'] ?? ''),
            'google_form_url' => esc_url_raw($data['google_form_url'] ?? ''),
            'max_attendees' => intval($data['max_attendees'] ?? 0),
            'payment_required' => intval($data['payment_required'] ?? 0),
            'payment_amount' => floatval($data['payment_amount'] ?? 0),
            'payment_description' => sanitize_textarea_field($data['payment_description'] ?? ''),
            'allow_partial_payment' => intval($data['allow_partial_payment'] ?? 0),
            'minimum_payment' => floatval($data['minimum_payment'] ?? 0),
            'is_active' => intval($data['is_active'] ?? 1),
        ];

        if (!empty($data['id'])) {
            // Update existing
            $wpdb->update(
                $this->table_name,
                $signup_data,
                ['id' => intval($data['id'])],
                ['%s', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%s', '%d', '%f', '%d'],
                ['%d']
            );
            return intval($data['id']);
        } else {
            // Insert new
            $wpdb->insert(
                $this->table_name,
                $signup_data,
                ['%s', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%s', '%d', '%f', '%d']
            );
            return $wpdb->insert_id;
        }
    }

    /**
     * Get all signups
     */
    public function get_signups($active_only = false) {
        global $wpdb;

        $where = $active_only ? "WHERE is_active = 1" : "";

        return $wpdb->get_results("
            SELECT * FROM {$this->table_name}
            {$where}
            ORDER BY event_date ASC
        ");
    }

    /**
     * Get single signup
     */
    public function get_signup($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }

    /**
     * Get signup by Google Form ID
     */
    public function get_signup_by_form_id($form_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE google_form_id = %s",
            $form_id
        ));
    }

    /**
     * Delete signup
     */
    public function delete_signup($id) {
        global $wpdb;
        return $wpdb->delete($this->table_name, ['id' => $id], ['%d']);
    }

    /**
     * Get registration count for a signup
     */
    public function get_registration_count($signup_id, $include_waitlist = true) {
        global $wpdb;

        $where = $include_waitlist ? "" : "AND is_waitlist = 0";

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->registrations_table} WHERE signup_id = %d {$where}",
            $signup_id
        ));
    }

    /**
     * Get registrations for a signup
     */
    public function get_registrations($signup_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->registrations_table} 
            WHERE signup_id = %d 
            ORDER BY registration_date ASC",
            $signup_id
        ));
    }

    /**
     * Get single registration
     */
    public function get_registration($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->registrations_table} WHERE id = %d",
            $id
        ));
    }

    /**
     * Save a registration
     */
    public function save_registration($data) {
        global $wpdb;

        $reg_data = [
            'signup_id' => intval($data['signup_id']),
            'form_response_id' => sanitize_text_field($data['form_response_id'] ?? ''),
            'first_name' => sanitize_text_field($data['first_name'] ?? ''),
            'last_name' => sanitize_text_field($data['last_name'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'registration_date' => sanitize_text_field($data['registration_date']),
            'form_data' => json_encode($data['form_data'] ?? []),
            'payment_status' => sanitize_text_field($data['payment_status'] ?? 'pending'),
            'payment_amount' => floatval($data['payment_amount'] ?? 0),
            'amount_paid' => floatval($data['amount_paid'] ?? 0),
            'is_waitlist' => intval($data['is_waitlist'] ?? 0),
        ];

        if (!empty($data['id'])) {
            // Update
            $wpdb->update(
                $this->registrations_table,
                $reg_data,
                ['id' => intval($data['id'])],
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%d'],
                ['%d']
            );
            return intval($data['id']);
        } else {
            // Insert
            $wpdb->insert(
                $this->registrations_table,
                $reg_data,
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%d']
            );
            return $wpdb->insert_id;
        }
    }

    /**
     * Delete registration
     */
    public function delete_registration($id) {
        global $wpdb;
        return $wpdb->delete($this->registrations_table, ['id' => $id], ['%d']);
    }

    /**
     * Get signups that have registrations
     */
    public function get_signups_with_registrations() {
        global $wpdb;

        return $wpdb->get_results("
            SELECT 
                s.*,
                COUNT(r.id) as total_registrations,
                SUM(CASE WHEN r.is_waitlist = 0 THEN 1 ELSE 0 END) as confirmed_count,
                SUM(CASE WHEN r.is_waitlist = 1 THEN 1 ELSE 0 END) as waitlist_count
            FROM {$this->table_name} s
            LEFT JOIN {$this->registrations_table} r ON s.id = r.signup_id
            GROUP BY s.id
            HAVING total_registrations > 0
            ORDER BY s.event_date ASC
        ");
    }
}
