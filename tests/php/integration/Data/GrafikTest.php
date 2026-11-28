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

	public function test_grafik_lists_rows_by_day_and_hour_with_grupa_and_day_names(): void {
		$maluchy = $this->grupa( 'Maluchy', 1 );
		$starsi  = $this->grupa( 'Starsi', 2 );
		$id      = $this->lokalizacja();
		update_post_meta(
			$id,
			'pw_grafik',
			array(
				array(
					'dzien' => 3,
					'od'    => '18:00',
					'do'    => '19:00',
					'grupa' => $starsi,
				),
				array(
					'dzien' => 1,
					'od'    => '17:30',
					'do'    => '18:15',
					'grupa' => $starsi,
				),
				array(
					'dzien' => 1,
					'od'    => '9:00',
					'do'    => '10:00',
					'grupa' => $maluchy,
				),
			)
		);

		$this->assertSame(
			array(
				array(
					'dzien'      => 1,
					'od'         => '09:00',
					'do'         => '10:00',
					'grupa'      => $maluchy,
					'dzienNazwa' => 'Poniedziałek',
					'grupaNazwa' => 'Maluchy',
				),
				array(
					'dzien'      => 1,
					'od'         => '17:30',
					'do'         => '18:15',
					'grupa'      => $starsi,
					'dzienNazwa' => 'Poniedziałek',
					'grupaNazwa' => 'Starsi',
				),
				array(
					'dzien'      => 3,
					'od'         => '18:00',
					'do'         => '19:00',
					'grupa'      => $starsi,
					'dzienNazwa' => 'Środa',
					'grupaNazwa' => 'Starsi',
				),
			),
			pw_grafik( $id )
		);
	}

	public function test_grafik_meta_stores_only_valid_rows(): void {
		$grupa = $this->grupa( 'Maluchy' );
		$id    = $this->lokalizacja();
		update_post_meta(
			$id,
			'pw_grafik',
			array(
				array(
					'dzien' => '2',
					'od'    => '7:00',
					'do'    => '8:00',
					'grupa' => (string) $grupa,
				),
				array(
					'dzien' => 2,
					'od'    => '9:00',
					'do'    => '8:00',
					'grupa' => $grupa,
				),
				array(
					'dzien' => 2,
					'od'    => '9:00',
					'do'    => '10:00',
					'grupa' => $grupa + 1000,
				),
			)
		);

		$this->assertSame(
			array(
				array(
					'dzien' => 2,
					'od'    => '07:00',
					'do'    => '08:00',
					'grupa' => $grupa,
				),
			),
			get_post_meta( $id, 'pw_grafik', true )
		);
	}

	public function test_grafik_is_empty_without_rows_or_for_other_posts(): void {
		$this->assertSame( array(), pw_grafik( $this->lokalizacja() ) );
		$this->assertSame( array(), pw_grafik( self::factory()->post->create() ) );
		$this->assertSame( array(), pw_grafik( 0 ) );
	}

	public function test_grafik_passes_through_its_filter(): void {
		$id = $this->lokalizacja();
		add_filter(
			'pw/data/grafik',
			static fn( array $wiersze, int $lokalizacja_id ): array => array( array( 'lokalizacja' => $lokalizacja_id ) ),
			10,
			2
		);

		$this->assertSame( array( array( 'lokalizacja' => $id ) ), pw_grafik( $id ) );
	}

	public function test_grafik_is_editable_through_rest(): void {
		$grupa = $this->grupa( 'Maluchy' );
		$id    = $this->lokalizacja();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$request = new \WP_REST_Request( 'POST', '/wp/v2/lokalizacja/' . $id );
		$request->set_body_params(
			array(
				'meta' => array(
					'pw_grafik' => array(
						array(
							'dzien' => 5,
							'od'    => '16:00',
							'do'    => '17:00',
							'grupa' => $grupa,
						),
					),
				),
			)
		);
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			array(
				array(
					'dzien' => 5,
					'od'    => '16:00',
					'do'    => '17:00',
					'grupa' => $grupa,
				),
			),
			$response->get_data()['meta']['pw_grafik']
		);

		$request->set_body_params(
			array(
				'meta' => array(
					'pw_grafik' => array(
						array(
							'dzien' => 9,
							'od'    => '16:00',
							'do'    => '17:00',
							'grupa' => $grupa,
						),
					),
				),
			)
		);
		$this->assertSame( 400, rest_do_request( $request )->get_status() );
	}

	/**
	 * Published Lokalizacja.
	 */
	private function lokalizacja(): int {
		return self::factory()->post->create(
			array(
				'post_type'   => 'lokalizacja',
				'post_status' => 'publish',
			)
		);
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
