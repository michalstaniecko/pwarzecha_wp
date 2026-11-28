<?php
/**
 * Pure Grafik helpers: row order, row validation, day names (#50).
 *
 * @package PW
 */

namespace PW\Tests\Unit\Data;

use Brain\Monkey\Functions;
use PW\Tests\Unit\TestCase;

/**
 * Functions from theme/inc/data/grafik-helpers.php.
 */
final class GrafikHelpersTest extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		require_once self::theme_path( 'inc/data/grafik-helpers.php' );
	}

	public function test_sort_orders_rows_by_day_then_start_time(): void {
		$rows = array(
			array(
				'dzien' => 3,
				'od'    => '17:00',
				'grupa' => 1,
			),
			array(
				'dzien' => 1,
				'od'    => '18:30',
				'grupa' => 2,
			),
			array(
				'dzien' => 3,
				'od'    => '09:15',
				'grupa' => 3,
			),
			array(
				'dzien' => 1,
				'od'    => '08:00',
				'grupa' => 4,
			),
		);

		$this->assertSame( array( 4, 2, 3, 1 ), array_column( pw_grafik_sort( $rows ), 'grupa' ) );
	}

	public function test_sort_keeps_entry_order_of_rows_with_the_same_day_and_time(): void {
		$rows = array(
			array(
				'dzien' => 2,
				'od'    => '17:00',
				'grupa' => 7,
			),
			array(
				'dzien' => 1,
				'od'    => '17:00',
				'grupa' => 5,
			),
			array(
				'dzien' => 2,
				'od'    => '17:00',
				'grupa' => 3,
			),
			array(
				'dzien' => 2,
				'od'    => '17:00',
				'grupa' => 9,
			),
		);

		$this->assertSame( array( 5, 7, 3, 9 ), array_column( pw_grafik_sort( $rows ), 'grupa' ) );
	}

	public function test_sort_returns_a_list(): void {
		$sorted = pw_grafik_sort(
			array(
				5 => array(
					'dzien' => 2,
					'od'    => '10:00',
				),
				9 => array(
					'dzien' => 1,
					'od'    => '10:00',
				),
			)
		);

		$this->assertSame( array( 0, 1 ), array_keys( $sorted ) );
		$this->assertSame( array(), pw_grafik_sort( array() ) );
	}

	public function test_day_names_are_polish_from_monday(): void {
		$this->assertSame( 'Poniedziałek', pw_grafik_dzien_nazwa( 1 ) );
		$this->assertSame( 'Środa', pw_grafik_dzien_nazwa( 3 ) );
		$this->assertSame( 'Niedziela', pw_grafik_dzien_nazwa( 7 ) );
		$this->assertSame(
			array( 1, 2, 3, 4, 5, 6, 7 ),
			array_keys( pw_grafik_dni() )
		);
		$this->assertSame( 'Sobota', pw_grafik_dni()[6] );
	}

	public function test_day_name_is_empty_outside_one_to_seven(): void {
		$this->assertSame( '', pw_grafik_dzien_nazwa( 0 ) );
		$this->assertSame( '', pw_grafik_dzien_nazwa( 8 ) );
	}

	public function test_sanitize_returns_valid_rows_as_typed_values_by_day_then_start_time(): void {
		$this->grupy_exist( 4, 5 );

		$this->assertSame(
			array(
				array(
					'dzien' => 1,
					'od'    => '09:05',
					'do'    => '10:00',
					'grupa' => 4,
				),
				array(
					'dzien' => 3,
					'od'    => '17:00',
					'do'    => '18:30',
					'grupa' => 5,
				),
			),
			pw_grafik_sanitize(
				array(
					7 => array(
						'dzien' => '3',
						'od'    => '17:00',
						'do'    => '18:30',
						'grupa' => '5',
						'extra' => 'x',
					),
					2 => array(
						'grupa' => '4',
						'do'    => '10:00',
						'od'    => '9:05',
						'dzien' => 1,
					),
				)
			)
		);
	}

	public function test_sanitize_orders_rows_of_the_same_day_by_start_time(): void {
		$this->grupy_exist( 4, 5, 6 );

		$rows = pw_grafik_sanitize(
			array(
				array(
					'dzien' => 2,
					'od'    => '18:00',
					'do'    => '19:00',
					'grupa' => 4,
				),
				array(
					'dzien' => 2,
					'od'    => '8:30',
					'do'    => '9:30',
					'grupa' => 5,
				),
				array(
					'dzien' => 2,
					'od'    => '12:00',
					'do'    => '13:00',
					'grupa' => 6,
				),
			)
		);

		$this->assertSame( array( 5, 6, 4 ), array_column( $rows, 'grupa' ) );
	}

	public function test_sanitize_of_nothing_is_an_empty_list(): void {
		$this->grupy_exist( 4 );

		$this->assertSame( array(), pw_grafik_sanitize( array() ) );
		$this->assertSame( array(), pw_grafik_sanitize( array( 'x', null, array() ) ) );
	}

	public function test_sanitize_drops_rows_without_an_existing_grupa(): void {
		$this->grupy_exist( 4 );
		$row = array(
			'dzien' => 2,
			'od'    => '17:00',
			'do'    => '18:00',
		);

		$this->assertSame(
			array( 4 ),
			array_column(
				pw_grafik_sanitize(
					array(
						$row,
						$row + array( 'grupa' => '' ),
						$row + array( 'grupa' => 0 ),
						$row + array( 'grupa' => 'abc' ),
						$row + array( 'grupa' => 99 ),
						$row + array( 'grupa' => 4 ),
					)
				),
				'grupa'
			)
		);
	}

	public function test_sanitize_drops_rows_with_a_bad_day(): void {
		$this->grupy_exist( 4 );
		$row = array(
			'od'    => '17:00',
			'do'    => '18:00',
			'grupa' => 4,
		);

		$this->assertSame(
			array( 7 ),
			array_column(
				pw_grafik_sanitize(
					array(
						$row,
						$row + array( 'dzien' => 0 ),
						$row + array( 'dzien' => 8 ),
						$row + array( 'dzien' => 'pon' ),
						$row + array( 'dzien' => '7' ),
					)
				),
				'dzien'
			)
		);
	}

	public function test_sanitize_drops_rows_with_bad_or_reversed_hours(): void {
		$this->grupy_exist( 4 );
		$rows = array_map(
			static fn( array $godziny ): array => array(
				'dzien' => 1,
				'od'    => $godziny[0],
				'do'    => $godziny[1],
				'grupa' => 4,
			),
			array(
				array( '', '18:00' ),
				array( '17:00', '' ),
				array( '24:00', '24:30' ),
				array( '17:60', '18:00' ),
				array( '7:5', '8:00' ),
				array( '17.00', '18.00' ),
				array( 'abc', '18:00' ),
				array( '18:00', '18:00' ),
				array( '18:00', '17:00' ),
				array( '9:00', '10:00' ),
				array( '7:30', '23:59' ),
			)
		);

		$this->assertSame(
			array( '07:30', '09:00' ),
			array_column( pw_grafik_sanitize( $rows ), 'od' )
		);
	}

	public function test_hour_normalisation(): void {
		$this->assertSame( '07:05', pw_grafik_godzina( '7:05' ) );
		$this->assertSame( '17:00', pw_grafik_godzina( ' 17:00 ' ) );
		$this->assertSame( '00:00', pw_grafik_godzina( '0:00' ) );
		$this->assertSame( '', pw_grafik_godzina( '7' ) );
		$this->assertSame( '', pw_grafik_godzina( '107:00' ) );
	}

	/**
	 * Only these term IDs exist in the `grupa` taxonomy.
	 *
	 * @param int ...$ids Term IDs.
	 */
	private function grupy_exist( int ...$ids ): void {
		Functions\when( 'term_exists' )->alias(
			static fn( $term, $taxonomy = '' ) => 'grupa' === $taxonomy && in_array( $term, $ids, true )
				? array(
					'term_id'          => (string) $term,
					'term_taxonomy_id' => (string) $term,
				)
				: null
		);
	}
}
