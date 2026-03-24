<?php
// includes/class-simplepco-google-forms-webhook.php

class SimplePCO_Google_Forms_Webhook {

    private $signups_handler;
    private $stripe_handler;

    public function __construct() {
        // Hook registration handled by the centralized loader in simplepco.php
    }

    /**
     * Set the signups handler
     */
    public function set_signups_handler($signups_handler) {
        $this->signups_handler = $signups_handler;
    }

    /**
     * Set the Stripe handler
     */
    public function set_stripe_handler($stripe_handler) {
        $this->stripe_handler = $stripe_handler;
    }

    /**
     * Register REST API endpoint for form submissions
     */
    public function register_rest_route() {
        register_rest_route('simplepco-forms/v1', '/submit', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_form_submission'],
            'permission_callback' => [$this, 'verify_webhook_secret']
        ]);
    }

    /**
     * Verify webhook secret from header
     */
    public function verify_webhook_secret($request) {
        $secret_header = $request->get_header('X-Form-Secret');
        $stored_secret = get_option('simplepco_google_forms_secret');

        return !empty($stored_secret) && hash_equals($stored_secret, $secret_header);
    }

    /**
     * Handle incoming form submission
     */
    public function handle_form_submission($request) {
        $params = $request->get_json_params();

        $form_id = $params['formId'] ?? '';
        $form_title = $params['formTitle'] ?? '';
        $response_id = $params['responseId'] ?? '';
        $timestamp = $params['timestamp'] ?? current_time('mysql');
        $email = $params['email'] ?? '';
        $responses = $params['responses'] ?? [];

        // Convert ISO timestamp to MySQL format
        if (!empty($timestamp)) {
            try {
                $dt = new DateTime($timestamp);
                $timestamp = $dt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                $timestamp = current_time('mysql');
            }
        }

        // Check if this form is linked to a signup
        if ($this->signups_handler) {
            $signup = $this->signups_handler->get_signup_by_form_id($form_id);

            if ($signup) {
                // This is a registration for a signup event
                return $this->process_signup_registration($signup, $responses, $email, $timestamp, $response_id);
            }
        }

        // If not linked to signup, just log it (backward compatibility)
        return $this->log_generic_submission($form_id, $form_title, $response_id, $email, $timestamp, $responses);
    }

    /**
     * Process registration for a signup event
     */
    private function process_signup_registration($signup, $responses, $email, $timestamp, $response_id) {
        // Extract name from responses (look for common field names)
        $first_name = '';
        $last_name = '';
        $phone = '';

        foreach ($responses as $question => $answer) {
            $question_lower = strtolower($question);

            if (strpos($question_lower, 'first name') !== false || $question_lower === 'first') {
                $first_name = $answer;
            } elseif (strpos($question_lower, 'last name') !== false || $question_lower === 'last') {
                $last_name = $answer;
            } elseif (strpos($question_lower, 'phone') !== false || strpos($question_lower, 'mobile') !== false) {
                $phone = $answer;
            } elseif (strpos($question_lower, 'email') !== false && empty($email)) {
                $email = $answer;
            }
        }

        // Check if we're at capacity
        $current_count = $this->signups_handler->get_registration_count($signup->id, false);
        $is_waitlist = ($signup->max_attendees > 0 && $current_count >= $signup->max_attendees) ? 1 : 0;

        // Determine payment amount
        $payment_amount = $signup->payment_required ? floatval($signup->payment_amount) : 0;
        $payment_status = $payment_amount > 0 ? 'pending' : 'paid';

        // Save registration
        $registration_data = [
            'signup_id' => $signup->id,
            'form_response_id' => $response_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'registration_date' => $timestamp,
            'form_data' => $responses,
            'payment_status' => $payment_status,
            'payment_amount' => $payment_amount,
            'amount_paid' => 0,
            'is_waitlist' => $is_waitlist,
        ];

        $registration_id = $this->signups_handler->save_registration($registration_data);

        // If payment required and not on waitlist, send payment email
        if ($payment_amount > 0 && !$is_waitlist && $this->stripe_handler && !empty($email)) {
            $payment_link = $this->stripe_handler->create_payment_link(
                $registration_id,
                $payment_amount,
                $signup->event_name . ' - Registration Fee',
                $email
            );

            if (!isset($payment_link['error'])) {
                $full_name = trim($first_name . ' ' . $last_name);
                $this->stripe_handler->send_payment_email(
                    $email,
                    $full_name,
                    $payment_link['url'],
                    $payment_amount,
                    $signup->event_name
                );
            }
        }

        // Fire action hook for extensibility
        do_action('simplepco_signup_registration_received', $registration_id, $signup->id, $registration_data);

        return [
            'status' => 'success',
            'message' => 'Registration saved successfully',
            'registration_id' => $registration_id,
            'is_waitlist' => $is_waitlist,
            'timestamp' => current_time('mysql')
        ];
    }

    /**
     * Log generic form submission (not linked to signup)
     */
    private function log_generic_submission($form_id, $form_title, $response_id, $email, $timestamp, $responses) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'simplepco_form_submissions';

        $data = [
            'form_id' => $form_id,
            'form_title' => $form_title,
            'response_id' => $response_id,
            'submitted_at' => $timestamp,
            'submitter_email' => $email,
            'form_data' => json_encode($responses)
        ];

        $wpdb->insert($table_name, $data, ['%s', '%s', '%s', '%s', '%s', '%s']);

        return [
            'status' => 'success',
            'message' => 'Form submission logged',
            'id' => $wpdb->insert_id,
            'timestamp' => current_time('mysql')
        ];
    }

    /**
     * Get submission by ID (backward compatibility)
     */
    public function get_submission($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'simplepco_form_submissions';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
    }

    /**
     * Get all submissions (backward compatibility)
     */
    public function get_submissions($form_filter = '', $limit = 50, $offset = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'simplepco_form_submissions';

        $where = '';
        if (!empty($form_filter)) {
            $where = $wpdb->prepare("WHERE form_id = %s", $form_filter);
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} {$where} ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
            $limit, $offset
        ));
    }

    /**
     * Get submission count (backward compatibility)
     */
    public function get_submission_count($form_filter = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'simplepco_form_submissions';

        if (!empty($form_filter)) {
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE form_id = %s",
                $form_filter
            ));
        }

        return $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }

    /**
     * Get form list (backward compatibility)
     */
    public function get_form_list() {
        global $wpdb;
        $table = $wpdb->prefix . 'simplepco_form_submissions';

        return $wpdb->get_results("
            SELECT form_id, form_title, COUNT(*) as count
            FROM {$table}
            GROUP BY form_id, form_title
            ORDER BY form_title ASC
        ");
    }

    /**
     * Delete submission (backward compatibility)
     */
    public function delete_submission($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'simplepco_form_submissions';
        return $wpdb->delete($table, ['id' => $id], ['%d']);
    }
}
