/**
 * Smoke test of the e2e layer: the front page of the blueprint site (#37).
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Front page', () => {
	test( 'renders with theme.json styles and no console errors', async ( {
		page,
	} ) => {
		const errors: string[] = [];
		page.on( 'console', ( message ) => {
			if ( message.type() === 'error' ) {
				errors.push( message.text() );
			}
		} );
		page.on( 'pageerror', ( error ) => errors.push( error.message ) );

		const response = await page.goto( '/' );

		expect( response?.status() ).toBe( 200 );
		// Not toContainText(): it reads innerText, which is empty for <style>.
		expect(
			await page
				.locator( 'head style#global-styles-inline-css' )
				.textContent()
		).toContain( '--wp--preset--' );
		expect( errors ).toEqual( [] );
	} );
} );
