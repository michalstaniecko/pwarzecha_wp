<?php
/**
 * `Service` node (#53, docs/design/decyzje-ds-i-layoutow.md §5): pages with `pw_schema_typ` =
 * `service` (1:1 and B2B). `areaServed` from `pw_area_served`; no Offer and no price (#8).
 *
 * @package PW
 */

namespace PW\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * The service a page describes.
 */
class Service_Piece extends Piece {

	/**
	 * Identifier for the `wpseo_schema_needs_<id>` and `wpseo_schema_<id>` filters.
	 *
	 * @var string
	 */
	public $identifier = 'pw-service';

	/**
	 * Needed on Service pages.
	 */
	public function is_needed(): bool {
		return 'service' === $this->typ();
	}

	/**
	 * The Service node.
	 *
	 * @return array<string, mixed>
	 */
	public function generate(): array {
		$id = $this->page_id();
		return service_node(
			wp_strip_all_tags( (string) get_post_field( 'post_title', $id, 'raw' ) ),
			(string) get_permalink( $id ),
			area_served( $id ),
			$this->organization()
		);
	}
}
