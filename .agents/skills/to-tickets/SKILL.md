---
name: to-tickets
description: Dividir uma especificação ou etapa do Naturally em tarefas locais pequenas, com dependências, critérios de aceite e commits incrementais.
---

# Decompor trabalho em incrementos

Leia a especificação ou solicitação de origem, AGENTS.md e docs/modernization/status.md.
Use a ordem de migração vigente e o trabalho já entregue para identificar o próximo passo;
não crie outro backlog completo quando uma atualização do existente resolver a necessidade.

## Divisão e sequência

- Cada tarefa entrega um resultado verificável com uma responsabilidade clara. Para código
  funcional, prefira um fluxo pequeno que atravesse as camadas necessárias e seus testes.
  Preparação de runtime, CI, documentação e skills pode ser uma entrega independente.
- Não agrupe responsabilidades distintas só porque pertencem à mesma etapa. Separe
  inventário, skills, CI e runtime conforme AGENTS.md. Não inclua o frontend de outro
  repositório como requisito de uma entrega deste BFF.
- Declare somente dependências reais, em ordem sem ciclos. Um item fica executável quando
  seus pré-requisitos estiverem concluídos com evidências, não apenas planejados.
- Para refactors amplos, avalie introduzir o novo contrato, migrar chamadores em lotes
  coerentes e remover o antigo depois da verificação. Cada lote deve manter os checks
  exigidos passando; não use a estratégia para preservar comportamento inseguro.
- Evite tarefas genéricas como 'refatorar tudo'. Se um item exige responsabilidades
  independentes ou não cabe em um commit revisável, divida-o por resultado.

## Registro local

Atualize o backlog existente ou escreva uma lista Markdown em docs/modernization/tasks/.
Para cada item, registre identificador, objetivo, origem, dependências, escopo provável,
critérios de aceite e evidências necessárias. Caminhos prováveis devem ser conferidos
na implementação; o resumo de aprovação terá os arquivos ou hunks exatos e a mensagem
Conventional Commit proposta. Não abra issues nem envie mensagens a serviços externos.

Não marque tarefas ou módulos como concluídos só porque a lista foi criada. Na execução,
atualize o inventário quando houver evidências; preserve a exigência de cobertura de
AGENTS.md. Aprovação do planejamento não autoriza commits ou pushes futuros. Depois de
cada commit e push aprovados, prepare o próximo incremento e solicite sua aprovação própria.
Origem e licença: docs/agents/provenance.md.
