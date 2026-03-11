<?php
/**
 * Settings — React-powered admin page.
 *
 * This class handles only the PHP side:
 *  1. Registers the submenu page under the MyPCO dashboard.
 *  2. Enqueues the compiled React JS bundle (built from src/settings/).
 *  3. Localises initial data so React can hydrate without an extra fetch.
 *  4. Renders the empty "mount point" div where React takes over.
 *
 * The actual UI lives in src/settings/SettingsApp.js — the "Skin" layer.
 *
 * @package MyPCO
 * @since   3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MyPCO_Settings {

    private $version;
    private $api_model;

    public function __construct( $version, $api_model ) {
        $this->version   = $version;
        $this->api_model = $api_model;
    }

    /**
     * Register the Settings submenu under the MyPCO dashboard.
     */
    public function add_settings_menu() {
        add_submenu_page(
            'mypco-dashboard',
            __( 'Settings', 'mypco-online' ),
            __( 'Settings', 'mypco-online' ),
            'manage_options',
            'mypco-settings',
            [ $this, 'render_settings_view' ]
        );
    }

    /**
     * Enqueue the compiled React JS bundle on the settings page only.
     */
    public function enqueue_assets( $hook ) {
        if ( 'mypco_page_mypco-settings' !== $hook ) {
            return;
        }

        wp_enqueue_script(
            'mypco-settings-app',
            MYPCO_PLUGIN_URL . 'assets/admin/js/settings.js',
            [ 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' ],
            $this->version,
            true
        );

        wp_enqueue_style( 'wp-components' );

        wp_localize_script( 'mypco-settings-app', 'mypcoSettings', $this->get_localized_data() );
    }

    /**
     * The HTML mount point — React renders into this div.
     */
    public function render_settings_view() {
        echo '<div id="mypco-settings-root"></div>';
    }

    /**
     * Build the initial data blob passed to the React app via wp_localize_script.
     */
    private function get_localized_data() {
        $pco_creds = MyPCO_Credentials_Manager::get_pco_credentials();
        $cs_creds  = MyPCO_Credentials_Manager::get_clearstream_credentials();

        $modules_data = [];
        if ( class_exists( 'MyPCO_Module_Manager' ) ) {
            $manager = new MyPCO_Module_Manager( null, $this->api_model );
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
            'pcoClientId'    => ! empty( $pco_creds['client_id'] ) ? '••••••••' . substr( $pco_creds['client_id'], -5 ) : '',
            'pcoSecretKey'   => '',
            'clearstreamKey' => ! empty( $cs_creds['api_key'] ) ? '••••••••' . substr( $cs_creds['api_key'], -5 ) : '',
            'modules'        => $modules_data,
            'nonce'          => wp_create_nonce( 'wp_rest' ),
            'restBase'       => esc_url_raw( rest_url( 'mypco/v1/' ) ),
        ];
    }
}
