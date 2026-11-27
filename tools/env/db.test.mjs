import { test } from 'node:test';
import assert from 'node:assert/strict';
import { dumpName, importTarget } from './db.mjs';

test( 'dump names carry the local date and time, zero-padded', () => {
	assert.equal(
		dumpName( new Date( 2026, 0, 5, 9, 4, 7 ) ),
		'pw-2026-01-05-090407.sql'
	);
	assert.equal(
		dumpName( new Date( 2026, 10, 27, 23, 59, 30 ) ),
		'pw-2026-11-27-235930.sql'
	);
} );

test( 'import defaults to .data/latest.sql', () => {
	assert.deepEqual( importTarget( undefined, '/repo' ), {
		host: '/repo/.data/latest.sql',
		container: 'pw-data/latest.sql',
	} );
} );

test( 'import accepts a dump given relative to the repo or by bare name', () => {
	const expected = {
		host: '/repo/.data/pw-2026-01-05-090407.sql',
		container: 'pw-data/pw-2026-01-05-090407.sql',
	};
	assert.deepEqual(
		importTarget( '.data/pw-2026-01-05-090407.sql', '/repo' ),
		expected
	);
	assert.deepEqual(
		importTarget( '/repo/.data/pw-2026-01-05-090407.sql', '/repo' ),
		expected
	);
	assert.deepEqual(
		importTarget( 'pw-2026-01-05-090407.sql', '/repo' ),
		expected
	);
} );

test( 'import refuses files outside .data/ (only .data is mounted in the container)', () => {
	assert.throws(
		() => importTarget( '/tmp/backup.sql', '/repo' ),
		/\.data\//
	);
	assert.throws(
		() => importTarget( '.data/../theme/x.sql', '/repo' ),
		/\.data\//
	);
} );
