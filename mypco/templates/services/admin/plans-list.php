<?php
/**
 * Service Plans List Template
 *
 * Displays all service plans with filtering and sorting options.
 *
 * Available variables:
 * - $service_types (array) - List of service types
 * - $all_plans (array) - All plans data
 * - $type_counts (array) - Count of plans per type
 * - $available_months (array) - Available months for filtering
 * - $filter_type (string) - Current type filter
 * - $filter_month (string) - Current month filter
 * - $orderby (string) - Current sort column
 * - $order (string) - Current sort order (asc/desc)
 * - $success_message (array|null) - Success message data
 * - $error_message (array|null) - Error message data
 * - $error (string|null) - General error message
 */

defined('ABSPATH') || exit;

// Helper function for sortable URLs
function mypco_get_services_sort_url($column, $current_orderby, $current_order, $filter_type, $filter_month) {
    $new_order = ($current_orderby === $column && $current_order === 'asc') ? 'desc' : 'asc';
    $url = admin_url('admin.php?page=mypco-services');
    $url = add_query_arg('orderby', $column, $url);
    $url = add_query_arg('order', $new_order, $url);
    if ($filter_type !== 'all') {
        $url = add_query_arg('filter_type', $filter_type, $url);
    }
    if ($filter_month !== 'all') {
        $url = add_query_arg('filter_month', $filter_month, $url);
    }
    return $url;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Service Plans', 'mypco-online'); ?></h1>
    <hr class="wp-header-end">

    <?php if (isset($error)): ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($error); ?></p>
        </div>
        <?php return; ?>
    <?php endif; ?>

    <?php
    // Display success messages
    if ($success_message):
        if (is_array($success_message)):
            $count = $success_message['count'] ?? 0;
            $status = $success_message['status'] ?? 'sent';
            $scheduled_at = $success_message['scheduled_at'] ?? null;

            if ($status === 'scheduled' && $scheduled_at):
                $formatted_time = date('M j, Y \a\t g:i A', strtotime($scheduled_at));
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php _e('Success!', 'mypco-online'); ?></strong>
                        <?php printf(__('Message scheduled for %s to %d recipients via Clearstream.', 'mypco-online'),
                                esc_html($formatted_time), intval($count)); ?></p>
                </div>
            <?php else: ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php _e('Success!', 'mypco-online'); ?></strong>
                        <?php printf(__('Message sent to %d recipients via Clearstream.', 'mypco-online'), intval($count)); ?></p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Old format (single number) -->
            <div class="notice notice-success is-dismissible">
                <p><strong><?php _e('Success!', 'mypco-online'); ?></strong>
                    <?php printf(__('Message sent to %d recipients via Clearstream.', 'mypco-online'), intval($success_message)); ?></p>
            </div>
        <?php endif; ?>
    <?php endif;

    // Display error messages
    if ($error_message): ?>
        <div class="notice notice-error is-dismissible">
            <p><strong><?php printf(__('Clearstream API Error (Code %d):', 'mypco-online'), intval($error_message['code'])); ?></strong>
                <?php echo esc_html($error_message['message']); ?></p>
        </div>
    <?php endif; ?>

    <!-- SERVICE TYPE FILTER TABS -->
    <ul class="subsubsub">
        <li class="all">
            <a href="<?php echo esc_url(admin_url('admin.php?page=mypco-services')); ?>"
                    <?php echo $filter_type === 'all' ? 'class="current"' : ''; ?>>
                <?php _e('All', 'mypco-online'); ?>
                <span class="count">(<?php echo intval($type_counts['all']); ?>)</span>
            </a>
            <?php if (!empty($service_types)): ?> | <?php endif; ?>
        </li>
        <?php foreach ($service_types as $index => $type):
            $type_id = $type['id'];
            $type_name = $type['attributes']['name'] ?? 'Unknown';
            $count = $type_counts[$type_id] ?? 0;
            ?>
            <li class="type-<?php echo esc_attr($type_id); ?>">
                <a href="<?php echo esc_url(add_query_arg('filter_type', $type_id, admin_url('admin.php?page=mypco-services'))); ?>"
                        <?php echo $filter_type === $type_id ? 'class="current"' : ''; ?>>
                    <?php echo esc_html($type_name); ?>
                    <span class="count">(<?php echo intval($count); ?>)</span>
                </a>
                <?php if ($index < count($service_types) - 1): ?> | <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if (empty($all_plans)): ?>
        <p><?php _e('No upcoming plans found.', 'mypco-online'); ?></p>
    <?php else: ?>

        <!-- FILTERS -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="filter_month" id="filter-month"
                        onchange="window.location.href='<?php echo esc_js(admin_url('admin.php?page=mypco-services')); ?>' + (this.value !== 'all' ? '&filter_month=' + encodeURIComponent(this.value) : '') + '<?php echo $filter_type !== 'all' ? '&filter_type=' . esc_js($filter_type) : ''; ?>' + '<?php echo '&orderby=' . esc_js($orderby) . '&order=' . esc_js($order); ?>';">
                    <option value="all"><?php _e('All Dates', 'mypco-online'); ?></option>
                    <?php foreach ($available_months as $month): ?>
                        <option value="<?php echo esc_attr($month); ?>" <?php selected($filter_month, $month); ?>>
                            <?php echo esc_html($month); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- PLANS TABLE -->
        <table class="wp-list-table widefat fixed striped table-view-list posts">
            <thead>
            <tr>
                <th class="manage-column sortable <?php echo $orderby === 'date' ? 'sorted' : ''; ?> <?php echo $orderby === 'date' ? $order : 'asc'; ?>" style="width: 180px;">
                    <a href="<?php echo esc_url(mypco_get_services_sort_url('date', $orderby, $order, $filter_type, $filter_month)); ?>">
                        <span><?php _e('Date', 'mypco-online'); ?></span>
                        <span class="sorting-indicators">
                            <span class="sorting-indicator asc" aria-hidden="true"></span>
                            <span class="sorting-indicator desc" aria-hidden="true"></span>
                        </span>
                    </a>
                </th>
                <th class="manage-column column-title column-primary sortable <?php echo $orderby === 'title' ? 'sorted' : ''; ?> <?php echo $orderby === 'title' ? $order : 'asc'; ?>">
                    <a href="<?php echo esc_url(mypco_get_services_sort_url('title', $orderby, $order, $filter_type, $filter_month)); ?>">
                        <span><?php _e('Title', 'mypco-online'); ?></span>
                        <span class="sorting-indicators">
                            <span class="sorting-indicator asc" aria-hidden="true"></span>
                            <span class="sorting-indicator desc" aria-hidden="true"></span>
                        </span>
                    </a>
                </th>
                <th class="manage-column sortable <?php echo $orderby === 'type_name' ? 'sorted' : ''; ?> <?php echo $orderby === 'type_name' ? $order : 'asc'; ?>" style="width: 200px;">
                    <a href="<?php echo esc_url(mypco_get_services_sort_url('type_name', $orderby, $order, $filter_type, $filter_month)); ?>">
                        <span><?php _e('Service Type', 'mypco-online'); ?></span>
                        <span class="sorting-indicators">
                            <span class="sorting-indicator asc" aria-hidden="true"></span>
                            <span class="sorting-indicator desc" aria-hidden="true"></span>
                        </span>
                    </a>
                </th>
            </tr>
            </thead>

            <tbody>
            <?php
            $row_count = 0;
            foreach ($all_plans as $plan):
                // Apply filters
                if ($filter_type !== 'all' && $filter_type !== $plan['type_id']) {
                    continue;
                }
                if ($filter_month !== 'all' && $filter_month !== $plan['month_year']) {
                    continue;
                }

                $row_count++;
                $details_url = admin_url('admin.php?page=mypco-services&view=plan_details&plan_id=' . $plan['plan_id']);
                ?>
                <tr>
                    <td data-colname="<?php esc_attr_e('Date', 'mypco-online'); ?>">
                        <?php echo esc_html($plan['date_str']); ?><br>
                        <small style="color: #666;"><?php echo esc_html($plan['time_str']); ?></small>
                    </td>
                    <td class="title column-title column-primary" data-colname="<?php esc_attr_e('Title', 'mypco-online'); ?>">
                        <strong>
                            <a href="<?php echo esc_url($details_url); ?>" class="row-title">
                                <?php echo esc_html($plan['title']); ?>
                            </a>
                        </strong>
                        <?php if ($plan['series'] !== '—'): ?>
                            <br><small style="color: #666;"><?php _e('Series:', 'mypco-online'); ?> <?php echo esc_html($plan['series']); ?></small>
                        <?php endif; ?>

                        <!-- Row Actions -->
                        <div class="row-actions">
                            <span class="view">
                                <a href="<?php echo esc_url($details_url); ?>">
                                    <?php _e('View Details', 'mypco-online'); ?>
                                </a> |
                            </span>
                            <span class="edit">
                                <a href="<?php echo esc_url($plan['pco_edit_link']); ?>" target="_blank" rel="noopener">
                                    <?php _e('Edit in PCO', 'mypco-online'); ?> ↗
                                </a>
                            </span>
                        </div>

                        <button type="button" class="toggle-row">
                            <span class="screen-reader-text"><?php _e('Show more details', 'mypco-online'); ?></span>
                        </button>
                    </td>
                    <td data-colname="<?php esc_attr_e('Service Type', 'mypco-online'); ?>">
                        <span class="post-state"><?php echo esc_html($plan['type_name']); ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($row_count === 0): ?>
            <p><?php _e('No plans match the selected filters.', 'mypco-online'); ?></p>
        <?php endif; ?>

    <?php endif; ?>
</div>
