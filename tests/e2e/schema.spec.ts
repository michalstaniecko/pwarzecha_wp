/**
 * Schema module (#53): the editor sets the page type in the panel „Lokalizacje i schema”, the
 * Yoast graph gets the theme node; without Yoast the site still renders.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';

/**
 * Nodes of the Yoast graph on the current page.
 *
 * @param page Page.
 */
async function graphNodes(
	page: Page
): Promise< Array< Record< string, unknown > > > {
	const json = await page
		.locator( 'script.yoast-schema-graph' )
		.textContent();
	return JSON.parse( json ?? '{}' )[ '@graph' ] ?? [];
}

test.describe( 'Schema', () => {
	test.afterEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin( 'yoast-seo' );
		await requestUtils.deleteAllPages();
	} );

	test( 'Service page set in the document panel gets a Service node', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( {
			postType: 'page',
			title: 'Treningi dla firm',
		} );
		await editor.openDocumentSettingsSidebar();
		const toggle = page.getByRole( 'button', {
			name: 'Lokalizacje i schema',
		} );
		if ( ( await toggle.getAttribute( 'aria-expanded' ) ) !== 'true' ) {
			await toggle.click();
		}

		await page
			.getByRole( 'combobox', { name: 'Typ strony w schemie' } )
			.selectOption( 'service' );
		const cities = page.getByRole( 'combobox', {
			name: 'Obszar usługi (miasta)',
		} );
		await cities.fill( 'Bytom' );
		await cities.press( 'Enter' );
		const id = await editor.publishPost();

		await page.goto( `/?page_id=${ id }` );
		const services = ( await graphNodes( page ) ).filter(
			( node ) => node[ '@type' ] === 'Service'
		);

		expect( services ).toHaveLength( 1 );
		expect( services[ 0 ].areaServed ).toEqual( [
			{ '@type': 'City', name: 'Bytom' },
		] );
		expect(
			( await graphNodes( page ) ).map( ( node ) => node[ '@type' ] )
		).not.toContain( 'Offer' );
	} );

	test( 'site renders without Yoast', async ( { page, requestUtils } ) => {
		const service = await requestUtils.createPage( {
			title: 'Treningi 1:1',
			status: 'publish',
		} );
		await requestUtils.rest( {
			method: 'POST',
			path: `/wp/v2/pages/${ service.id }`,
			data: { meta: { pw_schema_typ: 'service' } },
		} );
		await requestUtils.deactivatePlugin( 'yoast-seo' );

		for ( const url of [ '/', service.link ] ) {
			const response = await page.goto( url );
			expect( response?.status() ).toBe( 200 );
			const html = await page.content();
			expect( html ).not.toMatch( /Fatal error|Warning:|Notice:/ );
			expect( html ).not.toContain( 'yoast-schema-graph' );
		}
	} );
} );
