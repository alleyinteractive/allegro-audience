<?php
/**
 * The main plugin function
 *
 * @package allegro-audience
 */

declare(strict_types=1);

namespace Allegro_Audience;

use Allegro_Audience\Features\Allegro_Settings;
use Allegro_Audience\Features\Load_Client_Script;

defined( 'ABSPATH' ) || exit;

/**
 * Instantiate the plugin.
 */
function main(): void {
	( new Allegro_Settings() )->boot();
	( new Load_Client_Script() )->boot();
}
