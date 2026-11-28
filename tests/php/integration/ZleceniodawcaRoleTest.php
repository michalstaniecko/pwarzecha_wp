<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * The Zleceniodawca account (role `editor`) gets menus and „Dane strony”, never theme or plugin
 * switching (#43, migration 0001).
 *
 * @package PW
 */

namespace PW\Tests\Integration;

use WP_UnitTestCase;

/**
 * Runs the migration file the same way the runner does (`( require $file )()`).
 */
class ZleceniodawcaRoleTest extends WP_UnitTestCase {

	private const MIGRATION = __DIR__ . '/../../../theme/inc/migrations/0001-rola-zleceniodawcy.php';

	/**
	 * Executes the migration once.
	 */
	private function migrate(): void {
		$migration = require self::MIGRATION;
		$migration();
	}

	/**
	 * Menus and „Dane strony”.
	 */
	public function test_editor_can_edit_menus_and_site_data(): void {
		$this->migrate();

		$editor = get_role( 'editor' );
		$this->assertTrue( $editor->has_cap( 'edit_theme_options' ) );
		$this->assertTrue( $editor->has_cap( 'pw_manage_site_data' ) );
	}

	/**
	 * The data model lives in the theme (ADR 0007), so no theme or plugin switching.
	 */
	public function test_editor_cannot_switch_theme_or_manage_plugins(): void {
		$this->migrate();

		$editor = get_role( 'editor' );
		foreach ( array( 'switch_themes', 'activate_plugins', 'install_plugins' ) as $cap ) {
			$this->assertFalse( $editor->has_cap( $cap ), $cap );
		}
	}

	/**
	 * Administrators keep access to „Dane strony”.
	 */
	public function test_administrator_can_manage_site_data(): void {
		$this->migrate();

		$this->assertTrue( get_role( 'administrator' )->has_cap( 'pw_manage_site_data' ) );
	}

	/**
	 * A user with the editor role sees the result.
	 */
	public function test_zleceniodawca_user_gets_the_capabilities(): void {
		$this->migrate();

		$user = self::factory()->user->create_and_get( array( 'role' => 'editor' ) );
		$this->assertTrue( user_can( $user, 'edit_theme_options' ) );
		$this->assertTrue( user_can( $user, 'pw_manage_site_data' ) );
		$this->assertFalse( user_can( $user, 'switch_themes' ) );
	}

	/**
	 * The migration is idempotent.
	 */
	public function test_running_twice_gives_the_same_roles(): void {
		$this->migrate();
		$after_first = wp_roles()->roles;

		$this->migrate();

		$this->assertSame( $after_first, wp_roles()->roles );
	}
}
