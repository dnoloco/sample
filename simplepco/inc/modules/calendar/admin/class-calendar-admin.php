<?php
/**
 * Calendar Admin Component
 *
 * Handles all backend/admin functionality for the Calendar module.
 */

class SimplePCO_Calendar_Admin {

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
        // Add settings page
        $this->loader->add_action('admin_menu', $this, 'add_settings_page');

        // Enqueue admin assets
        $this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');

        // Handle cache clearing
        $this->loader->add_action('admin_init', $this, 'handle_cache_clear');
    }

    /**
     * Add admin settings page.
     */
    public function add_settings_page() {
        add_submenu_page(
            'simplepco-dashboard',
            __('Calendar', 'simplepco'),
            __('Calendar', 'simplepco'),
            'manage_options',
            'simplepco-calendar',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Enqueue admin-specific assets.
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our settings page
        if (strpos($hook, 'simplepco-calendar') === false) {
            return;
        }

        wp_enqueue_style(
            'simplepco-calendar-admin',
            SIMPLEPCO_PLUGIN_URL . 'inc/modules/calendar/admin/assets/css/calendar-admin.css',
            [],
            SIMPLEPCO_VERSION
        );

        wp_enqueue_script(
            'simplepco-calendar-admin',
            SIMPLEPCO_PLUGIN_URL . 'inc/modules/calendar/admin/assets/js/calendar-admin.js',
            ['jquery'],
            SIMPLEPCO_VERSION,
            true
        );
    }

    /**
     * Render the settings page.
     * NO HTML HERE - just prepare data and load template.
     */
    public function render_settings_page() {
        // Get all shortcodes and filter for calendar module
        $all_shortcodes = get_option(SimplePCO_Shortcodes_Admin::OPTION_KEY, []);
        $types = SimplePCO_Shortcodes_Admin::get_shortcode_types();

        $calendar_shortcodes = [];
        foreach ($all_shortcodes as $sc_id => $sc) {
            $type_slug = SimplePCO_Shortcodes_Admin::resolve_legacy_type($sc['shortcode_type'] ?? '', $sc);
            $type_def = isset($types[$type_slug]) ? $types[$type_slug] : null;
            if ($type_def && $type_def['module'] === 'calendar') {
                // Find pages where this shortcode is used
                global $wpdb;
                $tag = $type_def['tag'];
                $like_pattern = '%[' . $wpdb->esc_like($tag) . ' id="' . $sc_id . '"]%';
                $pages = $wpdb->get_results($wpdb->prepare(
                    "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_content LIKE %s AND post_status IN ('publish', 'draft', 'private') AND post_type IN ('page', 'post')",
                    $like_pattern
                ));

                $calendar_shortcodes[$sc_id] = [
                    'settings'  => $sc,
                    'type_slug' => $type_slug,
                    'type_def'  => $type_def,
                    'pages'     => $pages,
                ];
            }
        }

        // Prepare data for template
        $data = [
            'cache_cleared'       => isset($_GET['cache_cleared']),
            'calendar_shortcodes' => $calendar_shortcodes,
            'types'               => $types,
            'add_new_url'         => admin_url('admin.php?page=simplepco-shortcodes&action=new&module=calendar'),
            'shortcodes_page_url' => admin_url('admin.php?page=simplepco-shortcodes'),
        ];

        // Load template
        $this->load_template('settings-page', $data);
    }

    /**
     * Handle cache clearing.
     */
    public function handle_cache_clear() {
        if (!isset($_POST['simplepco_clear_calendar_cache'])) {
            return;
        }

        check_admin_referer('simplepco_clear_calendar_cache');

        if (!current_user_can('manage_options')) {
            return;
        }

        // Clear calendar transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_simplepco_calendar%'");

        // Redirect with success message
        wp_redirect(admin_url('admin.php?page=simplepco-calendar&cache_cleared=1'));
        exit;
    }

    /**
     * Load a template file.
     */
    private function load_template($template_name, $data = []) {
        extract($data);
        $template_path = SIMPLEPCO_PLUGIN_DIR . 'templates/calendar/admin/' . $template_name . '.php';

        if (file_exists($template_path)) {
            include $template_path;
        }
    }
}
