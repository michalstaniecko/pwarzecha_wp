<?php
/**
 * Styleguide route `/?pw_styleguide=1[&group=<slug>]` (#42): 404 unless enabled.
 *
 * @package PW
 */

namespace PW\Styleguide;

defined( 'ABSPATH' ) || exit;

/**
 * Default enablement: local and development always, other non-production environments only
 * with WP_DEBUG, production never.
 *
 * @param string $environment wp_get_environment_type().
 * @param bool   $debug       WP_DEBUG.
 */
function enabled_by_default( string $environment, bool $debug ): bool {
	return in_array( $environment, array( 'local', 'development' ), true )
		|| ( $debug && 'production' !== $environment );
}

/**
 * `wp_loaded` (last): loads the group partials when the styleguide is enabled.
 *
 * Decided here, not when the theme files load, so a `pw/styleguide/enabled` filter added by
 * theme code on `after_setup_theme` or `init` counts.
 */
function load_partials(): void {
	if ( ! pw_styleguide_enabled() ) {
		return;
	}
	foreach ( (array) glob( __DIR__ . '/partials/[!_]*.php' ) as $partial ) {
		require_once $partial;
	}
}
add_action( 'wp_loaded', __NAMESPACE__ . '\load_partials', PHP_INT_MAX );

/**
 * Query variable of the route.
 */
const QUERY_VAR = 'pw_styleguide';

add_filter(
	'query_vars',
	static function ( array $vars ): array {
		$vars[] = QUERY_VAR;
		return $vars;
	}
);

/**
 * `template_redirect` (priority 5, after the preview-mode gate of #82): serves the styleguide
 * document, or turns the request into a plain 404 when the styleguide is disabled.
 */
function route(): void {
	if ( '1' !== (string) get_query_var( QUERY_VAR ) ) {
		return;
	}
	if ( ! pw_styleguide_enabled() ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		return;
	}
	serve();
}
add_action( 'template_redirect', __NAMESPACE__ . '\route', 5 );

/**
 * Sends the headers and the document, then ends the request.
 */
function serve(): void {
	status_header( 200 );
	foreach ( headers() as $name => $value ) {
		header( "$name: $value" );
	}
	echo document( requested_group() ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped while rendering.
	exit;
}

/**
 * Response headers: never cached, never indexed.
 *
 * @return array<string, string>
 */
function headers(): array {
	return array_merge(
		array_filter( wp_get_nocache_headers(), 'is_string' ),
		array( 'X-Robots-Tag' => 'noindex, nofollow' )
	);
}

/**
 * The styleguide page: a full document with the production head (theme.json, block CSS, DS
 * stylesheet, block assets enqueued while rendering), the `.sg-*` chrome and one section per
 * registered group.
 *
 * @param string $group Group slug to render alone; '' = all groups.
 */
function document( string $group = '' ): string {
	$groups = pw_sg_groups();
	$shown  = '' === $group ? $groups : array_values( array_filter( $groups, static fn( array $g ): bool => $g['slug'] === $group ) );

	add_filter( 'wp_robots', __NAMESPACE__ . '\robots', PHP_INT_MAX );
	add_filter( 'pre_get_document_title', __NAMESPACE__ . '\title', PHP_INT_MAX );
	wp_enqueue_style( 'pw-styleguide', get_theme_file_uri( 'inc/styleguide/style.css' ), array(), wp_get_theme()->get( 'Version' ) );

	// Body first: blocks enqueue their CSS while rendering, wp_head() prints it afterwards.
	ob_start();
	echo '<main class="sg-page"><h1>' . esc_html__( 'Styleguide', 'pw-theme' ) . '</h1>';
	toc( $groups, $group );
	foreach ( $shown as $entry ) {
		pw_sg_section( $entry['slug'], $entry['title'], $entry['note'] );
		call_user_func( $entry['render'] );
		pw_sg_section_end();
	}
	echo '</main>';
	$body = (string) ob_get_clean();

	ob_start();
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'pw-styleguide' ); ?>>
	<?php wp_body_open(); ?>
<div class="wp-site-blocks">
	<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped while rendering. ?>
</div>
	<?php wp_footer(); ?>
</body>
</html>
	<?php
	$html = ltrim( (string) ob_get_clean() );

	remove_filter( 'wp_robots', __NAMESPACE__ . '\robots', PHP_INT_MAX );
	remove_filter( 'pre_get_document_title', __NAMESPACE__ . '\title', PHP_INT_MAX );
	return $html;
}

/**
 * Table of contents from the registry; with `group` set, a link back to all groups.
 *
 * @param array<int, array{slug: string, title: string}> $groups Registered groups.
 * @param string                                         $group  Group rendered alone, '' = all.
 */
function toc( array $groups, string $group ): void {
	echo '<nav class="sg-toc" aria-label="' . esc_attr__( 'Grupy styleguide', 'pw-theme' ) . '"><ul>';
	if ( '' !== $group ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( add_query_arg( QUERY_VAR, '1', home_url( '/' ) ) ), esc_html__( 'Wszystkie grupy', 'pw-theme' ) );
	} else {
		foreach ( $groups as $entry ) {
			printf( '<li><a href="#sg-%s">%s</a></li>', esc_attr( $entry['slug'] ), esc_html( $entry['title'] ) );
		}
	}
	echo '</ul></nav>';
}

/**
 * `wp_robots`: only noindex, nofollow (also over Yoast's directives).
 *
 * @return array<string, bool>
 */
function robots(): array {
	return array(
		'noindex'  => true,
		'nofollow' => true,
	);
}

/**
 * Document title of the styleguide.
 */
function title(): string {
	return __( 'Styleguide', 'pw-theme' );
}

/**
 * Group slug from `&group=<slug>`, '' = all groups.
 */
function requested_group(): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only dev page.
	return isset( $_GET['group'] ) ? sanitize_key( wp_unslash( $_GET['group'] ) ) : '';
}
