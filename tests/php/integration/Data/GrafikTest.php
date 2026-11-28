<?php
/**
 * Grafik of a Lokalizacja and the Grupy taxonomy (#50).
 *
 * @package PW
 */

namespace PW\Tests\Integration\Data;

use WP_UnitTestCase;

/**
 * Grupy and Grafik rows as the panel, the seed and the blocks see them.
 */
class GrafikTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		// WP_UnitTestCase::tear_down() unregisters every meta key; `init` does not run again.
		\PW\Data\Grafik\register_meta();
	}

	public function test_grupa_taxonomy_is_managed_in_the_panel_only(): void {
		$taxonomy = get_taxonomy( 'grupa' );

		$this->assertNotFalse( $taxonomy );
		$this->assertFalse( $taxonomy->public );
		$this->assertFalse( $taxonomy->publicly_queryable );
		$this->assertFalse( $taxonomy->hierarchical );
		$this->assertTrue( $taxonomy->show_ui );
		$this->assertTrue( $taxonomy->show_in_rest );
		$this->assertFalse( $taxonomy->rewrite );
		$this->assertFalse( $taxonomy->query_var );
		$this->assertFalse( $taxonomy->meta_box_cb );
		$this->assertSame( array( 'lokalizacja' ), $taxonomy->object_type );
		$this->assertSame( 'Grupy', $taxonomy->labels->name );
		$this->assertFalse( is_taxonomy_viewable( 'grupa' ) );

		self::factory()->term->create(
			array(
				'taxonomy' => 'grupa',
				'slug'     => 'dzieci',
			)
		);
		$this->go_to( home_url( '/?grupa=dzieci' ) );
		$this->assertFalse( is_tax( 'grupa' ) );
		$this->go_to( home_url( '/?taxonomy=grupa&term=dzieci' ) );
		$this->assertFalse( is_tax( 'grupa' ) );
	}

	public function test_grupy_come_in_pw_kolejnosc_order_then_by_name(): void {
		$starsi  = $this->grupa( 'Starsi', 2, '10–14 lat' );
		$maluchy = $this->grupa( 'Maluchy', 1, '4–6 lat' );
		$bez     = $this->grupa( 'Dorośli' );
		$adepci  = $this->grupa( 'Adepci', 2 );

		$this->assertSame(
			array(
				array(
					'id'        => $bez,
					'nazwa'     => 'Dorośli',
					'opis'      => '',
					'kolejnosc' => 0,
				),
				array(
					'id'        => $maluchy,
					'nazwa'     => 'Maluchy',
					'opis'      => '4–6 lat',
					'kolejnosc' => 1,
				),
				array(
					'id'        => $adepci,
					'nazwa'     => 'Adepci',
					'opis'      => '',
					'kolejnosc' => 2,
				),
				array(
					'id'        => $starsi,
					'nazwa'     => 'Starsi',
					'opis'      => '10–14 lat',
					'kolejnosc' => 2,
				),
			),
			pw_grupy()
		);
	}

	public function test_grupy_pass_through_their_filter(): void {
		$this->grupa( 'Maluchy', 1 );
		add_filter( 'pw/data/grupy', static fn( array $grupy ): array => array_slice( $grupy, 0, 0 ) );

		$this->assertSame( array(), pw_grupy() );
	}

	/**
	 * Grupa term with its order.
	 *
	 * @param string   $nazwa     Name.
	 * @param int|null $kolejnosc Order, null = no meta.
	 * @param string   $opis      Description (age range).
	 */
	private function grupa( string $nazwa, ?int $kolejnosc = null, string $opis = '' ): int {
		$id = self::factory()->term->create(
			array(
				'taxonomy'    => 'grupa',
				'name'        => $nazwa,
				'description' => $opis,
			)
		);
		if ( null !== $kolejnosc ) {
			update_term_meta( $id, 'pw_kolejnosc', $kolejnosc );
		}
		return $id;
	}
}
