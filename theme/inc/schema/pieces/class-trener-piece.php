<?php
/**
 * `Person` node of the Trener (#53, docs/design/decyzje-ds-i-layoutow.md §5): pages with
 * `pw_schema_typ` = `trener`.
 *
 * `name` is the first name only. The surname needs the Trener's consent [ZL], so it is off by
 * default and added only through `pw/schema/trener_nazwisko` (string, '' = no surname).
 *
 * @package PW
 */

namespace PW\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * The Trener as a Person.
 */
class Trener_Piece extends Piece {

	/**
	 * Identifier for the `wpseo_schema_needs_<id>` and `wpseo_schema_<id>` filters.
	 *
	 * @var string
	 */
	public $identifier = 'pw-trener';

	/**
	 * Needed on the Trener page.
	 */
	public function is_needed(): bool {
		return TYP_TRENER === $this->typ();
	}

	/**
	 * The Person node.
	 *
	 * @return array<string, mixed>
	 */
	public function generate(): array {
		/**
		 * First name of the Trener in the schema.
		 *
		 * @param string $imie First name.
		 */
		$imie = (string) apply_filters( 'pw/schema/trener_imie', TRENER_IMIE );

		/**
		 * Surname of the Trener in the schema; '' (default) = first name only. Set it only
		 * with the Trener's consent [ZL].
		 *
		 * @param string $nazwisko Surname.
		 */
		$nazwisko = (string) apply_filters( 'pw/schema/trener_nazwisko', '' );

		return person_node( $imie, $nazwisko, (string) get_permalink( $this->page_id() ) );
	}
}
