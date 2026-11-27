<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * Configuration handed to the editor script (#43): the post type of the editor screen, so
 * heading levels are limited only in an Aktualność (`post`).
 *
 * @package PW
 */

namespace PW\Tests\Integration;

use WP_UnitTestCase;

/**
 * `PW\Editor\editor_config()`.
 */
class EditorAssetsTest extends WP_UnitTestCase {

	public function tear_down(): void {
		set_current_screen( 'front' );
		parent::tear_down();
	}

	/**
	 * Screen => post type in the config.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function screens(): array {
		return array(
			'post editor'  => array( 'post', 'post' ),
			'page editor'  => array( 'page', 'page' ),
			'Site Editor'  => array( 'site-editor', '' ),
			'other screen' => array( 'dashboard', '' ),
		);
	}

	/**
	 * Post editor screens pass their post type; the Site Editor and other screens pass ''.
	 *
	 * @dataProvider screens
	 *
	 * @param string $screen    Screen ID.
	 * @param string $post_type Expected `postType`.
	 */
	public function test_config_names_the_post_type_of_the_editor_screen( string $screen, string $post_type ): void {
		set_current_screen( $screen );

		$this->assertSame( array( 'postType' => $post_type ), \PW\Editor\editor_config() );
	}

	public function test_config_without_a_screen_is_unrestricted(): void {
		$GLOBALS['current_screen'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- no admin screen.

		$this->assertSame( array( 'postType' => '' ), \PW\Editor\editor_config() );
	}
}
