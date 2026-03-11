<?php
/**
 * Service Repository
 *
 * Encapsulates all data access for Planning Center Services
 * (service types, plans, team members, schedules).
 *
 * Part of the Repository Pattern ("Muscle") in the blended architecture.
 *
 * @package MyPCO
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MyPCO_Service_Repository implements MyPCO_Repository_Interface {

    /**
     * @var MyPCO_API_Model
     */
    protected $api_model;

    const CACHE_GROUP = 'mypco_services';

    /**
     * @param MyPCO_API_Model $api_model The PCO API model.
     */
    public function __construct( MyPCO_API_Model $api_model ) {
        $this->api_model = $api_model;
    }

    /**
     * Find a single service plan by ID.
     *
     * @param string|int $id The plan ID.
     * @return array|null Normalized plan data or null.
     */
    public function find( $id ) {
        $response = $this->api_model->get_single_plan( $id );

        if ( ! $response || isset( $response['error'] ) ) {
            return null;
        }

        return $this->normalize_plan( $response );
    }

    /**
     * Find all service types.
     *
     * @param array $args Unused for now, kept for interface compliance.
     * @return array Array of service types.
     */
    public function find_all( $args = [] ) {
        $response = $this->api_model->get_service_types();

        if ( ! $response || isset( $response['error'] ) ) {
            return [];
        }

        $types = [];
        if ( isset( $response['data'] ) ) {
            foreach ( $response['data'] as $type ) {
                $types[] = [
                    'id'   => $type['id'],
                    'name' => $type['attributes']['name'] ?? '',
                ];
            }
        }

        return $types;
    }

    /**
     * Get upcoming plans for a given service type.
     *
     * @param string $type_id The service type ID.
     * @param int    $count   Number of plans to return.
     * @return array Array of normalized plans.
     */
    public function find_upcoming_plans( $type_id, $count = 5 ) {
        $response = $this->api_model->get_upcoming_plans( $type_id, $count );

        if ( ! $response || isset( $response['error'] ) ) {
            return [];
        }

        return $this->normalize_plans( $response );
    }

    /**
     * Get plans by date range for reporting.
     *
     * @param string $type_id    The service type ID.
     * @param string $start_date Start date (Y-m-d).
     * @param string $end_date   End date (Y-m-d).
     * @return array Array of normalized plans.
     */
    public function find_plans_by_date_range( $type_id, $start_date, $end_date ) {
        $response = $this->api_model->get_plans_by_date_range( $type_id, $start_date, $end_date );

        if ( ! $response || isset( $response['error'] ) ) {
            return [];
        }

        return $this->normalize_plans( $response );
    }

    /**
     * Get team members for a plan.
     *
     * @param string $plan_id The plan ID.
     * @return array Array of team member data.
     */
    public function find_plan_team_members( $plan_id ) {
        $response = $this->api_model->get_plan_team_members( $plan_id );

        if ( ! $response || isset( $response['error'] ) ) {
            return [];
        }

        $members = [];
        if ( isset( $response['data'] ) ) {
            foreach ( $response['data'] as $member ) {
                $attrs = $member['attributes'] ?? [];
                $members[] = [
                    'id'       => $member['id'],
                    'name'     => $attrs['name'] ?? '',
                    'status'   => $attrs['status'] ?? '',
                    'position' => $attrs['team_position_name'] ?? '',
                ];
            }
        }

        return $members;
    }

    /**
     * Clear all service caches.
     *
     * @return void
     */
    public function clear_cache() {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '%_transient_mypco_service%'
            )
        );
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '%_transient_mypco_plan%'
            )
        );
    }

    /**
     * Normalize a single plan response.
     *
     * @param array $response Raw API response.
     * @return array Normalized plan data.
     */
    protected function normalize_plan( $response ) {
        $data  = $response['data'] ?? $response;
        $attrs = $data['attributes'] ?? [];

        return [
            'id'            => $data['id'] ?? '',
            'title'         => $attrs['title'] ?? '',
            'dates'         => $attrs['dates'] ?? '',
            'sort_date'     => $attrs['sort_date'] ?? '',
            'items_count'   => $attrs['items_count'] ?? 0,
            'plan_notes'    => $attrs['plan_notes'] ?? [],
            'team_members'  => $this->extract_included_team_members( $response ),
            'raw'           => $data,
        ];
    }

    /**
     * Normalize multiple plans from a list response.
     *
     * @param array $response Raw API response with 'data' array.
     * @return array Array of normalized plans.
     */
    protected function normalize_plans( $response ) {
        if ( ! isset( $response['data'] ) ) {
            return [];
        }

        $plans = [];
        foreach ( $response['data'] as $item ) {
            $plans[] = $this->normalize_plan( [
                'data'     => $item,
                'included' => $response['included'] ?? [],
            ] );
        }

        return $plans;
    }

    /**
     * Extract team members from included resources.
     *
     * @param array $response API response.
     * @return array Team member data.
     */
    protected function extract_included_team_members( $response ) {
        $members = [];
        if ( isset( $response['included'] ) ) {
            foreach ( $response['included'] as $included ) {
                if ( isset( $included['type'] ) && $included['type'] === 'PlanPerson' ) {
                    $attrs = $included['attributes'] ?? [];
                    $members[] = [
                        'id'       => $included['id'],
                        'name'     => $attrs['name'] ?? '',
                        'status'   => $attrs['status'] ?? '',
                        'position' => $attrs['team_position_name'] ?? '',
                    ];
                }
            }
        }
        return $members;
    }
}
