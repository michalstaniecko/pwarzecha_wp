#!/usr/bin/env node
// Design System tokens.json → tokens.css (ADR 0009). No dependencies.
//
//   node tools/ds/tokens-css.mjs <tokens.json> [tokens.css]       compile (stdout without an output path)
//   node tools/ds/tokens-css.mjs --check <tokens.json> <tokens.css>  exit 0 if up to date, 1 with a diff if not
//   --name "<title>"  header name; defaults to tokens.json `name`

import { readFile, writeFile } from 'node:fs/promises';
import { compileTokensCss } from './compile.mjs';

const USAGE =
  'Usage: node tools/ds/tokens-css.mjs [--name <title>] <tokens.json> [tokens.css]\n' +
  '       node tools/ds/tokens-css.mjs [--name <title>] --check <tokens.json> <tokens.css>\n';

function parseArgs(argv) {
  const opts = { check: false, name: undefined, paths: [] };
  for (let i = 0; i < argv.length; i++) {
    const a = argv[i];
    if (a === '--check') opts.check = true;
    else if (a === '--name') opts.name = argv[++i];
    else if (a.startsWith('--')) return null;
    else opts.paths.push(a);
  }
  const [input, output] = opts.paths;
  if (input === undefined || opts.paths.length > 2 || (opts.check && output === undefined)) return null;
  return { ...opts, input, output };
}

// Line diff (LCS) between expected and actual text; only changed lines.
function diffLines(expected, actual) {
  const a = expected.split('\n');
  const b = actual.split('\n');
  const lcs = Array.from({ length: a.length + 1 }, () => new Array(b.length + 1).fill(0));
  for (let i = a.length - 1; i >= 0; i--) {
    for (let j = b.length - 1; j >= 0; j--) {
      lcs[i][j] = a[i] === b[j] ? lcs[i + 1][j + 1] + 1 : Math.max(lcs[i + 1][j], lcs[i][j + 1]);
    }
  }
  const out = [];
  let i = 0;
  let j = 0;
  while (i < a.length || j < b.length) {
    if (i < a.length && j < b.length && a[i] === b[j]) {
      i++;
      j++;
    } else if (j < b.length && (i === a.length || lcs[i][j + 1] >= lcs[i + 1][j])) {
      out.push(`+ ${b[j]}  (tokens.css line ${j + 1})`);
      j++;
    } else {
      out.push(`- ${a[i]}  (expected line ${i + 1})`);
      i++;
    }
  }
  return out;
}

async function main(argv) {
  const opts = parseArgs(argv);
  if (opts === null) {
    process.stderr.write(USAGE);
    return 2;
  }
  let css;
  let actual;
  try {
    css = compileTokensCss(await readFile(opts.input, 'utf8'), { name: opts.name });
    if (opts.check) actual = await readFile(opts.output, 'utf8');
  } catch (error) {
    // Unreadable or invalid input is a usage problem (2), not drift (1).
    const file = error.code === 'ENOENT' ? error.path : opts.input;
    process.stderr.write(`Cannot use ${file}: ${error.message}\n`);
    return 2;
  }

  if (opts.check) {
    if (actual === css) {
      process.stdout.write(`${opts.output} matches ${opts.input}\n`);
      return 0;
    }
    process.stderr.write(
      `${opts.output} does not match ${opts.input} (- expected from tokens.json, + in tokens.css):\n` +
        diffLines(css, actual).join('\n') +
        '\nRegenerate: node tools/ds/tokens-css.mjs ' +
        `${opts.input} ${opts.output}\n`,
    );
    return 1;
  }

  if (opts.output === undefined) process.stdout.write(css);
  else await writeFile(opts.output, css);
  return 0;
}

process.exitCode = await main(process.argv.slice(2));
