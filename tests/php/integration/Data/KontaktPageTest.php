<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * „Dane strony” screen (#48): menu, capability `pw_manage_site_data`, Settings API form.
 *
 * @package PW
 */

namespace PW\Tests\Integration\Data;

use WP_UnitTestCase;

/**
 * Screen registered on `admin_menu` / `admin_init`, rendered by pw_kontakt_page_render().
 */
class KontaktPageTest extends WP_UnitTestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/template.php';
	}

	public function tear_down(): void {
		remove_role( 'pw_test_options' );
		parent::tear_down();
	}

	public function test_anyone_with_manage_options_may_manage_site_data(): void {
		add_role( 'pw_test_options', 'Opcje', array( 'manage_options' => true ) );
		$user = self::factory()->user->create( array( 'role' => 'pw_test_options' ) );

		$this->assertTrue( user_can( $user, 'pw_manage_site_data' ) );
	}

	public function test_subscriber_may_not_manage_site_data(): void {
		$user = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->assertFalse( user_can( $user, 'pw_manage_site_data' ) );
	}

	public function test_options_form_requires_site_data_capability(): void {
		$this->assertSame( 'pw_manage_site_data', apply_filters( 'option_page_capability_pw_kontakt', 'manage_options' ) );
	}

	public function test_options_php_accepts_the_option(): void {
		$allowed = apply_filters( 'allowed_options', array() );

		$this->assertSame( array( 'pw_kontakt' ), $allowed['pw_kontakt'] ?? null );
	}

	public function test_menu_page_is_registered(): void {
		global $menu;
		$menu = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- admin menu built for the test.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		do_action( 'admin_menu' );

		$item = current( array_filter( $menu, static fn( $entry ) => 'pw-dane-strony' === $entry[2] ) );
		$this->assertNotFalse( $item, 'No pw-dane-strony menu item.' );
		$this->assertSame( 'Dane strony', $item[0] );
		$this->assertSame( 'pw_manage_site_data', $item[1] );
		$this->assertSame( 'dashicons-phone', $item[6] );
	}

	public function test_form_has_every_field_with_stored_value(): void {
		update_option(
			'pw_kontakt',
			array(
				'telefon'         => '600000000',
				'adresRejestrowy' => "ul. Przykładowa 1\n00-001 Warszawa",
				'nazwaFirmy'      => 'Firma "Cudzysłów"',
			)
		);
		pw_kontakt_page_fields(); // What admin_init does for the screen (the full action runs plugin admin code).

		ob_start();
		pw_kontakt_page_render();
		$html = (string) ob_get_clean();

		$doc = \Dom\HTMLDocument::createFromString( '<!DOCTYPE html><body>' . $html, LIBXML_NOERROR );
		$this->assertSame( 'options.php', $doc->querySelector( 'form' )->getAttribute( 'action' ) );
		$this->assertNotNull( $doc->querySelector( 'input[name="option_page"][value="pw_kontakt"]' ) );
		foreach ( array( 'telefon', 'email', 'messenger', 'godzinyTelefonu', 'facebook', 'instagram', 'youtube', 'tiktok', 'nazwaFirmy', 'nip', 'topbarTekst' ) as $key ) {
			$this->assertNotNull( $doc->querySelector( 'input[name="pw_kontakt[' . $key . ']"]' ), $key );
		}
		$this->assertSame( '+48 600 000 000', $doc->querySelector( 'input[name="pw_kontakt[telefon]"]' )->getAttribute( 'value' ) );
		$this->assertSame( 'Firma "Cudzysłów"', $doc->querySelector( 'input[name="pw_kontakt[nazwaFirmy]"]' )->getAttribute( 'value' ) );
		$this->assertSame( "ul. Przykładowa 1\n00-001 Warszawa", $doc->querySelector( 'textarea[name="pw_kontakt[adresRejestrowy]"]' )->textContent ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API.
		$this->assertSame( 'Pierwszy trening bezpłatny', $doc->querySelector( 'input[name="pw_kontakt[topbarTekst]"]' )->getAttribute( 'value' ) );
	}
}
