<?php
/**
 * CPT Lokalizacja: registration, metabox save, repository `pw_lokalizacje()` / `pw_lokalizacja()` (#49).
 *
 * @package PW
 */

namespace PW\Tests\Integration\Data;

use WP_UnitTestCase;

/**
 * Lokalizacja as the panel and the blocks see it.
 */
class LokalizacjaTest extends WP_UnitTestCase {

	use \PW\Tests\RegistersThemeMeta;

	/**
	 * Complete, valid metabox form.
	 */
	private const FORM = array(
		'pw_ulica'             => ' ul. Wolności 1 ',
		'pw_kod'               => '41-500',
		'pw_miasto'            => 'Chorzów',
		'pw_kotwica'           => '',
		'pw_geo_lat'           => '50,2975',
		'pw_geo_lng'           => '18.9546',
		'pw_mapa_url'          => 'https://maps.app.goo.gl/abc',
		'pw_mapa_embed'        => 'https://www.google.com/maps/embed?pb=!1m18',
		'pw_dojazd'            => "Tramwaj 6.\nParking przy wejściu.",
		'pw_podglad_id'        => '12',
		'pw_film_youtube_id'   => 'dQw4w9WgXcQ',
		'pw_film_miniatura_id' => '13',
		'pw_strona_oferty'     => '',
	);

	public function set_up(): void {
		parent::set_up();
		$this->register_theme_meta();
	}

	public function test_post_type_has_no_front_urls_nor_archive(): void {
		$type = get_post_type_object( 'lokalizacja' );

		$this->assertNotNull( $type );
		$this->assertFalse( $type->public );
		$this->assertTrue( $type->show_ui );
		$this->assertFalse( is_post_type_viewable( 'lokalizacja' ) );
		$this->assertFalse( get_post_type_archive_link( 'lokalizacja' ) );
		$this->assertSame( 'Lokalizacje', $type->labels->name );
		$this->assertSame( 'dashicons-location', $type->menu_icon );
		$this->assertTrue( post_type_supports( 'lokalizacja', 'title' ) );
		$this->assertTrue( post_type_supports( 'lokalizacja', 'page-attributes' ) );
		$this->assertFalse( post_type_supports( 'lokalizacja', 'editor' ) );

		$id = self::factory()->post->create(
			array(
				'post_type'  => 'lokalizacja',
				'post_title' => 'Chorzów',
			)
		);
		$this->go_to( get_permalink( $id ) );
		$this->assertFalse( is_singular( 'lokalizacja' ) );
		$this->assertTrue( is_404() );
		$this->go_to( home_url( '/?p=' . $id ) );
		$this->assertTrue( is_404() );
		$this->go_to( home_url( '/?lokalizacja=chorzow' ) );
		$this->assertFalse( is_singular( 'lokalizacja' ) );
	}

	public function test_rest_serves_lokalizacje_to_editors_only(): void {
		$id = self::factory()->post->create(
			array(
				'post_type'  => 'lokalizacja',
				'post_title' => 'Chorzów',
			)
		);
		update_post_meta( $id, 'pw_miasto', 'Chorzów' );

		wp_set_current_user( 0 );
		$this->assertSame( 401, rest_do_request( new \WP_REST_Request( 'GET', '/wp/v2/lokalizacja' ) )->get_status() );
		$this->assertSame( 401, rest_do_request( new \WP_REST_Request( 'GET', '/wp/v2/lokalizacja/' . $id ) )->get_status() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertSame( 403, rest_do_request( new \WP_REST_Request( 'GET', '/wp/v2/lokalizacja' ) )->get_status() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$response = rest_do_request( new \WP_REST_Request( 'GET', '/wp/v2/lokalizacja/' . $id ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'Chorzów', $response->get_data()['meta']['pw_miasto'] );
		$this->assertSame( 200, rest_do_request( new \WP_REST_Request( 'GET', '/wp/v2/lokalizacja' ) )->get_status() );
	}

	public function test_metabox_shows_every_field_with_a_nonce(): void {
		$id = $this->lokalizacja( array(), array( 'pw_miasto' => 'Chorzów' ) );
		set_current_screen( 'lokalizacja' );
		require_once ABSPATH . 'wp-admin/includes/template.php';
		do_action( 'add_meta_boxes_lokalizacja', get_post( $id ) );

		global $wp_meta_boxes;
		$box = $wp_meta_boxes['lokalizacja']['normal']['high']['pw_lokalizacja'] ?? null;
		$this->assertNotNull( $box );
		$this->assertFalse( $wp_meta_boxes['lokalizacja']['normal']['core']['postcustom'] ?? false, 'raw Custom Fields box is removed' );

		ob_start();
		call_user_func( $box['callback'], get_post( $id ) );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'name="pw_lokalizacja_nonce"', $html );
		foreach ( array_keys( self::FORM ) as $key ) {
			$this->assertStringContainsString( 'name="pw_lokalizacja[' . $key . ']"', $html, $key );
		}
		$this->assertStringContainsString( 'value="Chorzów"', $html );
		$this->assertMatchesRegularExpression( '/zrzut[^<]*Google Maps/u', $html, 'Podgląd warns against Google Maps screenshots' );
	}

	public function test_metabox_of_a_new_lokalizacja_shows_empty_coordinates(): void {
		$id = $this->lokalizacja();
		require_once ABSPATH . 'wp-admin/includes/template.php';

		ob_start();
		\PW\Data\Lokalizacja\render_metabox( get_post( $id ) );
		$html = (string) ob_get_clean();

		foreach ( array( 'pw_geo_lat', 'pw_geo_lng' ) as $key ) {
			$this->assertMatchesRegularExpression( '/<input[^>]*id="' . $key . '"[^>]*value=""/', $html, $key );
		}
	}

	public function test_metabox_saves_sanitised_fields(): void {
		$strona = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$id     = $this->lokalizacja();

		$this->save_metabox(
			$id,
			array_merge(
				self::FORM,
				array( 'pw_strona_oferty' => (string) $strona )
			)
		);

		$this->assertSame( 'ul. Wolności 1', get_post_meta( $id, 'pw_ulica', true ) );
		$this->assertSame( '41-500', get_post_meta( $id, 'pw_kod', true ) );
		$this->assertSame( 'Chorzów', get_post_meta( $id, 'pw_miasto', true ) );
		$this->assertSame( 'chorzow', get_post_meta( $id, 'pw_kotwica', true ), 'defaults to the city' );
		$this->assertEquals( 50.2975, get_post_meta( $id, 'pw_geo_lat', true ) );
		$this->assertEquals( 18.9546, get_post_meta( $id, 'pw_geo_lng', true ) );
		$this->assertSame( 'https://maps.app.goo.gl/abc', get_post_meta( $id, 'pw_mapa_url', true ) );
		$this->assertSame( 'https://www.google.com/maps/embed?pb=!1m18', get_post_meta( $id, 'pw_mapa_embed', true ) );
		$this->assertSame( "Tramwaj 6.\nParking przy wejściu.", get_post_meta( $id, 'pw_dojazd', true ) );
		$this->assertSame( '12', get_post_meta( $id, 'pw_podglad_id', true ) );
		$this->assertSame( 'dQw4w9WgXcQ', get_post_meta( $id, 'pw_film_youtube_id', true ) );
		$this->assertSame( '13', get_post_meta( $id, 'pw_film_miniatura_id', true ) );
		$this->assertSame( (string) $strona, get_post_meta( $id, 'pw_strona_oferty', true ) );
	}

	public function test_metabox_rejects_invalid_values(): void {
		$id = $this->lokalizacja( array(), array( 'pw_mapa_embed' => 'https://www.google.com/maps/embed?pb=old' ) );

		$this->save_metabox(
			$id,
			array(
				'pw_ulica'           => '<b>ul. Wolności 1</b><script>alert(1)</script>',
				'pw_kod'             => '41500',
				'pw_miasto'          => 'Chorzów',
				'pw_geo_lat'         => '123',
				'pw_mapa_url'        => 'javascript:alert(1)',
				'pw_mapa_embed'      => '<iframe src="https://evil.example/maps/embed?pb=1"></iframe>',
				'pw_dojazd'          => "Tramwaj <script>x</script>\n6",
				'pw_film_youtube_id' => 'https://youtu.be/dQw4w9WgXcQ',
				'pw_podglad_id'      => '-5',
			)
		);

		$this->assertSame( 'ul. Wolności 1', get_post_meta( $id, 'pw_ulica', true ) );
		$this->assertSame( "Tramwaj \n6", get_post_meta( $id, 'pw_dojazd', true ) );
		foreach ( array( 'pw_kod', 'pw_geo_lat', 'pw_geo_lng', 'pw_mapa_url', 'pw_mapa_embed', 'pw_film_youtube_id', 'pw_podglad_id' ) as $key ) {
			$this->assertFalse( metadata_exists( 'post', $id, $key ), $key );
		}
	}

	public function test_metabox_keeps_anchors_unique(): void {
		$this->lokalizacja( array(), array( 'pw_miasto' => 'Chorzów' ) );
		$id = $this->lokalizacja();

		$this->save_metabox( $id, array( 'pw_miasto' => 'Chorzów' ) );
		$this->assertSame( 'chorzow-2', get_post_meta( $id, 'pw_kotwica', true ) );

		$this->save_metabox(
			$id,
			array(
				'pw_miasto'  => 'Chorzów',
				'pw_kotwica' => 'Chorzów Batory',
			)
		);
		$this->assertSame( 'chorzow-batory', get_post_meta( $id, 'pw_kotwica', true ) );
	}

	public function test_anchor_written_in_code_stays_unique(): void {
		$this->lokalizacja( array(), array( 'pw_miasto' => 'Chorzów' ) );
		$id = $this->lokalizacja( array(), array( 'pw_miasto' => 'Katowice' ) );

		update_post_meta( $id, 'pw_kotwica', 'chorzow' );

		$this->assertSame( 'chorzow-2', get_post_meta( $id, 'pw_kotwica', true ) );
		$this->assertSame( 'chorzow-2', pw_lokalizacja( $id )['kotwica'] );
	}

	public function test_city_written_in_code_without_an_anchor_gets_a_unique_one(): void {
		$this->lokalizacja( array(), array( 'pw_miasto' => 'Chorzów' ) );
		$id = $this->lokalizacja();

		update_post_meta( $id, 'pw_miasto', 'Chorzów' );

		$this->assertSame( 'chorzow-2', pw_lokalizacja( $id )['kotwica'] );
	}

	public function test_anchor_written_through_rest_stays_unique(): void {
		$this->lokalizacja( array(), array( 'pw_kotwica' => 'centrum' ) );
		$id = $this->lokalizacja( array(), array( 'pw_miasto' => 'Katowice' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$request = new \WP_REST_Request( 'POST', '/wp/v2/lokalizacja/' . $id );
		$request->set_body_params( array( 'meta' => array( 'pw_kotwica' => 'Centrum' ) ) );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'centrum-2', get_post_meta( $id, 'pw_kotwica', true ) );
	}

	public function test_metabox_saves_nothing_without_a_valid_nonce_or_rights(): void {
		$id = $this->lokalizacja();

		$this->save_metabox( $id, array( 'pw_miasto' => 'Chorzów' ), 'zły nonce' );
		$this->assertFalse( metadata_exists( 'post', $id, 'pw_miasto' ), 'bad nonce' );

		$this->save_metabox( $id, array( 'pw_miasto' => 'Chorzów' ), null, 'contributor' );
		$this->assertFalse( metadata_exists( 'post', $id, 'pw_miasto' ), 'no edit_post' );
	}

	public function test_repository_returns_the_normalised_lokalizacja(): void {
		$this->set_permalink_structure( '/%postname%/' );
		$strona = self::factory()->post->create(
			array(
				'post_type' => 'page',
				'post_name' => 'zajecia-chorzow',
			)
		);
		$embed  = 'https://www.google.com/maps/embed?pb=!1m18';
		$id     = $this->lokalizacja(
			array(
				'post_title' => 'Sala przy Wieży',
			),
			array(
				'pw_ulica'             => 'ul. Wolności 1',
				'pw_kod'               => '41-500',
				'pw_miasto'            => 'Chorzów',
				'pw_kotwica'           => 'chorzow-centrum',
				'pw_geo_lat'           => '50.2975',
				'pw_geo_lng'           => '18.9546',
				'pw_mapa_url'          => 'https://maps.app.goo.gl/abc',
				'pw_mapa_embed'        => $embed,
				'pw_dojazd'            => "Tramwaj 6.\nParking przy wejściu.",
				'pw_podglad_id'        => '12',
				'pw_film_youtube_id'   => 'dQw4w9WgXcQ',
				'pw_film_miniatura_id' => '13',
				'pw_strona_oferty'     => (string) $strona,
			)
		);

		$this->assertSame(
			array(
				'id'              => $id,
				'nazwa'           => 'Sala przy Wieży',
				'ulica'           => 'ul. Wolności 1',
				'kod'             => '41-500',
				'miasto'          => 'Chorzów',
				'kotwica'         => 'chorzow-centrum',
				'geo'             => array(
					'lat' => 50.2975,
					'lng' => 18.9546,
				),
				'mapaUrl'         => 'https://maps.app.goo.gl/abc',
				'mapaEmbed'       => $embed,
				'dojazd'          => "Tramwaj 6.\nParking przy wejściu.",
				'podgladId'       => 12,
				'film'            => array(
					'youtubeId'   => 'dQw4w9WgXcQ',
					'miniaturaId' => 13,
				),
				'stronaOfertyUrl' => home_url( '/zajecia-chorzow/' ),
			),
			pw_lokalizacja( $id )
		);
	}

	public function test_repository_fills_gaps_of_a_bare_lokalizacja(): void {
		$id = $this->lokalizacja( array( 'post_title' => 'Katowice' ), array( 'pw_miasto' => 'Katowice Śródmieście' ) );

		$l = pw_lokalizacja( $id );

		$this->assertSame( 'katowice-srodmiescie', $l['kotwica'] );
		$this->assertNull( $l['geo'] );
		$this->assertNull( $l['film'] );
		$this->assertSame( 0, $l['podgladId'] );
		$this->assertSame( '', $l['stronaOfertyUrl'] );
		$this->assertSame( '', $l['ulica'] );
	}

	public function test_stored_point_0_0_is_no_geo(): void {
		// Left by the old metabox, which rendered the meta default 0 into the empty fields.
		$id = $this->lokalizacja(
			array(),
			array(
				'pw_geo_lat' => '0',
				'pw_geo_lng' => '0',
			)
		);

		$this->assertNull( pw_lokalizacja( $id )['geo'] );
	}

	public function test_strona_oferty_url_follows_the_page_slug_and_status(): void {
		$this->set_permalink_structure( '/%postname%/' );
		$strona = self::factory()->post->create(
			array(
				'post_type' => 'page',
				'post_name' => 'stary-slug',
			)
		);
		$id     = $this->lokalizacja( array(), array( 'pw_strona_oferty' => (string) $strona ) );

		wp_update_post(
			array(
				'ID'        => $strona,
				'post_name' => 'nowy-slug',
			)
		);
		$this->assertSame( home_url( '/nowy-slug/' ), pw_lokalizacja( $id )['stronaOfertyUrl'] );

		wp_update_post(
			array(
				'ID'          => $strona,
				'post_status' => 'draft',
			)
		);
		$this->assertSame( '', pw_lokalizacja( $id )['stronaOfertyUrl'] );
	}

	public function test_lokalizacje_are_published_ones_in_menu_order(): void {
		$druga    = $this->lokalizacja( array( 'menu_order' => 2 ) );
		$pierwsza = $this->lokalizacja( array( 'menu_order' => 1 ) );
		$this->lokalizacja(
			array(
				'menu_order'  => 0,
				'post_status' => 'draft',
			)
		);

		$this->assertSame( array( $pierwsza, $druga ), array_column( pw_lokalizacje(), 'id' ) );
		$this->assertSame( array( $druga ), array_column( pw_lokalizacje( array( 'ids' => array( $druga ) ) ), 'id' ) );
		$this->assertNull( pw_lokalizacja( self::factory()->post->create() ), 'a post is not a Lokalizacja' );
	}

	public function test_draft_lokalizacja_is_not_found(): void {
		$id = $this->lokalizacja( array( 'post_status' => 'draft' ) );

		$this->assertNull( pw_lokalizacja( $id ) );
		$this->assertSame( array(), pw_lokalizacje( array( 'ids' => array( $id ) ) ) );
	}

	public function test_filter_replaces_the_lokalizacje(): void {
		$id    = $this->lokalizacja();
		$fake  = array_merge( pw_lokalizacja( $id ), array( 'nazwa' => 'Z filtra' ) );
		$calls = array();
		add_filter(
			'pw/data/lokalizacje',
			static function ( array $lokalizacje, array $args ) use ( $fake, &$calls ): array {
				$calls[] = $args;
				return array( $fake );
			},
			10,
			2
		);

		$this->assertSame( array( $fake ), pw_lokalizacje() );
		$this->assertSame( 'Z filtra', pw_lokalizacja( $id )['nazwa'] );
		$this->assertSame( array( $id ), $calls[1]['ids'] );
	}

	/**
	 * Submits the metabox form the way post.php does (`$_POST` + `wp_update_post()`).
	 *
	 * @param int                   $id    Lokalizacja ID.
	 * @param array<string, string> $form  Fields `pw_lokalizacja[…]`.
	 * @param string|null           $nonce Nonce; null = a valid one.
	 * @param string                $role  Role of the submitting user.
	 */
	private function save_metabox( int $id, array $form, ?string $nonce = null, string $role = 'editor' ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
		$_POST = wp_slash(
			array(
				'pw_lokalizacja_nonce' => $nonce ?? wp_create_nonce( 'pw_lokalizacja_save' ),
				'pw_lokalizacja'       => $form,
			)
		);
		wp_update_post( array( 'ID' => $id ) );
		$_POST = array();
	}

	/**
	 * Published Lokalizacja with meta.
	 *
	 * @param array<string, mixed>  $post Post fields.
	 * @param array<string, string> $meta Meta.
	 */
	private function lokalizacja( array $post = array(), array $meta = array() ): int {
		$id = self::factory()->post->create(
			array_merge(
				array(
					'post_type'   => 'lokalizacja',
					'post_status' => 'publish',
				),
				$post
			)
		);
		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
		return $id;
	}
}
