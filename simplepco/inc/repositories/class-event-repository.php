<?php
/**
 * Event Repository
 *
 * Encapsulates all data access for Planning Center Calendar events.
 * This is the Repository Pattern (SSP-style "Muscle") applied to the
 * SimplePCO architecture. Display code never calls the API directly —
 * it always goes through this repository.
 *
 * If the data source changes (e.g., from PCO API + transient cache to
 * a custom WordPress database table), only this class needs to change.
 *
 * @package SimplePCO
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SimplePCO_Event_Repository implements SimplePCO_Repository_Interface {

    /**
     * @var SimplePCO_API_Model
     */
    protected $api_model;

    /**
     * @var SimplePCO_Date_Helper
     */
    protected $date_helper;

    /**
     * Cache group for transient keys.
     *
     * @var string
     */
    const CACHE_GROUP = 'simplepco_events';

    /**
     * @param SimplePCO_API_Model       $api_model   The PCO API model.
     * @param SimplePCO_Date_Helper|null $date_helper Optional date helper instance.
     */
    public function __construct( SimplePCO_API_Model $api_model, $date_helper = null ) {
        $this->api_model   = $api_model;
        $this->date_helper = $date_helper;
    }

    /**
     * Find a single event by PCO event ID.
     *
     * @param string|int $id The PCO event instance ID.
     * @return array|null Normalized event data or null.
     */
    public function find( $id ) {
        $transient_key = self::CACHE_GROUP . '_single_' . $id;
        $cached = get_transient( $transient_key );

        if ( false !== $cached ) {
            return $cached;
        }

        $response = $this->api_model->get_data_with_caching(
            'calendar',
            "/v2/event_instances/{$id}",
            [ 'include' => 'event,event_times,tags' ],
            $transient_key,
            15 * MINUTE_IN_SECONDS
        );

        if ( ! $response || isset( $response['error'] ) ) {
            return null;
        }

        return $this->normalize_event( $response );
    }

    /**
     * Find upcoming events matching criteria.
     *
     * @param array $args {
     *     Optional. Query arguments.
     *
     *     @type int    $per_page   Number of events to fetch. Default 25.
     *     @type string $filter     PCO filter string. Default 'future'.
     *     @type array  $tag_ids    Array of tag IDs to filter by.
     *     @type string $order      Sort order. Default 'starts_at'.
     * }
     * @return array Array of normalized event data.
     */
    public function find_all( $args = [] ) {
        $defaults = [
            'per_page' => 25,
            'filter'   => 'future',
            'tag_ids'  => [],
            'order'    => 'starts_at',
        ];
        $args = array_merge( $defaults, $args );

        $params = [
            'per_page' => min( (int) $args['per_page'], 100 ),
            'filter'   => $args['filter'],
            'order'    => $args['order'],
            'include'  => 'event,event_times,tags',
        ];

        if ( ! empty( $args['tag_ids'] ) ) {
            $params['where[tag_id]'] = implode( ',', $args['tag_ids'] );
        }

        $cache_key = self::CACHE_GROUP . '_list_' . md5( wp_json_encode( $params ) );

        $response = $this->api_model->get_data_with_caching(
            'calendar',
            '/v2/event_instances',
            $params,
            $cache_key,
            15 * MINUTE_IN_SECONDS
        );

        if ( ! $response || isset( $response['error'] ) ) {
            return [];
        }

        return $this->normalize_events( $response );
    }

    /**
     * Get events grouped by date for month-view display.
     *
     * @param int $year  The year (e.g., 2026).
     * @param int $month The month (1-12).
     * @return array Events grouped by date key (Y-m-d).
     */
    public function find_by_month( $year, $month ) {
        $start = sprintf( '%04d-%02d-01', $year, $month );
        $end   = date( 'Y-m-t', strtotime( $start ) );

        $events = $this->find_all( [
            'per_page' => 100,
            'filter'   => 'after,before',
            'after'    => $start,
            'before'   => $end,
        ] );

        $grouped = [];
        foreach ( $events as $event ) {
            $date_key = substr( $event['starts_at'], 0, 10 );
            $grouped[ $date_key ][] = $event;
        }

        return $grouped;
    }

    /**
     * Get featured / upcoming events (e.g., next 5).
     *
     * @param int $count Number of featured events.
     * @return array
     */
    public function find_featured( $count = 5 ) {
        return $this->find_all( [
            'per_page' => $count,
            'filter'   => 'future',
        ] );
    }

    /**
     * Clear all event caches.
     *
     * @return void
     */
    public function clear_cache() {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '%_transient_' . self::CACHE_GROUP . '%'
            )
        );
    }

    /**
     * Normalize a single API event response into a flat array.
     *
     * @param array $response Raw API response for a single event.
     * @return array Normalized event data.
     */
    protected function normalize_event( $response ) {
        $data = isset( $response['data'] ) ? $response['data'] : $response;
        $attrs = isset( $data['attributes'] ) ? $data['attributes'] : [];

        return [
            'id'          => $data['id'] ?? '',
            'name'        => $attrs['name'] ?? '',
            'description' => $attrs['description'] ?? '',
            'starts_at'   => $attrs['starts_at'] ?? '',
            'ends_at'     => $attrs['ends_at'] ?? '',
            'location'    => $attrs['location'] ?? '',
            'image_url'   => $attrs['image_url'] ?? '',
            'all_day'     => $attrs['all_day_event'] ?? false,
            'recurrence'  => $attrs['recurrence'] ?? '',
            'tags'        => $this->extract_tags( $response ),
            'raw'         => $data,
        ];
    }

    /**
     * Normalize a list API response into an array of flat event arrays.
     *
     * @param array $response Raw API response with 'data' array.
     * @return array Array of normalized events.
     */
    protected function normalize_events( $response ) {
        if ( ! isset( $response['data'] ) || ! is_array( $response['data'] ) ) {
            return [];
        }

        $events = [];
        foreach ( $response['data'] as $item ) {
            $events[] = $this->normalize_event( [ 'data' => $item, 'included' => $response['included'] ?? [] ] );
        }

        return $events;
    }

    /**
     * Extract tags from an API response's included resources.
     *
     * @param array $response API response.
     * @return array Array of tag names.
     */
    protected function extract_tags( $response ) {
        $tags = [];
        if ( isset( $response['included'] ) ) {
            foreach ( $response['included'] as $included ) {
                if ( isset( $included['type'] ) && $included['type'] === 'Tag' ) {
                    $tags[] = $included['attributes']['name'] ?? '';
                }
            }
        }
        return array_filter( $tags );
    }
}
