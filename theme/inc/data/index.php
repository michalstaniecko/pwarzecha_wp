<?php
/**
 * Loads theme/inc/data/ files (ADR 0013). Add a require_once per new file.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-private-posts-controller.php';
require_once __DIR__ . '/lokalizacja-helpers.php';
require_once __DIR__ . '/lokalizacja.php';
require_once __DIR__ . '/lokalizacja-repository.php';
require_once __DIR__ . '/admin-media-field.php';
require_once __DIR__ . '/lokalizacja-metabox.php';
