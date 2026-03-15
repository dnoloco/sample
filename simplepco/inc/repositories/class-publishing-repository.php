<?php
/**
 * Publishing Repository
 *
 * Encapsulates all data access for Planning Center Publishing
 * (episodes, series, speakers — the "Messages" module).
 *
 * Part of the Repository Pattern ("Muscle") in the blended architecture.
 *
 * @package SimplePCO
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SimplePCO_Publishing_Repository implements SimplePCO_Repository_Interface {

    /**
     * @var SimplePCO_API_Model
     */
    protected $api_model;

    const CACHE_GROUP = 'simplepco_publishing';

    /**
     * @param SimplePCO_API_Model $api_model The PCO API model.
     */
    public function __construct( SimplePCO_API_Model $api_model ) {
        $this->api_model = $api_model;
    }

    /**
     * Find a single episode by PCO episode ID.
     *
     * @param string|int $id The episode ID.
     * @return array|null Normalized episode or null.
     */
    public function find( $id ) {
        $response = $this->api_model->get_publishing_episode( $id );

        if ( ! $response || isset( $response['error'] ) ) {
            return null;
        }

        return $this->normalize_episode( $response );
    }

    /**
     * Find episodes with pagination.
     *
     * @param array $args {
     *     @type int    $per_page Default 25.
     *     @type int    $offset   Default 0.
     *     @type string $order    'asc' or 'desc'. Default 'desc'.
     * }
     * @return array Array of normalized episodes.
     */
    public function find_all( $args = [] ) {
        $defaults = [
            'per_page' => 25,
            'offset'   => 0,
            'order'    => 'desc',
        ];
        $args = array_merge( $defaults, $args );

        $response = $this->api_model->get_publishing_episodes(
            $args['per_page'],
            $args['offset'],
            $args['order']
        );

        if ( ! $response || isset( $response['error'] ) ) {
            return [];
        }

        return $this->normalize_episodes( $response );
    }

    /**
     * Find all series.
     *
     * @param int $per_page Max per page.
     * @param int $offset   Offset.
     * @return array Array of normalized series.
     */
    public function find_series( $per_page = 100, $offset = 0 ) {
        $response = $this->api_model->get_publishing_series( $per_page, $offset );

        if ( ! $response || isset( $response['error'] ) ) {
            return [];
        }

        $series = [];
        if ( isset( $response['data'] ) ) {
            foreach ( $response['data'] as $item ) {
                $attrs = $item['attributes'] ?? [];
                $series[] = [
                    'id'          => $item['id'],
                    'title'       => $attrs['title'] ?? '',
                    'description' => $attrs['description'] ?? '',
                    'artwork'     => $attrs['artwork_url'] ?? '',
                ];
            }
        }

        return $series;
    }

    /**
     * Find all speakers.
     *
     * @return array Keyed by speaker ID.
     */
    public function find_speakers() {
        $speakers = $this->api_model->get_all_publishing_speakers();

        $normalized = [];
        foreach ( $speakers as $id => $speaker ) {
            $attrs = $speaker['attributes'] ?? [];
            $normalized[ $id ] = [
                'id'   => $id,
                'name' => trim( ( $attrs['first_name'] ?? '' ) . ' ' . ( $attrs['last_name'] ?? '' ) ),
            ];
        }

        return $normalized;
    }

    /**
     * Get resources (media) for an episode.
     *
     * @param string $episode_id The episode ID.
     * @return array Array of resource data.
     */
    public function find_episode_resources( $episode_id ) {
        $response = $this->api_model->get_publishing_episode_resources( $episode_id );

        if ( ! $response || isset( $response['error'] ) ) {
            return [];
        }

        $resources = [];
        if ( isset( $response['data'] ) ) {
            foreach ( $response['data'] as $item ) {
                $attrs = $item['attributes'] ?? [];
                $resources[] = [
                    'id'          => $item['id'],
                    'title'       => $attrs['title'] ?? '',
                    'description' => $attrs['description'] ?? '',
                    'url'         => $attrs['url'] ?? '',
                    'type'        => $attrs['resource_type'] ?? '',
                ];
            }
        }

        return $resources;
    }

    /**
     * Clear all publishing caches.
     *
     * @return void
     */
    public function clear_cache() {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '%_transient_simplepco_pub_%'
            )
        );
    }

    /**
     * Normalize a single episode response.
     *
     * @param array $response API response.
     * @return array Normalized episode data.
     */
    protected function normalize_episode( $response ) {
        $data  = $response['data'] ?? $response;
        $attrs = $data['attributes'] ?? [];

        return [
            'id'           => $data['id'] ?? '',
            'title'        => $attrs['title'] ?? '',
            'description'  => $attrs['description'] ?? '',
            'published_at' => $attrs['published_at'] ?? '',
            'artwork_url'  => $attrs['artwork_url'] ?? '',
            'speakers'     => $this->extract_speakers( $response ),
            'series'       => $this->extract_series( $response ),
            'raw'          => $data,
        ];
    }

    /**
     * Normalize multiple episodes.
     *
     * @param array $response API response.
     * @return array
     */
    protected function normalize_episodes( $response ) {
        if ( ! isset( $response['data'] ) ) {
            return [];
        }

        $episodes = [];
        foreach ( $response['data'] as $item ) {
            $episodes[] = $this->normalize_episode( [
                'data'     => $item,
                'included' => $response['included'] ?? [],
            ] );
        }

        return $episodes;
    }

    /**
     * Extract speaker names from included resources.
     *
     * @param array $response API response.
     * @return array Speaker names.
     */
    protected function extract_speakers( $response ) {
        $speakers = [];

        // Get speaker name directly from episode attributes.
        $data = $response['data'] ?? $response;
        $speaker = $data['attributes']['speaker'] ?? '';
        if ( $speaker ) {
            $speakers[] = $speaker;
        }

        return array_filter( $speakers );
    }

    /**
     * Extract series info from included resources.
     *
     * @param array $response API response.
     * @return array|null Series data or null.
     */
    protected function extract_series( $response ) {
        if ( isset( $response['included'] ) ) {
            foreach ( $response['included'] as $included ) {
                if ( isset( $included['type'] ) && $included['type'] === 'Series' ) {
                    $attrs = $included['attributes'] ?? [];
                    return [
                        'id'    => $included['id'],
                        'title' => $attrs['title'] ?? '',
                    ];
                }
            }
        }
        return null;
    }
}
