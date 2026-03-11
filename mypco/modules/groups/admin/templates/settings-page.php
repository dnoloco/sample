<?php
/**
 * Groups Settings Template
 *
 * Admin settings page for Groups module.
 *
 * Available variables:
 * - $cache_cleared (bool) - Whether cache was just cleared
 */

defined('ABSPATH') || exit;
?>

<div class="wrap">
    <h1><?php _e('Groups Settings', 'mypco-online'); ?></h1>
    <hr class="wp-header-end">

    <?php if ($cache_cleared): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Groups cache cleared successfully!', 'mypco-online'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Shortcode Usage -->
    <div class="card" style="max-width: 800px;">
        <h2><?php _e('Shortcode Usage', 'mypco-online'); ?></h2>
        
        <h3><?php _e('Basic Usage', 'mypco-online'); ?></h3>
        <code>[pco_groups]</code>
        <p class="description"><?php _e('Displays up to 10 groups from Planning Center Groups', 'mypco-online'); ?></p>

        <h3 style="margin-top: 20px;"><?php _e('With Parameters', 'mypco-online'); ?></h3>
        <code>[pco_groups count="20"]</code>
        <p class="description"><?php _e('Display 20 groups', 'mypco-online'); ?></p>

        <code>[pco_groups campus="Main Campus"]</code>
        <p class="description"><?php _e('Filter groups by campus', 'mypco-online'); ?></p>

        <h3 style="margin-top: 20px;"><?php _e('Available Parameters', 'mypco-online'); ?></h3>
        <table class="widefat" style="margin-top: 10px;">
            <thead>
            <tr>
                <th><?php _e('Parameter', 'mypco-online'); ?></th>
                <th><?php _e('Description', 'mypco-online'); ?></th>
                <th><?php _e('Default', 'mypco-online'); ?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><code>count</code></td>
                <td><?php _e('Number of groups to display', 'mypco-online'); ?></td>
                <td>10</td>
            </tr>
            <tr>
                <td><code>campus</code></td>
                <td><?php _e('Filter by campus name (optional)', 'mypco-online'); ?></td>
                <td><?php _e('None (show all)', 'mypco-online'); ?></td>
            </tr>
            </tbody>
        </table>
    </div>

    <!-- Cache Management -->
    <div class="card" style="max-width: 800px; margin-top: 20px;">
        <h2><?php _e('Cache Management', 'mypco-online'); ?></h2>
        <p><?php _e('Groups data is cached for 1 hour to improve performance. Clear the cache if you need to see immediate updates from Planning Center.', 'mypco-online'); ?></p>
        
        <form method="POST">
            <?php wp_nonce_field('clear_groups_cache'); ?>
            <p>
                <input type="submit" name="clear_groups_cache" class="button button-secondary" 
                       value="<?php esc_attr_e('Clear Groups Cache', 'mypco-online'); ?>" 
                       onclick="return confirm('<?php esc_attr_e('Clear all cached groups data?', 'mypco-online'); ?>');">
            </p>
        </form>
    </div>

    <!-- Requirements -->
    <div class="card" style="max-width: 800px; margin-top: 20px;">
        <h2><?php _e('Requirements', 'mypco-online'); ?></h2>
        <ul style="list-style: disc; margin-left: 20px;">
            <li><?php _e('Planning Center Groups account', 'mypco-online'); ?></li>
            <li><?php _e('PCO Groups API access enabled in Planning Center', 'mypco-online'); ?></li>
            <li><?php _e('API credentials configured in MyPCO Settings', 'mypco-online'); ?></li>
            <li><?php _e('Groups must have public web URLs for "View Details" links', 'mypco-online'); ?></li>
        </ul>
    </div>

    <!-- Troubleshooting -->
    <div class="card" style="max-width: 800px; margin-top: 20px;">
        <h2><?php _e('Troubleshooting', 'mypco-online'); ?></h2>
        
        <h3><?php _e('No groups showing?', 'mypco-online'); ?></h3>
        <ol style="margin-left: 20px;">
            <li><?php _e('Verify PCO Groups API access is enabled in Planning Center', 'mypco-online'); ?></li>
            <li><?php _e('Check that API credentials are correct', 'mypco-online'); ?></li>
            <li><?php _e('Ensure groups are published and active', 'mypco-online'); ?></li>
            <li><?php _e('Try clearing the cache', 'mypco-online'); ?></li>
        </ol>

        <h3 style="margin-top: 15px;"><?php _e('Groups not updating?', 'mypco-online'); ?></h3>
        <p><?php _e('Clear the cache to fetch fresh data from Planning Center.', 'mypco-online'); ?></p>
    </div>
</div>
