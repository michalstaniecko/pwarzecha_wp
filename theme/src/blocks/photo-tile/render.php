<?php
/**
 * Server render of pw/photo-tile (#60): 4:3 photo (lazy, optional), H3 and short text.
 * Empty title → nothing (the tile has no heading to stand on).
 *
 * @package PW
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

$pw_tytul = trim( wp_kses( (string) ( $attributes['tytul'] ?? '' ), array() ) );
if ( '' === $pw_tytul ) {
	return;
}

$pw_tekst = trim(
	wp_kses(
		(string) ( $attributes['tekst'] ?? '' ),
		array(
			'strong' => array(),
			'em'     => array(),
		)
	)
);
$pw_photo = pw_photo(
	(int) ( $attributes['zdjecieId'] ?? 0 ),
	array(
		'ratio' => 'photo',
		'sizes' => '(min-width: 1240px) 370px, (min-width: 640px) 50vw, 100vw',
	)
);
?>
<article <?php echo get_block_wrapper_attributes( array( 'class' => 'sp-photo-tile' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core escapes wrapper attributes. ?>>
	<?php echo $pw_photo; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in pw_photo(). ?>
	<div class="sp-photo-tile__body">
		<h3 class="sp-photo-tile__title"><?php echo $pw_tytul; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses() without tags above. ?></h3>
		<?php if ( '' !== $pw_tekst ) : ?>
			<p><?php echo $pw_tekst; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses() above. ?></p>
		<?php endif; ?>
	</div>
</article>
