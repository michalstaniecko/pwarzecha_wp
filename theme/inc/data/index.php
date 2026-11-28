<?php
/**
 * Loads theme/inc/data/ files (ADR 0013). Add a require_once per new file.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/kontakt-format.php';
require_once __DIR__ . '/kontakt.php';
require_once __DIR__ . '/admin/kontakt-page.php';
