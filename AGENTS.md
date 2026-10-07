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

Development tool image (Docker with a Linux engine; run from the repository root):

- `docker build --target development -f docker/runtime/Dockerfile -t naturally-php-development:local docker/runtime` builds the PHP/Composer tool image without application source.
- `docker run --rm --network none --mount type=bind,source=./scripts/tests/runtime-image.php,target=/runtime-image.php,readonly naturally-php-development:local php /runtime-image.php` checks extensions, image formats, Composer, the default user and disabled Xdebug.
- `docker run --rm --network none -e XDEBUG_MODE=coverage --mount type=bind,source=./scripts/tests/runtime-image.php,target=/runtime-image.php,readonly naturally-php-development:local php /runtime-image.php --coverage` additionally checks real branch/path collection. It does not measure Laravel coverage or validate application startup.

Modern application dependencies (same tool image; run from the repository root):

- `docker run --rm --mount type=bind,source=./runtime,target=/app naturally-php-development:local composer install --no-plugins --no-scripts --no-interaction --prefer-dist` installs the committed lockfile without bootstrapping the application or running dependency scripts/plugins.
- `docker run --rm --network none --mount type=bind,source=./runtime,target=/app,readonly naturally-php-development:local composer validate --strict --no-plugins` checks the manifest and lockfile.
- `docker run --rm --network none --mount type=bind,source=./runtime,target=/app,readonly naturally-php-development:local composer check-platform-reqs --no-plugins` checks installed dependencies against the real PHP/extensions, rather than the resolution platform.
- `docker run --rm --mount type=bind,source=./runtime,target=/app,readonly naturally-php-development:local composer audit --locked --no-plugins` checks advisories and abandoned packages, including development dependencies.
- `docker run --rm --network none --mount type=bind,source=./runtime,target=/app,readonly naturally-php-development:local php vendor/bin/phpunit --version` checks the installed runner. Application tests, startup and coverage are not established by these dependency checks.

Modern runtime tests (same tool image and installed dependencies; run from the repository root):

- `docker run --rm --network none --mount type=bind,source=./runtime,target=/app,readonly naturally-php-development:local php vendor/bin/phpunit --no-coverage` runs the suites defined in runtime/phpunit.xml. Initially this tests the configuration loader, without Laravel startup or database/cache connections.
- `docker run --rm --network none -e XDEBUG_MODE=coverage --mount type=bind,source=./runtime,target=/app,readonly naturally-php-development:local php -d memory_limit=512M vendor/bin/phpunit --coverage-text` measures lines and branches with Xdebug. The source filter includes all PHP files in runtime/app, including unexecuted files; coverage ignores are disabled. Report each component separately until all application suites land. This does not establish coverage of the legacy application or completion of a functional module.
