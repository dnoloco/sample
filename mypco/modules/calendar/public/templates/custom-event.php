<?php
/**
 * Next Sunday Location Template
 *
 * Displays the upcoming Sunday gathering with date, time, location, and map.
 *
 * Available variables:
 * - $event (array) - Event data with keys: name, date_obj, location_full, location_name, location_address, maps_url
 * - $layout (string) - Layout style: card, minimal, or banner
 * - $show_map (bool) - Whether to show the map
 * - $show_title (bool) - Whether to show the event title
 * - $show_time (bool) - Whether to show the time
 * - $show_address (bool) - Whether to show the address
 * - $map_height (int) - Map height in pixels
 * - $date_format (string) - PHP date format
 * - $time_format (string) - PHP time format
 * - $settings (array) - All shortcode settings
 * - $scope_class (string) - Unique CSS class for scoped styles
 * - $custom_class (string) - User-defined CSS class
 * - $scoped_css (string) - Inline <style> block
 * - $create_maps_embed_url (callable) - Function to create map embed URL
 */

defined('ABSPATH') || exit;

$layout_class = 'mypco-location-' . esc_attr($layout);
$title_class = $show_title ? 'mypco-location-has-title' : 'mypco-location-no-title';
$has_location = !empty($event['location_full']);

// Format date/time using per-shortcode settings
$date_display = $event['date_obj']->format($date_format);
$time_display = $event['date_obj']->format($time_format);
?>

<?php echo $scoped_css; ?>

<div class="mypco-location-card <?php echo $layout_class; ?> <?php echo $title_class; ?> <?php echo esc_attr($scope_class); ?><?php echo $custom_class; ?>">
    <div class="mypco-location-content">
        <?php if ($show_title): ?>
            <!-- Event Name -->
            <h3 class="mypco-location-title">
                <?php echo esc_html($event['name']); ?>
            </h3>
        <?php endif; ?>

        <!-- Info Row: Date/Time and Location -->
        <div class="mypco-location-info-row">
            <!-- Date and Time -->
            <div class="mypco-location-datetime">
                <div class="mypco-location-date">
                    <svg class="mypco-location-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span><?php echo esc_html($date_display); ?></span>
                </div>
                <?php if ($show_time): ?>
                    <div class="mypco-location-time">
                        <svg class="mypco-location-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span><?php echo esc_html($time_display); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($has_location): ?>
                <!-- Location Details -->
                <div class="mypco-location-details">
                    <div class="mypco-location-info">
                        <svg class="mypco-location-icon mypco-location-pin" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <div class="mypco-location-text">
                            <?php if (!empty($event['location_name'])): ?>
                                <span class="mypco-location-name"><?php echo esc_html($event['location_name']); ?></span>
                            <?php endif; ?>
                            <?php if ($show_address && !empty($event['location_address'])): ?>
                                <span class="mypco-location-address"><?php echo esc_html($event['location_address']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- No Location Set -->
                <div class="mypco-location-no-location">
                    <p><?php _e('Location to be announced', 'mypco-online'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($has_location && $show_map && !empty($event['maps_url'])): ?>
            <!-- Embedded Map -->
            <div class="mypco-location-map-container">
                <a href="<?php echo esc_url($event['maps_url']); ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="mypco-location-map-link"
                   aria-label="<?php esc_attr_e('Open in Google Maps', 'mypco-online'); ?>">
                    <iframe
                        class="mypco-location-map"
                        src="<?php echo esc_url(call_user_func($create_maps_embed_url, $event['location_full'])); ?>"
                        width="100%"
                        height="<?php echo esc_attr($map_height); ?>"
                        style="border:0; border-radius: var(--mypco-loc-radius, 8px);"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                    <div class="mypco-location-map-overlay">
                        <span class="mypco-location-map-overlay-text">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                <polyline points="15 3 21 3 21 9"></polyline>
                                <line x1="10" y1="14" x2="21" y2="3"></line>
                            </svg>
                            <?php _e('Open in Google Maps', 'mypco-online'); ?>
                        </span>
                    </div>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
