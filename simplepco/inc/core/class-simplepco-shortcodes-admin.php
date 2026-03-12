<?php
/**
 * Shortcodes Admin
 *
 * Centralized shortcode management for all modules.
 * Provides a separate admin page for creating, editing, and managing
 * shortcode instances across all plugin modules.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SimplePCO_Shortcodes_Admin {

    private $loader;
    private $api_model;

    /**
     * Option key for storing shortcode configurations.
     */
    const OPTION_KEY = 'simplepco_shortcodes';

    public function __construct($loader, $api_model) {
        $this->loader = $loader;
        $this->api_model = $api_model;
    }

    /**
     * Initialize admin functionality.
     */
    public function init() {
        $this->loader->add_action('admin_menu', $this, 'add_menu_page');
        $this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');
        $this->loader->add_action('admin_init', $this, 'handle_save_shortcode');
        $this->loader->add_action('admin_init', $this, 'handle_delete_shortcode');
        $this->loader->add_action('admin_init', $this, 'handle_bulk_action');
    }

    // =========================================================================
    // Shortcode Type Registry
    // =========================================================================

    /**
     * Get all available shortcode types organized by module.
     *
     * Each module defines its shortcode types, default settings,
     * and the form fields available for configuration.
     *
     * @return array Shortcode type definitions keyed by type slug.
     */
    public static function get_shortcode_types() {
        $types = [
            'simplepco_calendar_default' => [
                'module'      => 'calendar',
                'module_name' => 'Calendar',
                'name'        => 'PCO Default Calendar',
                'description' => 'Default calendar with list, month and gallery views.',
                'tag'         => 'simplepco_calendar',
                'defaults'    => [
                    'description'      => '',
                    'count'            => 100,
                    'view'             => 'list',
                    'show_featured'    => true,
                    'featured_count'   => 1,
                    'featured_mode'    => 'upcoming',
                    'custom_class'     => '',
                    'primary_color'    => '#333333',
                    'text_color'       => '#333333',
                    'background_color' => '#ffffff',
                    'border_radius'    => 8,
                ],
                'fields' => [
                    [
                        'key'         => 'count',
                        'label'       => 'Number of Events',
                        'type'        => 'number',
                        'min'         => 1,
                        'max'         => 100,
                        'description' => 'Maximum number of events to fetch from Planning Center.',
                    ],
                    [
                        'key'         => 'show_featured',
                        'label'       => 'Include Featured Event in List View',
                        'type'        => 'checkbox',
                        'description' => 'Show the closest upcoming featured event at the top of the list view.',
                    ],
                ],
            ],
            'simplepco_calendar_list' => [
                'module'      => 'calendar',
                'module_name' => 'Calendar',
                'name'        => 'PCO Event List',
                'description' => 'Standalone chronological list of upcoming events.',
                'tag'         => 'simplepco_calendar_list',
                'defaults'    => [
                    'description'      => '',
                    'count'            => 100,
                    'view'             => 'list',
                    'custom_class'     => '',
                    'primary_color'    => '#333333',
                    'text_color'       => '#333333',
                    'background_color' => '#ffffff',
                    'border_radius'    => 8,
                ],
                'fields' => [
                    [
                        'key'         => 'count',
                        'label'       => 'Number of Events',
                        'type'        => 'number',
                        'min'         => 1,
                        'max'         => 100,
                        'description' => 'Maximum number of events to fetch from Planning Center.',
                    ],
                ],
            ],
            'simplepco_calendar_month' => [
                'module'      => 'calendar',
                'module_name' => 'Calendar',
                'name'        => 'PCO Monthly Calendar',
                'description' => 'Standalone calendar grid view of events by month.',
                'tag'         => 'simplepco_calendar_month',
                'defaults'    => [
                    'description'      => '',
                    'count'            => 100,
                    'view'             => 'month',
                    'custom_class'     => '',
                    'primary_color'    => '#333333',
                    'text_color'       => '#333333',
                    'background_color' => '#ffffff',
                    'border_radius'    => 8,
                ],
                'fields' => [
                    [
                        'key'         => 'count',
                        'label'       => 'Number of Events',
                        'type'        => 'number',
                        'min'         => 1,
                        'max'         => 100,
                        'description' => 'Maximum number of events to fetch from Planning Center.',
                    ],
                ],
            ],
            'simplepco_calendar_gallery' => [
                'module'      => 'calendar',
                'module_name' => 'Calendar',
                'name'        => 'PCO Event Gallery',
                'description' => 'Standalone card-based image layout of events.',
                'tag'         => 'simplepco_calendar_gallery',
                'defaults'    => [
                    'description'      => '',
                    'count'            => 100,
                    'view'             => 'gallery',
                    'custom_class'     => '',
                    'primary_color'    => '#333333',
                    'text_color'       => '#333333',
                    'background_color' => '#ffffff',
                    'border_radius'    => 8,
                ],
                'fields' => [
                    [
                        'key'         => 'count',
                        'label'       => 'Number of Events',
                        'type'        => 'number',
                        'min'         => 1,
                        'max'         => 100,
                        'description' => 'Maximum number of events to fetch from Planning Center.',
                    ],
                ],
            ],
            'simplepco_messages_list' => [
                'module'      => 'series',
                'module_name' => 'Series',
                'name'        => 'Message List',
                'description' => 'Display a list of messages with speaker, series, and media links.',
                'tag'         => 'simplepco_messages',
                'defaults'    => [
                    'description'      => '',
                    'count'            => 10,
                    'view'             => 'list',
                    'custom_class'     => '',
                    'primary_color'    => '#333333',
                    'text_color'       => '#333333',
                    'background_color' => '#ffffff',
                    'border_radius'    => 8,
                ],
                'fields' => [
                    [
                        'key'         => 'count',
                        'label'       => 'Number of Messages',
                        'type'        => 'number',
                        'min'         => 1,
                        'max'         => 100,
                        'description' => 'Maximum number of messages to display.',
                    ],
                ],
            ],
            'simplepco_groups' => [
                'module'      => 'groups',
                'module_name' => 'Groups',
                'name'        => 'Group Directory',
                'description' => 'Display Planning Center groups with filtering options.',
                'tag'         => 'simplepco_groups',
                'defaults'    => [
                    'description'      => '',
                    'count'            => 10,
                    'campus'           => '',
                    'custom_class'     => '',
                    'primary_color'    => '#333333',
                    'text_color'       => '#333333',
                    'background_color' => '#ffffff',
                    'border_radius'    => 8,
                ],
                'fields' => [
                    [
                        'key'         => 'count',
                        'label'       => 'Number of Groups',
                        'type'        => 'number',
                        'min'         => 1,
                        'max'         => 100,
                        'description' => 'Maximum number of groups to display.',
                    ],
                    [
                        'key'         => 'campus',
                        'label'       => 'Campus Filter',
                        'type'        => 'text',
                        'description' => 'Filter groups by campus name. Leave blank to show all campuses.',
                    ],
                ],
            ],
            'simplepco_payment_form' => [
                'module'      => 'signups',
                'module_name' => 'Signups',
                'name'        => 'Payment Form',
                'description' => 'Display a Stripe payment form for event registrations.',
                'tag'         => 'simplepco_payment_form',
                'defaults'    => [
                    'description'      => '',
                    'custom_class'     => '',
                    'primary_color'    => '#333333',
                    'text_color'       => '#333333',
                    'background_color' => '#ffffff',
                    'border_radius'    => 8,
                ],
                'fields' => [],
            ],
        ];

        // Filter out shortcode types for disabled modules
        $active_modules = get_option('simplepco_active_modules', []);
        $types = array_filter($types, function($type) use ($active_modules) {
            $module_key = $type['module'] ?? '';
            return isset($active_modules[$module_key]['enabled']) && $active_modules[$module_key]['enabled'] === true;
        });

        /**
         * Allow modules and add-ons to register their own shortcode types.
         *
         * @param array $types Shortcode type definitions.
         */
        return apply_filters('simplepco_shortcode_types', $types);
    }

    /**
     * Resolve a legacy shortcode type slug to the current slug.
     *
     * Handles backward compatibility when type slugs are renamed or split.
     *
     * @param string $type_slug  Original type slug.
     * @param array  $settings   Saved shortcode settings (used to determine the correct variant).
     * @return string Resolved type slug.
     */
    public static function resolve_legacy_type($type_slug, $settings = []) {
        if ($type_slug === 'simplepco_calendar') {
            $view = $settings['view'] ?? 'list';
            $map = [
                'list'    => 'simplepco_calendar_list',
                'month'   => 'simplepco_calendar_month',
                'gallery' => 'simplepco_calendar_gallery',
            ];
            return $map[$view] ?? 'simplepco_calendar_list';
        }
        return $type_slug;
    }

    /**
     * Get a single shortcode type definition.
     *
     * @param string $type_slug Shortcode type slug.
     * @return array|null Type definition or null.
     */
    public static function get_shortcode_type($type_slug) {
        $types = self::get_shortcode_types();
        if (isset($types[$type_slug])) {
            return $types[$type_slug];
        }
        // Try resolving as a legacy type
        $resolved = self::resolve_legacy_type($type_slug);
        if ($resolved !== $type_slug && isset($types[$resolved])) {
            return $types[$resolved];
        }
        return null;
    }

    /**
     * Get available modules (unique list from shortcode types).
     *
     * @return array Module key => name pairs.
     */
    public static function get_available_modules() {
        $types = self::get_shortcode_types();
        $modules = [];

        foreach ($types as $type) {
            $modules[$type['module']] = $type['module_name'];
        }

        return $modules;
    }

    /**
     * Get shortcode types for a specific module.
     *
     * @param string $module_key Module key.
     * @return array Filtered shortcode types.
     */
    public static function get_types_for_module($module_key) {
        $types = self::get_shortcode_types();

        return array_filter($types, function($type) use ($module_key) {
            return $type['module'] === $module_key;
        });
    }

    // =========================================================================
    // Shortcode Configuration CRUD
    // =========================================================================

    /**
     * Get all shortcode configurations.
     *
     * @return array Associative array of shortcode configs keyed by ID.
     */
    public function get_shortcodes() {
        $shortcodes = get_option(self::OPTION_KEY, []);

        if (!is_array($shortcodes)) {
            $shortcodes = [];
        }

        return $shortcodes;
    }

    /**
     * Get a single shortcode configuration by ID.
     *
     * @param int $id Shortcode ID.
     * @return array|null Shortcode config or null if not found.
     */
    public function get_shortcode($id) {
        $shortcodes = $this->get_shortcodes();
        return isset($shortcodes[$id]) ? $shortcodes[$id] : null;
    }

    /**
     * Save a shortcode configuration.
     *
     * @param int   $id   Shortcode ID (0 for new).
     * @param array $data Shortcode settings.
     * @return int The saved shortcode ID.
     */
    public function save_shortcode($id, $data) {
        $shortcodes = $this->get_shortcodes();

        if ($id === 0) {
            $id = $this->get_next_id($shortcodes);
        }

        $shortcodes[$id] = $data;
        update_option(self::OPTION_KEY, $shortcodes);

        return $id;
    }

    /**
     * Delete a shortcode configuration.
     *
     * @param int $id Shortcode ID.
     * @return bool True if deleted, false if not found.
     */
    public function delete_shortcode($id) {
        $shortcodes = $this->get_shortcodes();

        if (!isset($shortcodes[$id])) {
            return false;
        }

        unset($shortcodes[$id]);
        update_option(self::OPTION_KEY, $shortcodes);

        return true;
    }

    /**
     * Get the next available shortcode ID.
     *
     * @param array $shortcodes Current shortcodes.
     * @return int Next available ID.
     */
    private function get_next_id($shortcodes) {
        if (empty($shortcodes)) {
            return 1;
        }
        return max(array_keys($shortcodes)) + 1;
    }

    /**
     * Get settings for a shortcode by ID (static, for use by module public classes).
     *
     * @param int    $id        Shortcode ID.
     * @param string $type_slug Fallback shortcode type.
     * @return array Shortcode settings.
     */
    public static function get_shortcode_settings($id, $type_slug) {
        $shortcodes = get_option(self::OPTION_KEY, []);

        if (isset($shortcodes[$id])) {
            return $shortcodes[$id];
        }

        // Fallback to defaults for the type
        $type = self::get_shortcode_type($type_slug);
        if ($type) {
            return $type['defaults'];
        }

        return [];
    }

    // =========================================================================
    // Admin Menu & Assets
    // =========================================================================

    /**
     * Add the Shortcodes menu item.
     */
    public function add_menu_page() {
        add_submenu_page(
            'simplepco-dashboard',
            __('Shortcodes', 'simplepco-online'),
            __('Shortcodes', 'simplepco-online'),
            'manage_options',
            'simplepco-shortcodes',
            [$this, 'render_page']
        );
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'simplepco-shortcodes') === false) {
            return;
        }

        wp_enqueue_style(
            'simplepco-shortcodes-admin',
            SIMPLEPCO_PLUGIN_URL . 'assets/admin/css/simplepco-shortcodes-admin.css',
            [],
            SIMPLEPCO_VERSION
        );
    }

    // =========================================================================
    // Page Rendering
    // =========================================================================

    /**
     * Render the page (routes to list or edit view).
     */
    public function render_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        switch ($action) {
            case 'edit':
            case 'new':
                $this->render_edit_page();
                break;
            default:
                $this->render_list_page();
                break;
        }
    }

    /**
     * Render the list view.
     */
    private function render_list_page() {
        $all_shortcodes = $this->get_shortcodes();
        $types = self::get_shortcode_types();
        $modules = self::get_available_modules();

        // Count by module
        $count_all = count($all_shortcodes);
        $counts_by_module = [];
        foreach ($modules as $mod_key => $mod_name) {
            $counts_by_module[$mod_key] = 0;
        }
        foreach ($all_shortcodes as $sc) {
            $sc_type_slug = self::resolve_legacy_type($sc['shortcode_type'] ?? '', $sc);
            $sc_type = isset($types[$sc_type_slug]) ? $types[$sc_type_slug] : null;
            if ($sc_type) {
                $mod_key = $sc_type['module'];
                if (isset($counts_by_module[$mod_key])) {
                    $counts_by_module[$mod_key]++;
                }
            }
        }

        // Apply module filter
        $filter = isset($_GET['module_filter']) ? sanitize_text_field($_GET['module_filter']) : '';
        $shortcodes = $all_shortcodes;

        if (!empty($filter) && isset($modules[$filter])) {
            $shortcodes = array_filter($all_shortcodes, function($sc) use ($types, $filter) {
                $sc_type_slug = self::resolve_legacy_type($sc['shortcode_type'] ?? '', $sc);
                $sc_type = isset($types[$sc_type_slug]) ? $types[$sc_type_slug] : null;
                return $sc_type && $sc_type['module'] === $filter;
            });
        }

        $data = [
            'shortcodes'       => $shortcodes,
            'types'            => $types,
            'modules'          => $modules,
            'count_all'        => $count_all,
            'counts_by_module' => $counts_by_module,
            'current_filter'   => $filter,
            'settings_saved'   => isset($_GET['settings_saved']),
            'deleted'          => isset($_GET['deleted']),
            'bulk_deleted'     => isset($_GET['bulk_deleted']) ? absint($_GET['bulk_deleted']) : 0,
            'page_url'         => admin_url('admin.php?page=simplepco-shortcodes'),
        ];

        $this->load_template('shortcodes-page', $data);
    }

    /**
     * Render the edit/new shortcode view.
     */
    private function render_edit_page() {
        $action = sanitize_text_field($_GET['action']);
        $types = self::get_shortcode_types();
        $page_url = admin_url('admin.php?page=simplepco-shortcodes');

        if ($action === 'new') {
            // Two-panel builder — no specific type needed up front
            $data = [
                'action'   => 'new',
                'types'    => $types,
                'modules'  => self::get_available_modules(),
                'page_url' => $page_url,
            ];
            $this->load_template('shortcodes-page', $data);
            return;
        }

        // Edit existing shortcode
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        $shortcode = $this->get_shortcode($id);
        if (!$shortcode) {
            wp_redirect($page_url);
            exit;
        }
        $type_slug = self::resolve_legacy_type($shortcode['shortcode_type'], $shortcode);
        $type_def = isset($types[$type_slug]) ? $types[$type_slug] : null;

        $data = [
            'action'    => 'edit',
            'id'        => $id,
            'shortcode' => $shortcode,
            'type_slug' => $type_slug,
            'type_def'  => $type_def,
            'types'     => $types,
            'page_url'  => $page_url,
        ];

        $this->load_template('shortcodes-page', $data);
    }

    // =========================================================================
    // Form Handlers
    // =========================================================================

    /**
     * Handle shortcode save (create or update).
     */
    public function handle_save_shortcode() {
        if (!isset($_POST['simplepco_save_module_shortcode'])) {
            return;
        }

        check_admin_referer('simplepco_save_module_shortcode');

        if (!current_user_can('manage_options')) {
            return;
        }

        $id = isset($_POST['shortcode_id']) ? absint($_POST['shortcode_id']) : 0;
        $type_slug = isset($_POST['shortcode_type']) ? sanitize_text_field($_POST['shortcode_type']) : '';

        $types = self::get_shortcode_types();
        // Resolve legacy type slugs (e.g. simplepco_calendar → simplepco_calendar_list)
        $type_slug = self::resolve_legacy_type($type_slug);
        if (!isset($types[$type_slug])) {
            wp_redirect(admin_url('admin.php?page=simplepco-shortcodes'));
            exit;
        }

        $type_def = $types[$type_slug];

        // Build sanitized settings starting with the type
        $settings = [
            'shortcode_type' => $type_slug,
            'description'    => isset($_POST['shortcode_description']) ? sanitize_text_field($_POST['shortcode_description']) : '',
        ];

        // Process module-specific fields
        foreach ($type_def['fields'] as $field) {
            $key = $field['key'];
            switch ($field['type']) {
                case 'checkbox':
                    $settings[$key] = isset($_POST[$key]);
                    break;
                case 'number':
                    $settings[$key] = isset($_POST[$key]) ? absint($_POST[$key]) : ($type_def['defaults'][$key] ?? 0);
                    break;
                case 'select':
                    $valid_options = array_keys($field['options']);
                    $settings[$key] = (isset($_POST[$key]) && in_array($_POST[$key], $valid_options))
                        ? sanitize_text_field($_POST[$key])
                        : ($type_def['defaults'][$key] ?? '');
                    break;
                case 'text':
                default:
                    $settings[$key] = isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : ($type_def['defaults'][$key] ?? '');
                    break;
            }
        }

        // Merge any type defaults not covered by form fields (e.g. view for calendar types)
        foreach ($type_def['defaults'] as $key => $default_value) {
            if (!isset($settings[$key])) {
                $settings[$key] = $default_value;
            }
        }

        // Process common styling fields
        $settings['custom_class']     = isset($_POST['custom_class']) ? sanitize_html_class($_POST['custom_class']) : '';
        $settings['primary_color']    = isset($_POST['primary_color']) ? sanitize_hex_color($_POST['primary_color']) : '#333333';
        $settings['text_color']       = isset($_POST['text_color']) ? sanitize_hex_color($_POST['text_color']) : '#333333';
        $settings['background_color'] = isset($_POST['background_color']) ? sanitize_hex_color($_POST['background_color']) : '#ffffff';
        $settings['border_radius']    = isset($_POST['border_radius']) ? absint($_POST['border_radius']) : 8;

        $this->save_shortcode($id, $settings);

        wp_redirect(admin_url('admin.php?page=simplepco-shortcodes&settings_saved=1'));
        exit;
    }

    /**
     * Handle shortcode deletion.
     */
    public function handle_delete_shortcode() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'delete') {
            return;
        }

        if (!isset($_GET['page']) || $_GET['page'] !== 'simplepco-shortcodes') {
            return;
        }

        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if ($id === 0) {
            return;
        }

        check_admin_referer('simplepco_delete_module_shortcode_' . $id);

        if (!current_user_can('manage_options')) {
            return;
        }

        $this->delete_shortcode($id);

        wp_redirect(admin_url('admin.php?page=simplepco-shortcodes&deleted=1'));
        exit;
    }

    /**
     * Handle bulk actions.
     */
    public function handle_bulk_action() {
        if (!isset($_POST['simplepco_bulk_module_shortcodes'])) {
            return;
        }

        check_admin_referer('simplepco_bulk_module_shortcodes');

        if (!current_user_can('manage_options')) {
            return;
        }

        $action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $ids = isset($_POST['shortcode_ids']) ? array_map('absint', $_POST['shortcode_ids']) : [];

        if ($action !== 'trash' || empty($ids)) {
            wp_redirect(admin_url('admin.php?page=simplepco-shortcodes'));
            exit;
        }

        $deleted = 0;
        foreach ($ids as $id) {
            if ($this->delete_shortcode($id)) {
                $deleted++;
            }
        }

        wp_redirect(admin_url('admin.php?page=simplepco-shortcodes&bulk_deleted=' . $deleted));
        exit;
    }

    /**
     * Load a template file.
     */
    private function load_template($template_name, $data = []) {
        extract($data);
        $template_path = SIMPLEPCO_PLUGIN_DIR . 'templates/admin/' . $template_name . '.php';

        if (file_exists($template_path)) {
            include $template_path;
        }
    }
}
