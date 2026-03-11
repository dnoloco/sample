<?php
/**
 * Sunday List Template
 *
 * Displays a list of upcoming Sundays with date, time, and clickable location names.
 *
 * Available variables:
 * - $events (array) - Array of event data (each with date_obj DateTime)
 * - $date_format (string) - PHP date format
 * - $time_format (string) - PHP time format
 * - $show_time (bool) - Whether to show the time
 * - $show_address (bool) - Whether to show the address
 * - $settings (array) - All shortcode settings
 * - $scope_class (string) - Unique CSS class for scoped styles
 * - $custom_class (string) - User-defined CSS class
 * - $scoped_css (string) - Inline <style> block
 */

defined('ABSPATH') || exit;

if (empty($events)) {
    return;
}
?>

<?php echo $scoped_css; ?>

<div class="mypco-location-list <?php echo esc_attr($scope_class); ?><?php echo $custom_class; ?>">
    <ul class="mypco-location-list-items">
        <?php foreach ($events as $index => $event): ?>
            <?php
            $has_location = !empty($event['location_full']);
            $is_first = ($index === 0);
            $date_display = $event['date_obj']->format($date_format);
            $time_display = $event['date_obj']->format($time_format);
            ?>
            <li class="mypco-location-list-item <?php echo $is_first ? 'mypco-location-list-item-next' : ''; ?>">
                <!-- Date Badge -->
                <div class="mypco-location-list-date-badge">
                    <span class="mypco-location-list-day"><?php echo esc_html($event['day_short']); ?></span>
                    <span class="mypco-location-list-number"><?php echo esc_html($event['day_number']); ?></span>
                    <span class="mypco-location-list-month"><?php echo esc_html($event['month_short']); ?></span>
                </div>

                <!-- Event Details -->
                <div class="mypco-location-list-details">
                    <!-- Date Display -->
                    <div class="mypco-location-list-date-full">
                        <?php echo esc_html($date_display); ?>
                    </div>

                    <!-- Time -->
                    <?php if ($show_time): ?>
                        <div class="mypco-location-list-time">
                            <svg class="mypco-location-list-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <?php echo esc_html($time_display); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Location (Clickable) -->
                    <?php if ($has_location): ?>
                        <div class="mypco-location-list-location">
                            <svg class="mypco-location-list-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <?php if (!empty($event['maps_url'])): ?>
                                <a href="<?php echo esc_url($event['maps_url']); ?>"
                                   class="mypco-location-list-link"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="<?php esc_attr_e('Get directions in Google Maps', 'mypco-online'); ?>">
                                    <?php echo esc_html($event['location_name'] ?: $event['location_full']); ?>
                                    <?php if ($show_address && !empty($event['location_address'])): ?>
                                        <span class="mypco-location-list-address"> &mdash; <?php echo esc_html($event['location_address']); ?></span>
                                    <?php endif; ?>
                                    <svg class="mypco-location-list-external" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                        <polyline points="15 3 21 3 21 9"></polyline>
                                        <line x1="10" y1="14" x2="21" y2="3"></line>
                                    </svg>
                                </a>
                            <?php else: ?>
                                <span class="mypco-location-list-name">
                                    <?php echo esc_html($event['location_name'] ?: $event['location_full']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="mypco-location-list-location mypco-location-list-tba">
                            <svg class="mypco-location-list-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span class="mypco-location-list-name"><?php _e('Location TBA', 'mypco-online'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($is_first): ?>
                    <!-- "This Sunday" Badge for first item -->
                    <div class="mypco-location-list-badge">
                        <?php _e('This Week', 'mypco-online'); ?>
                    </div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
