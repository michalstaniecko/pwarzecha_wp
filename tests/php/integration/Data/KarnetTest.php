<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * CPT Karnet: fields, metabox, repository pw_karnety() and a single polecany Karnet (#51).
 *
 * @package PW
 */

namespace PW\Tests\Integration\Data;

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

	public function test_rest_serves_karnety_to_editors_only(): void {
		$id = $this->karnet( 'Miesiąc', 1, array( 'pw_cena' => 160 ) );

		wp_set_current_user( 0 );
		$this->assertSame( 401, rest_do_request( new \WP_REST_Request( 'GET', '/wp/v2/karnet' ) )->get_status() );
		$this->assertSame( 401, rest_do_request( new \WP_REST_Request( 'GET', '/wp/v2/karnet/' . $id ) )->get_status() );

		$this->log_in( 'subscriber' );
		$this->assertSame( 403, rest_do_request( new \WP_REST_Request( 'GET', '/wp/v2/karnet' ) )->get_status() );

		$this->log_in( 'editor' );
		$response = rest_do_request( new \WP_REST_Request( 'GET', '/wp/v2/karnet/' . $id ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 160, $response->get_data()['meta']['pw_cena'] );
		$this->assertSame( 200, rest_do_request( new \WP_REST_Request( 'GET', '/wp/v2/karnet' ) )->get_status() );
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

	/**
	 * Saves the Karnet the way the edit screen does: metabox fields in $_POST, then wp_update_post().
	 *
	 * @param int                  $id     Karnet ID.
	 * @param array<string, mixed> $fields Form fields (without the nonce).
	 * @param bool                 $nonce  Whether to send a valid nonce.
	 */
	private function save_metabox( int $id, array $fields, bool $nonce = true ): void {
		$_POST = $fields;
		if ( $nonce ) {
			$_POST['pw_karnet_nonce'] = wp_create_nonce( 'pw_karnet_save' );
		}
		wp_update_post( array( 'ID' => $id ) );
		$_POST = array();
	}

	private function log_in( string $role ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
	}

	public function test_metabox_is_shown_on_the_karnet_screen(): void {
		global $wp_meta_boxes;
		$this->log_in( 'editor' );
		set_current_screen( 'karnet' );

		require_once ABSPATH . 'wp-admin/includes/meta-boxes.php';
		register_and_do_post_meta_boxes( get_post( $this->karnet( 'Miesiąc', 1 ) ) );

		$this->assertArrayHasKey( 'pw_karnet', $wp_meta_boxes['karnet']['normal']['high'] ?? array() );
		$this->assertFalse( $wp_meta_boxes['karnet']['normal']['core']['postcustom'] ?? false, 'raw Custom Fields box is removed' );
	}

	public function test_metabox_renders_the_stored_values(): void {
		$id = $this->karnet(
			'Miesiąc',
			1,
			array(
				'pw_cena'           => 150,
				'pw_okres_miesiace' => 5,
				'pw_okres_etykieta' => '/ semestr',
				'pw_obejmuje'       => array( '8 zajęć', 'Ubezpieczenie' ),
				'pw_polecany'       => true,
			)
		);

		ob_start();
		pw_karnet_metabox_render( get_post( $id ) );
		$html = (string) ob_get_clean();
		$doc  = \Dom\HTMLDocument::createFromString( '<!DOCTYPE html><body>' . $html, LIBXML_NOERROR );

		$this->assertSame( '150', $doc->querySelector( 'input[name="pw_cena"]' )?->getAttribute( 'value' ) );
		$this->assertSame( '5', $doc->querySelector( 'input[name="pw_okres_miesiace"]' )?->getAttribute( 'value' ) );
		$this->assertSame( '/ semestr', $doc->querySelector( 'input[name="pw_okres_etykieta"]' )?->getAttribute( 'value' ) );
		$this->assertSame( "8 zajęć\nUbezpieczenie", $doc->querySelector( 'textarea[name="pw_obejmuje"]' )?->textContent );
		$this->assertTrue( (bool) $doc->querySelector( 'input[name="pw_polecany"]' )?->hasAttribute( 'checked' ) );
		$this->assertNotNull( $doc->querySelector( 'input[name="pw_karnet_nonce"]' ) );
		foreach ( array( 'pw_cena', 'pw_okres_miesiace', 'pw_okres_etykieta', 'pw_obejmuje', 'pw_polecany' ) as $field ) {
			$this->assertNotNull( $doc->querySelector( 'label[for="' . $field . '"]' ), "Label for $field" );
		}
	}

	public function test_saving_the_metabox_stores_the_fields(): void {
		$this->log_in( 'editor' );
		$id = $this->karnet( 'Semestr', 1 );

		$this->save_metabox(
			$id,
			array(
				'pw_cena'           => '600',
				'pw_okres_miesiace' => '5',
				'pw_okres_etykieta' => '/ semestr',
				'pw_obejmuje'       => "16 zajęć\r\n\r\n Strój ",
				'pw_polecany'       => '1',
			)
		);

		$this->assertSame(
			array(
				'cena'          => 600,
				'okresMiesiace' => 5,
				'okresEtykieta' => '/ semestr',
				'obejmuje'      => array( '16 zajęć', 'Strój' ),
				'polecany'      => true,
			),
			array_diff_key( pw_karnety()[0], array_flip( array( 'id', 'nazwa' ) ) )
		);
	}

	public function test_unchecked_polecany_clears_the_flag(): void {
		$this->log_in( 'editor' );
		$id = $this->karnet( 'Semestr', 1, array( 'pw_polecany' => true ) );

		$this->save_metabox( $id, array( 'pw_cena' => '600' ) );

		$this->assertFalse( pw_karnety()[0]['polecany'] );
	}

	public function test_metabox_save_needs_a_valid_nonce_and_the_capability(): void {
		$id = $this->karnet( 'Semestr', 1, array( 'pw_cena' => 600 ) );

		$this->log_in( 'editor' );
		$this->save_metabox( $id, array( 'pw_cena' => '1' ), false );
		$this->assertSame( 600, pw_karnety()[0]['cena'] );

		$this->log_in( 'subscriber' );
		$this->save_metabox( $id, array( 'pw_cena' => '1' ) );
		$this->assertSame( 600, pw_karnety()[0]['cena'] );
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
