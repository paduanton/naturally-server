---
name: improve-codebase-architecture
description: Analisar acoplamento e organizar refatorações incrementais do legado Naturally, preservando contratos testados e rastreabilidade entre arquivos antigos e novos.
---

# Refatoração arquitetural incremental

Leia AGENTS.md, docs/architecture.md e docs/modernization/status.md. O alvo é o BFF
modular aprovado, com migração para runtime/; essa estrutura ainda não comprova que
os módulos estejam implementados. Use o inventário e o registro de bugs como evidências.

## Escolher e analisar o fluxo

- Parta do módulo ou problema indicado pelo usuário. Na ausência de um recorte explícito,
  selecione o próximo fluxo da ordem de migração. Histórico de mudanças pode ajudar a
  priorizar dentro dessa etapa, mas não justifica antecipar entregas dependentes.
- Trace entrada, chamadores, regras, persistência, saída e efeitos externos antes de mover
  arquivos. Identifique os testes existentes e os contratos que precisam permanecer válidos.
- Procure problemas concretos: regra repetida entre controllers, consulta em Resource,
  modelo Eloquent atravessando módulos ou operação espalhada sem responsável claro.
  Demonstre o custo ou risco no fluxo; quantidade de classes não mede qualidade por si só.
- Diferencie reorganização sem mudança de comportamento de correção funcional. Uma falha
  insegura não é requisito de compatibilidade; registre-a e reproduza-a antes da correção.
- Compare manter a estrutura com a menor mudança que concentre a responsabilidade.
  Não acrescente uma interface a cada classe nem remova uma porta necessária só porque
  ela tem uma implementação. A direção de dependências segue docs/architecture.md.

## Propor um incremento

Apresente o problema com arquivos e chamadores, destino das responsabilidades, contrato
observável, risco e evidências necessárias. Use um diagrama apenas quando ele ajudar a
entender as relações. Um relatório HTML, tracker ou subagente não é pré-requisito.

Escolha uma entrega verificável que caiba em um commit coerente. Separe upgrade, formatação
ampla e mudança de negócio quando forem responsabilidades independentes. Para contratos
compartilhados, introduza a forma nova e migre chamadores em lotes quando necessário para
manter os checks passando. Não remova o legado antes de demonstrar a substituição.

## Executar e comprovar

Quando a implementação estiver no escopo autorizado, teste o comportamento antes de alterar
sua estrutura. Reexecute os testes afetados depois e verifique os chamadores; mover arquivos
ou trocar namespaces sozinho não demonstra migração funcional. Se faltar runtime aceito,
registre o pré-requisito e prepare-o em incremento próprio antes de alegar paridade.

Atualize docs/modernization/inventory.json com destinos e referências de testes reais.
Só marque conclusão quando os critérios de aceite e a cobertura exigida por AGENTS.md
passarem, incluindo arquivos não executados e branches. Registre limitações no status.
A skill não autoriza commit, push ou deploy; cada incremento segue a aprovação de AGENTS.md.
Origem e licença: docs/agents/provenance.md.
