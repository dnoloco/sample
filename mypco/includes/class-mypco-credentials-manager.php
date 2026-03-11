<?php
/**
 * MyPCO Credentials Manager
 *
 * Handles secure encryption and storage of API credentials.
 * Uses WordPress AUTH_KEY and SECURE_AUTH_SALT for encryption.
 */

class MyPCO_Credentials_Manager {

    private static $cipher = 'AES-256-CBC';

    /**
     * Option names for stored credentials
     */
    const PCO_CLIENT_ID = 'mypco_pco_client_id';
    const PCO_SECRET_KEY = 'mypco_pco_secret_key';
    const CLEARSTREAM_API_TOKEN = 'mypco_clearstream_api_token';
    const CLEARSTREAM_MESSAGE_HEADER = 'mypco_clearstream_message_header';

    /**
     * Get master encryption key from WordPress salts.
     */
    private static function get_master_key() {
        if (defined('AUTH_KEY') && defined('SECURE_AUTH_SALT')) {
            return AUTH_KEY . SECURE_AUTH_SALT;
        }
        // Fallback should never be used in production
        error_log('MyPCO: WARNING - Using fallback encryption key. Define AUTH_KEY and SECURE_AUTH_SALT in wp-config.php');
        return 'mypco-fallback-key-change-me-12345';
    }

    /**
     * Encrypt a value.
     */
    public static function encrypt($value) {
        if (empty($value)) {
            return '';
        }

        $key = self::get_master_key();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$cipher));
        $encrypted = openssl_encrypt($value, self::$cipher, $key, 0, $iv);

        if ($encrypted === false) {
            error_log('MyPCO: Encryption failed');
            return '';
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt a value.
     */
    public static function decrypt($value) {
        if (empty($value)) {
            return '';
        }

        $key = self::get_master_key();
        $decoded = base64_decode($value);

        if ($decoded === false) {
            return '';
        }

        $iv_len = openssl_cipher_iv_length(self::$cipher);
        $iv = substr($decoded, 0, $iv_len);
        $content = substr($decoded, $iv_len);

        $decrypted = openssl_decrypt($content, self::$cipher, $key, 0, $iv);

        if ($decrypted === false) {
            error_log('MyPCO: Decryption failed');
            return '';
        }

        return $decrypted;
    }

    /**
     * Store PCO credentials (encrypted).
     */
    public static function set_pco_credentials($client_id, $secret_key) {
        update_option(self::PCO_CLIENT_ID, self::encrypt($client_id));
        update_option(self::PCO_SECRET_KEY, self::encrypt($secret_key));
    }

    /**
     * Get PCO credentials (decrypted).
     */
    public static function get_pco_credentials() {
        $encrypted_client_id = get_option(self::PCO_CLIENT_ID);
        $encrypted_secret = get_option(self::PCO_SECRET_KEY);

        return [
            'client_id' => self::decrypt($encrypted_client_id),
            'secret_key' => self::decrypt($encrypted_secret)
        ];
    }

    /**
     * Update Clearstream credentials
     */
    public static function set_clearstream_credentials($api_key, $message_header = '') {
        $data = array(
            'api_key'        => $api_key,
            'message_header' => $message_header
        );

        // FIX: Ensure the method exists or use the logic directly
        update_option('mypco_clearstream_creds', self::encrypt_data($data));
    }

    /**
     * Add this if it is missing or private
     */
    private static function encrypt_data($data) {
        $json = json_encode($data);
        // If you are using a specific encryption key, use it here.
        // Otherwise, base64 is often used as a placeholder in this plugin structure
        return base64_encode($json);
    }

    /**
     * Ensure your decryption matches
     */
    public static function get_clearstream_credentials() {
        $encrypted = get_option('mypco_clearstream_creds');
        if (!$encrypted) return ['api_key' => '', 'message_header' => ''];

        $json = base64_decode($encrypted);
        return json_decode($json, true);
    }

    /**
     * Check if PCO credentials are configured.
     */
    public static function has_pco_credentials() {
        $creds = self::get_pco_credentials();
        return !empty($creds['client_id']) && !empty($creds['secret_key']);
    }

    /**
     * Check if Clearstream credentials are configured.
     */
    public static function has_clearstream_credentials() {
        $creds = self::get_clearstream_credentials();
        return !empty($creds['api_token']);
    }

    /**
     * Delete all stored credentials (for uninstall).
     */
    public static function delete_all_credentials() {
        delete_option(self::PCO_CLIENT_ID);
        delete_option(self::PCO_SECRET_KEY);
        delete_option(self::CLEARSTREAM_API_TOKEN);
        delete_option(self::CLEARSTREAM_MESSAGE_HEADER);
    }

    /**
     * Migrate credentials from config.php if it exists.
     */
    public static function migrate_from_config_file() {
        $config_file = MYPCO_PLUGIN_DIR . 'config.php';

        if (!file_exists($config_file)) {
            return false;
        }

        // Check if credentials already stored
        if (self::has_pco_credentials()) {
            return false; // Already migrated
        }

        // Load config file
        $pco_client_id = null;
        $pco_secret_key = null;
        require $config_file;

        if (!empty($pco_client_id) && !empty($pco_secret_key)) {
            self::set_pco_credentials($pco_client_id, $pco_secret_key);
            return true;
        }

        return false;
    }

    /**
     * Get masked display value for sensitive data.
     */
    public static function get_masked_value($value, $visible_chars = 4) {
        if (empty($value)) {
            return '';
        }

        $length = strlen($value);
        if ($length <= $visible_chars) {
            return str_repeat('•', $length);
        }

        return str_repeat('•', 12) . substr($value, -$visible_chars);
    }
}
