<?php
/**
 * Server render of the pw/test fixture block (#36). Registered only by the test bootstrap (#37).
 *
 * @package PW
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

?>
<p <?php echo get_block_wrapper_attributes( array( 'data-pw-test' => '1' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core escapes wrapper attributes. ?>>
	<?php echo esc_html( (string) $attributes['message'] ); ?>
</p>
