<?php
/**
 * Allegro Audience Tests: Base Test Class
 *
 * @package allegro-audience
 */

declare(strict_types=1);

namespace Allegro_Audience\Tests;

use Mantle\Testing\Concerns\Prevent_Remote_Requests;
use Mantle\Testkit\Test_Case as TestkitTest_Case;

/**
 * Allegro Audience Base Test Case
 */
abstract class TestCase extends TestkitTest_Case {
	use Prevent_Remote_Requests;
}
