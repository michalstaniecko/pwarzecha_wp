<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * „Dane strony” (#48): option `pw_kontakt`, repository pw_kontakt(), Block Bindings `pw/kontakt`.
 *
 * @package PW
 */

namespace PW\Tests\Integration\Data;

use PW\Tests\BlockTestCase;

/**
 * Stored through update_option(), read through pw_kontakt() and the `pw/kontakt` source.
 */
class KontaktTest extends BlockTestCase {

	/**
	 * Every key of the option.
	 */
	private const KEYS = array(
		'telefon',
		'email',
		'messenger',
		'godzinyTelefonu',
		'facebook',
		'instagram',
		'youtube',
		'tiktok',
		'nazwaFirmy',
		'nip',
		'adresRejestrowy',
		'topbarTekst',
	);

	public function set_up(): void {
		parent::set_up();
		$GLOBALS['wp_settings_errors'] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- settings errors are not reset between tests.
	}

	public function test_repository_returns_every_key_with_defaults(): void {
		$kontakt = pw_kontakt();

		$this->assertSame( self::KEYS, array_keys( $kontakt ) );
		$this->assertSame( '', $kontakt['telefon'] );
		$this->assertSame( 'Pierwszy trening bezpłatny', $kontakt['topbarTekst'] );
	}

	public function test_stored_empty_topbar_text_stays_hidden(): void {
		update_option( 'pw_kontakt', array( 'topbarTekst' => '' ) );

		$this->assertSame( '', pw_kontakt_pole( 'topbarTekst' ) );
	}

	public function test_filter_overrides_result_and_missing_keys_are_empty(): void {
		update_option( 'pw_kontakt', array( 'telefon' => '600000000' ) );
		$this->with_data( 'kontakt', array( 'email' => 'filtr@example.com' ) );

		$kontakt = pw_kontakt();

		$this->assertSame( self::KEYS, array_keys( $kontakt ) );
		$this->assertSame( 'filtr@example.com', $kontakt['email'] );
		$this->assertSame( '', $kontakt['telefon'] );
	}

	public function test_filter_receives_stored_data(): void {
		update_option( 'pw_kontakt', array( 'telefon' => '+48 600 000 000' ) );
		$seen = null;
		add_filter(
			'pw/data/kontakt',
			static function ( $data ) use ( &$seen ) {
				$seen = $data;
				return $data;
			}
		);

		pw_kontakt();

		$this->assertSame( '+48 600 000 000', $seen['telefon'] );
	}

	public function test_non_array_filter_result_gives_empty_values(): void {
		$this->with_data( 'kontakt', array() );
		add_filter( 'pw/data/kontakt', '__return_null', 20 );

		$this->assertSame( array_fill_keys( self::KEYS, '' ), pw_kontakt() );
	}

	public function test_unknown_field_is_empty(): void {
		$this->assertSame( '', pw_kontakt_pole( 'nieistniejace' ) );
	}

	/**
	 * Valid input of every field.
	 *
	 * @return array<string, string>
	 */
	private static function valid_input(): array {
		return array(
			'telefon'         => '600-123-456',
			'email'           => ' kontakt@example.com ',
			'messenger'       => 'https://m.me/samoobrona',
			'godzinyTelefonu' => '<b>pn.–pt.</b> 9:00–17:00',
			'facebook'        => 'https://www.facebook.com/samoobrona',
			'instagram'       => 'https://www.instagram.com/samoobrona',
			'youtube'         => 'https://www.youtube.com/@samoobrona',
			'tiktok'          => 'https://www.tiktok.com/@samoobrona',
			'nazwaFirmy'      => 'Samoobrona <i>Przemek</i>',
			'nip'             => '123-456-32-18',
			'adresRejestrowy' => "ul. Przykładowa 1\n00-001 Warszawa<script>x</script>",
			'topbarTekst'     => 'Pierwszy trening bezpłatny',
			'obce'            => 'x',
		);
	}

	/**
	 * Codes of the settings errors added for the option.
	 *
	 * @return string[]
	 */
	private static function error_codes(): array {
		return array_column( get_settings_errors( 'pw_kontakt' ), 'code' );
	}

	public function test_update_option_sanitizes_every_field(): void {
		update_option( 'pw_kontakt', self::valid_input() );

		$this->assertSame(
			array(
				'telefon'         => '+48 600 123 456',
				'email'           => 'kontakt@example.com',
				'messenger'       => 'https://m.me/samoobrona',
				'godzinyTelefonu' => 'pn.–pt. 9:00–17:00',
				'facebook'        => 'https://www.facebook.com/samoobrona',
				'instagram'       => 'https://www.instagram.com/samoobrona',
				'youtube'         => 'https://www.youtube.com/@samoobrona',
				'tiktok'          => 'https://www.tiktok.com/@samoobrona',
				'nazwaFirmy'      => 'Samoobrona Przemek',
				'nip'             => '1234563218',
				'adresRejestrowy' => "ul. Przykładowa 1\n00-001 Warszawa",
				'topbarTekst'     => 'Pierwszy trening bezpłatny',
			),
			get_option( 'pw_kontakt' )
		);
		$this->assertSame( array(), self::error_codes() );
	}

	public function test_invalid_email_is_rejected_with_settings_error(): void {
		update_option( 'pw_kontakt', array( 'email' => 'stary@example.com' ) );

		update_option( 'pw_kontakt', array( 'email' => 'to nie e-mail' ) );

		$this->assertSame( 'stary@example.com', get_option( 'pw_kontakt' )['email'] );
		$this->assertContains( 'pw_kontakt_email', self::error_codes() );
	}

	public function test_http_social_links_are_rejected(): void {
		update_option(
			'pw_kontakt',
			array(
				'facebook'  => 'http://www.facebook.com/samoobrona',
				'instagram' => 'javascript:alert(1)',
			)
		);

		$stored = get_option( 'pw_kontakt' );
		$this->assertSame( '', $stored['facebook'] );
		$this->assertSame( '', $stored['instagram'] );
		$this->assertContains( 'pw_kontakt_facebook', self::error_codes() );
		$this->assertContains( 'pw_kontakt_instagram', self::error_codes() );
	}

	public function test_messenger_accepts_only_messenger_hosts(): void {
		update_option( 'pw_kontakt', array( 'messenger' => 'https://www.messenger.com/t/samoobrona' ) );
		$this->assertSame( 'https://www.messenger.com/t/samoobrona', pw_kontakt_pole( 'messenger' ) );

		update_option( 'pw_kontakt', array( 'messenger' => 'https://example.com/m.me/x' ) );
		$this->assertSame( 'https://www.messenger.com/t/samoobrona', pw_kontakt_pole( 'messenger' ) );
		$this->assertContains( 'pw_kontakt_messenger', self::error_codes() );
	}

	public function test_invalid_phone_and_nip_are_rejected(): void {
		update_option(
			'pw_kontakt',
			array(
				'telefon' => 'zadzwoń',
				'nip'     => '1234563219',
			)
		);

		$this->assertSame( '', pw_kontakt_pole( 'telefon' ) );
		$this->assertSame( '', pw_kontakt_pole( 'nip' ) );
		$this->assertContains( 'pw_kontakt_telefon', self::error_codes() );
		$this->assertContains( 'pw_kontakt_nip', self::error_codes() );
	}

	public function test_empty_fields_are_allowed_without_errors(): void {
		update_option( 'pw_kontakt', array_fill_keys( self::KEYS, '' ) );

		$this->assertSame( array_fill_keys( self::KEYS, '' ), get_option( 'pw_kontakt' ) );
		$this->assertSame( array(), self::error_codes() );
	}

	public function test_option_is_autoloaded(): void {
		update_option( 'pw_kontakt', self::valid_input() );

		$this->assertArrayHasKey( 'pw_kontakt', wp_load_alloptions() );
	}

	public function test_update_fires_hook_with_new_and_old_data(): void {
		update_option( 'pw_kontakt', array( 'telefon' => '600000000' ) );
		$calls = array();
		add_action(
			'pw/data/kontakt/updated',
			static function ( $new_data, $old_data ) use ( &$calls ) {
				$calls[] = array( $new_data['telefon'], $old_data['telefon'] );
			},
			10,
			2
		);

		update_option( 'pw_kontakt', array( 'telefon' => '600111222' ) );

		$this->assertSame( array( array( '+48 600 111 222', '+48 600 000 000' ) ), $calls );
	}

	/**
	 * Value of the `pw/kontakt` source for a key.
	 *
	 * @param string $key Source key.
	 * @return mixed
	 */
	private static function binding( string $key ) {
		return get_block_bindings_source( 'pw/kontakt' )->get_value( array( 'key' => $key ), null, 'content' );
	}

	public function test_bindings_source_is_registered(): void {
		$source = get_block_bindings_source( 'pw/kontakt' );

		$this->assertNotNull( $source );
		$this->assertSame( 'Dane strony', $source->label );
		$this->assertSame( array(), $source->uses_context ?? array() );
	}

	public function test_bindings_source_returns_every_key(): void {
		update_option( 'pw_kontakt', self::valid_input() );
		$expected = array(
			'telefon'         => '+48 600 123 456',
			'telefonHref'     => 'tel:+48600123456',
			'email'           => 'kontakt@example.com',
			'emailHref'       => 'mailto:kontakt@example.com',
			'messenger'       => 'https://m.me/samoobrona',
			'godzinyTelefonu' => 'pn.–pt. 9:00–17:00',
			'nazwaFirmy'      => 'Samoobrona Przemek',
			'nip'             => '123-456-32-18',
			'adresRejestrowy' => "ul. Przykładowa 1\n00-001 Warszawa",
			'topbarTekst'     => 'Pierwszy trening bezpłatny',
			'facebook'        => 'https://www.facebook.com/samoobrona',
			'instagram'       => 'https://www.instagram.com/samoobrona',
			'youtube'         => 'https://www.youtube.com/@samoobrona',
			'tiktok'          => 'https://www.tiktok.com/@samoobrona',
			'copyright'       => '© ' . wp_date( 'Y' ) . ' Samoobrona Przemek',
		);

		foreach ( $expected as $key => $value ) {
			$this->assertSame( $value, self::binding( $key ), $key );
		}
		$this->assertSame( array_keys( $expected ), PW_KONTAKT_BINDING_KEYS );
	}

	public function test_copyright_falls_back_to_site_name(): void {
		update_option( 'blogname', 'Strona testowa' );
		$this->with_data( 'kontakt', array( 'nazwaFirmy' => '' ) );

		$this->assertSame( '© ' . wp_date( 'Y' ) . ' Strona testowa', self::binding( 'copyright' ) );
	}

	public function test_bindings_source_ignores_unknown_key(): void {
		$this->assertNull( self::binding( 'haslo' ) );
	}

	public function test_paragraph_with_binding_renders_phone(): void {
		$this->with_data( 'kontakt', array( 'telefon' => '+48 600 000 000' ) );

		$html = do_blocks(
			'<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"pw/kontakt","args":{"key":"telefon"}}}}} --><p>placeholder</p><!-- /wp:paragraph -->'
		);

		$this->assertStringContainsString( '>+48 600 000 000</p>', $html );
	}

	public function test_button_with_bindings_renders_text_and_url(): void {
		$this->with_data( 'kontakt', array( 'email' => 'kontakt@example.com' ) );

		$html = do_blocks(
			'<!-- wp:button {"metadata":{"bindings":{"text":{"source":"pw/kontakt","args":{"key":"email"}},"url":{"source":"pw/kontakt","args":{"key":"emailHref"}}}}} --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">x</a></div><!-- /wp:button -->'
		);

		$this->assertHtmlHasSelector( $html, 'a.wp-block-button__link[href="mailto:kontakt@example.com"]' );
		$this->assertStringContainsString( '>kontakt@example.com</a>', $html );
	}

	public function test_bound_values_are_escaped(): void {
		$this->with_data( 'kontakt', array( 'topbarTekst' => self::XSS ) );

		$html = do_blocks(
			'<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"pw/kontakt","args":{"key":"topbarTekst"}}}}} --><p>placeholder</p><!-- /wp:paragraph -->'
		);

		$this->assertNoUnescaped( $html );
	}
}
