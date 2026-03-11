<?php
/**
 * Signups List Template
 *
 * Displays all event signups with management actions.
 *
 * Available variables:
 * - $signups (array) - Array of signup objects
 * - $deleted (bool) - Whether a signup was just deleted
 */

defined('ABSPATH') || exit;
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Event Signups', 'mypco-online'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=mypco-signups&view=new')); ?>" class="page-title-action">
        <?php _e('Add New', 'mypco-online'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if ($deleted): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Signup deleted successfully.', 'mypco-online'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (empty($signups)): ?>
        <div class="notice notice-info">
            <p><?php _e('No signups created yet. Click "Add New" to create your first signup.', 'mypco-online'); ?></p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th style="width: 60px;"><?php _e('ID', 'mypco-online'); ?></th>
                <th><?php _e('Event Name', 'mypco-online'); ?></th>
                <th style="width: 150px;"><?php _e('Date', 'mypco-online'); ?></th>
                <th style="width: 100px;"><?php _e('Capacity', 'mypco-online'); ?></th>
                <th style="width: 100px;"><?php _e('Payment', 'mypco-online'); ?></th>
                <th style="width: 80px;"><?php _e('Status', 'mypco-online'); ?></th>
                <th style="width: 200px;"><?php _e('Actions', 'mypco-online'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($signups as $signup):
                $capacity_text = $signup->max_attendees > 0 
                    ? $signup->registration_count . ' / ' . $signup->max_attendees 
                    : $signup->registration_count;
                $payment_text = $signup->payment_required 
                    ? '$' . number_format($signup->payment_amount, 2) 
                    : '—';
                $status_class = $signup->is_active ? 'success' : 'error';
                $status_text = $signup->is_active ? __('Active', 'mypco-online') : __('Inactive', 'mypco-online');
                ?>
                <tr>
                    <td><?php echo intval($signup->id); ?></td>
                    <td>
                        <strong>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=mypco-signups&view=edit&id=' . $signup->id)); ?>">
                                <?php echo esc_html($signup->event_name); ?>
                            </a>
                        </strong>
                    </td>
                    <td><?php echo esc_html(mysql2date('M j, Y g:i A', $signup->event_date)); ?></td>
                    <td><?php echo esc_html($capacity_text); ?></td>
                    <td><?php echo esc_html($payment_text); ?></td>
                    <td>
                        <span class="notice notice-<?php echo esc_attr($status_class); ?> inline" style="padding: 5px 10px; margin: 0;">
                            <?php echo esc_html($status_text); ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=mypco-signups&view=edit&id=' . $signup->id)); ?>" 
                           class="button button-small">
                            <?php _e('Edit', 'mypco-online'); ?>
                        </a>
                        
                        <?php if ($signup->google_form_url): ?>
                            <a href="<?php echo esc_url($signup->google_form_url); ?>" 
                               class="button button-small" 
                               target="_blank">
                                <?php _e('View Form', 'mypco-online'); ?>
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=mypco-signups&action=delete&id=' . $signup->id), 'delete_signup_' . $signup->id)); ?>" 
                           class="button button-small" 
                           onclick="return confirm('<?php esc_attr_e('Delete this signup and all its registrations?', 'mypco-online'); ?>');">
                            <?php _e('Delete', 'mypco-online'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
