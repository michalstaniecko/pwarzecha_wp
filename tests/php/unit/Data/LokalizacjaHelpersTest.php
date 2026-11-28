<?php
/**
 * Pure Lokalizacja helpers: one-line address, Grafik link, anchor, field validation (#49).
 *
 * @package PW
 */

namespace PW\Tests\Unit\Data;

use Brain\Monkey\Functions;
use PW\Tests\Unit\TestCase;

/**
 * Functions from theme/inc/data/lokalizacja-helpers.php.
 */
final class LokalizacjaHelpersTest extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		require_once self::theme_path( 'inc/data/lokalizacja-helpers.php' );
	}

	public function test_address_joins_street_postcode_and_city_in_one_line(): void {
		$this->assertSame(
			'ul. Wolności 1, 41-500 Chorzów',
			pw_lokalizacja_adres(
				array(
					'ulica'  => 'ul. Wolności 1',
					'kod'    => '41-500',
					'miasto' => 'Chorzów',
				)
			)
		);
	}

	public function test_address_skips_missing_parts(): void {
		$this->assertSame( 'Chorzów', pw_lokalizacja_adres( array( 'miasto' => 'Chorzów' ) ) );
		$this->assertSame(
			'ul. Wolności 1, Chorzów',
			pw_lokalizacja_adres(
				array(
					'ulica'  => 'ul. Wolności 1',
					'kod'    => '',
					'miasto' => 'Chorzów',
				)
			)
		);
		$this->assertSame( '', pw_lokalizacja_adres( array() ) );
	}

	public function test_grafik_url_points_at_the_lokalizacja_anchor(): void {
		Functions\when( 'home_url' )->alias( static fn( $path = '' ) => 'https://example.pl' . $path );

		$this->assertSame( 'https://example.pl/grafik/#chorzow', pw_lokalizacja_grafik_url( array( 'kotwica' => 'chorzow' ) ) );
		$this->assertSame( 'https://example.pl/grafik/', pw_lokalizacja_grafik_url( array( 'kotwica' => '' ) ) );
	}

	public function test_anchor_defaults_to_the_city_slug(): void {
		Functions\expect( 'sanitize_title' )->once()->with( 'Chorzów' )->andReturn( 'chorzow' );

		$this->assertSame( 'chorzow', pw_lokalizacja_kotwica( '', 'Chorzów' ) );
	}

	public function test_anchor_typed_by_the_editor_wins_over_the_city(): void {
		Functions\expect( 'sanitize_title' )->once()->with( 'Chorzów Batory' )->andReturn( 'chorzow-batory' );

		$this->assertSame( 'chorzow-batory', pw_lokalizacja_kotwica( ' Chorzów Batory ', 'Chorzów' ) );
	}

	public function test_anchor_taken_by_another_lokalizacja_gets_a_number(): void {
		$this->assertSame( 'chorzow', pw_lokalizacja_unikalna_kotwica( 'chorzow', array( 'katowice' ) ) );
		$this->assertSame( 'chorzow-2', pw_lokalizacja_unikalna_kotwica( 'chorzow', array( 'chorzow' ) ) );
		$this->assertSame( 'chorzow-3', pw_lokalizacja_unikalna_kotwica( 'chorzow', array( 'chorzow', 'chorzow-2' ) ) );
		$this->assertSame( '', pw_lokalizacja_unikalna_kotwica( '', array( '' ) ) );
	}

	/**
	 * Typed YouTube IDs and what gets stored.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function youtube_ids(): array {
		return array(
			'plain id'              => array( 'dQw4w9WgXcQ', 'dQw4w9WgXcQ' ),
			'dash and underscore'   => array( 'a-B_c1D2e3F', 'a-B_c1D2e3F' ),
			'surrounding spaces'    => array( ' dQw4w9WgXcQ ', 'dQw4w9WgXcQ' ),
			'too short'             => array( 'dQw4w9WgXc', '' ),
			'too long'              => array( 'dQw4w9WgXcQQ', '' ),
			'forbidden character'   => array( 'dQw4w9WgX!Q', '' ),
			'full URL is not an id' => array( 'https://youtu.be/dQw4w9WgXcQ', '' ),
			'empty'                 => array( '', '' ),
		);
	}

	/**
	 * Only a bare ID is stored.
	 *
	 * @dataProvider youtube_ids
	 *
	 * @param string $value    Typed value.
	 * @param string $expected Stored value.
	 */
	public function test_youtube_id_is_eleven_characters_of_the_youtube_alphabet( string $value, string $expected ): void {
		$this->assertSame( $expected, pw_youtube_id( $value ) );
	}

	/**
	 * Typed map addresses and what gets stored.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function embed_urls(): array {
		$embed = 'https://www.google.com/maps/embed?pb=!1m18!1m12';
		return array(
			'Google Maps embed'       => array( $embed, $embed ),
			'surrounding spaces'      => array( " $embed ", $embed ),
			'http'                    => array( 'http://www.google.com/maps/embed?pb=!1m18', '' ),
			'share link, not embed'   => array( 'https://www.google.com/maps/place/Chorz%C3%B3w', '' ),
			'other host'              => array( 'https://evil.example/maps/embed?pb=1', '' ),
			'lookalike host'          => array( 'https://www.google.com.evil.example/maps/embed?pb=1', '' ),
			'no query'                => array( 'https://www.google.com/maps/embed', '' ),
			'javascript'              => array( 'javascript:alert(1)//https://www.google.com/maps/embed?', '' ),
			'iframe markup pasted in' => array( '<iframe src="' . $embed . '"></iframe>', '' ),
			'empty'                   => array( '', '' ),
		);
	}

	/**
	 * Anything but a Google Maps embed URL is rejected.
	 *
	 * @dataProvider embed_urls
	 *
	 * @param string $value    Typed value.
	 * @param string $expected Stored value.
	 */
	public function test_map_embed_accepts_only_google_maps_embed_urls( string $value, string $expected ): void {
		$this->assertSame( $expected, pw_lokalizacja_mapa_embed( $value ) );
	}
}
