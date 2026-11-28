<?php
/**
 * Canary of `@group mariadb` (#37, ADR 0011).
 *
 * @package PW
 */

namespace PW\Tests;

/**
 * Fails on SQLite, so it proves both that `npm run test:php` (Playground) skips the group and
 * that `npm run test:php:mariadb` (#38) really runs on MariaDB.
 *
 * @group mariadb
 */
final class DatabaseTest extends \WP_UnitTestCase {

	public function test_mariadb_group_runs_on_mariadb(): void {
		global $wpdb;

		$this->assertStringContainsStringIgnoringCase( 'mariadb', (string) $wpdb->db_server_info() );
	}
}
