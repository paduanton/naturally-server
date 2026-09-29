---
name: cache-consistency
description: Implementar e revisar cache de leituras públicas do Naturally, com chaves normalizadas, invalidação transacional, concorrência e isolamento de dados pessoais.
---

# Cache e consistência das leituras públicas

Leia AGENTS.md, docs/architecture.md e docs/modernization/status.md. Antes de cachear,
verifique consultas, índices e paginação do fluxo. O banco e o armazenamento continuam
sendo fontes da verdade; Redis guarda conteúdo descartável, compartilhado entre réplicas.
Sessões e filas permanecem no MySQL conforme o plano.

## Escopo e chaves

- Use cache-aside em Infrastructure, por contratos de Application. Domain não conhece Redis.
  Cacheie DTOs públicos com campos permitidos, não models Eloquent ou Responses completos.
- Autenticação, recuperação e respostas pessoais ficam fora do cache compartilhado.
  Separe flags pessoais dos dados públicos antes de compor a resposta. Uma chave com ID
  do usuário não autoriza incluir dados privados nessa camada; use no-store nessas respostas.
- Valide e normalize filtros, ordenação e paginação antes de criar a chave. Inclua versão
  do contrato, revisão dos dados e todos os parâmetros que alterem o resultado. Equivalência
  de parâmetros deve preservar a semântica da consulta; não normalize valores distintos
  apenas para aumentar a taxa de acertos.
- Limite tamanho das entradas, resultados e cardinalidade. Não inclua cookies, tokens
  ou texto sensível em chaves, logs e labels de métricas. Uma coleção vazia válida pode
  ser cacheada; falhas de autenticação e erros externos não podem virar resultados normais.

## Expiração e invalidação

Política inicial do projeto: categorias/tipos de refeição por 24 horas; buscas, listagens
públicas e rankings por 60 segundos; detalhe público de receita por 5 minutos. Configure
TTLs limitados, com pequena variação documentada contra expirações simultâneas. Esses
valores não substituem invalidação após alterações nem autorização de leitura.

Mapeie mutações que afetam detalhe, listas e agregações, incluindo visibilidade, exclusão,
restauração e relações. Atualize revisões duráveis na mesma transação dos dados; operações
sobre Redis e outros efeitos externos ocorrem somente após commit. Um rollback não deve
publicar conteúdo ou revisão de uma escrita abortada.

A leitura precisa obter uma revisão confiável e dados coerentes com ela. Não dependa só de
um evento Redis após commit: uma falha nesse intervalo não pode manter uma versão obsoleta
como atual. Recomputação antiga não pode publicar dados antigos sob a revisão nova; teste
esse interleaving. Se não puder garantir a revisão, consulte a fonte sem preencher o cache.

Revalide visibilidade/permissão no caminho de leitura; TTL ou cópia antiga não concedem
acesso a um recurso que deixou de ser público. Documente a consistência oferecida e o
ponto de leitura considerado, sem prometer consistência forte apenas por usar cache-aside.
Veja as [limitações do padrão](https://learn.microsoft.com/en-us/azure/architecture/patterns/cache-aside).

## Concorrência e falhas

Use lock compartilhado por chave para limitar recomputação, com espera e duração finitas.
Depois de adquiri-lo, releia o cache. Libere apenas o lock do próprio detentor e trate
expiração durante o cálculo; lock de cache não substitui constraints/transações do banco.
Confira a API da versão instalada nos [locks Laravel](https://laravel.com/docs/13.x/cache#atomic-locks).

Falha de conexão, timeout ou gravação no cache permite consultar o banco com trabalho
limitado. Defina a resposta à contenção sem retries infinitos nem avalanche de consultas.
Isole essa política da proteção contra abuso: falha do limitador não libera login ilimitado.
Não mascare erros de banco ou defeitos de programação como simples cache miss.

## Evidências

Com MySQL e Redis reais, teste parâmetros equivalentes/distintos, acerto/miss, expiração,
isolamento de usuários, rollback, invalidação de detalhe/listas/agregações, mudança de
visibilidade, recomputação concorrente e indisponibilidade/retorno do Redis. Controle a
concorrência nos testes; sleeps isolados não demonstram a ordem dos eventos.

Meça taxa de acerto, consultas, latência e cardinalidade sem labels com dados pessoais.
Se houver cache HTTP adicional, documente seu contrato separadamente; no frontend futuro,
cache de sessão deve ser limpo no logout e consultas afetadas atualizadas após mutações.
Não implemente o frontend neste repositório. Conclusão e cobertura seguem AGENTS.md;
esta orientação não prova que o cache esteja entregue. Origem: docs/agents/provenance.md.
