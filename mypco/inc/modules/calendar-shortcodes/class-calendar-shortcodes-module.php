<?php
/**
 * Calendar Shortcodes Addon - Main Orchestrator
 *
 * Provides custom calendar shortcodes (Custom Single Event & Custom Event List)
 * as a standalone addon that can be enabled independently from the Calendar module.
 */

require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-module-base.php';

class MyPCO_Calendar_Shortcodes_Module extends MyPCO_Module_Base {

    protected $module_key = 'calendar_shortcodes';
    protected $module_name = 'Calendar Shortcodes';
    protected $module_description = 'Custom single event and event list shortcodes for Planning Center Calendar.';

    protected $tier = 'freemium';
    protected $requires_license = false;
    protected $min_license_tier = 'starter';

    protected $features = [
        'free' => [
            'custom_event_shortcode',
            'custom_list_shortcode',
            'google_maps_links'
        ],
        'premium' => []
    ];

    /**
     * Public component instance
     */
    private $public;

    /**
     * Initialize the Calendar Shortcodes addon.
     */
    public function init() {
        // Register shortcode types via the centralized loader
        $this->loader->add_filter('mypco_shortcode_types', $this, 'register_shortcode_types');

        // Load and initialize public component (always loaded for shortcodes)
        $this->load_public_component();
    }

    /**
     * Register the addon's shortcode types into the centralized registry.
     *
     * @param array $types Existing shortcode types.
     * @return array Modified shortcode types.
     */
    public function register_shortcode_types($types) {
        $category_options = $this->fetch_category_options();

        $types['mypco_next_sunday'] = [
            'module'      => 'calendar',
            'module_name' => 'Calendar',
            'name'        => 'Custom Single Event',
            'description' => 'Show the next upcoming event matching a custom filter.',
            'tag'         => 'mypco_next_sunday',
            'is_addon'    => true,
            'addon_key'   => 'calendar_shortcodes',
            'addon_name'  => 'Calendar Shortcodes Addon',
            'defaults'    => [
                'description'         => '',
                'category'            => '',
                'event_name'          => '',
                'layout_style'        => 'card',
                'show_title'          => true,
                'show_time'           => true,
                'show_address'        => true,
                'show_map'            => true,
                'map_height'          => 200,
                'empty_message'       => '',
                'custom_class'        => '',
                'primary_color'       => '#333333',
                'text_color'          => '#333333',
                'background_color'    => '#ffffff',
                'border_radius'       => 8,
                'date_format'         => 'l, F j, Y',
                'date_format_custom'  => '',
                'time_format'         => 'g:i a',
                'time_format_custom'  => '',
            ],
            'fields' => [
                [
                    'key'         => 'category',
                    'label'       => 'Category',
                    'type'        => 'select',
                    'options'     => $category_options,
                    'description' => 'Filter events by Planning Center category.',
                ],
                [
                    'key'         => 'event_name',
                    'label'       => 'Event Name Filter',
                    'type'        => 'text',
                    'description' => 'Enter the event name as it appears in Planning Center. Events containing this text will be displayed.',
                ],
                [
                    'key'         => 'layout_style',
                    'label'       => 'Layout Style',
                    'type'        => 'select',
                    'options'     => [
                        'card'    => 'Card - Boxed layout with shadow',
                        'minimal' => 'Minimal - Clean, no border',
                        'banner'  => 'Banner - Full width with background',
                    ],
                ],
                [
                    'key'   => 'show_title',
                    'label' => 'Show Event Title',
                    'type'  => 'checkbox',
                    'description' => 'Display the event title heading.',
                ],
                [
                    'key'   => 'show_time',
                    'label' => 'Show Event Time',
                    'type'  => 'checkbox',
                ],
                [
                    'key'   => 'show_address',
                    'label' => 'Show Event Location',
                    'type'  => 'checkbox',
                ],
                [
                    'key'   => 'show_map',
                    'label' => 'Show Map',
                    'type'  => 'checkbox',
                    'description' => 'Display an embedded Google Map below the location.',
                ],
                [
                    'key'   => 'map_height',
                    'label' => 'Map Height',
                    'type'  => 'number',
                    'min'   => 100,
                    'max'   => 500,
                    'step'  => 10,
                    'after' => 'px',
                ],
                [
                    'key'         => 'empty_message',
                    'label'       => 'Empty State Message',
                    'type'        => 'text',
                    'description' => 'Message shown when no events are found. Leave blank for the default.',
                ],
                [
                    'key'     => 'date_format',
                    'label'   => 'Date Format',
                    'type'    => 'select',
                    'options' => [
                        'D, M j, Y'  => 'D, M j, Y',
                        'l, F j, Y'  => 'l, F j, Y',
                        'l, M j, Y'  => 'l, M j, Y',
                        'F j, Y'     => 'F j, Y',
                        'M j, Y'     => 'M j, Y',
                        'm/d/Y'      => 'm/d/Y',
                        'custom'     => 'Custom',
                    ],
                    'description' => 'PHP date format. D=day abbr, l=day name, M=month abbr, F=month name, j=day, m=month number, Y=year.',
                ],
                [
                    'key'         => 'date_format_custom',
                    'label'       => 'Custom Date Format',
                    'type'        => 'text',
                    'description' => 'Enter a custom PHP date format string.',
                    'show_when'   => ['field' => 'date_format', 'value' => 'custom'],
                ],
                [
                    'key'     => 'time_format',
                    'label'   => 'Time Format',
                    'type'    => 'select',
                    'options' => [
                        'g:i a' => 'g:i a',
                        'g:i A' => 'g:i A',
                        'H:i'   => 'H:i',
                        'custom' => 'Custom',
                    ],
                    'description' => 'PHP time format. g=12-hour, H=24-hour, i=minutes, a=am/pm, A=AM/PM.',
                ],
                [
                    'key'         => 'time_format_custom',
                    'label'       => 'Custom Time Format',
                    'type'        => 'text',
                    'description' => 'Enter a custom PHP time format string.',
                    'show_when'   => ['field' => 'time_format', 'value' => 'custom'],
                ],
            ],
        ];

        $types['mypco_featured_event'] = [
            'module'      => 'calendar',
            'module_name' => 'Calendar',
            'name'        => 'Custom Featured Event',
            'description' => 'Display featured events from Planning Center with full details and optional signup.',
            'tag'         => 'mypco_featured_event',
            'is_addon'    => true,
            'addon_key'   => 'calendar_shortcodes',
            'addon_name'  => 'Calendar Shortcodes Addon',
            'defaults'    => [
                'description'         => '',
                'featured_count'      => 1,
                'featured_mode'       => 'upcoming',
                'category'            => '',
                'event_name'          => '',
                'layout_style'        => 'card',
                'show_title'          => true,
                'show_time'           => true,
                'show_address'        => true,
                'show_map'            => true,
                'show_signup'         => false,
                'map_height'          => 200,
                'empty_message'       => '',
                'custom_class'        => '',
                'primary_color'       => '#333333',
                'text_color'          => '#333333',
                'background_color'    => '#ffffff',
                'border_radius'       => 8,
                'date_format'         => 'l, F j, Y',
                'date_format_custom'  => '',
                'time_format'         => 'g:i a',
                'time_format_custom'  => '',
            ],
            'fields' => [
                [
                    'key'         => 'featured_count',
                    'label'       => 'Number of Featured Events',
                    'type'        => 'number',
                    'min'         => 1,
                    'max'         => 10,
                    'description' => 'How many featured events to display.',
                ],
                [
                    'key'     => 'featured_mode',
                    'label'   => 'Display Mode',
                    'type'    => 'select',
                    'options' => [
                        'upcoming' => 'Closest Upcoming',
                        'random'   => 'Random',
                    ],
                    'description' => 'How to select featured events when there are more than the display limit.',
                ],
                [
                    'key'         => 'category',
                    'label'       => 'Category',
                    'type'        => 'select',
                    'options'     => $category_options,
                    'description' => 'Filter featured events by Planning Center category.',
                ],
                [
                    'key'         => 'event_name',
                    'label'       => 'Event Name Filter',
                    'type'        => 'text',
                    'description' => 'Enter the event name as it appears in Planning Center. Events containing this text will be displayed.',
                ],
                [
                    'key'         => 'layout_style',
                    'label'       => 'Layout Style',
                    'type'        => 'select',
                    'options'     => [
                        'card'    => 'Card - Boxed layout with shadow',
                        'minimal' => 'Minimal - Clean, no border',
                        'banner'  => 'Banner - Full width with background',
                    ],
                ],
                [
                    'key'   => 'show_title',
                    'label' => 'Show Event Title',
                    'type'  => 'checkbox',
                    'description' => 'Display the event title heading.',
                ],
                [
                    'key'   => 'show_time',
                    'label' => 'Show Event Time',
                    'type'  => 'checkbox',
                ],
                [
                    'key'   => 'show_address',
                    'label' => 'Show Event Location',
                    'type'  => 'checkbox',
                ],
                [
                    'key'   => 'show_map',
                    'label' => 'Show Map',
                    'type'  => 'checkbox',
                    'description' => 'Display an embedded Google Map below the location.',
                ],
                [
                    'key'   => 'show_signup',
                    'label' => 'Include Signup',
                    'type'  => 'checkbox',
                    'description' => 'Show the signup/registration link if the event has one.',
                ],
                [
                    'key'   => 'map_height',
                    'label' => 'Map Height',
                    'type'  => 'number',
                    'min'   => 100,
                    'max'   => 500,
                    'step'  => 10,
                    'after' => 'px',
                ],
                [
                    'key'         => 'empty_message',
                    'label'       => 'Empty State Message',
                    'type'        => 'text',
                    'description' => 'Message shown when no events are found. Leave blank for the default.',
                ],
                [
                    'key'     => 'date_format',
                    'label'   => 'Date Format',
                    'type'    => 'select',
                    'options' => [
                        'D, M j, Y'  => 'D, M j, Y',
                        'l, F j, Y'  => 'l, F j, Y',
                        'l, M j, Y'  => 'l, M j, Y',
                        'F j, Y'     => 'F j, Y',
                        'M j, Y'     => 'M j, Y',
                        'm/d/Y'      => 'm/d/Y',
                        'custom'     => 'Custom',
                    ],
                    'description' => 'PHP date format. D=day abbr, l=day name, M=month abbr, F=month name, j=day, m=month number, Y=year.',
                ],
                [
                    'key'         => 'date_format_custom',
                    'label'       => 'Custom Date Format',
                    'type'        => 'text',
                    'description' => 'Enter a custom PHP date format string.',
                    'show_when'   => ['field' => 'date_format', 'value' => 'custom'],
                ],
                [
                    'key'     => 'time_format',
                    'label'   => 'Time Format',
                    'type'    => 'select',
                    'options' => [
                        'g:i a' => 'g:i a',
                        'g:i A' => 'g:i A',
                        'H:i'   => 'H:i',
                        'custom' => 'Custom',
                    ],
                    'description' => 'PHP time format. g=12-hour, H=24-hour, i=minutes, a=am/pm, A=AM/PM.',
                ],
                [
                    'key'         => 'time_format_custom',
                    'label'       => 'Custom Time Format',
                    'type'        => 'text',
                    'description' => 'Enter a custom PHP time format string.',
                    'show_when'   => ['field' => 'time_format', 'value' => 'custom'],
                ],
            ],
        ];

        $types['mypco_sunday_list'] = [
            'module'      => 'calendar',
            'module_name' => 'Calendar',
            'name'        => 'Custom List',
            'description' => 'List multiple upcoming events matching a custom filter.',
            'tag'         => 'mypco_sunday_list',
            'is_addon'    => true,
            'addon_key'   => 'calendar_shortcodes',
            'addon_name'  => 'Calendar Shortcodes Addon',
            'defaults'    => [
                'description'         => '',
                'category'            => '',
                'event_name'          => '',
                'count'               => 'auto',
                'show_time'           => true,
                'show_address'        => true,
                'empty_message'       => '',
                'custom_class'        => '',
                'primary_color'       => '#333333',
                'text_color'          => '#333333',
                'background_color'    => '#ffffff',
                'border_radius'       => 8,
                'date_format'         => 'l, F j, Y',
                'date_format_custom'  => '',
                'time_format'         => 'g:i a',
                'time_format_custom'  => '',
            ],
            'fields' => [
                [
                    'key'         => 'category',
                    'label'       => 'Category',
                    'type'        => 'select',
                    'options'     => $category_options,
                    'description' => 'Filter events by Planning Center category.',
                ],
                [
                    'key'         => 'event_name',
                    'label'       => 'Event Name Filter',
                    'type'        => 'text',
                    'description' => 'Enter the event name as it appears in Planning Center. Events containing this text will be displayed.',
                ],
                [
                    'key'     => 'count',
                    'label'   => 'Number of Events',
                    'type'    => 'select',
                    'options' => array_merge(
                        ['auto' => 'Auto (4-5 weeks based on month)'],
                        array_combine(range(1, 12), array_map(function($n) {
                            return $n . ($n === 1 ? ' Event' : ' Events');
                        }, range(1, 12)))
                    ),
                ],
                [
                    'key'   => 'show_time',
                    'label' => 'Show Event Time',
                    'type'  => 'checkbox',
                ],
                [
                    'key'   => 'show_address',
                    'label' => 'Show Event Location',
                    'type'  => 'checkbox',
                ],
                [
                    'key'         => 'empty_message',
                    'label'       => 'Empty State Message',
                    'type'        => 'text',
                    'description' => 'Message shown when no events are found. Leave blank for the default.',
                ],
                [
                    'key'     => 'date_format',
                    'label'   => 'Date Format',
                    'type'    => 'select',
                    'options' => [
                        'D, M j, Y'  => 'D, M j, Y',
                        'l, F j, Y'  => 'l, F j, Y',
                        'l, M j, Y'  => 'l, M j, Y',
                        'F j, Y'     => 'F j, Y',
                        'M j, Y'     => 'M j, Y',
                        'm/d/Y'      => 'm/d/Y',
                        'custom'     => 'Custom',
                    ],
                    'description' => 'PHP date format. D=day abbr, l=day name, M=month abbr, F=month name, j=day, m=month number, Y=year.',
                ],
                [
                    'key'         => 'date_format_custom',
                    'label'       => 'Custom Date Format',
                    'type'        => 'text',
                    'description' => 'Enter a custom PHP date format string.',
                    'show_when'   => ['field' => 'date_format', 'value' => 'custom'],
                ],
                [
                    'key'     => 'time_format',
                    'label'   => 'Time Format',
                    'type'    => 'select',
                    'options' => [
                        'g:i a' => 'g:i a',
                        'g:i A' => 'g:i A',
                        'H:i'   => 'H:i',
                        'custom' => 'Custom',
                    ],
                    'description' => 'PHP time format. g=12-hour, H=24-hour, i=minutes, a=am/pm, A=AM/PM.',
                ],
                [
                    'key'         => 'time_format_custom',
                    'label'       => 'Custom Time Format',
                    'type'        => 'text',
                    'description' => 'Enter a custom PHP time format string.',
                    'show_when'   => ['field' => 'time_format', 'value' => 'custom'],
                ],
            ],
        ];

        return $types;
    }

    /**
     * Fetch public categories from the PCO Calendar API for the category dropdown.
     *
     * Only returns tags where church_center_category is true.
     * Results are cached via the API model's transient caching.
     *
     * @return array Select options keyed by tag ID with display labels.
     */
    private function fetch_category_options() {
        if (!$this->api_model) {
            return ['' => 'All Categories'];
        }

        $params = [
            'per_page' => 100,
            'include'  => 'tag_group',
        ];

        // Use same transient key as the calendar public component for shared cache
        $transient_key = 'mypco_calendar_tags_' . md5(serialize($params));

        $response = $this->api_model->get_data_with_caching(
            'calendar',
            '/v2/tags',
            $params,
            $transient_key
        );

        $options = ['' => 'All Categories'];

        if (isset($response['error']) || empty($response['data'])) {
            return $options;
        }

        // Build tag group map
        $tag_groups = [];
        if (!empty($response['included'])) {
            foreach ($response['included'] as $item) {
                if ($item['type'] === 'TagGroup') {
                    $tag_groups[$item['id']] = $item['attributes']['name'] ?? '';
                }
            }
        }

        // Collect public categories, sorted by group then name
        $tags = [];
        foreach ($response['data'] as $tag) {
            $is_public = $tag['attributes']['church_center_category'] ?? false;
            if (!$is_public) {
                continue;
            }

            $tag_id = $tag['id'];
            $tag_name = $tag['attributes']['name'] ?? '';
            $tag_group_id = $tag['relationships']['tag_group']['data']['id'] ?? null;
            $group_name = $tag_group_id ? ($tag_groups[$tag_group_id] ?? '') : '';

            $tags[] = [
                'id'         => $tag_id,
                'name'       => $tag_name,
                'group_name' => $group_name,
            ];
        }

        usort($tags, function ($a, $b) {
            $group_cmp = strcmp($a['group_name'], $b['group_name']);
            if ($group_cmp !== 0) return $group_cmp;
            return strcmp($a['name'], $b['name']);
        });

        foreach ($tags as $tag) {
            $options[$tag['id']] = $tag['name'];
        }

        return $options;
    }

    /**
     * Load the public component.
     */
    private function load_public_component() {
        require_once $this->get_module_path('public/class-calendar-shortcodes-public.php');
        $this->public = new MyPCO_Calendar_Shortcodes_Public($this->loader, $this->api_model);
        $this->public->init();
    }

    /**
     * Get path within this module.
     */
    private function get_module_path($relative_path) {
        return MYPCO_PLUGIN_DIR . 'inc/modules/calendar-shortcodes/' . $relative_path;
    }
}
