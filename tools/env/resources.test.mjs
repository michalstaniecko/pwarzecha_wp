import { test } from 'node:test';
import assert from 'node:assert/strict';
import { testResources } from './resources.mjs';

test( 'picks only pw-test* resources, in any Docker listing', () => {
	assert.deepEqual(
		testResources( [
			'pw-test-mariadb-1',
			'pw-test_db',
			'pw-tests-visual',
			'pw-dev-wordpress-1',
			'4f1c2a-wordpress-1',
			'other-project-pw-test',
			'',
		] ),
		[ 'pw-test-mariadb-1', 'pw-test_db', 'pw-tests-visual' ]
	);
} );

test( 'never selects anything for an empty listing', () => {
	assert.deepEqual( testResources( [] ), [] );
} );
