<?php
/**
 * Update Manager
 *
 * Handles automatic plugin updates from the license server.
 * Integrates with WordPress plugin update system.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SimplePCO_Update_Manager {

    /**
     * Singleton instance.
     */
    private static $instance = null;

    /**
     * Plugin slug.
     */
    private $plugin_slug = 'simplepco';

    /**
     * Plugin basename.
     */
    private $plugin_basename;

    /**
     * License server API URL.
     */
    private $api_url;

    /**
     * Cache key for update data.
     */
    private $cache_key = 'simplepco_update_data';

    /**
     * Cache duration (12 hours).
     */
    private $cache_duration = 43200;

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
        $this->plugin_basename = 'simplepco/simplepco.php';

        // Set API URL from constant (defined in simplepco.php)
        $this->api_url = defined('SIMPLEPCO_LICENSE_API_URL') ? SIMPLEPCO_LICENSE_API_URL : 'https://your-site.com/simplepco-license/api.php';

        // Allow filtering the API URL
        $this->api_url = apply_filters('simplepco_license_api_url', $this->api_url);
    }

    /**
     * Initialize update hooks via the centralized loader.
     *
     * @param SimplePCO_Loader $loader The centralized hook registry.
     */
    public function init( $loader ) {
        $loader->add_filter('pre_set_site_transient_update_plugins', $this, 'check_for_updates');
        $loader->add_filter('plugins_api', $this, 'plugin_info', 20, 3);
        $loader->add_filter('upgrader_package_options', $this, 'maybe_filter_package_options');
        $loader->add_action('upgrader_process_complete', $this, 'clear_update_cache', 10, 2);
    }

    /**
     * Check for plugin updates.
     *
     * @param object $transient Update transient
     * @return object
     */
    public function check_for_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Get current version
        $current_version = isset($transient->checked[$this->plugin_basename])
            ? $transient->checked[$this->plugin_basename]
            : SIMPLEPCO_VERSION;

        // Check for update
        $update_data = $this->get_update_data($current_version);

        if ($update_data && $update_data['update_available']) {
            $plugin_data = [
                'id' => $this->plugin_basename,
                'slug' => $this->plugin_slug,
                'plugin' => $this->plugin_basename,
                'new_version' => $update_data['latest_version'],
                'url' => $update_data['changelog_url'] ?? '',
                'tested' => get_bloginfo('version'),
                'requires_php' => '7.4',
                'icons' => [
                    '1x' => SIMPLEPCO_PLUGIN_URL . 'assets/admin/images/icon-128x128.png',
                    '2x' => SIMPLEPCO_PLUGIN_URL . 'assets/admin/images/icon-256x256.png'
                ]
            ];

            // Only include package URL if license is valid
            if (isset($update_data['download_url']) && !empty($update_data['download_url'])) {
                $plugin_data['package'] = $update_data['download_url'];
            } else {
                // No package means update requires license
                $plugin_data['upgrade_notice'] = 'A valid license is required for automatic updates. Please activate your license in SimplePCO Settings.';
            }

            $transient->response[$this->plugin_basename] = (object)$plugin_data;
        } else {
            // No update available - add to no_update array
            $transient->no_update[$this->plugin_basename] = (object)[
                'id' => $this->plugin_basename,
                'slug' => $this->plugin_slug,
                'plugin' => $this->plugin_basename,
                'new_version' => $current_version,
                'url' => ''
            ];
        }

        return $transient;
    }

    /**
     * Provide plugin information for the update details popup.
     *
     * @param mixed $result
     * @param string $action
     * @param object $args
     * @return mixed
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (!isset($args->slug) || $args->slug !== $this->plugin_slug) {
            return $result;
        }

        $update_data = $this->get_update_data(SIMPLEPCO_VERSION);

        if (!$update_data) {
            return $result;
        }

        $info = new stdClass();
        $info->name = 'SimplePCO';
        $info->slug = $this->plugin_slug;
        $info->version = $update_data['latest_version'];
        $info->author = '<a href="https://example.com">David Dean</a>';
        $info->homepage = 'https://example.com/simplepco';
        $info->requires = '5.8';
        $info->tested = get_bloginfo('version');
        $info->requires_php = '7.4';
        $info->downloaded = 0;
        $info->last_updated = date('Y-m-d');
        $info->sections = [
            'description' => 'Comprehensive Planning Center Online integration with modular architecture for churches.',
            'installation' => 'Upload the plugin files to /wp-content/plugins/simplepco/, activate through WordPress.',
            'changelog' => $this->get_changelog_html($update_data)
        ];
        $info->banners = [
            'low' => SIMPLEPCO_PLUGIN_URL . 'assets/admin/images/banner-772x250.png',
            'high' => SIMPLEPCO_PLUGIN_URL . 'assets/admin/images/banner-1544x500.png'
        ];

        if (isset($update_data['download_url'])) {
            $info->download_link = $update_data['download_url'];
        }

        return $info;
    }

    /**
     * Get update data from server.
     *
     * @param string $current_version
     * @return array|null
     */
    private function get_update_data($current_version) {
        // Check cache first
        $cached = get_transient($this->cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Get license info
        $license_manager = SimplePCO_License_Manager::get_instance();
        $license_key = $license_manager->get_license_key();

        // Make API request
        $response = wp_remote_post(
            add_query_arg('action', 'check_update', $this->api_url),
            [
                'timeout' => 15,
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode([
                    'license_key' => $license_key,
                    'site_url' => $license_manager->get_site_url(),
                    'current_version' => $current_version
                ])
            ]
        );

        if (is_wp_error($response)) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!$body || !$body['success']) {
            return null;
        }

        // Cache the result
        set_transient($this->cache_key, $body['data'], $this->cache_duration);

        return $body['data'];
    }

    /**
     * Generate changelog HTML.
     *
     * @param array $update_data
     * @return string
     */
    private function get_changelog_html($update_data) {
        $html = '<h4>Version ' . esc_html($update_data['latest_version']) . '</h4>';
        $html .= '<p>Visit the <a href="' . esc_url($update_data['changelog_url'] ?? '#') . '">changelog page</a> for full details.</p>';
        return $html;
    }

    /**
     * Filter package options for updates.
     *
     * @param array $options
     * @return array
     */
    public function maybe_filter_package_options($options) {
        if (isset($options['hook_extra']['plugin']) && $options['hook_extra']['plugin'] === $this->plugin_basename) {
            // Could add license key as header for authenticated downloads
        }
        return $options;
    }

    /**
     * Clear update cache after plugin update.
     *
     * @param object $upgrader
     * @param array $options
     */
    public function clear_update_cache($upgrader, $options) {
        if ($options['action'] === 'update' && $options['type'] === 'plugin') {
            if (isset($options['plugins']) && in_array($this->plugin_basename, $options['plugins'])) {
                delete_transient($this->cache_key);
            }
        }
    }

    /**
     * Force check for updates (clear cache and check).
     *
     * @return array|null
     */
    public function force_check() {
        delete_transient($this->cache_key);
        delete_site_transient('update_plugins');
        return $this->get_update_data(SIMPLEPCO_VERSION);
    }
}
