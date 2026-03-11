<?php
/**
 * Registrations List Template
 *
 * Shows signups that have registrations with counts.
 *
 * Available variables:
 * - $signups (array) - Signups with registration counts
 */

defined('ABSPATH') || exit;
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Event Registrations', 'mypco-online'); ?></h1>
    <hr class="wp-header-end">

    <?php if (empty($signups)): ?>
        <div class="notice notice-info">
            <p>
                <?php _e('No registrations yet.', 'mypco-online'); ?> 
                <a href="<?php echo esc_url(admin_url('admin.php?page=mypco-signups')); ?>">
                    <?php _e('Create a signup', 'mypco-online'); ?>
                </a> 
                <?php _e('to get started.', 'mypco-online'); ?>
            </p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th style="width: 60px;"><?php _e('ID', 'mypco-online'); ?></th>
                <th><?php _e('Event Name', 'mypco-online'); ?></th>
                <th style="width: 150px;"><?php _e('Date', 'mypco-online'); ?></th>
                <th style="width: 120px;"><?php _e('Registered', 'mypco-online'); ?></th>
                <th style="width: 100px;"><?php _e('Waitlist', 'mypco-online'); ?></th>
                <th style="width: 150px;"><?php _e('Actions', 'mypco-online'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($signups as $signup):
                $capacity_display = $signup->max_attendees > 0 ? " / {$signup->max_attendees}" : "";
                $waitlist_display = $signup->waitlist_count > 0 ? $signup->waitlist_count : '—';
                ?>
                <tr>
                    <td><?php echo intval($signup->id); ?></td>
                    <td>
                        <strong>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=mypco-registrations&view=registrants&signup_id=' . $signup->id)); ?>">
                                <?php echo esc_html($signup->event_name); ?>
                            </a>
                        </strong>
                    </td>
                    <td><?php echo esc_html(mysql2date('M j, Y', $signup->event_date)); ?></td>
                    <td><strong><?php echo intval($signup->confirmed_count) . esc_html($capacity_display); ?></strong></td>
                    <td><?php echo esc_html($waitlist_display); ?></td>
                    <td>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=mypco-registrations&view=registrants&signup_id=' . $signup->id)); ?>" 
                           class="button button-small">
                            <?php _e('View Registrants', 'mypco-online'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
