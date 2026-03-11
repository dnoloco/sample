<?php
/**
 * REST API Controller
 *
 * Provides REST endpoints that power the React-based "Skin" layer:
 *  - Settings page (credentials, module toggles, cache clearing)
 *  - Gutenberg block live preview (events, services)
 *
 * All data access goes through Repositories, never directly to the API model.
 *
 * @package MyPCO
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MyPCO_REST_Controller {

    const NAMESPACE = 'mypco/v1';

    /**
     * @var MyPCO_Loader
     */
    protected $loader;

    /**
     * @param MyPCO_Loader $loader The loader (provides access to repositories).
     */
    public function __construct( MyPCO_Loader $loader ) {
        $this->loader = $loader;
    }

    /**
     * Register REST routes.
     *
     * @return void
     */
    public function register_routes() {
        // Events endpoint (for Gutenberg block preview)
        register_rest_route( self::NAMESPACE, '/events', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_events' ],
            'permission_callback' => [ $this, 'check_read_permission' ],
            'args'                => [
                'per_page' => [
                    'type'              => 'integer',
                    'default'           => 5,
                    'sanitize_callback' => 'absint',
                ],
                'view' => [
                    'type'              => 'string',
                    'default'           => 'list',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );

        // Settings endpoints (for React settings page)
        register_rest_route( self::NAMESPACE, '/settings/credentials', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'save_credentials' ],
            'permission_callback' => [ $this, 'check_admin_permission' ],
        ] );

        register_rest_route( self::NAMESPACE, '/settings/test-connection', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'test_connection' ],
            'permission_callback' => [ $this, 'check_admin_permission' ],
        ] );

        // Module management
        register_rest_route( self::NAMESPACE, '/modules/(?P<key>[a-z_]+)', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'toggle_module' ],
            'permission_callback' => [ $this, 'check_admin_permission' ],
        ] );

        // Cache clearing
        register_rest_route( self::NAMESPACE, '/cache/clear', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'clear_cache' ],
            'permission_callback' => [ $this, 'check_admin_permission' ],
        ] );
    }

    /**
     * GET /events — Returns upcoming events for block preview.
     */
    public function get_events( $request ) {
        $repo = $this->loader->get_repository( 'events' );
        if ( ! $repo ) {
            return new WP_Error( 'no_repository', __( 'Event repository not available. Check API credentials.', 'mypco-online' ), [ 'status' => 503 ] );
        }

        $events = $repo->find_all( [
            'per_page' => $request->get_param( 'per_page' ),
        ] );

        return rest_ensure_response( $events );
    }

    /**
     * POST /settings/credentials — Save API credentials.
     */
    public function save_credentials( $request ) {
        $params = $request->get_json_params();

        if ( ! empty( $params['pco_client_id'] ) ) {
            MyPCO_Credentials_Manager::save_pco_credentials(
                sanitize_text_field( $params['pco_client_id'] ),
                sanitize_text_field( $params['pco_secret_key'] ?? '' )
            );
        }

        if ( ! empty( $params['clearstream_api_key'] ) ) {
            MyPCO_Credentials_Manager::save_clearstream_credentials(
                sanitize_text_field( $params['clearstream_api_key'] ),
                '' // message header
            );
        }

        return rest_ensure_response( [ 'success' => true ] );
    }

    /**
     * POST /settings/test-connection — Test PCO API connection.
     */
    public function test_connection( $request ) {
        $credentials = MyPCO_Credentials_Manager::get_pco_credentials();

        if ( empty( $credentials['client_id'] ) || empty( $credentials['secret_key'] ) ) {
            return rest_ensure_response( [ 'connected' => false, 'message' => 'No credentials configured.' ] );
        }

        $timezone  = get_option( 'timezone_string' ) ?: 'America/Chicago';
        $api_model = new MyPCO_API_Model( $credentials['client_id'], $credentials['secret_key'], $timezone );
        $result    = $api_model->get_organization();

        $connected = ! empty( $result ) && ! isset( $result['error'] );

        return rest_ensure_response( [
            'connected' => $connected,
            'message'   => $connected ? 'Connected' : ( $result['error'] ?? 'Unknown error' ),
        ] );
    }

    /**
     * POST /modules/{key} — Enable or disable a module.
     */
    public function toggle_module( $request ) {
        $key     = $request->get_param( 'key' );
        $params  = $request->get_json_params();
        $enabled = ! empty( $params['enabled'] );

        $active_modules = get_option( 'mypco_active_modules', [] );
        $active_modules[ $key ] = [
            'enabled'    => $enabled,
            'updated_at' => time(),
        ];
        update_option( 'mypco_active_modules', $active_modules );

        return rest_ensure_response( [ 'success' => true, 'module' => $key, 'enabled' => $enabled ] );
    }

    /**
     * POST /cache/clear — Clear all repository caches.
     */
    public function clear_cache( $request ) {
        foreach ( $this->loader->get_repositories() as $repo ) {
            $repo->clear_cache();
        }

        // Also clear the legacy transient caches
        MyPCO_API_Model::clear_all_cache();

        return rest_ensure_response( [ 'success' => true ] );
    }

    /**
     * Permission check: any logged-in user can read (for block preview).
     */
    public function check_read_permission() {
        return is_user_logged_in();
    }

    /**
     * Permission check: admin only for settings mutations.
     */
    public function check_admin_permission() {
        return current_user_can( 'manage_options' );
    }
}
