#!/usr/bin/env node
/**
 * Logic behind `.github/workflows/ci.yml` (#44, ADR 0010), kept here so `npm run test:tools` covers it.
 *
 *   node tools/ci/ci.mjs plan            – job `changes`: env EVENT, BEFORE, FORCE_ALL → `all=`/`base=` lines.
 *   node tools/ci/ci.mjs verdict         – job `ci-ok`: env NEEDS (toJSON(needs)); exit 1 on a failed job.
 *   node tools/ci/ci.mjs playground-key  – actions/cache key for ~/.wordpress-playground.
 */
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { PHP_VERSION, WP_VERSION, root } from '../playground/site.mjs';

const NO_COMMIT = /^0+$/;

/**
 * What job `changes` compares against before dorny/paths-filter runs.
 *
 * @param {Object}  context
 * @param {string}  context.event            `github.event_name` (for `workflow_call`: the caller's event).
 * @param {string}  [context.before]         `github.event.before` (push); all zeros for a new tag/branch.
 * @param {boolean} [context.forceAll]       `inputs.all` of `workflow_call`.
 * @param {string}  [context.previousCommit] SHA of HEAD~1, '' when there is none.
 * @return {{all: boolean, base: string}} `all` – skip the filter and run every job;
 *   `base` – ref for paths-filter ('' on PRs: it diffs against the PR base via the API).
 */
export function changePlan( {
	event,
	before = '',
	forceAll = false,
	previousCommit = '',
} ) {
	if ( forceAll || event === 'workflow_dispatch' ) {
		return { all: true, base: '' };
	}
	if ( event === 'pull_request' ) {
		return { all: false, base: '' };
	}
	const base = before && ! NO_COMMIT.test( before ) ? before : previousCommit;
	return base ? { all: false, base } : { all: true, base: '' };
}

/**
 * A job skipped by a path filter is fine; a failed or cancelled one blocks the PR.
 *
 * @param {Record<string, {result: string}>} needs `toJSON(needs)` of the aggregating job.
 * @return {{ok: boolean, failed: string[]}} Verdict and the offending jobs.
 */
export function verdict( needs ) {
	const failed = Object.entries( needs )
		.filter(
			( [ , job ] ) => ! [ 'success', 'skipped' ].includes( job.result )
		)
		.map( ( [ name, job ] ) => `${ name } (${ job.result })` );
	return { ok: failed.length === 0, failed };
}

/**
 * The Playground CLI caches only the downloaded WordPress zip (in ~/.wordpress-playground);
 * PHP WASM ships inside node_modules.
 *
 * @return {string} Cache key, new for every WordPress, PHP or CLI version.
 */
export function playgroundCacheKey() {
	const cli = JSON.parse(
		readFileSync(
			join( root, 'node_modules/@wp-playground/cli/package.json' ),
			'utf8'
		)
	).version;
	return `playground-wp${ WP_VERSION }-php${ PHP_VERSION }-cli${ cli }`;
}

/**
 * @return {string} SHA of HEAD~1, '' in a shallow clone or on the first commit.
 */
function headParent() {
	try {
		return execFileSync(
			'git',
			[ 'rev-parse', '--verify', '-q', 'HEAD~1' ],
			{
				cwd: root,
				encoding: 'utf8',
				stdio: [ 'ignore', 'pipe', 'ignore' ],
			}
		).trim();
	} catch {
		return '';
	}
}

const commands = {
	plan() {
		const { all, base } = changePlan( {
			event: process.env.EVENT ?? '',
			before: process.env.BEFORE ?? '',
			forceAll: process.env.FORCE_ALL === 'true',
			previousCommit:
				process.env.EVENT === 'pull_request' ? '' : headParent(),
		} );
		process.stdout.write( `all=${ all }\nbase=${ base }\n` );
	},
	verdict() {
		const { ok, failed } = verdict(
			JSON.parse( process.env.NEEDS ?? '{}' )
		);
		process.stdout.write(
			ok ? 'All required jobs passed or were skipped.\n' : ''
		);
		if ( ! ok ) {
			process.stderr.write( `Failed: ${ failed.join( ', ' ) }\n` );
			process.exitCode = 1;
		}
	},
	'playground-key'() {
		process.stdout.write( `${ playgroundCacheKey() }\n` );
	},
};

if ( process.argv[ 1 ] === fileURLToPath( import.meta.url ) ) {
	const command = commands[ process.argv[ 2 ] ];
	if ( ! command ) {
		process.stderr.write(
			`Usage: node tools/ci/ci.mjs <${ Object.keys( commands ).join( '|' ) }>\n`
		);
		process.exit( 2 );
	}
	command();
}
