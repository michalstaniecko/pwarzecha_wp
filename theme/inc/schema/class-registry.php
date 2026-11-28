<?php
/**
 * Registry of the theme's nodes in the Yoast graph (#53).
 *
 * Yoast (free) builds WebPage, Organization and BreadcrumbList; the theme only adds pieces from
 * global data, one module instead of JSON-LD per block. Without Yoast `wpseo_schema_graph_pieces`
 * never fires and the module does nothing.
 *
 * New node without touching the module: a class extending PW\Schema\Piece, added through
 *
 *     add_filter( 'pw/schema/pieces', fn( $pieces ) => [ ...$pieces, new Faq_Page_Piece() ] );
 *
 * The filter runs only while Yoast builds the graph, so classes extending Yoast's are loadable.
 *
 * @package PW
 */

namespace PW\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks the theme pieces into Yoast's graph.
 */
final class Registry {

	/**
	 * Adds the filter on Yoast's graph pieces.
	 */
	public static function register(): void {
		add_filter( 'wpseo_schema_graph_pieces', array( self::class, 'add_pieces' ), 10, 2 );
		add_filter( 'wpseo_schema_webpage', array( self::class, 'webpage_main_entity' ), 10, 4 );
	}

	/**
	 * Appends the theme pieces (`pw/schema/pieces`) to Yoast's. Entries that are not a Piece are
	 * dropped; Yoast injects `context` and `helpers` and calls `is_needed()` / `generate()`.
	 *
	 * @param mixed $pieces  Yoast's pieces.
	 * @param mixed $context Yoast's Meta_Tags_Context.
	 * @return array<int, mixed>
	 */
	public static function add_pieces( $pieces, $context ): array {
		$pieces = is_array( $pieces ) ? $pieces : array();
		self::load();

		/**
		 * Theme graph pieces (instances of PW\Schema\Piece).
		 *
		 * @param mixed $own     Default pieces (Piece[]).
		 * @param mixed $context Yoast's Meta_Tags_Context.
		 */
		$own = apply_filters( 'pw/schema/pieces', self::defaults(), $context );

		foreach ( is_array( $own ) ? $own : array() as $piece ) {
			if ( $piece instanceof Piece ) {
				$pieces[] = $piece;
			}
		}
		return $pieces;
	}

	/**
	 * Points the WebPage's `mainEntity` at the Trener's Person (`trener_id()`) when the graph
	 * emits it, i.e. Trener_Piece passed `is_needed()` and `wpseo_schema_needs_pw-trener`.
	 *
	 * @param mixed $data       WebPage node.
	 * @param mixed $context    Yoast's Meta_Tags_Context.
	 * @param mixed $piece      Yoast's WebPage generator.
	 * @param mixed $generators Pieces being generated, after `wpseo_schema_needs_<id>`.
	 * @return mixed
	 */
	public static function webpage_main_entity( $data, $context = null, $piece = null, $generators = array() ) {
		if ( ! is_array( $data ) || ! is_array( $generators ) ) {
			return $data;
		}
		foreach ( $generators as $generator ) {
			if ( $generator instanceof Trener_Piece ) {
				$data['mainEntity'] = array( '@id' => trener_id() );
				break;
			}
		}
		return $data;
	}

	/**
	 * Default theme pieces.
	 *
	 * @return Piece[]
	 */
	private static function defaults(): array {
		return array(
			new Location_Piece(),
			new Trener_Piece(),
			new Service_Piece(),
		);
	}

	/**
	 * Loads the piece classes (they extend Yoast's Abstract_Schema_Piece).
	 */
	private static function load(): void {
		require_once __DIR__ . '/pieces/class-piece.php';
		require_once __DIR__ . '/pieces/class-location-piece.php';
		require_once __DIR__ . '/pieces/class-trener-piece.php';
		require_once __DIR__ . '/pieces/class-service-piece.php';
	}
}
