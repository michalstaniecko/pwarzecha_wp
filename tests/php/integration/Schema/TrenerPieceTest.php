<?php
/**
 * `Person` node of the Trener page (#53, docs/design/decyzje-ds-i-layoutow.md §5).
 *
 * @package PW
 */

namespace PW\Tests\Integration\Schema;

/**
 * Name from „Dane strony”; surname only with the switch on [ZL].
 *
 * @group yoast
 */
class TrenerPieceTest extends SchemaTestCase {

	public function test_trener_page_has_a_person_with_the_first_name_only(): void {
		update_option(
			'pw_kontakt',
			array(
				'trenerImie'     => 'Jan',
				'trenerNazwisko' => 'Kowalski',
			)
		);
		$trener = $this->page( 'Trener', array( 'pw_schema_typ' => 'trener' ) );

		$graph   = $this->graph( get_permalink( $trener ) );
		$persons = self::nodes( $graph, 'Person' );

		$this->assertCount( 1, $persons );
		$this->assertSame( home_url( '/#/schema/trener' ), $persons[0]['@id'] );
		$this->assertSame( 'Jan', $persons[0]['name'] );
		$this->assertSame( get_permalink( $trener ), $persons[0]['url'] );
		$this->assertSame( array(), self::nodes( $graph, 'SportsActivityLocation' ) );
	}

	public function test_surname_is_added_when_switched_on(): void {
		update_option(
			'pw_kontakt',
			array(
				'trenerImie'             => 'Jan',
				'trenerNazwisko'         => 'Kowalski',
				'trenerNazwiskoWSchemie' => '1',
			)
		);
		$trener = $this->page( 'Trener', array( 'pw_schema_typ' => 'trener' ) );

		$persons = self::nodes( $this->graph( get_permalink( $trener ) ), 'Person' );

		$this->assertSame( 'Jan Kowalski', $persons[0]['name'] );
	}

	public function test_switch_without_a_surname_gives_the_first_name_only(): void {
		update_option(
			'pw_kontakt',
			array(
				'trenerImie'             => 'Jan',
				'trenerNazwiskoWSchemie' => '1',
			)
		);
		$trener = $this->page( 'Trener', array( 'pw_schema_typ' => 'trener' ) );

		$persons = self::nodes( $this->graph( get_permalink( $trener ) ), 'Person' );

		$this->assertSame( 'Jan', $persons[0]['name'] );
	}

	public function test_no_first_name_gives_no_person(): void {
		update_option(
			'pw_kontakt',
			array(
				'trenerNazwisko'         => 'Kowalski',
				'trenerNazwiskoWSchemie' => '1',
			)
		);
		$trener = $this->page( 'Trener', array( 'pw_schema_typ' => 'trener' ) );

		$this->assertSame( array(), self::nodes( $this->graph( get_permalink( $trener ) ), 'Person' ) );
	}

	public function test_filters_override_the_stored_name(): void {
		update_option(
			'pw_kontakt',
			array(
				'trenerImie'     => 'Jan',
				'trenerNazwisko' => 'Kowalski',
			)
		);
		$trener = $this->page( 'Trener', array( 'pw_schema_typ' => 'trener' ) );
		add_filter( 'pw/schema/trener_imie', static fn() => 'Piotr' );
		add_filter( 'pw/schema/trener_nazwisko', static fn() => 'Nowak' );

		$persons = self::nodes( $this->graph( get_permalink( $trener ) ), 'Person' );

		$this->assertSame( 'Piotr Nowak', $persons[0]['name'] );
	}

	public function test_empty_first_name_from_the_filter_gives_no_person(): void {
		update_option( 'pw_kontakt', array( 'trenerImie' => 'Jan' ) );
		$trener = $this->page( 'Trener', array( 'pw_schema_typ' => 'trener' ) );
		add_filter( 'pw/schema/trener_imie', '__return_empty_string' );

		$this->assertSame( array(), self::nodes( $this->graph( get_permalink( $trener ) ), 'Person' ) );
	}

	public function test_other_pages_have_no_trener(): void {
		$page = $this->page( 'Cennik' );

		$this->assertSame( array(), self::nodes( $this->graph( get_permalink( $page ) ), 'Person' ) );
	}
}
