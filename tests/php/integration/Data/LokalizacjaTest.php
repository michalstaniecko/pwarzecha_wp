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
}
