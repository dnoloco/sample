/**
 * MyPCO Settings Page — React App Entry Point
 *
 * This replaces the traditional PHP settings forms with a React single-page
 * app using @wordpress/components for a native WordPress look and feel.
 *
 * This is the "Skin" layer of the blended architecture (SSP-style).
 *
 * Build: npm run build
 * Dev:   npm run start
 */

import { render } from '@wordpress/element';
import { SettingsApp } from './SettingsApp';

document.addEventListener( 'DOMContentLoaded', () => {
    const container = document.getElementById( 'mypco-settings-root' );
    if ( container ) {
        render( <SettingsApp />, container );
    }
} );
