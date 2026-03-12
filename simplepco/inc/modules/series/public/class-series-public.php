<?php
/**
 * Series Public Component
 *
 * Handles all frontend/public functionality for the Series module.
 * Provides shortcodes for displaying messages in various formats.
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
        $this->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_public_assets');
    }

    /**
     * Enqueue public-facing assets.
     */
    public function enqueue_public_assets() {
        global $post;

        if (!is_a($post, 'WP_Post')) {
            return;
        }

        if (!has_shortcode($post->post_content, 'simplepco_messages') && !has_shortcode($post->post_content, 'simplepco_sermons')) {
            return;
        }

        wp_enqueue_style(
            'simplepco-series-public',
            SIMPLEPCO_PLUGIN_URL . 'modules/series/public/assets/css/series.css',
            [],
            SIMPLEPCO_VERSION
        );

        wp_enqueue_script(
            'simplepco-series-public',
            SIMPLEPCO_PLUGIN_URL . 'modules/series/public/assets/js/series.js',
            ['jquery'],
            SIMPLEPCO_VERSION,
            true
        );
    }

    /**
     * Get the URL for the default placeholder image.
     */
    public function get_placeholder_url() {
        return SIMPLEPCO_PLUGIN_URL . 'modules/series/public/assets/images/series-placeholder.svg';
    }

    /**
     * Render the messages shortcode.
     *
     * If ?simplepco_message=ID is present, renders the single message detail page.
     * Otherwise, renders the gallery of message cards.
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

        // Fetch messages from database
        $messages = $this->fetch_messages([
            'count'   => $count,
            'series'  => $atts['series'],
            'speaker' => $atts['speaker'],
            'topic'   => $atts['topic'],
            'orderby' => $atts['orderby'],
            'order'   => $order,
        ]);

        if (empty($messages)) {
            return '<div class="simplepco-messages-empty"><p>' . esc_html__('No messages found.', 'simplepco') . '</p></div>';
        }

        $template = ($view === 'list') ? 'messages-list' : 'messages-gallery';

        return $this->load_template($template, [
            'messages'        => $messages,
            'view'            => $view,
            'atts'            => $atts,
            'placeholder_url' => $this->get_placeholder_url(),
        ]);
    }

    /**
     * Render a single message detail page.
     *
     * @param int $message_id Message ID.
     * @return string HTML output.
     */
    private function render_single_message($message_id) {
        $message = $this->fetch_single_message($message_id);

        if (!$message) {
            return '<div class="simplepco-messages-empty"><p>' . esc_html__('Message not found.', 'simplepco') . '</p></div>';
        }

        return $this->load_template('message-single', [
            'message'         => $message,
            'placeholder_url' => $this->get_placeholder_url(),
        ]);
    }

    /**
     * Fetch a single message by ID with all joined data.
     *
     * @param int $message_id Message ID.
     * @return object|null Message object or null.
     */
    private function fetch_single_message($message_id) {
        global $wpdb;

        $table_messages = $wpdb->prefix . 'simplepco_messages';
        $table_speakers = $wpdb->prefix . 'simplepco_speakers';
        $table_series = $wpdb->prefix . 'simplepco_series';
        $table_topics = $wpdb->prefix . 'simplepco_topics';

        $query = "SELECT m.*,
                    sp.name AS speaker_name,
                    sp.title AS speaker_title,
                    sp.image_url AS speaker_image_url,
                    sr.title AS series_title,
                    sr.image_url AS series_image_url,
                    t.name AS topic_name
                  FROM {$table_messages} m
                  LEFT JOIN {$table_speakers} sp ON m.speaker_id = sp.id
                  LEFT JOIN {$table_series} sr ON m.series_id = sr.id
                  LEFT JOIN {$table_topics} t ON m.topic_id = t.id
                  WHERE m.id = %d";

        return $wpdb->get_row($wpdb->prepare($query, $message_id));
    }

    /**
     * Fetch messages from the database.
     *
     * @param array $args Query arguments.
     * @return array Array of message objects.
     */
    private function fetch_messages($args) {
        global $wpdb;

        $table_messages = $wpdb->prefix . 'simplepco_messages';
        $table_speakers = $wpdb->prefix . 'simplepco_speakers';
        $table_series = $wpdb->prefix . 'simplepco_series';
        $table_topics = $wpdb->prefix . 'simplepco_topics';

        $where = '1=1';
        $params = [];

        // Filter by series (by ID or slug/title)
        if (!empty($args['series'])) {
            if (is_numeric($args['series'])) {
                $where .= ' AND m.series_id = %d';
                $params[] = absint($args['series']);
            } else {
                $where .= ' AND sr.title LIKE %s';
                $params[] = '%' . $wpdb->esc_like(sanitize_text_field($args['series'])) . '%';
            }
        }

        // Filter by speaker (by ID or name)
        if (!empty($args['speaker'])) {
            if (is_numeric($args['speaker'])) {
                $where .= ' AND m.speaker_id = %d';
                $params[] = absint($args['speaker']);
            } else {
                $where .= ' AND sp.name LIKE %s';
                $params[] = '%' . $wpdb->esc_like(sanitize_text_field($args['speaker'])) . '%';
            }
        }

        // Filter by topic (by ID or name)
        if (!empty($args['topic'])) {
            if (is_numeric($args['topic'])) {
                $where .= ' AND m.topic_id = %d';
                $params[] = absint($args['topic']);
            } else {
                $where .= ' AND t.name LIKE %s';
                $params[] = '%' . $wpdb->esc_like(sanitize_text_field($args['topic'])) . '%';
            }
        }

        // Order
        $order_col = 'm.message_date';
        if ($args['orderby'] === 'title') {
            $order_col = 'm.title';
        }

        $order = $args['order'] === 'ASC' ? 'ASC' : 'DESC';
        $limit = min(absint($args['count']), 100);

        $query = "SELECT m.*,
                    sp.name AS speaker_name,
                    sp.image_url AS speaker_image_url,
                    sr.title AS series_title,
                    sr.image_url AS series_image_url,
                    t.name AS topic_name
                  FROM {$table_messages} m
                  LEFT JOIN {$table_speakers} sp ON m.speaker_id = sp.id
                  LEFT JOIN {$table_series} sr ON m.series_id = sr.id
                  LEFT JOIN {$table_topics} t ON m.topic_id = t.id
                  WHERE {$where}
                  ORDER BY {$order_col} {$order}
                  LIMIT %d";

        $params[] = $limit;

        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    /**
     * Load a template file and return output.
     */
    private function load_template($template_name, $data = []) {
        extract($data);

        ob_start();

        $template_path = SIMPLEPCO_PLUGIN_DIR . 'templates/series/public/' . $template_name . '.php';

        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<!-- Template not found: ' . esc_html($template_name) . ' -->';
        }

        return ob_get_clean();
    }
}
