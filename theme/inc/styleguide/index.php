<?php
/**
 * Loads theme/inc/styleguide/ files (ADR 0013). Add a require_once per new file.
 *
 * Partials (partials/*.php, one per group) are not listed: every file not starting with `_`
 * is loaded by PW\Styleguide\load_partials() on `wp_loaded`, only while the styleguide is
 * enabled, so a new block adds one file and edits nothing shared. On production none of them
 * is loaded.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/api.php';
require_once __DIR__ . '/chrome.php';
require_once __DIR__ . '/fixtures.php';
require_once __DIR__ . '/route.php';
