<?php
/**
 * Block registration from a wp-scripts manifest (#36): the code path of theme/inc/blocks.php.
 *
 * The bootstrap registers the pw/test fixture with register_block_type( dir ); here it is
 * registered again through PW\Blocks\register_collection() from a manifest generated into a
 * temporary directory, then restored.
 *
 * @package PW
 */

namespace PW\Tests\Integration;

use PW\Tests\BlockTestCase;
use WP_Block_Type_Registry;

/**
 * `PW\Blocks\register_collection()`.
 */
final class BlocksManifestTest extends BlockTestCase {

	/**
	 * Temporary directory with the generated manifest.
	 *
	 * @var string
	 */
	private string $tmp = '';

	public function set_up(): void {
		parent::set_up();
		$this->tmp = get_temp_dir() . 'pw-blocks-manifest-' . wp_generate_password( 8, false );
		wp_mkdir_p( $this->tmp );
		unregister_block_type( 'pw/test' );
	}

	public function tear_down(): void {
		if ( WP_Block_Type_Registry::get_instance()->is_registered( 'pw/test' ) ) {
			unregister_block_type( 'pw/test' );
		}
		register_block_type( self::fixtures() . '/pw-test' );
		foreach ( (array) glob( $this->tmp . '/*' ) as $file ) {
			wp_delete_file( $file );
		}
		rmdir( $this->tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- test temp dir.
		parent::tear_down();
	}

	public function test_fixture_block_registers_from_the_manifest_and_renders_through_render_php(): void {
		// Default changed only in the manifest: rendering it proves the manifest was read.
		$manifest = $this->manifest( array( 'attributes' => array( 'message' => array( 'default' => 'z manifestu' ) ) ) );

		\PW\Blocks\register_collection( self::fixtures(), $manifest );

		$this->assertTrue( WP_Block_Type_Registry::get_instance()->is_registered( 'pw/test' ) );
		$html = $this->render_block_markup( 'pw/test' );
		$this->assertHtmlHasSelector( $html, 'p.wp-block-pw-test[data-pw-test="1"]' );
		$this->assertStringContainsString( 'z manifestu', $html );
	}

	public function test_missing_manifest_registers_nothing(): void {
		\PW\Blocks\register_collection( self::fixtures(), $this->tmp . '/blocks-manifest.php' );

		$this->assertFalse( WP_Block_Type_Registry::get_instance()->is_registered( 'pw/test' ) );
	}

	public function test_theme_registers_its_build_on_init(): void {
		$this->assertSame( 10, has_action( 'init', 'PW\Blocks\register' ) );
	}

	/**
	 * Writes a manifest like `wp-scripts build-blocks-manifest`: `<?php return [ dir => block.json ];`.
	 *
	 * @param array<string, mixed> $override Merged (recursively) into the fixture's block.json.
	 * @return string Manifest path.
	 */
	private function manifest( array $override ): string {
		$metadata = json_decode( (string) file_get_contents( self::fixtures() . '/pw-test/block.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local fixture.
		$path     = $this->tmp . '/blocks-manifest.php';
		file_put_contents( $path, '<?php return ' . var_export( array( 'pw-test' => array_replace_recursive( $metadata, $override ) ), true ) . ';' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- test temp file.
		return $path;
	}

	/**
	 * Directory of the fixture blocks (collection root).
	 */
	private static function fixtures(): string {
		return dirname( __DIR__, 2 ) . '/fixtures/blocks';
	}
}
