<?php
/**
 * Series Module - Main Orchestrator
 *
 * Coordinates the admin and public components of the Series module.
 * Provides message management with series, speakers, topics, and media links.
 */

require_once MYPCO_PLUGIN_DIR . 'includes/class-mypco-module-base.php';

class MyPCO_Series_Module extends MyPCO_Module_Base {

    protected $module_key = 'series';
    protected $module_name = 'Series';
    protected $module_description = 'Manage and display message archives with series, speakers, topics, and media.';

    /**
     * Module tier: freemium (basic display free, customization premium)
     */
    protected $tier = 'freemium';
    protected $requires_license = false;
    protected $min_license_tier = 'starter';

    /**
     * Features available in this module
     *
     * Free: Basic message management and default shortcode display
     * Premium: Featured message widget, custom series display, advanced filtering
     */
    protected $features = [
        'free' => [
            'manage_messages',
            'manage_speakers',
            'manage_series',
            'manage_topics',
            'basic_shortcode',
            'message_list_view'
        ],
        'premium' => [
            'featured_message',
            'series_display',
            'custom_templates',
            'advanced_filtering',
            'custom_css'
        ]
    ];

    /**
     * Admin component instance
     */
    private $admin;

    /**
     * Public component instance
     */
    private $public;

    /**
     * Return customisable display names with defaults.
     *
     * Stored in the `mypco_series_labels` option. Empty values fall back to
     * the built-in defaults so users only need to fill in what they change.
     */
    public static function get_custom_labels() {
        $defaults = [
            'message_singular'      => 'Message',
            'message_plural'        => 'Messages',
            'speaker_singular'      => 'Speaker',
            'speaker_plural'        => 'Speakers',
            'series_singular'       => 'Series',
            'series_plural'         => 'Series',
            'service_type_singular' => 'Service Type',
            'service_type_plural'   => 'Service Types',
        ];

        $saved = get_option('mypco_series_labels', []);
        if (!is_array($saved)) {
            $saved = [];
        }

        $labels = [];
        foreach ($defaults as $key => $default) {
            $labels[$key] = (!empty($saved[$key])) ? $saved[$key] : $default;
        }

        return $labels;
    }

    /**
     * Initialize the Series module.
     */
    public function init() {
        // Register custom post types and taxonomies (must run on every request)
        $this->loader->add_action('init', $this, 'register_post_types');
        $this->loader->add_action('init', $this, 'register_taxonomies');

        // Customize editor title placeholders
        $this->loader->add_filter('enter_title_here', $this, 'custom_title_placeholder', 10, 2);

        // Load and initialize admin component
        if (is_admin()) {
            $this->load_admin_component();
        }

        // Load and initialize public component (always loaded for shortcodes)
        $this->load_public_component();
    }

    /**
     * Register the Message and Speaker custom post types.
     */
    public function register_post_types() {
        $names = self::get_custom_labels();

        // Message CPT
        $ms = $names['message_singular'];
        $mp = $names['message_plural'];

        register_post_type('mypco_message', [
            'labels' => [
                'name'               => $mp,
                'singular_name'      => $ms,
                'menu_name'          => $mp,
                'name_admin_bar'     => $ms,
                'add_new'            => sprintf(__('Add %s', 'mypco-online'), $ms),
                'add_new_item'       => sprintf(__('Add %s', 'mypco-online'), $ms),
                'new_item'           => $ms,
                'edit_item'          => sprintf(__('Edit %s', 'mypco-online'), $ms),
                'view_item'          => sprintf(__('View %s', 'mypco-online'), $ms),
                'all_items'          => sprintf(__('All %s', 'mypco-online'), $mp),
                'search_items'       => sprintf(__('Search %s', 'mypco-online'), $mp),
                'not_found'          => sprintf(__('No %s found.', 'mypco-online'), strtolower($mp)),
                'not_found_in_trash' => sprintf(__('No %s found in Trash.', 'mypco-online'), strtolower($mp)),
            ],
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => ['slug' => 'messages'],
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 26,
            'menu_icon'          => 'dashicons-microphone',
            'supports'           => ['title', 'editor', 'thumbnail'],
            'show_in_rest'       => true,
        ]);

        // Speaker CPT
        $ss = $names['speaker_singular'];
        $sp = $names['speaker_plural'];

        register_post_type('mypco_speaker', [
            'labels' => [
                'name'               => $sp,
                'singular_name'      => $ss,
                'menu_name'          => $sp,
                'name_admin_bar'     => $ss,
                'add_new'            => sprintf(__('Add %s', 'mypco-online'), $ss),
                'add_new_item'       => sprintf(__('Add %s', 'mypco-online'), $ss),
                'new_item'           => $ss,
                'edit_item'          => sprintf(__('Edit %s', 'mypco-online'), $ss),
                'view_item'          => sprintf(__('View %s', 'mypco-online'), $ss),
                'all_items'          => $sp,
                'search_items'       => sprintf(__('Search %s', 'mypco-online'), $sp),
                'not_found'          => sprintf(__('No %s found.', 'mypco-online'), strtolower($sp)),
                'not_found_in_trash' => sprintf(__('No %s found in Trash.', 'mypco-online'), strtolower($sp)),
            ],
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => 'edit.php?post_type=mypco_message',
            'query_var'          => true,
            'rewrite'            => ['slug' => 'speakers'],
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'supports'           => ['title', 'editor', 'thumbnail'],
            'show_in_rest'       => true,
        ]);
    }

    /**
     * Register the Series and Service Type taxonomies.
     *
     * Speaker assignment uses a post-meta relationship to the mypco_speaker CPT
     * rather than a taxonomy (see the Speaker meta box in class-series-admin.php).
     */
    public function register_taxonomies() {
        $names = self::get_custom_labels();

        // Series taxonomy
        $srs = $names['series_singular'];
        $srp = $names['series_plural'];

        register_taxonomy('mypco_series', ['mypco_message'], [
            'labels' => [
                'name'          => $srp,
                'singular_name' => $srs,
                'search_items'  => sprintf(__('Search %s', 'mypco-online'), $srp),
                'all_items'     => sprintf(__('All %s', 'mypco-online'), $srp),
                'edit_item'     => sprintf(__('Edit %s', 'mypco-online'), $srs),
                'update_item'   => sprintf(__('Update %s', 'mypco-online'), $srs),
                'add_new_item'  => sprintf(__('Add %s', 'mypco-online'), $srs),
                'new_item_name' => sprintf(__('%s Name', 'mypco-online'), $srs),
                'menu_name'     => $srp,
                'not_found'     => sprintf(__('No %s found.', 'mypco-online'), strtolower($srp)),
            ],
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => ['slug' => 'series'],
        ]);

        // Service Type taxonomy
        $sts = $names['service_type_singular'];
        $stp = $names['service_type_plural'];

        register_taxonomy('mypco_service_type', ['mypco_message'], [
            'labels' => [
                'name'          => $stp,
                'singular_name' => $sts,
                'search_items'  => sprintf(__('Search %s', 'mypco-online'), $stp),
                'all_items'     => sprintf(__('All %s', 'mypco-online'), $stp),
                'edit_item'     => sprintf(__('Edit %s', 'mypco-online'), $sts),
                'update_item'   => sprintf(__('Update %s', 'mypco-online'), $sts),
                'add_new_item'  => sprintf(__('Add %s', 'mypco-online'), $sts),
                'new_item_name' => sprintf(__('%s Name', 'mypco-online'), $sts),
                'menu_name'     => $stp,
                'not_found'     => sprintf(__('No %s found.', 'mypco-online'), strtolower($stp)),
            ],
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => ['slug' => 'service-types'],
        ]);

        // Flush rewrite rules once after activation so new CPT/taxonomy
        // URLs are recognised (the activator sets this transient).
        if (get_transient('mypco_flush_rewrite_rules')) {
            delete_transient('mypco_flush_rewrite_rules');
            flush_rewrite_rules();
        }

        // Also flush when CPT/taxonomy registrations change. Bump the
        // version string whenever slugs or post types are modified.
        $rewrite_version = 'mypco_rewrite_v4';
        if (get_option('mypco_rewrite_version') !== $rewrite_version) {
            flush_rewrite_rules();
            update_option('mypco_rewrite_version', $rewrite_version);
        }
    }

    /**
     * Customize the "Add title" placeholder in the post editor.
     *
     * @param string  $placeholder Default placeholder text.
     * @param WP_Post $post        The current post object.
     * @return string
     */
    public function custom_title_placeholder($placeholder, $post) {
        if ($post->post_type === 'mypco_message') {
            $names = self::get_custom_labels();
            return sprintf(__('Enter %s title here', 'mypco-online'), $names['message_singular']);
        }

        if ($post->post_type === 'mypco_speaker') {
            $names = self::get_custom_labels();
            return sprintf(__('Enter %s name here', 'mypco-online'), $names['speaker_singular']);
        }

        return $placeholder;
    }

    /**
     * Load the admin component.
     */
    private function load_admin_component() {
        require_once $this->get_module_path('admin/class-series-admin.php');
        $this->admin = new MyPCO_Series_Admin($this->loader, $this->api_model);
        $this->admin->init();
    }

    /**
     * Load the public component.
     */
    private function load_public_component() {
        require_once $this->get_module_path('public/class-series-public.php');
        $this->public = new MyPCO_Series_Public($this->loader, $this->api_model);
        $this->public->init();
    }

    /**
     * Get path within this module.
     */
    private function get_module_path($relative_path) {
        return MYPCO_PLUGIN_DIR . 'modules/series/' . $relative_path;
    }
}
