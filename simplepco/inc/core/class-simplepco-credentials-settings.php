<?php
/**
 * SimplePCO Credentials Settings Page
 *
 * Provides admin interface for managing API credentials securely.
 */

class SimplePCO_Credentials_Settings {

    /**
     * @var SimplePCO_Settings_Repository
     */
    private $settings_repo;

    public function __construct( $loader, SimplePCO_Settings_Repository $settings_repo ) {
        $this->settings_repo = $settings_repo;

        $loader->add_action('admin_menu', $this, 'add_credentials_page', 15);
        $loader->add_action('admin_init', $this, 'handle_credentials_save');

        // AJAX handlers for connection testing
        $loader->add_action('wp_ajax_simplepco_test_pco_connection', $this, 'test_pco_connection');
        $loader->add_action('wp_ajax_simplepco_test_clearstream_connection', $this, 'test_clearstream_connection');
    }

    /**
     * Add credentials settings page to admin menu.
     */
    public function add_credentials_page() {
        add_submenu_page(
                'simplepco-settings',
                __('API Credentials', 'simplepco-online'),
                __('API Credentials', 'simplepco-online'),
                'manage_options',
                'simplepco-credentials',
                [$this, 'render_credentials_page']
        );
    }

    /**
     * Handle credentials form submission.
     */
    public function handle_credentials_save() {
        if (!isset($_POST['simplepco_save_credentials'])) {
            return;
        }

        check_admin_referer('simplepco_credentials_save');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'simplepco-online'));
        }

        // Get submitted values
        $pco_client_id = isset($_POST['pco_client_id']) ? $_POST['pco_client_id'] : '';
        $pco_secret_key = isset($_POST['pco_secret_key']) ? $_POST['pco_secret_key'] : '';
        $clearstream_token = isset($_POST['clearstream_api_token']) ? $_POST['clearstream_api_token'] : '';
        $clearstream_header = isset($_POST['clearstream_message_header']) ? $_POST['clearstream_message_header'] : '';

        // Handle masked values (don't save if still masked)
        if (strpos($pco_client_id, '••••') !== false) {
            $pco_client_id = null; // Keep existing
        }
        if (strpos($pco_secret_key, '••••') !== false) {
            $pco_secret_key = null; // Keep existing
        }
        if (strpos($clearstream_token, '••••') !== false) {
            $clearstream_token = null; // Keep existing
        }

        // Save PCO credentials if provided
        if ($pco_client_id !== null && $pco_secret_key !== null) {
            $this->settings_repo->save_pco_credentials(
                    sanitize_text_field($pco_client_id),
                    sanitize_text_field($pco_secret_key)
            );
        }

        // Save Clearstream credentials if provided
        if ($clearstream_token !== null) {
            $this->settings_repo->save_clearstream_credentials(
                    sanitize_text_field($clearstream_token),
                    sanitize_text_field($clearstream_header)
            );
        }

        // Redirect with success message
        wp_redirect(add_query_arg([
                'page' => 'simplepco-credentials',
                'updated' => '1'
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Test PCO API connection.
     */
    public function test_pco_connection() {
        check_ajax_referer('simplepco_test_connection', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $creds = $this->settings_repo->get_pco_credentials();

        if (empty($creds['client_id']) || empty($creds['secret_key'])) {
            wp_send_json_error('No PCO credentials found. Please save credentials first.');
        }

        // Test connection to PCO API
        $credentials = base64_encode($creds['client_id'] . ':' . $creds['secret_key']);
        $response = wp_remote_get('https://api.planningcenteronline.com/people/v2/me', [
                'headers' => [
                        'Authorization' => 'Basic ' . $credentials
                ],
                'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code === 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $name = $body['data']['attributes']['name'] ?? 'Unknown';
            wp_send_json_success('✓ Connected successfully! Authenticated as: ' . esc_html($name));
        } elseif ($code === 401) {
            wp_send_json_error('Authentication failed. Please check your Client ID and Secret.');
        } else {
            wp_send_json_error('API returned error code: ' . $code);
        }
    }

    /**
     * Test Clearstream API connection.
     */
    public function test_clearstream_connection() {
        check_ajax_referer('simplepco_test_connection', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $creds = $this->settings_repo->get_clearstream_credentials();

        if (empty($creds['api_key'])) {
            wp_send_json_error('No Clearstream token found. Please save credentials first.');
        }

        // Test connection to Clearstream API
        // Official URL: https://api.getclearstream.com/v1/
        $response = wp_remote_get('https://api.getclearstream.com/v1/account', [
                'headers' => [
                        'X-API-Key' => $creds['api_key'],
                        'Accept' => 'application/json'
                ],
                'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code === 200) {
            $data = json_decode($body, true);
            $account_name = isset($data['data']['name']) ? $data['data']['name'] : 'Account verified';
            wp_send_json_success('✓ Connected successfully! ' . esc_html($account_name));
        } elseif ($code === 401 || $code === 403) {
            wp_send_json_error('Authentication failed. Please check your API token.');
        } else {
            wp_send_json_error('API error (Code ' . $code . '): ' . substr($body, 0, 200));
        }
    }

    /**
     * Render the credentials settings page.
     */
    public function render_credentials_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'simplepco-online'));
        }

        // Get current credentials (decrypted for display)
        $pco_creds = $this->settings_repo->get_pco_credentials();
        $clearstream_creds = $this->settings_repo->get_clearstream_credentials();

        // Create masked display values
        $pco_client_display = $this->settings_repo->get_masked_value($pco_creds['client_id']);
        $pco_secret_display = $this->settings_repo->get_masked_value($pco_creds['secret_key']);
        $clearstream_token_display = $this->settings_repo->get_masked_value($clearstream_creds['api_key'] ?? '');

        // Check for migration from config.php
        $config_file = SIMPLEPCO_PLUGIN_DIR . 'config.php';
        $config_exists = file_exists($config_file);
        $can_migrate = $config_exists && !$this->settings_repo->has_pco_credentials();

        ?>
        <div class="wrap">
            <h1><?php _e('API Credentials', 'simplepco-online'); ?></h1>

            <?php if (isset($_GET['updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php _e('Credentials saved successfully!', 'simplepco-online'); ?></strong></p>
                </div>
            <?php endif; ?>

            <?php if ($can_migrate): ?>
                <div class="notice notice-info">
                    <p><strong><?php _e('Migration Available:', 'simplepco-online'); ?></strong>
                        <?php _e('We detected credentials in your config.php file. Enter your credentials below and save to securely encrypt them. After migration, you can safely delete config.php.', 'simplepco-online'); ?></p>
                </div>
            <?php endif; ?>

            <p><?php _e('Enter your API credentials below. All credentials are encrypted and stored securely.', 'simplepco-online'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('simplepco_credentials_save'); ?>

                <!-- Planning Center Online Credentials -->
                <div class="card" style="max-width: 800px; margin-bottom: 20px;">
                    <h2><?php _e('Planning Center Online', 'simplepco-online'); ?></h2>
                    <p><?php _e('Required for Calendar, Groups, Services, and other PCO integrations.', 'simplepco-online'); ?></p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="pco_client_id"><?php _e('Application ID (Client ID)', 'simplepco-online'); ?></label>
                            </th>
                            <td>
                                <input type="text"
                                       id="pco_client_id"
                                       name="pco_client_id"
                                       value="<?php echo esc_attr($pco_client_display); ?>"
                                       class="regular-text"
                                       autocomplete="off">
                                <p class="description">
                                    <?php _e('Your PCO Application ID. Get this from', 'simplepco-online'); ?>
                                    <a href="https://api.planningcenteronline.com/oauth/applications" target="_blank">
                                        <?php _e('PCO Developer Console', 'simplepco-online'); ?> ↗
                                    </a>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="pco_secret_key"><?php _e('Secret', 'simplepco-online'); ?></label>
                            </th>
                            <td>
                                <input type="password"
                                       id="pco_secret_key"
                                       name="pco_secret_key"
                                       value="<?php echo esc_attr($pco_secret_display); ?>"
                                       class="regular-text"
                                       autocomplete="off">
                                <p class="description">
                                    <?php _e('Your PCO Application Secret.', 'simplepco-online'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <div style="padding: 0 10px 10px;">
                        <button type="button" id="test-pco-btn" class="button button-secondary">
                            <?php _e('Test PCO Connection', 'simplepco-online'); ?>
                        </button>
                        <span id="pco-test-result" style="margin-left: 10px; font-weight: bold;"></span>
                    </div>
                </div>

                <!-- Clearstream Credentials (Premium) -->
                <div class="card" style="max-width: 800px; margin-bottom: 20px;">
                    <h2><?php _e('Clearstream (Premium)', 'simplepco-online'); ?></h2>
                    <p><?php _e('Required for SMS messaging features. Requires Messages module license.', 'simplepco-online'); ?></p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="clearstream_api_token"><?php _e('API Token', 'simplepco-online'); ?></label>
                            </th>
                            <td>
                                <input type="password"
                                       id="clearstream_api_token"
                                       name="clearstream_api_token"
                                       value="<?php echo esc_attr($clearstream_token_display); ?>"
                                       class="regular-text"
                                       autocomplete="off">
                                <p class="description">
                                    <?php _e('Your Clearstream API token. Get this from your', 'simplepco-online'); ?>
                                    <a href="https://www.getclearstream.com/" target="_blank">
                                        <?php _e('Clearstream account', 'simplepco-online'); ?> ↗
                                    </a>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="clearstream_message_header"><?php _e('Message Header (Optional)', 'simplepco-online'); ?></label>
                            </th>
                            <td>
                                <input type="text"
                                       id="clearstream_message_header"
                                       name="clearstream_message_header"
                                       value="<?php echo esc_attr($clearstream_creds['message_header']); ?>"
                                       class="regular-text"
                                       placeholder="e.g., Church Name:">
                                <p class="description">
                                    <?php _e('Optional prefix added to all outgoing messages.', 'simplepco-online'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <div style="padding: 0 10px 10px;">
                        <button type="button" id="test-clearstream-btn" class="button button-secondary">
                            <?php _e('Test Clearstream Connection', 'simplepco-online'); ?>
                        </button>
                        <span id="clearstream-test-result" style="margin-left: 10px; font-weight: bold;"></span>
                    </div>
                </div>

                <?php submit_button(__('Save Credentials', 'simplepco-online')); ?>
                <input type="hidden" name="simplepco_save_credentials" value="1">
            </form>

            <!-- Security Information -->
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h3><?php _e('Security Information', 'simplepco-online'); ?></h3>
                <ul>
                    <li><?php _e('All credentials are encrypted using AES-256-CBC encryption', 'simplepco-online'); ?></li>
                    <li><?php _e('Encryption keys are derived from your WordPress AUTH_KEY and SECURE_AUTH_SALT', 'simplepco-online'); ?></li>
                    <li><?php _e('Credentials are never transmitted in plain text', 'simplepco-online'); ?></li>
                    <li><?php _e('Only administrators can access this page', 'simplepco-online'); ?></li>
                </ul>

                <?php if ($config_exists): ?>
                    <hr>
                    <p><strong><?php _e('Important:', 'simplepco-online'); ?></strong>
                        <?php _e('After successfully saving your credentials, you should delete the config.php file for security.', 'simplepco-online'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var testNonce = '<?php echo wp_create_nonce("simplepco_test_connection"); ?>';

                // Test PCO Connection
                $('#test-pco-btn').on('click', function(e) {
                    e.preventDefault();
                    var $btn = $(this);
                    var $result = $('#pco-test-result');

                    $btn.prop('disabled', true).text('<?php esc_js(_e('Testing...', 'simplepco-online')); ?>');
                    $result.text('').css('color', 'black');

                    $.post(ajaxurl, {
                        action: 'simplepco_test_pco_connection',
                        nonce: testNonce
                    }, function(response) {
                        $btn.prop('disabled', false).text('<?php esc_js(_e('Test PCO Connection', 'simplepco-online')); ?>');
                        if (response.success) {
                            $result.html(response.data).css('color', 'green');
                        } else {
                            $result.html('✗ ' + response.data).css('color', 'red');
                        }
                    });
                });

                // Test Clearstream Connection
                $('#test-clearstream-btn').on('click', function(e) {
                    e.preventDefault();
                    var $btn = $(this);
                    var $result = $('#clearstream-test-result');

                    $btn.prop('disabled', true).text('<?php esc_js(_e('Testing...', 'simplepco-online')); ?>');
                    $result.text('').css('color', 'black');

                    $.post(ajaxurl, {
                        action: 'simplepco_test_clearstream_connection',
                        nonce: testNonce
                    }, function(response) {
                        $btn.prop('disabled', false).text('<?php esc_js(_e('Test Clearstream Connection', 'simplepco-online')); ?>');
                        if (response.success) {
                            $result.html(response.data).css('color', 'green');
                        } else {
                            $result.html('✗ ' + response.data).css('color', 'red');
                        }
                    });
                });
            });
        </script>
        <?php
    }
}