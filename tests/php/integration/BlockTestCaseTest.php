<?php
/**
 * The block test helpers, exercised on the pw/test fixture block (#37).
 *
 * @package PW
 */

namespace PW\Tests;

use PHPUnit\Framework\AssertionFailedError;

/**
 * `PW\Tests\BlockTestCase`.
 */
final class BlockTestCaseTest extends BlockTestCase {

	public function test_renders_a_block_through_its_render_php(): void {
		$html = $this->render_block_markup( 'pw/test', array( 'message' => 'Cześć' ) );

		$this->assertHtmlHasSelector( $html, 'p.wp-block-pw-test[data-pw-test="1"]' );
		$this->assertStringContainsString( 'Cześć', $html );
	}

	public function test_renders_default_attributes(): void {
		$this->assertStringContainsString( 'pw/test', $this->render_block_markup( 'pw/test' ) );
	}

	public function test_selector_assertion_fails_when_nothing_matches(): void {
		$this->expectException( AssertionFailedError::class );
		$this->assertHtmlHasSelector( '<p class="a">x</p>', 'p.b' );
	}

	public function test_escaped_payload_passes_the_unescaped_check(): void {
		$html = $this->render_block_markup( 'pw/test', array( 'message' => self::XSS ) );

		$this->assertNoUnescaped( $html );
	}

	public function test_unescaped_script_fails_the_check(): void {
		$this->expectException( AssertionFailedError::class );
		$this->assertNoUnescaped( '<p>' . self::XSS . '</p>' );
	}

	public function test_unescaped_event_handler_attribute_fails_the_check(): void {
		$this->expectException( AssertionFailedError::class );
		$this->assertNoUnescaped( '<p title="" onmouseover="alert(1)">x</p>' );
	}

	public function test_with_data_overrides_global_data_for_the_test(): void {
		$this->with_data( 'kontakt', array( 'telefon' => '600 000 000' ) );

		$this->assertSame(
			array( 'telefon' => '600 000 000' ),
			apply_filters( 'pw/data/kontakt', array( 'telefon' => 'z bazy' ) )
		);
	}

	public function test_with_data_does_not_leak_into_the_next_test(): void {
		$this->assertFalse( has_filter( 'pw/data/kontakt' ) );
	}
}
