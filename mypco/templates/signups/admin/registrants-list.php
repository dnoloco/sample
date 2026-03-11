<?php
/**
 * Registrants List Template
 *
 * Displays registrants for a specific signup.
 *
 * Available variables:
 * - $signup (object) - Signup details
 * - $confirmed (array) - Confirmed registrations
 * - $waitlist (array) - Waitlist registrations
 * - $deleted (bool) - Whether registration was just deleted
 */

defined('ABSPATH') || exit;
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($signup->event_name); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=mypco-registrations')); ?>" class="page-title-action">
        ← <?php _e('Back to Registrations', 'mypco-online'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=mypco-registrations&view=add&signup_id=' . $signup->id)); ?>" 
       class="page-title-action">
        <?php _e('Add Registration', 'mypco-online'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if ($deleted): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Registration deleted successfully.', 'mypco-online'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Event Info Card -->
    <div class="card" style="margin-bottom: 20px;">
        <h2><?php _e('Event Information', 'mypco-online'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Date', 'mypco-online'); ?></th>
                <td><?php echo esc_html(mysql2date('l, F j, Y g:i A', $signup->event_date)); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Capacity', 'mypco-online'); ?></th>
                <td>
                    <?php if ($signup->max_attendees > 0): ?>
                        <?php echo count($confirmed); ?> / <?php echo intval($signup->max_attendees); ?> 
                        <?php _e('registrants', 'mypco-online'); ?>
                    <?php else: ?>
                        <?php echo count($confirmed); ?> <?php _e('registrants (unlimited)', 'mypco-online'); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($signup->payment_required): ?>
            <tr>
                <th scope="row"><?php _e('Payment', 'mypco-online'); ?></th>
                <td>$<?php echo number_format($signup->payment_amount, 2); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Confirmed Registrations -->
    <h2><?php _e('Confirmed Registrations', 'mypco-online'); ?> (<?php echo count($confirmed); ?>)</h2>

    <?php if (empty($confirmed)): ?>
        <p><?php _e('No confirmed registrations yet.', 'mypco-online'); ?></p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th style="width: 60px;"><?php _e('ID', 'mypco-online'); ?></th>
                <th><?php _e('Name', 'mypco-online'); ?></th>
                <th style="width: 200px;"><?php _e('Email', 'mypco-online'); ?></th>
                <th style="width: 120px;"><?php _e('Phone', 'mypco-online'); ?></th>
                <th style="width: 100px;"><?php _e('Payment', 'mypco-online'); ?></th>
                <th style="width: 150px;"><?php _e('Registered', 'mypco-online'); ?></th>
                <th style="width: 120px;"><?php _e('Actions', 'mypco-online'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($confirmed as $reg):
                $payment_status_colors = [
                    'paid' => 'green',
                    'partial' => 'orange',
                    'pending' => 'red'
                ];
                $status_color = $payment_status_colors[$reg->payment_status] ?? 'gray';
                ?>
                <tr>
                    <td><?php echo intval($reg->id); ?></td>
                    <td>
                        <strong>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=mypco-registrations&view=detail&id=' . $reg->id)); ?>">
                                <?php echo esc_html($reg->first_name . ' ' . $reg->last_name); ?>
                            </a>
                        </strong>
                    </td>
                    <td><?php echo esc_html($reg->email); ?></td>
                    <td><?php echo esc_html($reg->phone ?: '—'); ?></td>
                    <td>
                        <span style="color: <?php echo esc_attr($status_color); ?>;">
                            <?php echo esc_html(ucfirst($reg->payment_status)); ?>
                        </span>
                        <?php if ($reg->amount_paid > 0): ?>
                            <br><small>$<?php echo number_format($reg->amount_paid, 2); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html(mysql2date('M j, Y g:i A', $reg->registration_date)); ?></td>
                    <td>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=mypco-registrations&view=detail&id=' . $reg->id)); ?>" 
                           class="button button-small">
                            <?php _e('View', 'mypco-online'); ?>
                        </a>
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=mypco-registrations&view=registrants&signup_id=' . $signup->id . '&action=delete&reg_id=' . $reg->id), 'delete_registration_' . $reg->id)); ?>" 
                           class="button button-small" 
                           onclick="return confirm('<?php esc_attr_e('Delete this registration?', 'mypco-online'); ?>');">
                            <?php _e('Delete', 'mypco-online'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Waitlist -->
    <?php if (!empty($waitlist)): ?>
        <h2 style="margin-top: 40px;"><?php _e('Waitlist', 'mypco-online'); ?> (<?php echo count($waitlist); ?>)</h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th style="width: 60px;"><?php _e('ID', 'mypco-online'); ?></th>
                <th><?php _e('Name', 'mypco-online'); ?></th>
                <th style="width: 200px;"><?php _e('Email', 'mypco-online'); ?></th>
                <th style="width: 120px;"><?php _e('Phone', 'mypco-online'); ?></th>
                <th style="width: 150px;"><?php _e('Added', 'mypco-online'); ?></th>
                <th style="width: 120px;"><?php _e('Actions', 'mypco-online'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($waitlist as $reg): ?>
                <tr style="background-color: #fff9e6;">
                    <td><?php echo intval($reg->id); ?></td>
                    <td>
                        <strong>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=mypco-registrations&view=detail&id=' . $reg->id)); ?>">
                                <?php echo esc_html($reg->first_name . ' ' . $reg->last_name); ?>
                            </a>
                        </strong>
                    </td>
                    <td><?php echo esc_html($reg->email); ?></td>
                    <td><?php echo esc_html($reg->phone ?: '—'); ?></td>
                    <td><?php echo esc_html(mysql2date('M j, Y g:i A', $reg->registration_date)); ?></td>
                    <td>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=mypco-registrations&view=detail&id=' . $reg->id)); ?>" 
                           class="button button-small">
                            <?php _e('View', 'mypco-online'); ?>
                        </a>
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=mypco-registrations&view=registrants&signup_id=' . $signup->id . '&action=delete&reg_id=' . $reg->id), 'delete_registration_' . $reg->id)); ?>" 
                           class="button button-small" 
                           onclick="return confirm('<?php esc_attr_e('Delete this registration?', 'mypco-online'); ?>');">
                            <?php _e('Delete', 'mypco-online'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
