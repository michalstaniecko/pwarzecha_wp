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

	use \PW\Tests\RegistersThemeMeta;

	public function set_up(): void {
		parent::set_up();
		$this->register_theme_meta();
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

	public function test_grupa_used_in_a_grafik_cannot_be_deleted(): void {
		$grupa = $this->grupa( 'Maluchy' );
		$this->with_row( $this->lokalizacja( 'Chorzów', 'draft' ), $this->grupa( 'Starsi' ) );
		$this->with_row( $this->lokalizacja( 'Bytom', 'draft' ), $grupa );

		try {
			wp_delete_term( $grupa, 'grupa' );
			$this->fail( 'A used Grupa was deleted.' );
		} catch ( \WPDieException $e ) {
			$this->assertStringContainsString( 'Maluchy', $e->getMessage() );
			$this->assertStringContainsString( 'Bytom', $e->getMessage() );
			$this->assertStringNotContainsString( 'Chorzów', $e->getMessage() );
		}
		$this->assertInstanceOf( \WP_Term::class, get_term( $grupa, 'grupa' ) );
		$this->assertSame( array( 'Maluchy' ), array_column( pw_grafik( $this->lokalizacja_id( 'Bytom' ) ), 'grupaNazwa' ) );
	}

	public function test_grupa_used_only_in_a_trashed_lokalizacja_cannot_be_deleted_either(): void {
		$grupa = $this->grupa( 'Maluchy' );
		$this->with_row( $this->lokalizacja( 'Bytom', 'trash' ), $grupa );

		$this->expectException( \WPDieException::class );
		wp_delete_term( $grupa, 'grupa' );
	}

	public function test_unused_grupa_can_be_deleted(): void {
		$grupa = $this->grupa( 'Maluchy' );
		$this->with_row( $this->lokalizacja( 'Bytom' ), $this->grupa( 'Starsi' ) );
		$tag = self::factory()->term->create( array( 'taxonomy' => 'post_tag' ) );
		$this->with_row( $this->lokalizacja( 'Chorzów' ), $grupa );
		update_post_meta( $this->lokalizacja_id( 'Chorzów' ), 'pw_grafik', array() );

		$this->assertTrue( wp_delete_term( $grupa, 'grupa' ) );
		$this->assertTrue( wp_delete_term( $tag, 'post_tag' ) );
	}

	public function test_grupy_list_offers_delete_only_for_unused_grupy(): void {
		$uzywana = $this->grupa( 'Maluchy' );
		$wolna   = $this->grupa( 'Starsi' );
		$this->with_row( $this->lokalizacja( 'Bytom', 'draft' ), $uzywana );

		$this->assertStringNotContainsString( 'delete-tag', $this->grupy_list_row( $uzywana ), 'used Grupa: no „Usuń”' );
		$this->assertStringContainsString( 'delete-tag', $this->grupy_list_row( $wolna ) );
	}

	public function test_metabox_saves_the_valid_rows_of_the_form(): void {
		$grupa = $this->grupa( 'Maluchy' );
		$id    = $this->lokalizacja();

		$this->save_grafik(
			$id,
			array(
				'3' => array(
					'dzien' => '4',
					'od'    => '17:00',
					'do'    => '18:00',
					'grupa' => (string) $grupa,
				),
				'7' => array(
					'dzien' => '2',
					'od'    => '9:30',
					'do'    => '10:15',
					'grupa' => (string) $grupa,
				),
				'8' => array(
					'dzien' => '2',
					'od'    => '10:00',
					'do'    => '11:00',
					'grupa' => '',
				),
			)
		);

		$this->assertSame(
			array(
				array(
					'dzien' => 2,
					'od'    => '09:30',
					'do'    => '10:15',
					'grupa' => $grupa,
				),
				array(
					'dzien' => 4,
					'od'    => '17:00',
					'do'    => '18:00',
					'grupa' => $grupa,
				),
			),
			get_post_meta( $id, 'pw_grafik', true )
		);
	}

	public function test_metabox_shows_saved_rows_by_day_then_start_time(): void {
		$maluchy = $this->grupa( 'Maluchy' );
		$starsi  = $this->grupa( 'Starsi' );
		$id      = $this->lokalizacja();

		$this->save_grafik(
			$id,
			array(
				array(
					'dzien' => '5',
					'od'    => '17:00',
					'do'    => '18:00',
					'grupa' => (string) $maluchy,
				),
				array(
					'dzien' => '1',
					'od'    => '18:00',
					'do'    => '19:00',
					'grupa' => (string) $starsi,
				),
				array(
					'dzien' => '1',
					'od'    => '16:30',
					'do'    => '17:30',
					'grupa' => (string) $maluchy,
				),
			)
		);

		$expected = array( array( '1', '16:30' ), array( '1', '18:00' ), array( '5', '17:00' ) );
		$this->assertSame(
			$expected,
			array_map(
				static fn( array $row ): array => array( (string) $row['dzien'], $row['od'] ),
				get_post_meta( $id, 'pw_grafik', true )
			)
		);

		$html = $this->render_grafik_metabox( $id );
		$doc  = \Dom\HTMLDocument::createFromString( '<!DOCTYPE html><body>' . $html, LIBXML_NOERROR );
		$rows = iterator_to_array( $doc->querySelectorAll( '[data-pw-grafik-rows] > [data-pw-grafik-row]' ) );
		$this->assertSame(
			$expected,
			array_map(
				static fn( $row ): array => array(
					(string) $row->querySelector( 'select[name$="[dzien]"] option[selected]' )?->getAttribute( 'value' ),
					(string) $row->querySelector( 'input[name$="[od]"]' )?->getAttribute( 'value' ),
				),
				$rows
			)
		);
		$this->assertStringNotContainsString( 'Kolejność nie ma znaczenia', $html );
	}

	public function test_metabox_without_rows_clears_the_grafik(): void {
		$id = $this->lokalizacja();
		$this->with_row( $id, $this->grupa( 'Maluchy' ) );

		$this->save_grafik( $id, null );

		$this->assertFalse( metadata_exists( 'post', $id, 'pw_grafik' ) );
		$this->assertSame( array(), pw_grafik( $id ) );
	}

	public function test_metabox_ignores_a_request_without_a_valid_nonce_or_rights(): void {
		$grupa = $this->grupa( 'Maluchy' );
		$id    = $this->lokalizacja();
		$this->with_row( $id, $grupa );

		$this->save_grafik( $id, null, 'zly' );
		$this->save_grafik( $id, null, null, 'subscriber' );

		$this->assertCount( 1, pw_grafik( $id ) );
	}

	public function test_metabox_shows_rows_a_row_template_and_grupy_to_choose(): void {
		$maluchy = $this->grupa( 'Maluchy', 1 );
		$starsi  = $this->grupa( 'Starsi <b>', 2 );
		$id      = $this->lokalizacja();
		update_post_meta(
			$id,
			'pw_grafik',
			array(
				array(
					'dzien' => 6,
					'od'    => '10:00',
					'do'    => '11:30',
					'grupa' => $starsi,
				),
			)
		);

		$html = $this->render_grafik_metabox( $id );

		$this->assertStringContainsString( 'name="pw_grafik_nonce"', $html );
		$this->assertStringNotContainsString( 'Starsi <b>', $html );
		$doc  = \Dom\HTMLDocument::createFromString( '<!DOCTYPE html><body>' . $html, LIBXML_NOERROR );
		$rows = $doc->querySelectorAll( '[data-pw-grafik-rows] > [data-pw-grafik-row]' );
		$this->assertCount( 1, $rows );
		$row = $rows->item( 0 );
		$this->assertSame( '6', $row->querySelector( 'select[name="pw_grafik[0][dzien]"] option[selected]' )?->getAttribute( 'value' ) );
		$this->assertSame( '10:00', $row->querySelector( 'input[type="time"][name="pw_grafik[0][od]"]' )?->getAttribute( 'value' ) );
		$this->assertSame( '11:30', $row->querySelector( 'input[type="time"][name="pw_grafik[0][do]"]' )?->getAttribute( 'value' ) );
		$this->assertSame( (string) $starsi, $row->querySelector( 'select[name="pw_grafik[0][grupa]"] option[selected]' )?->getAttribute( 'value' ) );
		$this->assertNotNull( $row->querySelector( 'button[data-pw-grafik-remove]' ) );
		$this->assertSame( 7, $row->querySelectorAll( 'select[name="pw_grafik[0][dzien]"] option' )->length );
		$this->assertSame(
			array( '', (string) $maluchy, (string) $starsi ),
			array_map(
				static fn( $option ) => $option->getAttribute( 'value' ),
				iterator_to_array( $row->querySelectorAll( 'select[name="pw_grafik[0][grupa]"] option' ) )
			)
		);
		foreach ( $row->querySelectorAll( 'select, input' ) as $control ) {
			$this->assertNotSame( '', (string) $control->getAttribute( 'aria-label' ), (string) $control->getAttribute( 'name' ) );
		}

		$template = $doc->querySelector( 'template[data-pw-grafik-template]' );
		$this->assertNotNull( $template );
		$this->assertStringContainsString( 'name="pw_grafik[__i__][grupa]"', $doc->saveHtml( $template ) );
		$this->assertNotNull( $doc->querySelector( 'button[data-pw-grafik-add]' ) );
	}

	public function test_metabox_points_to_the_grupy_screen_while_the_list_is_empty(): void {
		$html = $this->render_grafik_metabox( $this->lokalizacja() );

		$this->assertStringContainsString( 'edit-tags.php?taxonomy=grupa&#038;post_type=lokalizacja', $html );
		$this->assertStringNotContainsString( 'pw_grafik_nonce', $html, 'saving the Lokalizacja keeps the Grafik' );
	}

	public function test_grupy_screen_edits_the_order(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$term = $this->grupa( 'Maluchy', 4 );

		ob_start();
		do_action( 'grupa_add_form_fields', 'grupa' );
		$add = (string) ob_get_clean();
		ob_start();
		do_action( 'grupa_edit_form_fields', get_term( $term, 'grupa' ), 'grupa' );
		$edit = (string) ob_get_clean();

		$this->assertMatchesRegularExpression( '/<input type="number"[^>]*name="pw_kolejnosc"/', $add );
		$this->assertMatchesRegularExpression( '/name="pw_kolejnosc"[^>]*value="4"/', $edit );

		$_POST = array( 'pw_kolejnosc' => '7' );
		wp_update_term( $term, 'grupa', array( 'name' => 'Maluchy' ) );
		$this->assertSame( '7', get_term_meta( $term, 'pw_kolejnosc', true ) );

		$_POST = array( 'pw_kolejnosc' => '2' );
		$nowa  = wp_insert_term( 'Starsi', 'grupa' )['term_id'];
		$_POST = array();
		$this->assertSame( '2', get_term_meta( $nowa, 'pw_kolejnosc', true ) );
		$this->assertSame( array( 'Starsi', 'Maluchy' ), array_column( pw_grupy(), 'nazwa' ) );
	}

	/**
	 * HTML of the „Grafik” metabox as the edit screen shows it.
	 *
	 * @param int $id Lokalizacja ID.
	 */
	private function render_grafik_metabox( int $id ): string {
		set_current_screen( 'lokalizacja' );
		require_once ABSPATH . 'wp-admin/includes/template.php';
		do_action( 'add_meta_boxes_lokalizacja', get_post( $id ) );

		global $wp_meta_boxes;
		$box = $wp_meta_boxes['lokalizacja']['normal']['default']['pw_grafik'] ?? null;
		$this->assertNotNull( $box );
		$this->assertSame( 'Grafik', $box['title'] );

		ob_start();
		call_user_func( $box['callback'], get_post( $id ) );
		return (string) ob_get_clean();
	}

	/**
	 * Submits the „Grafik” metabox (`save_post` through wp_update_post()).
	 *
	 * @param int                       $id    Lokalizacja ID.
	 * @param array<string, mixed>|null $rows  Form rows, null = no rows in the form.
	 * @param string|null               $nonce Nonce, null = a valid one.
	 * @param string                    $role  Role of the user.
	 */
	private function save_grafik( int $id, ?array $rows, ?string $nonce = null, string $role = 'editor' ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
		$post = array( 'pw_grafik_nonce' => $nonce ?? wp_create_nonce( 'pw_grafik_save' ) );
		if ( null !== $rows ) {
			$post['pw_grafik'] = $rows;
		}
		$_POST = wp_slash( $post );
		wp_update_post( array( 'ID' => $id ) );
		$_POST = array();
	}

	/**
	 * Lokalizacja post.
	 *
	 * @param string $nazwa  Title.
	 * @param string $status Post status.
	 */
	private function lokalizacja( string $nazwa = 'Chorzów', string $status = 'publish' ): int {
		return self::factory()->post->create(
			array(
				'post_type'   => 'lokalizacja',
				'post_status' => $status,
				'post_title'  => $nazwa,
			)
		);
	}

	/**
	 * ID of the Lokalizacja with this title.
	 *
	 * @param string $nazwa Title.
	 */
	private function lokalizacja_id( string $nazwa ): int {
		$ids = get_posts(
			array(
				'post_type'   => 'lokalizacja',
				'post_status' => 'any',
				'title'       => $nazwa,
				'fields'      => 'ids',
			)
		);
		return (int) $ids[0];
	}

	/**
	 * Row of the Grupa on the „Lokalizacje → Grupy” list, as an editor sees it.
	 *
	 * @param int $grupa Grupa term ID.
	 */
	private function grupy_list_row( int $grupa ): string {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-terms-list-table.php';
		$_REQUEST['taxonomy'] = 'grupa';
		set_current_screen( 'edit-grupa' );
		$table = new \WP_Terms_List_Table( array( 'screen' => 'edit-grupa' ) );
		ob_start();
		$table->single_row( get_term( $grupa, 'grupa' ) );
		$html = (string) ob_get_clean();
		unset( $_REQUEST['taxonomy'] );
		return $html;
	}

	/**
	 * Gives the Lokalizacja one Grafik row of the Grupa.
	 *
	 * @param int $lokalizacja Lokalizacja ID.
	 * @param int $grupa       Grupa term ID.
	 */
	private function with_row( int $lokalizacja, int $grupa ): void {
		update_post_meta(
			$lokalizacja,
			'pw_grafik',
			array(
				array(
					'dzien' => 1,
					'od'    => '17:00',
					'do'    => '18:00',
					'grupa' => $grupa,
				),
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
