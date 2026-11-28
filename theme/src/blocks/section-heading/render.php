<?php
/**
 * Server render of pw/section-heading (#56): `<header class="sp-section-head">` with optional
 * eyebrow, exactly one H2 and optional lead. No title → nothing (no empty H2).
 *
 * The H2 id is `naglowekId` (set by the editor) or the deterministic fallback of
 * pw_heading_id(), which `pw/section` (#55) uses for `aria-labelledby` too.
 *
 * @package PW
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

$pw_tytul = (string) ( $attributes['tytul'] ?? '' );
if ( '' === trim( wp_strip_all_tags( $pw_tytul ) ) ) {
	return;
}

$pw_nadtytul = (string) ( $attributes['nadtytul'] ?? '' );
$pw_lead     = (string) ( $attributes['lead'] ?? '' );
$pw_classes  = pw_classes(
	array(
		'sp-section-head',
		'sp-section-head--center' => 'center' === ( $attributes['wyrownanie'] ?? 'left' ),
	)
);
?>
<header <?php echo get_block_wrapper_attributes( array( 'class' => $pw_classes ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core escapes wrapper attributes. ?>>
	<?php if ( '' !== trim( wp_strip_all_tags( $pw_nadtytul ) ) ) : ?>
		<p class="sp-eyebrow"><?php echo wp_kses_post( $pw_nadtytul ); ?></p>
	<?php endif; ?>
	<h2 class="sp-h2" id="<?php echo esc_attr( pw_heading_id( $pw_tytul, (string) ( $attributes['naglowekId'] ?? '' ) ) ); ?>"><?php echo wp_kses_post( $pw_tytul ); ?></h2>
	<?php if ( '' !== trim( wp_strip_all_tags( $pw_lead ) ) ) : ?>
		<p class="sp-lead"><?php echo wp_kses_post( $pw_lead ); ?></p>
	<?php endif; ?>
</header>
