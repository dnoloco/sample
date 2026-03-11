<?php
/**
 * Groups Admin Component
 *
 * Handles all backend/admin functionality for the Groups module.
 * Provides settings page and cache management.
 */

class MyPCO_Groups_Admin {

    private $loader;
    private $api_model;

    public function __construct($loader, $api_model) {
        $this->loader = $loader;
        $this->api_model = $api_model;
    }

    /**
     * Initialize admin functionality.
     */
    public function init() {
        // Add admin pages
        $this->loader->add_action('admin_menu', $this, 'add_admin_pages');
        
        // Enqueue admin assets
        $this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');
        
        // Handle cache clear
        $this->loader->add_action('admin_init', $this, 'handle_cache_clear');
    }

    /**
     * Add admin menu pages.
     */
    public function add_admin_pages() {
        add_submenu_page(
            'mypco-settings',
            __('Groups Settings', 'mypco-online'),
            __('Groups', 'mypco-online'),
            'manage_options',
            'mypco-groups',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Enqueue admin-specific assets.
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our pages
        if (strpos($hook, 'mypco-groups') === false) {
            return;
        }

        wp_enqueue_style(
            'mypco-groups-admin',
            MYPCO_PLUGIN_URL . 'modules/groups/admin/assets/css/groups-admin.css',
            [],
            MYPCO_VERSION
        );
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'mypco-online'));
        }

        $template_data = [
            'cache_cleared' => isset($_GET['cache_cleared'])
        ];

        $this->load_template('settings-page', $template_data);
    }

    /**
     * Handle cache clear action.
     */
    public function handle_cache_clear() {
        if (!isset($_POST['clear_groups_cache'])) {
            return;
        }

        check_admin_referer('clear_groups_cache');

        if (!current_user_can('manage_options')) {
            return;
        }

        // Delete all groups cache transients
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_pco_groups_%' 
             OR option_name LIKE '_transient_timeout_pco_groups_%'"
        );

        wp_redirect(add_query_arg('cache_cleared', '1', admin_url('admin.php?page=mypco-groups')));
        exit;
    }

    /**
     * Load a template file.
     */
    private function load_template($template_name, $data = []) {
        extract($data);
        $template_path = MYPCO_PLUGIN_DIR . 'templates/groups/admin/' . $template_name . '.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap"><h1>Error</h1><p>Template not found: ' . esc_html($template_name) . '</p></div>';
        }
    }
}
