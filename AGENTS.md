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
