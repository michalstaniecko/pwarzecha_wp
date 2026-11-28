<?php
/**
 * Page meta `pw_schema_typ` and `pw_area_served` (#53).
 *
 * @package PW
 */

namespace PW\Tests\Integration\Schema;

use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Schema type and cities of a page as the document panel saves them.
 */
class MetaTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		\PW\Schema\register_meta();
	}

	public function test_editor_saves_type_and_cities_through_rest(): void {
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$response = $this->save_rest(
			$page,
			array(
				'pw_schema_typ'  => 'service',
				'pw_area_served' => array( 'Bytom', ' Chorzów ', 'Bytom', '' ),
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'service', get_post_meta( $page, 'pw_schema_typ', true ) );
		$this->assertSame( array( 'Bytom', 'Chorzów' ), get_post_meta( $page, 'pw_area_served', true ) );
	}

	public function test_strona_oferty_is_a_type(): void {
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$this->assertSame( 200, $this->save_rest( $page, array( 'pw_schema_typ' => 'oferta' ) )->get_status() );
		$this->assertSame( 'oferta', \PW\Schema\typ( $page ) );
	}

	public function test_rest_rejects_an_unknown_type(): void {
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$response = $this->save_rest( $page, array( 'pw_schema_typ' => 'faq' ) );

		$this->assertSame( 400, $response->get_status() );
	}

	public function test_defaults_are_brak_and_the_default_cities(): void {
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$meta = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/pages/' . $page ) )->get_data()['meta'];

		$this->assertSame( 'brak', $meta['pw_schema_typ'] );
		$this->assertSame( array(), $meta['pw_area_served'] );
		$this->assertSame( 'brak', \PW\Schema\typ( $page ) );
		$this->assertSame( array( 'Chorzów', 'Katowice' ), \PW\Schema\area_served( $page ) );
	}

	public function test_code_path_is_sanitised_too(): void {
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$post = self::factory()->post->create();

		update_post_meta( $page, 'pw_schema_typ', 'faq' );
		update_post_meta( $post, 'pw_schema_typ', 'trener' );

		$this->assertSame( 'brak', get_post_meta( $page, 'pw_schema_typ', true ) );
		$this->assertSame( 'brak', \PW\Schema\typ( $post ), 'only pages have a schema type' );
	}

	public function test_subscriber_cannot_change_the_type(): void {
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->assertGreaterThanOrEqual( 400, $this->save_rest( $page, array( 'pw_schema_typ' => 'trener' ) )->get_status() );
		$this->assertSame( 'brak', \PW\Schema\typ( $page ) );
	}

	/**
	 * Saves page meta through the REST API like the editor does.
	 *
	 * @param int                  $page Page ID.
	 * @param array<string, mixed> $meta Meta.
	 */
	private function save_rest( int $page, array $meta ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/wp/v2/pages/' . $page );
		$request->set_body_params( array( 'meta' => $meta ) );
		return rest_do_request( $request );
	}
}
