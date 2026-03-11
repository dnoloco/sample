<?php
/**
 * Calendar Gallery View Template
 *
 * Displays events in a visual gallery grid with images.
 *
 * Available variables:
 * - $all_events (array) - Array of all event objects
 * - $expanded_events (array) - Events organized by event ID for grouping recurring events
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

// Group events by name (to consolidate recurring events) and filter past events
$tz = new DateTimeZone('America/Chicago');
$today = new DateTime('today', $tz);

$events_by_name = [];
foreach ($all_events as $event) {
    // Skip events without images for gallery view
    if (empty($event['image_url'])) {
        continue;
    }

    // Parse event date and skip past events
    try {
        $event_start = new DateTime($event['starts_at'], new DateTimeZone('UTC'));
        $event_start->setTimezone($tz);

        // Skip events that have already passed
        if ($event_start < $today) {
            continue;
        }
    } catch (Exception $e) {
        continue;
    }

    $event_name = $event['name'];
    if (!isset($events_by_name[$event_name])) {
        $events_by_name[$event_name] = [
            'event' => $event,
            'instances' => [],
            'closest_instance' => $event,
            'closest_date' => $event_start
        ];
    }

    $events_by_name[$event_name]['instances'][] = $event;

    // Track the closest upcoming instance
    if ($event_start < $events_by_name[$event_name]['closest_date']) {
        $events_by_name[$event_name]['closest_instance'] = $event;
        $events_by_name[$event_name]['closest_date'] = $event_start;
    }
}

// Sort events: featured first, then by closest upcoming date
uasort($events_by_name, function($a, $b) {
    $a_featured = $a['event']['is_featured'] ?? false;
    $b_featured = $b['event']['is_featured'] ?? false;

    // Featured events come first
    if ($a_featured && !$b_featured) {
        return -1;
    }
    if (!$a_featured && $b_featured) {
        return 1;
    }

    // Then sort by closest upcoming date
    return $a['closest_date'] <=> $b['closest_date'];
});
?>

<div id="pco-view-gallery" class="pco-view-section<?php echo esc_attr($gallery_active); ?>">
    
    <h2 class="pco-section-title pco-gallery-title">
        <?php _e('Event Gallery', 'mypco-online'); ?>
    </h2>
    
    <?php if (empty($events_by_name)): ?>

        <p class="pco-no-events">
            <?php _e('No upcoming events found to display.', 'mypco-online'); ?>
        </p>

    <?php else: ?>

        <div class="pco-gallery-grid">

            <?php foreach ($events_by_name as $event_name => $event_group):
                $event = $event_group['event'];
                $instances = $event_group['instances'];
                $closest_instance = $event_group['closest_instance'];
                $closest_date = $event_group['closest_date'];

                // Determine if recurring (multiple upcoming instances)
                $is_recurring = count($instances) > 1;

                // Format date display using closest upcoming instance
                if ($is_recurring) {
                    $gallery_date = $closest_date->format('M j, Y');
                } else {
                    // Check for multi-day event
                    if ($closest_instance['ends_at']) {
                        try {
                            $end = new DateTime($closest_instance['ends_at'], new DateTimeZone('UTC'));
                            $end->setTimezone($tz);

                            if ($closest_date->format('Y-m-d') !== $end->format('Y-m-d')) {
                                // Multi-day event
                                if ($closest_date->format('m') === $end->format('m')) {
                                    $gallery_date = $closest_date->format('M j') . ' - ' . $end->format('M j, Y');
                                } else {
                                    $gallery_date = $closest_date->format('M j') . ' - ' . $end->format('M j, Y');
                                }
                            } else {
                                $gallery_date = $closest_date->format('M j, Y');
                            }
                        } catch (Exception $e) {
                            $gallery_date = $closest_date->format('M j, Y');
                        }
                    } else {
                        $gallery_date = $closest_date->format('M j, Y');
                    }
                }

                // Format time display with end time if available
                if ($closest_instance['is_all_day'] ?? false) {
                    $time_display = __('All Day', 'mypco-online');
                } else {
                    $time_display = $closest_date->format('g:ia');
                    if (!empty($closest_instance['ends_at'])) {
                        try {
                            $end_time = new DateTime($closest_instance['ends_at'], new DateTimeZone('UTC'));
                            $end_time->setTimezone($tz);
                            $time_display = $closest_date->format('g') . '–' . $end_time->format('g:ia');
                        } catch (Exception $e) {
                            // Keep just start time
                        }
                    }
                }
                $date_key = $closest_date->format('Y-m-d');

                // Extract location name
                $location = $closest_instance['location'];
                if (!empty($location) && strpos($location, ' - ') !== false) {
                    $location_name = trim(substr($location, 0, strpos($location, ' - ')));
                } else {
                    $location_name = $location;
                }
                
                // Prepare event data for detail view
                $event_data_json = json_encode([
                    'name' => $event['name'],
                    'description' => $event['description'],
                    'summary' => $event['summary'],
                    'image_url' => $event['image_url'],
                    'time' => $time_display,
                    'date' => $gallery_date,
                    'dateKey' => $date_key,
                    'location' => $location,
                    'location_name' => $location_name,
                    'registration_url' => $event['registration_url'],
                    'tag_ids' => $event['tag_ids'] ?? []
                ]);
                ?>
                
                <div class="pco-gallery-item">

                    <!-- Event Image -->
                    <div class="pco-gallery-image-wrapper">
                        <img src="<?php echo esc_url($event['image_url']); ?>"
                             class="pco-gallery-img"
                             alt="<?php echo esc_attr($event['name']); ?>">
                    </div>

                    <!-- Event Content -->
                    <div class="pco-gallery-content">

                        <!-- Event Title (Clickable) -->
                        <button class="pco-event-title-btn pco-gallery-title-btn"
                                data-event='<?php echo esc_attr($event_data_json); ?>'>
                            <strong class="pco-gallery-event-name">
                                <?php echo esc_html($event['name']); ?>
                            </strong>
                        </button>

                        <!-- Event Meta: Date and Recurring indicator -->
                        <div class="pco-gallery-meta">
                            <?php echo esc_html($gallery_date); ?><?php if ($is_recurring): ?> | <?php _e('Recurring', 'mypco-online'); ?><?php endif; ?>
                        </div>

                        <!-- Badges -->
                        <div class="pco-gallery-badges">
                            <?php if ($event['is_featured'] ?? false): ?>
                                <span class="pco-badge pco-badge-featured">
                                    <span class="dashicons dashicons-star-filled"></span>
                                    <?php _e('Featured', 'mypco-online'); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($event['registration_url']): ?>
                                <span class="pco-badge pco-badge-signup">
                                    <?php _e('Signups available', 'mypco-online'); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                    </div>

                </div>
                
            <?php endforeach; ?>
            
        </div>
        
    <?php endif; ?>
    
</div>
