<?php
/**
 * SimplePCO Modules UI Controller
 *
 * Handles the rendering and AJAX toggling of plugin modules.
 * Supports tiered module display (free, freemium, premium).
 */

if (!defined('ABSPATH')) {
    exit;
}

class SimplePCO_Modules {

    private $loader;
    private $api_model;
    private $module_manager;
    private $license_manager;

    public function __construct($loader, $api_model) {
        $this->loader = $loader;
        $this->api_model = $api_model;

        if (class_exists('SimplePCO_Module_Manager')) {
            $this->module_manager = new SimplePCO_Module_Manager($this->loader, $this->api_model);
            $this->module_manager->init_modules(); // Initialize to populate modules array
        }

        if (class_exists('SimplePCO_License_Manager')) {
            $this->license_manager = SimplePCO_License_Manager::get_instance();
        }
    }

    /**
     * Initialize the Module UI hooks
     */
    public function init() {
        $this->loader->add_action('wp_ajax_simplepco_toggle_module', $this, 'ajax_toggle_module');
    }

    /**
     * Render the Modules Management Page
     */
    public function render_modules_page() {
        if (!$this->module_manager) {
            echo '<div class="notice notice-error"><p>Module Manager not found.</p></div>';
            return;
        }

        $modules = $this->module_manager->get_modules();
        $license_status = $this->license_manager ? $this->license_manager->get_status_summary() : null;
        $is_licensed = $license_status && $license_status['status'] === 'active';

        ?>
        <div class="wrap">
            <h1><?php _e('SimplePCO Modules', 'simplepco'); ?></h1>
            <p class="description"><?php _e('Enable or disable features to customize your integration experience.', 'simplepco'); ?></p>

            <?php $this->render_license_status_banner($license_status); ?>

            <!-- Free & Freemium Modules -->
            <h2 style="margin-top: 30px;"><?php _e('Included Modules', 'simplepco'); ?></h2>
            <p class="description"><?php _e('These modules are included with SimplePCO. Freemium modules have additional premium features available with a license.', 'simplepco'); ?></p>

            <div class="simplepco-modules-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 20px;">
                <?php
                foreach ($modules as $key => $module) {
                    if ($module['tier'] === 'free' || $module['tier'] === 'freemium') {
                        $this->render_module_card($key, $module, $is_licensed);
                    }
                }
                ?>
            </div>

            <!-- Premium Modules -->
            <h2 style="margin-top: 40px;"><?php _e('Premium Modules', 'simplepco'); ?></h2>
            <p class="description">
                <?php _e('These modules require a Professional or Agency license.', 'simplepco'); ?>
                <?php if (!$is_licensed): ?>
                    <a href="<?php echo admin_url('admin.php?page=simplepco-license'); ?>"><?php _e('Activate your license', 'simplepco'); ?></a>
                <?php endif; ?>
            </p>

            <div class="simplepco-modules-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 20px;">
                <?php
                foreach ($modules as $key => $module) {
                    if ($module['tier'] === 'premium') {
                        $this->render_module_card($key, $module, $is_licensed);
                    }
                }
                ?>
            </div>
        </div>

        <?php $this->render_module_scripts(); ?>
        <?php
    }

    /**
     * Render license status banner
     */
    private function render_license_status_banner($license_status) {
        if (!$license_status || $license_status['status'] !== 'active') {
            ?>
            <div class="notice notice-info" style="margin: 20px 0;">
                <p>
                    <strong><?php _e('Unlock Premium Modules', 'simplepco'); ?></strong><br>
                    <?php _e('Get access to Groups, Signups, Messages, and premium features in Services and Calendar.', 'simplepco'); ?>
                    <a href="<?php echo admin_url('admin.php?page=simplepco-license'); ?>" class="button button-primary" style="margin-left: 15px;">
                        <?php _e('Activate License', 'simplepco'); ?>
                    </a>
                </p>
            </div>
            <?php
        } else {
            ?>
            <div class="notice notice-success" style="margin: 20px 0;">
                <p>
                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                    <strong><?php echo esc_html($license_status['tier_label']); ?> <?php _e('License Active', 'simplepco'); ?></strong>
                    <?php if ($license_status['expires_at']): ?>
                        &mdash; <?php printf(__('Expires: %s', 'simplepco'), date('F j, Y', strtotime($license_status['expires_at']))); ?>
                    <?php endif; ?>
                    <a href="<?php echo admin_url('admin.php?page=simplepco-license'); ?>" style="margin-left: 15px;"><?php _e('Manage License', 'simplepco'); ?></a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Render a single module card
     */
    private function render_module_card($key, $module, $is_licensed) {
        $enabled = $this->module_manager->is_module_enabled($key);
        $can_enable = $this->module_manager->can_enable_module($key);
        $access_status = $this->module_manager->get_module_access_status($key, $module);

        // Determine badge
        $badge = '';
        $badge_style = '';
        if (!empty($module['is_addon'])) {
            $badge = 'ADDON';
            $badge_style = 'background: #00a32a; color: #fff;';
        } elseif ($module['tier'] === 'freemium') {
            $badge = 'FREEMIUM';
            $badge_style = 'background: #2271b1; color: #fff;';
        } elseif ($module['tier'] === 'premium') {
            $badge = 'PREMIUM';
            $badge_style = 'background: #dba617; color: #fff;';
        }

        // Card styling based on state
        $card_class = $enabled ? 'is-active' : 'is-inactive';
        if ($access_status === 'locked') {
            $card_class .= ' is-locked';
        }
        ?>
        <div class="postbox simplepco-module-card <?php echo esc_attr($card_class); ?>" style="margin-bottom: 0; display: flex; flex-direction: column; <?php echo $access_status === 'locked' ? 'opacity: 0.8;' : ''; ?>">
            <div class="postbox-header" style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin:0; font-size: 1.1em;"><?php echo esc_html($module['name']); ?></h2>
                <?php if ($badge): ?>
                    <span class="simplepco-badge" style="font-size: 9px; padding: 2px 8px; border-radius: 3px; font-weight: bold; <?php echo $badge_style; ?>">
                        <?php echo esc_html($badge); ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="inside" style="padding: 15px; flex-grow: 1;">
                <p style="color: #666; min-height: 40px;"><?php echo esc_html($module['description']); ?></p>

                <?php if ($module['tier'] === 'freemium' && !empty($module['features']['premium'])): ?>
                    <div class="module-features" style="margin: 10px 0; padding: 10px; background: #f9f9f9; border-radius: 4px; font-size: 12px;">
                        <strong style="color: #666;"><?php _e('Premium Features:', 'simplepco'); ?></strong>
                        <span style="color: #888;">
                            <?php echo esc_html(implode(', ', array_map(function($f) {
                                return ucwords(str_replace('_', ' ', $f));
                            }, array_slice($module['features']['premium'], 0, 3)))); ?>
                            <?php if (count($module['features']['premium']) > 3): ?>
                                <?php printf(__('+ %d more', 'simplepco'), count($module['features']['premium']) - 3); ?>
                            <?php endif; ?>
                        </span>
                        <?php if (!$is_licensed): ?>
                            <span style="color: #dba617; margin-left: 5px;"><?php _e('(requires license)', 'simplepco'); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="module-footer" style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div class="module-status">
                        <?php if ($enabled): ?>
                            <span style="color: #46b450; font-weight: bold;">
                                <span class="dashicons dashicons-yes"></span> <?php _e('Enabled', 'simplepco'); ?>
                            </span>
                        <?php elseif ($access_status === 'locked'): ?>
                            <span style="color: #dba617;">
                                <span class="dashicons dashicons-lock"></span> <?php _e('License Required', 'simplepco'); ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #999;">
                                <span class="dashicons dashicons-no"></span> <?php _e('Disabled', 'simplepco'); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="module-action">
                        <?php if ($access_status === 'locked'): ?>
                            <a href="<?php echo admin_url('admin.php?page=simplepco-license'); ?>" class="button button-secondary">
                                <?php _e('Get License', 'simplepco'); ?>
                            </a>
                        <?php elseif ($access_status === 'upgrade_required'): ?>
                            <a href="<?php echo admin_url('admin.php?page=simplepco-license'); ?>" class="button button-secondary">
                                <?php _e('Upgrade License', 'simplepco'); ?>
                            </a>
                        <?php else: ?>
                            <button type="button"
                                    class="button toggle-module-btn <?php echo $enabled ? 'button-secondary' : 'button-primary'; ?>"
                                    data-module="<?php echo esc_attr($key); ?>"
                                    data-action="<?php echo $enabled ? 'disable' : 'enable'; ?>">
                                <?php echo $enabled ? __('Disable', 'simplepco') : __('Enable', 'simplepco'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render JavaScript for module toggling
     */
    private function render_module_scripts() {
        ?>
        <script>
            jQuery(document).ready(function($) {
                $('body').on('click', '.toggle-module-btn', function(e) {
                    e.preventDefault();

                    var $btn = $(this);
                    var moduleKey = $btn.attr('data-module');
                    var moduleAction = $btn.attr('data-action');

                    $btn.prop('disabled', true).text('<?php _e('Updating...', 'simplepco'); ?>');

                    $.post(ajaxurl, {
                        action: 'simplepco_toggle_module',
                        module: moduleKey,
                        todo: moduleAction,
                        nonce: '<?php echo wp_create_nonce("simplepco_module_toggle"); ?>'
                    }, function(res) {
                        if (res.success) {
                            window.location.reload();
                        } else {
                            alert(res.data || '<?php _e('Failed to update module.', 'simplepco'); ?>');
                            $btn.prop('disabled', false).text(moduleAction === 'enable' ? '<?php _e('Enable', 'simplepco'); ?>' : '<?php _e('Disable', 'simplepco'); ?>');
                        }
                    }).fail(function(xhr) {
                        console.error('AJAX Error:', xhr.responseText);
                        $btn.prop('disabled', false).text('<?php _e('Error - Try Again', 'simplepco'); ?>');
                    });
                });
            });
        </script>
        <style>
            .simplepco-module-card.is-active {
                border-left: 4px solid #46b450;
            }
            .simplepco-module-card.is-locked {
                border-left: 4px solid #dba617;
            }
            .simplepco-module-card .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                vertical-align: middle;
            }
        </style>
        <?php
    }

    /**
     * AJAX Handler to toggle module state
     */
    public function ajax_toggle_module() {
        check_ajax_referer('simplepco_module_toggle', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'simplepco'));
        }

        $module_key = sanitize_text_field($_POST['module']);
        $action = sanitize_text_field($_POST['todo']);

        // Verify module can be enabled (license check)
        if ($action === 'enable' && !$this->module_manager->can_enable_module($module_key)) {
            wp_send_json_error(__('This module requires a valid license.', 'simplepco'));
        }

        if ($action === 'enable') {
            $result = $this->module_manager->enable_module($module_key);
        } else {
            $result = $this->module_manager->disable_module($module_key);
        }

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(__('Failed to update module.', 'simplepco'));
        }
    }
}
