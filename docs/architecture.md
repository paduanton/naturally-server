# Arquitetura alvo

Este documento define o destino da modernização. Não indica que os módulos ou controles
descritos já estejam implementados. Cada entrega exige testes e evidência de conclusão.

Naturally é um BFF e monólito modular. Módulos: Identity, Recipes, Community e Media.
O frontend Angular não é implementado neste repositório.

Cada módulo usa Domain, Application, Infrastructure e Http. Domain contém invariantes puras.
Application expõe casos de uso e DTOs e define contratos necessários. Infrastructure implementa
persistência Eloquent, cache e serviços externos. Http valida entrada, autoriza e serializa respostas.
Models Eloquent não atravessam contratos entre módulos. Consultas retornam DTOs. Não criar
repositories genéricos nem interfaces que apenas espelham uma classe.

Controllers não executam regras de negócio; Form Requests validam entrada e Policies
verificam acesso. Casos de uso recebem DTOs, coordenam transações e não dependem de HTTP.
Efeitos externos são disparados após commit. Resources e views não executam consultas.
Regras e parsers de negócio ficam no Domain; parsing de HTTP fica em Http; parsing de
provedores fica nos adapters de Infrastructure. Compartilhar helpers apenas com uso real.

A nova aplicação será construída em runtime/ durante a transição; o código raiz permanece
como referência até ter substituição testada. A imagem de produção deverá incluir somente
a nova aplicação, sem copiar o legado, arquivos privados ou segredos.
A promoção à raiz acontecerá depois da paridade e da cobertura global, evitando publicar legado.

Sessão de servidor com Sanctum/Fortify, CSRF e cookies seguros; Socialite para provedores.
Sem refresh token próprio. Configuração JSON pública e arquivos de segredo; nunca .env.
Cache público separado de estado pessoal. Banco e armazenamento são fontes da verdade.

## Contratos

/api/v1; recursos plurais; PATCH parcial; PUT/DELETE idempotentes para relacionamentos.
Coleções vazias: 200. Paginação 20, máximo 100. Campos e ordenação em allowlist.
Problem Details (RFC 9457). Identidade vem da sessão e permissões são verificadas por recurso.

## Testes

Unitários para invariantes, HTTP para contratos, integração real MySQL/Redis para persistência
e concorrência, testes arquiteturais e operacionais. Cobertura final >=90% de linhas E branches,
por módulo e global. Código legado aparece separadamente durante a migração, nunca como
uma alegação de 90% global. Xdebug é necessário para branches.
