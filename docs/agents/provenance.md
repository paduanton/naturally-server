# Skills: origem e adaptação

Fonte: [mattpocock/skills](https://github.com/mattpocock/skills), commit fixo
`3cca18b368ae95cdbdebbff572ccafa662551015`. Licença MIT preservada integralmente em
[upstream-LICENSE.txt](upstream-LICENSE.txt), conferida com a origem em 17/09/2026.

## Skills adaptadas

| Skill local | Arquivo original nesse commit | Adaptação |
|---|---|---|
| `.agents/skills/codebase-design/SKILL.md` | `skills/engineering/codebase-design/SKILL.md` | Contratos pequenos, injeção de dependências e testabilidade aplicados às camadas Laravel |
| `.agents/skills/domain-modeling/SKILL.md` | `skills/engineering/domain-modeling/SKILL.md` | Vocabulário, invariantes, cenários e decisões aplicados aos módulos Naturally |
| `.agents/skills/tdd/SKILL.md` | `skills/engineering/tdd/SKILL.md` | Ciclo de regressão, integrações reais e evidências de linhas e branches |
| `.agents/skills/diagnosing-bugs/SKILL.md` | `skills/engineering/diagnosing-bugs/SKILL.md` | Reprodução mínima, hipóteses verificáveis e correção rastreável |
| `.agents/skills/code-review/SKILL.md` | `skills/engineering/code-review/SKILL.md` | Revisão local de requisitos e padrões, com achados fundamentados |
| `.agents/skills/grill-with-docs/SKILL.md` | `skills/engineering/grill-with-docs/SKILL.md` | Esclarecimento proporcional às dúvidas restantes e registro de decisões |
| `.agents/skills/to-spec/SKILL.md` | `skills/engineering/to-spec/SKILL.md` | Especificação local com contratos e critérios de aceite |
| `.agents/skills/to-tickets/SKILL.md` | `skills/engineering/to-tickets/SKILL.md` | Tarefas locais com dependências e incrementos de uma responsabilidade |
| `.agents/skills/improve-codebase-architecture/SKILL.md` | `skills/engineering/improve-codebase-architecture/SKILL.md` | Refatoração por fluxo, evidências de acoplamento e rastreabilidade do legado |

As entradas foram reescritas com apoio de skill-creator e são autocontidas: não dependem
dos arquivos auxiliares do upstream, de scripts geradores, de trackers ou de subagentes.
Os demais rascunhos locais de skills não fazem parte desta entrega.

O projeto usa portas para proteger a direção de dependências mesmo quando existe uma
única implementação. Isso adapta a recomendação original de exigir múltiplos adapters.
A documentação segue os caminhos do Naturally; criar glossários e ADRs depende de uma
necessidade concreta. Não há criação automática de funcionalidades ou decisões de negócio.

Em testes, as fronteiras previstas na arquitetura dispensam confirmação a cada caso; o
ciclo inclui refactor com testes verdes. Asserções no banco são permitidas quando verificam
o contrato de persistência. Diagnóstico admite inspeção antes da reprodução, mas exige
regressão executada antes de corrigir; hipóteses não precisam de uma quantidade fixa.
A revisão usa referências e requisitos disponíveis na conversa e no repositório, sem
tracker obrigatório, publicação de comentários ou delegação automática.

No planejamento, o fluxo original de entrevista e publicação em trackers foi substituído
por investigação no repositório e documentação local. grill-with-docs é autocontida e
dispensa as chamadas obrigatórias a outras skills da entrada original. Especificações
não exigem listas extensas de histórias nem nova confirmação de fronteiras já aprovadas.
Tarefas de infraestrutura podem ser independentes de fluxos funcionais; a sequência
preserva as etapas do projeto e o protocolo de aprovação individual dos commits e pushes.

Na refatoração, a ordem de migração prevalece sobre uma seleção baseada apenas no histórico
de alterações. A análise conecta chamadores, contratos, responsabilidades e testes antes
de propor movimentações. Relatório HTML, entrevistas e subagentes não são obrigatórios;
a entrada não depende do scaffold HTML importado. Remoção de legado exige comprovar sua
substituição, e correções de segurança não são tratadas como compatibilidade a preservar.

## Uso e manutenção

### Skill específica do projeto: runtime-secrets

`.agents/skills/runtime-secrets/SKILL.md` é uma orientação própria do Naturally, não uma
cópia do repositório upstream. Deriva das decisões de configuração sem .env do projeto:
JSON não sensível, segredos montados, validação central, desenvolvimento independente
de Azure e rotação aplicada por rollout. Não indica que esse runtime já esteja entregue.

Referências oficiais conferidas em 22/09/2026:
[configuração Laravel](https://laravel.com/docs/13.x/configuration#configuration-caching),
[rotação de chaves Laravel](https://laravel.com/docs/13.x/encryption#gracefully-rotating-encryption-keys)
e [rotação do CSI no AKS](https://learn.microsoft.com/en-us/azure/aks/csi-secrets-store-configuration-options).
Os exemplos oficiais que usam .env devem ser adaptados ao contrato de arquivos do projeto.
A licença upstream preservada neste diretório corresponde às nove adaptações listadas acima.

### Seleção e atualização

Use `codebase-design` quando contratos ou responsabilidades de camadas mudarem; use
`domain-modeling` quando regras ou termos do negócio mudarem. Use `tdd` para implementar
comportamentos, `diagnosing-bugs` para investigar falhas e `code-review` para revisar
incrementos. Todas seguem AGENTS.md e docs/architecture.md. Não autorizam commits,
pushes ou publicação e não exigem
confirmações já resolvidas. O protocolo de aprovação do repositório permanece aplicável.

Use `grill-with-docs` para resolver ambiguidades relevantes, `to-spec` para consolidar
uma mudança discutida e `to-tickets` para dividi-la em entregas. Esses passos não precisam
ser executados em sequência quando o requisito ou a divisão já estiverem definidos.

Use `runtime-secrets` ao alterar configuração, startup ou rotação; não para tarefas que
apenas consomem contratos de configuração já estabelecidos.

Use `improve-codebase-architecture` para analisar e migrar um fluxo existente. Ela complementa
`codebase-design`, voltada à definição dos contratos, sem exigir uma chamada a outra skill.

Atualizações da origem devem ser revisadas manualmente, preservando licença e registrando
o novo commit de referência. Não substituir adaptações locais por uma atualização automática.
Validar o frontmatter não comprova comportamento do agente nem cobertura da aplicação.
