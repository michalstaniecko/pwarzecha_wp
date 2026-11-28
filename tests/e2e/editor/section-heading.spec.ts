/**
 * pw/section-heading in the editor (#56): `naglowekId` is generated on insert and regenerated
 * when the block is duplicated, so every H2 on the page keeps a unique id; long titles warn.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

const ID = /^pw-h-[a-z0-9]{6}$/;

test.describe( 'Section heading editor', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost( { postType: 'page' } );
	} );

	test( 'generates an id on insert and a new one for a duplicate', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( {
			name: 'pw/section-heading',
			attributes: { tytul: 'Grafik zajęć' },
		} );
		await expect
			.poll(
				async () =>
					( await editor.getBlocks() )[ 0 ]?.attributes.naglowekId
			)
			.toMatch( ID );

		await page.evaluate( () => {
			const store = window.wp.data;
			const [ first ] = store.select( 'core/block-editor' ).getBlocks();
			store
				.dispatch( 'core/block-editor' )
				.duplicateBlocks( [ first.clientId ] );
		} );

		await expect
			.poll( async () => {
				const ids = ( await editor.getBlocks() ).map(
					( block ) => block.attributes.naglowekId
				);
				return (
					ids.length === 2 &&
					ids[ 0 ] !== ids[ 1 ] &&
					ids.every( ( id ) => ID.test( id ) )
				);
			} )
			.toBe( true );
		await expect( editor.canvas.locator( 'h2.sp-h2' ) ).toHaveCount( 2 );
	} );

	test( 'warns when the title is longer than 60 characters', async ( {
		editor,
	} ) => {
		await editor.insertBlock( {
			name: 'pw/section-heading',
			attributes: { tytul: 'x'.repeat( 61 ) },
		} );

		await expect( editor.canvas.getByRole( 'alert' ) ).toContainText(
			'61'
		);
	} );
} );
