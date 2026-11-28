<?php
/**
 * WP-CLI commands (`wp pw …`). Loaded by bootstrap.php only when WP_CLI is defined.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/migrate.php';
require_once __DIR__ . '/class-seed-command.php';

WP_CLI::add_command( 'pw seed', \PW\Cli\Seed_Command::class );
