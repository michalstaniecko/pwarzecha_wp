<?php
/**
 * `SportsActivityLocation` nodes in the Yoast graph (#53, docs/design/decyzje-ds-i-layoutow.md §5).
 *
 * @package PW
 */

namespace PW\Tests\Integration\Schema;

/**
 * Home page and Kontakt list every Lokalizacja, a Strona oferty only its own.
 *
 * @group yoast
 */
class LocationPieceTest extends SchemaTestCase {

	public function test_home_page_lists_every_lokalizacja(): void {
		$this->configure_organization();
		$this->lokalizacja(
			'Chorzów',
			array(
				'pw_ulica'   => 'ul. Wolności 1',
				'pw_kod'     => '41-500',
				'pw_geo_lat' => '50.29',
				'pw_geo_lng' => '18.95',
			)
		);
		$this->lokalizacja( 'Katowice' );
		add_filter(
			'pw/data/kontakt',
			static fn() =>          array(
				'telefon' => '+48 600 000 000',
				'email'   => 'kontakt@example.pl',
			)
		);
		$this->set_front_page( $this->page( 'Strona główna' ) );

		$graph     = $this->graph( home_url( '/' ) );
		$locations = self::nodes( $graph, 'SportsActivityLocation' );

		$this->assertCount( 2, $locations );
		$chorzow = $locations[0];
		$this->assertSame( home_url( '/#/schema/lokalizacja/chorzow' ), $chorzow['@id'] );
		$this->assertSame( 'Chorzów', $chorzow['name'] );
		$this->assertSame( 'PostalAddress', $chorzow['address']['@type'] );
		$this->assertSame( 'ul. Wolności 1', $chorzow['address']['streetAddress'] );
		$this->assertSame(
			array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => 50.29,
				'longitude' => 18.95,
			),
			$chorzow['geo']
		);
		$this->assertSame( '+48 600 000 000', $chorzow['telephone'] );
		$this->assertSame( 'kontakt@example.pl', $chorzow['email'] );
		$this->assertSame( array( '@id' => home_url( '/#organization' ) ), $chorzow['parentOrganization'] );
		$this->assertNotEmpty( self::nodes( $graph, 'Organization' ), 'the referenced Organization is in the graph' );
		$this->assertArrayNotHasKey( 'geo', $locations[1], 'no coordinates, no geo' );
		$this->assertNotContains( 'Offer', self::types( $graph ) );
	}
}
