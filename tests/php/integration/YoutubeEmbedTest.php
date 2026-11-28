<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * YouTube never becomes an iframe through oEmbed (ADR 0008, #43).
 *
 * @package PW
 */

namespace PW\Tests\Integration;

use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Front (`wp_oembed_get`, used by `core/embed` rendering) and the editor preview proxy.
 */
class YoutubeEmbedTest extends WP_UnitTestCase {

	/**
	 * YouTube URL forms.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function youtube_urls(): array {
		return array(
			'watch'    => array( 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' ),
			'short'    => array( 'https://youtu.be/dQw4w9WgXcQ' ),
			'mobile'   => array( 'https://m.youtube.com/watch?v=dQw4w9WgXcQ' ),
			'shorts'   => array( 'https://youtube.com/shorts/dQw4w9WgXcQ' ),
			'nocookie' => array( 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ' ),
		);
	}

	/**
	 * Fails the test if anything tries to reach the network.
	 */
	public function set_up(): void {
		parent::set_up();
		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) {
				$this->fail( 'Unexpected HTTP request: ' . $url );
			},
			10,
			3
		);
	}

	/**
	 * Front: no embed HTML, no request.
	 *
	 * @dataProvider youtube_urls
	 *
	 * @param string $url YouTube URL.
	 */
	public function test_oembed_returns_no_html_for_youtube( string $url ): void {
		$this->assertFalse( wp_oembed_get( $url ) );
	}

	/**
	 * Editor preview proxy refuses YouTube.
	 *
	 * @dataProvider youtube_urls
	 *
	 * @param string $url YouTube URL.
	 */
	public function test_editor_preview_proxy_refuses_youtube( string $url ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$request = new WP_REST_Request( 'GET', '/oembed/1.0/proxy' );
		$request->set_param( 'url', $url );

		$response = rest_do_request( $request );

		$this->assertTrue( $response->is_error() );
		$this->assertSame( 'pw_youtube_embed_disabled', $response->as_error()->get_error_code() );
	}

	/**
	 * A saved YouTube embed block renders without an iframe.
	 */
	public function test_embed_block_with_youtube_renders_no_iframe(): void {
		$url  = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
		$post = self::factory()->post->create_and_get(
			array(
				'post_content' => '<!-- wp:embed {"url":"' . $url . '","type":"video","providerNameSlug":"youtube"} -->'
					. '<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube"><div class="wp-block-embed__wrapper">'
					. "\n" . $url . "\n"
					. '</div></figure><!-- /wp:embed -->',
			)
		);
		$this->go_to( get_permalink( $post ) );

		$html = apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core hook.

		$this->assertStringNotContainsString( '<iframe', $html );
	}

	/**
	 * Only YouTube hosts are matched.
	 */
	public function test_other_providers_are_not_blocked(): void {
		$this->assertFalse( \PW\Editor\is_youtube_url( 'https://vimeo.com/76979871' ) );
		$this->assertFalse( \PW\Editor\is_youtube_url( 'https://notyoutube.com/watch?v=x' ) );
	}
}
