/**
 * Smoke test of the starting state: the blueprint (or `test:e2e:wp-env`) runs `wp pw seed` (#54).
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

type Post = { title: { raw: string } };

test.describe( 'Seeded site', () => {
	test( 'has the Lokalizacje, Karnety and menus of `wp pw seed`', async ( {
		requestUtils,
	} ) => {
		const titles = async ( path: string ) =>
			(
				( await requestUtils.rest( {
					path,
					params: {
						context: 'edit',
						orderby: 'menu_order',
						order: 'asc',
					},
				} ) ) as Post[]
			 ).map( ( post ) => post.title.raw );

		expect( await titles( '/wp/v2/lokalizacja' ) ).toEqual( [
			'Chorzów',
			'Katowice',
		] );
		expect( await titles( '/wp/v2/karnet' ) ).toHaveLength( 3 );

		const menus = ( await requestUtils.rest( {
			path: '/wp/v2/navigation',
			params: { context: 'edit' },
		} ) ) as Post[];
		expect( menus.map( ( menu ) => menu.title.raw ).sort() ).toEqual( [
			'Menu główne',
			'Stopka – Na skróty',
			'Stopka – Oferta',
		] );
	} );
} );
