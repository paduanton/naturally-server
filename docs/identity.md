# Autenticação do navegador no BFF

O contrato novo fica em `/api/v1/auth`. O cliente usa cookies de sessão de servidor,
sem tokens bearer, refresh tokens próprios ou credenciais no armazenamento do navegador.
Fortify executa cadastro, login, logout e confirmação de senha; Sanctum identifica a sessão
nas leituras protegidas. As rotas padrão dos pacotes e suas interfaces HTML estão desabilitadas.

## Fluxo e respostas

O navegador e o BFF compartilham a origem configurada em `app.url`, incluindo esquema,
host e porta. No desenvolvimento, a interface deverá encaminhar `/api` por um proxy na
mesma origem. Não habilitar CORS amplo para contornar essa fronteira. A configuração local
continua seguindo `docs/runtime.md`, com JSON público e segredos em volumes, sem `.env`.

Antes de escrever, chamar `GET /api/v1/auth/csrf-cookie` com cookies habilitados.
Enviar o cookie `XSRF-TOKEN`, decodificado, no cabeçalho `X-XSRF-TOKEN` das operações
de escrita. O cookie de sessão é `HttpOnly`, sem `Domain` compartilhado, `SameSite=Lax`
e `Secure` quando a URL configurada usa HTTPS. Produção exige HTTPS. Nunca copiar o
cookie de sessão para JavaScript, logs ou URLs.

Escritas recebem JSON. Respostas de autenticação e erros usam `Cache-Control: no-store`.

| Método e recurso | Entrada | Resultado |
| --- | --- | --- |
| `GET csrf-cookie` | Sem corpo | 204; inicializa cookies/CSRF |
| `POST register` | `name`, `username`, `email`, `password`, `password_confirmation` | 201 com `data.id`; inicia sessão |
| `POST login` | `email`, `password` | 204; regenera identificador da sessão |
| `GET me` | Sessão autenticada | 200 com `data`: `id`, `name`, `username`, `email`, `email_verified` |
| `POST logout` | Sessão e CSRF | 204; invalida a sessão atual e regenera CSRF |
| `DELETE sessions/others` | Sessão, CSRF e autenticação recente | 204; encerra outras sessões do mesmo usuário; repetível |
| `POST password/confirm` | `password`, sessão e CSRF | 204; renova a confirmação, sem renovar a validade absoluta da sessão |
| `PUT password` | `password`, `password_confirmation`, sessão, CSRF e autenticação recente | 204; troca a senha, invalida tokens de recuperação e todas as sessões do usuário; exige novo login |

O autor vem exclusivamente da sessão. Identificadores enviados no corpo ou na query não
selecionam outro usuário. O cadastro rejeita campos protegidos e não aceita remember-me.
O login inicial usa e-mail; login por username ainda não foi migrado.

E-mail e username são normalizados no domínio. Username mantém 4–20 caracteres ASCII
alfanuméricos, com no máximo um ponto ou underscore interno. A senha nova tem 15–1024
caracteres Unicode válidos, sem composição artificial, e é armazenada com Argon2id.
Contas apenas sociais não recebem senha local implicitamente por essas operações.

Sessões expiram após 120 minutos de inatividade e, independentemente da atividade,
12 horas desde a autenticação. Operações sensíveis exigem confirmação há menos de 15
minutos; timestamps ausentes ou futuros não autorizam a operação. Senha e revogação são
alteradas sob transação MySQL, verificando novamente a sessão atual para impedir uso de
uma sessão que tenha sido revogada durante a requisição.

## Falhas e limitação de tentativas

Erros seguem Problem Details, com `type`, `title`, `status`, `code` e `correlation_id`.
O identificador também aparece em `X-Request-ID`. Validação inclui apenas os nomes dos
campos e mensagens genéricas, sem refletir valores, SQL, senhas ou detalhes internos.

| Status | Significado |
| --- | --- |
| 401 | Sessão ausente, inválida ou expirada |
| 403 | Origem/host incompatíveis |
| 409 | Cadastro/login solicitado com sessão já autenticada |
| 415 | Escrita sem conteúdo JSON |
| 419 | Falha de CSRF |
| 422 | Entrada ou credencial inválida; conta desconhecida, senha incorreta e conta social têm a mesma forma de erro no login |
| 423 | `reauthentication_required`; confirmar a senha antes de repetir a operação sensível |
| 429 | Limite excedido; respeitar `Retry-After` |
| 503 | Redis indisponível durante a limitação; a autenticação não prossegue |

Redis limita cadastro a 5/minuto por IP; login a 5/minuto por combinação e-mail/IP e
30/minuto por IP; confirmação a 5/minuto por usuário/IP. As chaves usam HMAC e não
armazenam o identificador em claro. A política de IP depende de uma configuração revisada
de proxies confiáveis antes do AKS; não confiar em cabeçalhos encaminhados de qualquer origem.

## Limites desta migração

Este contrato cobre o fluxo local de credenciais e sessões. Ainda faltam verificação de
e-mail, recuperação HTTP, exclusão/restauração, vinculação e login social, autorização dos
demais módulos, agregação da cobertura de subprocessos e aceitação final do AKS.
O cadastro cria conta não verificada; `email_verified` informa esse estado e não deve ser
tratado como autorização para ações que futuramente exigirão verificação. Não há habilitação
de provedores externos, passkeys ou MFA neste incremento.

Os testes usam MySQL e Redis reais, CSRF ativo mesmo no ambiente de testes e um segundo
objeto de aplicação para verificar compartilhamento de sessão. Isso não substitui ensaios
de ingress, TLS, rotação de segredos e múltiplos pods no AKS. O módulo Identity permanece
em implementação; cobertura observada não certifica sua conclusão nem a release global.
