/**
 * Allegro Audience Admin Settings Entry
 *
 * @package wp-allegro-audience
 */

import { createElement, render } from '@wordpress/element';
import { AllegroSettingsPage } from './Settings';

const root = document.getElementById( 'allegro-settings-app' );

if ( root ) {
	render( createElement( AllegroSettingsPage, null ), root );
}
