<?php
/**
 * WP test suite config for WordPress Playground CLI (#37, ADR 0011).
 *
 * Playground installs a fresh WordPress (SQLite, prefix `wp_`) at /wordpress for every
 * `wp-playground-cli php` run and loads the SQLite driver plus WP_DEBUG, WP_HOME and WP_SITEURL
 * from its own auto_prepend file. The suite reuses that install: its own install.php runs in a
 * subprocess, and Playground subprocesses see neither the host mounts nor return exit codes.
 *
 * @package PW
 */

putenv( 'WP_TESTS_SKIP_INSTALL=1' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- read by the WP test bootstrap.

define( 'ABSPATH', '/wordpress/' );
define( 'WP_ENVIRONMENT_TYPE', 'local' );

define( 'DB_NAME', 'wordpress' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wp_'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'PW Tests' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
