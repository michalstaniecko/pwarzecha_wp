<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * CPT Karnet: fields, metabox, repository pw_karnety() and a single polecany Karnet (#51).
 *
 * @package PW
 */

namespace PW\Tests\Integration;

use WP_UnitTestCase;

/**
 * Karnet data model on a real WordPress.
 */
class KarnetTest extends WP_UnitTestCase {

	/**
	 * WP_UnitTestCase::tear_down() unregisters every meta key, so register them again.
	 */
	public function set_up(): void {
		parent::set_up();
		pw_karnet_register();
	}

	/**
	 * Karnet post with meta.
	 *
	 * @param string               $nazwa      Title.
	 * @param int                  $menu_order Order on the Cennik.
	 * @param array<string, mixed> $meta       Meta values by key.
	 * @param string               $status     Post status.
	 */
	private function karnet( string $nazwa, int $menu_order, array $meta = array(), string $status = 'publish' ): int {
		return self::factory()->post->create(
			array(
				'post_type'   => 'karnet',
				'post_title'  => $nazwa,
				'post_status' => $status,
				'menu_order'  => $menu_order,
				'meta_input'  => $meta,
			)
		);
	}

	public function test_repository_returns_published_karnety_in_menu_order(): void {
		$rok     = $this->karnet(
			'Rok',
			3,
			array(
				'pw_cena'           => 1300,
				'pw_okres_miesiace' => 10,
			)
		);
		$miesiac = $this->karnet(
			'Miesiąc',
			1,
			array(
				'pw_cena'           => 150,
				'pw_okres_etykieta' => '/ mies.',
				'pw_obejmuje'       => array( '8 zajęć', 'Ubezpieczenie' ),
				'pw_polecany'       => true,
			)
		);
		$semestr = $this->karnet(
			'Semestr',
			2,
			array(
				'pw_cena'           => 600,
				'pw_okres_miesiace' => 5,
			)
		);
		$this->karnet( 'Szkic', 0, array( 'pw_cena' => 1 ), 'draft' );

		$karnety = pw_karnety();

		$this->assertSame( array( 'Miesiąc', 'Semestr', 'Rok' ), array_column( $karnety, 'nazwa' ) );
		$this->assertSame( array( $miesiac, $semestr, $rok ), array_column( $karnety, 'id' ) );
		$this->assertSame(
			array(
				'id'            => $miesiac,
				'nazwa'         => 'Miesiąc',
				'cena'          => 150,
				'okresMiesiace' => 1,
				'okresEtykieta' => '/ mies.',
				'obejmuje'      => array( '8 zajęć', 'Ubezpieczenie' ),
				'polecany'      => true,
			),
			$karnety[0]
		);
		$this->assertSame( 5, $karnety[1]['okresMiesiace'] );
		$this->assertSame( array(), $karnety[1]['obejmuje'] );
		$this->assertFalse( $karnety[1]['polecany'] );
	}

	public function test_monthly_minimum_reads_the_repository(): void {
		$this->assertNull( pw_karnet_min_cena_miesieczna() );

		$this->karnet( 'Miesiąc', 1, array( 'pw_cena' => 150 ) );
		$this->karnet(
			'Semestr',
			2,
			array(
				'pw_cena'           => 599,
				'pw_okres_miesiace' => 5,
			)
		);

		$this->assertSame( 119, pw_karnet_min_cena_miesieczna() );
	}

	public function test_filter_replaces_the_karnety_and_feeds_the_monthly_minimum(): void {
		$this->karnet( 'Z bazy', 1, array( 'pw_cena' => 500 ) );
		$zamiennik = array(
			array(
				'id'            => 0,
				'nazwa'         => 'Z filtra',
				'cena'          => 900,
				'okresMiesiace' => 10,
				'okresEtykieta' => '/ rok',
				'obejmuje'      => array(),
				'polecany'      => false,
			),
		);
		add_filter( 'pw/data/karnety', static fn() => $zamiennik );

		$this->assertSame( $zamiennik, pw_karnety() );
		$this->assertSame( 90, pw_karnet_min_cena_miesieczna() );
	}

	public function test_fields_are_exposed_in_rest_with_types(): void {
		$meta = get_registered_meta_keys( 'post', 'karnet' );

		$expected = array(
			'pw_cena'           => 'integer',
			'pw_okres_miesiace' => 'integer',
			'pw_okres_etykieta' => 'string',
			'pw_obejmuje'       => 'array',
			'pw_polecany'       => 'boolean',
		);
		foreach ( $expected as $key => $type ) {
			$this->assertArrayHasKey( $key, $meta );
			$this->assertSame( $type, $meta[ $key ]['type'], $key );
			$this->assertNotEmpty( $meta[ $key ]['show_in_rest'], $key );
		}
		$this->assertSame( 1, $meta['pw_okres_miesiace']['default'] );
	}

	public function test_stored_values_are_clamped_to_valid_ranges(): void {
		$id = $this->karnet(
			'Zły',
			1,
			array(
				'pw_cena'           => -5,
				'pw_okres_miesiace' => 0,
				'pw_obejmuje'       => array( ' 8 zajęć ', '', '<b>Strój</b>' ),
			)
		);

		$this->assertSame( 0, (int) get_post_meta( $id, 'pw_cena', true ) );
		$this->assertSame( 1, (int) get_post_meta( $id, 'pw_okres_miesiace', true ) );
		$this->assertSame( array( '8 zajęć', 'Strój' ), get_post_meta( $id, 'pw_obejmuje', true ) );
	}

	public function test_saving_a_second_polecany_karnet_clears_the_first(): void {
		$pierwszy = $this->karnet( 'Miesiąc', 1, array( 'pw_polecany' => true ) );
		$drugi    = $this->karnet( 'Semestr', 2, array( 'pw_polecany' => true ) );

		$this->assertSame( array( $drugi ), $this->polecane() );

		update_post_meta( $pierwszy, 'pw_polecany', true );

		$this->assertSame( array( $pierwszy ), $this->polecane() );
	}

	public function test_clearing_the_flag_leaves_no_polecany_karnet(): void {
		$id = $this->karnet( 'Miesiąc', 1, array( 'pw_polecany' => true ) );
		$this->karnet( 'Semestr', 2 );

		update_post_meta( $id, 'pw_polecany', false );

		$this->assertSame( array(), $this->polecane() );
	}

	/**
	 * IDs of polecany Karnety.
	 *
	 * @return list<int>
	 */
	private function polecane(): array {
		return array_values( array_column( array_filter( pw_karnety(), static fn( array $k ): bool => $k['polecany'] ), 'id' ) );
	}

	public function test_karnet_is_a_non_public_post_type_managed_in_the_panel(): void {
		$type = get_post_type_object( 'karnet' );

		$this->assertNotNull( $type );
		$this->assertFalse( $type->public );
		$this->assertFalse( $type->publicly_queryable );
		$this->assertTrue( $type->show_ui );
		$this->assertTrue( $type->show_in_rest );
		$this->assertTrue( post_type_supports( 'karnet', 'title' ) );
		$this->assertTrue( post_type_supports( 'karnet', 'page-attributes' ) );
		$this->assertFalse( post_type_supports( 'karnet', 'editor' ) );
	}
}
