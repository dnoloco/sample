<?php
/**
 * Block Registrar Interface
 *
 * Modules that provide Gutenberg blocks implement this interface.
 * This is the "Skin" layer of the blended architecture — React-powered
 * UI components that integrate with the WordPress block editor.
 *
 * @package SimplePCO
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface SimplePCO_Block_Registrar_Interface {

    /**
     * Register all Gutenberg blocks provided by the implementor.
     *
     * Called on the 'init' hook at priority 12 (after CPTs are registered).
     *
     * @return void
     */
    public function register_blocks();
}
