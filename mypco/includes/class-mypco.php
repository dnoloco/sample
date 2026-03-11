<?php
/**
 * The core plugin class.
 */

class MyPCO {

    protected $loader;
    protected $plugin_name;
    protected $version;
    protected $api_model;
    protected $modules = [];
    protected $modules_ui;

    public function __construct() {
        $this->version = MYPCO_VERSION;
        $this->plugin_name = 'mypco-online';

        $this->load_dependencies();
        $this->set_locale();
        $this->init_api_model();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once MYPCO_PLUGIN_DIR . 'includes/class-mypco-loader.php';
        require_once MYPCO_PLUGIN_DIR . 'includes/class-mypco-i18n.php';
        require_once MYPCO_PLUGIN_DIR . 'includes/class-mypco-credentials-manager.php';

        // License and Update Managers (load early as other components depend on them)
        require_once MYPCO_PLUGIN_DIR . 'includes/class-mypco-license-manager.php';
        require_once MYPCO_PLUGIN_DIR . 'includes/class-mypco-update-manager.php';

        // Module system
        require_once MYPCO_PLUGIN_DIR . 'includes/class-mypco-module-base.php';
        require_once MYPCO_PLUGIN_DIR . 'includes/class-mypco-module-manager.php';
        require_once MYPCO_PLUGIN_DIR . 'modules/class-mypco-modules.php';

        // API and core functionality
        require_once MYPCO_PLUGIN_DIR . 'includes/class-mypco-api-model.php';

        // Admin pages
        require_once MYPCO_PLUGIN_DIR . 'admin/class-mypco-admin.php';
        require_once MYPCO_PLUGIN_DIR . 'admin/class-mypco-settings-page.php';
        require_once MYPCO_PLUGIN_DIR . 'admin/class-mypco-license-page.php';
        require_once MYPCO_PLUGIN_DIR . 'admin/class-mypco-shortcodes-admin.php';

        // Public functionality
        require_once MYPCO_PLUGIN_DIR . 'public/class-mypco-public.php';

        $this->loader = new MyPCO_Loader();

        // Initialize update manager for automatic updates
        $update_manager = MyPCO_Update_Manager::get_instance();
        $update_manager->init();
    }

    private function set_locale() {
        $plugin_i18n = new MyPCO_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    private function init_api_model() {
        $credentials = MyPCO_Credentials_Manager::get_pco_credentials();

        if (empty($credentials['client_id']) || empty($credentials['secret_key'])) {
            return;
        }

        $timezone = get_option('timezone_string') ?: 'America/Chicago';
        // Initialize the model and store it in the class property
        $this->api_model = new MyPCO_API_Model($credentials['client_id'], $credentials['secret_key'], $timezone);
    }

    private function load_modules() {
        $module_manager = new MyPCO_Module_Manager($this->loader, $this->api_model);
        $module_manager->init_modules();

        // Initialize UI Controller for module management page
        $modules_ui = new MyPCO_Modules($this->loader, $this->api_model);
        $modules_ui->init();

        $this->modules = $module_manager->get_modules();

        // Load enabled modules dynamically
        $available_modules = $module_manager->get_modules();

        foreach ($available_modules as $key => $config) {
            if ($module_manager->is_module_enabled($key) && $module_manager->can_enable_module($key)) {
                $module_file = MYPCO_PLUGIN_DIR . 'modules/' . $config['file'];

                if (file_exists($module_file)) {
                    require_once $module_file;

                    if (class_exists($config['class'])) {
                        $module_instance = new $config['class']($this->loader, $this->api_model);
                        $module_instance->init();
                    }
                }
            }
        }
    }

    private function define_admin_hooks() {
        // 1. Initialize the main Admin class
        $plugin_admin = new MyPCO_Admin($this->plugin_name, $this->version, $this->loader, $this->api_model);

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Add Dashboard menu first (priority 10)
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');

        // 2. Load the Module system - active modules add their menus at default priority
        // This means active module menus appear after Dashboard
        $this->load_modules();

        // 3. Initialize the centralized Shortcodes admin page
        $shortcodes_admin = new MyPCO_Shortcodes_Admin($this->loader, $this->api_model);
        $shortcodes_admin->init();

        // 4. Add Modules menu at later priority (after active module menus)
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_modules_menu', 99);

        // 5. Initialize the API Settings Page at later priority (after Modules)
        $plugin_settings = new MyPCO_Settings_Page($this->plugin_name, $this->version, $this->api_model);
        $this->loader->add_action('admin_menu', $plugin_settings, 'add_settings_menu', 99);
        $this->loader->add_action('admin_init', $plugin_settings, 'handle_settings_save');

        // 6. Initialize the License Page (hidden from menu)
        $license_page = new MyPCO_License_Page($this->plugin_name, $this->version);
        $license_page->init($this->loader);
    }

    private function define_public_hooks() {
        $plugin_public = new MyPCO_Public($this->plugin_name, $this->version);
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    public function run() { $this->loader->run(); }
}
