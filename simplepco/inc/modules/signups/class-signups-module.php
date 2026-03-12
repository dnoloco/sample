<?php
/**
 * Signups Module - Main Orchestrator
 *
 * This module handles event signups with Google Forms integration and Stripe payments.
 * 
 * Features:
 * - Create and manage event signups
 * - Google Forms webhook integration
 * - Stripe payment processing
 * - Registration management
 * - Waitlist functionality
 */

require_once SIMPLEPCO_PLUGIN_DIR . 'inc/core/class-simplepco-module-base.php';

class SimplePCO_Signups_Module extends SimplePCO_Module_Base {

    protected $module_key = 'signups';
    protected $module_name = 'Signups';
    protected $module_description = 'Event registrations with Google Forms and Stripe integration.';

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
            'event_signups',
            'payment_processing',
            'registration_management',
            'attendee_tracking'
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
     * Initialize the Signups module.
     */
    public function init() {
        // Load admin component
        if (is_admin()) {
            $this->load_admin_component();
        }
        
        // Load public component (for webhooks and payment processing)
        $this->load_public_component();
    }

    /**
     * Load the admin component.
     */
    private function load_admin_component() {
        require_once $this->get_module_path('admin/class-signups-admin.php');
        $this->admin = new SimplePCO_Signups_Admin($this->loader, $this->api_model);
        $this->admin->init();
    }

    /**
     * Load the public component.
     */
    private function load_public_component() {
        require_once $this->get_module_path('public/class-signups-public.php');
        $this->public = new SimplePCO_Signups_Public($this->loader, $this->api_model);
        $this->public->init();
    }

    /**
     * Get path within this module.
     */
    private function get_module_path($relative_path) {
        return SIMPLEPCO_PLUGIN_DIR . 'inc/modules/signups/' . $relative_path;
    }
}
