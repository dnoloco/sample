<?php
/**
 * Signups Public Component - UPDATED
 *
 * Handles public-facing functionality for the Signups module:
 * - Google Forms webhook endpoint
 * - Stripe payment processing
 * - Payment form shortcode
 */

class MyPCO_Signups_Public {

    private $loader;
    private $api_model;
    private $webhook_handler;
    private $stripe_handler;

    public function __construct($loader, $api_model) {
        $this->loader = $loader;
        $this->api_model = $api_model;
        
        // Initialize handlers
        global $mypco_webhook_handler, $mypco_stripe_handler;
        if ($mypco_webhook_handler) {
            $this->webhook_handler = $mypco_webhook_handler;
        }
        if ($mypco_stripe_handler) {
            $this->stripe_handler = $mypco_stripe_handler;
        }
    }

    /**
     * Initialize public functionality.
     */
    public function init() {
        // Register REST API endpoints for webhooks
        $this->loader->add_action('rest_api_init', $this, 'register_webhook_routes');
        
        // Register Stripe webhook handler
        $this->loader->add_action('rest_api_init', $this, 'register_stripe_webhook');
        
        // Register payment form shortcode
        $this->loader->add_action('init', $this, 'register_payment_shortcode');
        
        // Register AJAX handler for payment processing
        $this->loader->add_action('wp_ajax_mypco_process_payment', $this, 'ajax_process_payment');
        $this->loader->add_action('wp_ajax_nopriv_mypco_process_payment', $this, 'ajax_process_payment');
    }

    /**
     * Register Google Forms webhook route.
     */
    public function register_webhook_routes() {
        register_rest_route('mypco/v1', '/webhook/google-forms', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_google_forms_webhook'],
            'permission_callback' => [$this, 'verify_webhook_signature']
        ]);
    }

    /**
     * Register Stripe webhook route.
     */
    public function register_stripe_webhook() {
        register_rest_route('mypco/v1', '/webhook/stripe', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_stripe_webhook'],
            'permission_callback' => '__return_true' // Stripe has its own signature verification
        ]);
    }

    /**
     * Handle incoming Google Forms webhook.
     */
    public function handle_google_forms_webhook($request) {
        if (!$this->webhook_handler) {
            return new WP_Error('no_handler', 'Webhook handler not initialized', ['status' => 500]);
        }

        $body = $request->get_body();
        $data = json_decode($body, true);

        if (!$data) {
            return new WP_Error('invalid_json', 'Invalid JSON data', ['status' => 400]);
        }

        // Process the form submission
        $result = $this->webhook_handler->process_form_submission($data);

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response([
            'success' => true,
            'registration_id' => $result['registration_id'],
            'payment_required' => $result['payment_required']
        ], 200);
    }

    /**
     * Handle incoming Stripe webhook.
     */
    public function handle_stripe_webhook($request) {
        if (!$this->stripe_handler) {
            return new WP_Error('no_handler', 'Stripe handler not initialized', ['status' => 500]);
        }

        $body = $request->get_body();
        $signature = $request->get_header('stripe-signature');

        if (!$signature) {
            return new WP_Error('no_signature', 'Missing Stripe signature', ['status' => 400]);
        }

        // Process the Stripe event
        $result = $this->stripe_handler->handle_webhook($body, $signature);

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response([
            'success' => true,
            'event_type' => $result['event_type']
        ], 200);
    }

    /**
     * Verify webhook signature for Google Forms.
     */
    public function verify_webhook_signature($request) {
        $signature = $request->get_header('x-webhook-signature');
        $secret = get_option('mypco_webhook_secret');

        if (empty($secret)) {
            // If no secret is set, allow (for development)
            return true;
        }

        if (!$signature) {
            return false;
        }

        $body = $request->get_body();
        $expected_signature = hash_hmac('sha256', $body, $secret);

        return hash_equals($expected_signature, $signature);
    }

    /**
     * Register payment form shortcode.
     */
    public function register_payment_shortcode() {
        add_shortcode('mypco_payment_form', [$this, 'render_payment_form']);
    }

    /**
     * Render payment form shortcode.
     * [mypco_payment_form registration_id="123"]
     */
    public function render_payment_form($atts) {
        // Parse attributes
        $atts = shortcode_atts([
            'registration_id' => 0
        ], $atts);

        $registration_id = intval($atts['registration_id']);
        
        if (!$registration_id) {
            return $this->render_error('Invalid registration ID.');
        }

        // Get registration and signup details
        global $mypco_signups_handler;
        if (!$mypco_signups_handler) {
            return $this->render_error('Signups handler not initialized.');
        }

        $registration = $mypco_signups_handler->get_registration($registration_id);
        
        if (!$registration) {
            return $this->render_error('Registration not found.');
        }

        $signup = $mypco_signups_handler->get_signup($registration->signup_id);

        if (!$signup || !$signup->payment_required) {
            return $this->render_error('No payment required for this registration.');
        }

        // Calculate amount due
        $amount_due = $signup->payment_amount - $registration->amount_paid;

        if ($amount_due <= 0) {
            return '<div class="mypco-payment-complete"><p>' . __('Payment has been completed. Thank you!', 'mypco-online') . '</p></div>';
        }

        // Get Stripe public key
        $stripe_public_key = get_option('mypco_stripe_public_key');
        
        if (empty($stripe_public_key)) {
            return $this->render_error('Payment system not configured. Please contact the administrator.');
        }

        // Prepare template data
        $template_data = [
            'registration' => $registration,
            'signup' => $signup,
            'amount_due' => $amount_due,
            'stripe_public_key' => $stripe_public_key
        ];

        // Load template
        ob_start();
        $this->load_template('payment-form', $template_data);
        return ob_get_clean();
    }

    /**
     * AJAX handler for payment processing.
     */
    public function ajax_process_payment() {
        // Verify we have required data
        if (!isset($_POST['registration_id']) || !isset($_POST['payment_method_id'])) {
            wp_send_json_error(['message' => 'Missing required fields']);
            return;
        }

        $registration_id = intval($_POST['registration_id']);
        $payment_method_id = sanitize_text_field($_POST['payment_method_id']);

        // Get registration and signup
        global $mypco_signups_handler;
        if (!$mypco_signups_handler) {
            wp_send_json_error(['message' => 'System error']);
            return;
        }

        $registration = $mypco_signups_handler->get_registration($registration_id);
        $signup = $mypco_signups_handler->get_signup($registration->signup_id);

        // Calculate amount
        $amount_due = $signup->payment_amount - $registration->amount_paid;
        $amount_cents = intval($amount_due * 100); // Convert to cents

        // Create payment intent via Stripe handler
        if (!$this->stripe_handler) {
            wp_send_json_error(['message' => 'Payment system not available']);
            return;
        }

        $result = $this->stripe_handler->create_payment_intent([
            'amount' => $amount_cents,
            'currency' => 'usd',
            'payment_method' => $payment_method_id,
            'confirm' => true,
            'metadata' => [
                'registration_id' => $registration_id,
                'signup_id' => $signup->id,
                'event_name' => $signup->event_name
            ]
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }

        // Check if requires action (3D Secure)
        if ($result['status'] === 'requires_action') {
            wp_send_json_success([
                'requires_action' => true,
                'payment_intent_client_secret' => $result['client_secret']
            ]);
            return;
        }

        // Payment succeeded
        if ($result['status'] === 'succeeded') {
            // Update registration
            $mypco_signups_handler->update_payment_status($registration_id, [
                'payment_status' => 'paid',
                'amount_paid' => $registration->amount_paid + $amount_due,
                'stripe_payment_intent_id' => $result['id']
            ]);

            wp_send_json_success([
                'requires_action' => false,
                'message' => 'Payment successful'
            ]);
            return;
        }

        wp_send_json_error(['message' => 'Payment failed']);
    }

    /**
     * Render error message.
     */
    private function render_error($message) {
        $template_data = ['error' => $message];
        ob_start();
        $this->load_template('payment-form', $template_data);
        return ob_get_clean();
    }

    /**
     * Load a template file.
     */
    private function load_template($template_name, $data = []) {
        extract($data);
        $template_path = MYPCO_PLUGIN_DIR . 'templates/signups/public/' . $template_name . '.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<p>Template not found: ' . esc_html($template_name) . '</p>';
        }
    }
}
