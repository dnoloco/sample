<?php
/**
 * Contacts Module (Premium)
 *
 * Handles Clearstream SMS integration for sending messages to team members.
 */

require_once SIMPLEPCO_PLUGIN_DIR . 'inc/core/class-simplepco-module-base.php';

class SimplePCO_Contacts_Module extends SimplePCO_Module_Base {

    protected $module_key = 'contacts';
    protected $module_name = 'Contacts';
    protected $module_description = 'Send mass SMS via Clearstream integration.';

    /**
     * Module tier: premium (requires Professional or higher license)
     */
    protected $tier = 'premium';
    protected $requires_license = true;
    protected $min_license_tier = 'professional';

    /**
     * Module dependencies
     */
    protected $dependencies = ['services'];

    /**
     * Features available in this module
     */
    protected $features = [
        'free' => [],
        'premium' => [
            'send_sms',
            'message_templates',
            'scheduled_messages',
            'message_history'
        ]
    ];

    /**
     * Initialize the Contacts module.
     */
    public function init() {
        // Register admin menu page for Contacts info/documentation
        $this->loader->add_action('admin_menu', $this, 'add_info_page');
    }

    /**
     * Add info page for Contacts module.
     */
    public function add_info_page() {
        add_submenu_page(
                'simplepco-settings',
                __('Contacts', 'simplepco-online'),
                __('Contacts', 'simplepco-online'),
                'manage_options',
                'simplepco-contacts',
                [$this, 'render_info_page']
        );
    }

    /**
     * Render Contacts info page.
     */
    public function render_info_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'simplepco-online'));
        }

        // Check if Clearstream is configured
        $creds = $this->repository('settings')->get_clearstream_credentials();
        $is_configured = !empty($creds['api_key']);
        $credentials_url = admin_url('admin.php?page=simplepco-credentials');

        ?>
        <div class="wrap">
            <h1><?php _e('Contacts Module', 'simplepco-online'); ?></h1>

            <?php if (!$is_configured): ?>
                <div class="notice notice-warning">
                    <p><strong><?php _e('Clearstream Not Configured', 'simplepco-online'); ?></strong></p>
                    <p>
                        <?php _e('To use SMS messaging features, you need to configure your Clearstream API credentials.', 'simplepco-online'); ?>
                        <a href="<?php echo esc_url($credentials_url); ?>" class="button button-primary" style="margin-left: 10px;">
                            <?php _e('Configure Credentials', 'simplepco-online'); ?> →
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <div class="notice notice-success">
                    <p>
                        <strong><?php _e('✓ Clearstream Configured', 'simplepco-online'); ?></strong> -
                        <?php _e('You can now send SMS messages to team members.', 'simplepco-online'); ?>
                        <a href="<?php echo esc_url($credentials_url); ?>">
                            <?php _e('Manage credentials', 'simplepco-online'); ?> →
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <div class="card">
                <h2><?php _e('About the Contacts Module', 'simplepco-online'); ?></h2>
                <p><?php _e('The Contacts module integrates with Clearstream to send SMS messages to team members scheduled in Planning Center Services.', 'simplepco-online'); ?></p>

                <h3><?php _e('Features', 'simplepco-online'); ?></h3>
                <ul>
                    <li><?php _e('Send bulk SMS to selected team members', 'simplepco-online'); ?></li>
                    <li><?php _e('Automatic phone number lookup from PCO People', 'simplepco-online'); ?></li>
                    <li><?php _e('Character counter with credit calculation', 'simplepco-online'); ?></li>
                    <li><?php _e('Message logging and history', 'simplepco-online'); ?></li>
                    <li><?php _e('Permission management', 'simplepco-online'); ?></li>
                </ul>

                <h3><?php _e('Setup Instructions', 'simplepco-online'); ?></h3>
                <ol>
                    <li>
                        <?php _e('Get your Clearstream API token from your', 'simplepco-online'); ?>
                        <a href="https://www.getclearstream.com/" target="_blank">
                            <?php _e('Clearstream account', 'simplepco-online'); ?> ↗
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url($credentials_url); ?>">
                            <?php _e('Go to API Credentials', 'simplepco-online'); ?> →
                        </a>
                        <?php _e('and enter your Clearstream credentials', 'simplepco-online'); ?>
                    </li>
                    <li><?php _e('Navigate to a service plan in the Services module', 'simplepco-online'); ?></li>
                    <li><?php _e('Select team members and click "Send Message"', 'simplepco-online'); ?></li>
                </ol>

                <h3><?php _e('Requirements', 'simplepco-online'); ?></h3>
                <ul>
                    <li>
                        <strong><?php _e('Active Clearstream Account', 'simplepco-online'); ?></strong> -
                        <a href="https://www.getclearstream.com/" target="_blank">
                            <?php _e('Sign up here', 'simplepco-online'); ?> ↗
                        </a>
                    </li>
                    <li><strong><?php _e('Services Module', 'simplepco-online'); ?></strong> - <?php _e('Must be enabled', 'simplepco-online'); ?></li>
                    <li><strong><?php _e('Valid License', 'simplepco-online'); ?></strong> - <?php _e('Premium feature', 'simplepco-online'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Send SMS via Clearstream API.
     *
     * This method would be called from the Services module when composing messages.
     *
     * @param array $phone_numbers Array of phone numbers to send to
     * @param string $message The message content
     * @return array Result with success/error info
     */
    public function send_sms($phone_numbers, $message) {
        // Get credentials from settings repository
        $creds = $this->repository('settings')->get_clearstream_credentials();
        $api_token = $creds['api_key'];
        $message_header = $creds['message_header'];

        if (empty($api_token)) {
            return ['error' => 'Clearstream API token not configured'];
        }

        // Prepend header if set
        if (!empty($message_header)) {
            $message = $message_header . ' ' . $message;
        }

        // Prepare API request
        // Official URL: https://api.getclearstream.com/v1/
        $api_url = 'https://api.getclearstream.com/v1/messages';
        $body = json_encode([
                'message' => $message,
                'subscribers' => $phone_numbers
        ]);

        $response = wp_remote_post($api_url, [
                'headers' => [
                        'X-API-Key' => $api_token,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                ],
                'body' => $body,
                'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code === 200 || $response_code === 201) {
            return ['success' => true, 'data' => $response_body];
        } else {
            $error_msg = isset($response_body['message']) ? $response_body['message'] : 'Unknown error';
            return ['error' => $error_msg . ' (Code: ' . $response_code . ')'];
        }
    }
}
