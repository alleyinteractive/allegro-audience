<?php
/**
 * Feature: Load_Client_Script
 *
 * @package wp-allegro-audience
 */

declare(strict_types=1);

namespace Alley\WP\Allegro_Audience\Features;

/**
 * Injects the Allegro client.js script tag in wp_head on the frontend.
 */
class Load_Client_Script {

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'wp_head', $this->output_client_script( ... ), 1 );
	}

	/**
	 * Output the Allegro client.js script tag.
	 */
	public function output_client_script(): void {
		if ( is_admin() ) {
			return;
		}

		$raw_url    = get_option( Allegro_Settings::OPTION_TENANT_URL, '' );
		$tenant_url = is_string( $raw_url ) ? $raw_url : '';

		if ( '' === $tenant_url ) {
			return;
		}

		printf( '<script src="%s"></script>' . "\n", esc_url( $tenant_url . '/client.js' ) ); // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
	}
}
