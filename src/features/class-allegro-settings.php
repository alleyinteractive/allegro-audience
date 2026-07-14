<?php
/**
 * Feature: Allegro_Settings
 *
 * @package wp-allegro-audience
 */

declare(strict_types=1);

namespace Alley\WP\Allegro_Audience\Features;

use Alley\WP\Types\Feature;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Registers the Allegro Audience settings page under Settings → Allegro Audience.
 */
class Allegro_Settings implements Feature {

	/**
	 * Option name for the tenant URL.
	 */
	public const OPTION_TENANT_URL = 'allegro_audience_tenant_url';

	/**
	 * REST API namespace.
	 */
	private const REST_NAMESPACE = 'wp-allegro-audience/v1';

	/**
	 * Settings page slug.
	 */
	public const PAGE_SLUG = 'allegro-audience';

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Add the settings page under the Settings menu.
	 */
	public function add_settings_page(): void {
		add_options_page(
			__( 'Allegro Audience', 'wp-allegro-audience' ),
			__( 'Allegro Audience', 'wp-allegro-audience' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_settings_page' ],
		);
	}

	/**
	 * Register the REST API route for saving settings.
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/settings',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_save_settings' ],
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
				'args'                => [
					'tenant_url' => [
						'type'              => 'string',
						'required'          => true,
						'minLength'         => 1,
						'sanitize_callback' => 'sanitize_url',
					],
				],
			]
		);
	}

	/**
	 * Handle the REST API POST to save and verify the tenant URL.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error Response on success, WP_Error on failure.
	 */
	public function rest_save_settings( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$raw = $request->get_param( 'tenant_url' );
		$url = rtrim( is_string( $raw ) ? $raw : '', '/' );

		if ( '' === $url ) {
			return new WP_Error(
				'invalid_url',
				__( 'Please provide a valid URL.', 'wp-allegro-audience' ),
				[ 'status' => 422 ]
			);
		}

		$health = $this->check_health( $url );

		if ( is_wp_error( $health ) ) {
			$health->add_data( [ 'status' => 422 ] );
			return $health;
		}

		update_option( self::OPTION_TENANT_URL, $url );

		return new WP_REST_Response( [ 'tenant_url' => $url ], 200 );
	}

	/**
	 * Perform a server-side health check by requesting /up and verifying the x-allegro-health header.
	 *
	 * @param string $url Tenant base URL.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function check_health( string $url ): true|WP_Error {
		$response = wp_remote_get( "{$url}/up" ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$header = wp_remote_retrieve_header( $response, 'x-allegro-health' );

		if ( ! is_string( $header ) || '1' !== $header ) {
			return new WP_Error(
				'health_check_failed',
				__( 'The URL does not appear to be a valid Allegro instance (missing x-allegro-health header).', 'wp-allegro-audience' ),
			);
		}

		return true;
	}

	/**
	 * Render the settings page — outputs the React app mount point.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
<div class="wrap">
<div id="allegro-settings-app"></div>
</div>
		<?php
	}
}
