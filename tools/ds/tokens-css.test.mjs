import { test } from 'node:test';
import assert from 'node:assert/strict';
import { execFile } from 'node:child_process';
import { mkdtemp, writeFile, readFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { promisify } from 'node:util';

const script = new URL('./tokens-css.mjs', import.meta.url).pathname;
const run = (args) =>
  promisify(execFile)(process.execPath, [script, ...args]).then(
    ({ stdout, stderr }) => ({ code: 0, stdout, stderr }),
    (e) => ({ code: e.code, stdout: e.stdout, stderr: e.stderr }),
  );

const tokens = {
  name: 'T',
  color: { themes: [{ id: 'light' }], tokens: [{ name: 'base', value: '#fff' }, { name: 'ink', value: '#111' }] },
};
const expected =
  '/* T — generated from tokens.json */\n:root, [data-theme="light"] {\n  --base: #fff;\n  --ink: #111;\n}\n';

async function dir() {
  const d = await mkdtemp(join(tmpdir(), 'tokens-css-'));
  await writeFile(join(d, 'tokens.json'), JSON.stringify(tokens));
  return d;
}

test('compiles tokens.json into the given tokens.css', async () => {
  const d = await dir();
  const r = await run([join(d, 'tokens.json'), join(d, 'tokens.css')]);
  assert.equal(r.code, 0);
  assert.equal(await readFile(join(d, 'tokens.css'), 'utf8'), expected);
});

test('without an output path prints tokens.css to stdout', async () => {
  const d = await dir();
  const r = await run([join(d, 'tokens.json')]);
  assert.equal(r.code, 0);
  assert.equal(r.stdout, expected);
});

test('--check passes when tokens.css matches tokens.json', async () => {
  const d = await dir();
  await writeFile(join(d, 'tokens.css'), expected);
  const r = await run(['--check', join(d, 'tokens.json'), join(d, 'tokens.css')]);
  assert.equal(r.code, 0);
});

test('--check fails and lists the differing lines when tokens.css is stale', async () => {
  const d = await dir();
  await writeFile(join(d, 'tokens.css'), expected.replace('--ink: #111;', '--ink: #222;'));
  const r = await run(['--check', join(d, 'tokens.json'), join(d, 'tokens.css')]);
  assert.equal(r.code, 1);
  assert.match(r.stderr, /- {3}--ink: #111;/);
  assert.match(r.stderr, /\+ {3}--ink: #222;/);
  assert.doesNotMatch(r.stderr, /--base/);
});

test('wrong usage exits with 2 and prints usage', async () => {
  const r = await run(['--check', 'only-one.json']);
  assert.equal(r.code, 2);
  assert.match(r.stderr, /Usage/);
});

test('a missing or unreadable input exits with 2, not as drift', async () => {
  const d = await dir();
  const missing = await run(['--check', join(d, 'nope.json'), join(d, 'tokens.css')]);
  assert.equal(missing.code, 2);
  assert.match(missing.stderr, /nope\.json/);
  assert.doesNotMatch(missing.stderr, /at .*\.mjs:\d+/);
  await writeFile(join(d, 'broken.json'), '{ not json');
  const broken = await run([join(d, 'broken.json')]);
  assert.equal(broken.code, 2);
  assert.match(broken.stderr, /broken\.json/);
});
