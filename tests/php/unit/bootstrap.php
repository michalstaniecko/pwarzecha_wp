<?php
/**
 * Bootstrap of the unit suite: no WordPress, WP functions come from Brain Monkey (#37).
 *
 * @package PW
 */

// Theme files start with `defined( 'ABSPATH' ) || exit;`.
define( 'ABSPATH', dirname( __DIR__, 3 ) . '/.cache/no-wordpress/' );

require_once dirname( __DIR__, 3 ) . '/vendor/autoload.php';
require_once __DIR__ . '/TestCase.php';
