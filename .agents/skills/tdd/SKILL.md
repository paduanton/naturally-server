---
name: tdd
description: Implementar comportamento e corrigir bugs do Naturally com testes de regressão e aceitação, incluindo integração real e evidências de cobertura.
---

# Testes orientados a comportamento

Leia AGENTS.md, docs/architecture.md e docs/modernization/status.md. Use somente o
runtime e os comandos já aceitos; se faltar infraestrutura de teste, trate sua preparação
como incremento explícito antes de alegar que o comportamento está validado.

## Ciclo de implementação

- Escolha um cenário observável do incremento: regra de domínio, caso de uso, contrato
  HTTP, integração externa ou configuração de startup. Essas fronteiras já fazem parte
  da arquitetura aprovada; não peça nova confirmação para cada teste.
- Escreva um teste que demonstre o resultado esperado e execute-o antes da implementação.
  Confirme que ele falha pelo comportamento ausente ou incorreto, e não por erro de setup.
- Implemente o necessário para o cenário passar. Refatore mantendo os testes verdes e
  repita para o próximo comportamento, sem escrever uma suíte especulativa inteira.
- Asserções devem vir de regras e exemplos independentes da implementação. Evite testar
  métodos privados, apenas contagens de chamadas ou resultados recalculados pelo mesmo algoritmo.
- Teste persistência, constraints, transações e concorrência com MySQL real; comportamento
  de cache e locks com Redis real. Não substitua essas integrações por SQLite ou mocks.
  Provedores externos usam doubles controlados em CI; injete relógio e aleatoriedade quando
  necessário para tornar os cenários determinísticos. Use dados sintéticos e isolados.
- Verifique o resultado pela interface pública. Asserções diretas no banco são apropriadas
  quando persistência ou ausência de gravação indevida forem parte do contrato testado.
- Execute os testes afetados após o refactor e os checks exigidos pelo incremento. Documente
  falhas e limitações; não altere expectativas só para fazer um teste passar.

## Cobertura e conclusão

Para concluir um módulo, exija pelo menos 90% de linhas e de branches, incluindo arquivos
não executados. Use relatórios produzidos pelo runtime aceito com suporte real a branches
(Xdebug na arquitetura atual). Se só houver cobertura de linhas, branches permanece pendente.
Não esconda exclusões nem use a cobertura de um recorte como cobertura global do projeto.

Registre comandos, resultados e caminhos de evidências em docs/modernization/status.md
antes de propor conclusão no inventário. Testes de ferramentas Node não contam como testes
Laravel. Mudanças apenas documentais não precisam de testes que repitam seu texto.
Origem e licença: docs/agents/provenance.md. Aprovações seguem AGENTS.md.
