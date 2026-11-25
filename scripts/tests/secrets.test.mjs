import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { test } from 'node:test';
import { assertNoSourceIgnores } from '../check-secret-ignores.mjs';

const scanner = process.env.GITLEAKS_BIN || 'gitleaks';
const config = fileURLToPath(new URL('../../tools/gitleaks/config.toml', import.meta.url));
const guardScript = fileURLToPath(new URL('../check-secret-ignores.mjs', import.meta.url));

function scan(input, { ignoreComment = true, ignoreFingerprint, enforceSourceGuard = true } = {}) {
  const directory = mkdtempSync(join(tmpdir(), 'naturally-secrets-'));
  try {
    const report = join(directory, 'report.json');
    const args = ['stdin', '--config', config, '--redact=100', '--no-banner', '--no-color',
      '--gitleaks-ignore-path', join(directory, 'no-ignore'),
      '--report-format', 'json', '--report-path', report];
    if (ignoreComment) args.push('--ignore-gitleaks-allow');
    if (ignoreFingerprint) writeFileSync(join(directory, '.gitleaksignore'), ignoreFingerprint + '\n');
    if (enforceSourceGuard) {
      const guard = spawnSync(process.execPath, [guardScript, directory], {
        encoding: 'utf8', timeout: 15000,
      });
      assert.equal(guard.error, undefined, 'source validation must execute');
      if (guard.status !== 0) {
        assert.equal(guard.status, 2, 'source validation must reject unreviewed suppression');
        return { status: guard.status, output: guard.stdout + guard.stderr, report: '[]' };
      }
    }
    const result = spawnSync(scanner, args, {
      input, cwd: directory, encoding: 'utf8', timeout: 15000,
    });
    assert.equal(result.error, undefined, 'the pinned Gitleaks binary must be available');
    const output = result.stdout + result.stderr;
    assert.ok([0, 1].includes(result.status), 'scanner must finish with a valid scan result');
    return { status: result.status, output, report: readFileSync(report, 'utf8') };
  } finally {
    assert.equal(dirname(directory), resolve(tmpdir()), 'cleanup is restricted to the created temp directory');
    rmSync(directory, { recursive: true, force: true });
  }
}

test('the scanner version matches the reviewed release', () => {
  const result = spawnSync(scanner, ['version'], { encoding: 'utf8', timeout: 15000 });
  assert.equal(result.error, undefined, 'Gitleaks must be available');
  assert.equal(result.status, 0);
  assert.equal(result.stdout.trim(), '8.30.1');
});

test('source validation requires at least one source', () => {
  assert.throws(() => assertNoSourceIgnores([]), /At least one scan source/u);
});

test('documentation markers are accepted', () => {
  const result = scan('access_token = "<access-token>"\npassword = "<password>"');
  assert.equal(result.status, 0);
  assert.deepEqual(JSON.parse(result.report), []);
});

test('generated token values block the scan and are fully redacted', () => {
  const value = randomBytes(32).toString('hex');
  const result = scan(`access_token = "${value}"`);
  assert.equal(result.status, 1);
  const findings = JSON.parse(result.report);
  assert.ok(findings.some((finding) => finding.RuleID === 'generic-api-key'));
  assert.equal(result.output.includes(value), false, 'logs must not contain the generated value');
  assert.equal(result.report.includes(value), false, 'reports must not contain the generated value');
});

test('inline allow comments cannot bypass the gate', () => {
  const value = randomBytes(32).toString('hex');
  const input = `access_token = "${value}" # gitleaks:allow`;
  // The control confirms this fixture exercises the scanner suppression mechanism.
  assert.equal(scan(input, { ignoreComment: false }).status, 0);
  assert.equal(scan(input).status, 1);
});

test('an ambient ignore file cannot suppress a finding', () => {
  const value = randomBytes(32).toString('hex');
  const input = `access_token = "${value}"`;
  const ignoreFingerprint = JSON.parse(scan(input).report)[0].Fingerprint;
  assert.ok(ignoreFingerprint, 'the control finding must have a suppression fingerprint');
  assert.equal(scan(input, { ignoreFingerprint, enforceSourceGuard: false }).status, 0);
  const result = scan(input, { ignoreFingerprint });
  assert.equal(result.status, 2);
});

test('a generated provider token is detected without exposing its value', () => {
  const value = 'ghp_' + randomBytes(18).toString('hex');
  const result = scan(`github_token = "${value}"`);
  assert.equal(result.status, 1);
  assert.ok(JSON.parse(result.report).some((finding) => finding.RuleID === 'github-pat'));
  assert.equal(result.output.includes(value), false);
  assert.equal(result.report.includes(value), false);
});
