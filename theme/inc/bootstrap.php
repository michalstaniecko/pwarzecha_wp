<?php
/**
 * Loads the theme modules in a fixed order (#36, ADR 0013).
 *
 * No Composer autoloader at runtime: the theme has no production dependencies and its ZIP
 * ships without vendor/. Each inc/<dir>/index.php requires its own files.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/helpers/index.php';
require_once __DIR__ . '/data/index.php';
require_once __DIR__ . '/schema/index.php';
require_once __DIR__ . '/editor/index.php';
require_once __DIR__ . '/migrations/index.php';
require_once __DIR__ . '/styleguide/index.php';
require_once __DIR__ . '/blocks.php';
require_once __DIR__ . '/styles.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/cli/index.php';
}
