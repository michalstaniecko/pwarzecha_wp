<?php
/**
 * Loads every theme/inc/helpers/*.php file (ADR 0013), alphabetically, so a new helper file
 * (`helpers/<block>.php`) needs no edit here. Helper files only declare functions, constants
 * and hooks – none may depend on another being loaded first at include time.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

foreach ( (array) glob( __DIR__ . '/*.php' ) as $pw_helper_file ) {
	if ( __FILE__ !== $pw_helper_file ) {
		require_once $pw_helper_file;
	}
}
unset( $pw_helper_file );
