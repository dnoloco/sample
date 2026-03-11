<?php
if ( ! class_exists( 'MyPCO_Settings_Page' ) ) {
    class MyPCO_Settings_Page {
        private $api_model;

        public function __construct($plugin_name, $version, $api_model) {
            $this->api_model = $api_model;
            add_action('wp_ajax_mypco_test_pco_connection', [$this, 'ajax_test_pco_connection']);
            add_action('wp_ajax_mypco_test_clearstream_connection', [$this, 'ajax_test_clearstream_connection']);
        }

        public function add_settings_menu() {
            add_submenu_page('mypco-dashboard', 'Settings', 'Settings', 'manage_options', 'mypco-settings', [$this, 'render_settings_page']);
        }

        private function mask_credential($string) {
            if (empty($string)) return '';
            return str_repeat('•', 12) . substr($string, -5);
        }

        public function handle_settings_save() {
            if (!isset($_POST['mypco_settings_nonce']) || !wp_verify_nonce($_POST['mypco_settings_nonce'], 'mypco_save_settings')) return;

            // PCO Saving
            if (!empty($_POST['mypco_client_id']) && strpos($_POST['mypco_client_id'], '•') === false) {
                MyPCO_Credentials_Manager::update_pco_credentials(
                    sanitize_text_field($_POST['mypco_client_id']),
                    sanitize_text_field($_POST['mypco_client_secret'])
                );
            }

            // Clearstream Saving - Using 'X-API-Key' alignment
            if (isset($_POST['clearstream_api_key'])) {
                MyPCO_Credentials_Manager::set_clearstream_credentials(
                    sanitize_text_field($_POST['clearstream_api_key']),
                    sanitize_text_field($_POST['clearstream_header'])
                );
            }

            wp_redirect(add_query_arg('settings-updated', 'true', wp_get_referer()));
            exit;
        }

        public function render_settings_page() {
            $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'pco_api';
            ?>
            <div class="wrap">
                <h1>MyPCO Settings</h1>
                <h2 class="nav-tab-wrapper">
                    <a href="?page=mypco-settings&tab=pco_api" class="nav-tab <?php echo $active_tab == 'pco_api' ? 'nav-tab-active' : ''; ?>">Planning Center API</a>
                    <a href="?page=mypco-settings&tab=clearstream_api" class="nav-tab <?php echo $active_tab == 'clearstream_api' ? 'nav-tab-active' : ''; ?>">Clearstream API</a>
                </h2>

                <div class="mypco-settings-content" style="background: #fff; border: 1px solid #ccd0d4; border-top: none; padding: 20px;">
                    <form method="post" action="">
                        <?php wp_nonce_field('mypco_save_settings', 'mypco_settings_nonce'); ?>

                        <?php if ($active_tab == 'pco_api') :
                            $creds = MyPCO_Credentials_Manager::get_pco_credentials(); ?>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">PCO Client ID</th>
                                    <td>
                                        <input type="text" name="mypco_client_id"
                                               value="<?php echo esc_attr($this->mask_credential($creds['client_id'] ?? '')); ?>"
                                               onfocus="if(this.value.includes('•')) this.value='';"
                                               class="regular-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">PCO Client Secret</th>
                                    <td><input type="password" name="mypco_client_secret" value="" placeholder="••••••••••••" class="regular-text"></td>
                                </tr>
                            </table>
                        <?php else :
                            $cs = MyPCO_Credentials_Manager::get_clearstream_credentials(); ?>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Clearstream API Key</th>
                                    <td>
                                        <input type="password" name="clearstream_api_key"
                                               value="<?php echo esc_attr($cs['api_key'] ?? ''); ?>"
                                               class="regular-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Church Message Header</th>
                                    <td>
                                        <input type="text" name="clearstream_header"
                                               value="<?php echo esc_attr($cs['message_header'] ?? ''); ?>"
                                               class="regular-text">
                                    </td>
                                </tr>
                            </table>
                        <?php endif; ?>

                        <div style="margin-top: 20px;">
                            <?php submit_button('Save Settings', 'primary', 'submit', false); ?>
                            <button type="button" id="mypco-test-connection" class="button button-secondary" style="margin-left: 10px;">Test Connection</button>
                            <span id="test-results" style="margin-left: 15px; font-weight: bold;"></span>
                        </div>
                    </form>
                </div>
            </div>
            <script>
                jQuery(document).ready(function($) {
                    $('#mypco-test-connection').on('click', function() {
                        var btn = $(this);
                        btn.prop('disabled', true).text('Testing...');
                        $.post(ajaxurl, {
                            action: '<?php echo ($active_tab == "pco_api") ? "mypco_test_pco_connection" : "mypco_test_clearstream_connection"; ?>',
                            nonce: '<?php echo wp_create_nonce("mypco_test_nonce"); ?>'
                        }, function(res) {
                            btn.prop('disabled', false).text('Test Connection');
                            $('#test-results').html(res.success ? '<span style="color:green">✅ '+res.data.message+'</span>' : '<span style="color:red">❌ '+res.data.message+'</span>');
                        });
                    });
                });
            </script>
            <?php
        }

        public function ajax_test_pco_connection() {
            check_ajax_referer('mypco_test_nonce', 'nonce');
            $res = $this->api_model->get_organization();
            if ($res && isset($res['data']['attributes']['name'])) wp_send_json_success(['message' => 'Connected: ' . $res['data']['attributes']['name']]);
            wp_send_json_error(['message' => 'PCO Auth Failed.']);
        }

        public function ajax_test_clearstream_connection() {
            check_ajax_referer('mypco_test_nonce', 'nonce');

            $creds = MyPCO_Credentials_Manager::get_clearstream_credentials();
            $key = isset($creds['api_key']) ? $creds['api_key'] : '';

            if (empty($key)) {
                wp_send_json_error(['message' => 'No API Key saved. Please save settings first.']);
            }

            $response = wp_remote_get('https://api.getclearstream.com/v1/account', [
                'headers' => [
                    'X-Api-Key' => trim($key), // Ensure no accidental spaces
                    'Accept'    => 'application/json',
                ],
                'timeout' => 15
            ]);

            $code = wp_remote_retrieve_response_code($response);

            if ($code === 200) {
                wp_send_json_success(['message' => 'Clearstream Connected!']);
            } elseif ($code === 401) {
                wp_send_json_error(['message' => 'Invalid API Key. (401 Unauthorized)']);
            } else {
                wp_send_json_error(['message' => 'Connection Error. Code: ' . $code]);
            }
        }
    }
}