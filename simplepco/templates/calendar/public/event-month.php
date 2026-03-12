<?php
/**
 * Calendar Month View Template
 *
 * Displays events in a monthly calendar grid format.
 *
 * Available variables:
 * - $all_events (array) - Array of all event objects
 * - $current_month (string) - Current month name (e.g., "January 2026")
 * - $expanded_events (array) - Events organized by event ID with instances
 *
 * Note: Most of the month grid is built dynamically by JavaScript,
 * but the container structure is provided here.
 */

defined('ABSPATH') || exit;
?>

<div id="pco-view-month" class="pco-view-section<?php echo esc_attr($month_active); ?>">
    
    <!-- Month Navigation Header -->
    <div class="pco-month-header">
        <button id="pco-month-prev" class="pco-month-nav" title="<?php esc_attr_e('Previous Month', 'simplepco-online'); ?>">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
            <?php _e('Previous', 'simplepco-online'); ?>
        </button>
        
        <h2 id="pco-month-title" class="pco-month-title">
            <?php echo esc_html($current_month); ?>
        </h2>
        
        <button id="pco-month-next" class="pco-month-nav" title="<?php esc_attr_e('Next Month', 'simplepco-online'); ?>">
            <?php _e('Next', 'simplepco-online'); ?>
            <span class="dashicons dashicons-arrow-right-alt2"></span>
        </button>
    </div>
    
    <!-- Month Calendar Grid -->
    <div class="pco-month-calendar">
        
        <!-- Day Headers -->
        <div class="pco-month-days-header">
            <div class="pco-day-header"><?php _e('Sunday', 'simplepco-online'); ?></div>
            <div class="pco-day-header"><?php _e('Monday', 'simplepco-online'); ?></div>
            <div class="pco-day-header"><?php _e('Tuesday', 'simplepco-online'); ?></div>
            <div class="pco-day-header"><?php _e('Wednesday', 'simplepco-online'); ?></div>
            <div class="pco-day-header"><?php _e('Thursday', 'simplepco-online'); ?></div>
            <div class="pco-day-header"><?php _e('Friday', 'simplepco-online'); ?></div>
            <div class="pco-day-header"><?php _e('Saturday', 'simplepco-online'); ?></div>
        </div>
        
        <!-- Calendar Grid - Populated by JavaScript -->
        <div id="pco-month-grid" class="pco-month-grid">
            <!-- Days will be inserted here by JavaScript -->
            <div class="pco-month-loading">
                <p><?php _e('Loading calendar...', 'simplepco-online'); ?></p>
            </div>
        </div>
        
    </div>
    
    <!-- Events List Below Calendar -->
    <div id="pco-month-events-list" class="pco-month-events-list">
        <h3 class="pco-month-events-title">
            <?php _e('Events This Month', 'simplepco-online'); ?>
        </h3>
        
        <div id="pco-month-events-container" class="pco-month-events-container">
            <!-- Events will be populated by JavaScript based on selected date -->
            <?php if (!empty($all_events)): ?>
                <div class="pco-month-events-items">
                    <?php foreach ($all_events as $event):
                        $is_all_day = $event['is_all_day'] ?? false;
                        $event_data_json = json_encode([
                            'name' => $event['name'],
                            'description' => $event['description'],
                            'summary' => $event['summary'],
                            'image_url' => $event['image_url'],
                            'starts_at' => $event['starts_at'],
                            'ends_at' => $event['ends_at'],
                            'all_day' => $is_all_day,
                            'location' => $event['location'],
                            'registration_url' => $event['registration_url']
                        ]);

                        try {
                            $tz = new DateTimeZone('America/Chicago');
                            $start = new DateTime($event['starts_at'], new DateTimeZone('UTC'));
                            $start->setTimezone($tz);
                            $date_key = $start->format('Y-m-d');
                            $time_display = $is_all_day ? __('All Day', 'simplepco-online') : $start->format('g:i a');
                        } catch (Exception $e) {
                            $date_key = '';
                            $time_display = '';
                        }
                        ?>
                        
                        <div class="pco-month-event-item" 
                             data-date="<?php echo esc_attr($date_key); ?>"
                             data-event='<?php echo esc_attr($event_data_json); ?>'>
                            
                            <?php if ($event['image_url']): ?>
                                <div class="pco-month-event-thumb">
                                    <img src="<?php echo esc_url($event['image_url']); ?>" 
                                         alt="<?php echo esc_attr($event['name']); ?>">
                                </div>
                            <?php endif; ?>
                            
                            <div class="pco-month-event-info">
                                <button class="pco-event-title-btn pco-month-event-title">
                                    <?php echo esc_html($event['name']); ?>
                                </button>
                                
                                <div class="pco-month-event-time">
                                    <?php echo esc_html($time_display); ?>
                                </div>
                                
                                <?php if ($event['summary']): ?>
                                    <p class="pco-month-event-summary">
                                        <?php echo esc_html(wp_trim_words($event['summary'], 10)); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($event['is_featured'] ?? false): ?>
                                    <span class="pco-badge is-featured">
                                        ★ <?php _e('Featured', 'simplepco-online'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="pco-no-events">
                    <?php _e('No events found for this month.', 'simplepco-online'); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
    
</div>
