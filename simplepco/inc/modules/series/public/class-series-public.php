<?php
/**
 * Series Public Component
 *
 * Handles all frontend/public functionality for the Series module.
 * Provides shortcodes for displaying messages in various formats.
 *
 * Uses WordPress CPTs (simplepco_message, simplepco_speaker) and
 * taxonomies (simplepco_series, simplepco_service_type) with post meta.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SimplePCO_Series_Public {

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
        add_shortcode('simplepco_messages', [$this, 'render_messages_shortcode']);
        add_shortcode('simplepco_sermons', [$this, 'render_messages_shortcode']); // backward compat
        add_shortcode('simplepco_series_list', [$this, 'render_series_archive_shortcode']);
        $this->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_public_assets');
    }

    /**
     * Enqueue public-facing assets.
     */
    public function enqueue_public_assets() {
        // Always load on message/speaker CPT pages and series taxonomy archives
        $is_series_page = is_post_type_archive('simplepco_message')
            || is_singular('simplepco_message')
            || is_singular('simplepco_speaker')
            || is_tax('simplepco_series')
            || is_tax('simplepco_service_type');

        if (!$is_series_page) {
            // Check for shortcodes on regular pages/posts
            global $post;
            if (!is_a($post, 'WP_Post')) {
                return;
            }

            $shortcodes = ['simplepco_messages', 'simplepco_sermons', 'simplepco_series_list'];
            $has_shortcode = false;
            foreach ($shortcodes as $sc) {
                if (has_shortcode($post->post_content, $sc)) {
                    $has_shortcode = true;
                    break;
                }
            }

            if (!$has_shortcode) {
                return;
            }
        }

        wp_enqueue_style(
            'simplepco-series-public',
            SIMPLEPCO_PLUGIN_URL . 'inc/modules/series/public/assets/css/series.css',
            [],
            SIMPLEPCO_VERSION
        );

        wp_enqueue_script(
            'simplepco-series-public',
            SIMPLEPCO_PLUGIN_URL . 'inc/modules/series/public/assets/js/series.js',
            ['jquery'],
            SIMPLEPCO_VERSION,
            true
        );
    }

    /**
     * Get the URL for the default placeholder image.
     */
    public function get_placeholder_url() {
        return SIMPLEPCO_PLUGIN_URL . 'inc/modules/series/public/assets/images/series-placeholder.svg';
    }

    /**
     * Render the messages shortcode.
     *
     * If ?simplepco_message=ID is present, renders the single message detail page.
     * Otherwise, renders the gallery/list of message cards.
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_messages_shortcode($atts) {
        $atts = shortcode_atts([
            'id'      => 0,
            'count'   => 12,
            'series'  => '',
            'speaker' => '',
            'topic'   => '',
            'view'    => 'gallery',
            'orderby' => 'date',
            'order'   => 'DESC',
            'paged'   => '',
        ], $atts, 'simplepco_messages');

        // Load centralized shortcode settings when id is provided
        $id = absint($atts['id']);
        if ($id > 0) {
            require_once SIMPLEPCO_PLUGIN_DIR . 'inc/core/class-simplepco-shortcodes-admin.php';
            $settings = SimplePCO_Shortcodes_Admin::get_shortcode_settings($id, 'simplepco_messages_list');
        } else {
            $settings = [];
        }

        // Apply settings with fallback to shortcode attributes
        $count = !empty($settings['count']) ? (int) $settings['count'] : (int) $atts['count'];
        $view = !empty($settings['view']) ? $settings['view'] : $atts['view'];
        $order = strtoupper($atts['order']) === 'ASC' ? 'ASC' : 'DESC';

        // Check for single message view
        $single_id = isset($_GET['simplepco_message']) ? absint($_GET['simplepco_message']) : 0;
        if ($single_id > 0) {
            return $this->render_single_message($single_id);
        }

        // Build WP_Query args
        $paged = !empty($atts['paged']) ? absint($atts['paged']) : max(1, get_query_var('paged'));
        $query_args = [
            'post_type'      => 'simplepco_message',
            'posts_per_page' => min($count, 100),
            'paged'          => $paged,
            'post_status'    => 'publish',
        ];

        // Order
        if ($atts['orderby'] === 'title') {
            $query_args['orderby'] = 'title';
        } else {
            $query_args['meta_key'] = '_simplepco_message_date';
            $query_args['orderby']  = 'meta_value';
        }
        $query_args['order'] = $order;

        // Filter by series taxonomy
        if (!empty($atts['series'])) {
            $query_args['tax_query'] = [[
                'taxonomy' => 'simplepco_series',
                'field'    => is_numeric($atts['series']) ? 'term_id' : 'name',
                'terms'    => $atts['series'],
            ]];
        }

        // Filter by speaker (post meta → speaker post)
        if (!empty($atts['speaker'])) {
            if (is_numeric($atts['speaker'])) {
                $query_args['meta_query'][] = [
                    'key'   => '_simplepco_speaker_id',
                    'value' => absint($atts['speaker']),
                ];
            } else {
                // Lookup speaker post by name
                $speaker_posts = get_posts([
                    'post_type'      => 'simplepco_speaker',
                    'posts_per_page' => 1,
                    'title'          => sanitize_text_field($atts['speaker']),
                    'post_status'    => 'publish',
                    'fields'         => 'ids',
                ]);
                if (!empty($speaker_posts)) {
                    $query_args['meta_query'][] = [
                        'key'   => '_simplepco_speaker_id',
                        'value' => $speaker_posts[0],
                    ];
                }
            }
        }

        // Filter by topic taxonomy (service_type used as topic)
        if (!empty($atts['topic'])) {
            $topic_query = [
                'taxonomy' => 'simplepco_service_type',
                'field'    => is_numeric($atts['topic']) ? 'term_id' : 'name',
                'terms'    => $atts['topic'],
            ];
            if (!empty($query_args['tax_query'])) {
                $query_args['tax_query'][] = $topic_query;
            } else {
                $query_args['tax_query'] = [$topic_query];
            }
        }

        /**
         * Filter the messages query args before execution.
         *
         * @param array $query_args WP_Query arguments.
         * @param array $atts       Shortcode attributes.
         */
        $query_args = apply_filters('simplepco/messages/query_args', $query_args, $atts);

        $messages_query = new WP_Query($query_args);

        if (!$messages_query->have_posts()) {
            wp_reset_postdata();
            return '<div class="simplepco-messages-empty"><p>' . esc_html__('No messages found.', 'simplepco') . '</p></div>';
        }

        $template = ($view === 'list') ? 'messages-list' : 'messages-gallery';

        $output = $this->load_template($template, [
            'messages_query'  => $messages_query,
            'view'            => $view,
            'atts'            => $atts,
            'placeholder_url' => $this->get_placeholder_url(),
        ]);

        wp_reset_postdata();

        return $output;
    }

    /**
     * Render the series archive shortcode.
     *
     * Displays all series as cards with artwork and message counts.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_series_archive_shortcode($atts) {
        $atts = shortcode_atts([
            'count'   => 12,
            'orderby' => 'name',
            'order'   => 'ASC',
        ], $atts, 'simplepco_series_list');

        // Check for single series view
        $series_slug = isset($_GET['simplepco_series']) ? sanitize_text_field($_GET['simplepco_series']) : '';
        if (!empty($series_slug)) {
            return $this->render_series_single($series_slug);
        }

        // Check for single speaker view
        $speaker_id = isset($_GET['simplepco_speaker']) ? absint($_GET['simplepco_speaker']) : 0;
        if ($speaker_id > 0) {
            return $this->render_speaker_single($speaker_id);
        }

        $terms = get_terms([
            'taxonomy'   => 'simplepco_series',
            'number'     => min(absint($atts['count']), 100),
            'orderby'    => $atts['orderby'],
            'order'      => strtoupper($atts['order']) === 'DESC' ? 'DESC' : 'ASC',
            'hide_empty' => true,
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            return '<div class="simplepco-messages-empty"><p>' . esc_html__('No series found.', 'simplepco') . '</p></div>';
        }

        return $this->load_template('series-archive', [
            'terms'           => $terms,
            'atts'            => $atts,
            'placeholder_url' => $this->get_placeholder_url(),
        ]);
    }

    /**
     * Render a single message detail page.
     *
     * @param int $message_id Message post ID.
     * @return string HTML output.
     */
    private function render_single_message($message_id) {
        $post = get_post($message_id);

        if (!$post || $post->post_type !== 'simplepco_message' || $post->post_status !== 'publish') {
            return '<div class="simplepco-messages-empty"><p>' . esc_html__('Message not found.', 'simplepco') . '</p></div>';
        }

        return $this->load_template('message-single', [
            'message'         => $post,
            'placeholder_url' => $this->get_placeholder_url(),
        ]);
    }

    /**
     * Render messages for a single series.
     *
     * @param string $series_slug Series term slug.
     * @return string HTML output.
     */
    private function render_series_single($series_slug) {
        $term = get_term_by('slug', $series_slug, 'simplepco_series');

        if (!$term) {
            return '<div class="simplepco-messages-empty"><p>' . esc_html__('Series not found.', 'simplepco') . '</p></div>';
        }

        $messages_query = new WP_Query([
            'post_type'      => 'simplepco_message',
            'posts_per_page' => 100,
            'post_status'    => 'publish',
            'meta_key'       => '_simplepco_message_date',
            'orderby'        => 'meta_value',
            'order'          => 'DESC',
            'tax_query'      => [[
                'taxonomy' => 'simplepco_series',
                'field'    => 'term_id',
                'terms'    => $term->term_id,
            ]],
        ]);

        $output = $this->load_template('series-single', [
            'term'            => $term,
            'messages_query'  => $messages_query,
            'placeholder_url' => $this->get_placeholder_url(),
        ]);

        wp_reset_postdata();
        return $output;
    }

    /**
     * Render a speaker detail page with their messages.
     *
     * @param int $speaker_id Speaker post ID.
     * @return string HTML output.
     */
    private function render_speaker_single($speaker_id) {
        $speaker = get_post($speaker_id);

        if (!$speaker || $speaker->post_type !== 'simplepco_speaker' || $speaker->post_status !== 'publish') {
            return '<div class="simplepco-messages-empty"><p>' . esc_html__('Speaker not found.', 'simplepco') . '</p></div>';
        }

        $messages_query = new WP_Query([
            'post_type'      => 'simplepco_message',
            'posts_per_page' => 100,
            'post_status'    => 'publish',
            'meta_key'       => '_simplepco_message_date',
            'orderby'        => 'meta_value',
            'order'          => 'DESC',
            'meta_query'     => [[
                'key'   => '_simplepco_speaker_id',
                'value' => $speaker_id,
            ]],
        ]);

        $output = $this->load_template('speaker-single', [
            'speaker'         => $speaker,
            'messages_query'  => $messages_query,
            'placeholder_url' => $this->get_placeholder_url(),
        ]);

        wp_reset_postdata();
        return $output;
    }

    /**
     * Load a template file and return output.
     */
    private function load_template($template_name, $data = []) {
        extract($data);

        ob_start();

        $template_path = SIMPLEPCO_PLUGIN_DIR . 'templates/series/public/' . $template_name . '.php';

        /**
         * Filter the template path for series templates.
         *
         * Allows themes to override plugin templates by providing their own version.
         *
         * @param string $template_path Full path to the template file.
         * @param string $template_name Template name (without .php extension).
         * @param array  $data          Template data.
         */
        $template_path = apply_filters('simplepco/series/template_path', $template_path, $template_name, $data);

        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<!-- Template not found: ' . esc_html($template_name) . ' -->';
        }

        return ob_get_clean();
    }
}
