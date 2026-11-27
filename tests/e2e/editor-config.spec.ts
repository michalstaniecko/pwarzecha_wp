/**
 * Editor configuration for the Zleceniodawca (#43, ADR 0008): no `core/embed` in Aktualności,
 * a pasted YouTube link never becomes an iframe, heading levels H2–H3 only in Aktualności.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';

/**
 * Default `levelOptions` of core/heading as registered in this editor (undefined = all levels).
 *
 * @param page Editor page.
 */
function headingLevelOptions( page: Page ): Promise< number[] | undefined > {
	return page.evaluate(
		() =>
			window.wp.data
				.select( 'core/blocks' )
				.getBlockType( 'core/heading' ).attributes.levelOptions?.default
	);
}

const YOUTUBE_URL = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

test.describe( 'Aktualność editor', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'inserter offers no embed blocks', async ( { page } ) => {
		const inserterItems = await page.evaluate( () =>
			window.wp.data
				.select( 'core/block-editor' )
				.getInserterItems()
				.map( ( item: { name: string } ) => item.name )
		);

		expect( inserterItems ).toContain( 'core/paragraph' );
		expect(
			inserterItems.filter( ( name: string ) =>
				name.startsWith( 'core/embed' )
			)
		).toEqual( [] );
	} );

	test( 'heading offers only H2 and H3', async ( { page } ) => {
		expect( await headingLevelOptions( page ) ).toEqual( [ 2, 3 ] );
	} );

	// A text/plain-only clipboard, like a URL copied from the address bar. With Yoast SEO active
	// this paste used to be silently lost (theme/src/editor/url-paste.js, tests/README.md).
	test( 'pasting a YouTube URL does not create an iframe', async ( {
		editor,
		pageUtils,
	} ) => {
		await editor.insertBlock( { name: 'core/paragraph' } );
		pageUtils.setClipboardData( { plainText: YOUTUBE_URL } );
		await pageUtils.pressKeys( 'primary+v' );

		await expect(
			editor.canvas.getByRole( 'link', { name: YOUTUBE_URL } )
		).toHaveAttribute( 'href', YOUTUBE_URL );
		const blocks = await editor.getBlocks();
		expect( blocks.map( ( block ) => block.name ) ).not.toContain(
			'core/embed'
		);
		await expect( editor.canvas.locator( 'iframe' ) ).toHaveCount( 0 );
	} );
} );

test.describe( 'heading levels outside Aktualności', () => {
	test( 'a page offers every level', async ( { admin, page } ) => {
		await admin.createNewPost( { postType: 'page' } );

		expect( await headingLevelOptions( page ) ).toBeUndefined();
	} );

	test( 'the Site Editor offers every level', async ( { admin, page } ) => {
		await admin.visitSiteEditor();

		expect( await headingLevelOptions( page ) ).toBeUndefined();
	} );
} );
