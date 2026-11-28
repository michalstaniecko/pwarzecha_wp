<?php
/**
 * Registry `pw/schema/pieces` (#53): a node is added without changing the module.
 *
 * @package PW
 */

namespace PW\Tests\Integration\Schema;

/**
 * Extra pieces through the filter, as #67 adds the FAQPage.
 *
 * @group yoast
 */
class RegistryTest extends SchemaTestCase {

	public function test_a_piece_added_through_the_filter_lands_in_the_graph(): void {
		add_filter(
			'pw/schema/pieces',
			static fn( array $pieces ): array => array_merge(
				$pieces,
				array(
					new class() extends \PW\Schema\Piece {
						/**
						 * Identifier.
						 *
						 * @var string
						 */
						public $identifier = 'pw-test';

						public function is_needed(): bool {
							return $this->page_id() > 0;
						}

						public function generate(): array {
							return array(
								'@type' => 'FAQPage',
								'@id'   => home_url( '/#/schema/test' ),
							);
						}
					},
					'not a piece',
				)
			)
		);
		$page = $this->page( 'FAQ' );

		$graph = $this->graph( get_permalink( $page ) );

		$this->assertSame( array( home_url( '/#/schema/test' ) ), array_column( self::nodes( $graph, 'FAQPage' ), '@id' ) );
	}

	public function test_a_default_piece_can_be_removed_through_the_filter(): void {
		$this->lokalizacja( 'Chorzów' );
		$kontakt = $this->page( 'Kontakt', array( 'pw_schema_typ' => 'kontakt' ) );
		add_filter(
			'pw/schema/pieces',
			static fn( array $pieces ): array => array_filter( $pieces, static fn( $piece ): bool => ! $piece instanceof \PW\Schema\Location_Piece )
		);

		$this->assertSame( array(), self::nodes( $this->graph( get_permalink( $kontakt ) ), 'SportsActivityLocation' ) );
	}
}
