<?php
/**
 * `wp pw migrate [--list]` (#36).
 *
 * @package PW
 */

namespace PW\Cli;

use PW\Migrations;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Runs pending theme migrations.
 *
 * ## OPTIONS
 *
 * [--list]
 * : List migrations with their status instead of running them.
 *
 * ## EXAMPLES
 *
 *     wp pw migrate
 *     wp pw migrate --list
 *
 * @param array<int, string>         $args       Positional arguments (unused).
 * @param array<string, string|bool> $assoc_args Flags.
 */
function migrate( array $args, array $assoc_args ): void {
	unset( $args );

	if ( ! empty( $assoc_args['list'] ) ) {
		$done = Migrations\done();
		$rows = array();
		foreach ( array_keys( Migrations\available() ) as $id ) {
			$rows[] = array(
				'id'     => $id,
				'status' => in_array( $id, $done, true ) ? 'done' : 'pending',
			);
		}
		WP_CLI\Utils\format_items( 'table', $rows, array( 'id', 'status' ) );
		return;
	}

	$result = Migrations\run();
	foreach ( $result['ran'] as $id ) {
		WP_CLI::log( "Ran {$id}." );
	}
	if ( null !== $result['failed'] ) {
		WP_CLI::error( "Migration {$result['failed']} failed: {$result['error']}" );
	}
	WP_CLI::success( sprintf( '%d migration(s) run.', count( $result['ran'] ) ) );
}

WP_CLI::add_command( 'pw migrate', __NAMESPACE__ . '\migrate' );
