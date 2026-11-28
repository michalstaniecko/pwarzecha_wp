<?php
/**
 * Base of the theme's Yoast graph pieces (#53).
 *
 * Yoast 28 has no `WPSEO_Graph_Piece` interface any more; its pieces extend
 * `Abstract_Schema_Piece` (`is_needed()`, `generate()`, `$context`). Loaded only from
 * Registry::add_pieces(), i.e. when Yoast is active and builds the graph.
 *
 * @package PW
 */

namespace PW\Schema;

use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

defined( 'ABSPATH' ) || exit;

/**
 * Theme graph piece with context helpers. Register new pieces via the `pw/schema/pieces` filter.
 */
abstract class Piece extends Abstract_Schema_Piece {

	/**
	 * ID of the page the graph is built for, 0 elsewhere.
	 */
	protected function page_id(): int {
		$indexable = $this->context->indexable;
		return 'post' === $indexable->object_type && 'page' === $indexable->object_sub_type ? (int) $indexable->object_id : 0;
	}

	/**
	 * Whether the graph is built for the home page (static front page or the posts home).
	 */
	protected function is_front_page(): bool {
		if ( 'home-page' === $this->context->indexable->object_type ) {
			return true;
		}
		$id = $this->page_id();
		return $id > 0 && 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === $id;
	}

	/**
	 * Schema type of the current page (`brak` outside pages).
	 */
	protected function typ(): string {
		$id = $this->page_id();
		return $id > 0 ? typ( $id ) : TYP_BRAK;
	}

	/**
	 * `@id` reference of Yoast's Organization, null when the site does not represent a company
	 * (then Yoast emits no Organization node to point at).
	 *
	 * @return array{"@id": string}|null
	 */
	protected function organization(): ?array {
		if ( 'company' !== $this->context->site_represents ) {
			return null;
		}
		$reference = $this->context->site_represents_reference;
		return is_array( $reference ) && isset( $reference['@id'] ) ? array( '@id' => (string) $reference['@id'] ) : null;
	}
}
