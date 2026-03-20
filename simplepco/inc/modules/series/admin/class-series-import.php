<?php
/**
 * Series Import Component
 *
 * Handles importing messages (episodes) and series from Planning Center
 * Publishing into the WordPress simplepco_message and simplepco_series data model.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SimplePCO_Series_Import {

    private $loader;
    private $api_model;

    /**
     * Option key used to store the PCO episode ID on each imported post.
     * This prevents duplicate imports and allows re-sync.
     */
    const PCO_EPISODE_META_KEY  = '_simplepco_pco_episode_id';
    const PCO_SERIES_META_KEY   = '_simplepco_pco_series_id';
    const IMPORT_LOG_OPTION     = 'simplepco_import_log';
    const IMPORT_CACHE_KEY      = 'simplepco_import_episode_cache';

    public function __construct($loader, $api_model) {
        $this->loader    = $loader;
        $this->api_model = $api_model;
    }

    /**
     * Register hooks for the import functionality.
     */
    public function init() {
        $this->loader->add_action('wp_ajax_simplepco_import_fetch_episodes', $this, 'ajax_fetch_episodes');
        $this->loader->add_action('wp_ajax_simplepco_import_run', $this, 'ajax_run_import');
    }

    // =========================================================================
    // Settings Tab – Render
    // =========================================================================

    /**
     * Render the Import tab content on the Messages Settings page.
     */
    public function render_import_tab() {
        $names = SimplePCO_Series_Module::get_custom_labels();
        $last_import = get_option(self::IMPORT_LOG_OPTION, []);
        ?>
        <div class="simplepco-import-wrap">
            <h2><?php printf(esc_html__('Import %s from Planning Center', 'simplepco'), esc_html($names['message_plural'])); ?></h2>

            <p class="description">
                <?php printf(
                    esc_html__('Fetch episodes from your Planning Center Publishing account and import them as %s in WordPress. Series, media, and descriptions are mapped automatically.', 'simplepco'),
                    esc_html(strtolower($names['message_plural']))
                ); ?>
            </p>

            <?php if (!$this->api_model) : ?>
                <div class="notice notice-error inline" style="margin:15px 0;">
                    <p><?php esc_html_e('Planning Center API credentials are not configured. Please set them up on the Settings page first.', 'simplepco'); ?></p>
                </div>
                <?php return; ?>
            <?php endif; ?>

            <?php if (!empty($last_import)) : ?>
                <div class="simplepco-import-last-run" style="margin:15px 0;padding:10px 15px;background:#f0f6fc;border-left:4px solid #2271b1;">
                    <strong><?php esc_html_e('Last Import:', 'simplepco'); ?></strong>
                    <?php
                    $date = isset($last_import['date']) ? $last_import['date'] : '';
                    $count = isset($last_import['count']) ? (int) $last_import['count'] : 0;
                    $skipped = isset($last_import['skipped']) ? (int) $last_import['skipped'] : 0;
                    printf(
                        esc_html__('%1$s — %2$d imported, %3$d skipped (already existed)', 'simplepco'),
                        esc_html($date),
                        $count,
                        $skipped
                    );
                    ?>
                </div>
            <?php endif; ?>

            <!-- Step 1: Fetch -->
            <div id="simplepco-import-step-fetch" class="simplepco-import-step">
                <h3><?php esc_html_e('Step 1: Fetch Episodes', 'simplepco'); ?></h3>
                <p class="description"><?php esc_html_e('Connect to Planning Center Publishing and retrieve available episodes.', 'simplepco'); ?></p>
                <p>
                    <button type="button" id="simplepco-import-fetch-btn" class="button button-primary">
                        <?php esc_html_e('Fetch from Planning Center', 'simplepco'); ?>
                    </button>
                    <span id="simplepco-import-fetch-status" class="simplepco-import-status"></span>
                </p>
            </div>

            <!-- Step 2: Preview & Import -->
            <div id="simplepco-import-step-preview" class="simplepco-import-step" style="display:none;">
                <h3><?php esc_html_e('Step 2: Review & Import', 'simplepco'); ?></h3>
                <p class="description">
                    <?php printf(
                        esc_html__('Select which episodes to import as %s. Episodes already imported will be skipped automatically.', 'simplepco'),
                        esc_html(strtolower($names['message_plural']))
                    ); ?>
                </p>

                <div id="simplepco-import-summary" style="margin:10px 0;"></div>

                <table class="wp-list-table widefat fixed striped" id="simplepco-import-table">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="simplepco-import-select-all" checked />
                            </td>
                            <th class="manage-column"><?php esc_html_e('Title', 'simplepco'); ?></th>
                            <th class="manage-column"><?php echo esc_html($names['speaker_singular']); ?></th>
                            <th class="manage-column"><?php echo esc_html($names['series_singular']); ?></th>
                            <th class="manage-column"><?php esc_html_e('Date', 'simplepco'); ?></th>
                            <th class="manage-column"><?php esc_html_e('Media', 'simplepco'); ?></th>
                            <th class="manage-column"><?php esc_html_e('Status', 'simplepco'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="simplepco-import-tbody"></tbody>
                </table>

                <p style="margin-top:15px;">
                    <button type="button" id="simplepco-import-run-btn" class="button button-primary">
                        <?php printf(esc_html__('Import Selected %s', 'simplepco'), esc_html($names['message_plural'])); ?>
                    </button>
                    <span id="simplepco-import-run-status" class="simplepco-import-status"></span>
                </p>
            </div>

            <!-- Step 3: Results -->
            <div id="simplepco-import-step-results" class="simplepco-import-step" style="display:none;">
                <h3><?php esc_html_e('Import Complete', 'simplepco'); ?></h3>
                <div id="simplepco-import-results"></div>
            </div>

            <!-- Field Mapping Reference -->
            <div class="simplepco-import-mapping" style="margin-top:30px;">
                <h3><?php esc_html_e('Field Mapping', 'simplepco'); ?></h3>
                <table class="widefat fixed" style="max-width:600px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Planning Center Field', 'simplepco'); ?></th>
                            <th><?php esc_html_e('WordPress Field', 'simplepco'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><?php esc_html_e('Episode Title', 'simplepco'); ?></td><td><?php echo esc_html($names['message_singular']); ?> Title</td></tr>
                        <tr><td><?php esc_html_e('Episode Description', 'simplepco'); ?></td><td><?php echo esc_html($names['message_singular']); ?> Description + Content</td></tr>
                        <tr><td><code>published_to_library_at</code></td><td><?php echo esc_html($names['message_singular']); ?> Date</td></tr>
                        <tr><td><code>speaker</code></td><td><?php echo esc_html($names['speaker_singular']); ?></td></tr>
                        <tr><td><?php esc_html_e('Series', 'simplepco'); ?></td><td><?php echo esc_html($names['series_singular']); ?> Taxonomy</td></tr>
                        <tr><td><?php esc_html_e('Series Artwork', 'simplepco'); ?></td><td><?php echo esc_html($names['series_singular']); ?> Image</td></tr>
                        <tr><td><code>art</code> / <code>library_video_thumbnail_url</code></td><td><?php echo esc_html($names['message_singular']); ?> Image</td></tr>
                        <tr><td><code>library_video_url</code></td><td><?php echo esc_html($names['message_singular']); ?> Video URL</td></tr>
                        <tr><td><code>library_video_embed_code</code></td><td><?php echo esc_html($names['message_singular']); ?> Video Embed</td></tr>
                        <tr><td><code>library_audio_url</code> / <code>sermon_audio</code></td><td><?php echo esc_html($names['message_singular']); ?> Audio URL</td></tr>
                        <tr><td><code>library_video_thumbnail_url</code></td><td><?php echo esc_html($names['message_singular']); ?> Video Thumbnail</td></tr>
                        <tr><td><?php esc_html_e('Episode Resources (URL)', 'simplepco'); ?></td><td><?php echo esc_html($names['message_singular']); ?> Files</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    // =========================================================================
    // AJAX: Fetch Episodes from PCO
    // =========================================================================

    /**
     * AJAX handler to fetch episodes from Planning Center Publishing.
     */
    public function ajax_fetch_episodes() {
        check_ajax_referer('simplepco_import_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'simplepco')]);
        }

        if (!$this->api_model) {
            wp_send_json_error(['message' => __('API credentials not configured.', 'simplepco')]);
        }

        // Fetch all episodes with series included
        $response = $this->api_model->get_all_publishing_episodes();

        if (isset($response['error'])) {
            wp_send_json_error(['message' => $response['error']]);
        }

        if (empty($response['data'])) {
            wp_send_json_error(['message' => __('No episodes found in Planning Center Publishing.', 'simplepco')]);
        }

        // Fetch all speakers to build a speaker ID → name map
        $speakers_raw = $this->api_model->get_all_publishing_speakers();
        $speakers_map = []; // speaker ID → name
        foreach ($speakers_raw as $spk_id => $spk) {
            $sa = $spk['attributes'] ?? [];
            if (!empty($sa['full_name'])) {
                $speakers_map[$spk_id] = $sa['full_name'];
            } elseif (!empty($sa['first_name']) || !empty($sa['last_name'])) {
                $speakers_map[$spk_id] = trim(($sa['first_name'] ?? '') . ' ' . ($sa['last_name'] ?? ''));
            } else {
                $speakers_map[$spk_id] = $sa['name'] ?? '';
            }
        }

        // Build a series lookup and episode resources lookup from included data
        $series_map = [];
        $resources_map = [];    // episode_resource ID → full included item
        if (!empty($response['included'])) {
            foreach ($response['included'] as $included) {
                if ($included['type'] === 'Series') {
                    $series_map[$included['id']] = $included['attributes'];
                } elseif ($included['type'] === 'EpisodeResource') {
                    $resources_map[$included['id']] = $included;
                }
            }
        }

        // Get already-imported episode IDs
        $imported_ids = $this->get_imported_episode_ids();

        // Format episodes for the frontend
        $episodes = [];
        foreach ($response['data'] as $episode) {
            $attrs = $episode['attributes'];
            $ep_id = $episode['id'];

            // Resolve series info
            $series_name = '';
            $series_id   = '';
            if (!empty($episode['relationships']['series']['data'])) {
                $series_id = $episode['relationships']['series']['data']['id'];
                if (isset($series_map[$series_id])) {
                    $series_name = $series_map[$series_id]['title'] ?? '';
                }
            }

            // Resolve speaker name via speakerships endpoint
            $speaker_name = '';
            $speakerships_resp = $this->api_model->get_episode_speakerships($ep_id);
            if (!empty($speakerships_resp['data'])) {
                foreach ($speakerships_resp['data'] as $ss) {
                    $spk_id = $ss['relationships']['speaker']['data']['id'] ?? '';
                    if ($spk_id && isset($speakers_map[$spk_id])) {
                        $speaker_name = $speakers_map[$spk_id];
                        break; // Use the first speaker
                    }
                }
            }

            // Determine media availability
            $has_video       = !empty($attrs['library_video_url']) || !empty($attrs['library_video_embed_code']);
            $has_audio       = !empty($attrs['library_audio_url']);
            $has_art         = !empty($attrs['art']['id']);

            // Check sermon_audio File object for actual audio content
            if (!$has_audio && !empty($attrs['sermon_audio']['id'])) {
                $sa_attrs = $attrs['sermon_audio']['attributes'] ?? [];
                if (!empty($sa_attrs['signed_identifier']) || !empty($sa_attrs['variants']) || !empty($sa_attrs['url'])) {
                    $has_audio = true;
                }
            }

            // Check for episode_resources with a URL
            $has_resources = false;
            if (!empty($episode['relationships']['episode_resources']['data'])) {
                foreach ($episode['relationships']['episode_resources']['data'] as $res_ref) {
                    $res_id = $res_ref['id'] ?? '';
                    if (isset($resources_map[$res_id])) {
                        $res_url = $resources_map[$res_id]['attributes']['url'] ?? '';
                        if (!empty($res_url)) {
                            $has_resources = true;
                            break;
                        }
                    }
                }
            }

            // Parse the published date
            $published_date = '';
            if (!empty($attrs['published_to_library_at'])) {
                $published_date = date('Y-m-d', strtotime($attrs['published_to_library_at']));
            } elseif (!empty($attrs['published_live_at'])) {
                $published_date = date('Y-m-d', strtotime($attrs['published_live_at']));
            }

            $episodes[] = [
                'id'               => $ep_id,
                'title'            => $attrs['title'] ?? '',
                'description'      => $attrs['description'] ?? '',
                'published_date'   => $published_date,
                'series_id'        => $series_id,
                'series_name'      => $series_name,
                'speaker_name'     => $speaker_name,
                'has_video'        => $has_video,
                'has_audio'        => $has_audio,
                'has_art'          => $has_art,
                'has_resources'    => $has_resources,
                'already_imported' => in_array($ep_id, $imported_ids, true),
            ];
        }

        // Cache the raw API response so the import step can reuse it
        // without making additional API calls (avoids timeouts).
        $cache = [];
        foreach ($response['data'] as $episode) {
            $cache[$episode['id']] = $episode;
        }
        // Also cache the included series data keyed by ID
        $included_cache = [];
        if (!empty($response['included'])) {
            foreach ($response['included'] as $inc) {
                $included_cache[$inc['type']][$inc['id']] = $inc;
            }
        }
        set_transient(self::IMPORT_CACHE_KEY, [
            'episodes'     => $cache,
            'included'     => $included_cache,
            'speakers_map' => $speakers_map,
        ], 15 * MINUTE_IN_SECONDS);

        wp_send_json_success([
            'episodes'    => $episodes,
            'series'      => $series_map,
            'total_count' => count($episodes),
        ]);
    }

    // =========================================================================
    // AJAX: Run Import
    // =========================================================================

    /**
     * AJAX handler to import selected episodes as WordPress posts.
     */
    public function ajax_run_import() {
        check_ajax_referer('simplepco_import_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'simplepco')]);
        }

        $episode_ids = isset($_POST['episode_ids']) ? array_map('sanitize_text_field', (array) $_POST['episode_ids']) : [];

        if (empty($episode_ids)) {
            wp_send_json_error(['message' => __('No episodes selected for import.', 'simplepco')]);
        }

        // Load the cached episode data from the fetch step
        $cache = get_transient(self::IMPORT_CACHE_KEY);
        if (empty($cache) || empty($cache['episodes'])) {
            wp_send_json_error(['message' => __('Episode data has expired. Please click "Fetch from Planning Center" again.', 'simplepco')]);
        }

        $cached_episodes     = $cache['episodes'];
        $cached_included     = $cache['included'];
        $cached_speakers     = isset($cache['speakers_map']) ? $cache['speakers_map'] : [];

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $results  = [];

        foreach ($episode_ids as $episode_id) {
            // Skip if already imported
            if ($this->get_post_by_episode_id($episode_id)) {
                $skipped++;
                $results[] = [
                    'id'     => $episode_id,
                    'status' => 'skipped',
                    'message' => __('Already imported', 'simplepco'),
                ];
                continue;
            }

            // Look up episode from the cached fetch data
            if (!isset($cached_episodes[$episode_id])) {
                $errors[] = sprintf(__('Episode %s not found in cached data.', 'simplepco'), $episode_id);
                $results[] = [
                    'id'      => $episode_id,
                    'status'  => 'error',
                    'message' => __('Episode not found in cached data. Try fetching again.', 'simplepco'),
                ];
                continue;
            }

            $ep_data = [
                'data'     => $cached_episodes[$episode_id],
                'included' => [],
            ];

            // Attach the relevant included data (series + episode resources)
            if (!empty($cached_episodes[$episode_id]['relationships']['series']['data']['id'])) {
                $sid = $cached_episodes[$episode_id]['relationships']['series']['data']['id'];
                if (isset($cached_included['Series'][$sid])) {
                    $ep_data['included'][] = $cached_included['Series'][$sid];
                }
            }
            if (!empty($cached_episodes[$episode_id]['relationships']['episode_resources']['data'])) {
                foreach ($cached_episodes[$episode_id]['relationships']['episode_resources']['data'] as $res_ref) {
                    $rid = $res_ref['id'] ?? '';
                    if ($rid && isset($cached_included['EpisodeResource'][$rid])) {
                        $ep_data['included'][] = $cached_included['EpisodeResource'][$rid];
                    }
                }
            }

            // Resolve speaker name via speakerships endpoint
            $speaker_name = '';
            $speakerships_resp = $this->api_model->get_episode_speakerships($episode_id);
            if (!empty($speakerships_resp['data'])) {
                foreach ($speakerships_resp['data'] as $ss) {
                    $spk_id = $ss['relationships']['speaker']['data']['id'] ?? '';
                    if ($spk_id && isset($cached_speakers[$spk_id])) {
                        $speaker_name = $cached_speakers[$spk_id];
                        break;
                    }
                }
            }
            $ep_data['speaker_name'] = $speaker_name;

            $result = $this->import_episode($ep_data);

            if (is_wp_error($result)) {
                $errors[] = $result->get_error_message();
                $results[] = [
                    'id'      => $episode_id,
                    'status'  => 'error',
                    'message' => $result->get_error_message(),
                ];
            } else {
                $imported++;
                $results[] = [
                    'id'      => $episode_id,
                    'status'  => 'imported',
                    'post_id' => $result,
                    'message' => __('Imported successfully', 'simplepco'),
                ];
            }
        }

        // Log the import
        update_option(self::IMPORT_LOG_OPTION, [
            'date'    => current_time('M j, Y g:i A'),
            'count'   => $imported,
            'skipped' => $skipped,
            'errors'  => count($errors),
        ]);

        wp_send_json_success([
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors,
            'results'  => $results,
        ]);
    }

    // =========================================================================
    // Import Logic – Episode to Post Mapping
    // =========================================================================

    /**
     * Import a single episode from PCO into a simplepco_message post.
     *
     * @param array $ep_response Full episode API response with included data.
     * @return int|WP_Error The created post ID, or WP_Error on failure.
     */
    private function import_episode($ep_response) {
        $episode = $ep_response['data'];
        $attrs   = $episode['attributes'];
        $ep_id   = $episode['id'];

        // Build included resources lookup
        $included_map = [];
        if (!empty($ep_response['included'])) {
            foreach ($ep_response['included'] as $inc) {
                $included_map[$inc['type']][$inc['id']] = $inc;
            }
        }

        // --- Episode fields ---
        $title       = $attrs['title'] ?? '';
        $description = $attrs['description'] ?? '';
        $published   = $attrs['published_to_library_at'] ?? ($attrs['published_live_at'] ?? '');

        // Resolve artwork from the art File object
        $artwork_url = '';
        if (!empty($attrs['art']['id'])) {
            $art_attrs = $attrs['art']['attributes'] ?? [];
            // PCO File objects: check for variants (sized URLs) first, then direct URL
            if (!empty($art_attrs['variants'])) {
                // variants is typically an array or object with size keys → URLs
                $variants = $art_attrs['variants'];
                if (is_array($variants)) {
                    // Prefer original or largest variant
                    $artwork_url = $variants['original'] ?? ($variants['large'] ?? reset($variants));
                }
            }
            if (!$artwork_url && !empty($art_attrs['url'])) {
                $artwork_url = $art_attrs['url'];
            }
        }
        if (!$artwork_url && !empty($attrs['library_video_thumbnail_url'])) {
            $artwork_url = $attrs['library_video_thumbnail_url'];
        }

        $message_date = '';
        if ($published) {
            $message_date = date('Y-m-d', strtotime($published));
        }

        // --- Create the post ---
        $post_data = [
            'post_type'    => 'simplepco_message',
            'post_title'   => sanitize_text_field($title),
            'post_content' => wp_kses_post($description),
            'post_status'  => 'publish',
            'post_date'    => $published ? date('Y-m-d H:i:s', strtotime($published)) : '',
        ];

        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // --- Store PCO episode ID for dedup ---
        update_post_meta($post_id, self::PCO_EPISODE_META_KEY, $ep_id);

        // --- Message meta ---
        if ($description) {
            update_post_meta($post_id, '_simplepco_message_description', sanitize_textarea_field($description));
        }
        if ($message_date) {
            update_post_meta($post_id, '_simplepco_message_date', $message_date);
        }
        if ($artwork_url) {
            update_post_meta($post_id, '_simplepco_message_image', esc_url_raw($artwork_url));
        }

        // --- Extract media from episode attributes ---
        $this->map_episode_resources($post_id, $ep_id, $included_map, $attrs);

        // --- Map episode_resources (URL-type files) from cached included data ---
        $this->map_episode_resource_files($post_id, $ep_id, $included_map);

        // --- Map speaker ---
        $speaker_name = isset($ep_response['speaker_name']) ? $ep_response['speaker_name'] : '';
        $this->map_episode_speaker($post_id, $speaker_name);

        // --- Map series ---
        $this->map_episode_series($post_id, $episode, $included_map);

        return $post_id;
    }

    /**
     * Map episode resources (video/audio) to post meta.
     *
     * Resources may come from the included data or directly from episode attributes.
     */
    private function map_episode_resources($post_id, $ep_id, $included_map, $attrs) {
        $video_url = '';
        $audio_url = '';
        $video_embed = '';

        // Check episode attributes for media
        if (!empty($attrs['library_video_url'])) {
            $video_url = $attrs['library_video_url'];
        }
        if (!empty($attrs['library_video_embed_code'])) {
            $video_embed = $attrs['library_video_embed_code'];
        }
        if (!empty($attrs['library_audio_url'])) {
            $audio_url = $attrs['library_audio_url'];
        }

        // Check sermon_audio File object (has type, id, attributes with name/signed_identifier/source)
        if (!empty($attrs['sermon_audio']['id'])) {
            $sa_attrs = $attrs['sermon_audio']['attributes'] ?? [];

            // Check for variants (sized/formatted URLs)
            if (!$audio_url && !empty($sa_attrs['variants'])) {
                $variants = $sa_attrs['variants'];
                if (is_array($variants)) {
                    $audio_url = $variants['original'] ?? reset($variants);
                }
            }
            // Check for direct url attribute
            if (!$audio_url && !empty($sa_attrs['url'])) {
                $audio_url = $sa_attrs['url'];
            }

            // Store sermon audio metadata for reference even if no direct URL
            $sa_name = $sa_attrs['name'] ?? '';
            $sa_source = $sa_attrs['source'] ?? '';
            if ($sa_name) {
                update_post_meta($post_id, '_simplepco_sermon_audio_name', sanitize_text_field($sa_name));
            }
            if ($sa_source) {
                update_post_meta($post_id, '_simplepco_sermon_audio_source', sanitize_text_field($sa_source));
            }
            update_post_meta($post_id, '_simplepco_sermon_audio_pco_id', sanitize_text_field($attrs['sermon_audio']['id']));
        }

        // Fall back to included EpisodeResource items
        if (isset($included_map['EpisodeResource'])) {
            foreach ($included_map['EpisodeResource'] as $resource) {
                $res_attrs = $resource['attributes'];
                $res_type  = strtolower($res_attrs['resource_type'] ?? ($res_attrs['kind'] ?? ''));
                $res_url   = $res_attrs['url'] ?? ($res_attrs['file_url'] ?? '');

                if (empty($res_url)) {
                    continue;
                }

                if (!$video_url && in_array($res_type, ['video', 'embed', 'youtube', 'vimeo'], true)) {
                    $video_url = $res_url;
                } elseif (!$audio_url && in_array($res_type, ['audio', 'podcast'], true)) {
                    $audio_url = $res_url;
                }
            }
        }

        if ($video_url) {
            update_post_meta($post_id, '_simplepco_message_video', esc_url_raw($video_url));
        }
        if ($video_embed) {
            update_post_meta($post_id, '_simplepco_message_video_embed', wp_kses_post($video_embed));
        }
        if ($audio_url) {
            update_post_meta($post_id, '_simplepco_message_audio', esc_url_raw($audio_url));
        }

        // Store video thumbnail if available
        if (!empty($attrs['library_video_thumbnail_url'])) {
            update_post_meta($post_id, '_simplepco_message_video_thumbnail', esc_url_raw($attrs['library_video_thumbnail_url']));
        }
    }

    /**
     * Map EpisodeResource items (from cached included data) that have a URL
     * into the _simplepco_message_files meta.
     *
     * Only resources with a non-empty url attribute are imported as files.
     */
    private function map_episode_resource_files($post_id, $ep_id, $included_map) {
        if (!isset($included_map['EpisodeResource'])) {
            return;
        }

        $files = [];

        foreach ($included_map['EpisodeResource'] as $resource) {
            $res_attrs = $resource['attributes'] ?? [];
            $res_url   = $res_attrs['url'] ?? '';

            // Only import resources that have a URL
            if (empty($res_url)) {
                continue;
            }

            $res_name = $res_attrs['title'] ?? ($res_attrs['name'] ?? '');

            $files[] = [
                'name' => sanitize_text_field($res_name),
                'url'  => esc_url_raw($res_url),
            ];
        }

        if (!empty($files)) {
            update_post_meta($post_id, '_simplepco_message_files', $files);
        }
    }

    /**
     * Map the episode's series relationship to the simplepco_series taxonomy.
     */
    private function map_episode_series($post_id, $episode, $included_map) {
        if (empty($episode['relationships']['series']['data'])) {
            return;
        }

        $series_pco_id = $episode['relationships']['series']['data']['id'];
        $series_data   = $included_map['Series'][$series_pco_id] ?? null;

        if (!$series_data) {
            return;
        }

        $series_title = $series_data['attributes']['title'] ?? '';
        if (empty($series_title)) {
            return;
        }

        // Find or create the taxonomy term
        $term = get_term_by('name', $series_title, 'simplepco_series');

        if (!$term) {
            $series_desc = $series_data['attributes']['description'] ?? '';
            $result = wp_insert_term($series_title, 'simplepco_series', [
                'description' => sanitize_textarea_field($series_desc),
            ]);

            if (is_wp_error($result)) {
                return;
            }

            $term_id = $result['term_id'];

            // Store PCO series ID on the term
            update_term_meta($term_id, self::PCO_SERIES_META_KEY, $series_pco_id);

            // Store series artwork
            $series_artwork = $series_data['attributes']['artwork_url']
                ?? ($series_data['attributes']['image_url'] ?? '');
            if ($series_artwork) {
                update_term_meta($term_id, '_simplepco_series_image', esc_url_raw($series_artwork));
            }

            // Store series start date if available
            $series_date = $series_data['attributes']['created_at'] ?? '';
            if ($series_date) {
                update_term_meta($term_id, '_simplepco_series_start_date', date('Y-m-d', strtotime($series_date)));
            }
        } else {
            $term_id = $term->term_id;
        }

        wp_set_post_terms($post_id, [$term_id], 'simplepco_series');
    }

    /**
     * Map the episode's speaker to an simplepco_speaker post and link to the message.
     *
     * Finds or creates a matching simplepco_speaker post by name and stores
     * the speaker ID as _simplepco_speaker_id meta on the message post.
     *
     * @param int    $post_id      The message post ID.
     * @param string $speaker_name The resolved speaker name.
     */
    private function map_episode_speaker($post_id, $speaker_name) {
        $speaker_name = trim($speaker_name);

        if (empty($speaker_name)) {
            return;
        }

        // Look for an existing speaker post with this name
        $existing = get_posts([
            'post_type'      => 'simplepco_speaker',
            'posts_per_page' => 1,
            'title'          => $speaker_name,
            'post_status'    => 'publish',
            'fields'         => 'ids',
        ]);

        if (!empty($existing)) {
            $speaker_id = $existing[0];
        } else {
            // Create a new speaker post
            $speaker_id = wp_insert_post([
                'post_type'   => 'simplepco_speaker',
                'post_title'  => sanitize_text_field($speaker_name),
                'post_status' => 'publish',
            ], true);

            if (is_wp_error($speaker_id)) {
                return;
            }
        }

        update_post_meta($post_id, '_simplepco_speaker_id', $speaker_id);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Get all PCO episode IDs that have already been imported.
     *
     * @return array Array of episode ID strings.
     */
    private function get_imported_episode_ids() {
        global $wpdb;

        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = %s
             AND p.post_type = 'simplepco_message'
             AND p.post_status != 'trash'",
            self::PCO_EPISODE_META_KEY
        ));

        return $ids ?: [];
    }

    /**
     * Find an existing WP post by its PCO episode ID.
     *
     * @param string $episode_id The PCO episode ID.
     * @return int|false Post ID, or false if not found.
     */
    private function get_post_by_episode_id($episode_id) {
        global $wpdb;

        $post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT pm.post_id FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = %s
             AND pm.meta_value = %s
             AND p.post_type = 'simplepco_message'
             AND p.post_status != 'trash'
             LIMIT 1",
            self::PCO_EPISODE_META_KEY,
            $episode_id
        ));

        return $post_id ? (int) $post_id : false;
    }
}
