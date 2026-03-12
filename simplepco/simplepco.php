<?php
/**
 * Plugin Name: SimplePCO Online
 * Plugin URI: https://example.com/simplepco-online
 * Description: Comprehensive Planning Center Online integration with blended architecture for churches
 * Version: 3.1.0
 * Author: David Dean
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: simplepco-online
 * Domain Path: /languages
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/*--------------------------------------------------------------------------
 * 1. CONSTANTS
 *------------------------------------------------------------------------*/

define( 'SIMPLEPCO_VERSION',    '3.1.0' );
define( 'SIMPLEPCO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SIMPLEPCO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SIMPLEPCO_INCLUDES',   SIMPLEPCO_PLUGIN_DIR . 'inc/core/' );

if ( ! defined( 'SIMPLEPCO_LICENSE_API_URL' ) ) {
    define( 'SIMPLEPCO_LICENSE_API_URL', 'https://your-site.com/simplepco-license/api.php' );
}

/*--------------------------------------------------------------------------
 * 2. ACTIVATION / DEACTIVATION
 *------------------------------------------------------------------------*/

/*--------------------------------------------------------------------------
 * 3. LOAD DEPENDENCIES (Composer Classmap Autoloader)
 *------------------------------------------------------------------------*/

require_once SIMPLEPCO_PLUGIN_DIR . 'vendor/autoload.php';

/*--------------------------------------------------------------------------
 * 2. ACTIVATION / DEACTIVATION
 *------------------------------------------------------------------------*/

register_activation_hook( __FILE__, [ 'SimplePCO_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'SimplePCO_Deactivator', 'deactivate' ] );

/*--------------------------------------------------------------------------
 * 4. INITIALIZE THE LOADER  (the single hook registry)
 *------------------------------------------------------------------------*/

$loader = new SimplePCO_Loader();

/*--------------------------------------------------------------------------
 * 5. LOCALE
 *------------------------------------------------------------------------*/

$i18n = new SimplePCO_i18n();
$loader->add_action( 'plugins_loaded', $i18n, 'load_plugin_textdomain' );

/*--------------------------------------------------------------------------
 * 6. SETTINGS REPOSITORY  ("Data Vault" — local wp_options data access)
 *------------------------------------------------------------------------*/

$settings_repo = new SimplePCO_Settings_Repository();
$loader->register_repository( 'settings', $settings_repo );

/*--------------------------------------------------------------------------
 * 7. API MODEL
 *------------------------------------------------------------------------*/

$api_model   = null;
$credentials = $settings_repo->get_pco_credentials();

if ( ! empty( $credentials['client_id'] ) && ! empty( $credentials['secret_key'] ) ) {
    $timezone  = get_option( 'timezone_string' ) ?: 'America/Chicago';
    $api_model = new SimplePCO_API_Model( $credentials['client_id'], $credentials['secret_key'], $timezone );
}

/*--------------------------------------------------------------------------
 * 8. API REPOSITORIES  ("Muscle" — SSP-style external data access)
 *------------------------------------------------------------------------*/

if ( $api_model ) {
    $date_helper = class_exists( 'SimplePCO_Date_Helper' ) ? new SimplePCO_Date_Helper() : null;

    $loader->register_repository( 'events',     new SimplePCO_Event_Repository( $api_model, $date_helper ) );
    $loader->register_repository( 'services',   new SimplePCO_Service_Repository( $api_model ) );
    $loader->register_repository( 'publishing', new SimplePCO_Publishing_Repository( $api_model ) );
}

/*--------------------------------------------------------------------------
 * 9. UPDATE MANAGER
 *------------------------------------------------------------------------*/

$update_manager = SimplePCO_Update_Manager::get_instance();
$update_manager->init( $loader );

/*--------------------------------------------------------------------------
 * 10. ADMIN HOOKS
 *------------------------------------------------------------------------*/

$plugin_name = 'simplepco-online';

// Dashboard
$admin = new SimplePCO_Admin( $plugin_name, SIMPLEPCO_VERSION, $loader, $api_model );
$loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
$loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );
$loader->add_action( 'admin_menu',            $admin, 'add_admin_menu' );

// Modules — load enabled modules and register their hooks through the loader
$module_manager = new SimplePCO_Module_Manager( $loader, $api_model );
$module_manager->init_modules();

$modules_ui = new SimplePCO_Modules( $loader, $api_model );
$modules_ui->init();

foreach ( $module_manager->get_modules() as $key => $config ) {
    if ( $module_manager->is_module_enabled( $key ) && $module_manager->can_enable_module( $key ) ) {
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

// Shortcodes admin
$shortcodes_admin = new SimplePCO_Shortcodes_Admin( $loader, $api_model );
$shortcodes_admin->init();

// Modules menu (after module menus)
$loader->add_action( 'admin_menu', $admin, 'add_modules_menu', 99 );

// Settings — React mount point
$settings = new SimplePCO_Settings( SIMPLEPCO_VERSION, $api_model, $settings_repo );
$loader->add_action( 'admin_menu',            $settings, 'add_settings_menu', 99 );
$loader->add_action( 'admin_enqueue_scripts', $settings, 'enqueue_assets' );

// Credentials settings — legacy admin page (hooks registered in constructor)
$credentials_settings = new SimplePCO_Credentials_Settings( $loader, $settings_repo );

// License page
$license_page = new SimplePCO_License_Page( $plugin_name, SIMPLEPCO_VERSION );
$license_page->init( $loader );

// Stripe webhook
$stripe = new SimplePCO_Stripe_Handler();
$loader->add_action( 'rest_api_init', $stripe, 'register_webhook_endpoint' );

// Google Forms webhook
$forms_webhook = new SimplePCO_Google_Forms_Webhook();
$loader->add_action( 'rest_api_init', $forms_webhook, 'register_rest_route' );

// REST API routes for React UI
$license_manager = SimplePCO_License_Manager::get_instance();
$rest_controller = new SimplePCO_REST_Controller( $settings_repo, $license_manager, $loader );
$loader->add_action( 'rest_api_init', $rest_controller, 'register_routes' );

/*--------------------------------------------------------------------------
 * 11. PUBLIC HOOKS
 *------------------------------------------------------------------------*/

$public = new SimplePCO_Public( $plugin_name, SIMPLEPCO_VERSION );
$loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_styles' );
$loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_scripts' );

/*--------------------------------------------------------------------------
 * 12. FIRE EVERYTHING
 *------------------------------------------------------------------------*/

$loader->run();
