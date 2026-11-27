<?php
/**
 * Blocks the Zleceniodawca can insert, per editor (#43, decision #21).
 *
 * - Aktualność (`post`): simple blocks + Photo, CheckList, VideoFacade, Galeria.
 * - Strona (`page`): every `pw/*` block + selected core blocks (`core/group` only for patterns).
 * - Site Editor and other post types: unrestricted (templates are built from core blocks).
 *
 * No `core/embed` or `core/html` anywhere in content (ADR 0008).
 *
 * @package PW
 */

namespace PW\Editor;

use WP_Block_Editor_Context;
use WP_Block_Type_Registry;
use WP_Post;

defined( 'ABSPATH' ) || exit;

const POST_BLOCKS = array(
	'core/paragraph',
	'core/heading',
	'core/list',
	'core/list-item',
	'core/quote',
	'core/image',
	'core/gallery',
	'pw/checklist-pair',
	'pw/video',
);

const PAGE_CORE_BLOCKS = array(
	'core/paragraph',
	'core/heading',
	'core/list',
	'core/list-item',
	'core/quote',
	'core/image',
	'core/gallery',
	'core/table',
	'core/buttons',
	'core/button',
	'core/block',
	'core/group',
);

/**
 * `allowed_block_types_all` callback.
 *
 * @param bool|array<int, string> $allowed Allowed block types so far.
 * @param WP_Block_Editor_Context $context Editor context.
 * @return bool|array<int, string>
 */
function allowed_block_types( $allowed, WP_Block_Editor_Context $context ) {
	$post = $context->post;
	if ( ! $post instanceof WP_Post ) {
		return $allowed;
	}

	switch ( $post->post_type ) {
		case 'post':
			return POST_BLOCKS;
		case 'page':
			return array_values( array_unique( array_merge( pw_block_names(), PAGE_CORE_BLOCKS ) ) );
		default:
			return $allowed;
	}
}

/**
 * Names of all registered `pw/*` blocks.
 *
 * @return array<int, string>
 */
function pw_block_names(): array {
	$names = array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() );
	return array_values( array_filter( $names, static fn( string $name ): bool => str_starts_with( $name, 'pw/' ) ) );
}

add_filter( 'allowed_block_types_all', __NAMESPACE__ . '\allowed_block_types', 10, 2 );
