<?php
/**
 * Classic PHP metabox „Cena i zawartość” of the Karnet edit screen (#51).
 *
 * No JS: the fields have no logic. Values go through the meta sanitize callbacks
 * (inc/data/karnet.php), which clamp them.
 *
 * @package PW
 */

use function PW\Data\can_save_metabox;

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes_' . PW_KARNET_POST_TYPE, 'pw_karnet_add_metabox' );
add_action( 'save_post_' . PW_KARNET_POST_TYPE, 'pw_karnet_metabox_save' );

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
	$obejmuje = get_post_meta( $post->ID, PW_KARNET_OBEJMUJE, true );
	$obejmuje = is_array( $obejmuje ) ? implode( "\n", $obejmuje ) : '';
	$okres    = max( 1, (int) get_post_meta( $post->ID, PW_KARNET_OKRES_MIESIACE, true ) );

	wp_nonce_field( 'pw_karnet_save', 'pw_karnet_nonce' );
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="pw_cena"><?php esc_html_e( 'Cena (zł)', 'pw-theme' ); ?></label></th>
			<td><input type="number" id="pw_cena" name="pw_cena" min="0" step="1" class="small-text" value="<?php echo esc_attr( (string) max( 0, (int) get_post_meta( $post->ID, PW_KARNET_CENA, true ) ) ); ?>"></td>
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
			<td><input type="text" id="pw_okres_etykieta" name="pw_okres_etykieta" class="regular-text" placeholder="<?php esc_attr_e( '/ mies.', 'pw-theme' ); ?>" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, PW_KARNET_OKRES_ETYKIETA, true ) ); ?>"></td>
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
				<input type="checkbox" id="pw_polecany" name="pw_polecany" value="1" aria-describedby="pw_polecany_opis" <?php checked( (bool) get_post_meta( $post->ID, PW_KARNET_POLECANY, true ) ); ?>>
				<p class="description" id="pw_polecany_opis"><?php esc_html_e( 'Wyróżniony na Cenniku. Polecany może być tylko jeden Karnet – zaznaczenie zdejmuje wyróżnienie z pozostałych.', 'pw-theme' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Saves the metabox fields (nonce, capability; not on autosave or revisions). Meta sanitize callbacks
 * clamp the values.
 *
 * @param int $post_id Saved Karnet.
 */
function pw_karnet_metabox_save( $post_id ): void {
	if ( ! can_save_metabox( (int) $post_id, 'pw_karnet_nonce', 'pw_karnet_save' ) ) {
		return;
	}

	update_post_meta( $post_id, PW_KARNET_CENA, isset( $_POST[ PW_KARNET_CENA ] ) ? absint( $_POST[ PW_KARNET_CENA ] ) : 0 );
	update_post_meta( $post_id, PW_KARNET_OKRES_MIESIACE, isset( $_POST[ PW_KARNET_OKRES_MIESIACE ] ) ? absint( $_POST[ PW_KARNET_OKRES_MIESIACE ] ) : 1 );
	update_post_meta( $post_id, PW_KARNET_OKRES_ETYKIETA, isset( $_POST[ PW_KARNET_OKRES_ETYKIETA ] ) ? sanitize_text_field( wp_unslash( $_POST[ PW_KARNET_OKRES_ETYKIETA ] ) ) : '' );
	update_post_meta( $post_id, PW_KARNET_OBEJMUJE, pw_karnet_sanitize_obejmuje( isset( $_POST[ PW_KARNET_OBEJMUJE ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ PW_KARNET_OBEJMUJE ] ) ) : '' ) );

	if ( ! empty( $_POST[ PW_KARNET_POLECANY ] ) ) {
		update_post_meta( $post_id, PW_KARNET_POLECANY, true );
	} else {
		delete_post_meta( $post_id, PW_KARNET_POLECANY );
	}
}
