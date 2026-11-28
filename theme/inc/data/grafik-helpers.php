<?php
/**
 * Grafik rows (#50): pure functions, no WordPress calls at load time.
 *
 * Row (meta `pw_grafik` of a Lokalizacja): `dzien` int 1–7 (1 = Monday), `od` / `do` "HH:MM",
 * `grupa` int (term ID of the `grupa` taxonomy).
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rows by day, then by start time; rows with the same day and time keep their entry order.
 *
 * @param array<int|string, array{dzien: int, od: string}> $rows Grafik rows.
 * @return list<array<string, mixed>>
 */
function pw_grafik_sort( array $rows ): array {
	$rows = array_values( $rows );
	// usort() is stable since PHP 8.0.
	usort(
		$rows,
		static fn( array $a, array $b ): int => array( (int) $a['dzien'], (string) $a['od'] ) <=> array( (int) $b['dzien'], (string) $b['od'] )
	);
	return $rows;
}

/**
 * Days of the week, 1 = Monday … 7 = Sunday (ISO-8601, `date( 'N' )`).
 *
 * @return array<int, string> Day number => Polish name.
 */
function pw_grafik_dni(): array {
	return array(
		1 => 'Poniedziałek',
		2 => 'Wtorek',
		3 => 'Środa',
		4 => 'Czwartek',
		5 => 'Piątek',
		6 => 'Sobota',
		7 => 'Niedziela',
	);
}

/**
 * Polish name of a day, '' outside 1–7.
 *
 * @param int $dzien Day number, 1 = Monday.
 */
function pw_grafik_dzien_nazwa( int $dzien ): string {
	return pw_grafik_dni()[ $dzien ] ?? '';
}

/**
 * Time "H:MM" or "HH:MM" (00:00–23:59) as "HH:MM", '' for anything else.
 *
 * @param mixed $value Typed time (a `time` input sends "HH:MM").
 */
function pw_grafik_godzina( $value ): string {
	if ( ! is_string( $value ) || 1 !== preg_match( '/^(\d{1,2}):(\d{2})$/', trim( $value ), $match ) ) {
		return '';
	}
	$hour   = (int) $match[1];
	$minute = (int) $match[2];
	return $hour <= 23 && $minute <= 59 ? sprintf( '%02d:%02d', $hour, $minute ) : '';
}

/**
 * Valid Grafik rows, sorted by `pw_grafik_sort()`, as a list of `{dzien, od, do, grupa}` with typed
 * values, so the stored meta (and the metabox after saving) is in the same order as the site.
 * Drops rows that are not arrays, have a day outside 1–7, a bad time, `od` not before `do`,
 * no Grupa or a Grupa that is not an existing `grupa` term. Other keys are discarded.
 *
 * @param array<int|string, mixed> $rows Raw rows (metabox form, REST, seed).
 * @return list<array{dzien: int, od: string, do: string, grupa: int}>
 */
function pw_grafik_sanitize( array $rows ): array {
	$whole = static fn( $value ): int => is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) ? (int) $value : 0;

	$clean = array();
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$dzien = $whole( $row['dzien'] ?? 0 );
		$od    = pw_grafik_godzina( $row['od'] ?? '' );
		$do    = pw_grafik_godzina( $row['do'] ?? '' );
		$grupa = $whole( $row['grupa'] ?? 0 );

		if ( '' === pw_grafik_dzien_nazwa( $dzien ) || '' === $od || '' === $do || $od >= $do ) {
			continue;
		}
		if ( $grupa <= 0 || ! term_exists( $grupa, 'grupa' ) ) {
			continue;
		}
		$clean[] = array(
			'dzien' => $dzien,
			'od'    => $od,
			'do'    => $do,
			'grupa' => $grupa,
		);
	}
	return pw_grafik_sort( $clean );
}
