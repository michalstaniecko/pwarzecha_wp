<?php
/**
 * `Person` node of the Trener page (#53, docs/design/decyzje-ds-i-layoutow.md §5).
 *
 * @package PW
 */

namespace PW\Tests\Integration\Schema;

/**
 * Trener without a surname unless it is switched on [ZL].
 *
 * @group yoast
 */
class TrenerPieceTest extends SchemaTestCase {

	public function test_trener_page_has_a_person_with_the_first_name_only(): void {
		$trener = $this->page( 'Trener', array( 'pw_schema_typ' => 'trener' ) );

		$graph   = $this->graph( get_permalink( $trener ) );
		$persons = self::nodes( $graph, 'Person' );

		$this->assertCount( 1, $persons );
		$this->assertSame( home_url( '/#/schema/trener' ), $persons[0]['@id'] );
		$this->assertSame( 'Przemek', $persons[0]['name'] );
		$this->assertSame( get_permalink( $trener ), $persons[0]['url'] );
		$this->assertSame( array(), self::nodes( $graph, 'SportsActivityLocation' ) );
	}

	public function test_surname_is_added_only_through_the_filter(): void {
		$trener = $this->page( 'Trener', array( 'pw_schema_typ' => 'trener' ) );
		add_filter( 'pw/schema/trener_nazwisko', static fn() => 'Kowalski' );

		$persons = self::nodes( $this->graph( get_permalink( $trener ) ), 'Person' );

		$this->assertSame( 'Przemek Kowalski', $persons[0]['name'] );
	}

	public function test_other_pages_have_no_trener(): void {
		$page = $this->page( 'Cennik' );

		$this->assertSame( array(), self::nodes( $this->graph( get_permalink( $page ) ), 'Person' ) );
	}
}
