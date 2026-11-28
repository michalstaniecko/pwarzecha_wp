/**
 * The e2e blueprint seeds the site with the same command as `npm run seed` (#54, ADR 0011).
 */
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { BLUEPRINT } from './server.mjs';

const steps = JSON.parse( readFileSync( BLUEPRINT, 'utf8' ) ).steps;

test( 'blueprint runs `wp pw seed --force` once', () => {
	const seeds = steps.filter(
		( step ) => step.step === 'wp-cli' && /\bpw seed\b/.test( step.command )
	);
	assert.deepEqual(
		seeds.map( ( step ) => step.command ),
		[ 'wp pw seed --force' ]
	);
} );

test( 'seed runs after the theme and every plugin are active', () => {
	const seed = steps.findIndex( ( step ) => step.step === 'wp-cli' );
	const activations = steps
		.map( ( step, index ) =>
			step.step.startsWith( 'activate' ) ? index : -1
		)
		.filter( ( index ) => index >= 0 );
	assert.ok( activations.length > 0 );
	assert.ok( activations.every( ( index ) => index < seed ) );
} );
