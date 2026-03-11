<?php
/**
 * Plugin Name: MyPCO Online
 * Plugin URI: https://example.com/mypco-online
 * Description: Comprehensive Planning Center Online integration with blended architecture for churches
 * Version: 3.0.0
 * Author: David Dean
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: mypco-online
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('MYPCO_VERSION', '3.0.0');
define('MYPCO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MYPCO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MYPCO_INCLUDES', MYPCO_PLUGIN_DIR . 'inc/core/');

/**
 * License Server API URL
 * IMPORTANT: Update this to your actual license server URL after deployment
 * Example: https://your-site.com/mypco-license/api.php
 */
if (!defined('MYPCO_LICENSE_API_URL')) {
    define('MYPCO_LICENSE_API_URL', 'https://your-site.com/mypco-license/api.php');
}

/**
 * The code that runs during plugin activation.
 */
function activate_mypco_online() {
    require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-activator.php';
    MyPCO_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_mypco_online() {
    require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-deactivator.php';
    MyPCO_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_mypco_online');
register_deactivation_hook(__FILE__, 'deactivate_mypco_online');

/**
 * The core plugin class.
 */
require MYPCO_PLUGIN_DIR . 'inc/core/class-mypco.php';

/**
 * Begins execution of the plugin.
 */
function run_mypco_online() {
    $plugin = new MyPCO();
    $plugin->run();
}

run_mypco_online();
