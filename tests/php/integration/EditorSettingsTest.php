<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * Editor features the Zleceniodawca must not see (#43): Openverse, Font Library, wp.org patterns.
 *
 * @package PW
 */

namespace PW\Tests\Integration;

use WP_Block_Editor_Context;
use WP_UnitTestCase;

/**
 * Checks the filtered editor settings and pattern sources.
 */
class EditorSettingsTest extends WP_UnitTestCase {

	/**
	 * Editor settings for the post editor.
	 *
	 * @return array<string, mixed>
	 */
	private function post_editor_settings(): array {
		$post = self::factory()->post->create_and_get();
		return get_block_editor_settings( array(), new WP_Block_Editor_Context( array( 'post' => $post ) ) );
	}

	/**
	 * No Openverse media category.
	 */
	public function test_openverse_is_disabled(): void {
		$this->assertFalse( $this->post_editor_settings()['enableOpenverseMediaCategory'] );
	}

	/**
	 * No Font Library: fonts come from theme.json.
	 */
	public function test_font_library_is_disabled(): void {
		$this->assertFalse( $this->post_editor_settings()['fontLibraryEnabled'] );
	}

	/**
	 * Sekcje inserted from unsynced patterns stay fully editable (WP 7.1.5 locks their inner
	 * blocks to contentOnly by default; checked in the editor, see #43).
	 */
	public function test_unsynced_patterns_are_not_content_only(): void {
		$this->assertTrue( $this->post_editor_settings()['disableContentOnlyForUnsyncedPatterns'] );
	}

	/**
	 * Only the theme patterns (Sekcje) in the inserter.
	 */
	public function test_remote_and_core_patterns_are_not_loaded(): void {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core hook.
		$this->assertFalse( apply_filters( 'should_load_remote_block_patterns', true ) );
		$this->assertFalse( get_theme_support( 'core-block-patterns' ) );
	}
}
