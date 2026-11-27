import { test } from 'node:test';
import assert from 'node:assert/strict';
import { changePlan, playgroundCacheKey, verdict } from './ci.mjs';

test( 'playgroundCacheKey: changes with the WordPress, PHP and Playground CLI versions', () => {
	assert.match(
		playgroundCacheKey(),
		/^playground-wp7\.1\.5-php8\.4-cli\d+\.\d+\.\d+$/
	);
} );

test( 'verdict: passes when every needed job succeeded or was skipped by a path filter', () => {
	assert.deepEqual(
		verdict( {
			changes: { result: 'success' },
			static: { result: 'success' },
			'php-tests': { result: 'skipped' },
		} ),
		{ ok: true, failed: [] }
	);
} );

test( 'verdict: fails on a failed or cancelled job and names it', () => {
	assert.deepEqual(
		verdict( {
			changes: { result: 'failure' },
			static: { result: 'skipped' },
			'php-tests': { result: 'cancelled' },
		} ),
		{ ok: false, failed: [ 'changes (failure)', 'php-tests (cancelled)' ] }
	);
} );

const ZERO = '0000000000000000000000000000000000000000';

test( 'changePlan: workflow_dispatch and the `all` input run every filter', () => {
	assert.deepEqual( changePlan( { event: 'workflow_dispatch' } ), {
		all: true,
		base: '',
	} );
	assert.deepEqual(
		changePlan( { event: 'push', before: 'abc123', forceAll: true } ),
		{ all: true, base: '' }
	);
} );

test( 'changePlan: pull_request lets paths-filter diff against the PR base', () => {
	assert.deepEqual(
		changePlan( { event: 'pull_request', before: 'abc123' } ),
		{
			all: false,
			base: '',
		}
	);
} );

test( 'changePlan: a branch push compares with the commit before the push', () => {
	assert.deepEqual(
		changePlan( {
			event: 'push',
			before: 'abc123',
			previousCommit: 'def456',
		} ),
		{ all: false, base: 'abc123' }
	);
} );

test( 'changePlan: a new tag (zero `before`) compares with the previous commit', () => {
	assert.deepEqual(
		changePlan( { event: 'push', before: ZERO, previousCommit: 'def456' } ),
		{ all: false, base: 'def456' }
	);
} );

test( 'changePlan: with nothing to compare against, everything runs', () => {
	assert.deepEqual(
		changePlan( { event: 'push', before: ZERO, previousCommit: '' } ),
		{ all: true, base: '' }
	);
} );
