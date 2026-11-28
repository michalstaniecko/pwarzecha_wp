<?php
/**
 * YouTube never goes through oEmbed (ADR 0008, #43): films use the VideoFacade block (`pw/video`).
 *
 * Two entry points, because the editor preview proxy uses `WP_oEmbed::get_data()`, which skips
 * `pre_oembed_result`:
 * - `pre_oembed_result`: front rendering of `core/embed` / auto-embeds falls back to a plain link,
 *   no request to YouTube.
 * - `rest_pre_dispatch` on `/oembed/1.0/proxy`: the editor preview fails instead of showing an
 *   iframe (covers the Site Editor, where `core/embed` stays allowed).
 *
 * @package PW
 */

namespace PW\Editor;

use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

const YOUTUBE_HOSTS = array( 'youtube.com', 'youtu.be', 'youtube-nocookie.com' );

/**
 * Whether the URL points at YouTube (any subdomain of its hosts).
 *
 * @param string $url URL.
 */
function is_youtube_url( string $url ): bool {
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	foreach ( YOUTUBE_HOSTS as $youtube ) {
		if ( $host === $youtube || str_ends_with( $host, '.' . $youtube ) ) {
			return true;
		}
	}
	return false;
}

/**
 * `pre_oembed_result` callback: `false` (no embed) for YouTube.
 *
 * @param null|string|false $result Short-circuit value.
 * @param string            $url    Embedded URL.
 * @return null|string|false
 */
function refuse_youtube_oembed( $result, $url ) {
	return is_youtube_url( (string) $url ) ? false : $result;
}

/**
 * `rest_pre_dispatch` callback: the oEmbed proxy refuses YouTube URLs.
 *
 * @param mixed           $result  Response so far.
 * @param mixed           $server  REST server.
 * @param WP_REST_Request $request Request.
 * @return mixed
 */
function refuse_youtube_proxy( $result, $server, WP_REST_Request $request ) {
	if ( null !== $result || '/oembed/1.0/proxy' !== $request->get_route() ) {
		return $result;
	}
	if ( ! is_youtube_url( (string) $request->get_param( 'url' ) ) ) {
		return $result;
	}
	return new WP_Error(
		'pw_youtube_embed_disabled',
		__( 'Filmy z YouTube wstawiaj blokiem Film.', 'pw-theme' ),
		array( 'status' => 404 )
	);
}

add_filter( 'pre_oembed_result', __NAMESPACE__ . '\refuse_youtube_oembed', 10, 2 );
add_filter( 'rest_pre_dispatch', __NAMESPACE__ . '\refuse_youtube_proxy', 10, 3 );
