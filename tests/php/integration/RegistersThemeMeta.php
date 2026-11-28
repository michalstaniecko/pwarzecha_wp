<?php
/**
 * Re-registers the theme's meta in integration tests (ADR 0013).
 *
 * `WP_UnitTestCase::tear_down()` unregisters every meta key and `init` does not run again, so
 * without this `get_registered_meta_keys()` is empty and sanitizers are skipped from the second
 * test on. Call `$this->register_theme_meta()` in `set_up()` after `parent::set_up()`.
 *
 * @package PW
 */

namespace PW\Tests;

/**
 * Registers every post and term meta of the theme again.
 */
trait RegistersThemeMeta {

	/**
	 * Meta of Lokalizacje, Grafik and Grupy, pages (Lokalizacje, schema) and Karnety.
	 */
	protected function register_theme_meta(): void {
		\PW\Data\Lokalizacja\register_meta();
		\PW\Data\Grafik\register_meta();
		\PW\Data\PageMeta\register_meta();
		\PW\Schema\register_meta();
		pw_karnet_register_meta();
	}
}
