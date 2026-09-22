# Progresso da modernização

Base inicial: 4e96fcf. Plano privado: PLANO-MODERNIZACAO.md (não versionado).
Não há consumidores/dados de produção a preservar.

## Protocolo de aprovação (15/09/2026)

Todo commit e push exige resumo e aprovação explícita do usuário para um incremento pequeno, de responsabilidade única, com arquivos exatos e mensagem Conventional Commit informados. Depois do commit/push aprovados, prosseguir automaticamente com o próximo incremento. Não reutilizar a aprovação para incrementos seguintes. Preparar somente os arquivos ou trechos aprovados.

## Incrementos aprovados

- 03b74aa — docs: define incremental commit approval policy. Somente CONTRIBUTING.md;
  commit e push para origin/codex/modernize-naturally aprovados e concluídos.
- b926784 — docs(architecture): define modular BFF boundaries. Somente docs/architecture.md;
  commit e push para origin/codex/modernize-naturally aprovados e concluídos.
- 9c6edd5 — docs(agents): define workflow and migration tracking. AGENTS.md e este registro;
  commit e push para origin/codex/modernize-naturally aprovados e concluídos.

## Inventário do legado

Base fixa: 4e96fcfd35ad5534f70352ba6b4a79c739b40849. O inventário enumera os 210 arquivos
versionados nessa base, inclusive arquivos de configuração, assets e documentação. Todos
permanecem pendentes. Módulo, camada e ação são destinos propostos, sujeitos à análise
de comportamento; o inventário ainda não representa uma auditoria funcional concluída.

O verificador é somente leitura. Rejeita omissões, duplicações, caminhos extras, estados
inválidos e conclusões sem referências de evidência existentes. Remoções exigem justificativa.
Ele não executa as evidências referenciadas, não certifica cobertura nem substitui revisão.
Requer Node.js 22+ e Git com o commit-base disponível; clones rasos precisam obter essa base.

Validação local em 17/09/2026: 18 testes do verificador passaram; 210 arquivos encontrados,
zero componentes concluídos. Comandos: `node scripts/inventory.mjs --check` e
`node --test scripts/tests/inventory.test.mjs`. Nenhum teste da aplicação Laravel é
contabilizado nesse resultado. Incremento aprovado e enviado em 5da8dd5 —
`chore(inventory): validate legacy migration tracking` (cinco arquivos do inventário).

## Registro inicial de bugs

docs/modernization/bugs.md registra 12 achados de inspeção estática, com referências
ao código e critérios de regressão. Nenhuma correção funcional ou reprodução da aplicação
foi concluída neste incremento. Credenciais não foram testadas nem reproduzidas no relatório.
Incremento aprovado e enviado em de0f47d — `docs(security): record initial legacy findings`
(registro de bugs e atualização de progresso).

## CI do inventário

.github/workflows/quality.yml executa a conferência do inventário e os testes do verificador
em pushes e pull requests. Usa Node.js 22, Ubuntu 24.04, histórico Git completo para acessar
a base, permissões somente de leitura e actions fixadas por SHA. O checkout não persiste
credenciais; o job tem limite de cinco minutos e execuções anteriores da mesma referência
são canceladas quando substituídas.

Validação local em 17/09/2026: actionlint 1.7.12 sem erros (arquivo oficial com SHA-256
conferido), inventário com 210 arquivos e zero concluídos, 18 testes passando. ShellCheck
e Pyflakes não foram executados. Incremento aprovado e enviado em c7e70ca —
`ci(inventory): run migration checks on pushes and pull requests` (workflow e progresso).
A [execução 35263717315](https://github.com/paduanton/naturally-server/actions/runs/35263717315)
desse commit terminou com sucesso no GitHub Actions.
Este CI inicial não executa a aplicação Laravel nem mede sua cobertura.

## Skills de arquitetura e domínio

As entradas codebase-design e domain-modeling foram adaptadas para contratos, camadas,
invariantes e cenários de aceitação do Naturally. docs/agents/provenance.md registra o
escopo, a origem e as diferenças; upstream-LICENSE.txt preserva a licença MIT original.
As duas entradas são autocontidas e não exigem os arquivos auxiliares dos rascunhos locais.
Validação local: ambas passaram no quick_validate.py da skill-creator; os cinco arquivos
propostos não apresentam espaços ao final das linhas. A licença foi conferida com a origem.
Revisão de conteúdo realizada contra a arquitetura e o protocolo de aprovação do projeto.
A validação estrutural não comprova comportamento de agentes; nenhum teste da aplicação
ou medição de cobertura faz parte deste incremento exclusivamente documental.
Incremento aprovado e enviado em 974ad53 —
`docs(skills): adapt architecture and domain modeling guidance` (cinco arquivos).
O CI de inventário desse commit passou na
[execução 35292931073](https://github.com/paduanton/naturally-server/actions/runs/35292931073).

## Skills de qualidade

As entradas tdd, diagnosing-bugs e code-review foram adaptadas para regressões anteriores
às correções, integrações reais, evidências de cobertura e revisão de requisitos e padrões.
São autocontidas e preservam o protocolo de aprovação; sua origem e adaptações constam
em docs/agents/provenance.md. O incremento contém as três entradas, a proveniência e este
registro de progresso. As três entradas passaram no quick_validate.py da skill-creator;
o conteúdo foi revisado contra a arquitetura e AGENTS.md. Essa validação estrutural
não comprova comportamento de agentes; não houve alteração funcional nem medição de
cobertura da aplicação. Incremento aprovado e enviado em 1c697a6 —
`docs(skills): adapt testing debugging and review guidance` (cinco arquivos).
O CI de inventário desse commit passou na
[execução 35295003977](https://github.com/paduanton/naturally-server/actions/runs/35295003977).

## Incremento em revisão: skills de planejamento

As entradas grill-with-docs, to-spec e to-tickets orientam a resolução de ambiguidades,
especificações locais e a divisão do trabalho em incrementos com dependências e critérios
de aceite. Preservam as decisões existentes, o plano privado e as aprovações individuais
de commit/push. O escopo contém essas três entradas, proveniência e este registro.
As três entradas passaram no quick_validate.py da skill-creator. O conteúdo foi revisado
contra a ordem de migração, a arquitetura e o protocolo de aprovação de AGENTS.md.
Esta validação estrutural não comprova comportamento de agentes nem cobertura da aplicação.
Não houve alteração funcional. O incremento aguarda aprovação para commit/push.

## Estado técnico

As entregas aceitas compreendem documentação, verificador do inventário, seu CI e cinco
skills de arquitetura, domínio e qualidade. As demais skills e o runtime ainda possuem rascunhos
locais sem entrega aceita na branch. A cobertura global de 90%
não foi atingida nem demonstrada. Nenhum módulo funcional está concluído.

## Etapas

| Etapa | Estado | Critério |
|---|---|---|
| 1 Inventário e segurança | Em andamento | Todos os arquivos classificados e bugs reproduzidos |
| 2 Convenções, skills e CI | Em andamento | Verificações reproduzíveis |
| 3 Runtime atual sem .env | Pendente | Startup validado e segredos persistentes |
| 4 Schema e módulos | Pendente | Constraints e arquitetura testadas |
| 5 Identity | Pendente | Sessão, recuperação, social e cobertura >=90% |
| 6 Recipes e Media | Pendente | Paridade e cobertura >=90% |
| 7 Community | Pendente | Paridade e cobertura >=90% |
| 8 Cache, filas e observabilidade | Pendente | Falhas e concorrência verificadas |
| 9 Remoção do legado | Pendente | Rastreabilidade completa e cobertura global >=90% |
| 10 AKS e CD | Pendente | Deploy, rotação e recuperação demonstrados |
| 11 Homologação | Pendente | Contrato e documentação de integração concluídos |

A implantação real requer assinatura Azure, domínio e aplicações OAuth configuradas.
Nenhum serviço externo será apresentado como homologado apenas por testes simulados.
