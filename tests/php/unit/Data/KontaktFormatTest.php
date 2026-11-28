<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * Pure helpers of „Dane strony” (#48): NIP, phone and e-mail formatting.
 *
 * @package PW
 */

namespace PW\Tests\Unit\Data;

use PW\Tests\Unit\TestCase;

/**
 * Functions from theme/inc/data/kontakt-format.php.
 */
final class KontaktFormatTest extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		require_once self::theme_path( 'inc/data/kontakt-format.php' );
	}

	public function test_nip_format_groups_digits_with_dashes(): void {
		$this->assertSame( '123-456-78-90', pw_nip_format( '1234567890' ) );
		$this->assertSame( '123-456-78-90', pw_nip_format( '123 456 78 90' ) );
	}

	public function test_nip_format_leaves_other_lengths_as_digits(): void {
		$this->assertSame( '12345', pw_nip_format( '12-345' ) );
		$this->assertSame( '', pw_nip_format( '' ) );
	}

	public function test_nip_normalize_accepts_valid_nip_with_separators(): void {
		$this->assertSame( '1234563218', pw_nip_normalize( '123-456-32-18' ) );
		$this->assertSame( '1234563218', pw_nip_normalize( 'PL 123 456 32 18' ) );
	}

	public function test_nip_normalize_rejects_bad_checksum(): void {
		$this->assertSame( '', pw_nip_normalize( '1234563219' ) );
	}

	public function test_nip_normalize_rejects_wrong_length(): void {
		$this->assertSame( '', pw_nip_normalize( '123456321' ) );
		$this->assertSame( '', pw_nip_normalize( '12345632180' ) );
	}

	public function test_nip_normalize_rejects_checksum_of_ten(): void {
		// Weighted sum 8*6 + 1*5 + … + 1*7 = 87 ≡ 10 (mod 11): no valid control digit.
		$this->assertSame( '', pw_nip_normalize( '8111111110' ) );
	}

	public function test_email_short_keeps_local_part(): void {
		$this->assertSame( 'kontakt@…', pw_email_short( 'kontakt@przemyslawwarzecha.pl' ) );
	}

	public function test_email_short_without_at_returns_input(): void {
		$this->assertSame( 'kontakt', pw_email_short( 'kontakt' ) );
		$this->assertSame( '', pw_email_short( '' ) );
	}

	public function test_phone_format_adds_polish_prefix_and_groups_digits(): void {
		$this->assertSame( '+48 600 000 000', pw_phone_format( '600000000' ) );
		$this->assertSame( '+48 600 123 456', pw_phone_format( '600-123-456' ) );
		$this->assertSame( '+48 600 123 456', pw_phone_format( '+48600123456' ) );
		$this->assertSame( '+48 600 123 456', pw_phone_format( '0048 (600) 123.456' ) );
	}

	public function test_phone_format_keeps_foreign_number_spacing(): void {
		$this->assertSame( '+420 777 123 456', pw_phone_format( '+420  777 123 456' ) );
	}

	public function test_phone_format_rejects_letters_and_too_few_digits(): void {
		$this->assertSame( '', pw_phone_format( '600 000 00x' ) );
		$this->assertSame( '', pw_phone_format( '600 000' ) );
		$this->assertSame( '', pw_phone_format( '' ) );
	}
}
