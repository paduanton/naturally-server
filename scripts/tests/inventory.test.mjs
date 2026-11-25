import { after, before, test } from 'node:test';
import assert from 'node:assert/strict';
import { mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { basename, dirname, join, resolve } from 'node:path';
import { spawnSync } from 'node:child_process';

const root = resolve(import.meta.dirname, '../..');
const source = join(root, 'docs/modernization/inventory.json');
let directory;
before(() => { directory = mkdtempSync(join(tmpdir(), 'naturally-inventory-')); });
after(() => {
  assert.equal(dirname(resolve(directory)), resolve(tmpdir()));
  assert.ok(basename(directory).startsWith('naturally-inventory-'));
  rmSync(directory, { recursive: true, force: true });
});

function check(transform, raw) {
  const entries = JSON.parse(readFileSync(source, 'utf8')).map(entry => ({
    ...entry, status: 'pending', replacement: null, tests: [],
  }));
  transform?.(entries);
  const path = join(directory, 'inventory.json');
  writeFileSync(path, raw ?? JSON.stringify(entries));
  const result = spawnSync(process.execPath,
    [join(root, 'scripts/inventory.mjs'), '--check', '--file', path],
    { cwd: directory, encoding: 'utf8' });
  assert.ifError(result.error);
  return result;
}

test('validates the entire baseline even when invoked from another directory', () => {
  const result = check();
  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /210 files; 0 completed/);
});

for (const [name, mutate, message] of [
  ['missing legacy file', entries => entries.pop(), /missing baseline path/i],
  ['duplicate file', entries => entries.push(entries[0]), /duplicate path/i],
  ['unrelated file', entries => entries[0].path = 'unrelated.php', /outside baseline/i],
  ['unknown status', entries => entries[0].status = 'done', /invalid status/i],
  ['unknown module', entries => entries[0].module = 'Unknown', /invalid module/i],
  ['unknown layer', entries => entries[0].layer = 'Unknown', /invalid layer/i],
  ['unknown action', entries => entries[0].action = 'Unknown', /invalid action/i],
  ['missing evidence', entries => entries[0].status = 'complete', /completion evidence/i],
  ['invalid tests type', entries => entries[0].tests = 'test.php', /tests must be an array/i],
  ['unsafe evidence path', entries => {
    Object.assign(entries[0], {status: 'complete', replacement: '../outside', tests: ['CONTRIBUTING.md']});
  }, /invalid evidence path/i],
  ['missing evidence file', entries => {
    Object.assign(entries[0], {status: 'complete', replacement: 'does-not-exist.php', tests: ['CONTRIBUTING.md']});
  }, /evidence file not found/i],
]) {
  test('rejects '+name, () => {
    const result = check(mutate);
    assert.equal(result.status, 1);
    assert.match(result.stderr, message);
  });
}

test('rejects malformed JSON', () => {
  const result = check(null, '{');
  assert.equal(result.status, 1);
  assert.match(result.stderr, /invalid JSON/i);
});

test('rejects a non-array inventory', () => {
  const result = check(null, '{}');
  assert.equal(result.status, 1);
  assert.match(result.stderr, /must be an array/i);
});

test('accepts a tracked completion with existing replacement and evidence', () => {
  const result = check(entries => {
    Object.assign(entries[0], {status: 'complete', replacement: 'CONTRIBUTING.md', tests: ['docs/architecture.md']});
  });
  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /1 completed/);
});

test('accepts removal only with a rationale and regression evidence', () => {
  const result = check(entries => {
    Object.assign(entries[0], {action: 'remove', status: 'complete', replacement: null,
      reason: 'Superseded by the reviewed configuration.', tests: ['docs/architecture.md']});
  });
  assert.equal(result.status, 0, result.stderr);
});

test('rejects removal without a rationale', () => {
  const result = check(entries => {
    Object.assign(entries[0], {action: 'remove', status: 'complete', replacement: null, tests: ['docs/architecture.md']});
  });
  assert.equal(result.status, 1);
  assert.match(result.stderr, /removal rationale/i);
});

test('checking never rewrites the supplied inventory', () => {
  check(entries => entries.reverse());
  const path = join(directory, 'inventory.json');
  const before = readFileSync(path, 'utf8');
  const result = spawnSync(process.execPath,
    [join(root, 'scripts/inventory.mjs'), '--check', '--file', path], { cwd: root, encoding: 'utf8' });
  assert.equal(result.status, 0, result.stderr);
  assert.equal(readFileSync(path, 'utf8'), before);
});
