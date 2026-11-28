<?php
/**
 * Loads theme/inc/editor/ files (ADR 0013). Add a require_once per new file.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/allowed-blocks.php';
require_once __DIR__ . '/embeds.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/assets.php';
