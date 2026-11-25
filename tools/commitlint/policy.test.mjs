import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { test } from 'node:test';

const examples = [
  ['feature', 'feat(recipes): add recipe search', true],
  ['acronym', 'docs(security): describe BFF and AKS boundaries', true],
  ['breaking', 'feat(identity)!: change session contract\n\nBREAKING CHANGE: clients must use session cookies', true],
  ['missing scope', 'fix: reject invalid input', false],
  ['unknown scope', 'fix(payments): reject invalid input', false],
  ['multiple scopes', 'fix(identity,recipes): reject invalid input', false],
  ['slash scopes', 'fix(identity/recipes): reject invalid input', false],
  ['backslash scopes', 'fix(identity\\recipes): reject invalid input', false],
  ['unknown type', 'change(recipes): reject invalid input', false],
  ['empty subject', 'fix(recipes): ', false],
  ['missing breaking footer', 'feat(identity)!: change session contract', false],
  ['missing breaking marker', 'feat(identity): change session contract\n\nBREAKING CHANGE: clients must use session cookies', false],
  ['free text', 'update files', false],
  ['fixup', 'fixup! fix(recipes): reject invalid input', false],
  ['merge message', "Merge branch 'example'", false],
  ['long header', 'docs(docs): ' + 'a'.repeat(100), false],
  ['version', 'v1.2.3', false],
];

for (const [name, input, valid] of examples) {
  test(name, () => {
    const result = spawnSync(process.execPath, ['node_modules/@commitlint/cli/cli.js',
      '--config', 'commitlint.config.mjs', '--strict'], {
      cwd: import.meta.dirname, input, encoding: 'utf8', timeout: 15000,
    });
    assert.ifError(result.error);
    assert.equal(result.status, valid ? 0 : 3, `${result.stdout}${result.stderr}`);
  });
}
