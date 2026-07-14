<?php
/**
 * AllegroSettingsTest
 *
 * @package wp-allegro-audience
 */

declare(strict_types=1);

namespace Alley\WP\Allegro_Audience\Tests\Feature;

use Alley\WP\Allegro_Audience\Features\Allegro_Settings;
use Alley\WP\Allegro_Audience\Tests\TestCase;
use Mantle\Testing\Mock_Http_Response;

/**
 * Tests for the Allegro_Settings feature.
 */
class AllegroSettingsTest extends TestCase {

	/**
	 * Feature instance.
	 */
	private Allegro_Settings $feature;

	/**
	 * Set up the test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->feature = new Allegro_Settings();
		$this->feature->boot();
	}

	/**
	 * Test that the settings page renders with expected markup.
	 */
	public function test_settings_page_renders(): void {
		$this->acting_as( 'administrator' );

		ob_start();
		$this->feature->render_settings_page();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( '<form method="post"', $output );
		$this->assertStringContainsString( 'Save &amp; Verify', $output );
		$this->assertStringContainsString( 'options.php', $output );
	}

	/**
	 * Test that an empty value is rejected and returns an empty string.
	 */
	public function test_sanitize_tenant_url_rejects_empty(): void {
		$result = $this->feature->sanitize_tenant_url( '' );

		$this->assertSame( '', $result );
	}

	/**
	 * Test that a URL without a valid Allegro health response is rejected.
	 */
	public function test_sanitize_tenant_url_rejects_non_allegro_url(): void {
		$this->fake_request(
			'https://example.com/up',
			Mock_Http_Response::create(),
		);

		update_option( Allegro_Settings::OPTION_TENANT_URL, '' );

		$result = $this->feature->sanitize_tenant_url( 'https://example.com' );

		$this->assertSame( '', $result );

		$errors = get_settings_errors( Allegro_Settings::OPTION_TENANT_URL );
		$this->assertNotEmpty( $errors );
		$this->assertSame( 'health_check_failed', $errors[0]['code'] );
	}

	/**
	 * Test that a valid Allegro URL (with x-allegro-health: 1) is accepted.
	 */
	public function test_sanitize_tenant_url_accepts_valid_allegro_url(): void {
		$this->fake_request(
			'https://my-org.allegrocdp.com/up',
			Mock_Http_Response::create()->with_header( 'x-allegro-health', '1' ),
		);

		$result = $this->feature->sanitize_tenant_url( 'https://my-org.allegrocdp.com/' );

		$this->assertSame( 'https://my-org.allegrocdp.com', $result );

		$errors  = get_settings_errors( Allegro_Settings::OPTION_TENANT_URL );
		$success = array_filter( $errors, fn( $e ) => 'success' === $e['type'] );
		$this->assertNotEmpty( $success );
	}
}
