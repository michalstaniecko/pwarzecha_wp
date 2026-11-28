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

	public function set_up(): void {
		parent::set_up();
		// WP_UnitTestCase::tear_down() unregisters every meta key; `init` does not run again.
		\PW\Data\Lokalizacja\register_meta();
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
