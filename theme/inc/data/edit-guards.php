<?php
/**
 * Write guards shared by the panel data (ADR 0013): who may write post meta and when a classic
 * metabox form may be saved.
 *
 * @package PW
 */

namespace PW\Data;

defined( 'ABSPATH' ) || exit;

/**
 * `auth_callback` of the theme's post meta: users who can edit the post. Use as
 * `'auth_callback' => '\PW\Data\can_edit_post_meta'` in `register_post_meta()`.
 *
 * @param bool   $allowed  Default decision (unused).
 * @param string $meta_key Meta key (unused).
 * @param int    $post_id  Post the meta belongs to.
 */
function can_edit_post_meta( $allowed, $meta_key, $post_id ): bool {
	unset( $allowed, $meta_key );
	return current_user_can( 'edit_post', (int) $post_id );
}

/**
 * Whether a `save_post_<type>` callback may save its metabox form: not an autosave or a
 * revision, the form's nonce is valid and the user can edit the post.
 *
 * @param int    $post_id      Saved post.
 * @param string $nonce_field  `$_POST` key of the nonce (`wp_nonce_field()` name).
 * @param string $nonce_action Nonce action.
 */
function can_save_metabox( int $post_id, string $nonce_field, string $nonce_action ): bool {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return false;
	}
	$nonce = isset( $_POST[ $nonce_field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) ) : '';
	return false !== wp_verify_nonce( $nonce, $nonce_action ) && current_user_can( 'edit_post', $post_id );
}
