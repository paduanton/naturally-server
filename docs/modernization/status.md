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

## Skills de planejamento

As entradas grill-with-docs, to-spec e to-tickets orientam a resolução de ambiguidades,
especificações locais e a divisão do trabalho em incrementos com dependências e critérios
de aceite. Preservam as decisões existentes, o plano privado e as aprovações individuais
de commit/push. O escopo contém essas três entradas, proveniência e este registro.
As três entradas passaram no quick_validate.py da skill-creator. O conteúdo foi revisado
contra a ordem de migração, a arquitetura e o protocolo de aprovação de AGENTS.md.
Esta validação estrutural não comprova comportamento de agentes nem cobertura da aplicação.
Não houve alteração funcional. Incremento aprovado e enviado em 526293b —
`docs(skills): adapt requirements and incremental planning guidance` (cinco arquivos).
O CI de inventário desse commit passou na
[execução 35726829157](https://github.com/paduanton/naturally-server/actions/runs/35726829157).

## Skill de refatoração arquitetural

A entrada improve-codebase-architecture orienta análise por fluxo, separação entre refactor
e correção de bugs e migração com evidências no inventário. É a nona adaptação de engenharia
prevista no plano; sua preparação não conclui a etapa 2, que ainda inclui skills específicas
e verificações pendentes. O incremento contém a entrada, proveniência e este registro.
Validação em 22/09/2026: a entrada passou no quick_validate.py da skill-creator; seu conteúdo
foi revisado contra AGENTS.md, arquitetura e ordem de migração. Essa validação estrutural
não comprova comportamento de agentes nem cobertura. Não houve refactor da aplicação
neste incremento documental. Incremento aprovado e enviado em 64dc770 —
`docs(skills): adapt incremental architecture refactoring guidance` (três arquivos).
O CI de inventário desse commit passou na
[execução 35761907792](https://github.com/paduanton/naturally-server/actions/runs/35761907792).

## Skill de configuração e segredos

A entrada runtime-secrets documenta o contrato sem .env, fontes por ambiente, validação
antes do bootstrap, proteção do cache de configuração e rotação com rollout e tratamento
específico para APP_KEY. É uma skill própria do projeto, com fontes oficiais registradas
na proveniência. O incremento contém a entrada, proveniência e este registro; não altera
o runtime, não gera credenciais nem executa deploy. Validação em 22/09/2026: passou no
quick_validate.py da skill-creator; conteúdo revisado contra o plano e documentação oficial.
Validação estrutural não demonstra startup, rotação real ou cobertura. Incremento aprovado
e enviado em 269e66c — `docs(skills): define runtime configuration and secrets guidance`
(três arquivos).
O CI de inventário desse commit passou na
[execução 35762385369](https://github.com/paduanton/naturally-server/actions/runs/35762385369).

## Skill de segurança do BFF

A entrada bff-security orienta sessões, CSRF, credenciais, autorização por recurso e login
social, com atenção a concorrência e regressões. Os limites e escolhas de autenticação
seguem o plano; as referências oficiais constam na proveniência. O incremento contém
a entrada, proveniência e este registro; não implementa nem valida endpoints de Identity.
Validação em 22/09/2026: passou no quick_validate.py da skill-creator; conteúdo revisado
contra o plano, arquitetura e fontes oficiais. Essa validação não comprova a segurança
da aplicação nem sua cobertura. Incremento aprovado e enviado em 4b0e0b9 —
`docs(skills): define BFF authentication and authorization guidance` (três arquivos).
O CI de inventário desse commit passou na
[execução 35919702170](https://github.com/paduanton/naturally-server/actions/runs/35919702170).

## Skill de cache e consistência

A entrada cache-consistency orienta cache de DTOs públicos, normalização de parâmetros,
TTLs, revisões transacionais, invalidação e recomputação concorrente. Distingue falhas
de cache da proteção contra abuso e exige testes reais de MySQL/Redis. O incremento
contém a entrada, proveniência e este registro. Não implementa cache nem mede desempenho.
Validação em 23/09/2026: a entrada passou no quick_validate.py da skill-creator; conteúdo
revisado contra o plano, arquitetura e referências oficiais. A validação estrutural não
comprova consistência, ganho de desempenho ou cobertura. Incremento aprovado e enviado
em a5dbd7c — `docs(skills): define public cache consistency guidance` (três arquivos).
O CI de inventário desse commit passou na
[execução 36514357593](https://github.com/paduanton/naturally-server/actions/runs/36514357593).

## Skill de entrega no AKS

A entrada aks-delivery orienta Bicep/Kustomize, identidades, imagem por digest, configuração
montada, migrations, probes, rollout e recuperação. Preserva a revisão de recursos/custos
antes de implantação real. O incremento contém a entrada, proveniência e este registro;
não cria manifests, pipelines de CD ou recursos Azure. Validação em 28/09/2026: passou
no quick_validate.py da skill-creator; conteúdo revisado contra o plano e referências
oficiais. Essa validação não comprova funcionamento do AKS, segurança do pipeline ou
cobertura da aplicação. Incremento aprovado e enviado em c4b21e6 —
`docs(skills): define AKS delivery and recovery guidance` (três arquivos).
O CI de inventário desse commit passou na
[execução 36514641566](https://github.com/paduanton/naturally-server/actions/runs/36514641566).

Com este incremento, as 13 entradas de skills previstas foram entregues. A etapa 2
continua em andamento: o CI aceito verifica inventário e, desde o incremento seguinte,
mensagens de commits. Os demais controles de qualidade continuam pendentes.

## Validação de Conventional Commits

O novo job commits usa commitlint 21.2.3 com dependências isoladas e lockfile em
tools/commitlint. Exige tipo permitido, um único escopo permitido, descrição e marcação
consistente de mudanças incompatíveis. CONTRIBUTING.md documenta o formato e os comandos;
AGENTS.md registra a validação. O incremento contém nove arquivos, restritos a essa política.

O job verifica mensagens no intervalo de push/PR e exclui o histórico já alcançável de
c4b21e67bdb4b4f254e91e4a8e6210f10ec652c2, preservando commits aprovados. Em PRs, usa
o head real; na criação de branches, considera o histórico posterior à base de adoção.
Mensagens de merge, fixup e versão não recebem dispensa. Referências inválidas ou ausentes
fazem o job falhar. A coerência do incremento e a descrição em inglês exigem revisão humana.

Validação local concluída em 29/09/2026, usando Node.js 24.19.0 e npm 10.9.4 isolados:

- Instalação reproduzida com npm ci e scripts de instalação desativados; npm audit sem
  vulnerabilidades nas dependências desta ferramenta, sem incluir os pacotes do legado.
- 17 testes da política passaram usando a CLI real de commitlint.
- Nove cenários do script Bash passaram em ensaio local: exclusão do histórico aprovado,
  seleção em push, criação de branch e PR, rejeição de referências inválidas/ausentes e
  falha por mensagem inválida. O ensaio usa commits existentes e não cria commits.
- Inventário validado: 210 arquivos, zero concluídos; seus 18 testes passaram.
- actionlint 1.7.12 sem erros; ShellCheck e Pyflakes não foram executados.

O novo job usa Node.js 22 no GitHub Actions. Incremento aprovado e enviado em 3ac5dff —
`ci(ci): enforce conventional commit messages` (nove arquivos). Os jobs de inventário e
commits passaram na [execução 36936156116](https://github.com/paduanton/naturally-server/actions/runs/36936156116).
Proteção de branch/rulesets não foi configurada. Não houve alteração
funcional, teste Laravel ou medição de cobertura da aplicação neste incremento.

## Credenciais nos exemplos do README

Auditoria local em 01/10/2026, com Gitleaks 8.30.1 verificado por SHA-256, sobre a árvore
versionada de 3ac5dff e o histórico disponível. Encontrou quatro ocorrências na árvore e
dez no histórico; ambas as execuções retornaram 1. Metadados, método e limites estão em
secrets-audit.md. Valores não foram utilizados para consultar provedores ou verificar validade.

O README troca oito valores de campos de senha/token e um Bearer por marcadores e identifica
os contratos como legado. A varredura do documento corrigido retornou 0, sem ocorrências.
A árvore exportada com os quatro arquivos propostos também passou na varredura; a
conferência adicional dos campos confirmou a substituição dos valores. Inventário válido:
210 arquivos, zero concluídos. Não houve teste Laravel ou medição de cobertura.
O incremento contém README.md, a auditoria, o registro de bugs e este progresso. SEC-001
permanece aberto; as regras padrão não detectam todas as senhas literais conhecidas.
Nenhuma revogação, reescrita de histórico, alteração de runtime ou configuração de scanner
no CI foi realizada neste incremento. Correção aprovada e enviada em 4fc7496 —
`fix(security): remove credentials from legacy examples` (quatro arquivos).
O CI passou na [execução 37556928127](https://github.com/paduanton/naturally-server/actions/runs/37556928127).

## Incremento em revisão: detecção de segredos no CI

O job secrets prepara Gitleaks 8.30.1 por download com SHA-256 fixado e executa testes de
detecção/redação. Examina a árvore versionada do head e os diffs do histórico posterior
a 4fc7496b28e007d19f9a0ca2e3994d7b8334f45c, inclusive merges. Falha por achados ou erros;
não aceita comentários de dispensa ou arquivo de ignore do checkout. Relatórios são
redigidos e não publicados como artifacts. Não cria baseline de supressão.

O incremento contém sete arquivos: workflow, configuração, bloqueio de ignore na origem,
testes Node, guia de varredura, AGENTS.md e este progresso. Mantém explícitos o recorte de adoção, as dez ocorrências
históricas e as senhas literais não detectadas. SEC-001 não está concluído. Proteção de
branch/rulesets e segurança do runtime permanecem pendentes.

Validação local em 06/10/2026: a regressão reproduziu o carregamento adicional de `.gitleaksignore`
da origem, mesmo com outro caminho explícito. O job agora rejeita esse arquivo antes de
executar o scanner. Sete testes passaram (versão, validação da origem,
marcadores, token genérico, token de provedor, redação e resistência a dispensa/ignore).
Seis cenários do Bash passaram usando commits existentes: árvore limpa, bloqueio de árvore
antiga, detecção em intervalo antigo com valores removidos, head inválido/ausente e base
ausente. A árvore exportada com os sete arquivos propostos passou na varredura. Inventário
válido com 210 arquivos e zero concluídos. Testes locais usaram Node.js 24.19.0 e Gitleaks
Windows x64; a execução Linux/Node.js 22 do CI ainda está pendente.
Os comandos locais de diff/staging retornaram 0; Git emitiu avisos de CRLF e acesso à sua
configuração global, apresentados pelo scanner como ERR. O staging estava vazio.
actionlint 1.7.12 sem erros; ShellCheck e Pyflakes não executados. A validação remota do novo job
depende da aprovação de commit/push. Não há medição de cobertura Laravel neste incremento.

## Estado técnico

As entregas aceitas compreendem documentação, verificador do inventário, seu CI e nove
skills de engenharia, runtime-secrets, bff-security, cache-consistency e aks-delivery.
O CI também valida mensagens de commits; os exemplos do README foram corrigidos.
A detecção de segredos no CI aguarda aprovação; o runtime possui rascunhos locais sem entrega
aceita na branch. A cobertura global de 90%
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
