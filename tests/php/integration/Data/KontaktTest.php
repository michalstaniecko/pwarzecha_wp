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
}
