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

	public function test_only_unique_published_lokalizacje_are_stored(): void {
		$chorzow  = $this->lokalizacja( 'Chorzów' );
		$katowice = $this->lokalizacja( 'Katowice' );
		$szkic    = $this->lokalizacja( 'Gliwice', 'draft' );
		$strona   = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$page     = self::factory()->post->create( array( 'post_type' => 'page' ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$response = $this->save_rest( $page, array( $katowice, 999999, $szkic, $strona, $katowice, $chorzow ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( $katowice, $chorzow ), get_post_meta( $page, 'pw_lokalizacja', true ) );

		update_post_meta( $page, 'pw_lokalizacja', 'nie lista' );
		$this->assertSame( array(), get_post_meta( $page, 'pw_lokalizacja', true ), 'code path is sanitised too' );
	}

	public function test_page_without_lokalizacje_reads_an_empty_list(): void {
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/pages/' . $page ) );

		$this->assertSame( array(), $response->get_data()['meta']['pw_lokalizacja'] );
	}

	public function test_strona_lokalizacje_are_normalised_in_order_of_selection(): void {
		$chorzow  = $this->lokalizacja( 'Chorzów', 'publish', 1 );
		$katowice = $this->lokalizacja( 'Katowice', 'publish', 2 );
		$usuniety = $this->lokalizacja( 'Gliwice' );
		$page     = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_post_meta( $page, 'pw_lokalizacja', array( $katowice, $usuniety, $chorzow ) );
		wp_delete_post( $usuniety, true );

		$lokalizacje = pw_strona_lokalizacje( $page );

		$this->assertSame( array( $katowice, $chorzow ), array_column( $lokalizacje, 'id' ), 'selection order, not menu_order; deleted skipped' );
		$this->assertSame( 'Katowice', $lokalizacje[0]['miasto'] );
		$this->assertSame( 'katowice', $lokalizacje[0]['kotwica'], 'normalised by the repository' );
	}

	public function test_strona_lokalizacje_skip_lokalizacje_unpublished_after_selection(): void {
		$chorzow = $this->lokalizacja( 'Chorzów' );
		$page    = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_post_meta( $page, 'pw_lokalizacja', array( $chorzow ) );
		wp_update_post(
			array(
				'ID'          => $chorzow,
				'post_status' => 'draft',
			)
		);

		$this->assertSame( array(), pw_strona_lokalizacje( $page ) );
		$this->assertSame( array(), pw_strona_lokalizacje( self::factory()->post->create( array( 'post_type' => 'page' ) ) ), 'page without meta' );
	}

	public function test_strona_lokalizacje_go_through_the_repository_filter(): void {
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$id   = $this->lokalizacja( 'Chorzów' );
		update_post_meta( $page, 'pw_lokalizacja', array( $id ) );
		add_filter(
			'pw/data/lokalizacje',
			static fn(): array => array(
				array(
					'id'     => $id,
					'miasto' => 'Z filtra',
				),
			)
		);

		$this->assertSame( 'Z filtra', pw_strona_lokalizacje( $page )[0]['miasto'] );
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
	 * @param string $title      Title.
	 * @param string $status     Post status.
	 * @param int    $menu_order Order in the Lokalizacje list.
	 */
	private function lokalizacja( string $title, string $status = 'publish', int $menu_order = 0 ): int {
		$id = self::factory()->post->create(
			array(
				'post_type'   => 'lokalizacja',
				'post_status' => $status,
				'post_title'  => $title,
				'menu_order'  => $menu_order,
			)
		);
		update_post_meta( $id, 'pw_miasto', $title );
		return $id;
	}
}
