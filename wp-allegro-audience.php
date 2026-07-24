<?php
/**
 * Plugin Name: Allegro Audience
 * Plugin URI: https://github.com/alleyinteractive/wp-allegro-audience
 * Description: WordPress Plugin to include Allegro Audience on your site.
 * Version: 0.1.0
 * Author: Allegro Audience
 * Author URI: https://github.com/alleyinteractive/wp-allegro-audience
 * Requires at least: 6.5
 * Requires PHP: 8.3
 * Tested up to: 7.0
 *
 * Text Domain: wp-allegro-audience
 * Domain Path: /languages/
 *
 * @package wp-allegro-audience
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
