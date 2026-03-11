<?php
/**
 * Centralized Loader — The "Skeleton" of the Blended Architecture.
 *
 * This class is the single registry for everything the plugin does.
 * Instead of scattered add_action/add_filter calls, all hooks are
 * queued here and executed in one run() call.
 *
 * In the blended architecture it also manages:
 *  - Repository registration (the "Muscle" — SSP-style data access)
 *  - Block registrar tracking (the "Skin" — React-powered Gutenberg blocks)
 *
 * @package MyPCO
 * @since 2.0.0 Original loader.
 * @since 3.0.0 Extended with repository and block registrar support.
 */

class MyPCO_Loader {

    use MyPCO_Has_Repositories;

    /**
     * The array of actions registered with WordPress.
     */
    protected $actions;

    /**
     * The array of filters registered with WordPress.
     */
    protected $filters;

    /**
     * Block registrars collected from modules.
     *
     * @var MyPCO_Block_Registrar_Interface[]
     */
    protected $block_registrars = [];

    /**
     * Initialize the collections used to maintain the actions and filters.
     */
    public function __construct() {
        $this->actions = [];
        $this->filters = [];
    }

    /**
     * Add a new action to the collection to be registered with WordPress.
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Add a new filter to the collection to be registered with WordPress.
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * A utility function that is used to register the actions and hooks into a single collection.
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = [
            'hook' => $hook,
            'component' => $component,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args
        ];

        return $hooks;
    }

    /**
     * Register a block registrar to be initialized during run().
     *
     * @param MyPCO_Block_Registrar_Interface $registrar A module's block registrar.
     * @return void
     */
    public function add_block_registrar( MyPCO_Block_Registrar_Interface $registrar ) {
        $this->block_registrars[] = $registrar;
    }

    /**
     * Register the filters and actions with WordPress, then
     * fire block registrars on the 'init' hook at priority 12.
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                [$hook['component'], $hook['callback']],
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                [$hook['component'], $hook['callback']],
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        // Register Gutenberg blocks after CPTs (priority 12).
        if ( ! empty( $this->block_registrars ) ) {
            add_action( 'init', [ $this, 'register_all_blocks' ], 12 );
        }
    }

    /**
     * Callback: register blocks from all collected registrars.
     *
     * @return void
     */
    public function register_all_blocks() {
        foreach ( $this->block_registrars as $registrar ) {
            $registrar->register_blocks();
        }
    }
}
