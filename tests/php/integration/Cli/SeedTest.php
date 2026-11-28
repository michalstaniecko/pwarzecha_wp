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

	/**
	 * Data that makes the database non-empty.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function non_empty_databases(): array {
		return array(
			'Lokalizacja (draft)' => array( 'lokalizacja', 'draft' ),
			'Karnet'              => array( 'karnet', 'publish' ),
			'published page'      => array( 'page', 'publish' ),
		);
	}

	/**
	 * Any Lokalizacja or Karnet, or a published page, blocks the seed.
	 *
	 * @dataProvider non_empty_databases
	 *
	 * @param string $post_type Post type of the existing content.
	 * @param string $status    Its status.
	 */
	public function test_seed_refuses_a_non_empty_database_without_force( string $post_type, string $status ): void {
		self::factory()->post->create(
			array(
				'post_type'   => $post_type,
				'post_status' => $status,
				'post_title'  => 'Treść Zleceniodawcy',
			)
		);

		try {
			( new Seed_Command() )->run( array(), array() );
			$this->fail( 'Seed ran on a non-empty database.' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( '--force', $e->getMessage() );
		}
		$this->assertSame(
			array(),
			get_terms(
				array(
					'taxonomy'   => 'grupa',
					'hide_empty' => false,
				)
			)
		);
	}

	public function test_default_sample_page_and_draft_pages_do_not_count_as_content(): void {
		self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => 'przykladowa-strona',
				'post_title'  => 'Przykładowa strona',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
			)
		);

		( new Seed_Command() )->run( array(), array() );

		$this->assertCount( 2, pw_lokalizacje() );
	}

	public function test_force_seeds_a_non_empty_database(): void {
		self::factory()->post->create( array( 'post_type' => 'karnet' ) );

		( new Seed_Command() )->run( array(), array( 'force' => true ) );

		$this->assertCount( 2, pw_lokalizacje() );
	}

	public function test_seed_creates_the_working_grupy_and_a_grafik_per_lokalizacja(): void {
		( new Seed_Command() )->run( array(), array() );

		$this->assertSame(
			array( 'Dzieci 6–9 lat', 'Dzieci 10–13 lat', 'Młodzież 14+' ),
			array_column( pw_grupy(), 'nazwa' )
		);

		foreach ( pw_lokalizacje() as $lokalizacja ) {
			$grafik = pw_grafik( $lokalizacja['id'] );
			$this->assertGreaterThanOrEqual( 2, count( $grafik ), $lokalizacja['nazwa'] );
			$this->assertLessThanOrEqual( 3, count( $grafik ), $lokalizacja['nazwa'] );
		}
	}
}
