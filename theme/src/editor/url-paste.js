/**
 * Pasting a bare URL at a caret keeps it as a link (#43).
 *
 * Core's paste handler, for a URL pasted into an empty paragraph, first inserts the text inline
 * through the format types' paste rules (core/link turns it into a link) and then replaces the
 * paragraph with a `core/embed` block. In Aktualności `core/embed` is not allowed, so the
 * replacement is a no-op and only the inline link stays – a paragraph with a link, no iframe.
 *
 * Yoast SEO (28.9) re-registers `core/link` (nofollow/sponsored) with an older paste rule that
 * returns the value unchanged when nothing is selected. With a text/plain-only clipboard (a URL
 * copied from the address bar) nothing is inserted and the paste is silently lost. This rule
 * does what core/link does for a collapsed selection. Format paste rules run in registration
 * order and only the first one that changes the value applies, so with core's own core/link it
 * changes nothing.
 */
import { applyFormat, create, insert, isCollapsed } from '@wordpress/rich-text';

/**
 * @param {{html?: string, plainText?: string}} data Pasted clipboard content.
 * @return {string|null} The pasted http(s) URL, or null when the paste is not a single URL.
 */
export function pastedUrl( { html = '', plainText = '' } ) {
	const text = ( html || plainText ).replace( /<[^>]+>/g, '' ).trim();
	if ( ! /^https?:\/\/\S+$/i.test( text ) ) {
		return null;
	}
	try {
		new URL( text );
	} catch {
		return null;
	}
	return text;
}

/** Format type settings for `registerFormatType( 'pw/url-paste', … )`; adds only a paste rule. */
export const urlPasteFormat = {
	title: 'Wklejony link',
	tagName: 'span',
	className: 'pw-url-paste',
	edit: () => null,
	__unstablePasteRule( value, data ) {
		const url = pastedUrl( data );
		if ( ! url || ! isCollapsed( value ) ) {
			return value;
		}
		return insert(
			value,
			applyFormat(
				create( { text: url } ),
				{ type: 'core/link', attributes: { url } },
				0,
				url.length
			)
		);
	},
};
