<?php
/**
 * Server render of pw/tiles (#60): `sp-grid--3` / `sp-grid--4` around the rendered tiles.
 *
 * @package PW
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Rendered inner tiles.
 */

if ( '' === trim( $content ) ) {
	return;
}

$pw_kolumny = in_array( $attributes['kolumny'] ?? '', array( '3', '4' ), true ) ? $attributes['kolumny'] : '3';
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'sp-grid sp-grid--' . $pw_kolumny ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core escapes wrapper attributes. ?>>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered inner blocks. ?>
</div>
