<?php
/**
 * The public-facing functionality of the plugin.
 */

class MyPCO_Public {

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
            MYPCO_PLUGIN_URL . 'public/css/mypco-public.css',
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
            MYPCO_PLUGIN_URL . 'public/js/mypco-public.js',
            ['jquery'],
            $this->version,
            false
        );
    }
}
