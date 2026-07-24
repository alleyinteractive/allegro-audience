<?php
/**
 * Allegro_Settings class file
 *
 * @package allegro-audience
 */

declare(strict_types=1);

namespace Alley\WP\Allegro_Audience\Features;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Allegro Audience settings page under Settings → Allegro Audience.
 */
class Allegro_Settings {

	/**
	 * WordPress option name for the tenant URL.
	 */
	public const OPTION_TENANT_URL = 'allegro_audience_tenant_url';

	/**
	 * REST API namespace.
	 */
	private const string REST_NAMESPACE = 'allegro-audience/v1';

	/**
	 * Admin page slug.
	 */
	public const PAGE_SLUG = 'allegro-audience';

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'admin_menu', $this->add_settings_page( ... ) );
		add_action( 'admin_enqueue_scripts', $this->enqueue_settings_assets( ... ) );
		add_action( 'rest_api_init', $this->register_rest_routes( ... ) );
		add_filter( 'plugin_action_links_allegro-audience/allegro-audience.php', $this->add_settings_link( ... ) );
	}

	/**
	 * Enqueue the settings page script and pass configuration to it.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function enqueue_settings_assets( string $hook_suffix ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_script(
			'allegro-audience-settings',
			plugins_url( 'settings.js', __FILE__ ),
			[],
			WP_ALLEGRO_AUDIENCE_VERSION,
			true,
		);

		wp_add_inline_script(
			'allegro-audience-settings',
			'window.allegroAudienceSettings = ' . wp_json_encode( $this->settings_config() ) . ';',
			'before',
		);
	}

	/**
	 * Build the configuration object passed to the settings page script.
	 *
	 * @return array<string, mixed>
	 */
	private function settings_config(): array {
		return [
			'tenantUrl' => $this->get_saved_url(),
			'restUrl'   => rest_url( self::REST_NAMESPACE . '/settings' ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'docsUrl'   => 'https://docs.allegrocdp.com/developer/',
			'l10n'      => [
				'saving'         => __( 'Saving…', 'allegro-audience' ),
				'saveVerify'     => __( 'Save & Verify', 'allegro-audience' ),
				'notConfigured'  => __( 'Not configured', 'allegro-audience' ),
				'connected'      => __( '● Connected', 'allegro-audience' ),
				'corsWarning'    => __( '⚠ CORS not configured', 'allegro-audience' ),
				'successMessage' => __( 'Allegro Audience is connected. client.js will be loaded on every front-end page.', 'allegro-audience' ),
				'corsMessage'    => __( 'CORS is not configured for this domain. Your Allegro instance needs to allow cross-origin requests from this WordPress site. ', 'allegro-audience' ),
				'docsLinkText'   => __( 'View developer documentation', 'allegro-audience' ),
				'networkError'   => __( 'Could not reach the Allegro instance. Please check the URL and try again.', 'allegro-audience' ),
			],
		];
	}

	/**
	 * Add the settings page under the Settings menu.
	 */
	public function add_settings_page(): void {
		add_options_page(
			__( 'Allegro Audience', 'allegro-audience' ),
			__( 'Allegro Audience', 'allegro-audience' ),
			'manage_options',
			self::PAGE_SLUG,
			$this->render_settings_page( ... ),
		);
	}

	/**
	 * Add a Settings link to the plugin's action links on the Plugins screen.
	 *
	 * @param string[] $links Existing action links.
	 * @return string[]
	 */
	public function add_settings_link( $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) ),
			__( 'Settings', 'allegro-audience' ),
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Register the REST API route for saving and verifying the tenant URL.
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
	 * Handle the REST API request to save and verify the tenant URL.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 */
	public function rest_save_settings( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$raw = $request->get_param( 'tenant_url' );
		$url = rtrim( is_string( $raw ) ? $raw : '', '/' );

		if ( '' === $url ) {
			return new WP_Error(
				'invalid_url',
				__( 'Please provide a valid URL.', 'allegro-audience' ),
				[ 'status' => 422 ],
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
	 * Return the currently saved tenant URL, or an empty string if not set.
	 */
	private function get_saved_url(): string {
		$value = get_option( self::OPTION_TENANT_URL, '' );
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Server-side health check: GET /up and verify the x-allegro-health header equals "1".
	 *
	 * @param string $url Tenant base URL.
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
				__( 'The URL does not appear to be a valid Allegro instance (missing x-allegro-health header).', 'allegro-audience' ),
			);
		}

		return true;
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tenant_url = $this->get_saved_url();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="card allegro-card">
				<h2>
					<?php esc_html_e( 'Connection Settings', 'allegro-audience' ); ?>
					<span id="allegro-badge"></span>
				</h2>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="allegro-tenant-url">
								<?php esc_html_e( 'Allegro Organization URL', 'allegro-audience' ); ?>
							</label>
						</th>
						<td>
							<input
								type="url"
								id="allegro-tenant-url"
								class="regular-text"
								value="<?php echo esc_attr( $tenant_url ); ?>"
								placeholder="https://your-org.allegrocdp.com"
							/>
							<p class="description">
								<?php esc_html_e( 'The base URL of your Allegro CDP instance.', 'allegro-audience' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<p>
					<button id="allegro-save-btn" class="button button-primary" type="button">
						<?php esc_html_e( 'Save & Verify', 'allegro-audience' ); ?>
					</button>
				</p>

				<div id="allegro-steps">
					<div id="allegro-step-health" class="allegro-step">
						<span class="allegro-step-icon"></span>
						<span><?php esc_html_e( 'Server health check', 'allegro-audience' ); ?></span>
					</div>
					<div id="allegro-step-cors" class="allegro-step">
						<span class="allegro-step-icon"></span>
						<span><?php esc_html_e( 'CORS configuration', 'allegro-audience' ); ?></span>
					</div>
				</div>

				<div id="allegro-notice" role="alert"></div>
			</div>
		</div>

		<style>
			.allegro-card { max-width: 640px; padding: 16px 20px; }
			.allegro-card h2 { display: flex; align-items: center; justify-content: space-between; margin-top: 0; font-size: 14px; }
			#allegro-badge { font-size: 12px; font-weight: 500; padding: 2px 10px; border-radius: 3px; }
			#allegro-badge.badge-not-configured { background: #dcdcde; color: #50575e; }
			#allegro-badge.badge-connected { background: #d8f0d8; color: #1a6a1a; }
			#allegro-badge.badge-cors-warning { background: #fcf0d8; color: #8a5c0a; }
			#allegro-steps { display: none; margin: 12px 0 0; border-left: 3px solid #dcdcde; padding-left: 12px; }
			.allegro-step { display: none; align-items: center; gap: 8px; margin: 6px 0; font-size: 13px; }
			.allegro-step-icon { display: flex; align-items: center; width: 20px; }
			#allegro-notice .notice { margin: 12px 0 0; }
		</style>
		<?php
	}
}
