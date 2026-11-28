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

	public function test_kontakt_page_lists_every_lokalizacja(): void {
		$this->lokalizacja( 'Chorzów' );
		$this->lokalizacja( 'Katowice' );
		$kontakt = $this->page( 'Kontakt', array( 'pw_schema_typ' => 'kontakt' ) );

		$graph = $this->graph( get_permalink( $kontakt ) );

		$this->assertSame(
			array( home_url( '/#/schema/lokalizacja/chorzow' ), home_url( '/#/schema/lokalizacja/katowice' ) ),
			array_column( self::nodes( $graph, 'SportsActivityLocation' ), '@id' )
		);
	}

	public function test_strona_oferty_lists_only_its_lokalizacja_with_its_url(): void {
		$this->configure_organization();
		$this->lokalizacja( 'Chorzów' );
		$katowice = $this->lokalizacja( 'Katowice' );
		$oferta   = $this->page( 'Samoobrona w Katowicach' );
		update_post_meta( $katowice, 'pw_strona_oferty', $oferta );
		update_post_meta( $oferta, 'pw_lokalizacja', array( $katowice ) );

		$graph     = $this->graph( get_permalink( $oferta ) );
		$locations = self::nodes( $graph, 'SportsActivityLocation' );

		$this->assertCount( 1, $locations );
		$this->assertSame( home_url( '/#/schema/lokalizacja/katowice' ), $locations[0]['@id'] );
		$this->assertSame( get_permalink( $oferta ), $locations[0]['url'] );
		$this->assertSame( array( '@id' => home_url( '/#organization' ) ), $locations[0]['parentOrganization'] );
		$this->assertNotContains( 'Offer', self::types( $graph ) );
	}

	public function test_grafik_has_only_yoast_nodes(): void {
		$this->configure_organization();
		$this->lokalizacja( 'Chorzów' );
		$grafik = $this->page( 'Grafik', array( 'pw_schema_typ' => 'brak' ) );

		$graph = $this->graph( get_permalink( $grafik ) );

		$this->assertNotEmpty( self::nodes( $graph, 'WebPage' ) );
		$this->assertSame( array(), array_diff( self::types( $graph ), array( 'WebPage', 'WebSite', 'Organization', 'BreadcrumbList', 'ImageObject', 'ListItem', 'SearchAction' ) ) );
	}

	public function test_without_organization_locations_have_no_parent(): void {
		$this->lokalizacja( 'Chorzów' );
		$kontakt = $this->page( 'Kontakt', array( 'pw_schema_typ' => 'kontakt' ) );

		$locations = self::nodes( $this->graph( get_permalink( $kontakt ) ), 'SportsActivityLocation' );

		$this->assertArrayNotHasKey( 'parentOrganization', $locations[0], 'no dangling @id' );
	}
}
