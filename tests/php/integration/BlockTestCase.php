<?php
/**
 * Base class for block tests: render a block from markup and check its HTML (#37, ADR 0005).
 *
 * @package PW
 */

namespace PW\Tests;

use Dom\HTMLDocument;

/**
 * Hooks added in a test (also through with_data()) are removed after it by WP_UnitTestCase.
 */
abstract class BlockTestCase extends \WP_UnitTestCase {

	/**
	 * Payload for escaping tests: breaks out of an attribute and injects a script and a handler.
	 */
	public const XSS = '"\'><script>alert(1)</script><b onclick="alert(1)">x</b>';

	/**
	 * Renders a block the way the front end does: block markup through do_blocks().
	 *
	 * @param string               $name  Block name, e.g. `pw/hero`.
	 * @param array<string, mixed> $attrs Block attributes.
	 * @param string               $inner Inner HTML, may contain serialized inner blocks.
	 */
	protected function render_block_markup( string $name, array $attrs = array(), string $inner = '' ): string {
		return do_blocks(
			serialize_block(
				array(
					'blockName'    => $name,
					'attrs'        => $attrs,
					'innerBlocks'  => array(),
					'innerHTML'    => $inner,
					'innerContent' => '' === $inner ? array() : array( $inner ),
				)
			)
		);
	}

	/**
	 * Asserts that at least one element in the HTML matches a CSS selector.
	 *
	 * @param string $html     HTML fragment.
	 * @param string $selector CSS selector (Dom\HTMLDocument::querySelector).
	 * @param string $message  Failure message.
	 */
	public function assertHtmlHasSelector( string $html, string $selector, string $message = '' ): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- PHPUnit assertion naming.
		$this->assertNotNull(
			self::parse( $html )->querySelector( $selector ),
			'' !== $message ? $message : sprintf( "No element matches `%s` in:\n%s", $selector, $html )
		);
	}

	/**
	 * Asserts the HTML has no injected script and no inline event handler (e.g. after rendering
	 * with self::XSS in attributes or data).
	 *
	 * @param string $html HTML fragment.
	 */
	public function assertNoUnescaped( string $html ): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- PHPUnit assertion naming.
		$document = self::parse( $html );

		$this->assertSame( 0, $document->querySelectorAll( 'script' )->length, "Unescaped <script> in:\n" . $html );
		foreach ( $document->querySelectorAll( '*' ) as $element ) {
			foreach ( $element->attributes as $attribute ) {
				$this->assertStringStartsNotWith(
					'on',
					strtolower( $attribute->name ),
					sprintf( "Unescaped event handler `%s` in:\n%s", $attribute->name, $html )
				);
			}
		}
	}

	/**
	 * Replaces global data (`pw/data/<key>` filter, ADR 0007) for the current test.
	 *
	 * @param string               $key  Data set, e.g. `kontakt`.
	 * @param array<string, mixed> $data Data returned instead of the stored one.
	 */
	protected function with_data( string $key, array $data ): void {
		add_filter( 'pw/data/' . $key, static fn() => $data );
	}

	/**
	 * Parses an HTML fragment with the HTML5 parser of PHP 8.4.
	 *
	 * @param string $html HTML fragment.
	 */
	private static function parse( string $html ): HTMLDocument {
		return HTMLDocument::createFromString( '<!DOCTYPE html><body>' . $html, LIBXML_NOERROR );
	}
}
