---
name: to-spec
description: Consolidar uma mudança já discutida do Naturally em especificação local com escopo, contratos e critérios de aceitação para implementação.
---

# Especificação de uma mudança

Parta da conversa e das decisões existentes. Leia AGENTS.md, docs/architecture.md e
docs/modernization/status.md; inspecione o fluxo afetado para separar estado atual de
comportamento solicitado. Não conduza outra entrevista quando o contexto já for suficiente.

Atualize a especificação existente ou crie um documento de escopo claro em
docs/modernization/specs/ somente quando necessário para a entrega. Mantenha o plano
PLANO-MODERNIZACAO.md privado e excluído do Git; não replique seu conteúdo integralmente.

## Conteúdo necessário

- Problema concreto, atores e resultado esperado, com exemplo de antes e depois quando útil.
- Escopo e limites da mudança. Inclua somente cenários sustentados pelos requisitos;
  não invente uma lista extensa de funcionalidades para preencher um modelo.
- Módulo responsável, contratos entre camadas e dependências de incrementos anteriores.
  Referencie os caminhos existentes quando ajudarem a rastrear o legado e confirme-os.
- Para mudanças HTTP: método, rota, entrada validada, saída, permissões, erros e paginação
  aplicáveis, seguindo os padrões REST aprovados. Não exponha detalhes internos no contrato.
- Decisões relevantes de persistência, transação, configuração, cache e efeitos externos
  apenas quando afetados. Dúvidas não resolvidas permanecem identificadas como dúvidas.
- Critérios de aceitação verificáveis, incluindo falhas e acesso negado quando pertinentes.
  Indique quais testes de domínio, aplicação, HTTP ou integração comprovam cada risco.
- Evidências necessárias para concluir: comandos aceitos, resultados esperados e cobertura
  exigida por AGENTS.md. Se o tooling ainda não existir, descreva-o como pré-requisito.

Mantenha a especificação proporcional ao incremento. Para mudanças documentais ou de
infraestrutura, use critérios próprios; não force histórias de usuário ou endpoints fictícios.
Não publique em trackers. O documento preparado não equivale a uma aprovação de commit,
push ou deploy; essas ações seguem o protocolo de AGENTS.md.
Origem e licença: docs/agents/provenance.md.
