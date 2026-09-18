# Skills: origem e adaptação

Fonte: [mattpocock/skills](https://github.com/mattpocock/skills), commit fixo
`3cca18b368ae95cdbdebbff572ccafa662551015`. Licença MIT preservada integralmente em
[upstream-LICENSE.txt](upstream-LICENSE.txt), conferida com a origem em 17/09/2026.

## Escopo deste incremento

| Skill local | Arquivo original nesse commit | Adaptação |
|---|---|---|
| `.agents/skills/codebase-design/SKILL.md` | `skills/engineering/codebase-design/SKILL.md` | Contratos pequenos, injeção de dependências e testabilidade aplicados às camadas Laravel |
| `.agents/skills/domain-modeling/SKILL.md` | `skills/engineering/domain-modeling/SKILL.md` | Vocabulário, invariantes, cenários e decisões aplicados aos módulos Naturally |

As entradas foram reescritas com apoio de skill-creator e são autocontidas: não dependem
dos arquivos auxiliares do upstream, de scripts geradores, de trackers ou de subagentes.
Os demais rascunhos locais de skills não fazem parte desta entrega.

O projeto usa portas para proteger a direção de dependências mesmo quando existe uma
única implementação. Isso adapta a recomendação original de exigir múltiplos adapters.
A documentação segue os caminhos do Naturally; criar glossários e ADRs depende de uma
necessidade concreta. Não há criação automática de funcionalidades ou decisões de negócio.

## Uso e manutenção

Use `codebase-design` quando contratos ou responsabilidades de camadas mudarem; use
`domain-modeling` quando regras ou termos do negócio mudarem. Ambas seguem AGENTS.md
e docs/architecture.md. Não autorizam commits, pushes ou publicação e não exigem
confirmações já resolvidas. O protocolo de aprovação do repositório permanece aplicável.

Atualizações da origem devem ser revisadas manualmente, preservando licença e registrando
o novo commit de referência. Não substituir adaptações locais por uma atualização automática.
Validar o frontmatter não comprova comportamento do agente nem cobertura da aplicação.
