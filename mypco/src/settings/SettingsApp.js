/**
 * MyPCO Settings App
 *
 * A React-powered settings page that uses @wordpress/components
 * so the UI looks and feels like native WordPress.
 *
 * Tabs:
 *  - API Credentials (PCO + Clearstream)
 *  - Modules (enable/disable with feature toggles)
 *  - License (activate/deactivate via remote server verification)
 *  - Cache (clear transient caches per-module)
 */

import { useState, useEffect } from '@wordpress/element';
import {
    Card,
    CardBody,
    CardHeader,
    TabPanel,
    TextControl,
    Button,
    Notice,
    Spinner,
    ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * API Credentials Tab
 */
function CredentialsTab() {
    const [ clientId, setClientId ] = useState( '' );
    const [ secretKey, setSecretKey ] = useState( '' );
    const [ clearstreamKey, setClearstreamKey ] = useState( '' );
    const [ saving, setSaving ] = useState( false );
    const [ notice, setNotice ] = useState( null );

    useEffect( () => {
        // Load existing settings from localized data
        if ( window.mypcoSettings ) {
            setClientId( window.mypcoSettings.pcoClientId || '' );
            setSecretKey( window.mypcoSettings.pcoSecretKey || '' );
            setClearstreamKey( window.mypcoSettings.clearstreamKey || '' );
        }
    }, [] );

    const handleSave = async () => {
        setSaving( true );
        setNotice( null );

        try {
            await apiFetch( {
                path: '/mypco/v1/settings/credentials',
                method: 'POST',
                data: {
                    pco_client_id: clientId,
                    pco_secret_key: secretKey,
                    clearstream_api_key: clearstreamKey,
                },
            } );
            setNotice( { status: 'success', message: __( 'Credentials saved.', 'mypco-online' ) } );
        } catch ( err ) {
            setNotice( { status: 'error', message: err.message || __( 'Save failed.', 'mypco-online' ) } );
        }

        setSaving( false );
    };

    const handleTestConnection = async () => {
        setSaving( true );
        setNotice( null );

        try {
            const result = await apiFetch( {
                path: '/mypco/v1/settings/test-connection',
                method: 'POST',
            } );
            setNotice( {
                status: result.connected ? 'success' : 'error',
                message: result.connected
                    ? __( 'Connected to Planning Center!', 'mypco-online' )
                    : __( 'Connection failed. Check your credentials.', 'mypco-online' ),
            } );
        } catch ( err ) {
            setNotice( { status: 'error', message: err.message } );
        }

        setSaving( false );
    };

    return (
        <div>
            { notice && (
                <Notice status={ notice.status } isDismissible onDismiss={ () => setNotice( null ) }>
                    { notice.message }
                </Notice>
            ) }

            <Card>
                <CardHeader>
                    <h3>{ __( 'Planning Center Online', 'mypco-online' ) }</h3>
                </CardHeader>
                <CardBody>
                    <TextControl
                        label={ __( 'Application ID', 'mypco-online' ) }
                        value={ clientId }
                        onChange={ setClientId }
                        help={ __( 'Found at api.planningcenteronline.com/oauth/applications', 'mypco-online' ) }
                    />
                    <TextControl
                        label={ __( 'Secret Key', 'mypco-online' ) }
                        type="password"
                        value={ secretKey }
                        onChange={ setSecretKey }
                    />
                    <Button variant="secondary" onClick={ handleTestConnection } disabled={ saving }>
                        { saving ? <Spinner /> : __( 'Test Connection', 'mypco-online' ) }
                    </Button>
                </CardBody>
            </Card>

            <Card style={ { marginTop: '16px' } }>
                <CardHeader>
                    <h3>{ __( 'Clearstream SMS', 'mypco-online' ) }</h3>
                </CardHeader>
                <CardBody>
                    <TextControl
                        label={ __( 'API Key', 'mypco-online' ) }
                        type="password"
                        value={ clearstreamKey }
                        onChange={ setClearstreamKey }
                    />
                </CardBody>
            </Card>

            <div style={ { marginTop: '16px' } }>
                <Button variant="primary" onClick={ handleSave } disabled={ saving }>
                    { saving ? <Spinner /> : __( 'Save Credentials', 'mypco-online' ) }
                </Button>
            </div>
        </div>
    );
}

/**
 * Modules Tab
 */
function ModulesTab() {
    const [ modules, setModules ] = useState( [] );
    const [ loading, setLoading ] = useState( true );

    useEffect( () => {
        if ( window.mypcoSettings && window.mypcoSettings.modules ) {
            setModules( window.mypcoSettings.modules );
            setLoading( false );
        }
    }, [] );

    const handleToggle = async ( key, enabled ) => {
        try {
            await apiFetch( {
                path: `/mypco/v1/modules/${ key }`,
                method: 'POST',
                data: { enabled },
            } );

            setModules( ( prev ) =>
                prev.map( ( m ) => ( m.key === key ? { ...m, enabled } : m ) )
            );
        } catch ( err ) {
            // Revert on failure
            setModules( ( prev ) =>
                prev.map( ( m ) => ( m.key === key ? { ...m, enabled: ! enabled } : m ) )
            );
        }
    };

    if ( loading ) {
        return <Spinner />;
    }

    return (
        <Card>
            <CardHeader>
                <h3>{ __( 'Manage Modules', 'mypco-online' ) }</h3>
            </CardHeader>
            <CardBody>
                { modules.map( ( mod ) => (
                    <ToggleControl
                        key={ mod.key }
                        label={ mod.name }
                        help={ mod.description }
                        checked={ mod.enabled }
                        onChange={ ( val ) => handleToggle( mod.key, val ) }
                        disabled={ mod.tier === 'premium' && ! mod.has_license }
                    />
                ) ) }
            </CardBody>
        </Card>
    );
}

/**
 * Cache Management Tab
 */
function CacheTab() {
    const [ clearing, setClearing ] = useState( false );
    const [ notice, setNotice ] = useState( null );

    const handleClearAll = async () => {
        setClearing( true );
        try {
            await apiFetch( {
                path: '/mypco/v1/cache/clear',
                method: 'POST',
            } );
            setNotice( { status: 'success', message: __( 'All caches cleared.', 'mypco-online' ) } );
        } catch ( err ) {
            setNotice( { status: 'error', message: err.message } );
        }
        setClearing( false );
    };

    return (
        <div>
            { notice && (
                <Notice status={ notice.status } isDismissible onDismiss={ () => setNotice( null ) }>
                    { notice.message }
                </Notice>
            ) }
            <Card>
                <CardHeader>
                    <h3>{ __( 'Cache Management', 'mypco-online' ) }</h3>
                </CardHeader>
                <CardBody>
                    <p>{ __( 'Clear cached API data to fetch fresh information from Planning Center.', 'mypco-online' ) }</p>
                    <Button variant="secondary" isDestructive onClick={ handleClearAll } disabled={ clearing }>
                        { clearing ? <Spinner /> : __( 'Clear All Caches', 'mypco-online' ) }
                    </Button>
                </CardBody>
            </Card>
        </div>
    );
}

/**
 * License Management Tab
 *
 * Uses the React Feedback Loop: React → REST Controller → License Manager → Remote Server.
 * License status is cached server-side via transients (12h) so we don't hammer the remote API.
 */
function LicenseTab() {
    const [ key, setKey ] = useState( '' );
    const [ license, setLicense ] = useState( null );
    const [ loading, setLoading ] = useState( true );
    const [ busy, setBusy ] = useState( false );
    const [ notice, setNotice ] = useState( null );

    // Fetch current license status on mount (reads from transient cache).
    useEffect( () => {
        apiFetch( { path: '/mypco/v1/license/status' } )
            .then( ( data ) => {
                setLicense( data );
                setLoading( false );
            } )
            .catch( () => {
                setLoading( false );
            } );
    }, [] );

    const isActive = license && license.status === 'active';

    const handleActivate = async () => {
        setBusy( true );
        setNotice( null );

        try {
            const result = await apiFetch( {
                path: '/mypco/v1/license/activate',
                method: 'POST',
                data: { license_key: key },
            } );

            if ( result.success ) {
                setNotice( { status: 'success', message: result.message } );
                // Refresh status to get tier, modules, expiry from server.
                const updated = await apiFetch( { path: '/mypco/v1/license/status' } );
                setLicense( updated );
                setKey( '' );
            } else {
                setNotice( { status: 'error', message: result.message || __( 'Activation failed.', 'mypco-online' ) } );
            }
        } catch ( err ) {
            setNotice( { status: 'error', message: err.message || __( 'Could not connect to license server.', 'mypco-online' ) } );
        }

        setBusy( false );
    };

    const handleDeactivate = async () => {
        setBusy( true );
        setNotice( null );

        try {
            const result = await apiFetch( {
                path: '/mypco/v1/license/deactivate',
                method: 'POST',
            } );
            setNotice( { status: 'success', message: result.message } );
            setLicense( { status: 'inactive', tier: null, modules: [] } );
        } catch ( err ) {
            setNotice( { status: 'error', message: err.message } );
        }

        setBusy( false );
    };

    if ( loading ) {
        return <Spinner />;
    }

    return (
        <div>
            { notice && (
                <Notice status={ notice.status } isDismissible onDismiss={ () => setNotice( null ) }>
                    { notice.message }
                </Notice>
            ) }

            { isActive ? (
                <Card>
                    <CardHeader>
                        <h3>{ __( 'License Active', 'mypco-online' ) }</h3>
                    </CardHeader>
                    <CardBody>
                        <p><strong>{ __( 'Tier:', 'mypco-online' ) }</strong> { license.tier_label }</p>
                        { license.expires_at && (
                            <p><strong>{ __( 'Expires:', 'mypco-online' ) }</strong> { license.expires_at }</p>
                        ) }
                        { license.modules && license.modules.length > 0 && (
                            <p>
                                <strong>{ __( 'Modules:', 'mypco-online' ) }</strong>{ ' ' }
                                { license.modules.map( ( m ) => m.charAt( 0 ).toUpperCase() + m.slice( 1 ) ).join( ', ' ) }
                            </p>
                        ) }
                        { typeof license.sites_remaining !== 'undefined' && (
                            <p><strong>{ __( 'Sites remaining:', 'mypco-online' ) }</strong> { license.sites_remaining }</p>
                        ) }
                        <Button variant="secondary" isDestructive onClick={ handleDeactivate } disabled={ busy }>
                            { busy ? <Spinner /> : __( 'Deactivate License', 'mypco-online' ) }
                        </Button>
                    </CardBody>
                </Card>
            ) : (
                <Card>
                    <CardHeader>
                        <h3>{ __( 'Activate License', 'mypco-online' ) }</h3>
                    </CardHeader>
                    <CardBody>
                        <TextControl
                            label={ __( 'License Key', 'mypco-online' ) }
                            value={ key }
                            onChange={ setKey }
                            placeholder="MYPCO-XXXX-XXXX-XXXX-XXXX"
                            help={ __( 'Enter your license key to unlock premium modules.', 'mypco-online' ) }
                        />
                        <Button variant="primary" onClick={ handleActivate } isBusy={ busy } disabled={ busy || ! key }>
                            { busy ? __( 'Verifying...', 'mypco-online' ) : __( 'Activate Pro', 'mypco-online' ) }
                        </Button>
                    </CardBody>
                </Card>
            ) }
        </div>
    );
}

/**
 * Main Settings App with Tab Navigation
 */
export function SettingsApp() {
    const tabs = [
        {
            name: 'credentials',
            title: __( 'API Credentials', 'mypco-online' ),
            className: 'mypco-tab-credentials',
        },
        {
            name: 'modules',
            title: __( 'Modules', 'mypco-online' ),
            className: 'mypco-tab-modules',
        },
        {
            name: 'license',
            title: __( 'License', 'mypco-online' ),
            className: 'mypco-tab-license',
        },
        {
            name: 'cache',
            title: __( 'Cache', 'mypco-online' ),
            className: 'mypco-tab-cache',
        },
    ];

    return (
        <div className="wrap">
            <h1>{ __( 'MyPCO Settings', 'mypco-online' ) }</h1>
            <TabPanel tabs={ tabs }>
                { ( tab ) => {
                    switch ( tab.name ) {
                        case 'credentials':
                            return <CredentialsTab />;
                        case 'modules':
                            return <ModulesTab />;
                        case 'license':
                            return <LicenseTab />;
                        case 'cache':
                            return <CacheTab />;
                        default:
                            return null;
                    }
                } }
            </TabPanel>
        </div>
    );
}
