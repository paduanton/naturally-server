---
name: codebase-design
description: Definir ou revisar contratos e limites entre camadas dos módulos Laravel do Naturally ao planejar refactors e novos casos de uso.
---

# Contratos e camadas do Naturally

Leia AGENTS.md, docs/architecture.md e docs/modernization/status.md na raiz.
Inspecione os chamadores, a implementação e os testes do fluxo afetado antes de propor
uma abstração. Use o inventário para ligar o comportamento legado à substituição.

## Decisões de projeto

- Defina o módulo responsável (Identity, Recipes, Community ou Media), a operação que
  ele oferece e o que o chamador precisa conhecer: entrada, saída, falhas e permissões.
- Prefira contratos pequenos que concentrem regras e escondam detalhes de persistência.
  Se remover uma camada apenas eliminar encaminhamentos, reavalie sua necessidade.
- Http converte a entrada em DTOs, aplica validação e autorização e traduz o resultado
  para HTTP. Application coordena o caso de uso e suas transações. Domain decide as
  invariantes sem Laravel. Infrastructure implementa os contratos de acesso externo.
- Defina portas na Application quando forem necessárias para isolar persistência ou
  provedores. Uma segunda implementação não é pré-requisito para inverter dependências.
  Não crie interfaces para todas as classes nem repositories CRUD genéricos.
- Injete dependências. Contratos públicos não recebem Request, Response ou models Eloquent.
  Consultas devolvem DTOs; parsers de provedores pertencem aos respectivos adapters.
- Registre onde a transação começa e termina e quais efeitos precisam ocorrer após commit.
  Regras de negócio não devem depender de um controller para serem preservadas.

## Resultado esperado

Apresente o contrato proposto, a responsabilidade de cada camada alterada, os chamadores
impactados e os testes que demonstrarão o comportamento. Compare alternativas somente
quando houver um trade-off real. Valide regras pela interface pública do caso de uso;
integrações de persistência exigem MySQL/Redis reais conforme AGENTS.md.

Separe mudança funcional de reorganização e proponha um incremento coerente com a ordem
de migração. Limites de aprovação e critérios de conclusão continuam definidos em AGENTS.md.
Origem e licença: docs/agents/provenance.md.
