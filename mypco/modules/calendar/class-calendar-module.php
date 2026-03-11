<?php
/**
 * Calendar Module - Main Orchestrator
 *
 * This class coordinates the admin and public components of the Calendar module.
 */

require_once MYPCO_PLUGIN_DIR . 'includes/class-mypco-module-base.php';

class MyPCO_Calendar_Module extends MyPCO_Module_Base {

    protected $module_key = 'calendar';
    protected $module_name = 'Calendar';
    protected $module_description = 'Display and sync Planning Center events on your website.';

    /**
     * Module tier: freemium (basic display free, sync/templates premium)
     */
    protected $tier = 'freemium';
    protected $requires_license = false;
    protected $min_license_tier = 'starter';

    /**
     * Features available in this module
     *
     * Free: Basic shortcode display with all views (list, month, gallery)
     * Premium: CSS customization, widgets, templates, sync, exports
     */
    protected $features = [
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
    ];

    /**
     * Admin component instance
     */
    private $admin;

    /**
     * Public component instance
     */
    private $public;

    /**
     * Initialize the Calendar module.
     */
    public function init() {
        // Load and initialize admin component
        if (is_admin()) {
            $this->load_admin_component();
        }

        // Load and initialize public component (always loaded for shortcodes)
        $this->load_public_component();
    }

    /**
     * Load the admin component.
     */
    private function load_admin_component() {
        require_once $this->get_module_path('admin/class-calendar-admin.php');
        $this->admin = new MyPCO_Calendar_Admin($this->loader, $this->api_model);
        $this->admin->init();
    }

    /**
     * Load the public component.
     */
    private function load_public_component() {
        require_once $this->get_module_path('public/class-calendar-public.php');
        $this->public = new MyPCO_Calendar_Public($this->loader, $this->api_model);
        $this->public->init();
    }

    /**
     * Get path within this module.
     */
    private function get_module_path($relative_path) {
        return MYPCO_PLUGIN_DIR . 'modules/calendar/' . $relative_path;
    }
}
