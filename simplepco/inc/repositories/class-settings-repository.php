<?php
/**
 * Settings Repository — The "Data Vault" for plugin configuration.
 *
 * This repository owns all reads and writes for local plugin settings
 * stored in wp_options. Display code and REST endpoints never call
 * get_option/update_option directly — they go through this repository.
 *
 * Encryption of sensitive values is delegated to SimplePCO_Credentials_Manager
 * which serves purely as a crypto utility.
 *
 * @package SimplePCO
 * @since   3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SimplePCO_Settings_Repository implements SimplePCO_Repository_Interface {

    const CACHE_GROUP = 'simplepco_settings';

    /*----------------------------------------------------------------------
     * Option keys
     *--------------------------------------------------------------------*/

    const OPT_PCO_CLIENT_ID         = 'simplepco_pco_client_id';
    const OPT_PCO_SECRET_KEY        = 'simplepco_pco_secret_key';
    const OPT_CLEARSTREAM_CREDS     = 'simplepco_clearstream_creds';
    const OPT_ACTIVE_MODULES        = 'simplepco_active_modules';
    const OPT_GOOGLE_FORMS_SECRET   = 'simplepco_google_forms_secret';
    const OPT_STRIPE_SECRET_KEY     = 'stripe_secret_key';
    const OPT_STRIPE_WEBHOOK_SECRET = 'stripe_webhook_secret';

    // OAuth 2.0 token storage
    const OPT_PCO_ACCESS_TOKEN  = 'simplepco_pco_access_token';
    const OPT_PCO_REFRESH_TOKEN = 'simplepco_pco_refresh_token';
    const OPT_PCO_TOKEN_EXPIRES = 'simplepco_pco_token_expires';

    /*----------------------------------------------------------------------
     * PCO Credentials
     *--------------------------------------------------------------------*/

    /**
     * Get decrypted PCO API credentials.
     *
     * @return array { client_id: string, secret_key: string }
     */
    public function get_pco_credentials() {
        return [
            'client_id'  => SimplePCO_Credentials_Manager::decrypt( get_option( self::OPT_PCO_CLIENT_ID, '' ) ),
            'secret_key' => SimplePCO_Credentials_Manager::decrypt( get_option( self::OPT_PCO_SECRET_KEY, '' ) ),
        ];
    }

    /**
     * Store encrypted PCO API credentials.
     *
     * @param string $client_id PCO application ID.
     * @param string $secret_key PCO secret key.
     */
    public function save_pco_credentials( $client_id, $secret_key ) {
        $client_id  = sanitize_text_field( $client_id );
        $secret_key = sanitize_text_field( $secret_key );

        update_option( self::OPT_PCO_CLIENT_ID, SimplePCO_Credentials_Manager::encrypt( $client_id ) );
        update_option( self::OPT_PCO_SECRET_KEY, SimplePCO_Credentials_Manager::encrypt( $secret_key ) );
    }

    /**
     * @return bool
     */
    public function has_pco_credentials() {
        $creds = $this->get_pco_credentials();
        return ! empty( $creds['client_id'] ) && ! empty( $creds['secret_key'] );
    }

    /*----------------------------------------------------------------------
     * PCO OAuth 2.0 Tokens
     *--------------------------------------------------------------------*/

    /**
     * Store OAuth 2.0 tokens (encrypted).
     *
     * @param string $access_token  Access token from OAuth exchange.
     * @param string $refresh_token Refresh token for obtaining new access tokens.
     * @param int    $expires_in    Token lifetime in seconds.
     */
    public function save_pco_oauth_tokens( $access_token, $refresh_token, $expires_in ) {
        update_option( self::OPT_PCO_ACCESS_TOKEN, SimplePCO_Credentials_Manager::encrypt( $access_token ) );
        update_option( self::OPT_PCO_REFRESH_TOKEN, SimplePCO_Credentials_Manager::encrypt( $refresh_token ) );
        update_option( self::OPT_PCO_TOKEN_EXPIRES, time() + (int) $expires_in );
    }

    /**
     * Get decrypted OAuth access token.
     *
     * @return string
     */
    public function get_pco_access_token() {
        return SimplePCO_Credentials_Manager::decrypt( get_option( self::OPT_PCO_ACCESS_TOKEN, '' ) );
    }

    /**
     * Get decrypted OAuth refresh token.
     *
     * @return string
     */
    public function get_pco_refresh_token() {
        return SimplePCO_Credentials_Manager::decrypt( get_option( self::OPT_PCO_REFRESH_TOKEN, '' ) );
    }

    /**
     * Get token expiry timestamp.
     *
     * @return int Unix timestamp.
     */
    public function get_pco_token_expires() {
        return (int) get_option( self::OPT_PCO_TOKEN_EXPIRES, 0 );
    }

    /**
     * Check if a valid (non-expired) OAuth token exists.
     *
     * @return bool
     */
    public function has_pco_oauth_token() {
        $token = $this->get_pco_access_token();
        if ( empty( $token ) ) {
            return false;
        }
        // Consider token valid if it expires more than 60 seconds from now.
        return $this->get_pco_token_expires() > ( time() + 60 );
    }

    /**
     * Check if any OAuth tokens are stored (even if expired — refresh may work).
     *
     * @return bool
     */
    public function has_pco_oauth_connection() {
        return ! empty( $this->get_pco_refresh_token() );
    }

    /**
     * Remove all stored OAuth tokens (disconnect).
     */
    public function delete_pco_oauth_tokens() {
        delete_option( self::OPT_PCO_ACCESS_TOKEN );
        delete_option( self::OPT_PCO_REFRESH_TOKEN );
        delete_option( self::OPT_PCO_TOKEN_EXPIRES );
    }

    /*----------------------------------------------------------------------
     * Clearstream Credentials
     *--------------------------------------------------------------------*/

    /**
     * Get Clearstream credentials.
     *
     * @return array { api_key: string, message_header: string }
     */
    public function get_clearstream_credentials() {
        $encrypted = get_option( self::OPT_CLEARSTREAM_CREDS, '' );
        if ( empty( $encrypted ) ) {
            return [ 'api_key' => '', 'message_header' => '' ];
        }

        $json = base64_decode( $encrypted );
        $data = json_decode( $json, true );

        return is_array( $data ) ? $data : [ 'api_key' => '', 'message_header' => '' ];
    }

    /**
     * Store Clearstream credentials.
     *
     * @param string $api_key        Clearstream API key.
     * @param string $message_header Optional message header.
     */
    public function save_clearstream_credentials( $api_key, $message_header = '' ) {
        $data = [
            'api_key'        => sanitize_text_field( $api_key ),
            'message_header' => sanitize_text_field( $message_header ),
        ];
        update_option( self::OPT_CLEARSTREAM_CREDS, base64_encode( json_encode( $data ) ) );
    }

    /**
     * @return bool
     */
    public function has_clearstream_credentials() {
        $creds = $this->get_clearstream_credentials();
        return ! empty( $creds['api_key'] );
    }

    /*----------------------------------------------------------------------
     * Module settings
     *--------------------------------------------------------------------*/

    /**
     * Get active modules map.
     *
     * @return array
     */
    public function get_active_modules() {
        return get_option( self::OPT_ACTIVE_MODULES, [] );
    }

    /**
     * Save active modules map.
     *
     * @param array $modules Associative array of module_key => { enabled, updated_at }.
     */
    public function save_active_modules( $modules ) {
        $clean = [];
        foreach ( (array) $modules as $key => $data ) {
            $clean[ sanitize_key( $key ) ] = [
                'enabled'    => ! empty( $data['enabled'] ),
                'updated_at' => absint( $data['updated_at'] ?? time() ),
            ];
        }
        update_option( self::OPT_ACTIVE_MODULES, $clean );
    }

    /*----------------------------------------------------------------------
     * Generic settings helpers
     *--------------------------------------------------------------------*/

    /**
     * Get a single setting by key.
     *
     * @param string $key     Option name.
     * @param mixed  $default Default value.
     * @return mixed
     */
    public function get( $key, $default = '' ) {
        return get_option( $key, $default );
    }

    /**
     * Save a single setting.
     *
     * @param string $key   Option name.
     * @param mixed  $value Value to store (strings are sanitized).
     */
    public function save( $key, $value ) {
        $key = sanitize_key( $key );
        if ( is_string( $value ) ) {
            $value = sanitize_text_field( $value );
        }
        update_option( $key, $value );
    }

    /*----------------------------------------------------------------------
     * Display helpers
     *--------------------------------------------------------------------*/

    /**
     * Get masked display value for sensitive data.
     *
     * @param string $value         Raw value.
     * @param int    $visible_chars Chars to keep visible at end.
     * @return string
     */
    public function get_masked_value( $value, $visible_chars = 4 ) {
        if ( empty( $value ) ) {
            return '';
        }
        if ( strlen( $value ) <= $visible_chars ) {
            return str_repeat( '•', strlen( $value ) );
        }
        return str_repeat( '•', 12 ) . substr( $value, -$visible_chars );
    }

    /*----------------------------------------------------------------------
     * Delete all stored credentials (uninstall).
     *--------------------------------------------------------------------*/

    public function delete_all() {
        delete_option( self::OPT_PCO_CLIENT_ID );
        delete_option( self::OPT_PCO_SECRET_KEY );
        delete_option( self::OPT_PCO_ACCESS_TOKEN );
        delete_option( self::OPT_PCO_REFRESH_TOKEN );
        delete_option( self::OPT_PCO_TOKEN_EXPIRES );
        delete_option( self::OPT_CLEARSTREAM_CREDS );
        delete_option( self::OPT_ACTIVE_MODULES );
    }

    /*----------------------------------------------------------------------
     * SimplePCO_Repository_Interface
     *--------------------------------------------------------------------*/

    /**
     * Find a single setting group by key.
     *
     * @param string $id Setting group: 'pco', 'clearstream', or 'modules'.
     * @return array|null
     */
    public function find( $id ) {
        switch ( $id ) {
            case 'pco':
                return $this->get_pco_credentials();
            case 'clearstream':
                return $this->get_clearstream_credentials();
            case 'modules':
                return $this->get_active_modules();
            default:
                return null;
        }
    }

    /**
     * Get all settings as a keyed array.
     *
     * @param array $args Unused.
     * @return array
     */
    public function find_all( $args = [] ) {
        return [
            'pco'         => $this->get_pco_credentials(),
            'clearstream' => $this->get_clearstream_credentials(),
            'modules'     => $this->get_active_modules(),
        ];
    }

    /**
     * No caching layer for settings — reads go directly to wp_options.
     */
    public function clear_cache() {
        // Settings are read from wp_options; no transient layer to clear.
    }
}
