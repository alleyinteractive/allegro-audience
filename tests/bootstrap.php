<?php
/**
 * Allegro Audience Tests: Bootstrap
 *
 * @package allegro-audience
 */

declare(strict_types=1);

/**
 * Visit {@see https://mantle.alley.com/testing/test-framework.html} to learn more.
 */
\Mantle\Testing\manager()
	// Rsync the plugin to plugins/allegro-audience when testing.
	->maybe_rsync_plugin()
	// Load the main file of the plugin.
	->loaded( fn () => require_once __DIR__ . '/../allegro-audience.php' )
	->install();
