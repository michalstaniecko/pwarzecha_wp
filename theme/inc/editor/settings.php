<?php
/**
 * Editor features the Zleceniodawca does not need (#43): Openverse, Font Library (fonts come
 * from theme.json only) and patterns from wp.org / core (only the theme's Sekcje).
 *
 * @package PW
 */

namespace PW\Editor;

defined( 'ABSPATH' ) || exit;

/**
 * `block_editor_settings_all` callback.
 *
 * @param array<string, mixed> $settings Editor settings.
 * @return array<string, mixed>
 */
function editor_settings( array $settings ): array {
	$settings['enableOpenverseMediaCategory'] = false;
	$settings['fontLibraryEnabled']           = false;
	return $settings;
}

add_filter( 'block_editor_settings_all', __NAMESPACE__ . '\editor_settings' );
add_filter( 'should_load_remote_block_patterns', '__return_false' );
add_action(
	'after_setup_theme',
	static function (): void {
		remove_theme_support( 'core-block-patterns' );
	}
);
