<?php
/**
 * Feature: Load_Client_Script
 *
 * @package allegro-audience
 */

declare(strict_types=1);

namespace Alley\WP\Allegro_Audience\Features;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the Allegro client.js script on the front end.
 */
class Load_Client_Script {

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'wp_enqueue_scripts', $this->enqueue_client_script( ... ) );
	}

	/**
	 * Enqueue the Allegro client.js script for the configured tenant.
	 */
	public function enqueue_client_script(): void {
		$raw_url    = get_option( Allegro_Settings::OPTION_TENANT_URL, '' );
		$tenant_url = is_string( $raw_url ) ? $raw_url : '';

		if ( '' === $tenant_url ) {
			return;
		}

		wp_enqueue_script(
			'allegro-audience-client',
			$tenant_url . '/client.js',
			[],
			WP_ALLEGRO_AUDIENCE_VERSION,
			false,
		);
	}
}
