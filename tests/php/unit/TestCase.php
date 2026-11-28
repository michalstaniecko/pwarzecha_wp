<?php
/**
 * Base class of unit tests: Brain Monkey around every test (#37).
 *
 * Load the file under test with `require_once self::theme_path( 'inc/…' )` (in
 * set_up_before_class) – only files without side effects at load time, e.g. helpers.
 * Stub WP functions with `Brain\Monkey\Functions\when()` / `expect()`.
 *
 * @package PW
 */

namespace PW\Tests\Unit;

use Brain\Monkey;
use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;

/**
 * Brain Monkey set up and torn down per test; Mockery expectations count as assertions.
 */
abstract class TestCase extends PolyfillTestCase {

	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();
	}

	protected function tear_down(): void {
		Monkey\tearDown();
		parent::tear_down();
	}

	/**
	 * Absolute path of a file in theme/.
	 *
	 * @param string $relative Path relative to theme/.
	 */
	protected static function theme_path( string $relative ): string {
		return dirname( __DIR__, 3 ) . '/theme/' . ltrim( $relative, '/' );
	}
}
