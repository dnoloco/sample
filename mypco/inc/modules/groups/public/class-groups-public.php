<?php
/**
 * Groups Public Component
 *
 * Handles public-facing functionality for the Groups module:
 * - Groups shortcode [pco_groups]
 * - Groups display
 */

class MyPCO_Groups_Public {

    private $loader;
    private $api_model;

    public function __construct($loader, $api_model) {
        $this->loader = $loader;
        $this->api_model = $api_model;
    }

    /**
     * Initialize public functionality.
     */
    public function init() {
        // Register shortcodes
        $this->loader->add_action('init', $this, 'register_shortcodes');
        
        // Enqueue public assets
        $this->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_public_assets');
    }

    /**
     * Register shortcodes.
     */
    public function register_shortcodes() {
        add_shortcode('pco_groups', [$this, 'render_groups_shortcode']);
        add_shortcode('mypco_groups', [$this, 'render_groups_shortcode']);
    }

    /**
     * Enqueue public assets.
     */
    public function enqueue_public_assets() {
        // Only enqueue if shortcode is present
        global $post;
        if (!is_a($post, 'WP_Post')) {
            return;
        }

        if (has_shortcode($post->post_content, 'pco_groups') || has_shortcode($post->post_content, 'mypco_groups')) {
            wp_enqueue_style(
                'mypco-groups-public',
                MYPCO_PLUGIN_URL . 'modules/groups/public/assets/css/groups-public.css',
                [],
                MYPCO_VERSION
            );
        }
    }

    /**
     * Render groups shortcode.
     * [pco_groups count="10" campus="Main Campus"]
     */
    public function render_groups_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts([
            'id'     => 0,
            'count'  => '',
            'campus' => '',
        ], $atts);

        // Load centralized shortcode settings when id is provided
        $id = absint($atts['id']);
        if ($id > 0) {
            require_once MYPCO_PLUGIN_DIR . 'inc/core/class-mypco-shortcodes-admin.php';
            $settings = MyPCO_Shortcodes_Admin::get_shortcode_settings($id, 'mypco_groups');
        } else {
            $settings = [];
        }

        // Allow shortcode attributes to override stored settings, then fall back to defaults
        $atts['count']  = !empty($atts['count']) ? (int) $atts['count'] : ($settings['count'] ?? 10);
        $atts['campus'] = !empty($atts['campus']) ? $atts['campus'] : ($settings['campus'] ?? null);

        // Fetch groups data
        $groups_data = $this->fetch_groups($atts);

        if (isset($groups_data['error'])) {
            return '<div class="mypco-groups-error"><p>' . esc_html($groups_data['error']) . '</p></div>';
        }

        // Prepare template data
        $template_data = [
            'groups' => $groups_data['groups'],
            'campus_map' => $groups_data['campus_map'],
            'group_type_map' => $groups_data['group_type_map'],
            'atts' => $atts
        ];

        // Load template
        ob_start();
        $this->load_template('groups-list', $template_data);
        return ob_get_clean();
    }

    /**
     * Fetch groups from PCO API.
     */
    private function fetch_groups($atts) {
        $params = [
            'per_page' => intval($atts['count']),
            'include' => 'group_type,campus'
        ];

        // Build cache key
        $cache_key = 'pco_groups_v1_' . md5(serialize($atts));

        // Try to get cached data
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Fetch from API
        $response = $this->api_model->get_data('groups', '/v2/groups', $params);

        if (isset($response['error'])) {
            return ['error' => 'Groups Error: ' . $response['error']];
        }

        $groups = $response['data'] ?? [];
        $included = $response['included'] ?? [];

        // Build maps for included resources
        $campus_map = [];
        $group_type_map = [];

        foreach ($included as $item) {
            if ($item['type'] === 'Campus') {
                $campus_map[$item['id']] = $item['attributes'];
            } elseif ($item['type'] === 'GroupType') {
                $group_type_map[$item['id']] = $item['attributes'];
            }
        }

        $result = [
            'groups' => $groups,
            'campus_map' => $campus_map,
            'group_type_map' => $group_type_map
        ];

        // Cache for 1 hour
        set_transient($cache_key, $result, HOUR_IN_SECONDS);

        return $result;
    }

    /**
     * Load a template file.
     */
    private function load_template($template_name, $data = []) {
        extract($data);
        $template_path = MYPCO_PLUGIN_DIR . 'templates/groups/public/' . $template_name . '.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<p>Template not found: ' . esc_html($template_name) . '</p>';
        }
    }
}
