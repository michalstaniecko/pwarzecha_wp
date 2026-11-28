<?php
/**
 * Classic PHP metabox of the Lokalizacja edit screen (#49).
 *
 * No editor JS: the fields have no logic. The form posts `pw_lokalizacja[<meta key>]` with the
 * nonce `pw_lokalizacja_nonce`; `save_post_lokalizacja` sanitises every field with the same
 * callbacks as the registered meta (`fields()` in inc/data/lokalizacja.php) and deletes empty ones.
 *
 * @package PW
 */

namespace PW\Data\Lokalizacja;

use WP_Post;

use function PW\Data\Admin\enqueue_admin_scripts;
use function PW\Data\Admin\render_media_field;

defined( 'ABSPATH' ) || exit;

const NONCE        = 'pw_lokalizacja_nonce';
const NONCE_ACTION = 'pw_lokalizacja_save';
const FORM         = 'pw_lokalizacja';

/**
 * Adds the metabox and removes the raw „Custom Fields” box (the type supports `custom-fields`
 * only so that the meta shows in REST).
 */
function add_metabox(): void {
	add_meta_box( 'pw_lokalizacja', __( 'Dane Lokalizacji', 'pw-theme' ), __NAMESPACE__ . '\render_metabox', POST_TYPE, 'normal', 'high' );
	remove_meta_box( 'postcustom', POST_TYPE, 'normal' );
}

/**
 * Field definitions of the form: meta key => label, control and description.
 *
 * @return array<string, array{label: string, control: string, description?: string, attrs?: string}>
 */
function form_fields(): array {
	return array(
		'pw_ulica'             => array(
			'label'       => __( 'Ulica i numer', 'pw-theme' ),
			'control'     => 'text',
			'description' => __( 'Z prefiksem, np. „ul. Wolności 1”. Adres wpisz tak jak w wizytówce Google.', 'pw-theme' ),
		),
		'pw_kod'               => array(
			'label'   => __( 'Kod pocztowy', 'pw-theme' ),
			'control' => 'text',
			'attrs'   => ' pattern="\d{2}-\d{3}" placeholder="41-500" class="small-text"',
		),
		'pw_miasto'            => array(
			'label'       => __( 'Miasto', 'pw-theme' ),
			'control'     => 'text',
			'description' => __( 'W nagłówku Grafiku, w stopce i w kotwicy.', 'pw-theme' ),
		),
		'pw_kotwica'           => array(
			'label'       => __( 'Kotwica w Grafiku', 'pw-theme' ),
			'control'     => 'text',
			'description' => __( 'Puste = z nazwy miasta (np. „chorzow” → /grafik/#chorzow). Musi być unikalna.', 'pw-theme' ),
		),
		'pw_geo_lat'           => array(
			'label'   => __( 'Szerokość geograficzna', 'pw-theme' ),
			'control' => 'text',
			'attrs'   => ' inputmode="decimal" placeholder="50.2975"',
		),
		'pw_geo_lng'           => array(
			'label'   => __( 'Długość geograficzna', 'pw-theme' ),
			'control' => 'text',
			'attrs'   => ' inputmode="decimal" placeholder="18.9546"',
		),
		'pw_mapa_url'          => array(
			'label'       => __( 'Link „Wyznacz trasę”', 'pw-theme' ),
			'control'     => 'url',
			'description' => __( 'Link do miejsca w Google Maps (otwiera się w nowej karcie).', 'pw-theme' ),
		),
		'pw_mapa_embed'        => array(
			'label'       => __( 'Adres mapy „Pokaż mapę”', 'pw-theme' ),
			'control'     => 'url',
			'description' => __( 'Google Maps → Udostępnij → Umieść mapę: sam adres z atrybutu src, zaczynający się od https://www.google.com/maps/embed?. Inny adres nie zostanie zapisany.', 'pw-theme' ),
		),
		'pw_dojazd'            => array(
			'label'       => __( 'Dojazd', 'pw-theme' ),
			'control'     => 'textarea',
			'description' => __( 'Jak dojechać, także komunikacją miejską; gdzie zaparkować.', 'pw-theme' ),
		),
		'pw_podglad_id'        => array(
			'label'       => __( 'Podgląd', 'pw-theme' ),
			'control'     => 'media',
			'description' => __( 'Ilustracja albo zdjęcie wejścia, widoczne przed załadowaniem mapy. Nie wstawiaj zrzutu ekranu z Google Maps – licencja Google na to nie pozwala.', 'pw-theme' ),
		),
		'pw_film_youtube_id'   => array(
			'label'       => __( 'Film: ID z YouTube', 'pw-theme' ),
			'control'     => 'text',
			'attrs'       => ' maxlength="11" pattern="[A-Za-z0-9_\-]{11}" placeholder="dQw4w9WgXcQ"',
			'description' => __( '11 znaków z adresu filmu (po „v=” albo po „youtu.be/”), nie cały link.', 'pw-theme' ),
		),
		'pw_film_miniatura_id' => array(
			'label'       => __( 'Film: miniatura', 'pw-theme' ),
			'control'     => 'media',
			'description' => __( 'Obraz z Biblioteki mediów pokazywany zamiast odtwarzacza, dopóki ktoś nie kliknie.', 'pw-theme' ),
		),
		'pw_strona_oferty'     => array(
			'label'       => __( 'Strona oferty', 'pw-theme' ),
			'control'     => 'page',
			'description' => __( 'Link „Więcej o zajęciach w …” i link w stopce.', 'pw-theme' ),
		),
	);
}

/**
 * Renders the metabox.
 *
 * @param WP_Post $post Edited Lokalizacja.
 */
function render_metabox( WP_Post $post ): void {
	wp_nonce_field( NONCE_ACTION, NONCE );
	echo '<table class="form-table" role="presentation"><tbody>';
	foreach ( form_fields() as $key => $field ) {
		$value = (string) get_post_meta( $post->ID, $key, true );
		$name  = FORM . '[' . $key . ']';
		$desc  = isset( $field['description'] ) ? '<p class="description" id="' . esc_attr( $key ) . '-description">' . esc_html( $field['description'] ) . '</p>' : '';
		$aria  = isset( $field['description'] ) ? ' aria-describedby="' . esc_attr( $key ) . '-description"' : '';
		$attrs = ( $field['attrs'] ?? '' ) . $aria;

		switch ( $field['control'] ) {
			case 'textarea':
				$control = sprintf( '<textarea name="%1$s" id="%2$s" rows="4" class="large-text"%3$s>%4$s</textarea>', esc_attr( $name ), esc_attr( $key ), $attrs, esc_textarea( $value ) );
				break;
			case 'media':
				$control = render_media_field( $name, $key, absint( $value ) );
				break;
			case 'page':
				$control = page_dropdown( $name, $key, absint( $value ) );
				break;
			default:
				$class   = str_contains( $attrs, 'class=' ) ? '' : ' class="regular-text"';
				$control = sprintf( '<input type="%1$s" name="%2$s" id="%3$s" value="%4$s"%5$s%6$s>', esc_attr( $field['control'] ), esc_attr( $name ), esc_attr( $key ), esc_attr( $value ), $class, $attrs );
		}

		printf(
			'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td>%3$s%4$s</td></tr>',
			esc_attr( $key ),
			esc_html( $field['label'] ),
			$control, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above.
			$desc // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		);
	}
	echo '</tbody></table>';
}

/**
 * Select of published pages (`wp_dropdown_pages`), with „— brak —”.
 *
 * @param string $name     Input name.
 * @param string $id       Input ID.
 * @param int    $selected Selected page ID.
 */
function page_dropdown( string $name, string $id, int $selected ): string {
	$none     = __( '— brak —', 'pw-theme' );
	$args     = array(
		'name'              => $name,
		'id'                => $id,
		'selected'          => $selected,
		'show_option_none'  => $none,
		'option_none_value' => '',
		'echo'              => 0,
	);
	$dropdown = (string) wp_dropdown_pages( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- returned, not printed.
	if ( '' !== $dropdown ) {
		return $dropdown;
	}
	// wp_dropdown_pages() prints nothing while there are no pages.
	return sprintf( '<select name="%1$s" id="%2$s"><option value="">%3$s</option></select>', esc_attr( $name ), esc_attr( $id ), esc_html( $none ) );
}

/**
 * `save_post_lokalizacja` callback: saves the form after nonce and capability checks.
 *
 * @param int $post_id Lokalizacja ID.
 */
function save_metabox( int $post_id ): void {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	$nonce = isset( $_POST[ NONCE ] ) ? sanitize_text_field( wp_unslash( $_POST[ NONCE ] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, NONCE_ACTION ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	// Each value is sanitised below by its field callback.
	$form = isset( $_POST[ FORM ] ) && is_array( $_POST[ FORM ] ) ? wp_unslash( $_POST[ FORM ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	$values = array();
	foreach ( fields() as $key => $field ) {
		$raw            = $form[ $key ] ?? '';
		$values[ $key ] = is_scalar( $raw ) ? ( $field['sanitize'] )( $raw ) : '';
	}
	// Stored explicitly; keep_unique_anchor() (inc/data/lokalizacja.php) makes it unique.
	$values['pw_kotwica'] = pw_lokalizacja_kotwica( (string) $values['pw_kotwica'], (string) $values['pw_miasto'] );

	foreach ( $values as $key => $value ) {
		if ( '' === $value || 0 === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
}

/**
 * Loads the admin bundle (image fields, Grafik rows) and the media modal on the Lokalizacja edit
 * screen.
 */
function enqueue_admin_assets(): void {
	$screen = get_current_screen();
	if ( $screen && 'post' === $screen->base && POST_TYPE === $screen->post_type ) {
		enqueue_admin_scripts();
	}
}

add_action( 'add_meta_boxes_' . POST_TYPE, __NAMESPACE__ . '\add_metabox' );
add_action( 'save_post_' . POST_TYPE, __NAMESPACE__ . '\save_metabox' );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_admin_assets' );
