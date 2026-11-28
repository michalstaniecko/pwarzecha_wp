<?php
/**
 * `Service` node of the pages 1:1 and B2B (#53, docs/design/decyzje-ds-i-layoutow.md §5).
 *
 * @package PW
 */

namespace PW\Tests\Integration\Schema;

/**
 * Service with `areaServed`, provided by the Organization, without Offer or price.
 *
 * @group yoast
 */
class ServicePieceTest extends SchemaTestCase {

	public function test_service_page_has_a_service_served_in_the_default_cities(): void {
		$this->configure_organization();
		$page = $this->page( 'Treningi dla firm', array( 'pw_schema_typ' => 'service' ) );

		$graph    = $this->graph( get_permalink( $page ) );
		$services = self::nodes( $graph, 'Service' );

		$this->assertCount( 1, $services );
		$service = $services[0];
		$this->assertSame( get_permalink( $page ) . '#/schema/service', $service['@id'] );
		$this->assertSame( 'Treningi dla firm', $service['name'] );
		$this->assertSame( 'Treningi dla firm', $service['serviceType'] );
		$this->assertSame( get_permalink( $page ), $service['url'] );
		$this->assertSame( array( '@id' => home_url( '/#organization' ) ), $service['provider'] );
		$this->assertSame(
			array(
				array(
					'@type' => 'City',
					'name'  => 'Chorzów',
				),
				array(
					'@type' => 'City',
					'name'  => 'Katowice',
				),
			),
			$service['areaServed']
		);
		$this->assertArrayNotHasKey( 'offers', $service );
		$this->assertNotContains( 'Offer', self::types( $graph ) );
	}

	public function test_area_served_comes_from_the_page_meta(): void {
		$page = $this->page(
			'Treningi 1:1',
			array(
				'pw_schema_typ'  => 'service',
				'pw_area_served' => array( ' Bytom ', '', 'Bytom', 'Siemianowice Śląskie' ),
			)
		);

		$services = self::nodes( $this->graph( get_permalink( $page ) ), 'Service' );

		$this->assertSame( array( 'Bytom', 'Siemianowice Śląskie' ), array_column( $services[0]['areaServed'], 'name' ) );
		$this->assertArrayNotHasKey( 'provider', $services[0], 'no Organization, no dangling @id' );
	}

	public function test_page_with_a_location_and_service_has_both_nodes(): void {
		$chorzow = $this->lokalizacja( 'Chorzów' );
		$page    = $this->page(
			'Treningi 1:1',
			array(
				'pw_schema_typ'  => 'service',
				'pw_lokalizacja' => array( $chorzow ),
			)
		);

		$graph = $this->graph( get_permalink( $page ) );

		$this->assertCount( 1, self::nodes( $graph, 'Service' ) );
		$this->assertCount( 1, self::nodes( $graph, 'SportsActivityLocation' ) );
	}

	public function test_service_name_is_the_plain_title(): void {
		$page = $this->page( 'Treningi "1:1" -- indywidualne', array( 'pw_schema_typ' => 'service' ) );

		$services = self::nodes( $this->graph( get_permalink( $page ) ), 'Service' );

		$this->assertSame( 'Treningi "1:1" -- indywidualne', $services[0]['name'], 'no texturized entities' );
	}

	public function test_other_pages_have_no_service(): void {
		$page = $this->page( 'Cennik' );

		$this->assertSame( array(), self::nodes( $this->graph( get_permalink( $page ) ), 'Service' ) );
	}
}
