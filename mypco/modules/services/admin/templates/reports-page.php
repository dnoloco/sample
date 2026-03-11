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
    <h1><?php _e('Team Reports', 'mypco-online'); ?></h1>
    <hr class="wp-header-end">

    <div class="card">
        <h2><?php _e('Coming Soon', 'mypco-online'); ?></h2>
        <p><?php _e('Team reports functionality will be available in a future update.', 'mypco-online'); ?></p>
        
        <p><?php _e('Planned features:', 'mypco-online'); ?></p>
        <ul>
            <li><?php _e('Team member participation over time', 'mypco-online'); ?></li>
            <li><?php _e('Confirmation rates by team', 'mypco-online'); ?></li>
            <li><?php _e('Declined requests by person', 'mypco-online'); ?></li>
            <li><?php _e('Custom date range filtering', 'mypco-online'); ?></li>
            <li><?php _e('Export to CSV', 'mypco-online'); ?></li>
        </ul>

        <p><a href="<?php echo esc_url(admin_url('admin.php?page=mypco-services')); ?>" class="button">
            <?php _e('← Back to Service Plans', 'mypco-online'); ?>
        </a></p>
    </div>
</div>
