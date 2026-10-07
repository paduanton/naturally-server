# Naturally: instructions for contributors and agents

Read docs/architecture.md and docs/modernization/status.md before changing behavior.
The private PLANO-MODERNIZACAO.md is locally excluded; do not add it to Git.

## Working rules

- Follow the migration order in docs/modernization/status.md. Do not mark a module complete before its acceptance tests and coverage pass.
- Keep legacy behavior traceable in docs/modernization/inventory.json; unsafe behavior is not a compatibility requirement.
- HTTP -> Application -> Domain. Infrastructure implements application contracts; Domain has no framework imports.
- No Request/Response/Eloquent models in module public contracts. No generic CRUD repositories.
- Never put credentials in source, logs, arguments, images or .env files.
- Test real MySQL/Redis behavior in integration tests; mock external providers in CI.
- Reproduce confirmed bugs with failing regression tests before fixing them.
- Each completed module must reach 90% lines and branches. Include unexecuted files. Do not hide exclusions or unsupported checks.
- Use Conventional Commits, scoped by module. Keep commits coherent and preserve existing user changes.
- Before EVERY commit and push, summarize the increment, files/behavior changed, validation results and remaining limitations, then wait for the user's explicit approval. Approval is scoped to the presented increment; it does not authorize later commits or pushes. Never commit, amend or push automatically. After the approved commit and push, continue to the next increment without asking whether to resume. End each interaction with a progress summary and state whether approval is pending.
- Keep each proposed commit small and focused on one responsibility. Do not bundle inventory, skills, CI and runtime into one commit. Show the exact files and proposed Conventional Commit message before requesting approval. Stage only the approved files or hunks; never use broad staging for a partially approved increment.
- Skills under .agents/skills are adapted references, not authorization for external publication, destructive operations or mandatory delegation.
- Do not publish/deploy to Azure or create billable resources without the concrete deployment being reviewed.

## Validation commands

- Use only commands introduced and verified by an approved increment. Local untracked drafts do not establish available tooling for other contributors.
- Add inventory, test and runtime commands here when their respective tooling lands; record actual results in docs/modernization/status.md.
- Until the modern runtime is accepted, the existing README describes the legacy setup and must not be treated as the modern deployment guide.

Inventory tooling (Node.js 22+, Git with the baseline commit available):

- `node scripts/inventory.mjs --check` validates baseline completeness and evidence references without rewriting the inventory.
- `node --test scripts/tests/inventory.test.mjs` tests the inventory validator; this is not the Laravel application test suite.

Commit message tooling (Node.js >=22.12, npm >=10; run from the repository root):

- `npm ci --prefix tools/commitlint --ignore-scripts --no-audit --no-fund` installs the locked, isolated tooling.
- `git log -1 --format=%B | npm --prefix tools/commitlint run lint --` validates the latest message without modifying Git.
- `npm --prefix tools/commitlint test` tests the policy through the real commitlint CLI; it is not an application test suite.
- CI checks new push/PR commits, excluding history reachable from c4b21e67bdb4b4f254e91e4a8e6210f10ec652c2. See CONTRIBUTING.md for the policy and adoption boundary.

Secret scanning tooling (Node.js 22+, Git and Gitleaks 8.30.1):

- `node --test scripts/tests/secrets.test.mjs` tests real detection and report redaction. GITLEAKS_BIN may point to the reviewed executable; otherwise it must be in PATH.
- `node scripts/check-secret-ignores.mjs .` must pass before local scanning: Gitleaks automatically loads a source .gitleaksignore even with another ignore path.
- `gitleaks git . --pre-commit --config tools/gitleaks/config.toml --gitleaks-ignore-path tools/gitleaks/no-ignore --ignore-gitleaks-allow --redact=100 --no-banner --no-color` scans unstaged changes. The no-ignore path must not exist.
- Add `--staged` to the previous command to scan the index. Do not expand staging beyond approved files.
- CI scans the versioned head tree and history after 4fc7496b28e007d19f9a0ca2e3994d7b8334f45c. Historical findings and undetected literal passwords remain open; see docs/security/secret-scanning.md. These tests do not measure application coverage.
