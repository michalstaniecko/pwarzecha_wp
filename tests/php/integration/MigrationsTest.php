<?php
/**
 * Theme migrations in a real WordPress (#36, #37).
 *
 * @package PW
 */

namespace PW\Tests;

use function PW\Migrations\maybe_run;

/**
 * Migrations run on theme activation and after a theme Version change, each one only once.
 */
final class MigrationsTest extends \WP_UnitTestCase {

	private string $dir;

	public function set_up(): void {
		parent::set_up();
		delete_option( 'pw_migrations' );
		delete_option( 'pw_theme_version' );
		delete_option( 'pw_test_migration_runs' );
		$this->use_migrations( 'migrations' );
		add_filter( 'pw/migrations/dir', fn() => $this->dir );
	}

	public function test_pending_migration_runs_once_on_theme_activation(): void {
		$this->activate_theme();
		$this->activate_theme();

		$this->assertSame( array( '0001-count-runs' => 1 ), get_option( 'pw_test_migration_runs' ) );
	}

	public function test_new_migration_waits_for_a_version_change(): void {
		$this->activate_theme();
		$this->use_migrations( 'migrations-next' );

		maybe_run();
		$this->assertSame( array( '0001-count-runs' => 1 ), get_option( 'pw_test_migration_runs' ) );

		update_option( 'pw_theme_version', '0.0.0-previous' );
		maybe_run();
		$this->assertSame(
			array(
				'0001-count-runs' => 1,
				'0002-count-runs' => 1,
			),
			get_option( 'pw_test_migration_runs' )
		);
	}

	/**
	 * What WordPress does on the first request after the theme was switched to.
	 */
	private function activate_theme(): void {
		update_option( 'theme_switched', 'twentytwentyfive' );
		check_theme_switched();
	}

	/**
	 * Points the runner at a fixture directory.
	 *
	 * @param string $fixture Directory in tests/fixtures/.
	 */
	private function use_migrations( string $fixture ): void {
		$this->dir = dirname( __DIR__, 2 ) . '/fixtures/' . $fixture;
	}
}
