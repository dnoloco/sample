<?php
/**
 * Settings Repository — The "Data Vault" for plugin configuration.
 *
 * This repository owns all reads and writes for local plugin settings
 * stored in wp_options. Display code and REST endpoints never call
 * get_option/update_option directly — they go through this repository.
 *
 * Encryption of sensitive values is delegated to MyPCO_Credentials_Manager
 * which serves purely as a crypto utility.
 *
 * @package MyPCO
 * @since   3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MyPCO_Settings_Repository implements MyPCO_Repository_Interface {

    const CACHE_GROUP = 'mypco_settings';

    /*----------------------------------------------------------------------
     * Option keys
     *--------------------------------------------------------------------*/

    const OPT_PCO_CLIENT_ID         = 'mypco_pco_client_id';
    const OPT_PCO_SECRET_KEY        = 'mypco_pco_secret_key';
    const OPT_CLEARSTREAM_CREDS     = 'mypco_clearstream_creds';
    const OPT_ACTIVE_MODULES        = 'mypco_active_modules';
    const OPT_GOOGLE_FORMS_SECRET   = 'mypco_google_forms_secret';
    const OPT_STRIPE_SECRET_KEY     = 'stripe_secret_key';
    const OPT_STRIPE_WEBHOOK_SECRET = 'stripe_webhook_secret';

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
            'client_id'  => MyPCO_Credentials_Manager::decrypt( get_option( self::OPT_PCO_CLIENT_ID, '' ) ),
            'secret_key' => MyPCO_Credentials_Manager::decrypt( get_option( self::OPT_PCO_SECRET_KEY, '' ) ),
        ];
    }

    /**
     * Store encrypted PCO API credentials.
     */
    public function save_pco_credentials( $client_id, $secret_key ) {
        update_option( self::OPT_PCO_CLIENT_ID, MyPCO_Credentials_Manager::encrypt( $client_id ) );
        update_option( self::OPT_PCO_SECRET_KEY, MyPCO_Credentials_Manager::encrypt( $secret_key ) );
    }

    /**
     * @return bool
     */
    public function has_pco_credentials() {
        $creds = $this->get_pco_credentials();
        return ! empty( $creds['client_id'] ) && ! empty( $creds['secret_key'] );
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
     */
    public function save_clearstream_credentials( $api_key, $message_header = '' ) {
        $data = [ 'api_key' => $api_key, 'message_header' => $message_header ];
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
     */
    public function save_active_modules( $modules ) {
        update_option( self::OPT_ACTIVE_MODULES, $modules );
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
     * @param mixed  $value Value to store.
     */
    public function save( $key, $value ) {
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
        delete_option( self::OPT_CLEARSTREAM_CREDS );
        delete_option( self::OPT_ACTIVE_MODULES );
    }

    /*----------------------------------------------------------------------
     * MyPCO_Repository_Interface
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
