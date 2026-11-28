<?php
/**
 * Pure schema node builders: `@id` and `PostalAddress` of a Lokalizacja (#53).
 *
 * @package PW
 */

namespace PW\Tests\Unit\Schema;

use Brain\Monkey\Functions;
use PW\Tests\Unit\TestCase;

/**
 * Functions from theme/inc/schema/nodes.php.
 */
final class NodesTest extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		require_once self::theme_path( 'inc/schema/nodes.php' );
	}

	protected function set_up(): void {
		parent::set_up();
		Functions\when( 'home_url' )->alias( static fn( $path = '' ) => 'https://example.pl' . $path );
	}

	public function test_location_id_is_built_from_the_anchor(): void {
		$this->assertSame(
			'https://example.pl/#/schema/lokalizacja/chorzow',
			\PW\Schema\location_id( array( 'kotwica' => 'chorzow' ) )
		);
	}

	public function test_postal_address_maps_the_lokalizacja_fields(): void {
		$this->assertSame(
			array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => 'ul. Wolności 1',
				'postalCode'      => '41-500',
				'addressLocality' => 'Chorzów',
				'addressCountry'  => 'PL',
			),
			\PW\Schema\postal_address(
				array(
					'ulica'  => 'ul. Wolności 1',
					'kod'    => '41-500',
					'miasto' => 'Chorzów',
				)
			)
		);
	}

	public function test_postal_address_skips_empty_fields(): void {
		$this->assertSame(
			array(
				'@type'           => 'PostalAddress',
				'addressLocality' => 'Katowice',
				'addressCountry'  => 'PL',
			),
			\PW\Schema\postal_address(
				array(
					'ulica'  => ' ',
					'kod'    => '',
					'miasto' => 'Katowice',
				)
			)
		);
	}
}
