<?php
/**
 * Base Module Class — Blended Architecture
 *
 * All modules extend this class. In the blended architecture it provides:
 *
 * 1. Skeleton: Access to the centralized Loader for hook registration.
 * 2. Muscle:   A register_repositories() hook so modules can provide
 *              their own Repository classes for clean data access.
 * 3. Skin:     A get_block_registrar() hook so modules can declare
 *              their Gutenberg blocks and React-powered UI components.
 *
 * Subclasses override init(), and optionally register_repositories()
 * and get_block_registrar().
 */

abstract class SimplePCO_Module_Base {

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

    // =========================================================================
    // Blended Architecture: Repository Pattern ("Muscle")
    // =========================================================================

    /**
     * Register this module's repositories with the Loader.
     *
     * Override in subclasses to register module-specific repositories.
     * Repositories are the data access layer — display code should
     * never call the API model or run raw queries directly.
     *
     * Example override:
     *   public function register_repositories() {
     *       $this->loader->register_repository(
     *           'events',
     *           new SimplePCO_Event_Repository( $this->api_model )
     *       );
     *   }
     *
     * @return void
     */
    public function register_repositories() {
        // Default: no repositories. Override in subclasses.
    }

    /**
     * Convenience: get a repository from the Loader.
     *
     * @param string $key Repository identifier.
     * @return SimplePCO_Repository_Interface|null
     */
    protected function repository( $key ) {
        return $this->loader->get_repository( $key );
    }

    // =========================================================================
    // Blended Architecture: Block Registration ("Skin")
    // =========================================================================

    /**
     * Return a block registrar if this module provides Gutenberg blocks.
     *
     * Override in subclasses to return a SimplePCO_Block_Registrar_Interface
     * instance. The Loader will call register_blocks() on init at
     * priority 12 (after CPTs).
     *
     * @return SimplePCO_Block_Registrar_Interface|null
     */
    public function get_block_registrar() {
        return null;
    }

    // =========================================================================
    // Original helpers (unchanged)
    // =========================================================================

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
        $version = $version ?: SIMPLEPCO_VERSION;
        wp_enqueue_style($handle, $file, $dependencies, $version);
    }

    /**
     * Enqueue module-specific scripts.
     */
    protected function enqueue_script($handle, $file, $dependencies = [], $version = null, $in_footer = true) {
        $version = $version ?: SIMPLEPCO_VERSION;
        wp_enqueue_script($handle, $file, $dependencies, $version, $in_footer);
    }

    /**
     * Get data with caching using the API model.
     *
     * @deprecated 3.0.0 Use a Repository instead of calling the API model directly.
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

        $template_path = SIMPLEPCO_PLUGIN_DIR . "templates/{$this->module_key}/{$template_name}.php";

        if (file_exists($template_path)) {
            include $template_path;
        }
    }

    /**
     * Render a template file and return the output as a string.
     *
     * @param string $template_name Template filename without .php extension.
     * @param array  $variables     Variables to extract into the template scope.
     * @return string Rendered HTML.
     */
    protected function fetch_template($template_name, $variables = []) {
        ob_start();
        $this->render_template($template_name, $variables);
        return ob_get_clean();
    }

    // =========================================================================
    // Module metadata (unchanged)
    // =========================================================================

    public function get_module_key() {
        return $this->module_key;
    }

    public function get_module_name() {
        return $this->module_name;
    }

    public function get_module_description() {
        return $this->module_description;
    }

    public function get_tier() {
        return $this->tier;
    }

    public function requires_license() {
        return $this->requires_license;
    }

    public function get_min_license_tier() {
        return $this->min_license_tier;
    }

    public function get_dependencies() {
        return $this->dependencies;
    }

    public function get_features() {
        return $this->features;
    }

    public function is_feature_available($feature) {
        if (in_array($feature, $this->features['free'])) {
            return true;
        }

        if (in_array($feature, $this->features['premium'])) {
            return $this->has_premium_access();
        }

        return false;
    }

    public function has_premium_access() {
        if (!class_exists('SimplePCO_License_Manager')) {
            return false;
        }

        $license_manager = SimplePCO_License_Manager::get_instance();
        return $license_manager->has_module_access($this->module_key);
    }

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
