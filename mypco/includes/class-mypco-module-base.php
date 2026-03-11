<?php
/**
 * Base Module Class
 *
 * All modules should extend this class to maintain consistency.
 */

abstract class MyPCO_Module_Base {

    /**
     * The loader instance.
     */
    protected $loader;

    /**
     * The API model instance.
     */
    protected $api_model;

    /**
     * Module key/identifier.
     */
    protected $module_key;

    /**
     * Module display name.
     */
    protected $module_name;

    /**
     * Module description.
     */
    protected $module_description = '';

    /**
     * Module tier: 'core', 'free', 'freemium', or 'premium'
     *
     * - core: Always active, cannot be disabled
     * - free: Free module, can be enabled/disabled
     * - freemium: Has free features + premium features requiring license
     * - premium: Requires active license to use
     */
    protected $tier = 'free';

    /**
     * Whether this module requires a license to function.
     * For 'freemium' modules, this applies to premium features only.
     */
    protected $requires_license = false;

    /**
     * Minimum license tier required: 'starter', 'professional', 'agency'
     */
    protected $min_license_tier = 'starter';

    /**
     * Array of module keys this module depends on.
     */
    protected $dependencies = [];

    /**
     * Features this module provides.
     * Used to check if premium features are available.
     */
    protected $features = [
        'free' => [],    // Features available without license
        'premium' => []  // Features requiring license
    ];

    /**
     * Initialize the module.
     */
    public function __construct($loader, $api_model) {
        $this->loader = $loader;
        $this->api_model = $api_model;
    }

    /**
     * Initialize module functionality.
     * Override this in child classes to set up hooks and shortcodes.
     */
    abstract public function init();

    /**
     * Register a shortcode for this module.
     */
    protected function register_shortcode($tag, $callback) {
        add_shortcode($tag, [$this, $callback]);
    }

    /**
     * Enqueue module-specific styles.
     */
    protected function enqueue_style($handle, $file, $dependencies = [], $version = null) {
        $version = $version ?: MYPCO_VERSION;
        wp_enqueue_style($handle, $file, $dependencies, $version);
    }

    /**
     * Enqueue module-specific scripts.
     */
    protected function enqueue_script($handle, $file, $dependencies = [], $version = null, $in_footer = true) {
        $version = $version ?: MYPCO_VERSION;
        wp_enqueue_script($handle, $file, $dependencies, $version, $in_footer);
    }

    /**
     * Get data with caching using the API model.
     */
    protected function get_cached_data($app_domain, $endpoint_path, $params, $transient_key, $expiration = null) {
        if (!$this->api_model) {
            return ['error' => 'API model not initialized'];
        }

        return $this->api_model->get_data_with_caching($app_domain, $endpoint_path, $params, $transient_key, $expiration);
    }

    /**
     * Render a template file.
     */
    protected function render_template($template_name, $variables = []) {
        extract($variables);

        $template_path = MYPCO_PLUGIN_DIR . "modules/{$this->module_key}/templates/{$template_name}.php";

        if (file_exists($template_path)) {
            include $template_path;
        }
    }

    /**
     * Get module key.
     */
    public function get_module_key() {
        return $this->module_key;
    }

    /**
     * Get module name.
     */
    public function get_module_name() {
        return $this->module_name;
    }

    /**
     * Get module description.
     */
    public function get_module_description() {
        return $this->module_description;
    }

    /**
     * Get module tier.
     */
    public function get_tier() {
        return $this->tier;
    }

    /**
     * Check if module requires a license.
     */
    public function requires_license() {
        return $this->requires_license;
    }

    /**
     * Get minimum license tier required.
     */
    public function get_min_license_tier() {
        return $this->min_license_tier;
    }

    /**
     * Get module dependencies.
     */
    public function get_dependencies() {
        return $this->dependencies;
    }

    /**
     * Get module features.
     */
    public function get_features() {
        return $this->features;
    }

    /**
     * Check if a specific feature is available.
     *
     * @param string $feature Feature name to check
     * @return bool
     */
    public function is_feature_available($feature) {
        // Free features are always available
        if (in_array($feature, $this->features['free'])) {
            return true;
        }

        // Premium features require license check
        if (in_array($feature, $this->features['premium'])) {
            return $this->has_premium_access();
        }

        return false;
    }

    /**
     * Check if user has premium access for this module.
     *
     * @return bool
     */
    public function has_premium_access() {
        if (!class_exists('MyPCO_License_Manager')) {
            return false;
        }

        $license_manager = MyPCO_License_Manager::get_instance();
        return $license_manager->has_module_access($this->module_key);
    }

    /**
     * Get module configuration array for registration.
     *
     * @return array
     */
    public function get_module_config() {
        return [
            'key' => $this->module_key,
            'name' => $this->module_name,
            'description' => $this->module_description,
            'tier' => $this->tier,
            'requires_license' => $this->requires_license,
            'min_license_tier' => $this->min_license_tier,
            'dependencies' => $this->dependencies,
            'features' => $this->features
        ];
    }
}
