<?php
/**
 * Shortcode Configuration Admin
 *
 * Provides centralized shortcode documentation and management for all modules.
 * This admin page shows available shortcodes, their parameters, and usage examples.
 */

class SimplePCO_Shortcodes_Admin {

    private $loader;

    public function __construct($loader) {
        $this->loader = $loader;
    }

    /**
     * Initialize admin functionality.
     */
    public function init() {
        // Add admin page
        $this->loader->add_action('admin_menu', $this, 'add_admin_page');
        
        // Enqueue admin assets
        $this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');
    }

    /**
     * Add admin menu page.
     */
    public function add_admin_page() {
        add_submenu_page(
            'simplepco-settings',
            __('Shortcodes', 'simplepco-online'),
            __('Shortcodes', 'simplepco-online'),
            'edit_posts',
            'simplepco-shortcodes',
            [$this, 'render_shortcodes_page']
        );
    }

    /**
     * Enqueue admin-specific assets.
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our page
        if ($hook !== 'simplepco-settings_page_simplepco-shortcodes') {
            return;
        }

        wp_enqueue_style(
            'simplepco-shortcodes-admin',
            SIMPLEPCO_PLUGIN_URL . 'assets/admin/css/shortcodes-admin.css',
            [],
            SIMPLEPCO_VERSION
        );

        wp_enqueue_script(
            'simplepco-shortcodes-admin',
            SIMPLEPCO_PLUGIN_URL . 'assets/admin/js/shortcodes-admin.js',
            ['jquery'],
            SIMPLEPCO_VERSION,
            true
        );
    }

    /**
     * Render shortcodes configuration page.
     */
    public function render_shortcodes_page() {
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'simplepco-online'));
        }

        // Get all available shortcodes
        $shortcodes = $this->get_available_shortcodes();
        
        // Scan for shortcode usage
        $usage = $this->scan_shortcode_usage();

        $template_data = [
            'shortcodes' => $shortcodes,
            'usage' => $usage
        ];

        $this->load_template('shortcodes-page', $template_data);
    }

    /**
     * Get all available shortcodes from modules.
     */
    private function get_available_shortcodes() {
        return [
            'calendar' => [
                'module' => 'Calendar',
                'shortcodes' => [
                    [
                        'tag' => 'pco_calendar',
                        'alt_tag' => 'simplepco_calendar',
                        'description' => 'Display upcoming events from Planning Center Calendar',
                        'parameters' => [
                            ['name' => 'view', 'type' => 'string', 'default' => 'list', 'description' => 'Display mode: list, month, or gallery'],
                            ['name' => 'count', 'type' => 'int', 'default' => '20', 'description' => 'Number of events to display']
                        ],
                        'examples' => [
                            '[pco_calendar]',
                            '[pco_calendar view="month"]',
                            '[pco_calendar view="gallery" count="12"]'
                        ]
                    ]
                ]
            ],
            'groups' => [
                'module' => 'Groups',
                'shortcodes' => [
                    [
                        'tag' => 'pco_groups',
                        'alt_tag' => 'simplepco_groups',
                        'description' => 'Display groups from Planning Center Groups',
                        'parameters' => [
                            ['name' => 'count', 'type' => 'int', 'default' => '10', 'description' => 'Number of groups to display'],
                            ['name' => 'campus', 'type' => 'string', 'default' => 'all', 'description' => 'Filter by campus name']
                        ],
                        'examples' => [
                            '[pco_groups]',
                            '[pco_groups count="20"]',
                            '[pco_groups campus="Main Campus"]'
                        ]
                    ]
                ]
            ],
            'signups' => [
                'module' => 'Signups',
                'shortcodes' => [
                    [
                        'tag' => 'simplepco_payment_form',
                        'alt_tag' => null,
                        'description' => 'Display payment form for event registration',
                        'parameters' => [
                            ['name' => 'registration_id', 'type' => 'int', 'default' => 'required', 'description' => 'Registration ID to process payment for']
                        ],
                        'examples' => [
                            '[simplepco_payment_form registration_id="123"]'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Scan posts/pages for shortcode usage.
     */
    private function scan_shortcode_usage() {
        global $wpdb;

        $shortcode_tags = [
            'pco_calendar',
            'simplepco_calendar',
            'pco_groups',
            'simplepco_groups',
            'simplepco_payment_form'
        ];

        $usage = [];

        // Query all posts and pages
        $posts = $wpdb->get_results("
            SELECT ID, post_title, post_content, post_type 
            FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND post_type IN ('post', 'page')
        ");

        foreach ($posts as $post) {
            $found_shortcodes = [];
            
            foreach ($shortcode_tags as $tag) {
                if (has_shortcode($post->post_content, $tag)) {
                    $found_shortcodes[] = $tag;
                }
            }

            if (!empty($found_shortcodes)) {
                $usage[] = [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'type' => $post->post_type,
                    'shortcodes' => $found_shortcodes,
                    'edit_link' => get_edit_post_link($post->ID),
                    'view_link' => get_permalink($post->ID)
                ];
            }
        }

        return $usage;
    }

    /**
     * Load a template file.
     */
    private function load_template($template_name, $data = []) {
        extract($data);
        $template_path = SIMPLEPCO_PLUGIN_DIR . 'templates/admin/' . $template_name . '.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap"><h1>Error</h1><p>Template not found: ' . esc_html($template_name) . '</p></div>';
        }
    }
}
