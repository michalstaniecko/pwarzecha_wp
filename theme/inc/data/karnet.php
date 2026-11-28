<?php
/**
 * CPT Karnet (#51, ADR 0007): non-public post type edited in the panel, ordered by `menu_order`.
 *
 * Read only through the repository pw_karnety() (filter `pw/data/karnety`).
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/karnet-cena.php';

const PW_KARNET_POST_TYPE = 'karnet';

add_action( 'init', 'pw_karnet_register' );
add_action( 'added_post_meta', 'pw_karnet_keep_single_polecany', 10, 4 );
add_action( 'updated_post_meta', 'pw_karnet_keep_single_polecany', 10, 4 );
add_action( 'add_meta_boxes_' . PW_KARNET_POST_TYPE, 'pw_karnet_add_metabox' );
add_action( 'save_post_' . PW_KARNET_POST_TYPE, 'pw_karnet_metabox_save' );

/**
 * Registers the post type and its meta.
 */
function pw_karnet_register(): void {
	register_post_type(
		PW_KARNET_POST_TYPE,
		array(
			'labels'                => array(
				'name'          => __( 'Karnety', 'pw-theme' ),
				'singular_name' => __( 'Karnet', 'pw-theme' ),
				'add_new_item'  => __( 'Dodaj Karnet', 'pw-theme' ),
				'edit_item'     => __( 'Edytuj Karnet', 'pw-theme' ),
				'new_item'      => __( 'Nowy Karnet', 'pw-theme' ),
				'all_items'     => __( 'Wszystkie Karnety', 'pw-theme' ),
				'search_items'  => __( 'Szukaj Karnetów', 'pw-theme' ),
				'not_found'     => __( 'Brak Karnetów.', 'pw-theme' ),
			),
			'public'                => false,
			'show_ui'               => true,
			'show_in_rest'          => true,
			'menu_icon'             => 'dashicons-tickets-alt',
			'supports'              => array( 'title', 'page-attributes', 'custom-fields' ), // custom-fields: meta in REST.
			'rest_controller_class' => \PW\Data\Private_Posts_Controller::class,
		)
	);

	$meta = array(
		'pw_cena'           => array(
			'type'              => 'integer',
			'description'       => __( 'Cena w pełnych złotych.', 'pw-theme' ),
			'default'           => 0,
			'sanitize_callback' => static fn( $value ): int => max( 0, (int) $value ),
			'show_in_rest'      => array( 'schema' => array( 'minimum' => 0 ) ),
		),
		'pw_okres_miesiace' => array(
			'type'              => 'integer',
			'description'       => __( 'Okres płatności w miesiącach.', 'pw-theme' ),
			'default'           => 1,
			'sanitize_callback' => static fn( $value ): int => max( 1, (int) $value ),
			'show_in_rest'      => array( 'schema' => array( 'minimum' => 1 ) ),
		),
		'pw_okres_etykieta' => array(
			'type'              => 'string',
			'description'       => __( 'Tekst pod ceną, np. „/ mies.”.', 'pw-theme' ),
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => true,
		),
		'pw_obejmuje'       => array(
			'type'              => 'array',
			'description'       => __( 'Co obejmuje Karnet, punkt po punkcie.', 'pw-theme' ),
			'default'           => array(),
			'sanitize_callback' => 'pw_karnet_sanitize_obejmuje',
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
			),
		),
		'pw_polecany'       => array(
			'type'              => 'boolean',
			'description'       => __( 'Polecany Karnet (najwyżej jeden).', 'pw-theme' ),
			'default'           => false,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'show_in_rest'      => true,
		),
	);

	foreach ( $meta as $key => $args ) {
		register_post_meta(
			PW_KARNET_POST_TYPE,
			$key,
			$args + array(
				'single'        => true,
				'auth_callback' => static fn( $allowed, $meta_key, $post_id ): bool => current_user_can( 'edit_post', $post_id ),
			)
		);
	}
}

/**
 * „Co obejmuje” points: a list or text with one point per line → trimmed non-empty strings.
 *
 * @param mixed $value Raw value.
 * @return list<string>
 */
function pw_karnet_sanitize_obejmuje( $value ): array {
	if ( is_string( $value ) ) {
		$value = preg_split( '/\R/', $value );
	}

	if ( ! is_array( $value ) ) {
		return array();
	}

	$points = array_map( static fn( $point ): string => sanitize_text_field( (string) $point ), $value );

	return array_values( array_filter( $points, static fn( string $point ): bool => '' !== $point ) );
}

/**
 * Classic metabox with the Karnet fields (no JS). Removes the raw „Custom Fields” box (the type
 * supports `custom-fields` only so that the meta shows in REST).
 */
function pw_karnet_add_metabox(): void {
	add_meta_box( 'pw_karnet', __( 'Cena i zawartość', 'pw-theme' ), 'pw_karnet_metabox_render', PW_KARNET_POST_TYPE, 'normal', 'high' );
	remove_meta_box( 'postcustom', PW_KARNET_POST_TYPE, 'normal' );
}

/**
 * Metabox markup.
 *
 * @param WP_Post $post Edited Karnet.
 */
function pw_karnet_metabox_render( WP_Post $post ): void {
	$obejmuje = get_post_meta( $post->ID, 'pw_obejmuje', true );
	$obejmuje = is_array( $obejmuje ) ? implode( "\n", $obejmuje ) : '';
	$okres    = max( 1, (int) get_post_meta( $post->ID, 'pw_okres_miesiace', true ) );

	wp_nonce_field( 'pw_karnet_save', 'pw_karnet_nonce' );
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="pw_cena"><?php esc_html_e( 'Cena (zł)', 'pw-theme' ); ?></label></th>
			<td><input type="number" id="pw_cena" name="pw_cena" min="0" step="1" class="small-text" value="<?php echo esc_attr( (string) max( 0, (int) get_post_meta( $post->ID, 'pw_cena', true ) ) ); ?>"></td>
		</tr>
		<tr>
			<th scope="row"><label for="pw_okres_miesiace"><?php esc_html_e( 'Okres płatności (miesiące)', 'pw-theme' ); ?></label></th>
			<td>
				<input type="number" id="pw_okres_miesiace" name="pw_okres_miesiace" min="1" step="1" class="small-text" value="<?php echo esc_attr( (string) $okres ); ?>" aria-describedby="pw_okres_miesiace_opis">
				<p class="description" id="pw_okres_miesiace_opis"><?php esc_html_e( 'Na ile miesięcy jest cena, np. 1 – miesiąc, 5 – semestr. Z tego liczona jest cena „od … zł / mies.”.', 'pw-theme' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="pw_okres_etykieta"><?php esc_html_e( 'Tekst pod ceną', 'pw-theme' ); ?></label></th>
			<td><input type="text" id="pw_okres_etykieta" name="pw_okres_etykieta" class="regular-text" placeholder="<?php esc_attr_e( '/ mies.', 'pw-theme' ); ?>" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, 'pw_okres_etykieta', true ) ); ?>"></td>
		</tr>
		<tr>
			<th scope="row"><label for="pw_obejmuje"><?php esc_html_e( 'Co obejmuje', 'pw-theme' ); ?></label></th>
			<td>
				<textarea id="pw_obejmuje" name="pw_obejmuje" rows="5" class="large-text" aria-describedby="pw_obejmuje_opis"><?php echo esc_textarea( $obejmuje ); ?></textarea>
				<p class="description" id="pw_obejmuje_opis"><?php esc_html_e( 'Jeden punkt w linii.', 'pw-theme' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="pw_polecany"><?php esc_html_e( 'Polecany', 'pw-theme' ); ?></label></th>
			<td>
				<input type="checkbox" id="pw_polecany" name="pw_polecany" value="1" aria-describedby="pw_polecany_opis" <?php checked( (bool) get_post_meta( $post->ID, 'pw_polecany', true ) ); ?>>
				<p class="description" id="pw_polecany_opis"><?php esc_html_e( 'Wyróżniony na Cenniku. Polecany może być tylko jeden Karnet – zaznaczenie zdejmuje wyróżnienie z pozostałych.', 'pw-theme' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Saves the metabox fields (nonce, capability; not on autosave). Meta sanitize callbacks
 * clamp the values.
 *
 * @param int $post_id Saved Karnet.
 */
function pw_karnet_metabox_save( $post_id ): void {
	$nonce = isset( $_POST['pw_karnet_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['pw_karnet_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'pw_karnet_save' ) || wp_is_post_autosave( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, 'pw_cena', isset( $_POST['pw_cena'] ) ? absint( $_POST['pw_cena'] ) : 0 );
	update_post_meta( $post_id, 'pw_okres_miesiace', isset( $_POST['pw_okres_miesiace'] ) ? absint( $_POST['pw_okres_miesiace'] ) : 1 );
	update_post_meta( $post_id, 'pw_okres_etykieta', isset( $_POST['pw_okres_etykieta'] ) ? sanitize_text_field( wp_unslash( $_POST['pw_okres_etykieta'] ) ) : '' );
	update_post_meta( $post_id, 'pw_obejmuje', pw_karnet_sanitize_obejmuje( isset( $_POST['pw_obejmuje'] ) ? sanitize_textarea_field( wp_unslash( $_POST['pw_obejmuje'] ) ) : '' ) );

	if ( ! empty( $_POST['pw_polecany'] ) ) {
		update_post_meta( $post_id, 'pw_polecany', true );
	} else {
		delete_post_meta( $post_id, 'pw_polecany' );
	}
}

/**
 * At most one polecany Karnet: setting the flag on one (metabox, REST, code) clears it on
 * every other Karnet.
 *
 * @param int    $meta_id    Meta row ID (unused).
 * @param int    $post_id    Post the meta belongs to.
 * @param string $meta_key   Meta key.
 * @param mixed  $meta_value Stored value.
 */
function pw_karnet_keep_single_polecany( $meta_id, $post_id, $meta_key, $meta_value ): void {
	if ( 'pw_polecany' !== $meta_key || ! $meta_value || PW_KARNET_POST_TYPE !== get_post_type( (int) $post_id ) ) {
		return;
	}

	$inne = get_posts(
		array(
			'post_type'      => PW_KARNET_POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'post__not_in'   => array( (int) $post_id ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- a handful of Karnety.
			'meta_key'       => 'pw_polecany', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- a handful of Karnety.
			'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- a handful of Karnety.
		)
	);

	foreach ( $inne as $id ) {
		delete_post_meta( (int) $id, 'pw_polecany' );
	}
}

/**
 * Published Karnety in Cennik order (`menu_order` ASC), filtered by `pw/data/karnety`.
 *
 * @return list<array{id: int, nazwa: string, cena: int, okresMiesiace: int, okresEtykieta: string, obejmuje: list<string>, polecany: bool}>
 */
function pw_karnety(): array {
	$posts = get_posts(
		array(
			'post_type'      => PW_KARNET_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'ID'         => 'ASC',
			),
			'no_found_rows'  => true,
		)
	);

	$karnety = array_map(
		static function ( WP_Post $post ): array {
			$obejmuje = get_post_meta( $post->ID, 'pw_obejmuje', true );

			return array(
				'id'            => $post->ID,
				'nazwa'         => get_the_title( $post ),
				'cena'          => max( 0, (int) get_post_meta( $post->ID, 'pw_cena', true ) ),
				'okresMiesiace' => max( 1, (int) get_post_meta( $post->ID, 'pw_okres_miesiace', true ) ),
				'okresEtykieta' => (string) get_post_meta( $post->ID, 'pw_okres_etykieta', true ),
				'obejmuje'      => is_array( $obejmuje ) ? array_values( array_map( 'strval', $obejmuje ) ) : array(),
				'polecany'      => (bool) get_post_meta( $post->ID, 'pw_polecany', true ),
			);
		},
		$posts
	);

	/**
	 * Karnety for the Cennik and Teaser ceny (ADR 0007).
	 *
	 * @param array $karnety Karnety in Cennik order.
	 */
	return apply_filters( 'pw/data/karnety', $karnety );
}
