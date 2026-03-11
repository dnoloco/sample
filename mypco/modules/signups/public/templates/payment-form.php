<?php
/**
 * Payment Form Template
 *
 * Displays Stripe payment form for completing registration payment.
 * Used by [mypco_payment_form registration_id="123"] shortcode.
 *
 * Available variables:
 * - $registration (object) - Registration details
 * - $signup (object) - Event signup details
 * - $amount_due (float) - Amount still owed
 * - $stripe_public_key (string) - Stripe publishable key
 * - $error (string|null) - Error message if any
 */

defined('ABSPATH') || exit;
?>

<?php if (isset($error)): ?>
    <div class="mypco-payment-error">
        <p><?php echo esc_html($error); ?></p>
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="mypco-payment-form">
    <div class="mypco-payment-header">
        <h3><?php _e('Complete Your Registration', 'mypco-online'); ?></h3>
    </div>

    <div class="mypco-payment-details">
        <div class="detail-row">
            <span class="label"><?php _e('Event:', 'mypco-online'); ?></span>
            <span class="value"><strong><?php echo esc_html($signup->event_name); ?></strong></span>
        </div>
        
        <div class="detail-row">
            <span class="label"><?php _e('Date:', 'mypco-online'); ?></span>
            <span class="value"><?php echo esc_html(mysql2date('l, F j, Y g:i A', $signup->event_date)); ?></span>
        </div>
        
        <div class="detail-row">
            <span class="label"><?php _e('Registrant:', 'mypco-online'); ?></span>
            <span class="value"><?php echo esc_html($registration->first_name . ' ' . $registration->last_name); ?></span>
        </div>

        <?php if ($signup->allow_partial_payment && $registration->amount_paid > 0): ?>
        <div class="detail-row">
            <span class="label"><?php _e('Total Amount:', 'mypco-online'); ?></span>
            <span class="value">$<?php echo number_format($signup->payment_amount, 2); ?></span>
        </div>
        <div class="detail-row">
            <span class="label"><?php _e('Already Paid:', 'mypco-online'); ?></span>
            <span class="value">$<?php echo number_format($registration->amount_paid, 2); ?></span>
        </div>
        <?php endif; ?>
        
        <div class="detail-row total">
            <span class="label"><?php _e('Amount Due:', 'mypco-online'); ?></span>
            <span class="value"><strong>$<?php echo number_format($amount_due, 2); ?></strong></span>
        </div>
        
        <?php if (!empty($signup->payment_description)): ?>
        <div class="payment-description">
            <?php echo wp_kses_post(wpautop($signup->payment_description)); ?>
        </div>
        <?php endif; ?>
    </div>

    <form id="mypco-stripe-form" method="post">
        <input type="hidden" name="registration_id" value="<?php echo esc_attr($registration->id); ?>">
        <input type="hidden" name="amount" value="<?php echo esc_attr($amount_due); ?>">
        
        <div class="form-row">
            <label for="cardholder-name"><?php _e('Cardholder Name', 'mypco-online'); ?></label>
            <input type="text" id="cardholder-name" name="cardholder_name" 
                   value="<?php echo esc_attr($registration->first_name . ' ' . $registration->last_name); ?>" 
                   required>
        </div>
        
        <div class="form-row">
            <label for="card-element"><?php _e('Card Information', 'mypco-online'); ?></label>
            <div id="card-element">
                <!-- Stripe Card Element will be inserted here -->
            </div>
            <div id="card-errors" role="alert"></div>
        </div>
        
        <div class="form-row">
            <button type="submit" id="submit-payment" class="mypco-button">
                <?php _e('Pay', 'mypco-online'); ?> $<?php echo number_format($amount_due, 2); ?>
            </button>
        </div>
        
        <div id="payment-messages"></div>
    </form>
</div>

<!-- Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>
<script>
(function() {
    // Initialize Stripe
    const stripe = Stripe('<?php echo esc_js($stripe_public_key); ?>');
    const elements = stripe.elements();
    
    // Create card element
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        }
    });
    
    cardElement.mount('#card-element');
    
    // Handle real-time validation errors
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
            displayError.style.display = 'block';
        } else {
            displayError.textContent = '';
            displayError.style.display = 'none';
        }
    });
    
    // Handle form submission
    const form = document.getElementById('mypco-stripe-form');
    const submitButton = document.getElementById('submit-payment');
    const messagesDiv = document.getElementById('payment-messages');
    
    form.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        // Disable submit button
        submitButton.disabled = true;
        submitButton.textContent = '<?php esc_attr_e('Processing...', 'mypco-online'); ?>';
        
        // Create payment method
        const {paymentMethod, error} = await stripe.createPaymentMethod({
            type: 'card',
            card: cardElement,
            billing_details: {
                name: document.getElementById('cardholder-name').value
            }
        });
        
        if (error) {
            // Show error
            messagesDiv.innerHTML = '<div class="error">' + error.message + '</div>';
            submitButton.disabled = false;
            submitButton.textContent = '<?php esc_attr_e('Pay', 'mypco-online'); ?> $<?php echo number_format($amount_due, 2); ?>';
        } else {
            // Send to server to create payment intent
            handlePayment(paymentMethod.id);
        }
    });
    
    async function handlePayment(paymentMethodId) {
        const formData = new FormData(form);
        formData.append('payment_method_id', paymentMethodId);
        formData.append('action', 'mypco_process_payment');
        
        try {
            const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (result.data.requires_action) {
                    // 3D Secure authentication required
                    const {error: confirmError} = await stripe.confirmCardPayment(
                        result.data.payment_intent_client_secret
                    );
                    
                    if (confirmError) {
                        messagesDiv.innerHTML = '<div class="error">' + confirmError.message + '</div>';
                        submitButton.disabled = false;
                        submitButton.textContent = '<?php esc_attr_e('Pay', 'mypco-online'); ?> $<?php echo number_format($amount_due, 2); ?>';
                    } else {
                        // Payment succeeded
                        showSuccess();
                    }
                } else {
                    // Payment succeeded
                    showSuccess();
                }
            } else {
                messagesDiv.innerHTML = '<div class="error">' + (result.data.message || 'Payment failed') + '</div>';
                submitButton.disabled = false;
                submitButton.textContent = '<?php esc_attr_e('Pay', 'mypco-online'); ?> $<?php echo number_format($amount_due, 2); ?>';
            }
        } catch (err) {
            messagesDiv.innerHTML = '<div class="error">Network error. Please try again.</div>';
            submitButton.disabled = false;
            submitButton.textContent = '<?php esc_attr_e('Pay', 'mypco-online'); ?> $<?php echo number_format($amount_due, 2); ?>';
        }
    }
    
    function showSuccess() {
        form.style.display = 'none';
        messagesDiv.innerHTML = '<div class="success"><h4><?php esc_html_e('Payment Successful!', 'mypco-online'); ?></h4><p><?php esc_html_e('Thank you for your payment. You will receive a confirmation email shortly.', 'mypco-online'); ?></p></div>';
    }
})();
</script>

<style>
.mypco-payment-form {
    max-width: 500px;
    margin: 0 auto;
    padding: 30px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mypco-payment-header h3 {
    margin: 0 0 20px 0;
    padding-bottom: 15px;
    border-bottom: 2px solid #0073aa;
    color: #333;
}

.mypco-payment-details {
    margin-bottom: 25px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 4px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e5e5e5;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-row.total {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 2px solid #333;
    border-bottom: none;
    font-size: 1.2em;
}

.detail-row .label {
    color: #666;
}

.payment-description {
    margin-top: 15px;
    padding: 15px;
    background: #fff;
    border-left: 4px solid #0073aa;
    font-size: 14px;
}

.form-row {
    margin-bottom: 20px;
}

.form-row label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-row input[type="text"] {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

#card-element {
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
}

#card-errors {
    color: #fa755a;
    margin-top: 8px;
    display: none;
}

.mypco-button {
    width: 100%;
    padding: 15px;
    background: #0073aa;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.mypco-button:hover {
    background: #005a87;
}

.mypco-button:disabled {
    background: #ccc;
    cursor: not-allowed;
}

#payment-messages {
    margin-top: 20px;
}

#payment-messages .error {
    padding: 15px;
    background: #fef0f0;
    border-left: 4px solid #dc3232;
    color: #dc3232;
    border-radius: 4px;
}

#payment-messages .success {
    padding: 20px;
    background: #f0fef0;
    border-left: 4px solid #46b450;
    border-radius: 4px;
}

#payment-messages .success h4 {
    margin: 0 0 10px 0;
    color: #46b450;
}

.mypco-payment-error {
    padding: 20px;
    background: #fef0f0;
    border-left: 4px solid #dc3232;
    color: #dc3232;
    border-radius: 4px;
}
</style>
