/**
 * Calendar Events Block — Editor Component
 *
 * Provides a "Live Preview" experience in the Gutenberg editor.
 * Uses @wordpress/components for native WordPress look and feel,
 * and @wordpress/api-fetch to pull data from the SimplePCO REST API.
 */

import { useState, useEffect } from '@wordpress/element';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
    PanelBody,
    RangeControl,
    SelectControl,
    ToggleControl,
    Placeholder,
    Spinner,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

export default function Edit( { attributes, setAttributes } ) {
    const { count, view, showImages, showDescription } = attributes;
    const [ events, setEvents ] = useState( [] );
    const [ loading, setLoading ] = useState( true );
    const [ error, setError ] = useState( null );
    const blockProps = useBlockProps();

    useEffect( () => {
        setLoading( true );
        setError( null );

        apiFetch( {
            path: `/simplepco/v1/events?per_page=${ count }&view=${ view }`,
        } )
            .then( ( data ) => {
                setEvents( data );
                setLoading( false );
            } )
            .catch( ( err ) => {
                setError( err.message || __( 'Failed to load events.', 'simplepco-online' ) );
                setLoading( false );
            } );
    }, [ count, view ] );

    return (
        <div { ...blockProps }>
            <InspectorControls>
                <PanelBody title={ __( 'Event Settings', 'simplepco-online' ) }>
                    <RangeControl
                        label={ __( 'Number of Events', 'simplepco-online' ) }
                        value={ count }
                        onChange={ ( val ) => setAttributes( { count: val } ) }
                        min={ 1 }
                        max={ 25 }
                    />
                    <SelectControl
                        label={ __( 'View Style', 'simplepco-online' ) }
                        value={ view }
                        options={ [
                            { label: __( 'List', 'simplepco-online' ), value: 'list' },
                            { label: __( 'Grid', 'simplepco-online' ), value: 'grid' },
                            { label: __( 'Calendar', 'simplepco-online' ), value: 'calendar' },
                        ] }
                        onChange={ ( val ) => setAttributes( { view: val } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Images', 'simplepco-online' ) }
                        checked={ showImages }
                        onChange={ ( val ) => setAttributes( { showImages: val } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Description', 'simplepco-online' ) }
                        checked={ showDescription }
                        onChange={ ( val ) => setAttributes( { showDescription: val } ) }
                    />
                </PanelBody>
            </InspectorControls>

            { loading && (
                <Placeholder icon="calendar-alt" label={ __( 'PCO Calendar Events', 'simplepco-online' ) }>
                    <Spinner />
                </Placeholder>
            ) }

            { error && (
                <Placeholder icon="warning" label={ __( 'PCO Calendar Events', 'simplepco-online' ) }>
                    <p>{ error }</p>
                </Placeholder>
            ) }

            { ! loading && ! error && events.length === 0 && (
                <Placeholder icon="calendar-alt" label={ __( 'PCO Calendar Events', 'simplepco-online' ) }>
                    <p>{ __( 'No upcoming events found.', 'simplepco-online' ) }</p>
                </Placeholder>
            ) }

            { ! loading && ! error && events.length > 0 && (
                <div className={ `simplepco-events-preview simplepco-events-${ view }` }>
                    { events.map( ( event ) => (
                        <div key={ event.id } className="simplepco-event-item">
                            { showImages && event.image_url && (
                                <img
                                    src={ event.image_url }
                                    alt={ event.name }
                                    className="simplepco-event-image"
                                />
                            ) }
                            <div className="simplepco-event-details">
                                <h4 className="simplepco-event-title">{ event.name }</h4>
                                <time className="simplepco-event-date">{ event.starts_at }</time>
                                { showDescription && event.description && (
                                    <p className="simplepco-event-description">{ event.description }</p>
                                ) }
                                { event.location && (
                                    <span className="simplepco-event-location">{ event.location }</span>
                                ) }
                            </div>
                        </div>
                    ) ) }
                </div>
            ) }
        </div>
    );
}
