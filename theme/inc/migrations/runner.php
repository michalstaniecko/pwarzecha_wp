<?php
/**
 * Theme migrations runner (#36).
 *
 * A migration is a file `NNNN-name.php` in this directory that returns a callable. Its ID is the
 * file name without `.php`. Migrations must be idempotent. Executed IDs are stored in the
 * `pw_migrations` option; `pw_theme_version` stores the theme Version they were run for.
 *
 * This file only declares functions (no side effects), so it can be unit tested without WP.
 *
 * @package PW
 */

namespace PW\Migrations;

defined( 'ABSPATH' ) || exit;

const OPTION_DONE    = 'pw_migrations';
const OPTION_VERSION = 'pw_theme_version';

/**
 * Selects migrations still to run: available ones not yet executed, in ID order.
 *
 * @param array<string, string> $available Migration ID => file path.
 * @param array<int, string>    $done      IDs already executed.
 * @return array<string, string> Migration ID => file path, sorted by ID.
 */
function pending( array $available, array $done ): array {
	$pending = array_diff_key( $available, array_flip( $done ) );
	ksort( $pending, SORT_STRING );
	return $pending;
}

/**
 * Lists migration files in a directory.
 *
 * @param string $dir Directory to scan.
 * @return array<string, string> Migration ID => file path, sorted by ID.
 */
function available( string $dir = __DIR__ ): array {
	$files = glob( $dir . '/[0-9][0-9][0-9][0-9]-*.php' );
	$found = array();
	foreach ( false === $files ? array() : $files as $file ) {
		$found[ basename( $file, '.php' ) ] = $file;
	}
	ksort( $found, SORT_STRING );
	return $found;
}

/**
 * IDs of executed migrations.
 *
 * @return array<int, string>
 */
function done(): array {
	$done = get_option( OPTION_DONE, array() );
	return is_array( $done ) ? array_values( array_map( 'strval', $done ) ) : array();
}

/**
 * Version of the theme (from style.css), regardless of an active child theme.
 */
function current_version(): string {
	return (string) wp_get_theme( get_template() )->get( 'Version' );
}

/**
 * Runs pending migrations in order. Stops at the first failure so later migrations never run on
 * top of a broken one; `pw_theme_version` is updated only when everything succeeded.
 *
 * @return array{ran: array<int, string>, failed: string|null, error: string|null}
 */
function run(): array {
	$done   = done();
	$result = array(
		'ran'    => array(),
		'failed' => null,
		'error'  => null,
	);

	foreach ( pending( available(), $done ) as $id => $file ) {
		$error = null;
		try {
			$migration = require $file;
			if ( is_callable( $migration ) ) {
				$migration();
			} else {
				$error = 'Migration file does not return a callable.';
			}
		} catch ( \Throwable $e ) {
			$error = $e->getMessage();
		}
		if ( null !== $error ) {
			$result['failed'] = $id;
			$result['error']  = $error;
			return $result;
		}

		$done[] = $id;
		update_option( OPTION_DONE, $done, false );
		$result['ran'][] = $id;
	}

	update_option( OPTION_VERSION, current_version() );
	return $result;
}

/**
 * Runs migrations when the theme Version differs from the one they were last run for.
 */
function maybe_run(): void {
	if ( get_option( OPTION_VERSION ) !== current_version() ) {
		run();
	}
}
