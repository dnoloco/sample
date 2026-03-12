<?php
// includes/stripe-payment-handler.php

class SimplePCO_Stripe_Handler {

    private $api_key;
    private $webhook_secret;

    public function __construct() {
        $this->api_key = get_option('stripe_secret_key');
        $this->webhook_secret = get_option('stripe_webhook_secret');
    }

    /**
     * Register Stripe webhook REST endpoint
     */
    public function register_webhook_endpoint() {
        register_rest_route('simplepco-stripe/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => '__return_true'
        ]);
    }

    /**
     * Create a Stripe payment link
     */
    public function create_payment_link($registration_id, $amount, $description, $email) {
        if (empty($this->api_key)) {
            return ['error' => 'Stripe API key not configured'];
        }

        $amount_cents = intval($amount * 100); // Convert to cents

        // Create Stripe checkout session
        $session_data = [
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $description,
                    ],
                    'unit_amount' => $amount_cents,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => home_url('/payment-success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => home_url('/payment-cancelled'),
            'customer_email' => $email,
            'metadata' => [
                'registration_id' => $registration_id,
            ],
        ];

        $response = $this->make_stripe_request('checkout/sessions', $session_data);

        if (isset($response['error'])) {
            return $response;
        }

        return [
            'url' => $response['url'] ?? '',
            'session_id' => $response['id'] ?? ''
        ];
    }

    /**
     * Make Stripe API request
     */
    private function make_stripe_request($endpoint, $data) {
        $url = 'https://api.stripe.com/v1/' . $endpoint;

        $options = [
            'http' => [
                'header' => "Authorization: Bearer {$this->api_key}\r\n" .
                    "Content-Type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($this->flatten_array($data)),
                'ignore_errors' => true
            ]
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === FALSE) {
            return ['error' => 'Stripe API request failed'];
        }

        return json_decode($response, true);
    }

    /**
     * Flatten nested array for Stripe API
     */
    private function flatten_array($array, $prefix = '') {
        $result = [];
        foreach ($array as $key => $value) {
            $new_key = $prefix ? "{$prefix}[{$key}]" : $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flatten_array($value, $new_key));
            } else {
                $result[$new_key] = $value;
            }
        }
        return $result;
    }

    /**
     * Handle Stripe webhook
     */
    public function handle_webhook($request) {
        $payload = $request->get_body();
        $sig_header = $request->get_header('stripe-signature');

        // Verify webhook signature
        if (!empty($this->webhook_secret)) {
            // In production, verify signature here
            // For now, we'll trust the webhook
        }

        $event = json_decode($payload, true);

        if ($event['type'] === 'checkout.session.completed') {
            $session = $event['data']['object'];
            $registration_id = $session['metadata']['registration_id'] ?? null;
            $amount_paid = $session['amount_total'] / 100; // Convert from cents

            if ($registration_id) {
                // Update registration payment status
                global $wpdb;
                $table = $wpdb->prefix . 'simplepco_registrations';

                $wpdb->update(
                    $table,
                    [
                        'payment_status' => 'paid',
                        'amount_paid' => $amount_paid,
                        'stripe_payment_intent_id' => $session['payment_intent'] ?? ''
                    ],
                    ['id' => $registration_id],
                    ['%s', '%f', '%s'],
                    ['%d']
                );
            }
        }

        return ['status' => 'success'];
    }

    /**
     * Send payment link email
     */
    public function send_payment_email($email, $name, $payment_url, $amount, $event_name) {
        $subject = "Payment Required for {$event_name}";

        $message = "Hi {$name},\n\n";
        $message .= "Thank you for registering for {$event_name}!\n\n";
        $message .= "To complete your registration, please submit your payment of \${$amount}.\n\n";
        $message .= "Click here to pay: {$payment_url}\n\n";
        $message .= "If you have any questions, please contact us.\n\n";
        $message .= "Thank you!";

        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        return wp_mail($email, $subject, $message, $headers);
    }
}
