<?php
/**
 * Theme migrations: runs pending ones on theme activation and after a Version change (#36).
 *
 * @package PW
 */

namespace PW\Migrations;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/runner.php';

add_action(
	'after_switch_theme',
	static function (): void {
		run();
	}
);
add_action( 'admin_init', __NAMESPACE__ . '\maybe_run' );
