<?php
/**
 * Groups Module - Main Orchestrator
 *
 * This module handles PCO Groups integration with display shortcode.
 * 
 * Features:
 * - Display groups via shortcode
 * - Filter by campus
 * - Admin settings and cache management
 */

require_once MYPCO_PLUGIN_DIR . 'includes/class-mypco-module-base.php';

class MyPCO_Groups_Module extends MyPCO_Module_Base {

    protected $module_key = 'groups';
    protected $module_name = 'Groups';
    protected $module_description = 'Display and manage Planning Center groups.';

    /**
     * Module tier: premium (requires Professional or higher license)
     */
    protected $tier = 'premium';
    protected $requires_license = true;
    protected $min_license_tier = 'professional';

    /**
     * Features available in this module
     */
    protected $features = [
        'free' => [],
        'premium' => [
            'display_groups',
            'group_finder',
            'group_registration',
            'leader_tools'
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
     * Initialize the Groups module.
     */
    public function init() {
        // Load admin component
        if (is_admin()) {
            $this->load_admin_component();
        }
        
        // Always load public component (for shortcodes)
        $this->load_public_component();
    }

    /**
     * Load the admin component.
     */
    private function load_admin_component() {
        require_once $this->get_module_path('admin/class-groups-admin.php');
        $this->admin = new MyPCO_Groups_Admin($this->loader, $this->api_model);
        $this->admin->init();
    }

    /**
     * Load the public component.
     */
    private function load_public_component() {
        require_once $this->get_module_path('public/class-groups-public.php');
        $this->public = new MyPCO_Groups_Public($this->loader, $this->api_model);
        $this->public->init();
    }

    /**
     * Get path within this module.
     */
    private function get_module_path($relative_path) {
        return MYPCO_PLUGIN_DIR . 'modules/groups/' . $relative_path;
    }
}
