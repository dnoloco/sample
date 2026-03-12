<?php
/**
 * License Manager
 *
 * Handles license validation, activation, and status tracking.
 * Communicates with the remote license server.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SimplePCO_License_Manager {

    /**
     * Singleton instance.
     */
    private static $instance = null;

    /**
     * License server API URL.
     */
    private $api_url;

    /**
     * Cache duration for license checks (in seconds).
     * Default: 12 hours
     */
    private $cache_duration = 43200;

    /**
     * Cached license data.
     */
    private $license_data = null;

    /**
     * License tier hierarchy (lower index = lower tier).
     */
    private $tier_hierarchy = ['starter', 'professional', 'agency'];

    /**
     * Get singleton instance.
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor for singleton.
     */
    private function __construct() {
        // Set API URL from constant (defined in simplepco.php)
        $this->api_url = defined('SIMPLEPCO_LICENSE_API_URL') ? SIMPLEPCO_LICENSE_API_URL : 'https://your-site.com/simplepco-license/api.php';

        // Allow filtering the API URL (for testing or custom deployments)
        $this->api_url = apply_filters('simplepco_license_api_url', $this->api_url);

        // Load cached license data
        $this->license_data = get_transient('simplepco_license_data');
    }

    /**
     * Get the stored license key.
     *
     * @return string|null
     */
    public function get_license_key() {
        return get_option('simplepco_license_key', null);
    }

    /**
     * Get the current site URL (normalized).
     *
     * @return string
     */
    public function get_site_url() {
        $url = home_url();
        $url = strtolower($url);
        $url = preg_replace('#^https?://#', '', $url);
        $url = rtrim($url, '/');
        return $url;
    }

    /**
     * Check if license is active and valid.
     *
     * @param bool $force_refresh Force refresh from server
     * @return bool
     */
    public function is_license_active($force_refresh = false) {
        $license_data = $this->get_license_data($force_refresh);

        if (!$license_data || !isset($license_data['status'])) {
            return false;
        }

        return $license_data['status'] === 'active' && $license_data['is_site_activated'];
    }

    /**
     * Get cached or fresh license data.
     *
     * @param bool $force_refresh
     * @return array|null
     */
    public function get_license_data($force_refresh = false) {
        // Return cached data if available and not forcing refresh
        if (!$force_refresh && $this->license_data !== false && $this->license_data !== null) {
            return $this->license_data;
        }

        // Get license key
        $license_key = $this->get_license_key();
        if (empty($license_key)) {
            return null;
        }

        // Validate with server
        $response = $this->api_request('validate', [
            'license_key' => $license_key,
            'site_url' => $this->get_site_url()
        ]);

        if ($response && $response['success']) {
            $this->license_data = $response['data'];
            set_transient('simplepco_license_data', $this->license_data, $this->cache_duration);

            // Update legacy option for backwards compatibility
            update_option('simplepco_license_status', 'active');
        } else {
            $this->license_data = null;
            delete_transient('simplepco_license_data');
            update_option('simplepco_license_status', 'inactive');
        }

        return $this->license_data;
    }

    /**
     * Get the current license tier.
     *
     * @return string|null
     */
    public function get_license_tier() {
        $license_data = $this->get_license_data();
        return $license_data ? ($license_data['tier'] ?? null) : null;
    }

    /**
     * Get modules allowed by current license.
     *
     * @return array
     */
    public function get_licensed_modules() {
        $license_data = $this->get_license_data();
        return $license_data ? ($license_data['modules'] ?? []) : [];
    }

    /**
     * Check if a specific module is accessible with current license.
     *
     * @param string $module_key
     * @return bool
     */
    public function has_module_access($module_key) {
        if (!$this->is_license_active()) {
            return false;
        }

        $licensed_modules = $this->get_licensed_modules();
        return in_array($module_key, $licensed_modules);
    }

    /**
     * Check if current license tier meets minimum requirement.
     *
     * @param string $required_tier
     * @return bool
     */
    public function meets_tier_requirement($required_tier) {
        $current_tier = $this->get_license_tier();

        if (!$current_tier) {
            return false;
        }

        $current_index = array_search($current_tier, $this->tier_hierarchy);
        $required_index = array_search($required_tier, $this->tier_hierarchy);

        if ($current_index === false || $required_index === false) {
            return false;
        }

        return $current_index >= $required_index;
    }

    /**
     * Activate license on this site.
     *
     * @param string $license_key
     * @return array Response with success status and message
     */
    public function activate_license($license_key) {
        $response = $this->api_request('activate', [
            'license_key' => $license_key,
            'site_url' => $this->get_site_url(),
            'site_name' => get_bloginfo('name')
        ]);

        if ($response && $response['success']) {
            // Store the license key
            update_option('simplepco_license_key', $license_key);
            update_option('simplepco_license_status', 'active');

            // Cache the license data
            $this->license_data = array_merge(
                ['status' => 'active', 'is_site_activated' => true],
                $response['data'] ?? []
            );
            set_transient('simplepco_license_data', $this->license_data, $this->cache_duration);

            return [
                'success' => true,
                'message' => $response['message'],
                'data' => $response['data']
            ];
        }

        return [
            'success' => false,
            'message' => $response ? $response['message'] : 'Failed to connect to license server'
        ];
    }

    /**
     * Deactivate license from this site.
     *
     * @return array Response with success status and message
     */
    public function deactivate_license() {
        $license_key = $this->get_license_key();

        if (empty($license_key)) {
            return [
                'success' => false,
                'message' => 'No license key found'
            ];
        }

        $response = $this->api_request('deactivate', [
            'license_key' => $license_key,
            'site_url' => $this->get_site_url()
        ]);

        // Clear local data regardless of server response
        delete_option('simplepco_license_key');
        update_option('simplepco_license_status', 'inactive');
        delete_transient('simplepco_license_data');
        $this->license_data = null;

        if ($response && $response['success']) {
            return [
                'success' => true,
                'message' => $response['message']
            ];
        }

        return [
            'success' => true,
            'message' => 'License deactivated locally'
        ];
    }

    /**
     * Make API request to license server.
     *
     * @param string $action API action
     * @param array $data Request data
     * @return array|null
     */
    private function api_request($action, $data = []) {
        $url = add_query_arg('action', $action, $this->api_url);

        $response = wp_remote_post($url, [
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data)
        ]);

        if (is_wp_error($response)) {
            error_log('SimplePCO License API Error: ' . $response->get_error_message());
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        return $result;
    }

    /**
     * Get license status summary for display.
     *
     * @return array
     */
    public function get_status_summary() {
        $license_data = $this->get_license_data();

        if (!$license_data) {
            return [
                'status' => 'inactive',
                'status_label' => 'No License',
                'tier' => null,
                'tier_label' => 'Free',
                'expires_at' => null,
                'modules' => [],
                'sites_remaining' => 0
            ];
        }

        $tier_labels = [
            'starter' => 'Starter',
            'professional' => 'Professional',
            'agency' => 'Agency'
        ];

        return [
            'status' => $license_data['status'],
            'status_label' => ucfirst($license_data['status']),
            'tier' => $license_data['tier'] ?? null,
            'tier_label' => $tier_labels[$license_data['tier']] ?? 'Unknown',
            'expires_at' => $license_data['expires_at'] ?? null,
            'modules' => $license_data['modules'] ?? [],
            'sites_remaining' => $license_data['sites_remaining'] ?? 0,
            'customer_email' => $license_data['customer_email'] ?? ''
        ];
    }

    /**
     * Clear all cached license data.
     */
    public function clear_cache() {
        delete_transient('simplepco_license_data');
        $this->license_data = null;
    }
}
