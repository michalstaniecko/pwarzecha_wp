<?php
/**
 * Smoke test of the integration layer: WordPress boots with the theme active (#37).
 *
 * @package PW
 */

namespace PW\Tests;

/**
 * The theme as WordPress sees it.
 */
final class ThemeTest extends \WP_UnitTestCase {

	public function test_pw_theme_is_the_active_block_theme(): void {
		$this->assertSame( 'pw-theme', get_stylesheet() );
		$this->assertTrue( wp_is_block_theme() );
	}

	public function test_pinned_plugins_are_active(): void {
		$this->assertTrue( defined( 'WPSEO_VERSION' ), 'Yoast SEO not loaded.' );
		$this->assertTrue( defined( 'CLI_VERSION' ), 'CookieYes not loaded.' );
	}
}
