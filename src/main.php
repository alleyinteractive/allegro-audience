<?php
/**
 * The main plugin function
 *
 * @package wp-allegro-audience
 */

namespace Alley\WP\Allegro_Audience;

use Alley\WP\Allegro_Audience\Features\Allegro_Settings;
use Alley\WP\Allegro_Audience\Features\Load_Client_Script;
use Alley\WP\Features\Group;

/**
 * Instantiate the plugin.
 */
function main(): void {
	$plugin = new Group(
		new Allegro_Settings(),
		new Load_Client_Script(),
	);

	$plugin->boot();
}
