<?php
/**
 * Calendar Display Template - Main Container
 *
 * Available variables:
 * - $featured_events (array)
 * - $regular_events (array)
 * - $all_events (array)
 * - $event_map (array)
 * - $expanded_events (array)
 * - $current_month (string)
 * - $timezone (string)
 * - $tags (array) - Categories/tags from Planning Center
 */

defined('ABSPATH') || exit;

// Read saved view from cookie (WordPress-compliant: server-side rendering)
$saved_view = isset($_COOKIE['pco_calendar_view']) ? sanitize_key($_COOKIE['pco_calendar_view']) : 'list';
$valid_views = ['list', 'month', 'gallery'];
if (!in_array($saved_view, $valid_views, true)) {
    $saved_view = 'list';
}

// Determine active states
$list_active = ($saved_view === 'list') ? ' active' : '';
$month_active = ($saved_view === 'month') ? ' active' : '';
$gallery_active = ($saved_view === 'gallery') ? ' active' : '';

// Determine if sidebar should be hidden (month and gallery views)
$hide_sidebar = in_array($saved_view, ['month', 'gallery'], true);
$sidebar_class = $hide_sidebar ? ' pco-sidebar-hidden' : '';
$grid_class = $hide_sidebar ? ' pco-grid-full-width' : '';
?>

<div class="pco-wrapper" data-initial-view="<?php echo esc_attr($saved_view); ?>">
    <!-- Header with view switcher -->
    <div class="pco-header">
        <div class="pco-category-dropdown">
            <select id="pco-category-filter">
                <option value=""><?php _e('All Categories', 'mypco-online'); ?></option>
                <?php if (!empty($tags)): ?>
                    <?php
                    $current_group = '';
                    foreach ($tags as $tag):
                        // Add optgroup for tag groups
                        if ($tag['group_name'] !== $current_group):
                            if ($current_group !== ''):
                                echo '</optgroup>';
                            endif;
                            if (!empty($tag['group_name'])):
                                $current_group = $tag['group_name'];
                                echo '<optgroup label="' . esc_attr($current_group) . '">';
                            endif;
                        endif;
                    ?>
                        <option value="<?php echo esc_attr($tag['id']); ?>">
                            <?php echo esc_html($tag['name']); ?>
                        </option>
                    <?php endforeach; ?>
                    <?php if ($current_group !== ''): ?>
                        </optgroup>
                    <?php endif; ?>
                <?php endif; ?>
            </select>
        </div>
        <div class="pco-view-switcher">
            <button class="pco-view-btn<?php echo esc_attr($list_active); ?>" data-target="pco-view-list">
                <?php _e('List', 'mypco-online'); ?>
            </button>
            <button class="pco-view-btn<?php echo esc_attr($month_active); ?>" data-target="pco-view-month">
                <?php _e('Month', 'mypco-online'); ?>
            </button>
            <button class="pco-view-btn<?php echo esc_attr($gallery_active); ?>" data-target="pco-view-gallery">
                <?php _e('Gallery', 'mypco-online'); ?>
            </button>
        </div>
    </div>

    <div class="pco-layout-grid<?php echo esc_attr($grid_class); ?>">
        <!-- Sidebar with mini calendar -->
        <div class="pco-sidebar<?php echo esc_attr($sidebar_class); ?>">
            <div class="pco-mini-cal">
                <div class="pco-mini-cal-header">
                    <span class="pco-mini-cal-nav" data-nav="prev" title="<?php esc_attr_e('Previous month', 'mypco-online'); ?>">&lt;</span>
                    <span class="pco-mini-cal-month-display"><?php echo esc_html($current_month); ?></span>
                    <span class="pco-mini-cal-nav" data-nav="next" title="<?php esc_attr_e('Next month', 'mypco-online'); ?>">&gt;</span>
                </div>
                <div class="pco-mini-cal-grid">
                    <span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span>
                </div>
            </div>
        </div>

        <!-- Main content area -->
        <div class="pco-main-content">
            <!-- List View -->
            <?php include MYPCO_PLUGIN_DIR . 'templates/calendar/public/event-list.php'; ?>

            <!-- Month View -->
            <?php include MYPCO_PLUGIN_DIR . 'templates/calendar/public/event-month.php'; ?>

            <!-- Gallery View -->
            <?php include MYPCO_PLUGIN_DIR . 'templates/calendar/public/event-gallery.php'; ?>

            <!-- Event Detail View -->
            <?php include MYPCO_PLUGIN_DIR . 'templates/calendar/public/event-detail.php'; ?>
        </div>
    </div>
</div>

<!-- Output expanded events for JavaScript -->
<script>
    window.pcoExpandedEvents = <?php echo json_encode($expanded_events); ?>;
</script>
