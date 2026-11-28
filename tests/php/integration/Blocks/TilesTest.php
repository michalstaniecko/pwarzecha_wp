<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * Block pw/tiles: grid of tiles in 3 or 4 columns (#60).
 *
 * @package PW
 */

namespace PW\Tests\Integration\Blocks;

use PW\Tests\BlockTestCase;

/**
 * Rendered from block markup, the way the front end does.
 */
class TilesTest extends BlockTestCase {

	/**
	 * Inner blocks: one photo tile.
	 */
	private const INNER = '<!-- wp:pw/photo-tile {"tytul":"Bezpieczeństwo"} /-->';

	public function test_three_columns_by_default(): void {
		$html = $this->render_block_markup( 'pw/tiles', array(), self::INNER );

		$this->assertHtmlHasSelector( $html, 'div.sp-grid.sp-grid--3 > article.sp-photo-tile' );
	}

	public function test_four_columns(): void {
		$html = $this->render_block_markup( 'pw/tiles', array( 'kolumny' => '4' ), self::INNER );

		$this->assertHtmlHasSelector( $html, 'div.sp-grid.sp-grid--4' );
		$this->assertStringNotContainsString( 'sp-grid--3', $html );
	}

	public function test_unknown_column_count_falls_back_to_three(): void {
		$html = $this->render_block_markup( 'pw/tiles', array( 'kolumny' => '7" onclick="alert(1)' ), self::INNER );

		$this->assertHtmlHasSelector( $html, 'div.sp-grid.sp-grid--3' );
		$this->assertStringNotContainsString( 'sp-grid--7', $html );
		$this->assertNoUnescaped( $html );
	}

	public function test_empty_grid_renders_nothing(): void {
		$this->assertSame( '', trim( $this->render_block_markup( 'pw/tiles' ) ) );
	}
}
