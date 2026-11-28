<?php
/**
 * Bootstrap of the integration suite: WP test suite with the theme and the pinned plugins (#37).
 *
 * Expects the theme in wp-content/themes/pw-theme and the Composer plugins (.wp/plugins) in
 * wp-content/plugins – `npm run test:php` mounts them there.
 *
 * @package PW
 */

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__, 3 ) . '/vendor/yoast/phpunit-polyfills' );

if ( ! defined( 'WP_TESTS_CONFIG_FILE_PATH' ) && is_file( '/internal/shared/auto_prepend_file.php' ) ) {
	// WordPress Playground. Elsewhere (wp-env, #38) wp-phpunit reads WP_PHPUNIT__TESTS_CONFIG.
	define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config-playground.php' );
}

require_once dirname( __DIR__, 3 ) . '/vendor/autoload.php';

$pw_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( false === $pw_tests_dir || '' === $pw_tests_dir ) {
	$pw_tests_dir = (string) getenv( 'WP_PHPUNIT__DIR' );
}

require_once $pw_tests_dir . '/includes/functions.php';

/**
 * Plugins every integration test runs with (ADR 0008: Yoast, CookieYes).
 */
const PW_TESTS_PLUGINS = array(
	'wordpress-seo/wp-seo.php',
	'cookie-law-info/cookie-law-info.php',
);

tests_add_filter(
	'pre_option_active_plugins',
	static function (): array {
		return PW_TESTS_PLUGINS;
	}
);

tests_add_filter(
	'init',
	static function (): void {
		// Fixture block (#36), never shipped in the theme.
		register_block_type( dirname( __DIR__, 2 ) . '/fixtures/blocks/pw-test' );
	}
);

tests_add_filter(
	'setup_theme',
	static function (): void {
		if ( 'pw-theme' !== get_stylesheet() ) {
			switch_theme( 'pw-theme' );
		}
	}
);

require $pw_tests_dir . '/includes/bootstrap.php';

unset( $pw_tests_dir );

// Playground defines WP_DEBUG_DISPLAY=false and logs into its in-memory FS: show errors instead.
ini_set( 'display_errors', '1' ); // phpcs:ignore WordPress.PHP.IniSet.display_errors_Disallowed
ini_set( 'log_errors', '0' ); // phpcs:ignore WordPress.PHP.IniSet.log_errors_Disallowed

require_once __DIR__ . '/BlockTestCase.php';
