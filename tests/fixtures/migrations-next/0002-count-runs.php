<?php
/**
 * Fixture migration "added in a later release" (#37): counts its runs like 0001-count-runs.
 *
 * @package PW
 */

return static function (): void {
	$id          = basename( __FILE__, '.php' );
	$runs        = (array) get_option( 'pw_test_migration_runs', array() );
	$runs[ $id ] = (int) ( $runs[ $id ] ?? 0 ) + 1;
	update_option( 'pw_test_migration_runs', $runs );
};
