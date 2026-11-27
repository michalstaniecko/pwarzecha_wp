<?php
/**
 * Styleguide module loading (#42): whether partials load is decided late, not at file load.
 *
 * @package PW
 */

namespace PW\Tests\Unit;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

/**
 * Loads theme/inc/styleguide/index.php once; Brain Monkey records its hooks.
 */
final class StyleguideLoaderTest extends TestCase {

	public function test_enabled_filter_added_after_the_theme_loads_still_loads_the_partials(): void {
		Functions\when( 'wp_get_environment_type' )->justReturn( 'production' );
		Filters\expectApplied( 'pw/styleguide/enabled' )->never();

		require_once self::theme_path( 'inc/styleguide/index.php' );

		$this->assertFalse( has_filter( 'pw/styleguide/groups' ), 'no partial at file load' );
		$this->assertSame( PHP_INT_MAX, has_action( 'wp_loaded', 'PW\Styleguide\load_partials' ) );

		// Theme code (e.g. after_setup_theme) has not enabled it yet: still nothing.
		Filters\expectApplied( 'pw/styleguide/enabled' )->once()->andReturn( false );
		\PW\Styleguide\load_partials();
		$this->assertFalse( has_filter( 'pw/styleguide/groups' ), 'disabled = no partials' );

		// Enabled by a filter added after the file loaded.
		Filters\expectApplied( 'pw/styleguide/enabled' )->once()->andReturn( true );
		\PW\Styleguide\load_partials();
		$this->assertTrue( has_filter( 'pw/styleguide/groups' ), 'enabled = partials registered their groups' );
	}
}
