<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * Blocks the Zleceniodawca can insert per editor (#43, #21).
 *
 * @package PW
 */

namespace PW\Tests\Integration;

use WP_Block_Editor_Context;
use WP_UnitTestCase;

/**
 * Goes through `get_allowed_block_types()`, i.e. the `allowed_block_types_all` filter.
 */
class EditorAllowedBlocksTest extends WP_UnitTestCase {

	/**
	 * Allowed block types for a new post of the given type.
	 *
	 * @param string $post_type Post type.
	 * @return bool|array<int, string>
	 */
	private function allowed_for( string $post_type ) {
		$post = self::factory()->post->create_and_get( array( 'post_type' => $post_type ) );
		return get_allowed_block_types( new WP_Block_Editor_Context( array( 'post' => $post ) ) );
	}

	/**
	 * Aktualność gets only the #21 list.
	 */
	public function test_aktualnosc_allows_only_simple_blocks_photo_checklist_video_gallery(): void {
		$allowed = $this->allowed_for( 'post' );

		$this->assertIsArray( $allowed );
		$this->assertEqualsCanonicalizing(
			array(
				'core/paragraph',
				'core/heading',
				'core/list',
				'core/list-item',
				'core/quote',
				'core/image',
				'core/gallery',
				'pw/checklist-pair',
				'pw/video',
			),
			$allowed
		);
	}

	/**
	 * Pages get every pw/* block and the selected core blocks, never embed/html/layout blocks.
	 */
	public function test_page_allows_pw_blocks_and_selected_core_blocks(): void {
		$allowed = $this->allowed_for( 'page' );

		$this->assertIsArray( $allowed );
		// Fixture block registered by the test bootstrap (tests/fixtures/blocks/pw-test).
		$this->assertContains( 'pw/test', $allowed );
		foreach ( array( 'core/table', 'core/buttons', 'core/button', 'core/block', 'core/group', 'core/gallery' ) as $block ) {
			$this->assertContains( $block, $allowed );
		}
		foreach ( array( 'core/embed', 'core/html', 'core/columns', 'core/cover', 'core/media-text' ) as $block ) {
			$this->assertNotContains( $block, $allowed );
		}
	}

	/**
	 * Section blocks stay out of Aktualności.
	 */
	public function test_aktualnosc_does_not_get_page_sections(): void {
		$this->assertNotContains( 'pw/test', $this->allowed_for( 'post' ) );
	}

	/**
	 * Templates in the Site Editor use any block.
	 */
	public function test_site_editor_is_not_restricted(): void {
		$context = new WP_Block_Editor_Context( array( 'name' => 'core/edit-site' ) );

		$this->assertTrue( get_allowed_block_types( $context ) );
	}
}
