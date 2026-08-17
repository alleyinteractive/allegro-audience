<?php
/**
 * Plugin Name: Allegro Audience
 * Plugin URI: https://allegroaudience.com/
 * Description: WordPress Plugin to include Allegro Audience on your site.
 * Version: 0.1.2
 * Author: Alley Interactive
 * Author URI: https://alley.com/
 * Requires at least: 6.5
 * Requires PHP: 8.3
 * Tested up to: 7.0
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Text Domain: allegro-audience
 *
 * @package allegro-audience
 */

namespace Allegro_Audience;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Current plugin version, used for asset cache-busting.
 */
define( 'ALLEGRO_AUDIENCE_VERSION', '0.1.1' );

// Load the plugin's runtime classes (no Composer autoloader required).
require_once __DIR__ . '/src/features/class-allegro-settings.php';
require_once __DIR__ . '/src/features/class-load-client-script.php';
require_once __DIR__ . '/src/main.php';

main();
