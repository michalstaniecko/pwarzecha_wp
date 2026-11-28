/**
 * Decisions of .github/workflows/full.yml (#45, ADR 0010). The job conditions and the concurrency
 * group are evaluated with GitHub's own expression engine against the events that reach the
 * workflow, so "runs on `ci:full`, not on a plain PR" is checked, not just spelled out.
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { parse } from 'yaml';
import { root } from '../playground/site.mjs';
import { IMAGE as VISUAL_IMAGE } from '../visual/visual.mjs';
import { evaluate, interpolate } from './expressions.mjs';

const full = parse(
	readFileSync( join( root, '.github/workflows/full.yml' ), 'utf8' )
);

/**
 * `github` context of a pull_request event.
 *
 * @param {string}   action        Event action (`labeled`, `synchronize`, `ready_for_review`).
 * @param {string[]} labels        Labels on the PR after the event.
 * @param {string}   [label]       Label just added (`labeled` only).
 * @param {boolean}  [draft=false] Whether the PR is a draft.
 * @return {Object} Expression context.
 */
function pr( action, labels, label, draft = false ) {
	return {
		github: {
			event_name: 'pull_request',
			ref: 'refs/pull/7/merge',
			run_id: '1001',
			event: {
				action,
				pull_request: {
					number: 7,
					draft,
					labels: labels.map( ( name ) => ( { name } ) ),
				},
				...( label ? { label: { name: label } } : {} ),
			},
		},
		inputs: {},
	};
}

const push = {
	github: {
		event_name: 'push',
		ref: 'refs/heads/develop',
		run_id: '1002',
		event: {},
	},
	inputs: {},
};

/**
 * @param {boolean} updateSnapshots Input `update_snapshots`.
 * @return {Object} Context of a manual run on develop.
 */
function dispatch( updateSnapshots ) {
	return {
		github: { ...push.github, event_name: 'workflow_dispatch' },
		inputs: { ref: '', update_snapshots: updateSnapshots },
	};
}

/**
 * Whether a job starts, given the result of the `fast` job it needs.
 *
 * @param {string} job        Job id.
 * @param {Object} context    Expression context.
 * @param {string} fastResult `needs.fast.result`.
 * @return {boolean} Job condition.
 */
function runs( job, context, fastResult = 'success' ) {
	const condition = full.jobs[ job ].if ?? 'success()';
	return evaluate( condition, {
		...context,
		needs: { fast: { result: fastResult } },
	} );
}

/**
 * Steps of job e2e that run on a green run (Composer plugins not cached yet).
 *
 * @param {Object} context Expression context.
 * @return {Object[]} Steps.
 */
function e2eSteps( context ) {
	return full.jobs.e2e.steps.filter( ( step ) =>
		evaluate( step.if ?? 'success()', {
			...context,
			steps: { plugins: { outputs: { 'cache-hit': 'false' } } },
		} )
	);
}

test( 'full.yml: a plain PR starts no job, whatever happens to it', () => {
	for ( const context of [
		pr( 'synchronize', [] ),
		pr( 'labeled', [ 'bug' ], 'bug' ),
	] ) {
		assert.equal( runs( 'fast', context ), false );
		assert.equal( runs( 'e2e', context, 'skipped' ), false );
	}
} );

test( 'full.yml: label `ci:full` starts e2e on the PR without re-running ci.yml', () => {
	for ( const context of [
		pr( 'labeled', [ 'ci:full' ], 'ci:full' ),
		pr( 'synchronize', [ 'bug', 'ci:full' ] ),
	] ) {
		assert.equal( runs( 'fast', context ), false );
		assert.equal( runs( 'e2e', context, 'skipped' ), true );
	}
} );

test( 'full.yml: a draft PR starts no job, even with `ci:full`', () => {
	for ( const context of [
		pr( 'labeled', [ 'ci:full' ], 'ci:full', true ),
		pr( 'synchronize', [ 'ci:full' ], undefined, true ),
		pr( 'labeled', [ 'bug' ], 'bug', true ),
	] ) {
		assert.equal( runs( 'fast', context ), false );
		assert.equal( runs( 'e2e', context, 'skipped' ), false );
	}
} );

test( 'full.yml: marking a `ci:full` draft ready starts e2e, a plain one stays skipped', () => {
	const labelled = pr( 'ready_for_review', [ 'ci:full' ] );
	assert.equal( runs( 'fast', labelled ), false );
	assert.equal( runs( 'e2e', labelled, 'skipped' ), true );

	const plain = pr( 'ready_for_review', [ 'bug' ] );
	assert.equal( runs( 'fast', plain ), false );
	assert.equal( runs( 'e2e', plain, 'skipped' ), false );
} );

test( 'full.yml: another label on a `ci:full` PR does not start a second e2e', () => {
	const context = pr( 'labeled', [ 'ci:full', 'bug' ], 'bug' );
	assert.equal( runs( 'e2e', context, 'skipped' ), false );
} );

test( 'full.yml: push to develop and manual runs gate e2e on the fast checks', () => {
	for ( const context of [ push, dispatch( false ) ] ) {
		assert.equal( runs( 'fast', context ), true );
		assert.equal( runs( 'e2e', context, 'success' ), true );
		assert.equal( runs( 'e2e', context, 'failure' ), false );
		assert.equal( runs( 'e2e', context, 'cancelled' ), false );
	}
} );

test( 'full.yml: update_snapshots skips the fast checks and only rewrites baselines', () => {
	const context = dispatch( true );
	assert.equal( runs( 'fast', context ), false );
	assert.equal( runs( 'e2e', context, 'skipped' ), true );

	const steps = e2eSteps( context );
	const commands = steps.map( ( step ) => step.run ).filter( Boolean );
	assert.ok(
		commands.includes(
			'npx playwright test --project=visual --update-snapshots'
		)
	);
	assert.ok( ! commands.includes( 'npx playwright test' ) );
	assert.ok(
		steps.some(
			( step ) =>
				step.uses?.startsWith( 'actions/upload-artifact' ) &&
				step.with.path === 'tests/e2e/__screenshots__/'
		)
	);
} );

test( 'full.yml: a normal run tests e2e and visual, not the baseline update', () => {
	const commands = e2eSteps( push )
		.map( ( step ) => step.run )
		.filter( Boolean );
	assert.ok( commands.includes( 'npx playwright test' ) );
	assert.ok(
		! commands.some( ( command ) =>
			command.includes( '--update-snapshots' )
		)
	);
	assert.equal( full.jobs.e2e.env.PW_VISUAL, '1' );
	assert.equal( full.jobs.e2e.env.PW_E2E_PLUGINS, '1' );
} );

test( 'full.yml: a new push cancels the PR run, another label does not', () => {
	const group = ( context ) => interpolate( full.concurrency.group, context );
	assert.equal( full.concurrency[ 'cancel-in-progress' ], true );

	const running = group( pr( 'labeled', [ 'ci:full' ], 'ci:full' ) );
	assert.equal( running, 'full-7' );
	assert.equal( group( pr( 'synchronize', [ 'ci:full' ] ) ), running );
	assert.notEqual(
		group( pr( 'labeled', [ 'ci:full', 'bug' ], 'bug' ) ),
		running
	);
	assert.equal( group( push ), 'full-refs/heads/develop' );
} );

test( 'full.yml: the fast job grants what ci.yml asks for and passes the ref on', () => {
	const ci = parse(
		readFileSync( join( root, '.github/workflows/ci.yml' ), 'utf8' )
	);
	const granted = full.jobs.fast.permissions;
	for ( const job of Object.values( ci.jobs ) ) {
		for ( const [ scope, level ] of Object.entries(
			job.permissions ?? ci.permissions
		) ) {
			assert.equal( granted[ scope ], level, scope );
		}
	}
	assert.ok( 'ref' in ci.on.workflow_call.inputs );
	assert.equal( full.jobs.fast.with.ref, '${{ inputs.ref }}' );
	for ( const [ name, job ] of Object.entries( ci.jobs ) ) {
		for ( const step of job.steps ) {
			if ( step.uses?.startsWith( 'actions/checkout' ) ) {
				assert.equal( step.with?.ref, '${{ inputs.ref }}', name );
			}
		}
	}
} );

test( 'full.yml: push to develop starts only for files e2e depends on', () => {
	assert.deepEqual( full.on.push.branches, [ 'develop' ] );
	const paths = full.on.push.paths;
	for ( const path of [
		'theme/**',
		'tests/**',
		'tools/**',
		'package-lock.json',
		'composer.lock',
		'playwright.config.ts',
		'.github/workflows/**',
	] ) {
		assert.ok( paths.includes( path ), path );
	}
	assert.ok( ! paths.some( ( path ) => path.startsWith( 'docs' ) ) );
	assert.deepEqual( full.on.pull_request.types, [
		'labeled',
		'synchronize',
		'ready_for_review',
	] );
	assert.deepEqual( Object.keys( full.on.workflow_dispatch.inputs ).sort(), [
		'ref',
		'update_snapshots',
	] );
	assert.ok( 'workflow_call' in full.on );
} );

test( 'full.yml: e2e in the pinned Playwright container of `npm run test:visual`', () => {
	const pkg = JSON.parse(
		readFileSync( join( root, 'package.json' ), 'utf8' )
	);
	const { e2e } = full.jobs;
	assert.equal( e2e.container.image, VISUAL_IMAGE );
	assert.equal(
		e2e.container.image,
		`mcr.microsoft.com/playwright:v${ pkg.devDependencies[ '@playwright/test' ] }-noble`
	);
	assert.match( e2e.container.options, /--ipc=host/ );
	assert.equal( e2e[ 'runs-on' ], 'ubuntu-26.04' );
	assert.ok( e2e[ 'timeout-minutes' ] <= 20 );
	assert.deepEqual( full.permissions, { contents: 'read' } );
	// Browsers ship in the image.
	assert.ok(
		! e2e.steps.some( ( step ) =>
			String( step.run ).includes( 'playwright install' )
		)
	);
} );

test( 'full.yml: reports only on failure, baselines only on update, kept 7 days', () => {
	const uploads = full.jobs.e2e.steps.filter( ( step ) =>
		step.uses?.startsWith( 'actions/upload-artifact' )
	);
	assert.deepEqual(
		uploads.map( ( step ) => step.if ),
		[ 'failure()', '${{ inputs.update_snapshots }}' ]
	);
	for ( const step of uploads ) {
		assert.equal( step.with[ 'retention-days' ], 7 );
	}
	for ( const dir of [ 'playwright-report/', 'test-results/' ] ) {
		assert.ok( uploads[ 0 ].with.path.includes( dir ), dir );
	}
} );
