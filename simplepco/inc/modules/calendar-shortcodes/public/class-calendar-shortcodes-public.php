<?php
/**
 * Calendar Shortcodes Addon - Public Component
 *
 * Handles all frontend/public functionality for the custom calendar shortcodes.
 * Provides shortcodes for displaying custom single events and event lists.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SimplePCO_Calendar_Shortcodes_Public {

    private $loader;
    private $api_model;
    private $timezone;

    public function __construct($loader, $api_model) {
        $this->loader = $loader;
        $this->api_model = $api_model;

        require_once SIMPLEPCO_PLUGIN_DIR . 'inc/core/class-simplepco-date-helper.php';
        $this->timezone = SimplePCO_Date_Helper::get_timezone();
    }

    /**
     * Initialize public functionality.
     */
    public function init() {
        add_shortcode('simplepco_custom_single', [$this, 'render_custom_event_shortcode']);
        add_shortcode('simplepco_custom_featured', [$this, 'render_featured_event_shortcode']);
        add_shortcode('simplepco_custom_event_list', [$this, 'render_custom_list_shortcode']);

        $this->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_public_assets');
    }

    /**
     * Enqueue public-facing assets.
     */
    public function enqueue_public_assets() {
        global $post;

        if (!is_a($post, 'WP_Post')) {
            return;
        }

        $has_custom_event = has_shortcode($post->post_content, 'simplepco_custom_single') ||
                            has_shortcode($post->post_content, 'simplepco_custom_featured') ||
                            has_shortcode($post->post_content, 'simplepco_custom_event_list');

        if (!$has_custom_event) {
            return;
        }

        wp_enqueue_style(
            'simplepco-custom-events-public',
            SIMPLEPCO_PLUGIN_URL . 'modules/calendar-shortcodes/public/assets/css/custom-events.css',
            [],
            SIMPLEPCO_VERSION
        );
    }

    /**
     * Build scoped inline CSS for a custom event shortcode instance.
     */
    private function build_scoped_styles($scope_class, $settings) {
        $primary = $settings['primary_color'] ?? '#333333';
        $text = $settings['text_color'] ?? '#333333';
        $bg = $settings['background_color'] ?? '#ffffff';
        $radius = $settings['border_radius'] ?? 8;

        return "<style>.{$scope_class} {
    --simplepco-loc-primary: {$primary};
    --simplepco-loc-text: {$text};
    --simplepco-loc-bg: {$bg};
    --simplepco-loc-radius: {$radius}px;
}</style>";
    }

    /**
     * Render the custom single event shortcode.
     */
    public function render_custom_event_shortcode($atts) {
        $atts = shortcode_atts([
            'id'         => 0,
            'event'      => '',
            'layout'     => '',
            'show_title' => '',
            'show_map'   => '',
        ], $atts, 'simplepco_custom_single');

        $id = absint($atts['id']);
        if ($id > 0) {
            require_once SIMPLEPCO_PLUGIN_DIR . 'inc/core/class-simplepco-shortcodes-admin.php';
            $settings = SimplePCO_Shortcodes_Admin::get_shortcode_settings($id, 'simplepco_custom_single');
        } else {
            $settings = [];
        }

        $event_name = !empty($atts['event']) ? $atts['event'] : ($settings['event_name'] ?? '');
        $layout = !empty($atts['layout']) ? $atts['layout'] : ($settings['layout_style'] ?? 'card');

        if ($atts['show_title'] !== '') {
            $show_title = ($atts['show_title'] === 'yes' || $atts['show_title'] === '1' || $atts['show_title'] === true);
        } else {
            $show_title = $settings['show_title'] ?? true;
        }

        if ($atts['show_map'] !== '') {
            $show_map = ($atts['show_map'] === 'yes' || $atts['show_map'] === '1' || $atts['show_map'] === true);
        } else {
            $show_map = $settings['show_map'] ?? true;
        }

        $show_time = $settings['show_time'] ?? true;
        $show_address = $settings['show_address'] ?? true;

        $category = $settings['category'] ?? '';

        $events = $this->fetch_custom_events($event_name, $category);

        if (empty($events)) {
            $empty_msg = !empty($settings['empty_message'])
                ? $settings['empty_message']
                : __('No upcoming events found.', 'simplepco');
            return '<div class="simplepco-location-empty">' . esc_html($empty_msg) . '</div>';
        }

        $next_event = $events[0];

        $date_format = $this->resolve_format($settings['date_format'] ?? 'l, F j, Y', $settings['date_format_custom'] ?? '');
        $time_format = $this->resolve_format($settings['time_format'] ?? 'g:i a', $settings['time_format_custom'] ?? '');

        $scope_class = 'simplepco-sc-' . ($id > 0 ? $id : 'default-ns');
        $custom_class = !empty($settings['custom_class']) ? ' ' . esc_attr($settings['custom_class']) : '';
        $scoped_css = $this->build_scoped_styles($scope_class, $settings);

        $data = [
            'event'                 => $next_event,
            'layout'                => $layout,
            'show_title'            => $show_title,
            'show_map'              => $show_map,
            'show_time'             => $show_time,
            'show_address'          => $show_address,
            'map_height'            => $settings['map_height'] ?? 200,
            'date_format'           => $date_format,
            'time_format'           => $time_format,
            'settings'              => $settings,
            'scope_class'           => $scope_class,
            'custom_class'          => $custom_class,
            'scoped_css'            => $scoped_css,
            'create_maps_embed_url' => [$this, 'create_maps_embed_url_public'],
        ];

        return $this->load_template('custom-event', $data);
    }

    /**
     * Render the custom featured event shortcode.
     *
     * Fetches events marked as "featured" in PCO, filters by category and name,
     * applies count/mode settings, and renders each event using the custom-event template.
     */
    public function render_featured_event_shortcode($atts) {
        $atts = shortcode_atts([
            'id'         => 0,
            'event'      => '',
            'layout'     => '',
            'show_title' => '',
            'show_map'   => '',
        ], $atts, 'simplepco_custom_featured');

        $id = absint($atts['id']);
        if ($id > 0) {
            require_once SIMPLEPCO_PLUGIN_DIR . 'inc/core/class-simplepco-shortcodes-admin.php';
            $settings = SimplePCO_Shortcodes_Admin::get_shortcode_settings($id, 'simplepco_custom_featured');
        } else {
            $settings = [];
        }

        $event_name     = !empty($atts['event']) ? $atts['event'] : ($settings['event_name'] ?? '');
        $layout         = !empty($atts['layout']) ? $atts['layout'] : ($settings['layout_style'] ?? 'card');
        $featured_count = isset($settings['featured_count']) ? (int) $settings['featured_count'] : 1;
        $featured_mode  = $settings['featured_mode'] ?? 'upcoming';
        $category       = $settings['category'] ?? '';

        if ($atts['show_title'] !== '') {
            $show_title = ($atts['show_title'] === 'yes' || $atts['show_title'] === '1' || $atts['show_title'] === true);
        } else {
            $show_title = $settings['show_title'] ?? true;
        }

        if ($atts['show_map'] !== '') {
            $show_map = ($atts['show_map'] === 'yes' || $atts['show_map'] === '1' || $atts['show_map'] === true);
        } else {
            $show_map = $settings['show_map'] ?? true;
        }

        $show_time    = $settings['show_time'] ?? true;
        $show_address = $settings['show_address'] ?? true;
        $show_signup  = $settings['show_signup'] ?? false;

        // Fetch featured events from the PCO Calendar API
        $events = $this->fetch_featured_events($event_name, $category, $featured_count, $featured_mode);

        if (empty($events)) {
            $empty_msg = !empty($settings['empty_message'])
                ? $settings['empty_message']
                : __('No upcoming featured events found.', 'simplepco');
            return '<div class="simplepco-location-empty">' . esc_html($empty_msg) . '</div>';
        }

        $date_format = $this->resolve_format($settings['date_format'] ?? 'l, F j, Y', $settings['date_format_custom'] ?? '');
        $time_format = $this->resolve_format($settings['time_format'] ?? 'g:i a', $settings['time_format_custom'] ?? '');

        // Render each featured event
        $output = '';
        foreach ($events as $index => $event) {
            $scope_class = 'simplepco-sc-' . ($id > 0 ? $id : 'default-fe') . ($index > 0 ? '-' . $index : '');
            $custom_class = !empty($settings['custom_class']) ? ' ' . esc_attr($settings['custom_class']) : '';
            $scoped_css = $this->build_scoped_styles($scope_class, $settings);

            $data = [
                'event'                 => $event,
                'layout'                => $layout,
                'show_title'            => $show_title,
                'show_map'              => $show_map,
                'show_time'             => $show_time,
                'show_address'          => $show_address,
                'show_signup'           => $show_signup,
                'map_height'            => $settings['map_height'] ?? 200,
                'date_format'           => $date_format,
                'time_format'           => $time_format,
                'settings'              => $settings,
                'scope_class'           => $scope_class,
                'custom_class'          => $custom_class,
                'scoped_css'            => $scoped_css,
                'create_maps_embed_url' => [$this, 'create_maps_embed_url_public'],
            ];

            $output .= $this->load_template('custom-event', $data);
        }

        return $output;
    }

    /**
     * Fetch featured events from the PCO Calendar API.
     *
     * @param string $event_name     Optional event name filter.
     * @param string $category       Optional category tag ID to filter by.
     * @param int    $featured_count Max number of featured events.
     * @param string $featured_mode  'upcoming' or 'random'.
     * @return array Formatted featured events.
     */
    private function fetch_featured_events($event_name = '', $category = '', $featured_count = 1, $featured_mode = 'upcoming') {
        if (!$this->api_model) {
            return [];
        }

        $now = new DateTime('now', $this->timezone);
        $start_date = $now->format('Y-m-d\T00:00:00\Z');

        $end_date_obj = clone $now;
        $end_date_obj->modify('+6 weeks');
        $end_date = $end_date_obj->format('Y-m-d\T23:59:59\Z');

        $params = [
            'where[starts_at][gte]' => $start_date,
            'where[starts_at][lte]' => $end_date,
            'order' => 'starts_at',
            'per_page' => 100,
            'include' => 'event,event.tags'
        ];

        $transient_key = 'simplepco_custom_featureds_' . md5(serialize($params) . $event_name . $category);

        $response = $this->api_model->get_data_with_caching(
            'calendar',
            '/v2/event_instances',
            $params,
            $transient_key,
            HOUR_IN_SECONDS
        );

        if (isset($response['error']) || empty($response['data'])) {
            return [];
        }

        // Build event map and event-to-tags map from included data
        $event_map = [];
        $event_tags_map = [];

        if (!empty($response['included'])) {
            foreach ($response['included'] as $item) {
                if ($item['type'] === 'Event') {
                    $event_map[$item['id']] = $item['attributes'];
                    $tag_ids = [];
                    if (!empty($item['relationships']['tags']['data'])) {
                        foreach ($item['relationships']['tags']['data'] as $tag_ref) {
                            $tag_ids[] = $tag_ref['id'];
                        }
                    }
                    $event_tags_map[$item['id']] = $tag_ids;
                }
            }
        }

        // Filter for featured events, optionally by name and category
        $matched = [];
        $seen_parents = [];

        foreach ($response['data'] as $instance) {
            $parent_id = $instance['relationships']['event']['data']['id'] ?? null;
            $parent = $event_map[$parent_id] ?? null;

            if (!$parent || empty($parent['featured'])) {
                continue;
            }

            // Event name filter
            if (!empty($event_name) && stripos($parent['name'] ?? '', $event_name) === false) {
                continue;
            }

            // Category/tag filter (category value is a tag ID from the dropdown)
            if (!empty($category)) {
                $tags = $event_tags_map[$parent_id] ?? [];
                if (!in_array($category, $tags)) {
                    continue;
                }
            }

            // Deduplicate: one entry per parent event (closest upcoming instance)
            if (isset($seen_parents[$parent_id])) {
                continue;
            }
            $seen_parents[$parent_id] = true;

            $starts_at = $instance['attributes']['starts_at'];
            try {
                $event_date = new DateTime($starts_at, new DateTimeZone('UTC'));
                $event_date->setTimezone($this->timezone);
            } catch (Exception $e) {
                continue;
            }

            $matched[] = $this->format_featured_event($instance, $parent, $event_date);
        }

        // Apply display mode
        if ($featured_mode === 'random') {
            shuffle($matched);
        }
        // 'upcoming' is already sorted by start date from API

        // Limit to count
        return array_slice($matched, 0, $featured_count);
    }

    /**
     * Format a featured event instance for the custom-event template.
     */
    private function format_featured_event($instance, $parent, $event_date) {
        $attr = $instance['attributes'];
        $location_full = $attr['location'] ?? '';
        $location_parts = $this->parse_custom_event_location($location_full);
        $maps_url = $this->create_maps_url($location_full);

        $registration_url = $attr['registration_url']
            ?? $attr['signup_url']
            ?? ($parent['registration_url'] ?? '')
            ?? ($parent['signup_url'] ?? '');

        return [
            'id' => $instance['id'],
            'name' => $parent['name'] ?? 'Event',
            'date_obj' => $event_date,
            'date_key' => $event_date->format('Y-m-d'),
            'day_of_week' => $event_date->format('l'),
            'day_short' => $event_date->format('D'),
            'day_number' => $event_date->format('j'),
            'month_short' => $event_date->format('M'),
            'location_full' => $location_full,
            'location_name' => $location_parts['name'],
            'location_address' => $location_parts['address'],
            'maps_url' => $maps_url,
            'registration_url' => $registration_url,
        ];
    }

    /**
     * Render the custom list shortcode.
     */
    public function render_custom_list_shortcode($atts) {
        $atts = shortcode_atts([
            'id'    => 0,
            'event' => '',
            'count' => '',
        ], $atts, 'simplepco_custom_event_list');

        $id = absint($atts['id']);
        if ($id > 0) {
            require_once SIMPLEPCO_PLUGIN_DIR . 'inc/core/class-simplepco-shortcodes-admin.php';
            $settings = SimplePCO_Shortcodes_Admin::get_shortcode_settings($id, 'simplepco_custom_event_list');
        } else {
            $settings = [];
        }

        $event_name = !empty($atts['event']) ? $atts['event'] : ($settings['event_name'] ?? '');
        $count = !empty($atts['count']) ? $atts['count'] : ($settings['count'] ?? 'auto');
        $category = $settings['category'] ?? '';

        $show_time = $settings['show_time'] ?? true;
        $show_address = $settings['show_address'] ?? true;

        $events = $this->fetch_custom_events($event_name, $category);

        if (empty($events)) {
            $empty_msg = !empty($settings['empty_message'])
                ? $settings['empty_message']
                : __('No upcoming events found.', 'simplepco');
            return '<div class="simplepco-location-empty">' . esc_html($empty_msg) . '</div>';
        }

        $display_count = $this->calculate_event_count($count);
        $events_to_display = array_slice($events, 0, $display_count);

        $date_format = $this->resolve_format($settings['date_format'] ?? 'l, F j, Y', $settings['date_format_custom'] ?? '');
        $time_format = $this->resolve_format($settings['time_format'] ?? 'g:i a', $settings['time_format_custom'] ?? '');

        $scope_class = 'simplepco-sc-' . ($id > 0 ? $id : 'default-sl');
        $custom_class = !empty($settings['custom_class']) ? ' ' . esc_attr($settings['custom_class']) : '';
        $scoped_css = $this->build_scoped_styles($scope_class, $settings);

        $data = [
            'events'       => $events_to_display,
            'date_format'  => $date_format,
            'time_format'  => $time_format,
            'show_time'    => $show_time,
            'show_address' => $show_address,
            'settings'     => $settings,
            'scope_class'  => $scope_class,
            'custom_class' => $custom_class,
            'scoped_css'   => $scoped_css,
        ];

        return $this->load_template('custom-list', $data);
    }

    /**
     * Resolve a format string, handling the 'custom' option.
     */
    private function resolve_format($format, $custom_format) {
        if ($format === 'custom' && !empty($custom_format)) {
            return $custom_format;
        }
        if ($format === 'custom') {
            return 'l, F j, Y';
        }
        return $format;
    }

    /**
     * Calculate how many events to show based on the month.
     */
    private function calculate_event_count($count) {
        if ($count !== 'auto' && is_numeric($count)) {
            return absint($count);
        }

        $now = new DateTime('now', $this->timezone);
        $day_of_month = (int) $now->format('j');
        $current_month = (int) $now->format('n');
        $current_year = (int) $now->format('Y');

        if ($day_of_month <= 7) {
            $event_days = $this->count_event_days_in_month($current_month, $current_year);
            if ($event_days >= 5) {
                return 5;
            }
        }

        return 4;
    }

    /**
     * Count the number of Sundays in a given month.
     */
    private function count_event_days_in_month($month, $year) {
        $first_day = new DateTime("$year-$month-01", $this->timezone);
        $last_day = new DateTime($first_day->format('Y-m-t'), $this->timezone);

        $count = 0;
        $current = clone $first_day;

        while ($current <= $last_day) {
            if ($current->format('w') == 0) {
                $count++;
            }
            $current->modify('+1 day');
        }

        return $count;
    }

    /**
     * Fetch custom events from Planning Center Calendar.
     *
     * @param string $event_name  Optional event name filter.
     * @param string $category    Optional category tag ID to filter by.
     * @return array Formatted events.
     */
    private function fetch_custom_events($event_name, $category = '') {
        if (!$this->api_model) {
            return [];
        }

        $now = new DateTime('now', $this->timezone);
        $start_date = $now->format('Y-m-d\T00:00:00\Z');

        $end_date_obj = clone $now;
        $end_date_obj->modify('+6 weeks');
        $end_date = $end_date_obj->format('Y-m-d\T23:59:59\Z');

        $params = [
            'where[starts_at][gte]' => $start_date,
            'where[starts_at][lte]' => $end_date,
            'order' => 'starts_at',
            'per_page' => 50,
            'include' => 'event,event.tags'
        ];

        $transient_key = 'simplepco_custom_events_' . md5(serialize($params) . $event_name . $category);

        $response = $this->api_model->get_data_with_caching(
            'calendar',
            '/v2/event_instances',
            $params,
            $transient_key,
            HOUR_IN_SECONDS
        );

        if (isset($response['error']) || empty($response['data'])) {
            return [];
        }

        // Build event map and event-to-tags map from included data
        $event_map = [];
        $event_tags_map = [];

        if (!empty($response['included'])) {
            foreach ($response['included'] as $item) {
                if ($item['type'] === 'Event') {
                    $event_map[$item['id']] = $item['attributes'];
                    $tag_ids = [];
                    if (!empty($item['relationships']['tags']['data'])) {
                        foreach ($item['relationships']['tags']['data'] as $tag_ref) {
                            $tag_ids[] = $tag_ref['id'];
                        }
                    }
                    $event_tags_map[$item['id']] = $tag_ids;
                }
            }
        }

        $matched_events = [];
        $seen_dates = [];

        foreach ($response['data'] as $instance) {
            $parent_id = $instance['relationships']['event']['data']['id'] ?? null;
            $parent = $event_map[$parent_id] ?? null;

            if (!$parent) {
                continue;
            }

            $parent_name = $parent['name'] ?? '';

            if (!empty($event_name) && stripos($parent_name, $event_name) === false) {
                continue;
            }

            // Category/tag filter
            if (!empty($category)) {
                $tags = $event_tags_map[$parent_id] ?? [];
                if (!in_array($category, $tags)) {
                    continue;
                }
            }

            $starts_at = $instance['attributes']['starts_at'];
            try {
                $event_date = new DateTime($starts_at, new DateTimeZone('UTC'));
                $event_date->setTimezone($this->timezone);

                if ($event_date->format('w') != 0) {
                    continue;
                }

                $date_key = $event_date->format('Y-m-d');
                if (isset($seen_dates[$date_key])) {
                    continue;
                }
                $seen_dates[$date_key] = true;

                $matched_events[] = $this->format_custom_event($instance, $parent, $event_date);
            } catch (Exception $e) {
                continue;
            }
        }

        usort($matched_events, function($a, $b) {
            return strcmp($a['date_key'], $b['date_key']);
        });

        return $matched_events;
    }

    /**
     * Format a custom event instance for display.
     */
    private function format_custom_event($instance, $parent, $event_date) {
        $attr = $instance['attributes'];
        $location_full = $attr['location'] ?? '';
        $location_parts = $this->parse_custom_event_location($location_full);
        $maps_url = $this->create_maps_url($location_full);

        return [
            'id' => $instance['id'],
            'name' => $parent['name'] ?? 'Event',
            'date_obj' => $event_date,
            'date_key' => $event_date->format('Y-m-d'),
            'day_of_week' => $event_date->format('l'),
            'day_short' => $event_date->format('D'),
            'day_number' => $event_date->format('j'),
            'month_short' => $event_date->format('M'),
            'location_full' => $location_full,
            'location_name' => $location_parts['name'],
            'location_address' => $location_parts['address'],
            'maps_url' => $maps_url,
            'registration_url' => $parent['registration_url'] ?? ($attr['registration_url'] ?? ''),
        ];
    }

    /**
     * Parse location string into name and address.
     */
    private function parse_custom_event_location($location_full) {
        if (empty($location_full)) {
            return ['name' => '', 'address' => ''];
        }

        if (strpos($location_full, ' - ') !== false) {
            $parts = explode(' - ', $location_full, 2);
            return [
                'name' => trim($parts[0]),
                'address' => isset($parts[1]) ? trim($parts[1]) : '',
            ];
        }

        return ['name' => $location_full, 'address' => ''];
    }

    /**
     * Create Google Maps direction URL for a location.
     */
    private function create_maps_url($location) {
        if (empty($location)) {
            return '';
        }
        return 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($location);
    }

    /**
     * Create Google Maps embed URL for iframe.
     */
    public function create_maps_embed_url_public($location) {
        if (empty($location)) {
            return '';
        }
        return 'https://www.google.com/maps?q=' . urlencode($location) . '&output=embed';
    }

    /**
     * Load a template file and return output.
     */
    private function load_template($template_name, $data = []) {
        extract($data);

        ob_start();

        $template_path = SIMPLEPCO_PLUGIN_DIR . 'templates/calendar-shortcodes/public/' . $template_name . '.php';

        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<!-- Template not found: ' . esc_html($template_name) . ' -->';
        }

        return ob_get_clean();
    }
}
