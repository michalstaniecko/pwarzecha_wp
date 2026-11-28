<?php
/**
 * PHPUnit bootstrap: picks the bootstrap of the selected suite (#37).
 *
 * PHPUnit 9 has one bootstrap per configuration, and the two suites cannot share a process:
 * Brain Monkey defines WordPress functions that the WP test suite would load for real.
 *
 * @package PW
 */

$pw_argv  = $GLOBALS['argv'] ?? array();
$pw_suite = 'unit';
foreach ( $pw_argv as $pw_i => $pw_arg ) {
	if ( '--testsuite' === $pw_arg ) {
		$pw_suite = (string) ( $pw_argv[ $pw_i + 1 ] ?? '' );
	} elseif ( str_starts_with( $pw_arg, '--testsuite=' ) ) {
		$pw_suite = substr( $pw_arg, strlen( '--testsuite=' ) );
	}
}

if ( 'integration' === $pw_suite ) {
	require __DIR__ . '/integration/bootstrap.php';
} elseif ( 'unit' === $pw_suite ) {
	require __DIR__ . '/unit/bootstrap.php';
} else {
	fwrite( STDERR, "Run one suite at a time: --testsuite unit or --testsuite integration.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
	exit( 1 );
}
unset( $pw_argv, $pw_suite, $pw_i, $pw_arg );
