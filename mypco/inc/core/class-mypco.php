<?php
/**
 * The core plugin class — Blended Architecture Orchestrator.
 *
 * This class wires together the three layers of the blended architecture:
 *
 * 1. SKELETON (MyPCO Loader): Centralized hook registration.
 * 2. MUSCLE  (Repositories):  Data access abstraction via the Repository Pattern.
 * 3. SKIN    (React UI):      Gutenberg blocks and React-powered settings pages.
 *
 * Boot sequence:
 *   load_dependencies() → set_locale() → init_api_model()
 *   → init_repositories() → define_admin_hooks() → define_public_hooks()
 *   → run()
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
        $this->init_repositories();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        // --- Blended Architecture: Interfaces & Traits ---
        require_once MYPCO_PLUGIN_DIR . 'inc/core/interfaces/class-repository-interface.php';
        require_once MYPCO_PLUGIN_DIR . 'inc/core/interfaces/class-block-registrar-interface.php';
        require_once MYPCO_PLUGIN_DIR . 'inc/core/traits/class-has-repositories.php';

        // --- Core classes ---
        require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-loader.php';
        require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-i18n.php';
        require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-credentials-manager.php';

        // License and Update Managers (load early as other components depend on them)
        require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-license-manager.php';
        require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-update-manager.php';

        // Module system
        require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-module-base.php';
        require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-module-manager.php';
        require_once MYPCO_PLUGIN_DIR . 'inc/modules/class-mypco-modules.php';

        // API and core functionality
        require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-api-model.php';

        // --- Blended Architecture: Repositories ("Muscle") ---
        require_once MYPCO_PLUGIN_DIR . 'inc/repositories/class-event-repository.php';
        require_once MYPCO_PLUGIN_DIR . 'inc/repositories/class-service-repository.php';
        require_once MYPCO_PLUGIN_DIR . 'inc/repositories/class-publishing-repository.php';

        // --- Blended Architecture: REST API (powers the React "Skin") ---
        require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-rest-controller.php';

        // Admin pages
        require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-admin.php';
        require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-settings.php';
        require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-license-page.php';
        require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-shortcodes-admin.php';

        // Public functionality
        require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-public.php';

        $this->loader = new MyPCO_Loader();

        // Initialize update manager — routes its hooks through the loader
        $update_manager = MyPCO_Update_Manager::get_instance();
        $update_manager->init( $this->loader );
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
        $this->api_model = new MyPCO_API_Model($credentials['client_id'], $credentials['secret_key'], $timezone);
    }

    /**
     * Initialize core repositories and register them with the Loader.
     *
     * This is the "Muscle" layer. Repositories abstract away data access
     * so that modules, blocks, and settings pages never touch the API
     * model or raw queries directly.
     */
    private function init_repositories() {
        if ( ! $this->api_model ) {
            return;
        }

        $date_helper = class_exists( 'MyPCO_Date_Helper' ) ? new MyPCO_Date_Helper() : null;

        $this->loader->register_repository(
            'events',
            new MyPCO_Event_Repository( $this->api_model, $date_helper )
        );

        $this->loader->register_repository(
            'services',
            new MyPCO_Service_Repository( $this->api_model )
        );

        $this->loader->register_repository(
            'publishing',
            new MyPCO_Publishing_Repository( $this->api_model )
        );
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
                $module_file = MYPCO_PLUGIN_DIR . 'inc/modules/' . $config['file'];

                if (file_exists($module_file)) {
                    require_once $module_file;

                    if (class_exists($config['class'])) {
                        $module_instance = new $config['class']($this->loader, $this->api_model);

                        // Blended Architecture: let the module register its repositories
                        $module_instance->register_repositories();

                        // Blended Architecture: collect block registrars from modules
                        $block_registrar = $module_instance->get_block_registrar();
                        if ( $block_registrar ) {
                            $this->loader->add_block_registrar( $block_registrar );
                        }

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

        // 5. Initialize the React-powered Settings page (after Modules)
        $plugin_settings = new MyPCO_Settings( $this->version, $this->api_model );
        $this->loader->add_action('admin_menu', $plugin_settings, 'add_settings_menu', 99);
        $this->loader->add_action('admin_enqueue_scripts', $plugin_settings, 'enqueue_assets');

        // 6. Initialize the License Page — all hooks routed through the loader
        $license_page = new MyPCO_License_Page($this->plugin_name, $this->version);
        $license_page->init($this->loader);

        // 7. Initialize Stripe handler — hooks through the loader
        require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-stripe-handler.php';
        $stripe_handler = new MyPCO_Stripe_Handler();
        $this->loader->add_action('rest_api_init', $stripe_handler, 'register_webhook_endpoint');

        // 8. Initialize Google Forms webhook — hooks through the loader
        require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-google-forms-webhook.php';
        $forms_webhook = new MyPCO_Google_Forms_Webhook();
        $this->loader->add_action('rest_api_init', $forms_webhook, 'register_rest_route');

        // 9. Blended Architecture: Register REST API routes for React UI
        $rest_controller = new MyPCO_REST_Controller( $this->loader );
        $this->loader->add_action( 'rest_api_init', $rest_controller, 'register_routes' );
    }

    private function define_public_hooks() {
        $plugin_public = new MyPCO_Public($this->plugin_name, $this->version);
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    public function run() { $this->loader->run(); }
}
