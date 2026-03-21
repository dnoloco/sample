<?php
// includes/simplepco-api-model.php

class SimplePCO_API_Model {

    private $auth_header;
    private $base_url = "https://api.planningcenteronline.com";
    private $local_timezone;
    const CACHE_DURATION = 3600; // 1 hour

    /**
     * Create an API model with Basic Auth (client_id + secret_key).
     */
    public function __construct($client_id, $secret_key, $timezone_string = 'America/Chicago') {
        $credentials = base64_encode($client_id . ":" . $secret_key);
        $this->auth_header = "Basic " . $credentials;
        $this->local_timezone = new DateTimeZone($timezone_string);
    }

    /**
     * Create an API model using an OAuth 2.0 Bearer token.
     *
     * @param string $access_token  OAuth access token.
     * @param string $timezone_string WordPress timezone.
     * @return self
     */
    public static function from_oauth_token($access_token, $timezone_string = 'America/Chicago') {
        $instance = new self('', '', $timezone_string);
        $instance->auth_header = "Bearer " . $access_token;
        return $instance;
    }

    /**
     * Core function to fetch data from a specific PCO endpoint, handling caching.
     */
    public function get_data_with_caching($app_domain, $endpoint_path, $params, $transient_key, $cache_duration = null) {
        if ($cache_duration === null) {
            $cache_duration = self::CACHE_DURATION;
        }

        $output = get_transient($transient_key);

        if (false === $output) {
            $query_params = '?' . http_build_query($params);
            $url = $this->base_url . "/{$app_domain}{$endpoint_path}" . $query_params;

            $response = wp_remote_get($url, [
                'timeout' => 30,
                'headers' => [
                    'Authorization' => $this->auth_header,
                    'Accept'        => 'application/vnd.api+json',
                ],
            ]);

            if (is_wp_error($response)) {
                $error = 'API Connection Failed: ' . $response->get_error_message();
                error_log('[SimplePCO] API error for ' . $app_domain . $endpoint_path . ': ' . $error);
            } else {
                $status_code   = wp_remote_retrieve_response_code($response);
                $json_response = wp_remote_retrieve_body($response);
                $response_data = json_decode($json_response, true);

                if ($status_code >= 400) {
                    $error = isset($response_data['errors'][0]['detail'])
                        ? $response_data['errors'][0]['detail']
                        : 'API returned HTTP ' . $status_code;
                    error_log('[SimplePCO] API error for ' . $app_domain . $endpoint_path . ' (HTTP ' . $status_code . '): ' . $error);
                } elseif (isset($response_data['errors'])) {
                    $error = $response_data['errors'][0]['detail'] ?? 'Unknown API Error.';
                    error_log('[SimplePCO] API error for ' . $app_domain . $endpoint_path . ': ' . $error);
                } else {
                    $output = $response_data;
                }
            }

            if (isset($error)) {
                // Cache errors for a short time (60 seconds) to avoid hammering the API
                set_transient($transient_key, ['error' => $error], 60);
                return ['error' => $error];
            } else {
                // Save successful result to cache
                set_transient($transient_key, $output, $cache_duration);
            }
        }

        return $output;
    }

    /**
     * Test the connection by fetching the root organization data.
     */
    public function get_organization() {
        // The root of the services API returns organization info
        $endpoint = "/v2/";
        $key = 'simplepco_org_test';

        // We call your existing caching function
        // Services app domain, root endpoint, no extra params
        return $this->get_data_with_caching('services', $endpoint, [], $key);
    }

    /**
     * Retrieves the WordPress configured timezone string.
     * Used for display purposes.
     */
    public function get_timezone() {
        // This function MUST return a string (e.g., 'America/Chicago', 'UTC', or an offset)
        return get_option('timezone_string') ?: 'UTC';
    }

    /**
     * Activation Hook Helper: Clears any existing cache upon plugin activation.
     */
    public static function clear_all_cache() {
        global $wpdb;
        $sql = "DELETE FROM {$wpdb->options} WHERE option_name LIKE ('%_transient_pco_%');";
        $wpdb->query($sql);
    }

    /**
     * Fetches a list of upcoming service types.
     */
    public function get_service_types() {
        // Cache service types for a week as they change infrequently
        $key = 'simplepco_service_types';
        return $this->get_data_with_caching('services', '/v2/service_types', ['per_page' => 25], $key, 7 * DAY_IN_SECONDS);
    }

    /**
     * Fetches upcoming plans for a given service type ID.
     * Includes plan items (order of service) and team assignments (who is serving).
     * @param string $type_id The ID of the Service Type.
     * @param int $count The number of plans to fetch.
     */

    public function get_upcoming_plans($type_id, $count = 5) {
        $endpoint = "/v2/service_types/{$type_id}/plans";
        $params = [
            'filter' => 'future',
            'per_page' => $count,
            // --- ADD plan_times HERE ---
            'include' => 'plan_items,team_members,plan_times',
        ];
        // Cache plans briefly (15 minutes) since they change often
        $key = 'simplepco_plans_' . $type_id;
        return $this->get_data_with_caching('services', $endpoint, $params, $key, 15 * MINUTE_IN_SECONDS);
    }

    /**
     * Fetches plans for a given service type within a date range.
     * Used for reporting purposes.
     * @param string $type_id The ID of the Service Type.
     * @param string $start_date Start date in Y-m-d format.
     * @param string $end_date End date in Y-m-d format.
     */
    public function get_plans_by_date_range($type_id, $start_date, $end_date) {
        $endpoint = "/v2/service_types/{$type_id}/plans";

        // PCO's 'after' and 'before' filters are exclusive, so we need to adjust dates
        // to make the range inclusive (e.g., Jan 18 to Jan 18 should include Jan 18)
        $start_date_inclusive = date('Y-m-d', strtotime($start_date . ' -1 day'));
        $end_date_inclusive = date('Y-m-d', strtotime($end_date . ' +1 day'));

        $params = [
            'filter' => 'after,before',
            'per_page' => 100, // Fetch up to 100 plans
            'include' => 'team_members',
            'after' => $start_date_inclusive,
            'before' => $end_date_inclusive,
        ];

        // Use shorter cache for reports (5 minutes)
        $key = 'simplepco_plans_range_' . $type_id . '_' . md5($start_date . $end_date);
        return $this->get_data_with_caching('services', $endpoint, $params, $key, 5 * MINUTE_IN_SECONDS);
    }

    /**
     * Fetches a single plan with all details.
     */
    public function get_single_plan($plan_id) {
        $endpoint = "/v2/plans/{$plan_id}";
        // Now including plan_people which often carries name/team_name attributes directly
        $params = ['include' => 'plan_items,team_members,service_type,plan_people'];
        $key = 'simplepco_single_plan_' . $plan_id;
        return $this->get_data_with_caching('services', $endpoint, $params, $key, 15 * MINUTE_IN_SECONDS);
    }

    /**
     * Fetches details for a specific service type (used for time settings).
     */
    public function get_single_service_type($type_id) {
        $endpoint = "/v2/service_types/{$type_id}";
        $key = 'simplepco_service_type_' . $type_id;
        return $this->get_data_with_caching('services', $endpoint, [], $key, 7 * DAY_IN_SECONDS);
    }

    /**
     * Fetches all scheduled team members for a specific plan ID.
     * This is used as a fallback when the main plan include fails to return data.
     */
    public function get_plan_team_members($plan_id) {
        $endpoint = "/v2/plans/{$plan_id}/team_members";
        // Include person and team_position to get all member and position data
        $params = ['include' => 'person,team_position'];
        $key = 'simplepco_plan_teams_' . $plan_id;
        return $this->get_data_with_caching('services', $endpoint, $params, $key, 15 * MINUTE_IN_SECONDS);
    }

    /**
     * Fetches all Services Teams to create a Team ID -> Team Name map.
     */
    public function get_all_teams() {
        $endpoint = "/v2/teams";
        $key = 'simplepco_all_services_teams';
        // Cache this for a long time as team names rarely change
        return $this->get_data_with_caching('services', $endpoint, [], $key, 7 * DAY_IN_SECONDS);
    }

    /**
     * Fetches all members of a specific team (not plan-specific).
     * This gets the total team roster.
     */
    public function get_team_members($team_id) {
        $endpoint = "/v2/teams/{$team_id}/people";
        $params = ['per_page' => 100];
        $key = 'simplepco_team_members_' . $team_id;
        // Cache for a day since team membership changes infrequently
        return $this->get_data_with_caching('services', $endpoint, $params, $key, 1 * DAY_IN_SECONDS);
    }

    /**
     * Fetches all Team Positions for a service type to map IDs to names.
     */
    public function get_team_positions($service_type_id) {
        $endpoint = "/v2/service_types/{$service_type_id}/team_positions";
        $key = 'simplepco_team_positions_' . $service_type_id;
        // Cache for a week
        return $this->get_data_with_caching('services', $endpoint, [], $key, 7 * DAY_IN_SECONDS);
    }

    /**
     * Fetches phone numbers for a specific Person ID from the People App.
     */
    public function get_person_phone_numbers($person_id) {
        // Use 'people' domain for the People API
        $endpoint = "/v2/people/{$person_id}/phone_numbers";
        $key = 'simplepco_person_phones_' . $person_id;
        // Cache for a day
        return $this->get_data_with_caching('people', $endpoint, [], $key, 1 * DAY_IN_SECONDS);
    }

    /**
     * Fetches a person's schedules and includes assignments to find the position name.
     */
    public function get_person_schedules($person_id) {
        // Use 'services' app domain but target the person's schedule resource
        $endpoint = "/v2/people/{$person_id}/schedules";
        // We include assignments as the position data is often nested here
        $params = ['include' => 'assignments'];
        $key = 'simplepco_person_schedules_' . $person_id;
        // Cache for a short time
        return $this->get_data_with_caching('services', $endpoint, $params, $key, 1 * HOUR_IN_SECONDS);
    }

    // =========================================================================
    // Publishing API – Episodes, Series, Resources
    // =========================================================================

    /**
     * Fetches episodes from the Publishing API with pagination support.
     *
     * @param int    $per_page Number of episodes per page (max 100).
     * @param int    $offset   Offset for pagination.
     * @param string $order    Sort order: 'desc' or 'asc'.
     * @return array|false API response or false on error.
     */
    public function get_publishing_episodes($per_page = 25, $offset = 0, $order = 'desc') {
        $endpoint = '/v2/episodes';
        $params = [
            'per_page' => min($per_page, 100),
            'offset'   => $offset,
            'order'    => $order === 'asc' ? 'published_at' : '-published_at',
            'include'  => 'series,episode_resources',
        ];
        $key = 'simplepco_pub_episodes_' . md5($per_page . $offset . $order);
        return $this->get_data_with_caching('publishing', $endpoint, $params, $key, 5 * MINUTE_IN_SECONDS);
    }

    /**
     * Fetches a single episode with its resources (media attachments).
     *
     * @param string $episode_id The PCO episode ID.
     * @return array|false API response or false on error.
     */
    public function get_publishing_episode($episode_id) {
        $endpoint = "/v2/episodes/{$episode_id}";
        $params = [
            'include' => 'series,episode_resources',
        ];
        $key = 'simplepco_pub_episode_' . $episode_id;
        return $this->get_data_with_caching('publishing', $endpoint, $params, $key, 5 * MINUTE_IN_SECONDS);
    }

    /**
     * Fetches resources (video, audio, notes) for a specific episode.
     *
     * @param string $episode_id The PCO episode ID.
     * @return array|false API response or false on error.
     */
    public function get_publishing_episode_resources($episode_id) {
        $endpoint = "/v2/episodes/{$episode_id}/episode_resources";
        $key = 'simplepco_pub_ep_res_' . $episode_id;
        return $this->get_data_with_caching('publishing', $endpoint, [], $key, 5 * MINUTE_IN_SECONDS);
    }

    /**
     * Fetches speakerships for a specific episode.
     *
     * Speakerships link episodes to speakers via a relationship.
     * Each speakership has a relationship to a Speaker ID.
     *
     * @param string $episode_id The PCO episode ID.
     * @return array API response with speakership data.
     */
    public function get_episode_speakerships($episode_id) {
        $endpoint = "/v2/episodes/{$episode_id}/speakerships";
        $key = 'simplepco_pub_ep_spk_' . $episode_id;
        return $this->get_data_with_caching('publishing', $endpoint, [], $key, 15 * MINUTE_IN_SECONDS);
    }

    /**
     * Fetches all series from the Publishing API.
     *
     * @param int $per_page Number of series per page.
     * @param int $offset   Offset for pagination.
     * @return array|false API response or false on error.
     */
    /**
     * Fetches all speakers from the Publishing API.
     *
     * @param int $per_page Number of speakers per page.
     * @param int $offset   Offset for pagination.
     * @return array|false API response or false on error.
     */
    public function get_publishing_speakers($per_page = 100, $offset = 0) {
        $endpoint = '/v2/speakers';
        $params = [
            'per_page' => min($per_page, 100),
            'offset'   => $offset,
        ];
        $key = 'simplepco_pub_speakers_' . md5($per_page . $offset);
        return $this->get_data_with_caching('publishing', $endpoint, $params, $key, 5 * MINUTE_IN_SECONDS);
    }

    /**
     * Fetches all speakers from Publishing, handling pagination automatically.
     *
     * @return array All speaker data, keyed by speaker ID.
     */
    public function get_all_publishing_speakers() {
        $all_speakers = [];
        $offset = 0;
        $per_page = 100;

        do {
            $response = $this->get_publishing_speakers($per_page, $offset);

            if (!$response || isset($response['error'])) {
                return $all_speakers;
            }

            if (isset($response['data'])) {
                foreach ($response['data'] as $speaker) {
                    $all_speakers[$speaker['id']] = $speaker;
                }
            }

            $total = isset($response['meta']['total_count']) ? (int) $response['meta']['total_count'] : 0;
            $offset += $per_page;

        } while ($offset < $total && !empty($response['data']));

        return $all_speakers;
    }

    public function get_publishing_series($per_page = 100, $offset = 0) {
        $endpoint = '/v2/series';
        $params = [
            'per_page' => min($per_page, 100),
            'offset'   => $offset,
        ];
        $key = 'simplepco_pub_series_' . md5($per_page . $offset);
        return $this->get_data_with_caching('publishing', $endpoint, $params, $key, 15 * MINUTE_IN_SECONDS);
    }

    /**
     * Fetches all episodes from Publishing, handling pagination automatically.
     *
     * @return array All episode data items, or an error array.
     */
    public function get_all_publishing_episodes() {
        $all_episodes = [];
        $all_included = [];
        $offset = 0;
        $per_page = 100;

        do {
            $response = $this->get_publishing_episodes($per_page, $offset);

            if (!$response || isset($response['error'])) {
                return $response ?: ['error' => 'Failed to fetch episodes.'];
            }

            if (isset($response['data'])) {
                $all_episodes = array_merge($all_episodes, $response['data']);
            }

            if (isset($response['included'])) {
                $all_included = array_merge($all_included, $response['included']);
            }

            $total = isset($response['meta']['total_count']) ? (int) $response['meta']['total_count'] : 0;
            $offset += $per_page;

        } while ($offset < $total && !empty($response['data']));

        return [
            'data'     => $all_episodes,
            'included' => $all_included,
            'meta'     => ['total_count' => count($all_episodes)],
        ];
    }

    /**
     * Resolve a PCO file's actual download URL via the /open action.
     *
     * Makes an authenticated request that returns a 302 redirect to the
     * signed file URL (e.g. S3). Returns the redirect URL without following it.
     *
     * @param string $app_domain  e.g. 'publishing'.
     * @param string $endpoint_path e.g. '/v2/episodes/123/sermon_audio/open'.
     * @return string|false The direct download URL, or false on failure.
     */
    public function get_file_redirect_url($app_domain, $endpoint_path) {
        $url = $this->base_url . "/{$app_domain}{$endpoint_path}";

        // Try POST first (PCO action endpoints typically use POST)
        $response = wp_remote_post($url, [
            'timeout'     => 30,
            'redirection' => 0,
            'headers'     => [
                'Authorization' => $this->auth_header,
            ],
        ]);

        if (!is_wp_error($response)) {
            $location = wp_remote_retrieve_header($response, 'location');
            if ($location) {
                return $location;
            }
            $status = wp_remote_retrieve_response_code($response);
            $body   = wp_remote_retrieve_body($response);
            error_log('[SimplePCO] POST ' . $url . ' => HTTP ' . $status);

            // Check if the response body contains a URL (some endpoints return JSON with the URL)
            if ($body) {
                $data = json_decode($body, true);
                if (!empty($data['data']['attributes']['url'])) {
                    return $data['data']['attributes']['url'];
                }
                // Log truncated body for debugging
                error_log('[SimplePCO] POST response body: ' . substr($body, 0, 500));
            }
        } else {
            error_log('[SimplePCO] POST request failed: ' . $response->get_error_message());
        }

        // Fall back to GET
        $response = wp_remote_get($url, [
            'timeout'     => 30,
            'redirection' => 0,
            'headers'     => [
                'Authorization' => $this->auth_header,
            ],
        ]);

        if (is_wp_error($response)) {
            error_log('[SimplePCO] GET request failed: ' . $response->get_error_message());
            return false;
        }

        $status   = wp_remote_retrieve_response_code($response);
        $location = wp_remote_retrieve_header($response, 'location');

        if ($location) {
            return $location;
        }

        // Check if the response body contains a URL
        $body = wp_remote_retrieve_body($response);
        if ($body) {
            $data = json_decode($body, true);
            if (!empty($data['data']['attributes']['url'])) {
                return $data['data']['attributes']['url'];
            }
            error_log('[SimplePCO] GET ' . $url . ' => HTTP ' . $status . ' body: ' . substr($body, 0, 500));
        } else {
            error_log('[SimplePCO] GET ' . $url . ' => HTTP ' . $status . ' (no body)');
        }

        return false;
    }
}
