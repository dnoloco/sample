<?php
/**
 * Credentials Manager — Encryption utility.
 *
 * This class provides symmetric encrypt/decrypt helpers using
 * WordPress AUTH_KEY and SECURE_AUTH_SALT. It does NOT read or
 * write to wp_options — that responsibility belongs to
 * SimplePCO_Settings_Repository.
 *
 * @package SimplePCO
 * @since   2.0.0  Original version with data access.
 * @since   3.1.0  Stripped to encryption-only utility.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SimplePCO_Credentials_Manager {

    private static $cipher = 'AES-256-CBC';

    /**
     * Get master encryption key from WordPress salts.
     */
    private static function get_master_key() {
        if ( defined( 'AUTH_KEY' ) && defined( 'SECURE_AUTH_SALT' ) ) {
            return AUTH_KEY . SECURE_AUTH_SALT;
        }
        error_log( 'SimplePCO: WARNING - Using fallback encryption key. Define AUTH_KEY and SECURE_AUTH_SALT in wp-config.php' );
        return 'simplepco-fallback-key-change-me-12345';
    }

    /**
     * Encrypt a value.
     *
     * @param string $value Plain-text value.
     * @return string Base-64 encoded ciphertext (IV prepended).
     */
    public static function encrypt( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        $key = self::get_master_key();
        $iv  = openssl_random_pseudo_bytes( openssl_cipher_iv_length( self::$cipher ) );
        $encrypted = openssl_encrypt( $value, self::$cipher, $key, 0, $iv );

        if ( $encrypted === false ) {
            error_log( 'SimplePCO: Encryption failed' );
            return '';
        }

        return base64_encode( $iv . $encrypted );
    }

    /**
     * Decrypt a value.
     *
     * @param string $value Base-64 encoded ciphertext.
     * @return string Plain-text value.
     */
    public static function decrypt( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        $key     = self::get_master_key();
        $decoded = base64_decode( $value );

        if ( $decoded === false ) {
            return '';
        }

        $iv_len  = openssl_cipher_iv_length( self::$cipher );
        $iv      = substr( $decoded, 0, $iv_len );
        $content = substr( $decoded, $iv_len );

        $decrypted = openssl_decrypt( $content, self::$cipher, $key, 0, $iv );

        if ( $decrypted === false ) {
            error_log( 'SimplePCO: Decryption failed' );
            return '';
        }

        return $decrypted;
    }
}
