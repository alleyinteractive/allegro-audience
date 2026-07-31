<?php
/**
 * Allegro Audience Tests: Allegro Settings Feature Test
 *
 * @package allegro-audience
 */

declare(strict_types=1);

namespace Allegro_Audience\Tests\Feature;

use Allegro_Audience\Features\Allegro_Settings;
use Allegro_Audience\Tests\TestCase;
use Mantle\Testing\Mock_Http_Response;
use WP_REST_Request;

/**
 * Tests for the Allegro_Settings feature.
 */
class AllegroSettingsTest extends TestCase {

	/**
	 * Admin user ID.
	 */
	private int $admin_id;

	/**
	 * Set up the test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->admin_id = static::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $this->admin_id );

		( new Allegro_Settings() )->boot();
		do_action( 'rest_api_init' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Test that the settings page renders the URL input field.
	 */
	public function test_settings_page_renders(): void {
		ob_start();
		( new Allegro_Settings() )->render_settings_page();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'allegro-tenant-url', $output );
		$this->assertStringContainsString( 'allegro-save-btn', $output );
	}

	/**
	 * Test that the REST endpoint requires authentication.
	 */
	public function test_rest_endpoint_requires_auth(): void {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', '/allegro-audience/v1/settings' );
		$request->set_param( 'tenant_url', 'https://example.com' );

		$response = rest_do_request( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * Test that the REST endpoint rejects an empty URL.
	 */
	public function test_rest_endpoint_rejects_empty_url(): void {
		$request = new WP_REST_Request( 'POST', '/allegro-audience/v1/settings' );
		$request->set_param( 'tenant_url', '' );

		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), [ 400, 422 ] );
	}

	/**
	 * Test that the REST endpoint returns 422 when health check fails.
	 */
	public function test_rest_endpoint_rejects_non_allegro_url(): void {
		$this->fake_request(
			'https://not-allegro.example.com/up',
			Mock_Http_Response::create(),
		);

		$request = new WP_REST_Request( 'POST', '/allegro-audience/v1/settings' );
		$request->set_param( 'tenant_url', 'https://not-allegro.example.com' );

		$response = rest_do_request( $request );

		$this->assertSame( 422, $response->get_status() );
	}

	/**
	 * Test that the REST endpoint saves a valid Allegro URL.
	 */
	public function test_rest_endpoint_saves_valid_url(): void {
		$this->fake_request(
			'https://my-org.allegrocdp.com/up',
			Mock_Http_Response::create()->with_header( 'x-allegro-health', '1' ),
		);

		$request = new WP_REST_Request( 'POST', '/allegro-audience/v1/settings' );
		$request->set_param( 'tenant_url', 'https://my-org.allegrocdp.com' );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			'https://my-org.allegrocdp.com',
			get_option( Allegro_Settings::OPTION_TENANT_URL )
		);
	}
}
