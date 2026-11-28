<?php
/**
 * Migration files on disk and executed IDs in the options (#36, #37).
 *
 * @package PW
 */

namespace PW\Tests\Unit\Migrations;

use Brain\Monkey\Functions;
use PW\Tests\Unit\TestCase;

use function PW\Migrations\available;
use function PW\Migrations\done;

/**
 * `PW\Migrations\available()` and `done()`.
 */
final class StateTest extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		require_once self::theme_path( 'inc/migrations/runner.php' );
	}

	public function test_available_lists_only_numbered_files_by_id(): void {
		$dir = dirname( __DIR__, 3 ) . '/fixtures/migrations';

		$this->assertSame(
			array( '0001-count-runs' => $dir . '/0001-count-runs.php' ),
			available( $dir )
		);
	}

	public function test_done_reads_executed_ids_as_strings(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'pw_migrations', array() )
			->andReturn(
				array(
					3 => '0001-a',
					4 => 2,
				)
			);

		$this->assertSame( array( '0001-a', '2' ), done() );
	}

	public function test_done_is_empty_when_the_option_is_corrupted(): void {
		Functions\when( 'get_option' )->justReturn( 'garbage' );

		$this->assertSame( array(), done() );
	}
}
