<?php
/**
 * `Person` node of the Trener (#53, docs/design/decyzje-ds-i-layoutow.md §5): pages with
 * `pw_schema_typ` = `trener`.
 *
 * The name comes from „Dane strony” (`pw_kontakt()`): `name` = `trenerImie`, plus
 * `trenerNazwisko` only when `trenerNazwiskoWSchemie` is on (the surname needs the Trener's
 * consent [ZL]). No first name = no Person node.
 *
 * Filters (overrides on top of the stored values, both strings):
 * - `pw/schema/trener_imie` ( $imie ) gets `trenerImie`; '' = no Person node.
 * - `pw/schema/trener_nazwisko` ( $nazwisko ) gets the surname the switch allows: `trenerNazwisko`
 *   when the switch is on, '' otherwise; '' = first name only. A non-empty value is emitted even
 *   with the switch off, so a filter that sets it takes over the consent check.
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
	 * Needed on the Trener page when the Trener has a first name.
	 */
	public function is_needed(): bool {
		return TYP_TRENER === $this->typ() && '' !== $this->name()['imie'];
	}

	/**
	 * The Person node.
	 *
	 * @return array<string, mixed>
	 */
	public function generate(): array {
		$name = $this->name();
		return person_node( $name['imie'], $name['nazwisko'], (string) get_permalink( $this->page_id() ) );
	}

	/**
	 * First name and surname for the schema, after the filters.
	 *
	 * @return array{imie: string, nazwisko: string}
	 */
	private function name(): array {
		$kontakt = pw_kontakt();

		/**
		 * First name of the Trener in the schema; '' = no Person node.
		 *
		 * @param string $imie `trenerImie` from „Dane strony”.
		 */
		$imie = trim( (string) apply_filters( 'pw/schema/trener_imie', $kontakt['trenerImie'] ) );

		/**
		 * Surname of the Trener in the schema; '' = first name only. Set it only with the
		 * Trener's consent [ZL].
		 *
		 * @param string $nazwisko `trenerNazwisko` when „Pokaż nazwisko Trenera w schemie” is on, '' otherwise.
		 */
		$nazwisko = (string) apply_filters( 'pw/schema/trener_nazwisko', '1' === $kontakt['trenerNazwiskoWSchemie'] ? $kontakt['trenerNazwisko'] : '' );

		return array(
			'imie'     => $imie,
			'nazwisko' => $nazwisko,
		);
	}
}
