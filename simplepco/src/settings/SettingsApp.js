/**
 * SimplePCO Settings App
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
 * Planning Center OAuth Connection Card
 *
 * Shows either a "Connect" button or the connected status with "Disconnect".
 */
function PcoOAuthCard() {
    const settings = window.simplepcoSettings || {};
    const [ connected, setConnected ] = useState( settings.oauthConnected || false );
    const [ busy, setBusy ] = useState( false );
    const [ notice, setNotice ] = useState( () => {
        // Show notice from OAuth redirect (PHP sets oauthStatus on the page load after callback).
        const status = settings.oauthStatus;
        if ( status === 'connected' ) {
            return { status: 'success', message: __( 'Successfully connected to Planning Center!', 'simplepco' ) };
        }
        if ( status === 'oauth_denied' ) {
            return { status: 'error', message: __( 'Authorization was denied. Please try again.', 'simplepco' ) };
        }
        if ( status === 'invalid_state' || status === 'missing_code' || status === 'token_exchange_failed' ) {
            return { status: 'error', message: __( 'Connection failed. Please try again.', 'simplepco' ) };
        }
        return null;
    } );

    const handleConnect = async () => {
        setBusy( true );
        setNotice( null );

        try {
            const result = await apiFetch( { path: '/simplepco/v1/oauth/authorize-url' } );
            // Redirect the browser to the external OAuth server.
            window.location.href = result.authorize_url;
        } catch ( err ) {
            setNotice( { status: 'error', message: err.message || __( 'Could not start OAuth flow.', 'simplepco' ) } );
            setBusy( false );
        }
    };

    const handleDisconnect = async () => {
        setBusy( true );
        setNotice( null );

        try {
            const result = await apiFetch( {
                path: '/simplepco/v1/oauth/disconnect',
                method: 'POST',
            } );
            setConnected( false );
            setNotice( { status: 'success', message: result.message } );
        } catch ( err ) {
            setNotice( { status: 'error', message: err.message } );
        }

        setBusy( false );
    };

    const handleTestConnection = async () => {
        setBusy( true );
        setNotice( null );

        try {
            const result = await apiFetch( {
                path: '/simplepco/v1/settings/test-connection',
                method: 'POST',
            } );
            setNotice( {
                status: result.connected ? 'success' : 'error',
                message: result.connected
                    ? __( 'Connected to Planning Center!', 'simplepco' )
                    : __( 'Connection failed. Try disconnecting and reconnecting.', 'simplepco' ),
            } );
        } catch ( err ) {
            setNotice( { status: 'error', message: err.message } );
        }

        setBusy( false );
    };

    return (
        <Card>
            <CardHeader>
                <h3>{ __( 'Planning Center Online', 'simplepco' ) }</h3>
            </CardHeader>
            <CardBody>
                { notice && (
                    <Notice status={ notice.status } isDismissible onDismiss={ () => setNotice( null ) }
                        style={ { marginBottom: '12px' } }>
                        { notice.message }
                    </Notice>
                ) }

                { connected ? (
                    <div>
                        <p style={ { color: '#00a32a', fontWeight: 600 } }>
                            { __( 'Connected via OAuth', 'simplepco' ) }
                        </p>
                        { settings.oauthExpiresAt && (
                            <p className="description">
                                { __( 'Token expires:', 'simplepco' ) } { settings.oauthExpiresAt }
                                { ' — ' }
                                { __( 'tokens refresh automatically.', 'simplepco' ) }
                            </p>
                        ) }
                        <div style={ { display: 'flex', gap: '8px', marginTop: '12px' } }>
                            <Button variant="secondary" onClick={ handleTestConnection } disabled={ busy }>
                                { busy ? <Spinner /> : __( 'Test Connection', 'simplepco' ) }
                            </Button>
                            <Button variant="secondary" isDestructive onClick={ handleDisconnect } disabled={ busy }>
                                { busy ? <Spinner /> : __( 'Disconnect', 'simplepco' ) }
                            </Button>
                        </div>
                    </div>
                ) : (
                    <div>
                        <p className="description" style={ { marginBottom: '12px' } }>
                            { __( 'Connect your Planning Center account using secure OAuth 2.0 authorization. You will be redirected to approve access.', 'simplepco' ) }
                        </p>
                        <Button variant="primary" onClick={ handleConnect } isBusy={ busy } disabled={ busy }>
                            { busy ? __( 'Redirecting...', 'simplepco' ) : __( 'Connect to Planning Center', 'simplepco' ) }
                        </Button>
                    </div>
                ) }
            </CardBody>
        </Card>
    );
}

/**
 * API Credentials Tab
 */
function CredentialsTab() {
    const [ clearstreamKey, setClearstreamKey ] = useState( '' );
    const [ saving, setSaving ] = useState( false );
    const [ notice, setNotice ] = useState( null );

    useEffect( () => {
        if ( window.simplepcoSettings ) {
            setClearstreamKey( window.simplepcoSettings.clearstreamKey || '' );
        }
    }, [] );

    const handleSaveClearstream = async () => {
        setSaving( true );
        setNotice( null );

        try {
            await apiFetch( {
                path: '/simplepco/v1/settings/credentials',
                method: 'POST',
                data: {
                    clearstream_api_key: clearstreamKey,
                },
            } );
            setNotice( { status: 'success', message: __( 'Credentials saved.', 'simplepco' ) } );
        } catch ( err ) {
            setNotice( { status: 'error', message: err.message || __( 'Save failed.', 'simplepco' ) } );
        }

        setSaving( false );
    };

    return (
        <div>
            <PcoOAuthCard />

            { notice && (
                <Notice status={ notice.status } isDismissible onDismiss={ () => setNotice( null ) }
                    style={ { marginTop: '16px' } }>
                    { notice.message }
                </Notice>
            ) }

            <Card style={ { marginTop: '16px' } }>
                <CardHeader>
                    <h3>{ __( 'Clearstream SMS', 'simplepco' ) }</h3>
                </CardHeader>
                <CardBody>
                    <TextControl
                        label={ __( 'API Key', 'simplepco' ) }
                        type="password"
                        value={ clearstreamKey }
                        onChange={ setClearstreamKey }
                    />
                </CardBody>
            </Card>

            <div style={ { marginTop: '16px' } }>
                <Button variant="primary" onClick={ handleSaveClearstream } disabled={ saving }>
                    { saving ? <Spinner /> : __( 'Save Credentials', 'simplepco' ) }
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
        if ( window.simplepcoSettings && window.simplepcoSettings.modules ) {
            setModules( window.simplepcoSettings.modules );
            setLoading( false );
        }
    }, [] );

    const handleToggle = async ( key, enabled ) => {
        try {
            await apiFetch( {
                path: `/simplepco/v1/modules/${ key }`,
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
                <h3>{ __( 'Manage Modules', 'simplepco' ) }</h3>
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
                path: '/simplepco/v1/cache/clear',
                method: 'POST',
            } );
            setNotice( { status: 'success', message: __( 'All caches cleared.', 'simplepco' ) } );
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
                    <h3>{ __( 'Cache Management', 'simplepco' ) }</h3>
                </CardHeader>
                <CardBody>
                    <p>{ __( 'Clear cached API data to fetch fresh information from Planning Center.', 'simplepco' ) }</p>
                    <Button variant="secondary" isDestructive onClick={ handleClearAll } disabled={ clearing }>
                        { clearing ? <Spinner /> : __( 'Clear All Caches', 'simplepco' ) }
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
        apiFetch( { path: '/simplepco/v1/license/status' } )
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
                path: '/simplepco/v1/license/activate',
                method: 'POST',
                data: { license_key: key },
            } );

            if ( result.success ) {
                setNotice( { status: 'success', message: result.message } );
                // Refresh status to get tier, modules, expiry from server.
                const updated = await apiFetch( { path: '/simplepco/v1/license/status' } );
                setLicense( updated );
                setKey( '' );
            } else {
                setNotice( { status: 'error', message: result.message || __( 'Activation failed.', 'simplepco' ) } );
            }
        } catch ( err ) {
            setNotice( { status: 'error', message: err.message || __( 'Could not connect to license server.', 'simplepco' ) } );
        }

        setBusy( false );
    };

    const handleDeactivate = async () => {
        setBusy( true );
        setNotice( null );

        try {
            const result = await apiFetch( {
                path: '/simplepco/v1/license/deactivate',
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
                        <h3>{ __( 'License Active', 'simplepco' ) }</h3>
                    </CardHeader>
                    <CardBody>
                        <p><strong>{ __( 'Tier:', 'simplepco' ) }</strong> { license.tier_label }</p>
                        { license.expires_at && (
                            <p><strong>{ __( 'Expires:', 'simplepco' ) }</strong> { license.expires_at }</p>
                        ) }
                        { license.modules && license.modules.length > 0 && (
                            <p>
                                <strong>{ __( 'Modules:', 'simplepco' ) }</strong>{ ' ' }
                                { license.modules.map( ( m ) => m.charAt( 0 ).toUpperCase() + m.slice( 1 ) ).join( ', ' ) }
                            </p>
                        ) }
                        { typeof license.sites_remaining !== 'undefined' && (
                            <p><strong>{ __( 'Sites remaining:', 'simplepco' ) }</strong> { license.sites_remaining }</p>
                        ) }
                        <Button variant="secondary" isDestructive onClick={ handleDeactivate } disabled={ busy }>
                            { busy ? <Spinner /> : __( 'Deactivate License', 'simplepco' ) }
                        </Button>
                    </CardBody>
                </Card>
            ) : (
                <Card>
                    <CardHeader>
                        <h3>{ __( 'Activate License', 'simplepco' ) }</h3>
                    </CardHeader>
                    <CardBody>
                        <TextControl
                            label={ __( 'License Key', 'simplepco' ) }
                            value={ key }
                            onChange={ setKey }
                            placeholder="SIMPLEPCO-XXXX-XXXX-XXXX-XXXX"
                            help={ __( 'Enter your license key to unlock premium modules.', 'simplepco' ) }
                        />
                        <Button variant="primary" onClick={ handleActivate } isBusy={ busy } disabled={ busy || ! key }>
                            { busy ? __( 'Verifying...', 'simplepco' ) : __( 'Activate Pro', 'simplepco' ) }
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
            title: __( 'API Credentials', 'simplepco' ),
            className: 'simplepco-tab-credentials',
        },
        {
            name: 'modules',
            title: __( 'Modules', 'simplepco' ),
            className: 'simplepco-tab-modules',
        },
        {
            name: 'license',
            title: __( 'License', 'simplepco' ),
            className: 'simplepco-tab-license',
        },
        {
            name: 'cache',
            title: __( 'Cache', 'simplepco' ),
            className: 'simplepco-tab-cache',
        },
    ];

    return (
        <div className="wrap">
            <h1>{ __( 'SimplePCO Settings', 'simplepco' ) }</h1>
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
