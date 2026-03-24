<?php
/**
 * Service Plan Details Template
 *
 * Displays detailed information for a single service plan including team members.
 *
 * Available variables:
 * - $plan_id (string) - Plan ID
 * - $title (string) - Plan title
 * - $series (string) - Series title
 * - $day_str (string) - Day of week
 * - $date_str (string) - Formatted date
 * - $time_str (string) - Formatted time
 * - $service_type_id (string) - Service type ID
 * - $pco_edit_link (string) - Link to edit in PCO
 * - $team_members (array) - Array of team member data
 * - $team_summary (array) - Summary of teams with counts
 * - $status_counts (array) - Counts by status
 * - $team_name_map (array) - Team ID to name mapping
 * - $error (string|null) - Error message if any
 */

defined('ABSPATH') || exit;

// Get filter parameters
$filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : 'all';
$filter_team = isset($_GET['filter_team']) ? sanitize_text_field($_GET['filter_team']) : 'all';
$view_mode = isset($_GET['view_mode']) ? sanitize_text_field($_GET['view_mode']) : 'team';
?>

<div class="wrap">
    
    <?php if (isset($error)): ?>
        <h1><?php _e('Error', 'simplepco'); ?></h1>
        <p><?php echo esc_html($error); ?></p>
        <p><a href="<?php echo esc_url(admin_url('admin.php?page=simplepco-services')); ?>" class="button">
            <?php _e('← Back to Plans', 'simplepco'); ?>
        </a></p>
        <?php return; ?>
    <?php endif; ?>

    <!-- Breadcrumb Navigation -->
    <div style="margin-bottom: 15px;">
        <a href="<?php echo esc_url(admin_url('admin.php?page=simplepco-services')); ?>" class="button">
            ← <?php _e('Back to All Plans', 'simplepco'); ?>
        </a>
    </div>

    <!-- Plan Header -->
    <h1 class="wp-heading-inline">
        <?php echo esc_html($title); ?>
    </h1>

    <?php if ($series): ?>
        <p style="font-size: 1.1em; color: #666; margin-top: 5px;">
            <?php _e('Series:', 'simplepco'); ?> <?php echo esc_html($series); ?>
        </p>
    <?php endif; ?>

    <hr class="wp-header-end">

    <!-- Plan Meta Information -->
    <div class="card" style="margin-bottom: 20px;">
        <h2><?php _e('Plan Information', 'simplepco'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Date', 'simplepco'); ?></th>
                <td><strong><?php echo esc_html($day_str . ', ' . $date_str); ?></strong></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Time', 'simplepco'); ?></th>
                <td><?php echo esc_html($time_str); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Actions', 'simplepco'); ?></th>
                <td>
                    <a href="<?php echo esc_url($pco_edit_link); ?>" class="button" target="_blank" rel="noopener">
                        <?php _e('Edit Plan in Planning Center ↗', 'simplepco'); ?>
                    </a>
                </td>
            </tr>
        </table>
    </div>

    <!-- Team Members Section -->
    <h2><?php _e('Team Members', 'simplepco'); ?></h2>

    <!-- Status Filter Tabs -->
    <ul class="subsubsub">
        <li class="all">
            <a href="<?php echo esc_url(add_query_arg('filter_status', 'all', remove_query_arg('filter_team'))); ?>" 
               <?php echo $filter_status === 'all' ? 'class="current"' : ''; ?>>
                <?php _e('All', 'simplepco'); ?> 
                <span class="count">(<?php echo intval($status_counts['all']); ?>)</span>
            </a> | 
        </li>
        <li class="confirmed">
            <a href="<?php echo esc_url(add_query_arg('filter_status', 'C', remove_query_arg('filter_team'))); ?>" 
               <?php echo $filter_status === 'C' ? 'class="current"' : ''; ?>>
                <?php _e('Confirmed', 'simplepco'); ?> 
                <span class="count">(<?php echo intval($status_counts['C']); ?>)</span>
            </a> | 
        </li>
        <li class="unconfirmed">
            <a href="<?php echo esc_url(add_query_arg('filter_status', 'U', remove_query_arg('filter_team'))); ?>" 
               <?php echo $filter_status === 'U' ? 'class="current"' : ''; ?>>
                <?php _e('Unconfirmed', 'simplepco'); ?> 
                <span class="count">(<?php echo intval($status_counts['U']); ?>)</span>
            </a> | 
        </li>
        <li class="declined">
            <a href="<?php echo esc_url(add_query_arg('filter_status', 'D', remove_query_arg('filter_team'))); ?>" 
               <?php echo $filter_status === 'D' ? 'class="current"' : ''; ?>>
                <?php _e('Declined', 'simplepco'); ?> 
                <span class="count">(<?php echo intval($status_counts['D']); ?>)</span>
            </a>
        </li>
    </ul>

    <!-- View Mode and Team Filter -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <!-- View Mode Toggle -->
            <select name="view_mode" id="view-mode" 
                    onchange="window.location.href='<?php echo esc_js(remove_query_arg('filter_team')); ?>' + '&view_mode=' + this.value;">
                <option value="team" <?php selected($view_mode, 'team'); ?>>
                    <?php _e('Group by Team', 'simplepco'); ?>
                </option>
                <option value="alpha" <?php selected($view_mode, 'alpha'); ?>>
                    <?php _e('Alphabetical', 'simplepco'); ?>
                </option>
            </select>

            <!-- Team Filter (only shown in alphabetical view) -->
            <?php if ($view_mode === 'alpha'): ?>
                <select name="filter_team" id="filter-team" 
                        onchange="window.location.href='<?php echo esc_js(remove_query_arg('filter_team')); ?>' + (this.value !== 'all' ? '&filter_team=' + encodeURIComponent(this.value) : '');">
                    <option value="all"><?php _e('All Teams', 'simplepco'); ?></option>
                    <?php foreach ($team_name_map as $team_id => $team_name): ?>
                        <option value="<?php echo esc_attr($team_id); ?>" <?php selected($filter_team, $team_id); ?>>
                            <?php echo esc_html($team_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>

        <div class="alignright actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=simplepco-services&view=clearstream_compose&plan_id=' . $plan_id . '&from=plan')); ?>" 
               class="button button-primary">
                <?php _e('📱 Message Team', 'simplepco'); ?>
            </a>
        </div>
    </div>

    <?php if (empty($team_members)): ?>
        <p><?php _e('No team members found for this plan.', 'simplepco'); ?></p>
    <?php else: ?>

        <?php if ($view_mode === 'team'): ?>
            <!-- GROUP BY TEAM VIEW -->
            <?php
            // Group members by team
            $members_by_team = [];
            foreach ($team_members as $member) {
                // Apply status filter
                if ($filter_status !== 'all' && $member['status'] !== $filter_status) {
                    continue;
                }
                
                $team = $member['team_name'];
                if (!isset($members_by_team[$team])) {
                    $members_by_team[$team] = [];
                }
                $members_by_team[$team][] = $member;
            }

            // Display each team
            foreach ($members_by_team as $team_name => $members):
                ?>
                <div class="card" style="margin-bottom: 20px;">
                    <h3><?php echo esc_html($team_name); ?> 
                        <span style="color: #666; font-weight: normal; font-size: 0.9em;">
                            (<?php echo count($members); ?>)
                        </span>
                    </h3>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                        <tr>
                            <th><?php _e('Name', 'simplepco'); ?></th>
                            <th><?php _e('Position', 'simplepco'); ?></th>
                            <th style="width: 120px;"><?php _e('Status', 'simplepco'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($member['name']); ?></strong>
                                </td>
                                <td>
                                    <?php echo esc_html($member['position'] ?: '—'); ?>
                                </td>
                                <td>
                                    <?php
                                    $status_labels = [
                                        'C' => '<span style="color: green;">✓ ' . __('Confirmed', 'simplepco') . '</span>',
                                        'U' => '<span style="color: orange;">? ' . __('Unconfirmed', 'simplepco') . '</span>',
                                        'D' => '<span style="color: red;">✗ ' . __('Declined', 'simplepco') . '</span>'
                                    ];
                                    echo $status_labels[$member['status']] ?? $member['status'];
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <!-- ALPHABETICAL VIEW -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <th><?php _e('Name', 'simplepco'); ?></th>
                    <th><?php _e('Team', 'simplepco'); ?></th>
                    <th><?php _e('Position', 'simplepco'); ?></th>
                    <th style="width: 120px;"><?php _e('Status', 'simplepco'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php 
                $row_count = 0;
                foreach ($team_members as $member):
                    // Apply filters
                    if ($filter_status !== 'all' && $member['status'] !== $filter_status) {
                        continue;
                    }
                    if ($filter_team !== 'all' && $member['team_id'] !== $filter_team) {
                        continue;
                    }
                    
                    $row_count++;
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($member['name']); ?></strong>
                        </td>
                        <td>
                            <?php echo esc_html($member['team_name']); ?>
                        </td>
                        <td>
                            <?php echo esc_html($member['position'] ?: '—'); ?>
                        </td>
                        <td>
                            <?php
                            $status_labels = [
                                'C' => '<span style="color: green;">✓ ' . __('Confirmed', 'simplepco') . '</span>',
                                'U' => '<span style="color: orange;">? ' . __('Unconfirmed', 'simplepco') . '</span>',
                                'D' => '<span style="color: red;">✗ ' . __('Declined', 'simplepco') . '</span>'
                            ];
                            echo $status_labels[$member['status']] ?? $member['status'];
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($row_count === 0): ?>
                <p><?php _e('No team members match the selected filters.', 'simplepco'); ?></p>
            <?php endif; ?>

        <?php endif; ?>

    <?php endif; ?>

</div>
