---
name: grill-with-docs
description: Esclarecer requisitos e decisões ainda ambíguas no Naturally antes de detalhar uma mudança de negócio ou arquitetura, registrando conclusões locais.
---

# Esclarecer decisões pendentes

Leia AGENTS.md, docs/architecture.md e docs/modernization/status.md. Recupere as decisões
já tomadas na conversa e no plano privado local, quando disponível. Não reabra escolhas
resolvidas sem evidência de conflito ou mudança explícita do usuário.

## Investigar antes de perguntar

- Localize o fluxo afetado, seus testes, schema e contratos. Resolva dúvidas factuais pelo
  código e documentação; diferencie comportamento atual, destino aprovado e hipótese.
- Identifique a dúvida que muda a solução: ator, permissão, regra, resultado observável
  ou restrição operacional. Apresente cenários concretos e suas consequências.
- Pergunte apenas o que permanece indefinido e é necessário para avançar. Prefira uma
  pergunta curta com alternativas quando houver escolhas claras. Não imponha uma entrevista
  completa a uma alteração que já possui requisitos suficientes.
- Continue o trabalho independente enquanto aguarda a resposta. Não transforme silêncio
  em decisão de negócio ou autorização para commit, push ou publicação.
- Se surgir contradição, mostre as duas evidências e explique o impacto. Resolva detalhes
  técnicos reversíveis com os padrões existentes; registre suposições relevantes.

## Registrar

Use a documentação existente: termos de negócio em docs/domain.md quando esse glossário
for necessário, contratos em docs/architecture.md e andamento em docs/modernization/status.md.
Registre um ADR apenas para trade-offs relevantes difíceis de reverter. Não crie arquivos
vazios nem copie o plano privado inteiro para um documento versionado.

Entregue decisões confirmadas, dúvidas restantes e consequência para o próximo incremento.
A skill não exige outra skill, tracker ou subagente. Aprovações continuam regidas por AGENTS.md.
Origem e licença: docs/agents/provenance.md.
