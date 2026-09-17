# Registro inicial de bugs e riscos

Inspeção estática em 17/09/2026 sobre o legado da base 4e96fcf. As referências abaixo usam
caminhos a partir da raiz e linhas dessa base. Não houve execução da aplicação ou tentativa
de exploração; todos os itens estão abertos, com reprodução automatizada pendente.
As prioridades orientam a migração, não representam pontuação CVSS ou impacto comprovado.

| ID | Prioridade | Evidência por inspeção | Critério de correção e regressão |
|---|---|---|---|
| SEC-001 | Alta | `docker-compose.yml:11` contém senha literal de root; `README.md:101`, `:113` e `:124` incluem valores com formato de credenciais. Validade externa não verificada. | Segredos gerados/montados, exemplos sintéticos e varredura redigida da árvore e histórico. Revogar valores reais se identificados. |
| SEC-002 | Alta | `app/Http/Controllers/API/SocialAuthController.php:85` coloca token e segredo do provedor na URL do frontend. | Callback termina a troca no backend; testar que Location, resposta e logs não contêm credenciais externas. |
| SEC-003 | Alta | `app/Services/SocialNetworkAccountService.php:131` encontra/cria usuário por e-mail e associa a identidade social. | Testar colisão de e-mail sem vínculo implícito; vinculação explícita exige sessão e autenticação recente. |
| SEC-004 | Alta | `app/Http/Controllers/API/RecipesController.php:122` usa usuário da rota; `:124` e `:160` persistem request completo. Não há autorização de propriedade nos métodos; a rota exige apenas autenticação. | Testar que usuário A não cria em nome de B, nem altera/remove receita de B; campos não permitidos nunca são persistidos. |
| SEC-005 | Alta | `app/Http/Resources/UserAuthResource.php:27` expõe remember_token explicitamente, apesar de oculto no model. | Respostas de autenticação contêm apenas dados públicos necessários; sessão usa cookie HttpOnly. Testar ausência de remember_token e tokens de provedor. |
| OPS-001 | Alta | `bootstrap.sh:10` gera chave a cada execução, `:14` usa migrate:fresh e `:32` concede escrita ampla em storage. | Reiniciar preserva dados e chave; reset é operação separada; validar privilégios mínimos e inicialização idempotente. |
| API-001 | Média | `routes/api.php:102` e `:103` não fornecem id; os métodos em `app/Http/Controllers/API/RecipesController.php:135` e `:171` exigem esse parâmetro. | Testar rota modernizada com identificador: sucesso autorizado, inexistente e acesso indevido; nenhum erro de parâmetro ausente. |
| URL-001 | Média | `app/Rules/YoutubeURL.php:17` acessa componentes sem verificar resultado de parse_url; `:24` aceita sufixo sem fronteira de hostname. | Testar URL vazia/malformada, domínio semelhante, userinfo, esquema e porta não permitidos; aceitar somente hosts HTTPS autorizados. |
| DATA-001 | Média | `database/factories/UserFactory.php:5` aponta App\\User; o model existente é App\\Users. | Factory modernizada cria usuário sintético persistível no banco de testes; sem senha fixa reutilizada fora do teste. |
| DATA-002 | Média | `app/Services/RecipeService.php:61` inicializa recipesArray somente no loop; `:79` usa a variável mesmo com entrada vazia. | Testar coleção vazia sem aviso/exceção e resultado vazio coerente com o contrato modernizado. |
| PDF-001 | Média | `app/Http/Controllers/RecipeController.php:37` e `:62` leem REMOTE_PORT diretamente; exceções de geração viram redirecionamento genérico. | Gerar documento sem depender de REMOTE_PORT; diferenciar receita inexistente de falha interna, sem expor detalhes sensíveis. |
| API-002 | Média | `app/Http/Controllers/API/RecipesController.php:38`, `:62` e `:92` lançam ModelNotFoundException em coleções vazias. | Listagens/buscas vazias retornam 200, coleção vazia e paginação; consulta por id inexistente mantém 404. |

## Limites e próximos passos

- A falha de rota API-001 interfere na reprodução HTTP de SEC-004. Corrigir a rota exige
  testar a autorização no mesmo fluxo; autenticação por si só não comprova propriedade.
- O hash constante em `database/factories/UserFactory.php:25` é uma fixture previsível.
  Isso não comprova exposição de senha real; será substituído por dados sintéticos na migração.
- Presença de arquivo/linha comprova apenas a observação de código. Cada correção precisa de
  teste que falhe pelo motivo correto antes da alteração e passe depois dela.
- Auditar também outros controllers, integrações, uploads, SQL, cascatas e restauração.
  Estes 12 itens não constituem auditoria completa dos 210 arquivos.
- Não registrar valores de segredos aqui. Revogação, remoção da árvore atual e limpeza
  coordenada do histórico são ações diferentes; nenhuma foi concluída por esta inspeção.
- Não executar o bootstrap legado para reproduzir problemas: ele pode apagar dados.
