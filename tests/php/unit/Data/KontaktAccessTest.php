<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * Only inc/data reads the `pw_kontakt` option; blocks use pw_kontakt() or `pw/kontakt` (#48, ADR 0007).
 *
 * @package PW
 */

namespace PW\Tests\Unit\Data;

use PW\Tests\Unit\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Scans theme/ PHP files for direct option reads.
 */
final class KontaktAccessTest extends TestCase {

	public function test_no_theme_file_outside_data_reads_the_option(): void {
		$theme     = self::theme_path( '' );
		$offenders = array();
		foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $theme, RecursiveDirectoryIterator::SKIP_DOTS ) ) as $file ) {
			$path = $file->getPathname();
			if ( 'php' !== $file->getExtension() || str_starts_with( $path, $theme . 'inc/data/' ) ) {
				continue;
			}
			if ( preg_match( '/get_option\(\s*[\'"]pw_kontakt[\'"]/', (string) file_get_contents( $path ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
				$offenders[] = substr( $path, strlen( $theme ) );
			}
		}

		$this->assertSame( array(), $offenders, 'Use pw_kontakt() / pw_kontakt_pole() instead of get_option( \'pw_kontakt\' ).' );
	}
}
