<?php
/**
 * Settings — React-powered admin page.
 *
 * This class handles only the PHP side:
 *  1. Registers the submenu page under the SimplePCO dashboard.
 *  2. Enqueues the compiled React JS bundle (built from src/settings/).
 *  3. Localises initial data so React can hydrate without an extra fetch.
 *  4. Renders the empty "mount point" div where React takes over.
 *
 * The actual UI lives in src/settings/SettingsApp.js — the "Skin" layer.
 *
 * @package SimplePCO
 * @since   3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SimplePCO_Settings {

    private $version;
    private $api_model;
    private $settings_repo;

    public function __construct( $version, $api_model, SimplePCO_Settings_Repository $settings_repo ) {
        $this->version       = $version;
        $this->api_model     = $api_model;
        $this->settings_repo = $settings_repo;
    }

    /**
     * Register the Settings submenu under the SimplePCO dashboard.
     */
    public function add_settings_menu() {
        add_submenu_page(
            'simplepco-dashboard',
            __( 'Settings', 'simplepco' ),
            __( 'Settings', 'simplepco' ),
            'manage_options',
            'simplepco-settings',
            [ $this, 'render_settings_view' ]
        );
    }

    /**
     * Enqueue the compiled React JS bundle on the settings page only.
     */
    public function enqueue_assets( $hook ) {
        if ( 'simplepco_page_simplepco-settings' !== $hook ) {
            return;
        }

        wp_enqueue_script(
            'simplepco-settings-app',
            SIMPLEPCO_PLUGIN_URL . 'build/settings.js',
            [ 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' ],
            $this->version,
            true
        );

        wp_enqueue_style( 'wp-components' );

        wp_localize_script( 'simplepco-settings-app', 'simplepcoSettings', $this->get_localized_data() );
    }

    /**
     * The HTML mount point — React renders into this div.
     */
    public function render_settings_view() {
        echo '<div id="simplepco-settings-root"></div>';
    }

    /**
     * Build the initial data blob passed to the React app via wp_localize_script.
     */
    private function get_localized_data() {
        $pco_creds = $this->settings_repo->get_pco_credentials();
        $cs_creds  = $this->settings_repo->get_clearstream_credentials();

        $modules_data = [];
        if ( class_exists( 'SimplePCO_Module_Manager' ) ) {
            $manager = new SimplePCO_Module_Manager( null, $this->api_model );
            $manager->init_modules();

            foreach ( $manager->get_registered_modules() as $key => $mod ) {
                $modules_data[] = [
                    'key'         => $key,
                    'name'        => $mod['name'],
                    'description' => $mod['description'],
                    'tier'        => $mod['tier'] ?? 'premium',
                    'enabled'     => $manager->is_module_enabled( $key ),
                    'has_license' => $manager->can_enable_module( $key ),
                ];
            }
        }

        return [
            'pcoClientId'    => $this->settings_repo->get_masked_value( $pco_creds['client_id'] ?? '', 5 ),
            'pcoSecretKey'   => '',
            'clearstreamKey' => $this->settings_repo->get_masked_value( $cs_creds['api_key'] ?? '', 5 ),
            'modules'        => $modules_data,
            'nonce'          => wp_create_nonce( 'wp_rest' ),
            'restBase'       => esc_url_raw( rest_url( 'simplepco/v1/' ) ),
        ];
    }
}
