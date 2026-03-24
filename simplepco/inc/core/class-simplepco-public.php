<?php
/**
 * The public-facing functionality of the plugin.
 */

class SimplePCO_Public {

    private $plugin_name;
    private $version;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            SIMPLEPCO_PLUGIN_URL . 'assets/public/css/simplepco-public.css',
            [],
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            SIMPLEPCO_PLUGIN_URL . 'assets/public/js/simplepco-public.js',
            ['jquery'],
            $this->version,
            false
        );
    }
}
