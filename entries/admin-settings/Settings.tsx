/**
 * Allegro Audience Admin Settings Component
 *
 * @package wp-allegro-audience
 */

import {
createElement,
Fragment,
useState,
useEffect,
useCallback,
} from '@wordpress/element';
import {
Button,
Card,
CardBody,
CardHeader,
ExternalLink,
Flex,
FlexItem,
Notice,
Spinner,
TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

interface AllegroSettings {
restUrl: string;
nonce: string;
tenantUrl: string;
}

declare global {
interface Window {
allegroAudienceSettings: AllegroSettings;
}
}

type StepStatus = 'idle' | 'pending' | 'success' | 'error';

interface VerificationState {
health: StepStatus;
cors: StepStatus;
healthError: string;
corsError: string;
}

const INITIAL_VERIFICATION: VerificationState = {
health: 'idle',
cors: 'idle',
healthError: '',
corsError: '',
};

function StatusIcon( { status }: { status: StepStatus } ) {
if ( status === 'pending' ) {
return <Spinner />;
}
if ( status === 'success' ) {
return <span style={ { color: '#00a32a', fontWeight: 600 } }>✓</span>;
}
if ( status === 'error' ) {
return <span style={ { color: '#d63638', fontWeight: 600 } }>✗</span>;
}
return null;
}

function ConnectionBadge( {
tenantUrl,
corsOk,
}: {
tenantUrl: string;
corsOk: boolean | null;
} ) {
const baseStyle: React.CSSProperties = {
borderRadius: '2px',
padding: '2px 8px',
fontSize: '12px',
fontWeight: 500,
};

if ( ! tenantUrl ) {
return (
<span style={ { ...baseStyle, background: '#dcdcde', color: '#50575e' } }>
{ __( 'Not configured', 'wp-allegro-audience' ) }
</span>
);
}
if ( corsOk === null ) {
return <Spinner />;
}
if ( corsOk ) {
return (
<span style={ { ...baseStyle, background: '#d8f0d8', color: '#1a6a1a' } }>
{ __( '● Connected', 'wp-allegro-audience' ) }
</span>
);
}
return (
<span style={ { ...baseStyle, background: '#fcf0d8', color: '#8a5c0a' } }>
{ __( '⚠ CORS not configured', 'wp-allegro-audience' ) }
</span>
);
}

async function checkCors( url: string ): Promise< boolean > {
try {
await fetch( `${ url }/client.js`, {
method: 'GET',
mode: 'cors',
cache: 'no-store',
} );
return true;
} catch {
return false;
}
}

export function AllegroSettingsPage() {
const { restUrl, nonce, tenantUrl: initialUrl } =
window.allegroAudienceSettings;

const [ url, setUrl ] = useState( initialUrl );
const [ savedUrl, setSavedUrl ] = useState( initialUrl );
const [ corsOk, setCorsOk ] = useState< boolean | null >(
initialUrl ? null : false
);
const [ verification, setVerification ] =
useState< VerificationState >( INITIAL_VERIFICATION );
const [ isSaving, setIsSaving ] = useState( false );
const [ saveSuccess, setSaveSuccess ] = useState( false );

// Auto-check CORS on load when a URL is already saved.
useEffect( () => {
if ( ! initialUrl ) return;
checkCors( initialUrl ).then( setCorsOk );
}, [ initialUrl ] );

const handleSave = useCallback( async () => {
setIsSaving( true );
setSaveSuccess( false );
setVerification( {
health: 'pending',
cors: 'idle',
healthError: '',
corsError: '',
} );

// Step 1: server-side health check via REST.
try {
await apiFetch( {
url: restUrl,
method: 'POST',
data: { tenant_url: url },
headers: { 'X-WP-Nonce': nonce },
} );
} catch ( err: unknown ) {
const message =
err instanceof Error
? err.message
: __( 'Could not connect to the Allegro instance.', 'wp-allegro-audience' );
setVerification( ( v ) => ( { ...v, health: 'error', healthError: message } ) );
setIsSaving( false );
return;
}

setVerification( ( v ) => ( { ...v, health: 'success', cors: 'pending' } ) );

// Step 2: in-browser CORS check.
const ok = await checkCors( url );
setCorsOk( ok );
setSavedUrl( url );
setSaveSuccess( true );
setVerification( ( v ) => ( {
...v,
cors: ok ? 'success' : 'error',
corsError: ok
? ''
: __( 'CORS is not yet configured for this domain.', 'wp-allegro-audience' ),
} ) );

setIsSaving( false );
}, [ url, restUrl, nonce ] );

const isDirty = url !== savedUrl;
const hasSteps = verification.health !== 'idle' || verification.cors !== 'idle';

return (
<Fragment>
<h1 style={ { marginBottom: '1em' } }>
{ __( 'Allegro Audience', 'wp-allegro-audience' ) }
</h1>
<Card style={ { maxWidth: '640px' } }>
<CardHeader>
<Flex justify="space-between" align="center" style={ { width: '100%' } }>
<FlexItem>
<strong>
{ __( 'Connection Settings', 'wp-allegro-audience' ) }
</strong>
</FlexItem>
<FlexItem>
<ConnectionBadge tenantUrl={ savedUrl } corsOk={ corsOk } />
</FlexItem>
</Flex>
</CardHeader>
<CardBody>
<div style={ { display: 'flex', flexDirection: 'column', gap: '16px' } }>
<TextControl
label={ __( 'Allegro Organization URL', 'wp-allegro-audience' ) }
help={ __(
'The base URL of your Allegro CDP instance, e.g. https://your-org.allegrocdp.com',
'wp-allegro-audience'
) }
type="url"
value={ url }
onChange={ ( val: string ) => {
setUrl( val );
setSaveSuccess( false );
setVerification( INITIAL_VERIFICATION );
} }
placeholder="https://your-org.allegrocdp.com"
disabled={ isSaving }
__nextHasNoMarginBottom
/>
<Button
variant="primary"
onClick={ handleSave }
disabled={ isSaving || ! url }
isBusy={ isSaving }
>
{ isDirty || ! savedUrl
? __( 'Save & Verify', 'wp-allegro-audience' )
: __( 'Re-verify', 'wp-allegro-audience' ) }
</Button>
{ hasSteps && (
<div
style={ {
display: 'flex',
flexDirection: 'column',
gap: '8px',
borderLeft: '3px solid #dcdcde',
paddingLeft: '12px',
} }
>
<Flex gap={ 2 } align="center">
<StatusIcon status={ verification.health } />
<span>
{ __( 'Server health check', 'wp-allegro-audience' ) }
</span>
</Flex>
{ verification.health === 'error' && verification.healthError && (
<Notice status="error" isDismissible={ false }>
{ verification.healthError }
</Notice>
) }
{ verification.cors !== 'idle' && (
<Flex gap={ 2 } align="center">
<StatusIcon status={ verification.cors } />
<span>
{ __( 'CORS configuration', 'wp-allegro-audience' ) }
</span>
</Flex>
) }
{ verification.cors === 'error' && (
<Notice status="warning" isDismissible={ false }>
{ __(
'CORS is not configured for this domain. Your Allegro instance needs to allow cross-origin requests from this site. ',
'wp-allegro-audience'
) }
<ExternalLink href="https://docs.allegrocdp.com/developer/">
{ __( 'View developer documentation', 'wp-allegro-audience' ) }
</ExternalLink>
.
</Notice>
) }
</div>
) }
{ saveSuccess && verification.cors === 'success' && (
<Notice status="success" isDismissible={ false }>
{ __(
'Allegro Audience is connected and client.js will be loaded on all frontend pages.',
'wp-allegro-audience'
) }
</Notice>
) }
</div>
</CardBody>
</Card>
</Fragment>
);
}
