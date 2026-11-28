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
	 * Default theme pieces.
	 *
	 * @return Piece[]
	 */
	private static function defaults(): array {
		return array(
			new Location_Piece(),
			new Trener_Piece(),
		);
	}

	/**
	 * Loads the piece classes (they extend Yoast's Abstract_Schema_Piece).
	 */
	private static function load(): void {
		require_once __DIR__ . '/pieces/class-piece.php';
		require_once __DIR__ . '/pieces/class-location-piece.php';
		require_once __DIR__ . '/pieces/class-trener-piece.php';
	}
}
