<?php
/**
 * Standalone Calendar Month View Template
 *
 * Renders only the month view without the view switcher.
 * Used by the [simplepco_calendar_month] shortcode.
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

// Month view is always active in standalone mode
$month_active = ' active';
?>

<div class="pco-wrapper pco-wrapper-standalone" data-initial-view="month" data-standalone="month">
    <!-- Header with category filter (no view switcher) -->
    <div class="pco-header">
        <div class="pco-category-dropdown">
            <select id="pco-category-filter">
                <option value=""><?php _e('All Categories', 'simplepco'); ?></option>
                <?php if (!empty($tags)): ?>
                    <?php
                    $current_group = '';
                    foreach ($tags as $tag):
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
    </div>

    <div class="pco-layout-grid pco-grid-full-width">
        <!-- Main content area -->
        <div class="pco-main-content">
            <?php include SIMPLEPCO_PLUGIN_DIR . 'templates/calendar/public/event-month.php'; ?>

            <!-- Event Detail View -->
            <?php include SIMPLEPCO_PLUGIN_DIR . 'templates/calendar/public/event-detail.php'; ?>
        </div>
    </div>
</div>

<!-- Output expanded events for JavaScript -->
<script>
    window.pcoExpandedEvents = <?php echo json_encode($expanded_events); ?>;
</script>
