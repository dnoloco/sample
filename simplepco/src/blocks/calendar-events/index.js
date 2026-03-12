/**
 * Calendar Events Block
 *
 * Displays upcoming Planning Center calendar events.
 * Uses the Event Repository on the server side for data access,
 * and the REST API + @wordpress/components for a live preview
 * in the Gutenberg editor.
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import Edit from './edit';

registerBlockType( 'simplepco/calendar-events', {
    title: __( 'PCO Calendar Events', 'simplepco-online' ),
    description: __( 'Display upcoming events from Planning Center Calendar.', 'simplepco-online' ),
    category: 'widgets',
    icon: 'calendar-alt',
    keywords: [
        __( 'events', 'simplepco-online' ),
        __( 'calendar', 'simplepco-online' ),
        __( 'planning center', 'simplepco-online' ),
    ],
    attributes: {
        count: {
            type: 'number',
            default: 5,
        },
        view: {
            type: 'string',
            default: 'list',
        },
        showImages: {
            type: 'boolean',
            default: true,
        },
        showDescription: {
            type: 'boolean',
            default: true,
        },
    },
    edit: Edit,
    save: () => null, // Server-side rendered
} );
