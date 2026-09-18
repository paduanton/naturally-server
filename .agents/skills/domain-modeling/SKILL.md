---
name: domain-modeling
description: Definir vocabulário, propriedade e invariantes de receitas e relações sociais do Naturally ao alterar regras de domínio e seus cenários de aceitação.
---

# Modelagem de domínio do Naturally

Leia docs/architecture.md e o fluxo legado relevante, incluindo testes e schema.
Consulte docs/modernization/inventory.json e docs/modernization/bugs.md para distinguir
comportamento existente, falha conhecida e regra desejada. Código inseguro não define o contrato.

## Modelagem

- Identifique os termos usados pelo produto e o módulo que é responsável por cada conceito
  (Identity, Recipes, Community ou Media). Evite renomeações que só troquem sinônimos.
- Descreva invariantes em linguagem de negócio: quem pode agir, sobre qual recurso,
  em qual estado e qual resultado deve permanecer verdadeiro depois da operação.
- Confronte regras com exemplos concretos: acesso a receita de outro autor, mudança de
  visibilidade, relação social duplicada, exclusão/restauração e ações concorrentes.
  Trate esses exemplos como perguntas de análise, não como funcionalidades já decididas.
- Separe fatos confirmados, hipóteses e dúvidas. Use decisões existentes para resolver
  dúvidas técnicas; peça esclarecimento apenas quando faltar uma decisão de negócio
  que altere o resultado. Não invente requisitos para preencher lacunas do legado.
- Mantenha invariantes independentes de Laravel. Decida quais exigem proteção adicional
  por constraints ou transações na infraestrutura para resistir a concorrência.
- Traduza cada regra alterada em cenários de aceitação, incluindo acesso negado e estados
  inválidos. Bugs confirmados exigem regressão que falhe antes da correção, conforme AGENTS.md.

## Registro

Atualize a documentação de domínio existente; se faltar um vocabulário necessário,
registre apenas os termos resolvidos em docs/domain.md. Crie um ADR em docs/adr/ somente
para uma decisão difícil de reverter, com alternativas reais e contexto que seria perdido.
Não crie documentos vazios nem registre hipóteses como decisões aceitas.

Entregue as regras, sua origem, o módulo responsável, dúvidas restantes e cenários de teste.
Essa modelagem não comprova implementação ou cobertura; siga os critérios de AGENTS.md.
Origem e licença: docs/agents/provenance.md.
