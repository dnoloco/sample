<?php
/**
 * License Management Page
 *
 * Handles license activation, deactivation, and status display.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SimplePCO_License_Page {

    private $plugin_name;
    private $version;
    private $license_manager;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        if (class_exists('SimplePCO_License_Manager')) {
            $this->license_manager = SimplePCO_License_Manager::get_instance();
        }
    }

    /**
     * Initialize hooks
     */
    public function init($loader) {
        $loader->add_action('admin_menu', $this, 'add_license_menu');
        $loader->add_action('admin_menu', $this, 'hide_license_menu', 999);
        $loader->add_filter('parent_file', $this, 'set_license_menu_highlight');
        $loader->add_action('current_screen', $this, 'set_license_page_title');

        $loader->add_action('wp_ajax_simplepco_activate_license', $this, 'ajax_activate_license');
        $loader->add_action('wp_ajax_simplepco_deactivate_license', $this, 'ajax_deactivate_license');
        $loader->add_action('wp_ajax_simplepco_refresh_license', $this, 'ajax_refresh_license');
    }

    /**
     * Set Modules as active submenu when on License page
     */
    public function set_license_menu_highlight($parent_file) {
        global $submenu_file;

        if (isset($_GET['page']) && $_GET['page'] === 'simplepco-license') {
            $submenu_file = 'simplepco-modules';
        }

        return $parent_file ?? '';
    }

    /**
     * Set page title to avoid null deprecation warning after remove_submenu_page
     */
    public function set_license_page_title() {
        global $title;

        if (isset($_GET['page']) && $_GET['page'] === 'simplepco-license') {
            $title = __('License', 'simplepco');
        }
    }

    /**
     * Add license page under simplepco-dashboard
     */
    public function add_license_menu() {
        add_submenu_page(
            'simplepco-dashboard',
            __('License', 'simplepco'),
            __('License', 'simplepco'),
            'manage_options',
            'simplepco-license',
            [$this, 'render_license_page']
        );
    }

    /**
     * Hide license menu item from visible menu
     */
    public function hide_license_menu() {
        remove_submenu_page('simplepco-dashboard', 'simplepco-license');
    }

    /**
     * Render the license management page
     */
    public function render_license_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'simplepco'));
        }

        $license_status = $this->license_manager ? $this->license_manager->get_status_summary() : null;
        $license_key = $this->license_manager ? $this->license_manager->get_license_key() : '';
        $is_active = $license_status && $license_status['status'] === 'active';

        ?>
        <div class="wrap">
            <h1><?php _e('SimplePCO License', 'simplepco'); ?></h1>

            <div class="simplepco-license-container" style="max-width: 800px;">

                <!-- Current Status Card -->
                <div class="card" style="max-width: 100%; margin-bottom: 20px;">
                    <h2><?php _e('License Status', 'simplepco'); ?></h2>

                    <?php if ($is_active): ?>
                        <div class="simplepco-license-active" style="background: #ecf7ed; border-left: 4px solid #46b450; padding: 15px; margin: 15px 0;">
                            <p style="margin: 0;">
                                <span class="dashicons dashicons-yes-alt" style="color: #46b450; font-size: 24px; vertical-align: middle;"></span>
                                <strong style="font-size: 16px; margin-left: 10px;">
                                    <?php printf(__('%s License Active', 'simplepco'), esc_html($license_status['tier_label'])); ?>
                                </strong>
                            </p>
                            <table class="form-table" style="margin-top: 15px;">
                                <tr>
                                    <th scope="row"><?php _e('License Key', 'simplepco'); ?></th>
                                    <td><code><?php echo esc_html($this->mask_license_key($license_key)); ?></code></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Tier', 'simplepco'); ?></th>
                                    <td><?php echo esc_html($license_status['tier_label']); ?></td>
                                </tr>
                                <?php if ($license_status['expires_at']): ?>
                                <tr>
                                    <th scope="row"><?php _e('Expires', 'simplepco'); ?></th>
                                    <td><?php echo esc_html(date('F j, Y', strtotime($license_status['expires_at']))); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th scope="row"><?php _e('Available Modules', 'simplepco'); ?></th>
                                    <td>
                                        <?php
                                        $modules = $license_status['modules'] ?? [];
                                        echo esc_html(implode(', ', array_map('ucfirst', $modules)));
                                        ?>
                                    </td>
                                </tr>
                                <?php if (isset($license_status['sites_remaining'])): ?>
                                <tr>
                                    <th scope="row"><?php _e('Site Activations', 'simplepco'); ?></th>
                                    <td><?php printf(__('%d remaining', 'simplepco'), $license_status['sites_remaining']); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>

                            <p style="margin-top: 20px;">
                                <button type="button" id="simplepco-refresh-license" class="button">
                                    <span class="dashicons dashicons-update" style="vertical-align: middle;"></span>
                                    <?php _e('Refresh Status', 'simplepco'); ?>
                                </button>
                                <button type="button" id="simplepco-deactivate-license" class="button" style="margin-left: 10px; color: #a00;">
                                    <?php _e('Deactivate License', 'simplepco'); ?>
                                </button>
                            </p>
                        </div>

                    <?php else: ?>
                        <div class="simplepco-license-inactive" style="background: #fff8e5; border-left: 4px solid #dba617; padding: 15px; margin: 15px 0;">
                            <p style="margin: 0;">
                                <span class="dashicons dashicons-warning" style="color: #dba617; font-size: 24px; vertical-align: middle;"></span>
                                <strong style="font-size: 16px; margin-left: 10px;">
                                    <?php _e('No Active License', 'simplepco'); ?>
                                </strong>
                            </p>
                            <p style="color: #666; margin-top: 10px;">
                                <?php _e('Enter your license key below to activate premium modules and features.', 'simplepco'); ?>
                            </p>
                        </div>

                        <!-- Activation Form -->
                        <form id="simplepco-license-form" style="margin-top: 20px;">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="simplepco-license-key"><?php _e('License Key', 'simplepco'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text"
                                               id="simplepco-license-key"
                                               name="license_key"
                                               class="regular-text"
                                               placeholder="SIMPLEPCO-XXXX-XXXX-XXXX-XXXX"
                                               value="<?php echo esc_attr($license_key); ?>"
                                               style="font-family: monospace;">
                                        <p class="description">
                                            <?php _e('Enter your license key in the format: SIMPLEPCO-XXXX-XXXX-XXXX-XXXX', 'simplepco'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p class="submit">
                                <button type="submit" id="simplepco-activate-license" class="button button-primary">
                                    <?php _e('Activate License', 'simplepco'); ?>
                                </button>
                            </p>

                            <div id="simplepco-license-message" style="display: none;"></div>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Pricing Info -->
                <?php if (!$is_active): ?>
                <div class="card" style="max-width: 100%;">
                    <h2><?php _e('Get a License', 'simplepco'); ?></h2>
                    <p><?php _e('Unlock premium modules and features with a SimplePCO license.', 'simplepco'); ?></p>

                    <div class="simplepco-pricing-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">

                        <div class="simplepco-pricing-tier" style="border: 1px solid #ddd; border-radius: 4px; padding: 20px; text-align: center;">
                            <h3 style="margin-top: 0;"><?php _e('Starter', 'simplepco'); ?></h3>
                            <p style="font-size: 24px; font-weight: bold; margin: 10px 0;">$49<span style="font-size: 14px; font-weight: normal;">/year</span></p>
                            <ul style="text-align: left; padding-left: 20px; color: #666;">
                                <li><?php _e('Services Premium Features', 'simplepco'); ?></li>
                                <li><?php _e('Calendar Premium Features', 'simplepco'); ?></li>
                                <li><?php _e('1 Site Activation', 'simplepco'); ?></li>
                            </ul>
                        </div>

                        <div class="simplepco-pricing-tier" style="border: 2px solid #2271b1; border-radius: 4px; padding: 20px; text-align: center; background: #f0f6fc;">
                            <span style="background: #2271b1; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;"><?php _e('POPULAR', 'simplepco'); ?></span>
                            <h3 style="margin-top: 10px;"><?php _e('Professional', 'simplepco'); ?></h3>
                            <p style="font-size: 24px; font-weight: bold; margin: 10px 0;">$99<span style="font-size: 14px; font-weight: normal;">/year</span></p>
                            <ul style="text-align: left; padding-left: 20px; color: #666;">
                                <li><?php _e('All Premium Modules', 'simplepco'); ?></li>
                                <li><?php _e('Groups, Signups, Messages', 'simplepco'); ?></li>
                                <li><?php _e('3 Site Activations', 'simplepco'); ?></li>
                            </ul>
                        </div>

                        <div class="simplepco-pricing-tier" style="border: 1px solid #ddd; border-radius: 4px; padding: 20px; text-align: center;">
                            <h3 style="margin-top: 0;"><?php _e('Agency', 'simplepco'); ?></h3>
                            <p style="font-size: 24px; font-weight: bold; margin: 10px 0;">$249<span style="font-size: 14px; font-weight: normal;">/year</span></p>
                            <ul style="text-align: left; padding-left: 20px; color: #666;">
                                <li><?php _e('All Premium Modules', 'simplepco'); ?></li>
                                <li><?php _e('Priority Support', 'simplepco'); ?></li>
                                <li><?php _e('25 Site Activations', 'simplepco'); ?></li>
                            </ul>
                        </div>

                    </div>

                    <p style="margin-top: 20px; text-align: center;">
                        <a href="https://your-site.com/pricing" target="_blank" class="button button-primary button-hero">
                            <?php _e('Purchase a License', 'simplepco'); ?>
                        </a>
                    </p>
                </div>
                <?php endif; ?>

            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Activate License
            $('#simplepco-license-form').on('submit', function(e) {
                e.preventDefault();

                var $btn = $('#simplepco-activate-license');
                var $msg = $('#simplepco-license-message');
                var licenseKey = $('#simplepco-license-key').val().trim();

                if (!licenseKey) {
                    $msg.html('<div class="notice notice-error"><p><?php _e('Please enter a license key.', 'simplepco'); ?></p></div>').show();
                    return;
                }

                $btn.prop('disabled', true).text('<?php _e('Activating...', 'simplepco'); ?>');
                $msg.hide();

                $.post(ajaxurl, {
                    action: 'simplepco_activate_license',
                    license_key: licenseKey,
                    nonce: '<?php echo wp_create_nonce('simplepco_license_action'); ?>'
                }, function(res) {
                    if (res.success) {
                        $msg.html('<div class="notice notice-success"><p>' + res.data.message + '</p></div>').show();
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        $msg.html('<div class="notice notice-error"><p>' + (res.data.message || '<?php _e('Activation failed.', 'simplepco'); ?>') + '</p></div>').show();
                        $btn.prop('disabled', false).text('<?php _e('Activate License', 'simplepco'); ?>');
                    }
                }).fail(function() {
                    $msg.html('<div class="notice notice-error"><p><?php _e('Connection error. Please try again.', 'simplepco'); ?></p></div>').show();
                    $btn.prop('disabled', false).text('<?php _e('Activate License', 'simplepco'); ?>');
                });
            });

            // Deactivate License
            $('#simplepco-deactivate-license').on('click', function() {
                if (!confirm('<?php _e('Are you sure you want to deactivate your license from this site?', 'simplepco'); ?>')) {
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true).text('<?php _e('Deactivating...', 'simplepco'); ?>');

                $.post(ajaxurl, {
                    action: 'simplepco_deactivate_license',
                    nonce: '<?php echo wp_create_nonce('simplepco_license_action'); ?>'
                }, function(res) {
                    window.location.reload();
                }).fail(function() {
                    alert('<?php _e('Error deactivating license.', 'simplepco'); ?>');
                    $btn.prop('disabled', false).text('<?php _e('Deactivate License', 'simplepco'); ?>');
                });
            });

            // Refresh License
            $('#simplepco-refresh-license').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true);
                $btn.find('.dashicons').addClass('spin');

                $.post(ajaxurl, {
                    action: 'simplepco_refresh_license',
                    nonce: '<?php echo wp_create_nonce('simplepco_license_action'); ?>'
                }, function(res) {
                    window.location.reload();
                }).fail(function() {
                    alert('<?php _e('Error refreshing license status.', 'simplepco'); ?>');
                    $btn.prop('disabled', false);
                    $btn.find('.dashicons').removeClass('spin');
                });
            });
        });
        </script>

        <style>
            .dashicons.spin {
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                100% { transform: rotate(360deg); }
            }
        </style>
        <?php
    }

    /**
     * Mask license key for display
     */
    private function mask_license_key($key) {
        if (strlen($key) < 10) {
            return $key;
        }
        return substr($key, 0, 10) . str_repeat('*', strlen($key) - 14) . substr($key, -4);
    }

    /**
     * AJAX: Activate license
     */
    public function ajax_activate_license() {
        check_ajax_referer('simplepco_license_action', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'simplepco')]);
        }

        $license_key = sanitize_text_field($_POST['license_key']);

        if (empty($license_key)) {
            wp_send_json_error(['message' => __('Please enter a license key.', 'simplepco')]);
        }

        $result = $this->license_manager->activate_license($license_key);

        if ($result['success']) {
            wp_send_json_success(['message' => $result['message']]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    /**
     * AJAX: Deactivate license
     */
    public function ajax_deactivate_license() {
        check_ajax_referer('simplepco_license_action', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'simplepco')]);
        }

        $result = $this->license_manager->deactivate_license();
        wp_send_json_success(['message' => $result['message']]);
    }

    /**
     * AJAX: Refresh license status
     */
    public function ajax_refresh_license() {
        check_ajax_referer('simplepco_license_action', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'simplepco')]);
        }

        $this->license_manager->clear_cache();
        $this->license_manager->get_license_data(true);

        wp_send_json_success();
    }
}
