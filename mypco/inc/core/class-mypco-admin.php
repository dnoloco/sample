<?php
class MyPCO_Admin {

    private $plugin_name;
    private $version;
    private $modules;
    private $loader;
    private $api_model;

    /**
     * Update the constructor to accept loader and api_model
     */
    public function __construct($plugin_name, $version, $loader, $api_model) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
        $this->loader      = $loader;
        $this->api_model   = $api_model;

        $loader->add_action('wp_ajax_mypco_save_dashboard_order', $this, 'ajax_save_dashboard_order');
    }

    public function enqueue_styles() {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'toplevel_page_mypco-dashboard') {
            wp_enqueue_style('dashboard');
            wp_enqueue_style($this->plugin_name . '-dashboard', MYPCO_PLUGIN_URL . 'assets/admin/css/mypco-dashboard.css', ['dashboard'], $this->version);
        }
    }

    public function enqueue_scripts() {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'toplevel_page_mypco-dashboard') {
            wp_enqueue_script('postbox');
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script($this->plugin_name . '-dashboard', MYPCO_PLUGIN_URL . 'assets/admin/js/mypco-dashboard.js', ['jquery', 'postbox', 'jquery-ui-sortable'], $this->version, true);
        }
    }

    public function add_admin_menu() {
        // Main Dashboard
        $page_hook = add_menu_page(
                'MyPCO Dashboard', 'MyPCO', 'manage_options',
                'mypco-dashboard', [$this, 'render_dashboard'],
                'dashicons-cloud', 30
        );

        // Dashboard submenu (first item)
        add_submenu_page('mypco-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'mypco-dashboard', [$this, 'render_dashboard']);

        // Active modules will add their own menu items here (via their own admin_menu hooks)
        // Modules and Settings are added at a later priority to appear after active modules

        add_action("load-$page_hook", [$this, 'on_load_dashboard_page']);
    }

    /**
     * Add Modules menu item (called at later priority so it appears after active module menus)
     */
    public function add_modules_menu() {
        add_submenu_page('mypco-dashboard', 'Modules', 'Modules', 'manage_options', 'mypco-modules', [$this, 'render_modules_page']);
    }

    public function on_load_dashboard_page() {
        $screen_id = get_current_screen()->id;

        // Force 3 column layout
        add_screen_option('layout_columns', ['max' => 3, 'default' => 3]);

        // COLUMN 1 (Side)
        add_meta_box('mypco_quick_links', 'Quick Links', [$this, 'render_quick_links_metabox'], $screen_id, 'side', 'high');
        add_meta_box('mypco_connection_status', 'API Connection Status', [$this, 'render_status_metabox'], $screen_id, 'side', 'default');

        // COLUMN 2 (Normal)
        add_meta_box('mypco_welcome', 'Welcome to MyPCO Online', [$this, 'render_welcome_metabox'], $screen_id, 'normal', 'high');
        add_meta_box('mypco_support', 'Support & Documentation', [$this, 'render_support_box'], $screen_id, 'normal', 'default');

        // COLUMN 3 (Advanced)
        add_meta_box('mypco_modules', 'Installed Modules', [$this, 'render_modules_box'], $screen_id, 'advanced', 'high');
    }

    public function render_dashboard() {
        $screen = get_current_screen();
        ?>
        <div class="wrap">
            <h1>MyPCO Dashboard</h1>
            <div id="dashboard-widgets-wrap">
                <form method="post">
                    <?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false); ?>
                    <?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false); ?>
                    <input type="hidden" id="mypco-dashboard-nonce" value="<?php echo wp_create_nonce('mypco-dashboard-nonce'); ?>">
                    <div id="dashboard-widgets" class="metabox-holder columns-3" data-pagenow="<?php echo esc_attr($screen->id); ?>">
                        <div id="postbox-container-1" class="postbox-container"><?php do_meta_boxes($screen->id, 'side', null); ?></div>
                        <div id="postbox-container-2" class="postbox-container"><?php do_meta_boxes($screen->id, 'normal', null); ?></div>
                        <div id="postbox-container-3" class="postbox-container"><?php do_meta_boxes($screen->id, 'advanced', null); ?></div>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    // Metabox Callbacks
    public function render_welcome_metabox() {
        ?>
        <div class="mypco-dashboard-content">
            <p>A modular Planning Center Online integration for WordPress.</p>
            <h4>Getting Started</h4>
            <ol>
                <li>Configure your API credentials in <a href="<?php echo admin_url('admin.php?page=mypco-settings'); ?>">API Credentials</a></li>
                <li>Visit the <a href="<?php echo admin_url('admin.php?page=mypco-modules'); ?>">Modules</a> page to enable features</li>
                <li>Use shortcodes in your pages to display PCO content</li>
            </ol>
        </div>
        <?php
    }
    public function render_status_metabox() {
        $pco_creds = MyPCO_Credentials_Manager::get_pco_credentials();
        $cs_creds  = MyPCO_Credentials_Manager::get_clearstream_credentials();

        $pco_ok = !empty($pco_creds['client_id']);
        $cs_ok  = !empty($cs_creds['api_key']);
        ?>
        <div class="mypco-status-card <?php echo $pco_ok ? 'connected' : 'disconnected'; ?>">
            <h4><?php echo $pco_ok ? '✅ PCO Connected' : '❌ PCO Disconnected'; ?></h4>
            <p>API credentials configured and ready.</p>
        </div>

        <div class="mypco-status-card <?php echo $cs_ok ? 'connected' : 'disconnected'; ?>">
            <h4><?php echo $cs_ok ? '✅ Clearstream Connected' : '❌ Clearstream Disconnected'; ?></h4>
            <p>SMS messaging is available.</p>
        </div>
        <?php
    }

    /**
     * Render the Support & Documentation Box (Center Column)
     */
    public function render_support_box() {
        ?>
        <div class="mypco-dashboard-content">
            <p><strong>Need Help?</strong></p>
            <ul class="mypco-dashboard-list">
                <li><a href="https://developer.planningcenteronline.com/docs" target="_blank">Planning Center Documentation <span class="dashicons dashicons-external"></span></a></li>
                <li><a href="https://api.getclearstream.com/v1/" target="_blank">Clearstream API Docs <span class="dashicons dashicons-external"></span></a></li>
            </ul>
            <hr>
            <p><strong>Plugin Info</strong></p>
            <?php
            $active_modules = get_option('mypco_active_modules', []);
            $active_count = 0;
            foreach ($active_modules as $mod) {
                if (!empty($mod['enabled'])) $active_count++;
            }
            ?>
            <p><strong>Version:</strong> <?php echo esc_html($this->version); ?><br>
                <strong>Modules:</strong> <?php echo (int) $active_count; ?> active</p>
        </div>
        <?php
    }

    /**
     * Render the Installed Modules Table (Right Column)
     */
    public function render_modules_box() {
        $manager = new MyPCO_Module_Manager($this->loader, $this->api_model);
        $manager->init_modules();
        $modules = $manager->get_registered_modules();
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th>Module</th>
                <th>Type</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($modules as $key => $module) :
                if (!empty($module['is_addon'])) continue;

                $tier = $module['tier'] ?? 'premium';
                if ($tier === 'free' || $tier === 'freemium') {
                    $tier_icon = '<span class="dashicons dashicons-yes" style="color:green"></span> Free';
                } else {
                    $tier_icon = '<span class="dashicons dashicons-star-filled" style="color:#ffb900"></span> Premium';
                }

                $is_active = $manager->is_module_enabled($key);
            ?>
            <tr>
                <td><strong><?php echo esc_html($module['name']); ?></strong><br><small><?php echo esc_html($module['description']); ?></small></td>
                <td><?php echo $tier_icon; ?></td>
                <td>
                    <?php if ($is_active) : ?>
                        <span style="color:green">● Active</span>
                    <?php else : ?>
                        <span style="color:#999">○ Inactive</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render the Quick Links (Left Column)
     */
    public function render_quick_links_metabox() {
        $active_modules = get_option('mypco_active_modules', []);

        $module_links = [
            'calendar' => [
                'icon'  => 'dashicons-calendar-alt',
                'label' => 'Calendar',
                'url'   => admin_url('admin.php?page=mypco-calendar'),
            ],
            'groups' => [
                'icon'  => 'dashicons-groups',
                'label' => 'Groups',
                'url'   => admin_url('admin.php?page=mypco-groups'),
            ],
            'services' => [
                'icon'  => 'dashicons-clipboard',
                'label' => 'Service Plans',
                'url'   => admin_url('admin.php?page=mypco-services'),
            ],
            'series' => [
                'icon'  => 'dashicons-microphone',
                'label' => 'Messages',
                'url'   => admin_url('edit.php?post_type=mypco_message'),
            ],
            'signups' => [
                'icon'  => 'dashicons-tickets-alt',
                'label' => 'Signups',
                'url'   => admin_url('admin.php?page=mypco-signups'),
            ],
            'contacts' => [
                'icon'  => 'dashicons-email-alt',
                'label' => 'Contacts',
                'url'   => admin_url('admin.php?page=mypco-contacts'),
            ],
        ];
        ?>
        <ul class="mypco-dashboard-list">
            <li><span class="dashicons dashicons-admin-network"></span> <a href="<?php echo esc_url(admin_url('admin.php?page=mypco-settings')); ?>">API Credentials</a></li>
            <li><span class="dashicons dashicons-admin-plugins"></span> <a href="<?php echo esc_url(admin_url('admin.php?page=mypco-modules')); ?>">Manage Modules</a></li>
            <?php foreach ($module_links as $key => $link) :
                if (empty($active_modules[$key]['enabled'])) continue;
            ?>
            <li><span class="dashicons <?php echo esc_attr($link['icon']); ?>"></span> <a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a></li>
            <?php endforeach; ?>
        </ul>
        <?php
    }

    private function set_default_metabox_order($pagenow) {
        $meta_key = 'meta-box-order_' . $pagenow;
        if (!get_user_meta(get_current_user_id(), $meta_key, true)) {
            update_user_meta(get_current_user_id(), $meta_key, ['postbox-container-1' => 'mypco_quick_links','postbox-container-2' => 'mypco_welcome']);
        }
    }

    public function ajax_save_dashboard_order() {
        check_ajax_referer('mypco-dashboard-nonce', '_ajax_nonce');
        update_user_meta(get_current_user_id(), 'meta-box-order_' . $_POST['page'], $_POST['order']);
        wp_send_json_success();
    }

    public function render_modules_page() {
        // Assume $this->modules_ui was passed or instantiated
        $modules_ui = new MyPCO_Modules($this->loader, $this->api_model);
        $modules_ui->render_modules_page();
    }

    public function render_license_page() { ?>
        <div class="wrap"><h1>License</h1><form method="post" action="options.php">
                <?php settings_fields('mypco_license_settings'); ?>
                <input type="text" name="mypco_license_services" value="<?php echo esc_attr(get_option('mypco_license_services', '')); ?>">
                <?php submit_button(); ?></form></div>
    <?php }
}
