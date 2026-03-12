<?php
/**
 * Module Manager
 *
 * Handles registration, initialization, and management of plugin modules.
 * Supports tiered module access (free, freemium, premium).
 */

if (!defined('ABSPATH')) {
    exit;
}

class SimplePCO_Module_Manager {

    /**
     * Array of registered modules.
     */
    private $modules = [];

    /**
     * The loader instance.
     */
    private $loader;

    /**
     * The API model instance.
     */
    private $api_model;

    /**
     * License manager instance.
     */
    private $license_manager;

    /**
     * Initialize the module manager.
     */
    public function __construct($loader, $api_model) {
        $this->loader = $loader;
        $this->api_model = $api_model;

        // Initialize license manager
        if (class_exists('SimplePCO_License_Manager')) {
            $this->license_manager = SimplePCO_License_Manager::get_instance();
        }
    }

    /**
     * Get all available modules with their configurations.
     *
     * Module tiers:
     * - 'free': Completely free, no license needed
     * - 'freemium': Has free features + premium features requiring license
     * - 'premium': Requires license to use at all
     *
     * @return array
     */
    public function get_modules() {
        return [
            'services' => [
                'name' => 'Services',
                'description' => 'Manage service plans, teams, and volunteer scheduling.',
                'tier' => 'freemium',
                'requires_license' => false, // Base features are free
                'min_license_tier' => 'starter',
                'features' => [
                    'free' => [
                        'view_service_types',
                        'view_plans',
                        'view_team_members'
                    ],
                    'premium' => [
                        'bulk_messaging',
                        'advanced_reports',
                        'schedule_management'
                    ]
                ],
                'file' => 'services/class-services-module.php',
                'class' => 'SimplePCO_Services_Module'
            ],
            'calendar' => [
                'name' => 'Calendar',
                'description' => 'Display and sync Planning Center events on your website.',
                'tier' => 'freemium',
                'requires_license' => false,
                'min_license_tier' => 'starter',
                'features' => [
                    'free' => [
                        'display_events',
                        'basic_shortcode',
                        'list_view',
                        'month_view',
                        'gallery_view'
                    ],
                    'premium' => [
                        'custom_css',
                        'calendar_widgets',
                        'custom_templates',
                        'ical_export',
                        'event_filtering',
                        'calendar_sync'
                    ]
                ],
                'file' => 'calendar/class-calendar-module.php',
                'class' => 'SimplePCO_Calendar_Module'
            ],
            'groups' => [
                'name' => 'Groups',
                'description' => 'Display and manage Planning Center groups.',
                'tier' => 'premium',
                'requires_license' => true,
                'min_license_tier' => 'professional',
                'features' => [
                    'free' => [],
                    'premium' => [
                        'display_groups',
                        'group_finder',
                        'group_registration',
                        'leader_tools'
                    ]
                ],
                'file' => 'groups/class-groups-module.php',
                'class' => 'SimplePCO_Groups_Module'
            ],
            'signups' => [
                'name' => 'Signups',
                'description' => 'Event registrations with Google Forms and Stripe integration.',
                'tier' => 'premium',
                'requires_license' => true,
                'min_license_tier' => 'professional',
                'features' => [
                    'free' => [],
                    'premium' => [
                        'event_signups',
                        'payment_processing',
                        'registration_management',
                        'attendee_tracking'
                    ]
                ],
                'file' => 'signups/class-signups-module.php',
                'class' => 'SimplePCO_Signups_Module'
            ],
            'contacts' => [
                'name' => 'Contacts',
                'description' => 'Send mass SMS via Clearstream integration.',
                'tier' => 'premium',
                'requires_license' => true,
                'min_license_tier' => 'professional',
                'features' => [
                    'free' => [],
                    'premium' => [
                        'send_sms',
                        'message_templates',
                        'scheduled_messages',
                        'message_history'
                    ]
                ],
                'file' => 'contacts/class-contacts-module.php',
                'class' => 'SimplePCO_Contacts_Module'
            ],
            'series' => [
                'name' => 'Series',
                'description' => 'Manage and display message archives with series, speakers, topics, and media.',
                'tier' => 'freemium',
                'requires_license' => false,
                'min_license_tier' => 'starter',
                'features' => [
                    'free' => [
                        'manage_messages',
                        'manage_speakers',
                        'manage_series',
                        'manage_topics',
                        'basic_shortcode',
                        'message_list_view'
                    ],
                    'premium' => [
                        'featured_message',
                        'series_display',
                        'custom_templates',
                        'advanced_filtering',
                        'custom_css'
                    ]
                ],
                'file' => 'series/class-series-module.php',
                'class' => 'SimplePCO_Series_Module'
            ],
            'calendar_shortcodes' => [
                'name' => 'Calendar Shortcodes',
                'description' => 'Custom single event and event list shortcodes for Planning Center Calendar.',
                'tier' => 'freemium',
                'requires_license' => false,
                'min_license_tier' => 'starter',
                'is_addon' => true,
                'features' => [
                    'free' => [
                        'custom_event_shortcode',
                        'custom_list_shortcode',
                        'google_maps_links'
                    ],
                    'premium' => []
                ],
                'file' => 'calendar-shortcodes/class-calendar-shortcodes-module.php',
                'class' => 'SimplePCO_Calendar_Shortcodes_Module'
            ],
        ];
    }

    /**
     * Initialize all enabled modules.
     */
    public function init_modules() {
        $active_settings = get_option('simplepco_active_modules', []);
        $available = $this->get_modules();

        foreach ($available as $key => $config) {
            $is_enabled = isset($active_settings[$key]['enabled']) && $active_settings[$key]['enabled'] === true;

            $this->modules[$key] = array_merge($config, [
                'key' => $key,
                'enabled' => $is_enabled,
                'access_status' => $this->get_module_access_status($key, $config)
            ]);
        }

        // Allow add-ons to register their modules
        do_action('simplepco_register_modules', $this);
    }

    /**
     * Register a module (used by add-ons).
     *
     * @param string $key Unique module identifier
     * @param array $config Module configuration
     */
    public function register_module($key, $config) {
        $defaults = [
            'name' => '',
            'description' => '',
            'tier' => 'premium',
            'requires_license' => true,
            'min_license_tier' => 'starter',
            'features' => ['free' => [], 'premium' => []],
            'file' => '',
            'class' => '',
            'enabled' => false,
            'is_addon' => true
        ];

        $this->modules[$key] = wp_parse_args($config, $defaults);
        $this->modules[$key]['key'] = $key;
        $this->modules[$key]['access_status'] = $this->get_module_access_status($key, $this->modules[$key]);
    }

    /**
     * Get a specific module.
     *
     * @param string $key
     * @return array|null
     */
    public function get_module($key) {
        return isset($this->modules[$key]) ? $this->modules[$key] : null;
    }

    /**
     * Get all registered modules.
     *
     * @return array
     */
    public function get_registered_modules() {
        return $this->modules;
    }

    /**
     * Get modules filtered by tier.
     *
     * @param string $tier
     * @return array
     */
    public function get_modules_by_tier($tier) {
        return array_filter($this->modules, function($module) use ($tier) {
            return $module['tier'] === $tier;
        });
    }

    /**
     * Check if a module is enabled.
     *
     * @param string $key
     * @return bool
     */
    public function is_module_enabled($key) {
        $active_modules = get_option('simplepco_active_modules', []);
        return isset($active_modules[$key]['enabled']) && $active_modules[$key]['enabled'] === true;
    }

    /**
     * Check if module can be enabled (license check).
     *
     * @param string $key
     * @return bool
     */
    public function can_enable_module($key) {
        $module = $this->get_module($key);

        if (!$module) {
            return false;
        }

        // Free and freemium modules can always be enabled
        if ($module['tier'] === 'free' || $module['tier'] === 'freemium') {
            return true;
        }

        // Premium modules require license
        if ($module['tier'] === 'premium') {
            return $this->has_license_for_module($key);
        }

        return false;
    }

    /**
     * Check if user has license for a module.
     *
     * @param string $key
     * @return bool
     */
    public function has_license_for_module($key) {
        if (!$this->license_manager) {
            return false;
        }

        return $this->license_manager->has_module_access($key);
    }

    /**
     * Get module access status.
     *
     * @param string $key
     * @param array $config
     * @return string 'available', 'locked', 'upgrade_required'
     */
    public function get_module_access_status($key, $config) {
        // Free modules are always available
        if ($config['tier'] === 'free') {
            return 'available';
        }

        // Freemium modules are available (but premium features may be locked)
        if ($config['tier'] === 'freemium') {
            return 'available';
        }

        // Premium modules require license check
        if ($config['tier'] === 'premium') {
            if ($this->has_license_for_module($key)) {
                return 'available';
            }

            // Check if they have a license but wrong tier
            if ($this->license_manager && $this->license_manager->is_license_active()) {
                return 'upgrade_required';
            }

            return 'locked';
        }

        return 'locked';
    }

    /**
     * Enable a module.
     *
     * @param string $key
     * @return bool
     */
    public function enable_module($key) {
        if (!$this->can_enable_module($key)) {
            return false;
        }

        $active_modules = get_option('simplepco_active_modules', []);
        $active_modules[$key] = [
            'enabled' => true,
            'enabled_at' => time()
        ];
        update_option('simplepco_active_modules', $active_modules);

        if (isset($this->modules[$key])) {
            $this->modules[$key]['enabled'] = true;
        }

        do_action('simplepco_module_enabled', $key);
        return true;
    }

    /**
     * Disable a module.
     *
     * @param string $key
     * @return bool
     */
    public function disable_module($key) {
        $active_modules = get_option('simplepco_active_modules', []);

        if (isset($active_modules[$key])) {
            $active_modules[$key]['enabled'] = false;
            $active_modules[$key]['disabled_at'] = time();
        }

        update_option('simplepco_active_modules', $active_modules);

        if (isset($this->modules[$key])) {
            $this->modules[$key]['enabled'] = false;
        }

        do_action('simplepco_module_disabled', $key);
        return true;
    }

    /**
     * Check if a feature is available (for freemium modules).
     *
     * @param string $module_key
     * @param string $feature
     * @return bool
     */
    public function is_feature_available($module_key, $feature) {
        $module = $this->get_module($module_key);

        if (!$module) {
            return false;
        }

        // Check free features
        if (isset($module['features']['free']) && in_array($feature, $module['features']['free'])) {
            return true;
        }

        // Check premium features (requires license)
        if (isset($module['features']['premium']) && in_array($feature, $module['features']['premium'])) {
            return $this->has_license_for_module($module_key);
        }

        return false;
    }

    /**
     * Get modules grouped by access status for UI display.
     *
     * @return array
     */
    public function get_modules_grouped() {
        $grouped = [
            'free' => [],
            'freemium' => [],
            'premium_available' => [],
            'premium_locked' => []
        ];

        foreach ($this->modules as $key => $module) {
            if ($module['tier'] === 'free') {
                $grouped['free'][$key] = $module;
            } elseif ($module['tier'] === 'freemium') {
                $grouped['freemium'][$key] = $module;
            } elseif ($module['access_status'] === 'available') {
                $grouped['premium_available'][$key] = $module;
            } else {
                $grouped['premium_locked'][$key] = $module;
            }
        }

        return $grouped;
    }
}
