<?php
/**
 * Migration 0001: the Zleceniodawca role (#43).
 *
 * The Zleceniodawca account uses the `editor` role, extended with:
 * - `edit_theme_options`: menus and template parts in the Site Editor,
 * - `pw_manage_site_data`: the „Dane strony” screen (#48).
 * Lokalizacja and Karnet use standard post capabilities, which `editor` already has.
 * Never `switch_themes`, `activate_plugins`, `install_plugins`: the data model lives in the theme
 * (ADR 0007), so the Zleceniodawca must not be able to switch it off.
 * Administrators get `pw_manage_site_data` too.
 *
 * Idempotent: `add_cap` / `remove_cap` only write when something changes.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

return static function (): void {
	$editor = get_role( 'editor' );
	if ( null !== $editor ) {
		foreach ( array( 'edit_theme_options', 'pw_manage_site_data' ) as $cap ) {
			if ( ! $editor->has_cap( $cap ) ) {
				$editor->add_cap( $cap );
			}
		}
		foreach ( array( 'switch_themes', 'activate_plugins', 'install_plugins' ) as $cap ) {
			if ( array_key_exists( $cap, $editor->capabilities ) ) {
				$editor->remove_cap( $cap );
			}
		}
	}

	$administrator = get_role( 'administrator' );
	if ( null !== $administrator && ! $administrator->has_cap( 'pw_manage_site_data' ) ) {
		$administrator->add_cap( 'pw_manage_site_data' );
	}
};
