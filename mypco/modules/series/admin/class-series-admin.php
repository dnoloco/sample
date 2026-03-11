<?php
/**
 * Series Admin Component
 *
 * Handles all backend/admin functionality for the Series module.
 * Provides meta boxes for the Message and Speaker post types,
 * custom fields for the Series taxonomy, and inline AJAX speaker creation.
 */

if (!defined('ABSPATH')) {
    exit;
}

class MyPCO_Series_Admin {

    private $loader;
    private $api_model;
    private $import;

    public function __construct($loader, $api_model) {
        $this->loader = $loader;
        $this->api_model = $api_model;

        // Initialize the import component
        require_once __DIR__ . '/class-series-import.php';
        $this->import = new MyPCO_Series_Import($loader, $api_model);
    }

    /**
     * Initialize admin functionality.
     */
    public function init() {
        $this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');
        $this->loader->add_filter('upload_dir', $this, 'custom_upload_dir');

        // Meta boxes on Message post type (order determines display order)
        $this->loader->add_action('add_meta_boxes', $this, 'add_message_info_meta_box');
        $this->loader->add_action('save_post_mypco_message', $this, 'save_message_info_meta', 10, 2);

        $this->loader->add_action('add_meta_boxes', $this, 'add_scripture_meta_box');
        $this->loader->add_action('save_post_mypco_message', $this, 'save_scripture_meta', 10, 2);

        $this->loader->add_action('add_meta_boxes', $this, 'add_media_meta_box');
        $this->loader->add_action('save_post_mypco_message', $this, 'save_media_meta', 10, 2);

        $this->loader->add_action('add_meta_boxes', $this, 'add_speaker_meta_box');
        $this->loader->add_action('save_post_mypco_message', $this, 'save_speaker_meta', 10, 2);

        $this->loader->add_action('add_meta_boxes', $this, 'add_series_info_meta_box');
        $this->loader->add_action('save_post_mypco_message', $this, 'save_series_info_meta', 10, 2);

        // Force meta box display order (overrides any saved user preference)
        $this->loader->add_filter('get_user_option_meta-box-order_mypco_message', $this, 'force_meta_box_order');

        // Meta boxes on Speaker post type
        $this->loader->add_action('add_meta_boxes', $this, 'add_speaker_details_meta_box');
        $this->loader->add_action('save_post_mypco_speaker', $this, 'save_speaker_details_meta', 10, 2);

        // Speaker list table columns
        $this->loader->add_filter('manage_mypco_speaker_posts_columns', $this, 'speaker_list_columns');
        $this->loader->add_action('manage_mypco_speaker_posts_custom_column', $this, 'speaker_list_column_content', 10, 2);

        // Message list table columns
        $this->loader->add_filter('manage_mypco_message_posts_columns', $this, 'message_list_columns');
        $this->loader->add_action('manage_mypco_message_posts_custom_column', $this, 'message_list_column_content', 10, 2);

        // AJAX: create speaker from Message editor meta box
        $this->loader->add_action('wp_ajax_mypco_add_speaker', $this, 'ajax_add_speaker');

        // Series taxonomy custom fields
        $this->loader->add_action('mypco_series_add_form_fields', $this, 'render_series_info_add_fields');
        $this->loader->add_action('mypco_series_edit_form_fields', $this, 'render_series_info_edit_fields');
        $this->loader->add_action('created_mypco_series', $this, 'save_series_info_term_meta');
        $this->loader->add_action('edited_mypco_series', $this, 'save_series_info_term_meta');

        // Reorder Messages submenu
        $this->loader->add_action('admin_menu', $this, 'reorder_messages_submenu', 999);

        // Settings page under Messages menu
        $this->loader->add_action('admin_menu', $this, 'add_settings_page');
        $this->loader->add_action('admin_init', $this, 'handle_settings_save');

        // Import functionality
        $this->import->init();
    }

    // =========================================================================
    // Admin Assets
    // =========================================================================

    /**
     * Enqueue admin-specific assets.
     */
    public function enqueue_admin_assets($hook) {
        $screen = get_current_screen();
        $is_module_post_type = ($screen && in_array($screen->post_type, ['mypco_message', 'mypco_speaker'], true));
        $is_module_taxonomy = ($screen && in_array($screen->taxonomy, ['mypco_series', 'mypco_service_type'], true));
        $is_settings_page = ($hook === 'mypco_message_page_mypco-series-settings');

        if (!$is_module_post_type && !$is_module_taxonomy && !$is_settings_page) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'mypco-series-admin',
            MYPCO_PLUGIN_URL . 'modules/series/admin/assets/css/series-admin.css',
            [],
            MYPCO_VERSION
        );

        wp_enqueue_script(
            'mypco-series-admin',
            MYPCO_PLUGIN_URL . 'modules/series/admin/assets/js/series-admin.js',
            ['jquery'],
            MYPCO_VERSION,
            true
        );

        $localize_data = [
            'ajaxUrl'         => admin_url('admin-ajax.php'),
            'addSpeakerNonce' => wp_create_nonce('mypco_add_speaker'),
        ];

        // Include Bible data only on the message editor
        if ($screen && $screen->post_type === 'mypco_message' && $screen->base === 'post') {
            $localize_data['bibleData'] = include MYPCO_PLUGIN_DIR . 'modules/series/admin/bible-data.php';
            $localize_data['i18n'] = [
                'selectBook' => __('Select Book', 'mypco-online'),
                'chapter'    => __('Chapter', 'mypco-online'),
                'verseStart' => __('Start Verse', 'mypco-online'),
                'verseEnd'   => __('End Verse', 'mypco-online'),
            ];
        }

        wp_localize_script('mypco-series-admin', 'mypcoSeriesAdmin', $localize_data);

        // Enqueue import script on the settings/import page
        if ($is_settings_page && isset($_GET['tab']) && $_GET['tab'] === 'import') {
            wp_enqueue_script(
                'mypco-series-import',
                MYPCO_PLUGIN_URL . 'modules/series/admin/assets/js/series-import.js',
                ['jquery'],
                MYPCO_VERSION,
                true
            );

            wp_localize_script('mypco-series-import', 'mypcoImport', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('mypco_import_nonce'),
                'i18n'    => [
                    'fetching'       => __('Fetching episodes...', 'mypco-online'),
                    'importing'      => __('Importing...', 'mypco-online'),
                    'imported'       => __('Imported', 'mypco-online'),
                    'skipped'        => __('Skipped', 'mypco-online'),
                    'error'          => __('Error', 'mypco-online'),
                    'alreadyExists'  => __('Already imported', 'mypco-online'),
                    'noEpisodes'     => __('No episodes selected.', 'mypco-online'),
                    'fetchError'     => __('Failed to fetch episodes.', 'mypco-online'),
                    'importComplete' => __('Import complete!', 'mypco-online'),
                    'episodesFound'  => __('episodes found.', 'mypco-online'),
                    'newEpisodes'    => __('new,', 'mypco-online'),
                    'alreadyImported' => __('already imported.', 'mypco-online'),
                    'video'          => __('Video', 'mypco-online'),
                    'audio'          => __('Audio', 'mypco-online'),
                    'sermonAudio'    => __('Sermon Audio', 'mypco-online'),
                    'art'            => __('Image', 'mypco-online'),
                    'files'          => __('Files', 'mypco-online'),
                    'none'           => __('None', 'mypco-online'),
                    'requestTimeout' => __('Request timed out. Try importing fewer episodes at a time.', 'mypco-online'),
                    'cacheExpired'   => __('Episode data expired. Please fetch again.', 'mypco-online'),
                ],
            ]);
        }
    }

    // =========================================================================
    // Message Post Type – Meta Box (Series Info)
    // =========================================================================

    /**
     * Register the "Series Info" meta box on the mypco_message post type.
     */
    public function add_series_info_meta_box() {
        $names = MyPCO_Series_Module::get_custom_labels();
        add_meta_box(
            'mypco_series_info',
            sprintf(__('%s Info', 'mypco-online'), $names['series_singular']),
            [$this, 'render_series_info_meta_box'],
            'mypco_message',
            'normal',
            'high'
        );
    }

    /**
     * Render the "Series Info" meta box fields.
     *
     * Reads from the first assigned mypco_series taxonomy term so the data
     * is shared with the term and not duplicated as post meta.
     */
    public function render_series_info_meta_box($post) {
        wp_nonce_field('mypco_series_info_meta_save', 'mypco_series_info_meta_nonce');

        $description = '';
        $start_date  = '';
        $image       = '';
        $term_name   = '';

        $terms = wp_get_post_terms($post->ID, 'mypco_series');
        if (!is_wp_error($terms) && !empty($terms)) {
            $term        = $terms[0];
            $term_name   = $term->name;
            $description = $term->description;
            $start_date  = get_term_meta($term->term_id, '_mypco_series_start_date', true);
            $image       = get_term_meta($term->term_id, '_mypco_series_image', true);
        }

        $names = MyPCO_Series_Module::get_custom_labels();

        if (empty($terms) || is_wp_error($terms)) : ?>
            <p><em><?php echo esc_html(sprintf(
                /* translators: %s: series label */
                __('Select a %s from the %s panel to edit its info here.', 'mypco-online'),
                $names['series_singular'],
                $names['series_singular']
            )); ?></em></p>
        <?php else : ?>
            <p><?php printf(
                /* translators: %1$s: series label, %2$s: series term name */
                esc_html__('Editing info for %1$s: %2$s', 'mypco-online'),
                strtolower($names['series_singular']),
                '<strong>' . esc_html($term_name) . '</strong>'
            ); ?></p>
            <table class="form-table mypco-meta-table">
                <tr>
                    <th><label for="mypco_series_description"><?php esc_html_e('Description', 'mypco-online'); ?></label></th>
                    <td>
                        <textarea id="mypco_series_description" name="mypco_series_description"
                                  rows="4"><?php echo esc_textarea($description); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="mypco_series_start_date"><?php esc_html_e('Start Date', 'mypco-online'); ?></label></th>
                    <td>
                        <input type="date" id="mypco_series_start_date" name="mypco_series_start_date"
                               value="<?php echo esc_attr($start_date); ?>" />
                    </td>
                </tr>
                <tr>
                    <th><label for="mypco_series_image"><?php esc_html_e('Image', 'mypco-online'); ?></label></th>
                    <td>
                        <input type="hidden" id="mypco_series_image" name="mypco_series_image"
                               value="<?php echo esc_url($image); ?>" />
                        <button type="button" class="button mypco-upload-image-btn"
                                data-target="#mypco_series_image"
                                data-preview="#mypco-series-image-preview"><?php esc_html_e('Select Image', 'mypco-online'); ?></button>
                        <button type="button" class="button mypco-remove-image-btn"
                                data-target="#mypco_series_image"
                                data-preview="#mypco-series-image-preview"
                                <?php echo $image ? '' : 'style="display:none;"'; ?>><?php esc_html_e('Remove Image', 'mypco-online'); ?></button>
                        <div id="mypco-series-image-preview" style="margin-top:10px;">
                            <?php if ($image) : ?>
                                <img src="<?php echo esc_url($image); ?>" style="max-width:200px;height:auto;" />
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            </table>
        <?php endif;
    }

    /**
     * Save the "Series Info" meta box data to the assigned Series taxonomy term.
     */
    public function save_series_info_meta($post_id, $post) {
        if (!isset($_POST['mypco_series_info_meta_nonce']) ||
            !wp_verify_nonce($_POST['mypco_series_info_meta_nonce'], 'mypco_series_info_meta_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $terms = wp_get_post_terms($post_id, 'mypco_series');
        if (is_wp_error($terms) || empty($terms)) {
            return;
        }

        $term_id = $terms[0]->term_id;

        if (isset($_POST['mypco_series_description'])) {
            wp_update_term($term_id, 'mypco_series', [
                'description' => sanitize_textarea_field($_POST['mypco_series_description']),
            ]);
        }

        if (isset($_POST['mypco_series_start_date'])) {
            update_term_meta($term_id, '_mypco_series_start_date', sanitize_text_field($_POST['mypco_series_start_date']));
        }

        if (isset($_POST['mypco_series_image'])) {
            update_term_meta($term_id, '_mypco_series_image', esc_url_raw($_POST['mypco_series_image']));
        }
    }

    // =========================================================================
    // Message Post Type – Meta Box (Speaker)
    // =========================================================================

    /**
     * Register the "Speaker" meta box on the mypco_message post type.
     */
    public function add_speaker_meta_box() {
        $names = MyPCO_Series_Module::get_custom_labels();
        add_meta_box(
            'mypco_speaker_meta',
            $names['message_singular'] . ' ' . $names['speaker_singular'],
            [$this, 'render_speaker_meta_box'],
            'mypco_message',
            'normal',
            'high'
        );
    }

    /**
     * Render the "Speaker" meta box with a dropdown and toggle-able inline add.
     */
    public function render_speaker_meta_box($post) {
        wp_nonce_field('mypco_speaker_meta_save', 'mypco_speaker_meta_nonce');

        $names = MyPCO_Series_Module::get_custom_labels();
        $speaker_s = $names['speaker_singular'];

        $current_speaker = get_post_meta($post->ID, '_mypco_speaker_id', true);

        $speakers = get_posts([
            'post_type'      => 'mypco_speaker',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ]);
        ?>
        <table class="form-table mypco-meta-table">
            <tr>
                <th><label for="mypco_speaker_id"><?php echo esc_html($speaker_s); ?></label></th>
                <td>
                    <select name="mypco_speaker_id" id="mypco_speaker_id">
                        <option value=""><?php echo esc_html(sprintf(__('Select a %s', 'mypco-online'), $speaker_s)); ?></option>
                        <?php foreach ($speakers as $speaker) : ?>
                            <option value="<?php echo (int) $speaker->ID; ?>" <?php selected($current_speaker, $speaker->ID); ?>>
                                <?php echo esc_html($speaker->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <a href="#" id="mypco_toggle_add_speaker" style="display:inline-block;margin-left:8px;font-size:12px;">
                        <?php echo esc_html(sprintf(__('Add %s', 'mypco-online'), $speaker_s)); ?>
                    </a>
                    <div id="mypco_add_speaker_form" style="display:none;margin-top:8px;">
                        <div class="mypco-field-with-button">
                            <input type="text" id="mypco_new_speaker_name" class="regular-text"
                                   placeholder="<?php echo esc_attr(sprintf(__('%s name', 'mypco-online'), $speaker_s)); ?>" />
                            <input type="button" id="mypco_add_speaker_btn" class="button"
                                   value="<?php echo esc_attr(sprintf(__('Add %s', 'mypco-online'), $speaker_s)); ?>" />
                        </div>
                        <span id="mypco_add_speaker_status" style="display:none;font-style:italic;font-size:12px;margin-left:4px;"></span>
                    </div>
                    <p class="description"><?php echo esc_html(sprintf(
                        /* translators: %1$s: speaker label, %2$s: message label */
                        __('Choose the %1$s for this %2$s.', 'mypco-online'),
                        strtolower($names['speaker_singular']),
                        strtolower($names['message_singular'])
                    )); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save the selected speaker ID as post meta.
     */
    public function save_speaker_meta($post_id, $post) {
        if (!isset($_POST['mypco_speaker_meta_nonce']) ||
            !wp_verify_nonce($_POST['mypco_speaker_meta_nonce'], 'mypco_speaker_meta_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $speaker_id = isset($_POST['mypco_speaker_id']) ? absint($_POST['mypco_speaker_id']) : 0;

        if ($speaker_id > 0) {
            update_post_meta($post_id, '_mypco_speaker_id', $speaker_id);
        } else {
            delete_post_meta($post_id, '_mypco_speaker_id');
        }
    }

    /**
     * AJAX: create a new mypco_speaker post from the Message editor meta box.
     */
    public function ajax_add_speaker() {
        check_ajax_referer('mypco_add_speaker', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permission denied.', 'mypco-online')]);
        }

        $name = isset($_POST['speaker_name']) ? sanitize_text_field($_POST['speaker_name']) : '';
        if (empty($name)) {
            wp_send_json_error(['message' => __('Speaker name is required.', 'mypco-online')]);
        }

        $post_id = wp_insert_post([
            'post_type'   => 'mypco_speaker',
            'post_title'  => $name,
            'post_status' => 'publish',
        ], true);

        if (is_wp_error($post_id)) {
            wp_send_json_error(['message' => $post_id->get_error_message()]);
        }

        wp_send_json_success([
            'id'   => $post_id,
            'name' => $name,
        ]);
    }

    // =========================================================================
    // Speaker Post Type – Meta Box (Speaker Details)
    // =========================================================================

    /**
     * Register the "Speaker Details" meta box on the mypco_speaker post type.
     */
    public function add_speaker_details_meta_box() {
        $names = MyPCO_Series_Module::get_custom_labels();
        add_meta_box(
            'mypco_speaker_details',
            sprintf(__('%s Details', 'mypco-online'), $names['speaker_singular']),
            [$this, 'render_speaker_details_meta_box'],
            'mypco_speaker',
            'normal',
            'high'
        );
    }

    /**
     * Render the "Speaker Details" meta box fields.
     */
    public function render_speaker_details_meta_box($post) {
        wp_nonce_field('mypco_speaker_details_meta_save', 'mypco_speaker_details_meta_nonce');

        $title_role = get_post_meta($post->ID, '_mypco_speaker_title', true);
        $image      = get_post_meta($post->ID, '_mypco_speaker_image', true);
        $links      = get_post_meta($post->ID, '_mypco_speaker_links', true);

        if (!is_array($links) || empty($links)) {
            $links = [['label' => '', 'url' => '']];
        }
        ?>
        <table class="form-table mypco-meta-table">
            <tr>
                <th><label for="mypco_speaker_title"><?php esc_html_e('Title / Role', 'mypco-online'); ?></label></th>
                <td>
                    <input type="text" id="mypco_speaker_title" name="mypco_speaker_title"
                           value="<?php echo esc_attr($title_role); ?>" class="regular-text"
                           placeholder="<?php esc_attr_e('e.g. Senior Pastor', 'mypco-online'); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="mypco_speaker_image"><?php esc_html_e('Photo', 'mypco-online'); ?></label></th>
                <td>
                    <input type="hidden" id="mypco_speaker_image" name="mypco_speaker_image"
                           value="<?php echo esc_url($image); ?>" />
                    <div class="mypco-field-with-button">
                        <button type="button" class="button mypco-upload-image-btn"
                                data-target="#mypco_speaker_image"
                                data-preview="#mypco-speaker-image-preview"><?php esc_html_e('Select Image', 'mypco-online'); ?></button>
                        <button type="button" class="button mypco-remove-image-btn"
                                data-target="#mypco_speaker_image"
                                data-preview="#mypco-speaker-image-preview"
                                <?php echo $image ? '' : 'style="display:none;"'; ?>><?php esc_html_e('Remove Image', 'mypco-online'); ?></button>
                    </div>
                    <div id="mypco-speaker-image-preview" style="margin-top:10px;">
                        <?php if ($image) : ?>
                            <img src="<?php echo esc_url($image); ?>" style="max-width:200px;height:auto;" />
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Links', 'mypco-online'); ?></th>
                <td>
                    <div id="mypco-speaker-links">
                        <?php foreach ($links as $i => $link) : ?>
                            <div class="mypco-speaker-link-row" data-index="<?php echo (int) $i; ?>">
                                <input type="text" name="mypco_speaker_links[<?php echo (int) $i; ?>][label]"
                                       class="regular-text mypco-link-label"
                                       value="<?php echo esc_attr($link['label'] ?? ''); ?>"
                                       placeholder="<?php esc_attr_e('Label (e.g. Facebook)', 'mypco-online'); ?>" />
                                <input type="url" name="mypco_speaker_links[<?php echo (int) $i; ?>][url]"
                                       class="regular-text mypco-link-url"
                                       value="<?php echo esc_url($link['url'] ?? ''); ?>"
                                       placeholder="<?php esc_attr_e('https://...', 'mypco-online'); ?>" />
                                <button type="button" class="button mypco-remove-speaker-link"
                                        title="<?php esc_attr_e('Remove', 'mypco-online'); ?>">&times;</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p style="margin-top:8px;">
                        <button type="button" class="button" id="mypco-add-speaker-link">
                            <?php esc_html_e('Add Link', 'mypco-online'); ?>
                        </button>
                    </p>
                    <p class="description"><?php esc_html_e('Add links to social profiles, websites, or other resources.', 'mypco-online'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save the "Speaker Details" meta box data.
     */
    public function save_speaker_details_meta($post_id, $post) {
        if (!isset($_POST['mypco_speaker_details_meta_nonce']) ||
            !wp_verify_nonce($_POST['mypco_speaker_details_meta_nonce'], 'mypco_speaker_details_meta_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Title / Role
        if (isset($_POST['mypco_speaker_title'])) {
            update_post_meta($post_id, '_mypco_speaker_title', sanitize_text_field($_POST['mypco_speaker_title']));
        }

        // Photo
        if (isset($_POST['mypco_speaker_image'])) {
            $image = esc_url_raw($_POST['mypco_speaker_image']);
            if ($image) {
                update_post_meta($post_id, '_mypco_speaker_image', $image);
            } else {
                delete_post_meta($post_id, '_mypco_speaker_image');
            }
        }

        // Links
        $links = [];
        if (isset($_POST['mypco_speaker_links']) && is_array($_POST['mypco_speaker_links'])) {
            foreach ($_POST['mypco_speaker_links'] as $entry) {
                $label = isset($entry['label']) ? sanitize_text_field($entry['label']) : '';
                $url   = isset($entry['url']) ? esc_url_raw($entry['url']) : '';
                if (!empty($url)) {
                    $links[] = ['label' => $label, 'url' => $url];
                }
            }
        }

        if (!empty($links)) {
            update_post_meta($post_id, '_mypco_speaker_links', $links);
        } else {
            delete_post_meta($post_id, '_mypco_speaker_links');
        }
    }

    // =========================================================================
    // Speaker Post Type – List Table Columns
    // =========================================================================

    /**
     * Define custom columns for the mypco_speaker list table.
     */
    public function speaker_list_columns($columns) {
        $names = MyPCO_Series_Module::get_custom_labels();
        $new_columns = [];
        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;
            if ($key === 'title') {
                $new_columns['speaker_title'] = __('Title / Role', 'mypco-online');
                $new_columns['message_count'] = $names['message_plural'];
            }
        }
        return $new_columns;
    }

    /**
     * Render content for custom speaker list table columns.
     */
    public function speaker_list_column_content($column, $post_id) {
        if ($column === 'speaker_title') {
            $title = get_post_meta($post_id, '_mypco_speaker_title', true);
            echo esc_html($title ?: '—');
        }

        if ($column === 'message_count') {
            global $wpdb;
            $count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = '_mypco_speaker_id'
                 AND pm.meta_value = %s
                 AND p.post_type = 'mypco_message'
                 AND p.post_status != 'trash'",
                (string) $post_id
            ));
            echo (int) $count;
        }
    }

    // =========================================================================
    // Message Post Type – List Table Columns
    // =========================================================================

    /**
     * Define custom columns for the mypco_message list table.
     */
    public function message_list_columns($columns) {
        $names = MyPCO_Series_Module::get_custom_labels();

        $new_columns = [];
        $new_columns['cb'] = isset($columns['cb']) ? $columns['cb'] : '<input type="checkbox" />';
        $new_columns['title'] = isset($columns['title']) ? $columns['title'] : __('Title', 'mypco-online');
        $new_columns['message_series'] = $names['series_singular'];
        $new_columns['message_speaker'] = $names['speaker_singular'];
        $new_columns['message_date_published'] = __('Date Published', 'mypco-online');

        return $new_columns;
    }

    /**
     * Render content for custom message list table columns.
     */
    public function message_list_column_content($column, $post_id) {
        if ($column === 'message_series') {
            $terms = wp_get_post_terms($post_id, 'mypco_series');
            if (!is_wp_error($terms) && !empty($terms)) {
                $names = [];
                foreach ($terms as $term) {
                    $names[] = esc_html($term->name);
                }
                echo implode(', ', $names);
            } else {
                echo '—';
            }
        }

        if ($column === 'message_speaker') {
            $speaker_id = get_post_meta($post_id, '_mypco_speaker_id', true);
            if ($speaker_id) {
                $speaker = get_post($speaker_id);
                if ($speaker && $speaker->post_status !== 'trash') {
                    echo esc_html($speaker->post_title);
                } else {
                    echo '—';
                }
            } else {
                echo '—';
            }
        }

        if ($column === 'message_date_published') {
            $message_date = get_post_meta($post_id, '_mypco_message_date', true);
            if ($message_date) {
                $timestamp = strtotime($message_date);
                if ($timestamp) {
                    echo esc_html(date('n.j.y', $timestamp));
                } else {
                    echo '—';
                }
            } else {
                echo '—';
            }
        }
    }

    // =========================================================================
    // Message Post Type – Meta Box (Message Info)
    // =========================================================================

    /**
     * Register the "Message Info" meta box on the mypco_message post type.
     */
    public function add_message_info_meta_box() {
        $names = MyPCO_Series_Module::get_custom_labels();
        add_meta_box(
            'mypco_message_info',
            sprintf(__('%s Info', 'mypco-online'), $names['message_singular']),
            [$this, 'render_message_info_meta_box'],
            'mypco_message',
            'normal',
            'high'
        );
    }

    /**
     * Render the "Message Info" meta box fields.
     */
    public function render_message_info_meta_box($post) {
        wp_nonce_field('mypco_message_info_meta_save', 'mypco_message_info_meta_nonce');

        $names        = MyPCO_Series_Module::get_custom_labels();
        $description  = get_post_meta($post->ID, '_mypco_message_description', true);
        $message_date = get_post_meta($post->ID, '_mypco_message_date', true);
        $image        = get_post_meta($post->ID, '_mypco_message_image', true);
        ?>
        <table class="form-table mypco-meta-table">
            <tr>
                <th><label for="mypco_message_description"><?php esc_html_e('Description', 'mypco-online'); ?></label></th>
                <td>
                    <textarea id="mypco_message_description" name="mypco_message_description"
                              rows="4"><?php echo esc_textarea($description); ?></textarea>
                    <p class="description"><?php echo esc_html(sprintf(
                        /* translators: %s: message label */
                        __('A short summary of the %s.', 'mypco-online'),
                        strtolower($names['message_singular'])
                    )); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="mypco_message_date"><?php esc_html_e('Date', 'mypco-online'); ?></label></th>
                <td>
                    <input type="date" id="mypco_message_date" name="mypco_message_date"
                           value="<?php echo esc_attr($message_date); ?>" />
                    <p class="description"><?php echo esc_html(sprintf(
                        /* translators: %s: message label */
                        __('The date this %s was delivered.', 'mypco-online'),
                        strtolower($names['message_singular'])
                    )); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="mypco_message_image"><?php esc_html_e('Image', 'mypco-online'); ?></label></th>
                <td>
                    <input type="hidden" id="mypco_message_image" name="mypco_message_image"
                           value="<?php echo esc_url($image); ?>" />
                    <div class="mypco-field-with-button">
                        <button type="button" class="button mypco-upload-image-btn"
                                data-target="#mypco_message_image"
                                data-preview="#mypco-message-image-preview"><?php esc_html_e('Select Image', 'mypco-online'); ?></button>
                        <button type="button" class="button mypco-remove-image-btn"
                                data-target="#mypco_message_image"
                                data-preview="#mypco-message-image-preview"
                                <?php echo $image ? '' : 'style="display:none;"'; ?>><?php esc_html_e('Remove Image', 'mypco-online'); ?></button>
                    </div>
                    <div id="mypco-message-image-preview" style="margin-top:10px;">
                        <?php if ($image) : ?>
                            <img src="<?php echo esc_url($image); ?>" style="max-width:200px;height:auto;" />
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save the "Message Info" meta box data.
     */
    public function save_message_info_meta($post_id, $post) {
        if (!isset($_POST['mypco_message_info_meta_nonce']) ||
            !wp_verify_nonce($_POST['mypco_message_info_meta_nonce'], 'mypco_message_info_meta_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['mypco_message_description'])) {
            update_post_meta($post_id, '_mypco_message_description', sanitize_textarea_field($_POST['mypco_message_description']));
        }

        if (isset($_POST['mypco_message_date'])) {
            update_post_meta($post_id, '_mypco_message_date', sanitize_text_field($_POST['mypco_message_date']));
        }

        if (isset($_POST['mypco_message_image'])) {
            update_post_meta($post_id, '_mypco_message_image', esc_url_raw($_POST['mypco_message_image']));
        }
    }

    // =========================================================================
    // Message Post Type – Meta Box (Media)
    // =========================================================================

    /**
     * Register the "Media" meta box on the mypco_message post type.
     */
    public function add_media_meta_box() {
        $names = MyPCO_Series_Module::get_custom_labels();
        add_meta_box(
            'mypco_media_meta',
            sprintf(__('%s Media', 'mypco-online'), $names['message_singular']),
            [$this, 'render_media_meta_box'],
            'mypco_message',
            'normal',
            'high'
        );
    }

    /**
     * Render the "Message Media" meta box fields (audio + video).
     */
    public function render_media_meta_box($post) {
        wp_nonce_field('mypco_media_meta_save', 'mypco_media_meta_nonce');

        $audio = get_post_meta($post->ID, '_mypco_message_audio', true);
        $video = get_post_meta($post->ID, '_mypco_message_video', true);
        $files = get_post_meta($post->ID, '_mypco_message_files', true);
        if (!is_array($files)) {
            $files = [];
        }
        ?>
        <table class="form-table mypco-meta-table">
            <tr>
                <th><label for="mypco_message_audio"><?php esc_html_e('Audio', 'mypco-online'); ?></label></th>
                <td>
                    <div class="mypco-field-with-button">
                        <input type="url" id="mypco_message_audio" name="mypco_message_audio"
                               value="<?php echo esc_url($audio); ?>" class="regular-text" />
                        <button type="button" class="button mypco-upload-media-btn"
                                data-target="#mypco_message_audio"
                                data-media-type="audio"><?php esc_html_e('Add or Upload File', 'mypco-online'); ?></button>
                        <button type="button" class="button mypco-remove-media-btn"
                                data-target="#mypco_message_audio"
                                <?php echo $audio ? '' : 'style="display:none;"'; ?>><?php esc_html_e('Remove', 'mypco-online'); ?></button>
                    </div>
                    <p class="description"><?php esc_html_e('Enter a URL or upload an audio file.', 'mypco-online'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="mypco_message_video"><?php esc_html_e('Video', 'mypco-online'); ?></label></th>
                <td>
                    <div class="mypco-field-with-button">
                        <input type="url" id="mypco_message_video" name="mypco_message_video"
                               value="<?php echo esc_url($video); ?>" class="regular-text" />
                        <button type="button" class="button mypco-upload-media-btn"
                                data-target="#mypco_message_video"
                                data-media-type="video"><?php esc_html_e('Add or Upload File', 'mypco-online'); ?></button>
                        <button type="button" class="button mypco-remove-media-btn"
                                data-target="#mypco_message_video"
                                <?php echo $video ? '' : 'style="display:none;"'; ?>><?php esc_html_e('Remove', 'mypco-online'); ?></button>
                    </div>
                    <p class="description"><?php esc_html_e('Enter a URL or upload a video file.', 'mypco-online'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Files', 'mypco-online'); ?></th>
                <td>
                    <div id="mypco-message-files" class="mypco-files-repeater">
                        <?php if (!empty($files)) : ?>
                            <?php foreach ($files as $idx => $file) :
                                $fname = isset($file['name']) ? $file['name'] : '';
                                $furl  = isset($file['url'])  ? $file['url']  : '';
                            ?>
                            <div class="mypco-file-row" data-index="<?php echo (int) $idx; ?>">
                                <span class="mypco-file-drag dashicons dashicons-menu" title="<?php esc_attr_e('Drag to reorder', 'mypco-online'); ?>"></span>
                                <input type="text"
                                       name="mypco_message_files[<?php echo (int) $idx; ?>][name]"
                                       value="<?php echo esc_attr($fname); ?>"
                                       class="regular-text mypco-file-name"
                                       placeholder="<?php esc_attr_e('Name', 'mypco-online'); ?>" />
                                <input type="url"
                                       name="mypco_message_files[<?php echo (int) $idx; ?>][url]"
                                       value="<?php echo esc_url($furl); ?>"
                                       class="regular-text mypco-file-url"
                                       placeholder="<?php esc_attr_e('File URL', 'mypco-online'); ?>" />
                                <button type="button" class="button mypco-file-upload-btn"><?php esc_html_e('Upload', 'mypco-online'); ?></button>
                                <button type="button" class="button mypco-file-remove-btn" title="<?php esc_attr_e('Remove', 'mypco-online'); ?>">&times;</button>
                            </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="mypco-file-row" data-index="0">
                                <span class="mypco-file-drag dashicons dashicons-menu" title="<?php esc_attr_e('Drag to reorder', 'mypco-online'); ?>"></span>
                                <input type="text"
                                       name="mypco_message_files[0][name]"
                                       value=""
                                       class="regular-text mypco-file-name"
                                       placeholder="<?php esc_attr_e('Name', 'mypco-online'); ?>" />
                                <input type="url"
                                       name="mypco_message_files[0][url]"
                                       value=""
                                       class="regular-text mypco-file-url"
                                       placeholder="<?php esc_attr_e('File URL', 'mypco-online'); ?>" />
                                <button type="button" class="button mypco-file-upload-btn"><?php esc_html_e('Upload', 'mypco-online'); ?></button>
                                <button type="button" class="button mypco-file-remove-btn" title="<?php esc_attr_e('Remove', 'mypco-online'); ?>">&times;</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <p style="margin-top:8px;">
                        <button type="button" class="button" id="mypco-add-file"><?php esc_html_e('+ Add File', 'mypco-online'); ?></button>
                    </p>
                    <p class="description"><?php esc_html_e('Attach downloadable files such as notes, outlines, or study guides.', 'mypco-online'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save the "Media" meta box data.
     */
    public function save_media_meta($post_id, $post) {
        if (!isset($_POST['mypco_media_meta_nonce']) ||
            !wp_verify_nonce($_POST['mypco_media_meta_nonce'], 'mypco_media_meta_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['mypco_message_audio'])) {
            $audio = esc_url_raw($_POST['mypco_message_audio']);
            if ($audio) {
                update_post_meta($post_id, '_mypco_message_audio', $audio);
            } else {
                delete_post_meta($post_id, '_mypco_message_audio');
            }
        }

        if (isset($_POST['mypco_message_video'])) {
            $video = esc_url_raw($_POST['mypco_message_video']);
            if ($video) {
                update_post_meta($post_id, '_mypco_message_video', $video);
            } else {
                delete_post_meta($post_id, '_mypco_message_video');
            }
        }

        // Save files repeater
        if (isset($_POST['mypco_message_files']) && is_array($_POST['mypco_message_files'])) {
            $clean_files = [];
            foreach ($_POST['mypco_message_files'] as $file) {
                $name = isset($file['name']) ? sanitize_text_field($file['name']) : '';
                $url  = isset($file['url'])  ? esc_url_raw($file['url'])          : '';
                if ($name || $url) {
                    $clean_files[] = ['name' => $name, 'url' => $url];
                }
            }
            if (!empty($clean_files)) {
                update_post_meta($post_id, '_mypco_message_files', $clean_files);
            } else {
                delete_post_meta($post_id, '_mypco_message_files');
            }
        } else {
            delete_post_meta($post_id, '_mypco_message_files');
        }
    }

    // =========================================================================
    // Message Post Type – Meta Box (Scripture)
    // =========================================================================

    /**
     * Register the "Scripture" meta box on the mypco_message post type.
     */
    public function add_scripture_meta_box() {
        $names = MyPCO_Series_Module::get_custom_labels();
        add_meta_box(
            'mypco_scripture_meta',
            sprintf(__('%s Scripture', 'mypco-online'), $names['message_singular']),
            [$this, 'render_scripture_meta_box'],
            'mypco_message',
            'normal',
            'high'
        );
    }

    /**
     * Render the "Message Scripture" meta box with repeatable passage rows.
     *
     * Each passage has cascading dropdowns: Book, Chapter, Start Verse, End Verse.
     * JavaScript populates the options from localised Bible data.
     */
    public function render_scripture_meta_box($post) {
        wp_nonce_field('mypco_scripture_meta_save', 'mypco_scripture_meta_nonce');

        $scriptures = get_post_meta($post->ID, '_mypco_message_scriptures', true);
        if (!is_array($scriptures) || empty($scriptures)) {
            $scriptures = [['book' => '', 'chapter' => '', 'verse_start' => '', 'verse_end' => '']];
        }
        ?>
        <table class="form-table mypco-meta-table">
            <tr>
                <th><?php esc_html_e('Passages', 'mypco-online'); ?></th>
                <td>
                    <div id="mypco-scripture-passages">
                        <?php foreach ($scriptures as $i => $scripture) : ?>
                        <div class="mypco-scripture-row" data-index="<?php echo (int) $i; ?>">
                            <select name="mypco_scriptures[<?php echo (int) $i; ?>][book]" class="mypco-scripture-book"
                                    data-value="<?php echo esc_attr($scripture['book'] ?? ''); ?>">
                                <option value=""><?php esc_html_e('Select Book', 'mypco-online'); ?></option>
                            </select>
                            <select name="mypco_scriptures[<?php echo (int) $i; ?>][chapter]" class="mypco-scripture-chapter"
                                    data-value="<?php echo esc_attr($scripture['chapter'] ?? ''); ?>" disabled>
                                <option value=""><?php esc_html_e('Chapter', 'mypco-online'); ?></option>
                            </select>
                            <select name="mypco_scriptures[<?php echo (int) $i; ?>][verse_start]" class="mypco-scripture-verse-start"
                                    data-value="<?php echo esc_attr($scripture['verse_start'] ?? $scripture['verse'] ?? ''); ?>" disabled>
                                <option value=""><?php esc_html_e('Start Verse', 'mypco-online'); ?></option>
                            </select>
                            <span class="mypco-scripture-dash">&ndash;</span>
                            <select name="mypco_scriptures[<?php echo (int) $i; ?>][verse_end]" class="mypco-scripture-verse-end"
                                    data-value="<?php echo esc_attr($scripture['verse_end'] ?? ''); ?>" disabled>
                                <option value=""><?php esc_html_e('End Verse', 'mypco-online'); ?></option>
                            </select>
                            <button type="button" class="button mypco-remove-scripture"
                                    title="<?php esc_attr_e('Remove', 'mypco-online'); ?>">&times;</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p style="margin-top:8px;">
                        <button type="button" class="button" id="mypco-add-scripture">
                            <?php esc_html_e('Add Passage', 'mypco-online'); ?>
                        </button>
                    </p>
                    <p class="description"><?php esc_html_e('Select a book, chapter, and optional verse range for each passage.', 'mypco-online'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save the "Scripture" meta box data.
     */
    public function save_scripture_meta($post_id, $post) {
        if (!isset($_POST['mypco_scripture_meta_nonce']) ||
            !wp_verify_nonce($_POST['mypco_scripture_meta_nonce'], 'mypco_scripture_meta_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $scriptures = [];

        if (isset($_POST['mypco_scriptures']) && is_array($_POST['mypco_scriptures'])) {
            foreach ($_POST['mypco_scriptures'] as $entry) {
                $book        = isset($entry['book']) ? sanitize_text_field($entry['book']) : '';
                $chapter     = isset($entry['chapter']) ? absint($entry['chapter']) : 0;
                $verse_start = isset($entry['verse_start']) ? absint($entry['verse_start']) : 0;
                $verse_end   = isset($entry['verse_end']) ? absint($entry['verse_end']) : 0;

                if (!empty($book)) {
                    $scriptures[] = [
                        'book'        => $book,
                        'chapter'     => $chapter,
                        'verse_start' => $verse_start,
                        'verse_end'   => $verse_end,
                    ];
                }
            }
        }

        if (!empty($scriptures)) {
            update_post_meta($post_id, '_mypco_message_scriptures', $scriptures);
        } else {
            delete_post_meta($post_id, '_mypco_message_scriptures');
        }
    }

    // =========================================================================
    // Series Taxonomy – Custom Fields
    // =========================================================================

    /**
     * Render Series Info custom fields on the "Add New Series" term form.
     *
     * Name and Description are built-in taxonomy term fields.
     * Start Date and Image are stored as term meta.
     */
    public function render_series_info_add_fields() {
        wp_nonce_field('mypco_series_info_save', 'mypco_series_info_nonce');
        ?>
        <div class="form-field">
            <label for="mypco_series_start_date"><?php esc_html_e('Start Date', 'mypco-online'); ?></label>
            <input type="date" id="mypco_series_start_date" name="mypco_series_start_date" value="" />
        </div>
        <div class="form-field">
            <label for="mypco_series_image"><?php esc_html_e('Image', 'mypco-online'); ?></label>
            <input type="url" id="mypco_series_image" name="mypco_series_image" value="" class="regular-text" />
            <button type="button" class="button mypco-upload-image-btn"
                    data-target="#mypco_series_image"><?php esc_html_e('Upload Image', 'mypco-online'); ?></button>
        </div>
        <?php
    }

    /**
     * Render Series Info custom fields on the "Edit Series" term form.
     */
    public function render_series_info_edit_fields($term) {
        wp_nonce_field('mypco_series_info_save', 'mypco_series_info_nonce');

        $start_date = get_term_meta($term->term_id, '_mypco_series_start_date', true);
        $image      = get_term_meta($term->term_id, '_mypco_series_image', true);
        ?>
        <tr class="form-field">
            <th scope="row"><label for="mypco_series_start_date"><?php esc_html_e('Start Date', 'mypco-online'); ?></label></th>
            <td>
                <input type="date" id="mypco_series_start_date" name="mypco_series_start_date"
                       value="<?php echo esc_attr($start_date); ?>" />
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="mypco_series_image"><?php esc_html_e('Image', 'mypco-online'); ?></label></th>
            <td>
                <input type="url" id="mypco_series_image" name="mypco_series_image"
                       value="<?php echo esc_url($image); ?>" class="regular-text" />
                <button type="button" class="button mypco-upload-image-btn"
                        data-target="#mypco_series_image"><?php esc_html_e('Upload Image', 'mypco-online'); ?></button>
                <?php if ($image) : ?>
                    <div style="margin-top:10px;">
                        <img src="<?php echo esc_url($image); ?>" style="max-width:200px;height:auto;" />
                    </div>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Save Series Info term meta when a Series term is created or updated.
     */
    public function save_series_info_term_meta($term_id) {
        if (!isset($_POST['mypco_series_info_nonce']) ||
            !wp_verify_nonce($_POST['mypco_series_info_nonce'], 'mypco_series_info_save')) {
            return;
        }

        if (!current_user_can('manage_categories')) {
            return;
        }

        if (isset($_POST['mypco_series_start_date'])) {
            update_term_meta($term_id, '_mypco_series_start_date', sanitize_text_field($_POST['mypco_series_start_date']));
        }

        if (isset($_POST['mypco_series_image'])) {
            update_term_meta($term_id, '_mypco_series_image', esc_url_raw($_POST['mypco_series_image']));
        }
    }

    // =========================================================================
    // Settings Page
    // =========================================================================

    /**
     * Register the Settings submenu page under Messages.
     */
    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=mypco_message',
            __('Messages Settings', 'mypco-online'),
            __('Settings', 'mypco-online'),
            'manage_options',
            'mypco-series-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Render the Settings page form with tabbed navigation.
     */
    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'settings';
        $names = MyPCO_Series_Module::get_custom_labels();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($names['message_plural']); ?> <?php esc_html_e('Settings', 'mypco-online'); ?></h1>

            <?php if (isset($_GET['settings-updated'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Settings saved.', 'mypco-online'); ?></p>
                </div>
            <?php endif; ?>

            <h2 class="nav-tab-wrapper">
                <a href="?post_type=mypco_message&page=mypco-series-settings&tab=settings"
                   class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Settings', 'mypco-online'); ?>
                </a>
                <a href="?post_type=mypco_message&page=mypco-series-settings&tab=import"
                   class="nav-tab <?php echo $active_tab === 'import' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Import', 'mypco-online'); ?>
                </a>
            </h2>

            <div class="mypco-settings-content" style="background:#fff;border:1px solid #ccd0d4;border-top:none;padding:20px;">
                <?php if ($active_tab === 'import') : ?>
                    <?php $this->render_import_tab(); ?>
                <?php else : ?>
                    <?php $this->render_labels_settings(); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render the Import tab content.
     */
    private function render_import_tab() {
        if ($this->import) {
            $this->import->render_import_tab();
        } else {
            echo '<p>' . esc_html__('Import functionality requires the API model to be initialized.', 'mypco-online') . '</p>';
        }
    }

    /**
     * Render the Labels/Settings tab content.
     */
    private function render_labels_settings() {
        $saved = get_option('mypco_series_labels', []);
        if (!is_array($saved)) {
            $saved = [];
        }

        $sections = [
            'message' => [
                'heading'  => __('Messages', 'mypco-online'),
                'singular' => 'Message',
                'plural'   => 'Messages',
            ],
            'speaker' => [
                'heading'  => __('Speakers', 'mypco-online'),
                'singular' => 'Speaker',
                'plural'   => 'Speakers',
            ],
            'series' => [
                'heading'  => __('Series', 'mypco-online'),
                'singular' => 'Series',
                'plural'   => 'Series',
            ],
            'service_type' => [
                'heading'  => __('Service Types', 'mypco-online'),
                'singular' => 'Service Type',
                'plural'   => 'Service Types',
            ],
        ];
        ?>
        <p class="description" style="margin-bottom:15px;">
            <?php esc_html_e('Customize the display names used throughout the admin area. Leave a field empty to use the default.', 'mypco-online'); ?>
        </p>

        <form method="post">
            <?php wp_nonce_field('mypco_series_labels_save', 'mypco_series_labels_nonce'); ?>

            <table class="form-table">
                <?php foreach ($sections as $key => $section) :
                    $s_key = $key . '_singular';
                    $p_key = $key . '_plural';
                    $s_val = isset($saved[$s_key]) ? $saved[$s_key] : '';
                    $p_val = isset($saved[$p_key]) ? $saved[$p_key] : '';
                ?>
                <tr>
                    <th scope="row"><?php echo esc_html($section['heading']); ?></th>
                    <td>
                        <label style="display:inline-block;margin-right:20px;">
                            <span class="description"><?php esc_html_e('Singular', 'mypco-online'); ?></span><br>
                            <input type="text"
                                   name="mypco_series_labels[<?php echo esc_attr($s_key); ?>]"
                                   value="<?php echo esc_attr($s_val); ?>"
                                   placeholder="<?php echo esc_attr($section['singular']); ?>"
                                   class="regular-text" />
                        </label>
                        <label style="display:inline-block;">
                            <span class="description"><?php esc_html_e('Plural', 'mypco-online'); ?></span><br>
                            <input type="text"
                                   name="mypco_series_labels[<?php echo esc_attr($p_key); ?>]"
                                   value="<?php echo esc_attr($p_val); ?>"
                                   placeholder="<?php echo esc_attr($section['plural']); ?>"
                                   class="regular-text" />
                        </label>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <?php submit_button(__('Save Settings', 'mypco-online')); ?>
        </form>
        <?php
    }

    /**
     * Handle the Settings page form submission.
     */
    public function handle_settings_save() {
        if (!isset($_POST['mypco_series_labels_nonce']) ||
            !wp_verify_nonce($_POST['mypco_series_labels_nonce'], 'mypco_series_labels_save')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $raw = isset($_POST['mypco_series_labels']) && is_array($_POST['mypco_series_labels'])
            ? $_POST['mypco_series_labels']
            : [];

        $allowed_keys = [
            'message_singular', 'message_plural',
            'speaker_singular', 'speaker_plural',
            'series_singular',  'series_plural',
            'service_type_singular', 'service_type_plural',
        ];

        $clean = [];
        foreach ($allowed_keys as $key) {
            if (isset($raw[$key]) && '' !== trim($raw[$key])) {
                $clean[$key] = sanitize_text_field($raw[$key]);
            }
        }

        update_option('mypco_series_labels', $clean);

        wp_redirect(add_query_arg('settings-updated', 'true', wp_get_referer()));
        exit;
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Reorder the Messages admin submenu.
     *
     * Ensures the submenu appears as:
     * All Messages, Add Message, Speakers, Series, Service Types.
     */
    public function reorder_messages_submenu() {
        global $submenu;

        $menu_slug = 'edit.php?post_type=mypco_message';

        if (!isset($submenu[$menu_slug])) {
            return;
        }

        $desired_order = [
            'edit.php?post_type=mypco_message',
            'post-new.php?post_type=mypco_message',
            'edit.php?post_type=mypco_speaker',
            'edit-tags.php?taxonomy=mypco_series&amp;post_type=mypco_message',
            'edit-tags.php?taxonomy=mypco_service_type&amp;post_type=mypco_message',
            'mypco-series-settings',
        ];

        $ordered  = [];
        $position = 0;

        foreach ($desired_order as $slug) {
            foreach ($submenu[$menu_slug] as $item) {
                if ($item[2] === $slug) {
                    $ordered[$position] = $item;
                    $position++;
                    break;
                }
            }
        }

        // Append any remaining items not in our desired order.
        foreach ($submenu[$menu_slug] as $item) {
            if (!in_array($item[2], $desired_order, true)) {
                $ordered[$position] = $item;
                $position++;
            }
        }

        $submenu[$menu_slug] = $ordered;
    }

    /**
     * Force meta box display order on the mypco_message editor.
     *
     * WordPress saves meta box positions per-user. This filter overrides
     * that saved preference so our boxes always appear in a fixed order.
     */
    public function force_meta_box_order($order) {
        return [
            'normal'   => 'mypco_message_info,mypco_scripture_meta,mypco_media_meta,mypco_speaker_meta,mypco_series_info',
            'side'     => '',
            'advanced' => '',
        ];
    }

    /**
     * Customize the upload directory for series module uploads.
     *
     * When uploads come from our admin pages with a mypco_upload_type param,
     * route them into organised subdirectories:
     *   wp-content/uploads/mypco-series/speakers/
     *   wp-content/uploads/mypco-series/messages/
     *   wp-content/uploads/mypco-series/series/
     */
    public function custom_upload_dir($uploads) {
        $type = isset($_REQUEST['mypco_upload_type']) ? sanitize_key($_REQUEST['mypco_upload_type']) : '';

        $allowed = ['speakers', 'messages', 'series'];
        if (empty($type) || !in_array($type, $allowed, true)) {
            return $uploads;
        }

        $subdir = '/mypco-series/' . $type;

        $uploads['subdir'] = $subdir;
        $uploads['path']   = $uploads['basedir'] . $subdir;
        $uploads['url']    = $uploads['baseurl'] . $subdir;

        return $uploads;
    }
}
