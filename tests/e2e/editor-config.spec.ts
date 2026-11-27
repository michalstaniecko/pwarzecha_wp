/**
 * Editor configuration for the Zleceniodawca (#43, ADR 0008): no `core/embed` in Aktualności and
 * a pasted YouTube link never becomes an iframe.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

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

	test( 'pasting a YouTube URL does not create an iframe', async ( {
		editor,
		pageUtils,
	} ) => {
		await editor.insertBlock( { name: 'core/paragraph' } );
		pageUtils.setClipboardData( { plainText: YOUTUBE_URL } );
		await pageUtils.pressKeys( 'primary+v' );

		await expect(
			editor.canvas.getByRole( 'link', { name: YOUTUBE_URL } )
		).toBeVisible();
		const blocks = await editor.getBlocks();
		expect( blocks.map( ( block ) => block.name ) ).not.toContain(
			'core/embed'
		);
		await expect( editor.canvas.locator( 'iframe' ) ).toHaveCount( 0 );
	} );
} );
