<?php
/**
 * LoadClientScriptTest
 *
 * @package allegro-audience
 */

declare(strict_types=1);

namespace Alley\WP\Allegro_Audience\Tests\Feature;

use Alley\WP\Allegro_Audience\Features\Allegro_Settings;
use Alley\WP\Allegro_Audience\Features\Load_Client_Script;
use Alley\WP\Allegro_Audience\Tests\TestCase;

/**
 * Tests for the Load_Client_Script feature.
 */
class LoadClientScriptTest extends TestCase {

	/**
	 * Test that no script tag is output when the tenant URL option is not set.
	 */
	public function test_no_script_tag_when_option_not_set(): void {
		delete_option( Allegro_Settings::OPTION_TENANT_URL );

		$feature = new Load_Client_Script();
		$feature->boot();

		ob_start();
		do_action( 'wp_head' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$output = (string) ob_get_clean();

		$this->assertStringNotContainsString( 'client.js', $output );
	}

	/**
	 * Test that the client.js script tag is output when the tenant URL option is set.
	 */
	public function test_script_tag_output_when_option_set(): void {
		update_option( Allegro_Settings::OPTION_TENANT_URL, 'https://my-org.allegrocdp.com' );

		$feature = new Load_Client_Script();
		$feature->boot();

		ob_start();
		do_action( 'wp_head' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( '/client.js', $output );
		$this->assertStringContainsString( 'my-org.allegrocdp.com', $output );
	}
}
