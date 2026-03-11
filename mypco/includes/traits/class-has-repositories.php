<?php
/**
 * Has Repositories Trait
 *
 * Provides repository registration and resolution to any class that uses it.
 * This bridges the MyPCO Loader (Skeleton) with the Repository Pattern (Muscle).
 *
 * @package MyPCO
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

trait MyPCO_Has_Repositories {

    /**
     * Registered repository instances.
     *
     * @var MyPCO_Repository_Interface[]
     */
    protected $repositories = [];

    /**
     * Register a repository instance.
     *
     * @param string                     $key        Unique identifier (e.g., 'events', 'services').
     * @param MyPCO_Repository_Interface $repository The repository instance.
     * @return void
     */
    public function register_repository( $key, MyPCO_Repository_Interface $repository ) {
        $this->repositories[ $key ] = $repository;
    }

    /**
     * Retrieve a registered repository.
     *
     * @param string $key The repository identifier.
     * @return MyPCO_Repository_Interface|null
     */
    public function get_repository( $key ) {
        return isset( $this->repositories[ $key ] ) ? $this->repositories[ $key ] : null;
    }

    /**
     * Get all registered repositories.
     *
     * @return MyPCO_Repository_Interface[]
     */
    public function get_repositories() {
        return $this->repositories;
    }
}
