<?php
/**
 * Karnet prices (#51): pure functions, no WordPress calls at load time.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Price in whole złoty as text: „120 zł”, „1 200 zł”. Every space is non-breaking, so the
 * price never wraps between digits or before „zł”.
 *
 * @param int $zl Price in whole złoty.
 */
function pw_cena_format( int $zl ): string {
	$nbsp = "\u{00A0}";

	return number_format( $zl, 0, '', $nbsp ) . $nbsp . 'zł';
}

/**
 * Lowest Karnet price per month, for Teaser ceny („od X zł / mies.”). Each Karnet counts as
 * `floor( cena / okresMiesiace )` – rounded down to whole złoty (Q12). PassCard shows the
 * price as entered, not this value.
 *
 * @param array|null $karnety Karnety in the pw_karnety() shape; null = pw_karnety().
 * @phpstan-param list<array{cena: int, okresMiesiace: int}>|null $karnety
 * @return int|null Null when there are no Karnety.
 */
function pw_karnet_min_cena_miesieczna( ?array $karnety = null ): ?int {
	$karnety ??= pw_karnety();
	$min       = null;

	foreach ( $karnety as $karnet ) {
		$miesiecznie = intdiv( (int) $karnet['cena'], max( 1, (int) $karnet['okresMiesiace'] ) );
		$min         = null === $min ? $miesiecznie : min( $min, $miesiecznie );
	}

	return $min;
}
