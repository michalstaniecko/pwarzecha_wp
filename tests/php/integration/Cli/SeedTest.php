<?php
/**
 * `wp pw seed` (#54): starting state of every environment, called without the WP-CLI runner.
 *
 * @package PW
 */

namespace PW\Tests\Integration\Cli;

use PW\Cli\Seed_Command;
use WP_UnitTestCase;

/**
 * Seed on a real WordPress: what it creates, idempotency and the empty-database guard.
 */
class SeedTest extends WP_UnitTestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		require_once get_theme_file_path( 'inc/cli/class-seed-command.php' );
	}

	public function set_up(): void {
		parent::set_up();
		// WP_UnitTestCase::tear_down() unregisters every meta key; `init` does not run again.
		\PW\Data\Lokalizacja\register_meta();
		\PW\Data\Grafik\register_meta();
		pw_karnet_register();
	}

	public function test_seed_on_an_empty_database_creates_lokalizacje_in_order(): void {
		( new Seed_Command() )->run( array(), array() );

		$lokalizacje = pw_lokalizacje();

		$this->assertSame( array( 'Chorzów', 'Katowice' ), array_column( $lokalizacje, 'nazwa' ) );
		$this->assertSame( array( 'chorzow', 'katowice' ), array_column( $lokalizacje, 'kotwica' ) );
		$this->assertSame( array( 'Chorzów', 'Katowice' ), array_column( $lokalizacje, 'miasto' ) );
		$this->assertSame( array( '[...]', '[...]' ), array_column( $lokalizacje, 'ulica' ) );
	}
}
