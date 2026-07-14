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

/**
 * Registers the Allegro Audience settings page under Settings → Allegro Audience.
 */
class Allegro_Settings implements Feature {

	/**
	 * Option name for the tenant URL.
	 */
	public const OPTION_TENANT_URL = 'allegro_audience_tenant_url';

	/**
	 * Settings group name.
	 */
	private const SETTINGS_GROUP = 'allegro_audience';

	/**
	 * Settings page slug.
	 */
	public const PAGE_SLUG = 'allegro-audience';

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_cors_check' ] );
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
	 * Register settings, sections, and fields.
	 */
	public function register_settings(): void {
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_TENANT_URL,
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_tenant_url' ],
			]
		);

		add_settings_section(
			'allegro_audience_main',
			'',
			'__return_false',
			self::PAGE_SLUG,
		);

		add_settings_field(
			self::OPTION_TENANT_URL,
			__( 'Allegro Organization URL', 'wp-allegro-audience' ),
			[ $this, 'render_url_field' ],
			self::PAGE_SLUG,
			'allegro_audience_main',
		);
	}

	/**
	 * Sanitize and verify the tenant URL via a server-side health check.
	 *
	 * @param mixed $value Raw value from POST data.
	 * @return string Sanitized URL, or previous option value on failure.
	 */
	public function sanitize_tenant_url( mixed $value ): string {
		if ( ! is_string( $value ) || '' === $value ) {
			return '';
		}

		$url = rtrim( esc_url_raw( $value ), '/' );

		if ( '' === $url ) {
			add_settings_error(
				self::OPTION_TENANT_URL,
				'invalid_url',
				__( 'Please enter a valid URL.', 'wp-allegro-audience' ),
			);
			return $this->previous_tenant_url();
		}

		$health = $this->check_health( $url );

		if ( is_wp_error( $health ) ) {
			add_settings_error(
				self::OPTION_TENANT_URL,
				'health_check_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'Could not verify Allegro instance: %s', 'wp-allegro-audience' ),
					$health->get_error_message(),
				),
			);
			return $this->previous_tenant_url();
		}

		add_settings_error(
			self::OPTION_TENANT_URL,
			'settings_updated',
			__( 'Allegro Organization URL saved and verified.', 'wp-allegro-audience' ),
			'success',
		);

		return $url;
	}

	/**
	 * Get the currently saved tenant URL option as a string, defaulting to empty string.
	 */
	private function previous_tenant_url(): string {
		$value = get_option( self::OPTION_TENANT_URL, '' );
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Perform a server-side health check by requesting /up and verifying the x-allegro-health header.
	 *
	 * @param string $url Tenant base URL.
	 * @return true|\WP_Error True on success, WP_Error on failure.
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
				__( 'The URL does not appear to be a valid Allegro instance.', 'wp-allegro-audience' ),
			);
		}

		return true;
	}

	/**
	 * Render the Allegro Organization URL input field.
	 */
	public function render_url_field(): void {
		$value = $this->previous_tenant_url();
		?>
		<input
			type="url"
			id="<?php echo esc_attr( self::OPTION_TENANT_URL ); ?>"
			name="<?php echo esc_attr( self::OPTION_TENANT_URL ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<?php if ( '' !== $value ) : ?>
		<div id="allegro-cors-status"></div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button( __( 'Save & Verify', 'wp-allegro-audience' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Enqueue the in-browser CORS check inline script when on the settings page and a URL is saved.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function maybe_enqueue_cors_check( string $hook ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		$tenant_url = $this->previous_tenant_url();

		if ( '' === $tenant_url ) {
			return;
		}

		$url_json = wp_json_encode( $tenant_url );

		if ( false === $url_json ) {
			return;
		}

		// phpcs:disable Generic.Strings.UnnecessaryStringConcat.Found
		$js = sprintf(
			'(function(){' .
				'var u=%1$s;' .
				'var el=document.getElementById("allegro-cors-status");' .
				'if(!el)return;' .
				'fetch(u+"/client.js",{mode:"cors"})' .
					'.then(function(){el.innerHTML=\'<span style="color:green">&#10003; CORS is configured for this WordPress instance.</span>\';})' .
					'.catch(function(){el.innerHTML=\'<span style="color:darkorange">&#9888; CORS is not configured. <a href="https://docs.allegrocdp.com/developer/" target="_blank" rel="noopener noreferrer">See the developer documentation</a> to enable it.</span>\';});' .
			'})();',
			$url_json,
		);
		// phpcs:enable Generic.Strings.UnnecessaryStringConcat.Found

		wp_add_inline_script( 'wp-a11y', $js, 'after' );
	}
}
