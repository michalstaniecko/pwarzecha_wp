/**
 * GitHub Actions expressions evaluated with GitHub's own engine (@actions/expressions), so tests can
 * check a workflow's `if:` and `concurrency` against sample events (#45). Test-only helper.
 */
import { Evaluator, Lexer, Parser, data } from '@actions/expressions';
import { truthy } from '@actions/expressions/result';

const STATUS = /\b(?:success|failure|cancelled|always)\s*\(/i;
const TEMPLATE = /\$\{\{(.*?)\}\}/gs;

/**
 * Job status functions as on a run that was not cancelled.
 *
 * @param {Record<string, {result: string}>} needs `needs` context.
 * @return {Map<string, import('@actions/expressions/funcs/info').FunctionDefinition>} Functions.
 */
function statusFunctions( needs ) {
	const results = Object.values( needs ).map( ( job ) => job.result );
	const fn = ( name, value ) => [
		name,
		{
			name,
			minArgs: 0,
			maxArgs: 0,
			call: () => new data.BooleanData( value ),
		},
	];
	return new Map( [
		fn( 'always', true ),
		fn( 'cancelled', false ),
		fn(
			'success',
			results.every( ( result ) => result === 'success' )
		),
		fn(
			'failure',
			results.some( ( result ) => result === 'failure' )
		),
	] );
}

/**
 * @param {string} expression Expression without `${{ }}`.
 * @param {Object} context    Plain object: `{ github, inputs, needs, … }`.
 * @return {import('@actions/expressions/data').ExpressionData} Result.
 */
function run( expression, context ) {
	const functions = statusFunctions( context.needs ?? {} );
	const { tokens } = new Lexer( expression ).lex();
	const ast = new Parser( tokens, Object.keys( context ), [
		...functions.values(),
	] ).parse();
	const dictionary = JSON.parse( JSON.stringify( context ), data.reviver );
	return new Evaluator( ast, dictionary, functions ).evaluate();
}

/**
 * A job/step `if:` – as GitHub does, `success() &&` is implied without a status function.
 *
 * @param {string|boolean} condition `if:` value (with or without `${{ }}`).
 * @param {Object}         context   Expression context.
 * @return {boolean} Whether the job/step runs.
 */
export function evaluate( condition, context ) {
	let expression = String( condition ).trim();
	const wrapped = expression.match( /^\$\{\{(.*)\}\}$/s );
	if ( wrapped ) {
		expression = wrapped[ 1 ];
	}
	if ( ! STATUS.test( expression ) ) {
		expression = `success() && (${ expression })`;
	}
	return truthy( run( expression, context ) );
}

/**
 * A string with `${{ }}` parts, e.g. a concurrency group.
 *
 * @param {string} template Value from the workflow.
 * @param {Object} context  Expression context.
 * @return {string} Interpolated string.
 */
export function interpolate( template, context ) {
	return template.replace( TEMPLATE, ( _, expression ) =>
		run( expression, context ).coerceString()
	);
}
