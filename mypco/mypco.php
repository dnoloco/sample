<?php
/**
 * Plugin Name: MyPCO Online
 * Plugin URI: https://example.com/mypco-online
 * Description: Comprehensive Planning Center Online integration with blended architecture for churches
 * Version: 3.1.0
 * Author: David Dean
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: mypco-online
 * Domain Path: /languages
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/*--------------------------------------------------------------------------
 * 1. CONSTANTS
 *------------------------------------------------------------------------*/

define( 'MYPCO_VERSION',    '3.1.0' );
define( 'MYPCO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MYPCO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MYPCO_INCLUDES',   MYPCO_PLUGIN_DIR . 'inc/core/' );

if ( ! defined( 'MYPCO_LICENSE_API_URL' ) ) {
    define( 'MYPCO_LICENSE_API_URL', 'https://your-site.com/mypco-license/api.php' );
}

/*--------------------------------------------------------------------------
 * 2. ACTIVATION / DEACTIVATION
 *------------------------------------------------------------------------*/

function activate_mypco() {
    require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-activator.php';
    MyPCO_Activator::activate();
}

function deactivate_mypco() {
    require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-deactivator.php';
    MyPCO_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_mypco' );
register_deactivation_hook( __FILE__, 'deactivate_mypco' );

/*--------------------------------------------------------------------------
 * 3. LOAD DEPENDENCIES
 *------------------------------------------------------------------------*/

// Interfaces & Traits
require_once MYPCO_PLUGIN_DIR . 'inc/core/interfaces/class-repository-interface.php';
require_once MYPCO_PLUGIN_DIR . 'inc/core/interfaces/class-block-registrar-interface.php';
require_once MYPCO_PLUGIN_DIR . 'inc/core/traits/class-has-repositories.php';

// Core classes
require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-loader.php';
require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-i18n.php';
require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-credentials-manager.php';
require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-license-manager.php';
require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-update-manager.php';
require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-module-base.php';
require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-module-manager.php';
require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-api-model.php';
require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-rest-controller.php';
require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-stripe-handler.php';
require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-google-forms-webhook.php';

// Repositories ("Muscle")
require_once MYPCO_PLUGIN_DIR . 'inc/repositories/class-event-repository.php';
require_once MYPCO_PLUGIN_DIR . 'inc/repositories/class-service-repository.php';
require_once MYPCO_PLUGIN_DIR . 'inc/repositories/class-publishing-repository.php';

// Module UI
require_once MYPCO_PLUGIN_DIR . 'inc/modules/class-mypco-modules.php';

// Admin
require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-admin.php';
require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-settings.php';
require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-license-page.php';
require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-shortcodes-admin.php';

// Public
require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-public.php';

/*--------------------------------------------------------------------------
 * 4. INITIALIZE THE LOADER  (the single hook registry)
 *------------------------------------------------------------------------*/

$loader = new MyPCO_Loader();

/*--------------------------------------------------------------------------
 * 5. LOCALE
 *------------------------------------------------------------------------*/

$i18n = new MyPCO_i18n();
$loader->add_action( 'plugins_loaded', $i18n, 'load_plugin_textdomain' );

/*--------------------------------------------------------------------------
 * 6. API MODEL
 *------------------------------------------------------------------------*/

$api_model   = null;
$credentials = MyPCO_Credentials_Manager::get_pco_credentials();

if ( ! empty( $credentials['client_id'] ) && ! empty( $credentials['secret_key'] ) ) {
    $timezone  = get_option( 'timezone_string' ) ?: 'America/Chicago';
    $api_model = new MyPCO_API_Model( $credentials['client_id'], $credentials['secret_key'], $timezone );
}

/*--------------------------------------------------------------------------
 * 7. REPOSITORIES  ("Muscle" — SSP-style data access)
 *------------------------------------------------------------------------*/

if ( $api_model ) {
    $date_helper = class_exists( 'MyPCO_Date_Helper' ) ? new MyPCO_Date_Helper() : null;

    $loader->register_repository( 'events',     new MyPCO_Event_Repository( $api_model, $date_helper ) );
    $loader->register_repository( 'services',   new MyPCO_Service_Repository( $api_model ) );
    $loader->register_repository( 'publishing', new MyPCO_Publishing_Repository( $api_model ) );
}

/*--------------------------------------------------------------------------
 * 8. UPDATE MANAGER
 *------------------------------------------------------------------------*/

$update_manager = MyPCO_Update_Manager::get_instance();
$update_manager->init( $loader );

/*--------------------------------------------------------------------------
 * 9. ADMIN HOOKS
 *------------------------------------------------------------------------*/

$plugin_name = 'mypco-online';

// Dashboard
$admin = new MyPCO_Admin( $plugin_name, MYPCO_VERSION, $loader, $api_model );
$loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
$loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );
$loader->add_action( 'admin_menu',            $admin, 'add_admin_menu' );

// Modules — load enabled modules and register their hooks through the loader
$module_manager = new MyPCO_Module_Manager( $loader, $api_model );
$module_manager->init_modules();

$modules_ui = new MyPCO_Modules( $loader, $api_model );
$modules_ui->init();

foreach ( $module_manager->get_modules() as $key => $config ) {
    if ( $module_manager->is_module_enabled( $key ) && $module_manager->can_enable_module( $key ) ) {
        $module_file = MYPCO_PLUGIN_DIR . 'inc/modules/' . $config['file'];
        if ( file_exists( $module_file ) ) {
            require_once $module_file;
            if ( class_exists( $config['class'] ) ) {
                $module_instance = new $config['class']( $loader, $api_model );
                $module_instance->register_repositories();

                $block_registrar = $module_instance->get_block_registrar();
                if ( $block_registrar ) {
                    $loader->add_block_registrar( $block_registrar );
                }

                $module_instance->init();
            }
        }
    }
}

// Shortcodes admin
$shortcodes_admin = new MyPCO_Shortcodes_Admin( $loader, $api_model );
$shortcodes_admin->init();

// Modules menu (after module menus)
$loader->add_action( 'admin_menu', $admin, 'add_modules_menu', 99 );

// Settings — React mount point
$settings = new MyPCO_Settings( MYPCO_VERSION, $api_model );
$loader->add_action( 'admin_menu',            $settings, 'add_settings_menu', 99 );
$loader->add_action( 'admin_enqueue_scripts', $settings, 'enqueue_assets' );

// License page
$license_page = new MyPCO_License_Page( $plugin_name, MYPCO_VERSION );
$license_page->init( $loader );

// Stripe webhook
$stripe = new MyPCO_Stripe_Handler();
$loader->add_action( 'rest_api_init', $stripe, 'register_webhook_endpoint' );

// Google Forms webhook
$forms_webhook = new MyPCO_Google_Forms_Webhook();
$loader->add_action( 'rest_api_init', $forms_webhook, 'register_rest_route' );

// REST API routes for React UI
$rest_controller = new MyPCO_REST_Controller( $loader );
$loader->add_action( 'rest_api_init', $rest_controller, 'register_routes' );

/*--------------------------------------------------------------------------
 * 10. PUBLIC HOOKS
 *------------------------------------------------------------------------*/

$public = new MyPCO_Public( $plugin_name, MYPCO_VERSION );
$loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_styles' );
$loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_scripts' );

/*--------------------------------------------------------------------------
 * 11. FIRE EVERYTHING
 *------------------------------------------------------------------------*/

$loader->run();
