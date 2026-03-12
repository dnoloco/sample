<?php
/**
 * Registrant Detail Template
 *
 * Displays detailed information for a single registration.
 *
 * Available variables:
 * - $registration (object) - Registration details
 * - $signup (object) - Associated signup details
 * - $form_data (array) - Google Forms response data
 * - $error (string|null) - Error message if any
 */

defined('ABSPATH') || exit;
?>

<div class="wrap">
    <?php if (isset($error)): ?>
        <h1><?php _e('Error', 'simplepco'); ?></h1>
        <p><?php echo esc_html($error); ?></p>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=simplepco-registrations')); ?>" class="button">
                ← <?php _e('Back to Registrations', 'simplepco'); ?>
            </a>
        </p>
        <?php return; ?>
    <?php endif; ?>

    <h1 class="wp-heading-inline"><?php _e('Registration Details', 'simplepco'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=simplepco-registrations&view=registrants&signup_id=' . $signup->id)); ?>" 
       class="page-title-action">
        ← <?php _e('Back to Registrants', 'simplepco'); ?>
    </a>
    <hr class="wp-header-end">

    <!-- Registration Info Card -->
    <div class="card" style="max-width: 800px; margin-bottom: 20px;">
        <h2><?php _e('Registration Information', 'simplepco'); ?></h2>
        <table class="form-table">
            <tr>
                <th style="width: 200px;"><?php _e('Registration ID', 'simplepco'); ?>:</th>
                <td><strong>#<?php echo intval($registration->id); ?></strong></td>
            </tr>
            <tr>
                <th><?php _e('Event', 'simplepco'); ?>:</th>
                <td><strong><?php echo esc_html($signup->event_name); ?></strong></td>
            </tr>
            <tr>
                <th><?php _e('Event Date', 'simplepco'); ?>:</th>
                <td><?php echo esc_html(mysql2date('l, F j, Y g:i A', $signup->event_date)); ?></td>
            </tr>
            <tr>
                <th><?php _e('Registration Date', 'simplepco'); ?>:</th>
                <td><?php echo esc_html(mysql2date('l, F j, Y g:i A', $registration->registration_date)); ?></td>
            </tr>
            <tr>
                <th><?php _e('Status', 'simplepco'); ?>:</th>
                <td>
                    <?php if ($registration->is_waitlist): ?>
                        <span style="color: orange; font-weight: bold;">
                            ⏳ <?php _e('Waitlist', 'simplepco'); ?>
                        </span>
                    <?php else: ?>
                        <span style="color: green; font-weight: bold;">
                            ✓ <?php _e('Confirmed', 'simplepco'); ?>
                        </span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- Registrant Info Card -->
    <div class="card" style="max-width: 800px; margin-bottom: 20px;">
        <h2><?php _e('Registrant Information', 'simplepco'); ?></h2>
        <table class="form-table">
            <tr>
                <th style="width: 200px;"><?php _e('Name', 'simplepco'); ?>:</th>
                <td><strong><?php echo esc_html($registration->first_name . ' ' . $registration->last_name); ?></strong></td>
            </tr>
            <tr>
                <th><?php _e('Email', 'simplepco'); ?>:</th>
                <td><a href="mailto:<?php echo esc_attr($registration->email); ?>"><?php echo esc_html($registration->email); ?></a></td>
            </tr>
            <?php if ($registration->phone): ?>
            <tr>
                <th><?php _e('Phone', 'simplepco'); ?>:</th>
                <td><?php echo esc_html($registration->phone); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Payment Info Card -->
    <?php if ($signup->payment_required): ?>
    <div class="card" style="max-width: 800px; margin-bottom: 20px;">
        <h2><?php _e('Payment Information', 'simplepco'); ?></h2>
        <table class="form-table">
            <tr>
                <th style="width: 200px;"><?php _e('Payment Status', 'simplepco'); ?>:</th>
                <td>
                    <?php
                    $status_colors = [
                        'paid' => 'green',
                        'partial' => 'orange',
                        'pending' => 'red'
                    ];
                    $color = $status_colors[$registration->payment_status] ?? 'gray';
                    ?>
                    <span style="color: <?php echo esc_attr($color); ?>; font-weight: bold;">
                        <?php echo esc_html(ucfirst($registration->payment_status)); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th><?php _e('Total Amount', 'simplepco'); ?>:</th>
                <td>$<?php echo number_format($registration->payment_amount, 2); ?></td>
            </tr>
            <tr>
                <th><?php _e('Amount Paid', 'simplepco'); ?>:</th>
                <td>$<?php echo number_format($registration->amount_paid, 2); ?></td>
            </tr>
            <?php if ($registration->amount_paid < $registration->payment_amount): ?>
            <tr>
                <th><?php _e('Balance Due', 'simplepco'); ?>:</th>
                <td><strong>$<?php echo number_format($registration->payment_amount - $registration->amount_paid, 2); ?></strong></td>
            </tr>
            <?php endif; ?>
            <?php if ($registration->stripe_payment_intent_id): ?>
            <tr>
                <th><?php _e('Stripe Payment ID', 'simplepco'); ?>:</th>
                <td><code><?php echo esc_html($registration->stripe_payment_intent_id); ?></code></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    <?php endif; ?>

    <!-- Form Responses Card -->
    <?php if (!empty($form_data) && !isset($form_data['manual_entry'])): ?>
    <div class="card" style="max-width: 800px; margin-bottom: 20px;">
        <h2><?php _e('Form Responses', 'simplepco'); ?></h2>
        <table class="form-table">
            <?php foreach ($form_data as $question => $answer): ?>
                <?php if (is_string($answer)): ?>
                <tr>
                    <th style="width: 250px; vertical-align: top;">
                        <?php echo esc_html($question); ?>:
                    </th>
                    <td><?php echo esc_html($answer); ?></td>
                </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

</div>
