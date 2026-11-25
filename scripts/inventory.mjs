import { execFileSync } from 'node:child_process';
import { readFileSync, realpathSync, statSync } from 'node:fs';
import { isAbsolute, relative, resolve } from 'node:path';
import { parseArgs } from 'node:util';

// Module/layer assignments are proposals until the corresponding code is reviewed.
// This read-only check validates traceability, not whether referenced tests have passed.
const baseline = '4e96fcfd35ad5534f70352ba6b4a79c739b40849';
const root = realpathSync(resolve(import.meta.dirname, '..'));
const modules = new Set(['Identity', 'Recipes', 'Community', 'Media', 'Shared', 'Platform']);
const layers = new Set(['Domain', 'Application', 'Infrastructure', 'Http', 'Tooling']);
const actions = new Set(['adapt', 'refactor', 'replace', 'retain', 'remove', 'review-usage']);
const statuses = new Set(['pending', 'in-progress', 'complete']);

function evidence(path) {
  if (typeof path !== 'string' || !path || /[:\\]/.test(path) || isAbsolute(path)
      || path.split('/').some(segment => segment === '..' || segment === '.')) {
    throw new Error('Invalid evidence path. Use a repository-relative file path.');
  }
  const candidate = resolve(root, path);
  let actual;
  try {
    actual = realpathSync(candidate);
    if (!statSync(actual).isFile()) throw new Error();
  } catch { throw new Error('Evidence file not found: '+path); }
  const within = relative(root, actual);
  if (within === '..' || within.startsWith('../') || within.startsWith('..\\') || isAbsolute(within)) {
    throw new Error('Invalid evidence path. File resolves outside the repository.');
  }
}

function check() {
  const { values } = parseArgs({ options: {
    check: { type: 'boolean' }, file: { type: 'string' },
  } });
  const path = values.file ? resolve(values.file) : resolve(root, 'docs/modernization/inventory.json');
  const text = readFileSync(path, 'utf8');
  let entries;
  try { entries = JSON.parse(text); }
  catch { throw new Error('Invalid JSON in inventory.'); }
  if (!Array.isArray(entries)) throw new Error('Inventory must be an array.');
  const paths = execFileSync('git', ['ls-tree', '-rz', '--name-only', baseline],
    { cwd: root, encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }).split('\0').filter(Boolean);
  const expected = new Set(paths);
  const seen = new Set();
  for (const entry of entries) {
    if (!entry || typeof entry.path !== 'string') throw new Error('Invalid inventory entry.');
    if (seen.has(entry.path)) throw new Error('Duplicate path: '+entry.path);
    if (!expected.has(entry.path)) throw new Error('Path outside baseline: '+entry.path);
    seen.add(entry.path);
    for (const [field, allowed] of [['status', statuses], ['module', modules], ['layer', layers], ['action', actions]]) {
      if (!allowed.has(entry[field])) throw new Error('Invalid '+field+': '+entry.path);
    }
    if (!Array.isArray(entry.tests) || entry.tests.some(value => typeof value !== 'string' || !value.trim())) {
      throw new Error('Tests must be an array of nonempty evidence paths: '+entry.path);
    }
    if (entry.replacement !== null && typeof entry.replacement !== 'string') {
      throw new Error('Invalid replacement: '+entry.path);
    }
    if (entry.status === 'complete') {
      if (!entry.tests.length) throw new Error('Completion evidence is required: '+entry.path);
      if (entry.action === 'remove') {
        if (typeof entry.reason !== 'string' || !entry.reason.trim()) throw new Error('Removal rationale is required: '+entry.path);
      } else {
        if (!entry.replacement) throw new Error('Completion evidence requires a replacement: '+entry.path);
        evidence(entry.replacement);
      }
      entry.tests.forEach(evidence);
    }
  }
  for (const path of expected) if (!seen.has(path)) throw new Error('Missing baseline path: '+path);
  console.log('Legacy inventory: '+entries.length+' files; '+entries.filter(entry => entry.status === 'complete').length+' completed.');
}

try { check(); }
catch (error) {
  console.error(error.message);
  process.exitCode = 1;
}
