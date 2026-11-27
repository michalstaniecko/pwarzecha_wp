/**
 * Budget rules of ADR 0010 that ci.yml must keep (#44). actionlint checks syntax in CI;
 * this checks the decisions.
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { parse } from 'yaml';
import { root } from '../playground/site.mjs';

const ci = parse(
	readFileSync( join( root, '.github/workflows/ci.yml' ), 'utf8' )
);
const jobs = Object.entries( ci.jobs );

test( 'ci.yml: runs on PRs to develop and main, reusable and manual', () => {
	assert.deepEqual( ci.on.pull_request.branches, [ 'develop', 'main' ] );
	assert.ok( ci.on.pull_request.types.includes( 'ready_for_review' ) );
	assert.ok( 'workflow_call' in ci.on );
	assert.ok( 'workflow_dispatch' in ci.on );
} );

test( 'ci.yml: a new push cancels the previous run of the same PR', () => {
	assert.equal(
		ci.concurrency.group,
		'ci-${{ github.workflow }}-${{ github.event.pull_request.number || github.ref }}'
	);
	assert.equal( ci.concurrency[ 'cancel-in-progress' ], true );
} );

test( 'ci.yml: read-only token, pinned runner, timeout on every job', () => {
	assert.deepEqual( ci.permissions, { contents: 'read' } );
	for ( const [ name, job ] of jobs ) {
		assert.equal( job[ 'runs-on' ], 'ubuntu-26.04', name );
		assert.ok( job[ 'timeout-minutes' ] <= 10, name );
	}
} );

test( 'ci.yml: draft PRs start no job', () => {
	const draftGuard =
		"github.event_name != 'pull_request' || !github.event.pull_request.draft";
	// Guarded itself, or (without always()) skipped because a job it needs is guarded.
	const guarded = ( job ) =>
		String( job.if ).includes( draftGuard ) ||
		( Boolean( job.needs ) &&
			! String( job.if ).includes( 'always()' ) &&
			[ job.needs ]
				.flat()
				.some( ( need ) => guarded( ci.jobs[ need ] ) ) );
	for ( const [ name, job ] of jobs ) {
		assert.ok( guarded( job ), name );
	}
} );

test( 'ci.yml: artifacts only on failure, kept 7 days', () => {
	const uploads = jobs.flatMap( ( [ , job ] ) =>
		job.steps.filter( ( step ) =>
			step.uses?.startsWith( 'actions/upload-artifact' )
		)
	);
	assert.ok( uploads.length > 0 );
	for ( const step of uploads ) {
		assert.equal( step.if, 'failure()' );
		assert.equal( step.with[ 'retention-days' ], 7 );
	}
} );
