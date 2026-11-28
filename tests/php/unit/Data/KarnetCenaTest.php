<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * Karnet prices: monthly minimum for Teaser ceny and the „120 zł” format (#51, Q12).
 *
 * @package PW
 */

namespace PW\Tests\Unit\Data;

use PW\Tests\Unit\TestCase;

/**
 * Pure functions from theme/inc/data/karnet-cena.php.
 */
final class KarnetCenaTest extends TestCase {

	private const NBSP = "\u{00A0}";

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		require_once self::theme_path( 'inc/data/karnet-cena.php' );
	}

	/**
	 * Karnet in the shape returned by pw_karnety().
	 *
	 * @param int $cena           Price in złoty.
	 * @param int $okres_miesiace Payment period in months.
	 * @return array<string, mixed>
	 */
	private static function karnet( int $cena, int $okres_miesiace ): array {
		return array(
			'id'            => 1,
			'nazwa'         => 'Karnet',
			'cena'          => $cena,
			'okresMiesiace' => $okres_miesiace,
			'okresEtykieta' => '/ mies.',
			'obejmuje'      => array(),
			'polecany'      => false,
		);
	}

	public function test_no_karnety_means_no_monthly_price(): void {
		$this->assertNull( pw_karnet_min_cena_miesieczna( array() ) );
	}

	public function test_monthly_karnet_keeps_its_price(): void {
		$this->assertSame( 150, pw_karnet_min_cena_miesieczna( array( self::karnet( 150, 1 ) ) ) );
	}

	public function test_returns_the_lowest_price_per_month_across_periods(): void {
		$karnety = array(
			self::karnet( 150, 1 ),  // 150 / mies.
			self::karnet( 600, 5 ),  // 120 / mies. (semestr)
			self::karnet( 1300, 10 ), // 130 / mies. (rok)
		);

		$this->assertSame( 120, pw_karnet_min_cena_miesieczna( $karnety ) );
	}

	public function test_rounds_the_monthly_price_down_to_whole_zloty(): void {
		// 1199 / 10 = 119.9 → 119; 599 / 5 = 119.8 → 119; 120 / 1 = 120.
		$this->assertSame( 119, pw_karnet_min_cena_miesieczna( array( self::karnet( 120, 1 ), self::karnet( 1199, 10 ) ) ) );
		$this->assertSame( 119, pw_karnet_min_cena_miesieczna( array( self::karnet( 599, 5 ) ) ) );
	}

	public function test_formats_whole_zloty_with_non_breaking_space(): void {
		$this->assertSame( '120' . self::NBSP . 'zł', pw_cena_format( 120 ) );
	}

	public function test_formats_zero(): void {
		$this->assertSame( '0' . self::NBSP . 'zł', pw_cena_format( 0 ) );
	}

	public function test_separates_thousands_with_a_non_breaking_space(): void {
		$this->assertSame( '1' . self::NBSP . '200' . self::NBSP . 'zł', pw_cena_format( 1200 ) );
		$this->assertSame( '12' . self::NBSP . '345' . self::NBSP . '678' . self::NBSP . 'zł', pw_cena_format( 12345678 ) );
	}
}
