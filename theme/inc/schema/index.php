<?php
/**
 * Loads theme/inc/schema/ files (ADR 0013). Add a require_once per new file.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/nodes.php';
