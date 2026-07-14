<?php
/**
 * Plugin Name: Allegro Audience
 * Plugin URI: https://github.com/alleyinteractive/wp-allegro-audience
 * Description: WordPress Plugin to include Allegro Audience on your site.
 * Version: 0.0.0
 * Author: Allegro Audience
 * Author URI: https://github.com/alleyinteractive/wp-allegro-audience
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * Tested up to: 6.8
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

// Check if Composer is installed (remove if Composer is not required for your plugin).
if ( ! file_exists( __DIR__ . '/vendor/wordpress-autoload.php' ) ) {
	// Will also check for the presence of an already loaded Composer autoloader
	// to see if the Composer dependencies have been installed in a parent
	// folder. This is useful for when the plugin is loaded as a Composer
	// dependency in a larger project.
	if ( ! class_exists( \Composer\InstalledVersions::class ) ) {
		\add_action(
			'admin_notices',
			function () {
				?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Composer is not installed and wp-allegro-audience cannot load. Try using a `*-built` branch if the plugin is being loaded as a submodule.', 'wp-allegro-audience' ); ?></p>
				</div>
				<?php
			}
		);

		return;
	}
} else {
	// Load Composer dependencies.
	require_once __DIR__ . '/vendor/wordpress-autoload.php';
}

// Load the plugin's main files.
require_once __DIR__ . '/src/meta.php';
require_once __DIR__ . '/src/main.php';

register_post_meta_from_defs();
register_term_meta_from_defs();
main();
