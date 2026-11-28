<?php // phpcs:disable WordPress.Files.FileName -- PSR-4 test class.
/**
 * Base of the schema tests (#53): renders the Yoast graph of a URL like the front end does.
 *
 * Tests are `@group yoast` and skipped when Yoast is not loaded in the suite.
 *
 * @package PW
 */

namespace PW\Tests\Integration\Schema;

use WP_UnitTestCase;
use WPSEO_Options;
use Yoast\WP\SEO\Memoizers\Meta_Tags_Context_Memoizer;

/**
 * Graph rendering, node lookup and fixtures of the schema tests.
 */
abstract class SchemaTestCase extends WP_UnitTestCase {

	use \PW\Tests\RegistersThemeMeta;

	public function set_up(): void {
		parent::set_up();
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			$this->markTestSkipped( 'Yoast SEO is not loaded.' );
		}
		$this->register_theme_meta();
	}

	/**
	 * Yoast's Organization node exists only for a named company with a logo.
	 */
	protected function configure_organization(): void {
		$logo = self::factory()->attachment->create_object(
			'logo.png',
			0,
			array(
				'post_mime_type' => 'image/png',
				'post_type'      => 'attachment',
			)
		);
		WPSEO_Options::set( 'company_or_person', 'company' );
		WPSEO_Options::set( 'company_name', 'Klub' );
		WPSEO_Options::set( 'company_logo', wp_get_attachment_url( $logo ) );
		WPSEO_Options::set( 'company_logo_id', $logo );
	}

	/**
	 * `@graph` of the `yoast-schema-graph` script rendered in `wp_head` of the URL.
	 *
	 * @param string $url URL to visit.
	 * @return array<int, array<string, mixed>>
	 */
	protected function graph( string $url ): array {
		$this->go_to( $url );
		// Yoast memoises the context of the "current page" for the whole process.
		YoastSEO()->classes->get( Meta_Tags_Context_Memoizer::class )->clear();
		ob_start();
		do_action( 'wp_head' );
		$head = (string) ob_get_clean();

		$found = preg_match( '#<script type="application/ld\+json" class="yoast-schema-graph">(.*?)</script>#s', $head, $match );
		$this->assertSame( 1, $found, 'Yoast schema graph rendered in wp_head' );
		$json = json_decode( $match[1], true, 512, JSON_THROW_ON_ERROR );
		return $json['@graph'];
	}

	/**
	 * Nodes of the graph with the given `@type`.
	 *
	 * @param array<int, array<string, mixed>> $graph Graph.
	 * @param string                           $type  Schema type.
	 * @return array<int, array<string, mixed>>
	 */
	protected static function nodes( array $graph, string $type ): array {
		return array_values(
			array_filter(
				$graph,
				static fn( array $node ): bool => in_array( $type, (array) ( $node['@type'] ?? array() ), true )
			)
		);
	}

	/**
	 * All `@type` values of the graph.
	 *
	 * @param array<int, array<string, mixed>> $graph Graph.
	 * @return string[]
	 */
	protected static function types( array $graph ): array {
		$types = array();
		foreach ( $graph as $node ) {
			$types = array_merge( $types, (array) ( $node['@type'] ?? array() ) );
		}
		return $types;
	}

	/**
	 * Published Lokalizacja with raw meta.
	 *
	 * @param string               $miasto City (also the title).
	 * @param array<string, mixed> $meta   Raw meta.
	 */
	protected function lokalizacja( string $miasto, array $meta = array() ): int {
		$id = self::factory()->post->create(
			array(
				'post_type'   => 'lokalizacja',
				'post_status' => 'publish',
				'post_title'  => $miasto,
			)
		);
		foreach ( array_merge( array( 'pw_miasto' => $miasto ), $meta ) as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
		return $id;
	}

	/**
	 * Published page with page meta.
	 *
	 * @param string               $title Title.
	 * @param array<string, mixed> $meta  Meta (`pw_schema_typ`, `pw_lokalizacja`, …).
	 */
	protected function page( string $title, array $meta = array() ): int {
		$id = self::factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => $title,
			)
		);
		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
		return $id;
	}

	/**
	 * Sets the static front page.
	 *
	 * @param int $page Page ID.
	 */
	protected function set_front_page( int $page ): void {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page );
	}
}
