<?php
/**
 * Enqueue the Allegro Audience admin settings assets.
 *
 * @package wp-allegro-audience
 */

declare(strict_types=1);

namespace Alley\WP\Allegro_Audience;

use Alley\WP\Allegro_Audience\Features\Allegro_Settings;

add_action(
	'admin_enqueue_scripts',
	function ( string $hook ): void {
		if ( 'settings_page_' . Allegro_Settings::PAGE_SLUG !== $hook ) {
			return;
		}

		$asset_file = __DIR__ . '/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		/** @var array{dependencies: string[], version: string} Asset manifest. */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort
		$asset = require $asset_file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable

		wp_enqueue_script(
			'allegro-audience-admin-settings',
			plugin_dir_url( __FILE__ ) . 'index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_localize_script(
			'allegro-audience-admin-settings',
			'allegroAudienceSettings',
			[
				'restUrl'   => rest_url( 'wp-allegro-audience/v1/settings' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'tenantUrl' => (string) get_option( Allegro_Settings::OPTION_TENANT_URL, '' ),
			]
		);
	}
);
