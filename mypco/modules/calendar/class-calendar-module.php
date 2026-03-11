<?php
/**
 * Calendar Module — Blended Architecture Orchestrator
 *
 * This class demonstrates the complete blended architecture pattern:
 *
 * 1. SKELETON: Uses the centralized Loader for all hook registration.
 * 2. MUSCLE:   Registers an Event Repository so that admin, public,
 *              and block code never touches the API model directly.
 * 3. SKIN:     Provides a Block Registrar for the Gutenberg
 *              "Calendar Events" block with live preview.
 *
 * The admin and public components still work as before (shortcodes,
 * settings pages) but now they can use $this->repository('events')
 * for all data access instead of calling the API model.
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
            'gallery_view',
            'gutenberg_block'    // NEW: Gutenberg block is free
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

    // =========================================================================
    // Blended Architecture: Repository Registration ("Muscle")
    // =========================================================================

    /**
     * Register the Event Repository with the Loader.
     *
     * This is called by the main MyPCO class before init(), ensuring
     * the repository is available to all components (admin, public, blocks).
     */
    public function register_repositories() {
        // The core Event Repository is already registered by the main plugin.
        // Modules can register additional repositories here if needed.
        // For example, a calendar-specific settings repository:
        //   $this->loader->register_repository('calendar_settings', new MyPCO_Calendar_Settings_Repository());
    }

    // =========================================================================
    // Blended Architecture: Block Registration ("Skin")
    // =========================================================================

    /**
     * Return the block registrar for the Calendar Events Gutenberg block.
     *
     * @return MyPCO_Block_Registrar_Interface|null
     */
    public function get_block_registrar() {
        $event_repo = $this->repository( 'events' );

        if ( ! $event_repo ) {
            return null;
        }

        require_once $this->get_module_path( 'class-calendar-block-registrar.php' );
        return new MyPCO_Calendar_Block_Registrar( $event_repo );
    }

    // =========================================================================
    // Module Initialization (Original + Blended)
    // =========================================================================

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
