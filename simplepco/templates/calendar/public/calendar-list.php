<?php
/**
 * Standalone Calendar List View Template - Accordion Style
 *
 * Renders events in an accordion list, one month at a time.
 * Clicking an event expands it to show full details while hiding
 * other events. An X button closes the expanded event.
 *
 * Used by the [simplepco_calendar_list] shortcode.
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

if (!function_exists('simplepco_get_location_name')) {
    function simplepco_get_location_name($location) {
        if (empty($location)) return '';
        if (strpos($location, ' - ') !== false) {
            return trim(substr($location, 0, strpos($location, ' - ')));
        }
        return $location;
    }
}

// Group events by month, filtering out past events
$tz = new DateTimeZone('America/Chicago');
$today = new DateTime('today', $tz);
$current_month_key = $today->format('Y-m');
$events_by_month = [];
$available_months = [];

if (!empty($regular_events)) {
    foreach ($regular_events as $event) {
        try {
            $start = new DateTime($event['starts_at'], new DateTimeZone('UTC'));
            $start->setTimezone($tz);

            if ($start < $today) {
                continue;
            }

            $month_key = $start->format('Y-m');
            $month_display = $start->format('F Y');

            if (!isset($events_by_month[$month_key])) {
                $events_by_month[$month_key] = [
                    'display' => $month_display,
                    'events' => []
                ];
                $available_months[] = $month_key;
            }

            // Store parsed date info with each event for the template
            $event['_day_abbr'] = strtoupper($start->format('D'));
            $event['_day_num'] = $start->format('j');
            $event['_month_abbr'] = strtoupper($start->format('M'));
            $event['_day_key'] = $start->format('Y-m-d');

            // Determine if event is "this week"
            $end_of_week = clone $today;
            $end_of_week->modify('+6 days');
            $event['_this_week'] = ($start >= $today && $start <= $end_of_week);

            // Track position for nearest-event highlighting
            $event['_event_index'] = count($events_by_month[$month_key]['events']);

            $events_by_month[$month_key]['events'][] = $event;
        } catch (Exception $e) {
            continue;
        }
    }
}

// Determine which month to show initially
$initial_month = !empty($available_months) ? $available_months[0] : $current_month_key;
$initial_month_display = isset($events_by_month[$initial_month])
    ? $events_by_month[$initial_month]['display']
    : $today->format('F Y');
?>

<div class="pco-accordion-wrapper" data-current-month="<?php echo esc_attr($initial_month); ?>">

    <!-- Month navigation + category filter -->
    <div class="pco-accordion-header">
        <div class="pco-accordion-nav-row">
            <button class="pco-accordion-nav-btn" data-dir="prev" aria-label="<?php esc_attr_e('Previous month', 'simplepco'); ?>">&#8249;</button>
            <h2 class="pco-accordion-month-title"><?php echo esc_html($initial_month_display); ?></h2>
            <button class="pco-accordion-nav-btn" data-dir="next" aria-label="<?php esc_attr_e('Next month', 'simplepco'); ?>">&#8250;</button>
        </div>

    </div>

    <!-- Event months -->
    <?php if (empty($events_by_month)): ?>
        <div class="pco-accordion-empty">
            <p><?php _e('No upcoming events scheduled.', 'simplepco'); ?></p>
        </div>
    <?php else: ?>
        <?php $nearest_found = false; ?>
        <?php foreach ($events_by_month as $month_key => $month_data):
            $is_active_month = ($month_key === $initial_month);
        ?>
        <div class="pco-accordion-month<?php echo $is_active_month ? ' active' : ''; ?>"
             data-month="<?php echo esc_attr($month_key); ?>"
             data-month-display="<?php echo esc_attr($month_data['display']); ?>">

            <?php foreach ($month_data['events'] as $event):
                $is_all_day = $event['is_all_day'] ?? false;
                $location_name = simplepco_get_location_name($event['location']);

                // Format time
                try {
                    $start_dt = new DateTime($event['starts_at'], new DateTimeZone('UTC'));
                    $start_dt->setTimezone($tz);

                    if ($is_all_day) {
                        $time_display = __('ALL DAY', 'simplepco');
                    } else {
                        $time_display = strtoupper($start_dt->format('gA'));
                    }
                } catch (Exception $e) {
                    $time_display = '';
                }

                // Parse address from location
                $address = '';
                if (!empty($event['location']) && strpos($event['location'], ' - ') !== false) {
                    $address = substr($event['location'], strpos($event['location'], ' - ') + 3);
                }

                $tag_ids_json = json_encode($event['tag_ids'] ?? []);
                $event_name_upper = strtoupper($event['name']);
                $is_nearest = (!$nearest_found && $event['_event_index'] === 0);
                if ($is_nearest) $nearest_found = true;
                $badge_class = $is_nearest ? 'pco-accordion-date-badge' : 'pco-accordion-date-badge pco-accordion-date-badge--light';

                // Build Google Maps search URL from location
                $maps_query = $location_name;
                if ($address) {
                    $maps_query .= ', ' . $address;
                }
                $maps_url = 'https://www.google.com/maps/search/' . rawurlencode($maps_query);
            ?>
            <div class="pco-accordion-item<?php echo $is_nearest ? ' pco-accordion-item--nearest' : ''; ?>" data-tag-ids='<?php echo esc_attr($tag_ids_json); ?>'>
                <!-- Collapsed card -->
                <button class="pco-accordion-row" type="button">
                    <span class="<?php echo esc_attr($badge_class); ?>">
                        <span class="pco-accordion-day-abbr"><?php echo esc_html($event['_day_abbr']); ?></span>
                        <span class="pco-accordion-day-num"><?php echo esc_html($event['_day_num']); ?></span>
                        <span class="pco-accordion-month-abbr"><?php echo esc_html($event['_month_abbr']); ?></span>
                    </span>
                    <span class="pco-accordion-event-info">
                        <span class="pco-accordion-event-name"><?php echo esc_html($event_name_upper); ?></span>
                        <span class="pco-accordion-event-meta-row">
                            <?php if ($time_display): ?>
                            <span class="pco-accordion-event-meta">
                                <svg class="pco-accordion-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <?php echo esc_html($time_display); ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($location_name): ?>
                            <span class="pco-accordion-event-meta">
                                <svg class="pco-accordion-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                <?php echo esc_html($location_name); ?>
                            </span>
                            <?php endif; ?>
                        </span>
                    </span>
                </button>

                <!-- Expanded detail panel -->
                <div class="pco-accordion-detail">
                    <div class="pco-accordion-detail-header">
                        <span class="<?php echo esc_attr($badge_class); ?>">
                            <span class="pco-accordion-day-abbr"><?php echo esc_html($event['_day_abbr']); ?></span>
                            <span class="pco-accordion-day-num"><?php echo esc_html($event['_day_num']); ?></span>
                            <span class="pco-accordion-month-abbr"><?php echo esc_html($event['_month_abbr']); ?></span>
                        </span>
                        <span class="pco-accordion-event-info">
                            <span class="pco-accordion-event-name"><?php echo esc_html($event_name_upper); ?></span>
                            <span class="pco-accordion-event-meta-row">
                                <?php if ($time_display): ?>
                                <span class="pco-accordion-event-meta">
                                    <svg class="pco-accordion-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    <?php echo esc_html($time_display); ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($location_name): ?>
                                <span class="pco-accordion-event-meta">
                                    <svg class="pco-accordion-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <?php echo esc_html($location_name); ?>
                                </span>
                                <?php endif; ?>
                            </span>
                        </span>
                        <button class="pco-accordion-close" type="button" aria-label="<?php esc_attr_e('Close', 'simplepco'); ?>">&times;</button>
                    </div>

                    <div class="pco-accordion-detail-body">
                        <?php if (!empty($event['description']) || !empty($event['summary'])): ?>
                        <div class="pco-accordion-detail-desc">
                            <?php echo wp_kses_post($event['description'] ?: $event['summary']); ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($location_name): ?>
                        <div class="pco-accordion-detail-location">
                            <svg class="pco-accordion-pin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <div>
                                <a href="<?php echo esc_url($maps_url); ?>" class="pco-accordion-location-link" target="_blank" rel="noopener"><strong><?php echo esc_html($location_name); ?></strong></a>
                                <?php if ($address): ?>
                                    <span><?php echo esc_html($address); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($event['registration_url'])): ?>
                        <a href="<?php echo esc_url($event['registration_url']); ?>"
                           class="pco-accordion-register-btn"
                           target="_blank"
                           rel="noopener">
                            <?php _e('Register', 'simplepco'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <!-- No events for filtered month -->
        <div class="pco-accordion-no-events" style="display: none;">
            <p><?php _e('No events scheduled this month.', 'simplepco'); ?></p>
        </div>
    <?php endif; ?>
</div>

<script>
(function() {
    'use strict';

    var wrapper = document.querySelector('.pco-accordion-wrapper');
    if (!wrapper) return;

    var months = wrapper.querySelectorAll('.pco-accordion-month');
    var monthKeys = [];
    months.forEach(function(m) { monthKeys.push(m.dataset.month); });

    var currentIndex = 0;
    var titleEl = wrapper.querySelector('.pco-accordion-month-title');
    var noEventsEl = wrapper.querySelector('.pco-accordion-no-events');

    // Month navigation
    function showMonth(index) {
        if (index < 0 || index >= monthKeys.length) return;
        currentIndex = index;

        months.forEach(function(m) {
            m.classList.toggle('active', m.dataset.month === monthKeys[currentIndex]);
        });

        var activeMonth = wrapper.querySelector('.pco-accordion-month.active');
        if (activeMonth) {
            titleEl.textContent = activeMonth.dataset.monthDisplay;
            wrapper.dataset.currentMonth = monthKeys[currentIndex];
            if (noEventsEl) noEventsEl.style.display = 'none';
        }

        // Close any expanded event when switching months
        closeExpanded();
    }

    wrapper.querySelectorAll('.pco-accordion-nav-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var dir = this.dataset.dir;
            if (dir === 'prev' && currentIndex > 0) {
                showMonth(currentIndex - 1);
            } else if (dir === 'next' && currentIndex < monthKeys.length - 1) {
                showMonth(currentIndex + 1);
            }
        });
    });

    // Accordion expand
    wrapper.addEventListener('click', function(e) {
        var row = e.target.closest('.pco-accordion-row');
        if (row) {
            var item = row.closest('.pco-accordion-item');
            if (item.classList.contains('expanded')) {
                closeExpanded();
                return;
            }
            expandItem(item);
            return;
        }

        var closeBtn = e.target.closest('.pco-accordion-close');
        if (closeBtn) {
            closeExpanded();
        }
    });

    function expandItem(item) {
        closeExpanded();
        item.classList.add('expanded');
        wrapper.classList.add('has-expanded');

        // Scroll expanded item into view
        setTimeout(function() {
            item.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 50);
    }

    function closeExpanded() {
        wrapper.querySelectorAll('.pco-accordion-item.expanded').forEach(function(el) {
            el.classList.remove('expanded');
        });
        wrapper.classList.remove('has-expanded');
    }

    // Open all links inside event descriptions in a new window
    wrapper.querySelectorAll('.pco-accordion-detail-desc a').forEach(function(link) {
        link.setAttribute('target', '_blank');
        link.setAttribute('rel', 'noopener');
    });

})();
</script>
