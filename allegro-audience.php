<?php
/**
 * Plugin Name: Allegro Audience
 * Plugin URI: https://github.com/alleyinteractive/allegro-audience
 * Description: WordPress Plugin to include Allegro Audience on your site.
 * Version: 0.1.0
 * Author: Allegro Audience
 * Author URI: https://allegroaudience.com/
 * Requires at least: 6.5
 * Requires PHP: 8.3
 * Tested up to: 6.7
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Text Domain: allegro-audience
 * Domain Path: /languages/
 *
 * @package allegro-audience
 */

namespace Alley\WP\Allegro_Audience;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Root directory to this plugin.
 */
define( 'WP_ALLEGRO_AUDIENCE_DIR', __DIR__ );

// Load the plugin's runtime classes (no Composer autoloader required).
require_once __DIR__ . '/src/features/class-allegro-settings.php';
require_once __DIR__ . '/src/features/class-load-client-script.php';
require_once __DIR__ . '/src/main.php';

main();
