<?php
/**
 * Services Module - Main Orchestrator
 *
 * This module handles PCO Services integration including:
 * - Service plans management
 * - Team member lookup and management
 * - Team reports
 * - Message composition (Clearstream integration)
 *
 * Note: This is primarily an admin-only module with no public component.
 */

require_once MYPCO_PLUGIN_DIR . 'includes/class-mypco-module-base.php';

class MyPCO_Services_Module extends MyPCO_Module_Base {

    protected $module_key = 'services';
    protected $module_name = 'Services';
    protected $module_description = 'Manage service plans, teams, and volunteer scheduling.';

    /**
     * Module tier: freemium (basic features free, advanced features premium)
     */
    protected $tier = 'freemium';
    protected $requires_license = false;
    protected $min_license_tier = 'starter';

    /**
     * Features available in this module
     */
    protected $features = [
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
    ];

    /**
     * Admin component instance
     */
    private $admin;

    /**
     * Initialize the Services module.
     */
    public function init() {
        // Services module is admin-only
        if (is_admin()) {
            $this->load_admin_component();
        }
    }

    /**
     * Load the admin component.
     */
    private function load_admin_component() {
        require_once $this->get_module_path('admin/class-services-admin.php');
        $this->admin = new MyPCO_Services_Admin($this->loader, $this->api_model);
        $this->admin->init();
    }

    /**
     * Get path within this module.
     */
    private function get_module_path($relative_path) {
        return MYPCO_PLUGIN_DIR . 'modules/services/' . $relative_path;
    }
}
