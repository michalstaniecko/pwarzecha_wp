/**
 * Styleguide `/?pw_styleguide=1` (#42, ADR 0010): the only visual and a11y target of the
 * DS primitives and blocks. The sections are read from the DOM, so a group added by a new
 * partial (theme/inc/styleguide/partials/) is covered without touching this file.
 *
 * - axe (WCAG 2.2 AA) per section, no console errors, no requests to Google/YouTube: `npm run test:e2e`.
 * - `@visual` screenshot per section (`<group>.png` in tests/e2e/__screenshots__/): only in the
 *   Playwright container, `npm run test:visual` / `npm run test:visual:update`. A group with
 *   `'viewports' => [ 375 ]` (section `data-sg-viewports="375"`) also gets `<group>-375.png`, taken
 *   after the desktop pass with the viewport resized to that width.
 */
import AxeBuilder from '@axe-core/playwright';
import type { Page } from '@playwright/test';
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

const STYLEGUIDE = '/?pw_styleguide=1';
const WCAG_TAGS = [ 'wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa' ];
// ADR 0008: third-party content only behind facades – the styleguide must not load any.
const THIRD_PARTY =
	/(^|\.)(google|googleapis|gstatic|googletagmanager|doubleclick|youtube|youtube-nocookie|ytimg)\.[a-z.]+$/;

/**
 * Opens the styleguide and records console errors and third-party requests.
 *
 * @param page Playwright page.
 */
async function openStyleguide( page: Page ) {
	const errors: string[] = [];
	const thirdParty: string[] = [];
	page.on( 'console', ( message ) => {
		if ( message.type() === 'error' ) {
			errors.push( message.text() );
		}
	} );
	page.on( 'pageerror', ( error ) => errors.push( error.message ) );
	page.on( 'request', ( request ) => {
		const { hostname } = new URL( request.url() );
		if ( THIRD_PARTY.test( hostname ) ) {
			thirdParty.push( request.url() );
		}
	} );

	const response = await page.goto( STYLEGUIDE, { waitUntil: 'load' } );
	const sections = await page
		.locator( '[data-sg-section]' )
		.evaluateAll( ( nodes ) =>
			nodes.map(
				( node ) => node.getAttribute( 'data-sg-section' ) ?? ''
			)
		);

	return { response, sections, errors, thirdParty };
}

/**
 * Loads every lazy image and waits for fonts, so screenshots do not depend on scrolling; hides
 * the fixed CookieYes banner, which would otherwise cover whichever section is in the viewport.
 *
 * @param page Playwright page.
 */
async function settle( page: Page ) {
	await page.addStyleTag( {
		content:
			'.cky-consent-container, .cky-overlay, .cky-modal, .cky-btn-revisit-wrapper { display: none !important; }',
	} );
	await page.evaluate( async () => {
		const images = Array.from( document.images );
		images.forEach( ( image ) => ( image.loading = 'eager' ) );
		await Promise.all(
			images.map( ( image ) =>
				image.complete
					? Promise.resolve()
					: new Promise( ( resolve ) => {
							image.addEventListener( 'load', resolve, {
								once: true,
							} );
							image.addEventListener( 'error', resolve, {
								once: true,
							} );
						} )
			)
		);
		await document.fonts.ready;
	} );
}

test.describe( 'Styleguide', () => {
	// As a visitor: no admin bar in the screenshots.
	test.use( { storageState: { cookies: [], origins: [] } } );

	test( 'is a noindex, no-cache page with every primitive group', async ( {
		page,
	} ) => {
		const { response, sections } = await openStyleguide( page );

		expect( response?.status() ).toBe( 200 );
		expect( response?.headers()[ 'x-robots-tag' ] ).toBe(
			'noindex, nofollow'
		);
		expect( response?.headers()[ 'cache-control' ] ).toContain(
			'no-cache'
		);
		await expect( page.locator( 'meta[name="robots"]' ) ).toHaveAttribute(
			'content',
			/noindex, nofollow/
		);
		expect( sections ).toEqual(
			expect.arrayContaining( [
				'tokeny',
				'typografia',
				'ikony',
				'przyciski',
				'plakietki',
				'zdjecia',
				'tresc',
				'paginacja',
				'kontekst-navy',
			] )
		);
	} );

	test( 'every section passes axe (WCAG 2.2 AA), no console errors, no Google/YouTube', async ( {
		page,
	} ) => {
		const { sections, errors, thirdParty } = await openStyleguide( page );
		await settle( page );

		for ( const section of sections ) {
			await test.step( section, async () => {
				const { violations } = await new AxeBuilder( { page } )
					.include( `[data-sg-section="${ section }"]` )
					.withTags( WCAG_TAGS )
					.analyze();
				expect
					.soft(
						violations.map( ( violation ) => ( {
							id: violation.id,
							help: violation.help,
							targets: violation.nodes.map( ( node ) =>
								node.target.join( ' ' )
							),
						} ) ),
						`axe violations in section "${ section }"`
					)
					.toEqual( [] );
			} );
		}
		expect( errors ).toEqual( [] );
		expect( thirdParty ).toEqual( [] );
	} );

	test( 'every section matches its screenshot @visual', async ( {
		page,
	} ) => {
		const { sections } = await openStyleguide( page );
		await settle( page );

		for ( const section of sections ) {
			await test.step( section, async () => {
				await expect
					.soft( page.locator( `[data-sg-section="${ section }"]` ) )
					.toHaveScreenshot( `${ section }.png`, {
						animations: 'disabled',
						maxDiffPixelRatio: 0.001,
					} );
			} );
		}

		// Extra widths declared by the groups (`data-sg-viewports`), narrowest first.
		const extra = await page
			.locator( '[data-sg-viewports]' )
			.evaluateAll( ( nodes ) =>
				nodes.map( ( node ) => ( {
					section: node.getAttribute( 'data-sg-section' ) ?? '',
					widths: ( node.getAttribute( 'data-sg-viewports' ) ?? '' )
						.split( ' ' )
						.map( Number )
						.filter( ( width ) => width > 0 ),
				} ) )
			);
		const widths = [
			...new Set( extra.flatMap( ( { widths: w } ) => w ) ),
		].sort( ( a, b ) => a - b );
		const height = page.viewportSize()?.height ?? 720;
		for ( const width of widths ) {
			await page.setViewportSize( { width, height } );
			await settle( page );
			for ( const { section } of extra.filter( ( { widths: w } ) =>
				w.includes( width )
			) ) {
				await test.step( `${ section } @ ${ width }px`, async () => {
					await expect
						.soft(
							page.locator( `[data-sg-section="${ section }"]` )
						)
						.toHaveScreenshot( `${ section }-${ width }.png`, {
							animations: 'disabled',
							maxDiffPixelRatio: 0.001,
						} );
				} );
			}
		}
	} );
} );
