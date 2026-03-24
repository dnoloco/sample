<?php
/**
 * Team Reports Template
 *
 * Displays reports on team member participation and statistics.
 *
 * Available variables (to be implemented):
 * - $date_range (array) - Start and end dates
 * - $team_stats (array) - Statistics by team
 * - $person_stats (array) - Statistics by person
 */

defined('ABSPATH') || exit;
?>

<div class="wrap">
    <h1><?php _e('Team Reports', 'simplepco'); ?></h1>
    <hr class="wp-header-end">

    <div class="card">
        <h2><?php _e('Coming Soon', 'simplepco'); ?></h2>
        <p><?php _e('Team reports functionality will be available in a future update.', 'simplepco'); ?></p>
        
        <p><?php _e('Planned features:', 'simplepco'); ?></p>
        <ul>
            <li><?php _e('Team member participation over time', 'simplepco'); ?></li>
            <li><?php _e('Confirmation rates by team', 'simplepco'); ?></li>
            <li><?php _e('Declined requests by person', 'simplepco'); ?></li>
            <li><?php _e('Custom date range filtering', 'simplepco'); ?></li>
            <li><?php _e('Export to CSV', 'simplepco'); ?></li>
        </ul>

        <p><a href="<?php echo esc_url(admin_url('admin.php?page=simplepco-services')); ?>" class="button">
            <?php _e('← Back to Service Plans', 'simplepco'); ?>
        </a></p>
    </div>
</div>
