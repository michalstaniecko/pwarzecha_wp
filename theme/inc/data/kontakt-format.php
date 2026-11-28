<?php
/**
 * „Dane strony” formatting and validation helpers (#48): pure functions, no hooks.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

/**
 * NIP weights of digits 1–9; the weighted sum mod 11 is the 10th (control) digit.
 */
const PW_NIP_WEIGHTS = array( 6, 5, 7, 2, 3, 4, 5, 6, 7 );

/**
 * NIP for display: `123-456-78-90`.
 *
 * @param string $nip NIP, with or without separators.
 * @return string Grouped NIP; digits only when there are not exactly 10 of them.
 */
function pw_nip_format( string $nip ): string {
	$digits = (string) preg_replace( '/\D/', '', $nip );
	if ( 10 !== strlen( $digits ) ) {
		return $digits;
	}
	return substr( $digits, 0, 3 ) . '-' . substr( $digits, 3, 3 ) . '-' . substr( $digits, 6, 2 ) . '-' . substr( $digits, 8, 2 );
}

/**
 * NIP as stored: 10 digits without separators, checksum verified.
 *
 * @param string $nip NIP as typed (spaces, dashes, `PL` prefix allowed).
 * @return string 10 digits, '' when the NIP is invalid.
 */
function pw_nip_normalize( string $nip ): string {
	$digits = (string) preg_replace( '/^PL|[\s.-]/i', '', trim( $nip ) );
	if ( ! preg_match( '/^\d{10}$/', $digits ) ) {
		return '';
	}
	$sum = 0;
	foreach ( PW_NIP_WEIGHTS as $i => $weight ) {
		$sum += $weight * (int) $digits[ $i ];
	}
	$control = $sum % 11;
	return 10 !== $control && (int) $digits[9] === $control ? $digits : '';
}

/**
 * Phone number as stored and displayed: `+48 600 000 000`.
 *
 * Accepts digits, spaces, `+`, dashes, dots and brackets. A 9-digit number (with or without
 * `+48` / `0048`) is grouped 3-3-3 after `+48`; any other number keeps its own grouping.
 *
 * @param string $phone Number as typed.
 * @return string Formatted number, '' when it has other characters or fewer than 9 digits.
 */
function pw_phone_format( string $phone ): string {
	$phone = trim( $phone );
	if ( ! preg_match( '/^\+?[\d\s().-]+$/', $phone ) || preg_match_all( '/\d/', $phone ) < 9 ) {
		return '';
	}
	$number = (string) preg_replace( '/[^\d+]/', '', $phone );
	if ( str_starts_with( $number, '00' ) ) {
		$number = '+' . substr( $number, 2 );
	}
	if ( str_starts_with( $number, '+48' ) ) {
		$number = substr( $number, 3 );
	}
	if ( preg_match( '/^\d{9}$/', $number ) ) {
		return '+48 ' . implode( ' ', str_split( $number, 3 ) );
	}
	$spaced = trim( (string) preg_replace( '/[\s().-]+/', ' ', $phone ) );
	return str_starts_with( $spaced, '00' ) ? '+' . substr( $spaced, 2 ) : $spaced;
}

/**
 * Short e-mail for the topbar: the part before `@` followed by `@…`.
 *
 * @param string $email Address.
 * @return string `kontakt@…`; the input unchanged when it has no `@`.
 */
function pw_email_short( string $email ): string {
	$at = strpos( $email, '@' );
	return false === $at ? $email : substr( $email, 0, $at ) . '@…';
}
