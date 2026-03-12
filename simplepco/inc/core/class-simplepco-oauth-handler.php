<?php
/**
 * OAuth 2.0 Handler — Manages the PCO OAuth handshake and token lifecycle.
 *
 * Responsibilities:
 *  1. Build the authorization redirect URL (Step 1: Redirect).
 *  2. Handle the callback and exchange the code for tokens (Steps 3–4).
 *  3. Refresh expired access tokens using the refresh token.
 *
 * Token storage is delegated to SimplePCO_Settings_Repository.
 * This class never touches wp_options directly.
 *
 * @package SimplePCO
 * @since   3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SimplePCO_OAuth_Handler {

    /**
     * Your external server's OAuth endpoints.
     * Override via constants in wp-config.php for staging/dev.
     */
    private $authorize_url;
    private $token_url;
    private $client_id;
    private $client_secret;

    /**
     * @var SimplePCO_Settings_Repository
     */
    private $settings_repo;

    /**
     * @param SimplePCO_Settings_Repository $settings_repo Token storage.
     */
    public function __construct( SimplePCO_Settings_Repository $settings_repo ) {
        $this->settings_repo = $settings_repo;

        $this->authorize_url = defined( 'SIMPLEPCO_OAUTH_AUTHORIZE_URL' )
            ? SIMPLEPCO_OAUTH_AUTHORIZE_URL
            : 'https://api.planningcenteronline.com/oauth/authorize';

        $this->token_url = defined( 'SIMPLEPCO_OAUTH_TOKEN_URL' )
            ? SIMPLEPCO_OAUTH_TOKEN_URL
            : 'https://api.planningcenteronline.com/oauth/token';

        $this->client_id = defined( 'SIMPLEPCO_OAUTH_CLIENT_ID' )
            ? SIMPLEPCO_OAUTH_CLIENT_ID
            : '';

        $this->client_secret = defined( 'SIMPLEPCO_OAUTH_CLIENT_SECRET' )
            ? SIMPLEPCO_OAUTH_CLIENT_SECRET
            : '';
    }

    /**
     * Build the URL to redirect the user to for authorization.
     *
     * Generates a CSRF state token and stores it in a short-lived transient.
     *
     * @return string Full authorization URL with query parameters.
     */
    public function get_authorize_url() {
        $state = wp_generate_password( 32, false );
        set_transient( 'simplepco_oauth_state', $state, 600 ); // 10 minutes

        return add_query_arg( [
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->get_callback_url(),
            'response_type' => 'code',
            'scope'         => 'people services calendar groups publishing',
            'state'         => $state,
        ], $this->authorize_url );
    }

    /**
     * The WordPress admin URL that the external server redirects back to.
     *
     * @return string
     */
    public function get_callback_url() {
        return admin_url( 'admin.php?page=simplepco-oauth-callback' );
    }

    /**
     * Handle the OAuth callback: validate state, exchange code for tokens.
     *
     * Called from the admin_menu callback page. On success, stores tokens
     * and redirects to the settings page. On failure, redirects with error.
     */
    public function handle_callback() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth state param serves as CSRF token.
        $code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
        $state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
        $error = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';

        // Authorization denied by user.
        if ( ! empty( $error ) ) {
            $this->redirect_to_settings( 'oauth_denied' );
            return;
        }

        // Validate CSRF state.
        $expected_state = get_transient( 'simplepco_oauth_state' );
        delete_transient( 'simplepco_oauth_state' );

        if ( empty( $state ) || $state !== $expected_state ) {
            $this->redirect_to_settings( 'invalid_state' );
            return;
        }

        if ( empty( $code ) ) {
            $this->redirect_to_settings( 'missing_code' );
            return;
        }

        // Exchange the authorization code for tokens.
        $result = $this->exchange_code( $code );

        if ( is_wp_error( $result ) ) {
            $this->redirect_to_settings( 'token_exchange_failed' );
            return;
        }

        $this->redirect_to_settings( 'connected' );
    }

    /**
     * Exchange an authorization code for access + refresh tokens.
     *
     * @param string $code Authorization code from callback.
     * @return true|WP_Error
     */
    public function exchange_code( $code ) {
        $response = wp_remote_post( $this->token_url, [
            'timeout' => 30,
            'body'    => [
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => $this->get_callback_url(),
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
            ],
        ] );

        return $this->process_token_response( $response );
    }

    /**
     * Refresh the access token using the stored refresh token.
     *
     * @return true|WP_Error
     */
    public function refresh_token() {
        $refresh_token = $this->settings_repo->get_pco_refresh_token();

        if ( empty( $refresh_token ) ) {
            return new WP_Error( 'no_refresh_token', 'No refresh token available.' );
        }

        $response = wp_remote_post( $this->token_url, [
            'timeout' => 30,
            'body'    => [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refresh_token,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
            ],
        ] );

        return $this->process_token_response( $response );
    }

    /**
     * Get a valid access token, refreshing if necessary.
     *
     * @return string|WP_Error Access token or error.
     */
    public function get_valid_access_token() {
        if ( $this->settings_repo->has_pco_oauth_token() ) {
            return $this->settings_repo->get_pco_access_token();
        }

        // Token expired — try to refresh.
        if ( $this->settings_repo->has_pco_oauth_connection() ) {
            $result = $this->refresh_token();
            if ( is_wp_error( $result ) ) {
                return $result;
            }
            return $this->settings_repo->get_pco_access_token();
        }

        return new WP_Error( 'not_connected', 'No OAuth connection established.' );
    }

    /**
     * Disconnect: remove all stored OAuth tokens.
     */
    public function disconnect() {
        $this->settings_repo->delete_pco_oauth_tokens();
    }

    /**
     * Parse a token endpoint response and store tokens.
     *
     * @param array|WP_Error $response wp_remote_post response.
     * @return true|WP_Error
     */
    private function process_token_response( $response ) {
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 || empty( $body['access_token'] ) ) {
            $msg = $body['error_description'] ?? $body['error'] ?? 'Token exchange failed.';
            return new WP_Error( 'token_error', $msg );
        }

        $this->settings_repo->save_pco_oauth_tokens(
            $body['access_token'],
            $body['refresh_token'] ?? '',
            $body['expires_in'] ?? 7200
        );

        return true;
    }

    /**
     * Redirect to the settings page with a status message.
     *
     * @param string $status Status code for the notice.
     */
    private function redirect_to_settings( $status ) {
        wp_safe_redirect( add_query_arg(
            'simplepco_oauth', $status,
            admin_url( 'admin.php?page=simplepco-settings' )
        ) );
        exit;
    }
}
