<?php
/**
 * Custom Event List Template
 *
 * Displays upcoming events grouped by month with navigation arrows,
 * date badges, event names, times and locations.
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

// Group events by month.
$months = [];
foreach ($events as $event) {
    $key = $event['date_obj']->format('Y-m');
    if (!isset($months[$key])) {
        $months[$key] = [
            'label' => $event['date_obj']->format('F Y'),
            'events' => [],
        ];
    }
    $months[$key]['events'][] = $event;
}

$month_keys = array_keys($months);
$list_id = 'simplepco-list-' . ($scope_class ?: wp_unique_id('cl-'));
?>

<?php echo $scoped_css; ?>

<div id="<?php echo esc_attr($list_id); ?>"
     class="simplepco-location-list <?php echo esc_attr($scope_class); ?><?php echo $custom_class; ?>">

    <?php if (count($month_keys) > 1): ?>
    <!-- Month Navigation -->
    <div class="simplepco-list-nav">
        <button class="simplepco-list-nav-btn simplepco-list-prev" aria-label="<?php esc_attr_e('Previous month', 'simplepco'); ?>" disabled>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </button>
        <h3 class="simplepco-list-month-title"><?php echo esc_html($months[$month_keys[0]]['label']); ?></h3>
        <button class="simplepco-list-nav-btn simplepco-list-next" aria-label="<?php esc_attr_e('Next month', 'simplepco'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </button>
    </div>
    <?php else: ?>
    <h3 class="simplepco-list-month-title simplepco-list-month-title--solo"><?php echo esc_html($months[$month_keys[0]]['label']); ?></h3>
    <?php endif; ?>

    <!-- Month Groups -->
    <?php foreach ($months as $month_key => $month): ?>
    <div class="simplepco-list-month" data-month="<?php echo esc_attr($month_key); ?>"
         <?php echo ($month_key !== $month_keys[0]) ? 'style="display:none"' : ''; ?>>
        <ul class="simplepco-location-list-items">
            <?php foreach ($month['events'] as $event):
                $has_location = !empty($event['location_full']);
                $time_display = $event['date_obj']->format($time_format);
            ?>
            <li class="simplepco-location-list-item">
                <!-- Date Badge -->
                <div class="simplepco-location-list-date-badge">
                    <span class="simplepco-location-list-day"><?php echo esc_html($event['day_short']); ?></span>
                    <span class="simplepco-location-list-number"><?php echo esc_html($event['day_number']); ?></span>
                    <span class="simplepco-location-list-month"><?php echo esc_html($event['month_short']); ?></span>
                </div>

                <!-- Event Details -->
                <div class="simplepco-location-list-details">
                    <div class="simplepco-location-list-event-name">
                        <?php echo esc_html($event['name']); ?>
                    </div>
                    <div class="simplepco-location-list-meta">
                        <?php if ($show_time): ?>
                        <span class="simplepco-location-list-meta-item">
                            <svg class="simplepco-location-list-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <?php echo esc_html($time_display); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($has_location): ?>
                        <span class="simplepco-location-list-meta-item">
                            <svg class="simplepco-location-list-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <?php if (!empty($event['maps_url'])): ?>
                                <a href="<?php echo esc_url($event['maps_url']); ?>"
                                   class="simplepco-location-list-link"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="<?php esc_attr_e('Get directions', 'simplepco'); ?>">
                                    <?php echo esc_html($event['location_name'] ?: $event['location_full']); ?>
                                </a>
                            <?php else: ?>
                                <?php echo esc_html($event['location_name'] ?: $event['location_full']); ?>
                            <?php endif; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endforeach; ?>
</div>

<?php if (count($month_keys) > 1): ?>
<script>
(function(){
    var root = document.getElementById('<?php echo esc_js($list_id); ?>');
    if (!root) return;
    var months = <?php echo wp_json_encode(array_map(function($k) use ($months) {
        return ['key' => $k, 'label' => $months[$k]['label']];
    }, $month_keys)); ?>;
    var idx = 0;
    var title = root.querySelector('.simplepco-list-month-title');
    var prev = root.querySelector('.simplepco-list-prev');
    var next = root.querySelector('.simplepco-list-next');

    function show(i) {
        idx = i;
        title.textContent = months[i].label;
        prev.disabled = (i === 0);
        next.disabled = (i === months.length - 1);
        var groups = root.querySelectorAll('.simplepco-list-month');
        for (var g = 0; g < groups.length; g++) {
            groups[g].style.display = (groups[g].getAttribute('data-month') === months[i].key) ? '' : 'none';
        }
    }

    prev.addEventListener('click', function(){ if (idx > 0) show(idx - 1); });
    next.addEventListener('click', function(){ if (idx < months.length - 1) show(idx + 1); });
})();
</script>
<?php endif; ?>
