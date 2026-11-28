<?php
/**
 * Image sizes used by pw_photo() and blocks, big image threshold, WebP output (#40).
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme sizes: name => [ width, height ], all hard-cropped to the DS ratios (16:9, 4:3, 1:1).
 */
const PW_IMAGE_SIZES = array(
	'pw-hero'    => array( 1920, 1080 ),
	'pw-hero-md' => array( 1280, 720 ),
	'pw-wide'    => array( 960, 540 ),
	'pw-photo'   => array( 800, 600 ),
	'pw-square'  => array( 600, 600 ),
);

add_action(
	'after_setup_theme',
	static function (): void {
		foreach ( PW_IMAGE_SIZES as $name => $size ) {
			add_image_size( $name, $size[0], $size[1], true );
		}
	}
);

// Core 1536/2048 sizes are never used (the largest theme size is 1920 wide).
add_action(
	'init',
	static function (): void {
		remove_image_size( '1536x1536' );
		remove_image_size( '2048x2048' );
	},
	20
);

add_filter( 'big_image_size_threshold', static fn(): int => 2560 );

add_filter(
	'image_editor_output_format',
	static function ( array $formats ): array {
		$formats['image/jpeg'] = 'image/webp';
		return $formats;
	}
);
