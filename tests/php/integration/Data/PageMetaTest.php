<?php
/**
 * Page meta `pw_lokalizacja`, `pw_strona_lokalizacje()` and page excerpts (#52).
 *
 * @package PW
 */

namespace PW\Tests\Integration\Data;

use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Lokalizacje of a page as the document panel, the blocks and the schema see them.
 */
class PageMetaTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		// WP_UnitTestCase::tear_down() unregisters every meta key; `init` does not run again.
		\PW\Data\PageMeta\register_meta();
		\PW\Data\Lokalizacja\register_meta();
	}

	public function test_editor_saves_lokalizacje_through_rest_in_order_of_selection(): void {
		$chorzow  = $this->lokalizacja( 'Chorzów' );
		$katowice = $this->lokalizacja( 'Katowice' );
		$page     = self::factory()->post->create( array( 'post_type' => 'page' ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$response = $this->save_rest( $page, array( $katowice, $chorzow ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( $katowice, $chorzow ), $response->get_data()['meta']['pw_lokalizacja'] );
		$this->assertSame( array( $katowice, $chorzow ), get_post_meta( $page, 'pw_lokalizacja', true ) );
	}

	public function test_page_without_lokalizacje_reads_an_empty_list(): void {
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/pages/' . $page ) );

		$this->assertSame( array(), $response->get_data()['meta']['pw_lokalizacja'] );
	}

	/**
	 * Updates the page meta like the editor does (POST /wp/v2/pages/<id>).
	 *
	 * @param int   $page Page ID.
	 * @param mixed $ids  Meta value.
	 */
	private function save_rest( int $page, $ids ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/wp/v2/pages/' . $page );
		$request->set_body_params( array( 'meta' => array( 'pw_lokalizacja' => $ids ) ) );
		return rest_do_request( $request );
	}

	/**
	 * Lokalizacja post (published by default).
	 *
	 * @param string $title  Title.
	 * @param string $status Post status.
	 */
	private function lokalizacja( string $title, string $status = 'publish' ): int {
		$id = self::factory()->post->create(
			array(
				'post_type'   => 'lokalizacja',
				'post_status' => $status,
				'post_title'  => $title,
			)
		);
		update_post_meta( $id, 'pw_miasto', $title );
		return $id;
	}
}
