<?php
/**
 * Loads theme/inc/styleguide/ files (ADR 0013). Add a require_once per new file.
 *
 * Partials (partials/*.php, one per group) are not listed: every file not starting with `_`
 * is loaded while the styleguide is enabled, so a new block adds one file and edits nothing
 * shared. On production none of them is loaded.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/api.php';
require_once __DIR__ . '/chrome.php';
require_once __DIR__ . '/fixtures.php';
require_once __DIR__ . '/route.php';

if ( pw_styleguide_enabled() ) {
	foreach ( (array) glob( __DIR__ . '/partials/[!_]*.php' ) as $pw_sg_partial ) {
		require_once $pw_sg_partial;
	}
	unset( $pw_sg_partial );
}
