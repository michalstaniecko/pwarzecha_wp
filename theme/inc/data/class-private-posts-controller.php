<?php
/**
 * REST controller of panel-only post types (#49): the editor selects read them, visitors do not.
 *
 * `WP_REST_Posts_Controller` serves published posts of every `show_in_rest` type to anyone,
 * also when the type is not public. Here reading needs the type's `edit_posts` capability.
 * Use as `rest_controller_class` in `register_post_type()`.
 *
 * @package PW
 */

namespace PW\Data;

use WP_Error;
use WP_REST_Posts_Controller;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Posts controller that requires `edit_posts` of the type for reading.
 */
class Private_Posts_Controller extends WP_REST_Posts_Controller {

	/**
	 * Collection: only users who can edit posts of the type.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		$denied = $this->deny_readers();
		return $denied ?? parent::get_items_permissions_check( $request );
	}

	/**
	 * Single post: only users who can edit posts of the type.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		$denied = $this->deny_readers();
		return $denied ?? parent::get_item_permissions_check( $request );
	}

	/**
	 * Error for users who cannot edit posts of the type, null for the others.
	 */
	private function deny_readers(): ?WP_Error {
		$type = get_post_type_object( $this->post_type );
		if ( $type && current_user_can( $type->cap->edit_posts ) ) {
			return null;
		}
		return new WP_Error(
			'rest_forbidden',
			__( 'Brak uprawnień do odczytu.', 'pw-theme' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}
}
