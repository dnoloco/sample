<?php
/**
 * Repository Interface
 *
 * All repositories must implement this interface to ensure a consistent
 * data access contract across the plugin. This is the "Muscle" of the
 * blended architecture (SSP-style Repository Pattern).
 *
 * By coding against this interface, modules never touch raw SQL or
 * get_post_meta directly in their display logic. If the underlying
 * data source changes (e.g., from transient cache to a custom DB table),
 * only the repository implementation needs to change.
 *
 * @package MyPCO
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface MyPCO_Repository_Interface {

    /**
     * Find a single record by its unique identifier.
     *
     * @param string|int $id The unique identifier.
     * @return array|null The record data or null if not found.
     */
    public function find( $id );

    /**
     * Find multiple records matching the given criteria.
     *
     * @param array $args Query arguments specific to the repository.
     * @return array Array of record data.
     */
    public function find_all( $args = [] );

    /**
     * Clear any cached data this repository manages.
     *
     * @return void
     */
    public function clear_cache();
}
