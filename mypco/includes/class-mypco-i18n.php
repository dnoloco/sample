<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    MyPCO_Online
 * @subpackage MyPCO_Online/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    MyPCO_Online
 * @subpackage MyPCO_Online/includes
 */
class MyPCO_i18n {

    /**
     * The text domain of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $domain    The text domain used for translation.
     */
    protected $domain;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $domain    The text domain of this plugin.
     */
    public function __construct($domain = 'mypco-online') {
        $this->domain = $domain;
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            $this->domain,
            false,
            dirname(plugin_basename(__FILE__), 2) . '/languages/'
        );
    }

    /**
     * Get the text domain.
     *
     * @since    1.0.0
     * @return   string    The text domain.
     */
    public function get_domain() {
        return $this->domain;
    }
}
