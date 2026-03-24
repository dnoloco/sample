<?php
/**
 * Calendar List View Template
 *
 * Displays events in a list format with featured events highlighted.
 *
 * Available variables:
 * - $featured_events (array) - Array of featured event objects
 * - $regular_events (array) - Array of regular event objects
 *
 * Event object structure:
 * - id (string)
 * - name (string)
 * - starts_at (string ISO date)
 * - ends_at (string ISO date)
 * - all_day (bool)
 * - location (string)
 * - description (string)
 * - summary (string)
 * - image_url (string)
 * - featured (bool)
 * - registration_url (string)
 */

defined('ABSPATH') || exit;

// Helper function to format dates (minimal PHP allowed in templates)
if (!function_exists('simplepco_format_event_date')) {
    function simplepco_format_event_date($starts_at, $ends_at, $all_day) {
        try {
            $tz = new DateTimeZone('America/Chicago');
            $start = new DateTime($starts_at, new DateTimeZone('UTC'));
            $start->setTimezone($tz);
            
            if ($all_day) {
                return $start->format('l, M j');
            } else {
                return $start->format('l, M j') . ' at ' . $start->format('g:i a');
            }
        } catch (Exception $e) {
            return 'Date unavailable';
        }
    }
}

if (!function_exists('simplepco_get_location_name')) {
    function simplepco_get_location_name($location) {
        if (empty($location)) {
            return '';
        }
        
        // Extract location name (before " - ")
        if (strpos($location, ' - ') !== false) {
            return trim(substr($location, 0, strpos($location, ' - ')));
        }
        
        return $location;
    }
}
?>

<div id="pco-view-list" class="pco-view-section<?php echo esc_attr($list_active); ?>">
    
    <!-- Featured Events Section -->
    <?php if (!empty($featured_events)): ?>
        <div class="pco-featured-section">
            <h2 class="pco-section-title pco-featured-title">
                <?php _e('Featured', 'simplepco'); ?>
            </h2>

            <?php foreach ($featured_events as $event):
                $is_all_day = $event['is_all_day'] ?? false;
                $is_recurring = $event['is_recurring'] ?? false;
                $is_multi_day = $event['is_multi_day'] ?? false;
                $initially_hidden = $event['initially_hidden'] ?? false;

                // Determine date and time display for featured event
                $tz = new DateTimeZone('America/Chicago');
                try {
                    $start_dt = new DateTime($event['starts_at'], new DateTimeZone('UTC'));
                    $start_dt->setTimezone($tz);

                    if (!empty($event['featured_date_display'])) {
                        $featured_date = $event['featured_date_display'];
                    } else {
                        $featured_date = $start_dt->format('M j, Y');
                    }

                    // Format time display with proper timezone
                    if ($is_all_day) {
                        $featured_time = __('All Day', 'simplepco');
                    } else {
                        $featured_time = $start_dt->format('g:ia');

                        // Add end time if available
                        if (!empty($event['ends_at'])) {
                            $end_dt = new DateTime($event['ends_at'], new DateTimeZone('UTC'));
                            $end_dt->setTimezone($tz);
                            $featured_time = $start_dt->format('g') . '–' . $end_dt->format('g:ia');
                        }
                    }
                } catch (Exception $e) {
                    $featured_date = $event['date_display'] ?? '';
                    $featured_time = '';
                }

                $location_name = simplepco_get_location_name($event['location']);
                $event_data_json = json_encode([
                    'name' => $event['name'],
                    'description' => $event['description'],
                    'summary' => $event['summary'],
                    'image_url' => $event['image_url'],
                    'date' => $featured_date,
                    'time' => $featured_time,
                    'location' => $event['location'],
                    'location_name' => $location_name,
                    'registration_url' => $event['registration_url'],
                    'tag_ids' => $event['tag_ids'] ?? []
                ]);

                // Add hidden class for featured events beyond the initial limit
                $card_class = 'pco-featured-card';
                if ($initially_hidden) {
                    $card_class .= ' pco-featured-initially-hidden';
                }
                ?>

                <div class="<?php echo esc_attr($card_class); ?>">
                    <?php if ($event['image_url']): ?>
                        <div class="pco-featured-image">
                            <img src="<?php echo esc_url($event['image_url']); ?>"
                                 alt="<?php echo esc_attr($event['name']); ?>"
                                 class="pco-featured-img">
                        </div>
                    <?php endif; ?>

                    <div class="pco-featured-content">
                        <button class="pco-event-title-btn pco-featured-title-btn"
                                data-event='<?php echo esc_attr($event_data_json); ?>'>
                            <strong class="pco-featured-name">
                                <?php echo esc_html($event['name']); ?>
                            </strong>
                        </button>

                        <div class="pco-featured-meta">
                            <span class="pco-featured-date"><?php echo esc_html($featured_date); ?></span>
                            <?php if ($is_recurring): ?>
                                <span class="pco-featured-recurring">| <?php _e('Recurring', 'simplepco'); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="pco-featured-badges">
                            <span class="pco-badge is-featured">
                                ★ <?php _e('Featured', 'simplepco'); ?>
                            </span>

                            <?php if ($event['registration_url']): ?>
                                <span class="pco-badge pco-badge-signup">
                                    <?php _e('Signups available', 'simplepco'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Regular Events Section -->
    <div class="pco-events-section">
        <h2 class="pco-section-title pco-upcoming-title">
            <?php _e('Upcoming', 'simplepco'); ?>
        </h2>

        <?php
        // Group events by month and day, filtering out past events
        $tz = new DateTimeZone('America/Chicago');
        $today = new DateTime('today', $tz);
        $current_month_key = $today->format('Y-m');
        $current_month_display = $today->format('F Y');
        $events_by_month = [];

        if (!empty($regular_events)) {
            foreach ($regular_events as $event) {
                try {
                    $start = new DateTime($event['starts_at'], new DateTimeZone('UTC'));
                    $start->setTimezone($tz);

                    // Skip past events
                    if ($start < $today) {
                        continue;
                    }

                    $month_key = $start->format('Y-m');
                    $month_display = $start->format('F Y');
                    $day_key = $start->format('Y-m-d');
                    $day_display = strtoupper($start->format('l, M j'));

                    if (!isset($events_by_month[$month_key])) {
                        $events_by_month[$month_key] = [
                            'display' => $month_display,
                            'days' => []
                        ];
                    }

                    if (!isset($events_by_month[$month_key]['days'][$day_key])) {
                        $events_by_month[$month_key]['days'][$day_key] = [
                            'display' => $day_display,
                            'events' => []
                        ];
                    }

                    $events_by_month[$month_key]['days'][$day_key]['events'][] = $event;
                } catch (Exception $e) {
                    // Skip events with invalid dates
                }
            }
        }

        // Check if current month has any events
        $current_month_has_events = isset($events_by_month[$current_month_key]);
        ?>

        <?php if (!$current_month_has_events): ?>
            <!-- Current month has no events - show "No events scheduled" -->
            <div class="pco-month-group">
                <h3 class="pco-month-header"><?php echo esc_html($current_month_display); ?></h3>
                <div class="pco-no-events-box">
                    <?php _e('No events scheduled', 'simplepco'); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($events_by_month)): ?>
            <?php foreach ($events_by_month as $month_key => $month_data): ?>
                <div class="pco-month-group">
                    <h3 class="pco-month-header"><?php echo esc_html($month_data['display']); ?></h3>

                    <?php foreach ($month_data['days'] as $day_key => $day_data): ?>
                        <div class="pco-day-group" data-date="<?php echo esc_attr($day_key); ?>">
                            <h4 class="pco-day-header"><?php echo esc_html($day_data['display']); ?></h4>

                            <?php foreach ($day_data['events'] as $event):
                                $is_all_day = $event['is_all_day'] ?? false;
                                $location_name = simplepco_get_location_name($event['location']);

                                // Format time display
                                try {
                                    $tz = new DateTimeZone('America/Chicago');
                                    $start_dt = new DateTime($event['starts_at'], new DateTimeZone('UTC'));
                                    $start_dt->setTimezone($tz);

                                    if ($is_all_day) {
                                        $time_display = __('All Day', 'simplepco');
                                    } else {
                                        $time_display = $start_dt->format('g:ia');

                                        // Add end time if available
                                        if (!empty($event['ends_at'])) {
                                            $end_dt = new DateTime($event['ends_at'], new DateTimeZone('UTC'));
                                            $end_dt->setTimezone($tz);
                                            $time_display = $start_dt->format('g') . '–' . $end_dt->format('g:ia');
                                        }
                                    }
                                } catch (Exception $e) {
                                    $time_display = '';
                                }

                                $event_data_json = json_encode([
                                    'name' => $event['name'],
                                    'description' => $event['description'],
                                    'summary' => $event['summary'],
                                    'image_url' => $event['image_url'],
                                    'date' => $event['date_display'] ?? '',
                                    'time' => $time_display,
                                    'location' => $event['location'],
                                    'location_name' => $location_name,
                                    'registration_url' => $event['registration_url'],
                                    'tag_ids' => $event['tag_ids'] ?? []
                                ]);
                                ?>

                                <div class="pco-event-item">
                                    <button class="pco-event-title-btn"
                                            data-event='<?php echo esc_attr($event_data_json); ?>'>
                                        <strong class="pco-event-name">
                                            <?php echo esc_html($event['name']); ?>
                                        </strong>
                                    </button>

                                    <div class="pco-event-time">
                                        <?php echo esc_html($time_display); ?>
                                    </div>

                                    <?php if ($location_name): ?>
                                        <div class="pco-event-location">
                                            <?php echo esc_html(strtoupper($location_name)); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($event['registration_url']): ?>
                                        <div class="pco-event-badges">
                                            <span class="pco-badge pco-badge-signup">
                                                <?php _e('Signups available', 'simplepco'); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>

</div>
