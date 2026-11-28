<?php
/**
 * Choosing which migrations to run (#36, #37).
 *
 * @package PW
 */

namespace PW\Tests\Unit\Migrations;

use PW\Tests\Unit\TestCase;

use function PW\Migrations\pending;

/**
 * `PW\Migrations\pending()`.
 */
final class PendingTest extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		require_once self::theme_path( 'inc/migrations/runner.php' );
	}

	public function test_returns_migrations_not_yet_done_sorted_by_id(): void {
		$available = array(
			'0003-c' => '/m/0003-c.php',
			'0001-a' => '/m/0001-a.php',
			'0002-b' => '/m/0002-b.php',
		);

		$this->assertSame(
			array(
				'0001-a' => '/m/0001-a.php',
				'0003-c' => '/m/0003-c.php',
			),
			pending( $available, array( '0002-b' ) )
		);
	}

	public function test_ignores_done_ids_whose_files_are_gone(): void {
		$this->assertSame(
			array( '0002-b' => '/m/0002-b.php' ),
			pending( array( '0002-b' => '/m/0002-b.php' ), array( '0001-removed' ) )
		);
	}

	public function test_nothing_pending_when_all_done(): void {
		$this->assertSame(
			array(),
			pending( array( '0001-a' => '/m/0001-a.php' ), array( '0001-a' ) )
		);
	}
}
