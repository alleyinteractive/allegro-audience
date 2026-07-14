<?php
/**
 * Allegro Audience Tests: Example Feature Test
 *
 * @package wp-allegro-audience
 */

namespace Alley\WP\Allegro_Audience\Tests\Feature;

use Alley\WP\Allegro_Audience\Tests\TestCase;

/**
 * A test suite for an example feature.
 *
 * @link https://mantle.alley.com/testing/test-framework.html
 */
class ExampleFeatureTest extends TestCase {
	/**
	 * An example test for the example feature. In practice, this should be updated to test an aspect of the feature.
	 */
	public function test_example(): void {
		$this->assertTrue( true );
		$this->assertNotEmpty( home_url() );
	}
}
